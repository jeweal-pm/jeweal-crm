@include('administrator.enquiry.partials.crm-workspace-styles')
<style>
    .email-workspace .crm-page {
        padding-top: 28px;
    }

    .email-workspace .crm-topbar {
        align-items: center;
        margin-bottom: 22px;
    }

    .email-workspace .crm-topbar-actions,
    .email-workspace .email-inline-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .email-workspace .crm-panel {
        box-shadow: 0 1px 2px rgba(16, 24, 40, .03);
    }

    .email-workspace .crm-panel + .crm-panel {
        margin-top: 16px;
    }

    .email-workspace .email-panel-head {
        align-items: center;
        border-bottom: 1px solid #e8edf4;
        display: flex;
        justify-content: space-between;
        min-height: 58px;
        padding: 13px 18px;
    }

    .email-workspace .email-panel-copy {
        color: #667085;
        font-size: 12px;
        margin-top: 3px;
    }

    .email-workspace .email-panel-body {
        padding: 18px;
    }

    .email-workspace .email-section-label {
        color: #667085;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        margin: 4px 0 12px;
        text-transform: uppercase;
    }

    .email-workspace .email-kpi {
        border-left: 3px solid #1f6feb;
    }

    .email-workspace .email-kpi:nth-child(2) { border-left-color: #067647; }
    .email-workspace .email-kpi:nth-child(3) { border-left-color: #b54708; }
    .email-workspace .email-kpi:nth-child(4) { border-left-color: #7f56d9; }
    .email-workspace .email-kpi:nth-child(5) { border-left-color: #0e7490; }
    .email-workspace .email-kpi:nth-child(6) { border-left-color: #c4320a; }

    .email-workspace .email-kpi .crm-metric-value {
        font-size: 24px;
    }

    .email-workspace .email-nav-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .email-workspace .email-nav-item {
        align-items: center;
        border: 1px solid #e1e6ef;
        border-radius: 7px;
        color: #344054;
        display: flex;
        gap: 10px;
        min-height: 54px;
        padding: 10px 12px;
        transition: border-color .15s ease, background-color .15s ease;
    }

    .email-workspace .email-nav-item:hover {
        background: #f8fbff;
        border-color: #9dbcf2;
        color: #1750b8;
        text-decoration: none;
    }

    .email-workspace .email-nav-item i {
        color: #1f6feb;
        font-size: 15px;
        text-align: center;
        width: 18px;
    }

    .email-workspace .email-table-wrap {
        overflow-x: auto;
    }

    .email-workspace .crm-table {
        min-width: 760px;
    }

    .email-workspace .crm-table tr:hover td {
        background: #fbfdff;
    }

    .email-workspace .email-code {
        background: #f8fafc;
        border: 1px solid #e8edf4;
        border-radius: 4px;
        color: #475467;
        display: inline-block;
        font-size: 12px;
        max-width: 250px;
        overflow-wrap: anywhere;
        padding: 3px 6px;
    }

    .email-workspace .email-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        padding: 6px 9px;
        white-space: nowrap;
    }

    .email-workspace .email-status-published,
    .email-workspace .email-status-approved,
    .email-workspace .email-status-active,
    .email-workspace .email-status-sent,
    .email-workspace .email-status-delivered,
    .email-workspace .email-status-completed { background: #ecfdf3; color: #027a48; }
    .email-workspace .email-status-draft,
    .email-workspace .email-status-pending,
    .email-workspace .email-status-queued,
    .email-workspace .email-status-processing { background: #fff7e6; color: #9a6700; }
    .email-workspace .email-status-failed,
    .email-workspace .email-status-suppressed,
    .email-workspace .email-status-hard-bounced { background: #fff1f3; color: #b42318; }
    .email-workspace .email-status-archived,
    .email-workspace .email-status-paused,
    .email-workspace .email-status-deferred { background: #f2f4f7; color: #475467; }

    .email-workspace .email-form-panel {
        max-width: 1180px;
    }

    .email-workspace .email-form-section {
        border-bottom: 1px solid #edf1f6;
        padding: 2px 0 8px;
    }

    .email-workspace .email-form-section:last-of-type {
        border-bottom: 0;
    }

    .email-workspace .email-form-actions {
        border-top: 1px solid #edf1f6;
        display: flex;
        gap: 8px;
        margin-top: 8px;
        padding-top: 18px;
    }

    .email-workspace .form-group label {
        color: #475467;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .email-workspace .form-control {
        border-color: #d7deea;
        border-radius: 5px;
        font-size: 13px;
        min-height: 38px;
    }

    .email-workspace textarea.form-control {
        min-height: 96px;
    }

    .email-workspace .email-helper {
        color: #667085;
        font-size: 12px;
        line-height: 1.5;
    }

    .email-workspace .email-preview {
        background: #f8fafc;
        border: 1px solid #e1e6ef;
        border-radius: 6px;
        min-height: 220px;
        padding: 24px;
    }

    .email-workspace .email-preview-subject {
        border-bottom: 1px solid #e1e6ef;
        color: #172033;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 18px;
        padding-bottom: 14px;
    }

    .email-workspace .email-empty-icon {
        color: #98a2b3;
        display: block;
        font-size: 24px;
        margin-bottom: 8px;
    }

    @media (max-width: 991px) {
        .email-workspace .email-nav-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .email-workspace .email-nav-grid {
            grid-template-columns: 1fr;
        }

        .email-workspace .email-panel-head {
            align-items: flex-start;
            display: block;
        }

        .email-workspace .email-panel-head .email-inline-actions {
            margin-top: 10px;
        }

        .email-workspace .email-preview {
            padding: 16px;
        }
    }
</style>
