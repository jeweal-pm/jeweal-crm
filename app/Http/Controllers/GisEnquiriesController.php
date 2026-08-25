<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEnquiryRequest;
use App\Http\Requests\BulkDeleteEnquiryRequest;
use App\Http\Requests\EnquiryFilterRequest;
use App\Http\Requests\EnquiryReplyRequest;
use App\Http\Requests\GisEnquiryRequest;
use App\Http\Requests\UpdateSpamStatusRequest;
use App\Http\Requests\UpdateEnquiryStatusRequest;
use App\Http\Resources\GisEnquiryResource;
use App\Mail\EnquiryReply;
use App\Models\GisEnquiry;
use App\Models\User;
use App\Services\Spam\EnquirySpamScorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\Email\EnquiryEmailAutomationService;

class GisEnquiriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(EnquiryFilterRequest $request)
    {
        $this->authorize('viewAny', GisEnquiry::class);

        $query = $this->applyFilters(
            GisEnquiry::query()
                ->with(['assignedTo', 'deletedBy'])
                ->visibleTo(auth()->user()),
            $request
        );

        return view('administrator.enquiry.gis-list', [
            'data' => $query->paginate(25)->appends($request->validated()),
            'assignableUsers' => $this->assignableUsers(auth()->user()),
            'summary' => $this->summary(auth()->user()),
            'teamUsers' => $this->teamUsers(),
            'filters' => $request->validated(),
            'statusOptions' => [
                'lead_mql' => 'Lead / MQL',
                'sql' => 'SQL',
                'prospect' => 'Prospect',
                'customer' => 'Customer',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(GisEnquiryRequest $request)
    {
        $enquiry = GisEnquiry::create($request->validated());
        app(EnquirySpamScorer::class)->apply($enquiry);
        $enquiry->recordActivity('created');
        app(EnquiryEmailAutomationService::class)->dispatchFor($enquiry, 'gis');

        return response()->json([
            'status' => 'complete',
            'data' => new GisEnquiryResource($enquiry),
        ], 201);
    }

    public function filter(EnquiryFilterRequest $request)
    {
        $this->authorize('viewAny', GisEnquiry::class);

        $query = $this->applyFilters(
            GisEnquiry::query()->visibleTo($request->user()),
            $request
        );

        return GisEnquiryResource::collection($query->paginate(20));
    }

    public function assign(AssignEnquiryRequest $request, int $id)
    {
        $actor = $request->user();

        return DB::transaction(function () use ($actor, $id, $request) {
            $enquiry = GisEnquiry::query()
                ->visibleTo($actor)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('assign', $enquiry);

            $target = User::query()
                ->whereKey($request->validated('user_id'))
                ->where('is_active', true)
                ->firstOrFail();

            $targetRole = $target->primaryRoleName();
            abort_unless(in_array($targetRole, ['sale', 'sale_manager'], true), 422, 'Invalid assignment target.');
            abort_if(
                $targetRole === 'sale_manager' && ! $actor->hasCrmPermission('enquiry.assign.to_sale_manager'),
                403
            );
            abort_if(
                $targetRole === 'sale' && ! $actor->hasCrmPermission('enquiry.assign.to_sale'),
                403
            );

            $enquiry->assignTo($target, $actor);

            if (! $request->expectsJson()) {
                return redirect()
                    ->route('gisEnquiry', $this->filtersForAssignmentRedirect($request))
                    ->with('status', 'Assignee updated successfully.');
            }

            return new GisEnquiryResource($enquiry->refresh());
        });
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, int $id)
    {
        $actor = $request->user();

        return DB::transaction(function () use ($actor, $id, $request) {
            $enquiry = GisEnquiry::query()
                ->visibleTo($actor)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('updateStatus', $enquiry);
            $enquiry->changeStatus($request->validated('status'), $actor);

            if (! $request->expectsJson()) {
                return redirect()->back();
            }

            return new GisEnquiryResource($enquiry->refresh());
        });
    }

    public function bulkDelete(BulkDeleteEnquiryRequest $request)
    {
        $this->authorize('bulkDelete', GisEnquiry::class);

        DB::transaction(function () use ($request) {
            GisEnquiry::query()
                ->visibleTo($request->user())
                ->whereIn('id', $request->validated('ids'))
                ->lockForUpdate()
                ->get()
                ->each(fn (GisEnquiry $enquiry) => $enquiry->softDeleteBy($request->user()));
        });

        if (! $request->expectsJson()) {
            return redirect()->back();
        }

        return response()->json(['status' => 'complete']);
    }

    public function restore(Request $request, int $id)
    {
        $enquiry = GisEnquiry::withTrashed()
            ->visibleTo($request->user())
            ->whereKey($id)
            ->firstOrFail();

        $this->authorize('restore', $enquiry);
        $enquiry->restoreBy($request->user());

        if (! $request->expectsJson()) {
            return redirect()->back();
        }

        return new GisEnquiryResource($enquiry->refresh());
    }

    public function updateSpamStatus(UpdateSpamStatusRequest $request, int $id)
    {
        $enquiry = GisEnquiry::withTrashed()
            ->visibleTo($request->user())
            ->whereKey($id)
            ->firstOrFail();

        $this->authorize('restore', $enquiry);

        $enquiry->forceFill([
            'spam_status' => $request->validated('spam_status'),
            'spam_reviewed_by' => $request->user()->id,
            'spam_reviewed_at' => now(),
        ])->save();

        return redirect()->back();
    }

    public function reply(Request $request, int $id)
    {
        $enquiry = GisEnquiry::query()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('view', $enquiry);

        $name = trim($enquiry->first_name.' '.$enquiry->last_name);

        return view('administrator.enquiry.reply', [
            'enquiry' => $enquiry,
            'type' => 'gis',
            'backRoute' => route('gisEnquiry'),
            'sendRoute' => route('gis-enquiries.reply.send', $enquiry->id),
            'recipientName' => $name,
            'recipientEmail' => $enquiry->email,
            'subtitle' => $enquiry->inquiry ?: 'GIS enquiry',
            'subject' => $this->replySubject($enquiry),
            'body' => $this->defaultReplyBody($name, $request->user()),
        ]);
    }

    public function sendReply(EnquiryReplyRequest $request, int $id)
    {
        $enquiry = GisEnquiry::query()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('view', $enquiry);

        $validated = $request->validated();
        $name = trim($enquiry->first_name.' '.$enquiry->last_name);

        Mail::to($enquiry->email, $name)
            ->send(new EnquiryReply(
                $enquiry,
                'GIS enquiry',
                $validated['subject'],
                $validated['message'],
                $request->user()
            ));

        return redirect()
            ->route('gisEnquiry')
            ->with('status', 'Reply email sent successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $enquiry = GisEnquiry::query()
            ->visibleTo(request()->user())
            ->whereKey($id)
            ->firstOrFail();

        $this->authorize('delete', $enquiry);
        $enquiry->softDeleteBy(request()->user());

        if (! request()->expectsJson()) {
            return redirect()->back();
        }

        return response()->json(['status' => 'complete']);
    }

    private function applyFilters(Builder $query, EnquiryFilterRequest $request): Builder
    {
        $validated = $request->validated();

        if (($validated['trashed'] ?? null) && ! $request->user()->hasCrmPermission('enquiry.restore')) {
            abort(403);
        }

        if (($validated['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (($validated['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        $spam = $validated['spam'] ?? 'inbox';

        if ($spam === 'inbox') {
            $query->where('spam_status', EnquirySpamScorer::STATUS_CLEAN);
        }

        if ($spam === 'suspected') {
            $query->where('spam_status', EnquirySpamScorer::STATUS_SUSPECTED);
        }

        if ($spam === 'confirmed') {
            $query->where('spam_status', EnquirySpamScorer::STATUS_CONFIRMED);
        }

        if ($spam === 'not_spam') {
            $query->where('spam_status', EnquirySpamScorer::STATUS_NOT_SPAM);
        }

        $query
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['assigned_to'] ?? null, fn (Builder $query, int $userId) => $query->where('assigned_to', $userId))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['q'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            });

        $sort = $validated['sort'] ?? '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        return $query->orderBy($column, $direction);
    }

    private function filtersForAssignmentRedirect(Request $request): array
    {
        $query = [];
        parse_str(parse_url($request->headers->get('referer', ''), PHP_URL_QUERY) ?? '', $query);

        unset($query['assigned_to'], $query['page']);

        return array_intersect_key($query, array_flip([
            'status',
            'trashed',
            'date_from',
            'date_to',
            'sort',
            'q',
            'spam',
        ]));
    }

    private function assignableUsers(User $actor)
    {
        if (
            ! $actor->hasCrmPermission('enquiry.assign.to_sale')
            && ! $actor->hasCrmPermission('enquiry.assign.to_sale_manager')
        ) {
            return collect();
        }

        $roles = [];

        if ($actor->hasCrmPermission('enquiry.assign.to_sale')) {
            $roles[] = 'sale';
        }

        if ($actor->hasCrmPermission('enquiry.assign.to_sale_manager')) {
            $roles[] = 'sale_manager';
        }

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($roles) {
                $query->whereHas('primaryRole', fn (Builder $query) => $query->whereIn('name', $roles))
                    ->orWhereHas('roles', fn (Builder $query) => $query->whereIn('name', $roles));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'primary_role_id']);
    }

    private function teamUsers()
    {
        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereHas('primaryRole', fn (Builder $query) => $query->whereIn('name', ['sale', 'sale_manager']))
                    ->orWhereHas('roles', fn (Builder $query) => $query->whereIn('name', ['sale', 'sale_manager']));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function summary(User $user): array
    {
        $base = GisEnquiry::query()->visibleTo($user);

        return [
            'total' => (clone $base)->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)->count(),
            'unassigned' => (clone $base)->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)->whereNull('assigned_to')->count(),
            'customers' => (clone $base)->where('spam_status', EnquirySpamScorer::STATUS_CLEAN)->where('status', 'customer')->count(),
            'deleted' => (clone $base)->onlyTrashed()->count(),
            'suspected_spam' => (clone $base)->where('spam_status', EnquirySpamScorer::STATUS_SUSPECTED)->count(),
            'confirmed_spam' => (clone $base)->where('spam_status', EnquirySpamScorer::STATUS_CONFIRMED)->count(),
        ];
    }

    private function defaultReplyBody(string $name, User $user): string
    {
        return trim(sprintf(
            "Dear %s,\n\nThank you for your GIS enquiry. We have received your information and our team will follow up shortly.\n\nBest regards,\n%s",
            $name,
            $user->name
        ));
    }

    private function replySubject(GisEnquiry $enquiry): string
    {
        $inquiry = trim(str_replace('_', ' ', (string) $enquiry->inquiry));

        return 'Re: '.($inquiry ?: 'GIS enquiry');
    }
}
