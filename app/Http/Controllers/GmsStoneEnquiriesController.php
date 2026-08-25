<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEnquiryRequest;
use App\Http\Requests\GmsStoneEnquiryFilterRequest;
use App\Http\Requests\GmsStoneEnquiryRequest;
use App\Http\Requests\GmsStoneEnquiryReplyRequest;
use App\Http\Requests\UpdateEnquiryStatusRequest;
use App\Http\Resources\GmsStoneEnquiryResource;
use App\Mail\GmsStoneEnquiryReply;
use App\Models\GmsStoneEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\Email\EnquiryEmailAutomationService;

class GmsStoneEnquiriesController extends Controller
{
    public function index(GmsStoneEnquiryFilterRequest $request)
    {
        $this->authorize('viewAny', GmsStoneEnquiry::class);

        $query = $this->applyFilters(
            GmsStoneEnquiry::query()
                ->with(['assignedTo'])
                ->visibleTo($request->user()),
            $request
        );

        if ($request->expectsJson()) {
            return GmsStoneEnquiryResource::collection($query->paginate(20));
        }

        return view('administrator.gms-enquiries.index', [
            'data' => $query->paginate(25)->appends($request->validated()),
            'assignableUsers' => $this->assignableUsers($request->user()),
            'teamUsers' => $this->teamUsers(),
            'filters' => $request->validated(),
            'summary' => $this->summary($request->user()),
            'statusOptions' => [
                'lead_mql' => 'Lead / MQL',
                'sql' => 'SQL',
                'prospect' => 'Prospect',
                'customer' => 'Customer',
            ],
        ]);
    }

