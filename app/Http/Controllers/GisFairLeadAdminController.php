<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEnquiryRequest;
use App\Http\Requests\GisFairLeadFilterRequest;
use App\Http\Requests\UpdateEnquiryStatusRequest;
use App\Models\GisFairCampaign;
use App\Models\GisFairLead;
use App\Models\User;
use App\Services\GisFair\GisFairLeadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GisFairLeadAdminController extends Controller
{
    public function index(GisFairLeadFilterRequest $request)
    {
        $this->authorize('viewAny', GisFairLead::class);
        $filters = $request->validated();
        if (($filters['trashed'] ?? null) && ! $request->user()->hasCrmPermission('enquiry.restore')) {
            abort(403);
        }
        $query = GisFairLead::query()
            ->with(['campaign', 'trackingLink', 'assignedTo'])
            ->visibleTo($request->user());

        if (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        } elseif (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        $query
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, int $id) => $query->where('campaign_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when(($filters['marketing_consent'] ?? null) === 'yes', fn (Builder $query) => $query->where('marketing_consent', true))
            ->when(($filters['marketing_consent'] ?? null) === 'no', fn (Builder $query) => $query->where('marketing_consent', false))
            ->when($filters['q'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $query) use ($keyword) {
                    $query->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('company', 'like', "%{$keyword}%")
                        ->orWhere('fair_code', 'like', "%{$keyword}%");
                });
            });

        $visible = GisFairLead::query()->visibleTo($request->user());

        return view('administrator.gis-fair.leads.index', [
            'leads' => $query->latest('last_submitted_at')->paginate(25)->appends($filters),
            'campaigns' => GisFairCampaign::query()->latest('id')->get(),
            'sources' => config('gis_fair.sources'),
            'filters' => $filters,
            'assignableUsers' => $this->assignableUsers($request->user()),
            'summary' => [
                'total' => (clone $visible)->count(),
                'unassigned' => (clone $visible)->whereNull('assigned_to')->count(),
                'customers' => (clone $visible)->where('status', 'customer')->count(),
                'marketing' => (clone $visible)->where('marketing_consent', true)->count(),
            ],
            'statusOptions' => ['lead_mql' => 'Lead / MQL', 'sql' => 'SQL', 'prospect' => 'Prospect', 'customer' => 'Customer'],
        ]);
    }

    public function show(Request $request, int $lead)
    {
        $lead = GisFairLead::withTrashed()
            ->visibleTo($request->user())
            ->findOrFail($lead);
        $this->authorize('view', $lead);

        return view('administrator.gis-fair.leads.show', [
            'lead' => $lead->load(['campaign', 'trackingLink', 'assignedTo', 'submissions' => fn ($query) => $query->latest('submitted_at')]),
        ]);
    }

    public function assign(AssignEnquiryRequest $request, GisFairLead $lead)
    {
        $this->authorize('assign', $lead);
        $target = User::query()->whereKey($request->validated('user_id'))->where('is_active', true)->firstOrFail();
        $targetRole = $target->primaryRoleName();
        abort_unless(in_array($targetRole, ['sale', 'sale_manager'], true), 422, 'Invalid assignment target.');
        abort_if($targetRole === 'sale' && ! $request->user()->hasCrmPermission('enquiry.assign.to_sale'), 403);
        abort_if($targetRole === 'sale_manager' && ! $request->user()->hasCrmPermission('enquiry.assign.to_sale_manager'), 403);
        $lead->assignTo($target, $request->user());

        return redirect()->back()->with('status', 'Fair lead assignee updated.');
    }

    public function updateStatus(UpdateEnquiryStatusRequest $request, GisFairLead $lead)
    {
        $this->authorize('updateStatus', $lead);
        $lead->changeStatus($request->validated('status'), $request->user());

        return redirect()->back()->with('status', 'Fair lead status updated.');
    }

    public function resend(Request $request, GisFairLead $lead, GisFairLeadService $service)
    {
        $this->authorize('view', $lead);
        abort_unless($request->user()->hasCrmPermission('funnel.message.manage'), 403);
        $service->resendConfirmation($lead, $request->user());

        return redirect()->back()->with('status', 'Fair code confirmation queued for resend.');
    }

    public function withdrawMarketing(Request $request, GisFairLead $lead)
    {
        $this->authorize('updateStatus', $lead);
        $lead->forceFill([
            'marketing_consent' => false,
            'marketing_consent_withdrawn_at' => now(),
            'last_updated_by' => $request->user()->id,
        ])->save();
        $lead->recordActivity('status_changed', $request->user(), [
            'activity' => 'marketing_consent_withdrawn',
        ], $lead->status, $lead->status);

        return redirect()->back()->with('status', 'Marketing consent withdrawn.');
    }

    public function destroy(Request $request, GisFairLead $lead)
    {
        $this->authorize('delete', $lead);
        $lead->softDeleteBy($request->user());

        return redirect()->route('gis-fair.leads.index')->with('status', 'Fair lead moved to deleted records.');
    }

    public function restore(Request $request, int $lead)
    {
        $model = GisFairLead::withTrashed()->visibleTo($request->user())->findOrFail($lead);
        $this->authorize('restore', $model);
        $model->restoreBy($request->user());

        return redirect()->back()->with('status', 'Fair lead restored.');
    }

    private function assignableUsers(User $actor)
    {
        if (! $actor->hasCrmPermission('enquiry.assign.to_sale') && ! $actor->hasCrmPermission('enquiry.assign.to_sale_manager')) {
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
}
