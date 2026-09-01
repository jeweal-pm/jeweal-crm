<form
    id="{{ $bulkFormId }}"
    class="crm-bulk-bar"
    method="post"
    action="{{ $bulkActionRoute }}"
    data-bulk-form
    data-confirm="Apply this action to the selected records?"
    data-confirm-title="Confirm bulk action"
>
    @csrf
    <div class="crm-bulk-summary">
        <span class="crm-bulk-count" data-bulk-count>0 selected</span>
        <span class="crm-bulk-help">Select records on this page</span>
    </div>

    <div class="crm-bulk-controls">
        <select class="form-control form-control-sm" name="action" data-bulk-action required aria-label="Bulk action">
            <option value="">Choose action</option>
            @if(auth()->user()->hasCrmPermission('enquiry.bulk_delete'))
                <option value="delete">Delete selected</option>
            @endif
            @if(auth()->user()->hasCrmPermission('enquiry.restore'))
                <option value="restore">Restore selected</option>
            @endif
            @if($assignableUsers->isNotEmpty())
                <option value="assign">Assign selected</option>
            @endif
            @if(auth()->user()->hasCrmPermission('enquiry.update_status'))
                <option value="status">Change status</option>
            @endif
        </select>

        @if($assignableUsers->isNotEmpty())
            <div class="crm-bulk-target" data-bulk-target="assign" hidden>
                <select class="form-control form-control-sm" name="user_id" disabled aria-label="Assign selected records to">
                    <option value="">Select assignee</option>
                    @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if(auth()->user()->hasCrmPermission('enquiry.update_status'))
            <div class="crm-bulk-target" data-bulk-target="status" hidden>
                <select class="form-control form-control-sm" name="status" disabled aria-label="New status">
                    <option value="">Select status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <button class="btn btn-primary btn-sm crm-bulk-apply" type="submit" data-bulk-apply disabled>
            <i class="fas fa-play"></i> Apply
        </button>
    </div>

    <div class="crm-bulk-feedback" data-bulk-feedback role="status" aria-live="polite"></div>
</form>