    public function create()
    {
        return view('administrator.gms-enquiries.form', [
            'enquiry' => new GmsStoneEnquiry([
                'account_type' => 'personal',
                'country_code' => 'TH',
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(GmsStoneEnquiryRequest $request)
    {
        $enquiry = GmsStoneEnquiry::create($request->validated());
        $enquiry->recordActivity('created');
        app(EnquiryEmailAutomationService::class)->dispatchFor($enquiry, 'gms');

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'status' => 'complete',
                'data' => new GmsStoneEnquiryResource($enquiry),
            ], 201);
        }

        return redirect()
            ->route('gms-enquiries.index')
            ->with('status', 'GMS enquiry created successfully.');
    }

    public function show(Request $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::withTrashed()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('view', $enquiry);

        if ($this->shouldReturnJson($request)) {
            return new GmsStoneEnquiryResource($enquiry);
        }

        return view('administrator.gms-enquiries.show', [
            'enquiry' => $enquiry,
        ]);
    }

    public function reply(Request $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::query()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('view', $enquiry);

        return view('administrator.gms-enquiries.reply', [
            'enquiry' => $enquiry,
            'subject' => 'Re: GMS stone account request',
            'body' => $this->defaultReplyBody($enquiry, $request->user()),
        ]);
    }

    public function sendReply(GmsStoneEnquiryReplyRequest $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::query()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('view', $enquiry);

        $validated = $request->validated();

        Mail::to($enquiry->email, $enquiry->full_name)
            ->send(new GmsStoneEnquiryReply(
                $enquiry,
                $validated['subject'],
                $validated['message'],
                $request->user()
            ));

        $enquiry->forceFill(['is_seen' => true])->save();

        return redirect()
            ->route('gms-enquiries.show', $enquiry->id)
            ->with('status', 'Reply email sent successfully.');
    }

    public function edit(int $id)
    {
        return view('administrator.gms-enquiries.form', [
            'enquiry' => GmsStoneEnquiry::query()->findOrFail($id),
            'mode' => 'edit',
        ]);
    }

    public function update(GmsStoneEnquiryRequest $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::query()->findOrFail($id);
        $enquiry->update($request->validated());

        if ($this->shouldReturnJson($request)) {
            return new GmsStoneEnquiryResource($enquiry->refresh());
        }

        return redirect()
            ->route('gms-enquiries.index')
            ->with('status', 'GMS enquiry updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::query()
            ->visibleTo($request->user())
            ->findOrFail($id);

        $this->authorize('delete', $enquiry);
        $enquiry->delete();

        if ($this->shouldReturnJson($request)) {
            return response()->json(['status' => 'complete']);
        }

        return redirect()
            ->route('gms-enquiries.index')
            ->with('status', 'GMS enquiry moved to deleted records.');
    }

    public function restore(Request $request, int $id)
    {
        $enquiry = GmsStoneEnquiry::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $enquiry);
        $enquiry->restore();

        if ($this->shouldReturnJson($request)) {
            return new GmsStoneEnquiryResource($enquiry->refresh());
        }

        return redirect()
            ->route('gms-enquiries.index', ['trashed' => 'with'])
            ->with('status', 'GMS enquiry restored successfully.');
    }

    public function assign(AssignEnquiryRequest $request, int $id)
    {
        $actor = $request->user();

        return DB::transaction(function () use ($actor, $id, $request) {
            $enquiry = GmsStoneEnquiry::query()
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

            if (! $this->shouldReturnJson($request)) {
                return redirect()
                    ->route('gms-enquiries.index', $this->filtersForAssignmentRedirect($request))
                    ->with('status', 'Assignee updated successfully.');
            }

            return new GmsStoneEnquiryResource($enquiry->refresh());
        });
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, int $id)
    {
        $actor = $request->user();

        return DB::transaction(function () use ($actor, $id, $request) {
            $enquiry = GmsStoneEnquiry::query()
                ->visibleTo($actor)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('updateStatus', $enquiry);

            $status = $request->validated('status');
            $enquiry->changeStatus($status, $actor);

            if ($status === 'customer' && ! $enquiry->is_approved) {
                $enquiry->forceFill(['is_approved' => true])->save();
            }

            if (! $this->shouldReturnJson($request)) {
                return redirect()->back()->with('status', 'Status updated successfully.');
            }

            return new GmsStoneEnquiryResource($enquiry->refresh());
        });
    }

    private function applyFilters(Builder $query, GmsStoneEnquiryFilterRequest $request): Builder
    {
        $validated = $request->validated();

        if (($validated['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (($validated['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        $query
            ->when($validated['account_type'] ?? null, fn (Builder $query, string $type) => $query->where('account_type', $type))
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(array_key_exists('is_seen', $validated) && $validated['is_seen'] !== null, fn (Builder $query) => $query->where('is_seen', (bool) $validated['is_seen']))
            ->when(array_key_exists('is_approved', $validated) && $validated['is_approved'] !== null, fn (Builder $query) => $query->where('is_approved', (bool) $validated['is_approved']))
            ->when($validated['assigned_to'] ?? null, fn (Builder $query, int $userId) => $query->where('assigned_to', $userId))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['q'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone_number', 'like', "%{$keyword}%")
                        ->orWhere('company_name', 'like', "%{$keyword}%")
                        ->orWhere('business_name', 'like', "%{$keyword}%");
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
            'account_type',
            'status',
            'is_seen',
            'is_approved',
            'trashed',
            'date_from',
            'date_to',
            'sort',
            'q',
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
        $base = GmsStoneEnquiry::query()->visibleTo($user);

        return [
            'total' => (clone $base)->count(),
            'business' => (clone $base)->where('account_type', 'business')->count(),
            'unseen' => (clone $base)->where('is_seen', false)->count(),
            'approved' => (clone $base)->where('is_approved', true)->count(),
            'deleted' => (clone $base)->onlyTrashed()->count(),
        ];
    }

    private function defaultReplyBody(GmsStoneEnquiry $enquiry, User $user): string
    {
        return trim(sprintf(
            "Dear %s,\n\nThank you for your GMS stone account request. We have received your information and our team will review it shortly.\n\nBest regards,\n%s",
            $enquiry->full_name,
            $user->name
        ));
    }

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson() || $request->is('api/*');
    }
}
