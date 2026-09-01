<style>
    .app-confirm-overlay {
        align-items: center;
        background: rgba(15, 23, 42, .52);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 20px;
        position: fixed;
        z-index: 2050;
    }

    .app-confirm-overlay.is-open {
        display: flex;
    }

    .app-confirm-dialog {
        background: #fff;
        border: 1px solid #d9e0e9;
        border-radius: 8px;
        box-shadow: 0 24px 64px rgba(15, 23, 42, .24);
        max-width: 440px;
        overflow: hidden;
        width: 100%;
    }

    .app-confirm-head {
        align-items: center;
        border-bottom: 1px solid #e7ebf1;
        display: flex;
        justify-content: space-between;
        padding: 15px 18px;
    }

    .app-confirm-title {
        color: #172033;
        font-size: 16px;
        font-weight: 700;
        margin: 0;
    }

    .app-confirm-close {
        align-items: center;
        background: transparent;
        border: 0;
        color: #667085;
        display: inline-flex;
        height: 32px;
        justify-content: center;
        padding: 0;
        width: 32px;
    }

    .app-confirm-body {
        color: #475467;
        font-size: 14px;
        line-height: 1.55;
        padding: 20px 18px;
    }

    .app-confirm-actions {
        align-items: center;
        background: #f8fafc;
        border-top: 1px solid #e7ebf1;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        padding: 12px 18px;
    }
</style>

<div class="app-confirm-overlay" data-app-confirm-overlay aria-hidden="true">
    <div class="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message">
        <div class="app-confirm-head">
            <h2 class="app-confirm-title" id="app-confirm-title" data-app-confirm-title>Confirm action</h2>
            <button class="app-confirm-close" type="button" data-app-confirm-cancel title="Close confirmation">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="app-confirm-body" id="app-confirm-message" data-app-confirm-message></div>
        <div class="app-confirm-actions">
            <button class="btn btn-outline-secondary" type="button" data-app-confirm-cancel>Cancel</button>
            <button class="btn btn-primary" type="button" data-app-confirm-submit>Confirm</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.querySelector('[data-app-confirm-overlay]');
        var title = overlay.querySelector('[data-app-confirm-title]');
        var message = overlay.querySelector('[data-app-confirm-message]');
        var confirmButton = overlay.querySelector('[data-app-confirm-submit]');
        var cancelButtons = overlay.querySelectorAll('[data-app-confirm-cancel]');
        var pendingForm = null;
        var pendingSubmitter = null;
        var previousFocus = null;

        function bulkItems(form) {
            return Array.prototype.slice.call(document.querySelectorAll('input[data-bulk-item][form="' + form.id + '"]'));
        }

        function updateBulkForm(form) {
            var items = bulkItems(form);
            var selected = items.filter(function (item) { return item.checked; });
            var master = document.querySelector('[data-bulk-select-all="' + form.id + '"]');
            var action = form.querySelector('[data-bulk-action]');
            var apply = form.querySelector('[data-bulk-apply]');
            var count = form.querySelector('[data-bulk-count]');
            var feedback = form.querySelector('[data-bulk-feedback]');

            count.textContent = selected.length + ' selected';
            apply.disabled = selected.length === 0 || !action.value;
            feedback.textContent = '';

            if (master) {
                master.checked = items.length > 0 && selected.length === items.length;
                master.indeterminate = selected.length > 0 && selected.length < items.length;
            }

            items.forEach(function (item) {
                var row = item.closest('tr');
                if (row) {
                    row.classList.toggle('crm-row-selected', item.checked);
                }
            });

            form.querySelectorAll('[data-bulk-target]').forEach(function (target) {
                var active = target.getAttribute('data-bulk-target') === action.value;
                target.hidden = !active;
                target.querySelectorAll('select, input').forEach(function (control) {
                    control.disabled = !active;
                    control.required = active;
                });
            });
        }

        document.querySelectorAll('[data-bulk-form]').forEach(function (form) {
            var master = document.querySelector('[data-bulk-select-all="' + form.id + '"]');

            if (master) {
                master.addEventListener('change', function () {
                    bulkItems(form).forEach(function (item) { item.checked = master.checked; });
                    updateBulkForm(form);
                });
            }

            bulkItems(form).forEach(function (item) {
                item.addEventListener('change', function () { updateBulkForm(form); });
            });

            form.querySelector('[data-bulk-action]').addEventListener('change', function () {
                updateBulkForm(form);
            });

            updateBulkForm(form);
        });

        function closeDialog() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            pendingForm = null;
            pendingSubmitter = null;
            if (previousFocus) {
                previousFocus.focus();
            }
        }

        function openDialog(form, submitter) {
            pendingForm = form;
            pendingSubmitter = submitter;
            previousFocus = document.activeElement;
            title.textContent = form.getAttribute('data-confirm-title') || 'Confirm action';
            message.textContent = form.getAttribute('data-confirm') || 'Are you sure you want to continue?';

            var danger = form.getAttribute('data-confirm-tone') === 'danger';
            confirmButton.className = danger ? 'btn btn-danger' : 'btn btn-primary';
            confirmButton.textContent = form.getAttribute('data-confirm-button') || 'Confirm';

            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            confirmButton.focus();
        }

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.hasAttribute('data-bulk-form')) {
                var selectedCount = bulkItems(form).filter(function (item) { return item.checked; }).length;
                var action = form.querySelector('[data-bulk-action]');
                var feedback = form.querySelector('[data-bulk-feedback]');

                if (selectedCount === 0 || !action.value) {
                    event.preventDefault();
                    feedback.textContent = selectedCount === 0 ? 'Select at least one record.' : 'Choose an action.';
                    return;
                }

                var actionLabel = action.options[action.selectedIndex].text.toLowerCase();
                form.setAttribute('data-confirm', 'Apply "' + actionLabel + '" to ' + selectedCount + ' selected record' + (selectedCount === 1 ? '?' : 's?'));
                form.setAttribute('data-confirm-tone', action.value === 'delete' ? 'danger' : 'primary');
                form.setAttribute('data-confirm-button', action.value === 'delete' ? 'Delete selected' : 'Apply action');
            }

            if (!form.hasAttribute('data-confirm')) {
                return;
            }

            if (form.getAttribute('data-confirmed') === 'true') {
                form.removeAttribute('data-confirmed');
                return;
            }

            event.preventDefault();
            openDialog(form, event.submitter || null);
        });

        cancelButtons.forEach(function (button) {
            button.addEventListener('click', closeDialog);
        });

        confirmButton.addEventListener('click', function () {
            if (!pendingForm) {
                return;
            }

            var form = pendingForm;
            var submitter = pendingSubmitter;
            form.setAttribute('data-confirmed', 'true');
            closeDialog();

            if (form.requestSubmit) {
                form.requestSubmit(submitter || undefined);
            } else {
                form.submit();
            }
        });

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeDialog();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
                closeDialog();
            }
        });
    });
</script>
