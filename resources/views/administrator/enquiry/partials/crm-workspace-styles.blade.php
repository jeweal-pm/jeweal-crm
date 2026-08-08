<style>
    .crm-page {
        background: var(--app-bg, #f4f6f9);
        min-height: calc(100vh - 68px);
        padding: 24px 24px 40px;
    }

    .crm-page > .container-fluid {
        width: 100%;
        max-width: none;
        margin: 0;
        padding-left: 0;
        padding-right: 0;
    }

    .crm-topbar {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .crm-title h2 {
        color: #1f2937;
        font-size: 25px;
        font-weight: 700;
        letter-spacing: 0;
        margin: 0;
    }

    .crm-subtitle {
        color: #667085;
        font-size: 13px;
        margin-top: 4px;
    }

    .crm-switcher {
        display: inline-flex;
        gap: 8px;
        white-space: nowrap;
    }

    .crm-metrics {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 14px;
    }

    .crm-metric {
        background: #fff;
        border: 1px solid #e1e6ef;
        border-radius: 8px;
        padding: 14px 16px;
    }

    .crm-metric-label {
        color: #667085;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .crm-metric-value {
        color: #111827;
        font-size: 26px;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 5px;
    }

    .crm-toolbar {
        background: #fff;
        border: 1px solid #e1e6ef;
        border-radius: 8px;
        margin-bottom: 14px;
        padding: 14px;
    }

    .crm-tabs {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .crm-tab {
        align-items: center;
        background: #fff;
        border: 1px solid #d7deea;
        border-radius: 999px;
        color: #475467;
        display: inline-flex;
        font-size: 13px;
        font-weight: 800;
        gap: 7px;
        padding: 8px 12px;
    }

    .crm-tab:hover {
        color: #1d4ed8;
        text-decoration: none;
    }

    .crm-tab-active {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }

    .crm-tab-active:hover {
        color: #fff;
    }

    .crm-tab-count {
        background: rgba(255, 255, 255, .22);
        border-radius: 999px;
        font-size: 12px;
        padding: 2px 7px;
    }

    .crm-tab:not(.crm-tab-active) .crm-tab-count {
        background: #eef2f7;
        color: #475467;
    }

    .crm-toolbar label {
        color: #475467;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .crm-toolbar .form-control {
        border-color: #d7deea;
        font-size: 13px;
        min-height: 38px;
    }

    .crm-panel {
        background: #fff;
        border: 1px solid #e1e6ef;
        border-radius: 8px;
        overflow: hidden;
    }

    .crm-panel-head {
        align-items: center;
        border-bottom: 1px solid #e8edf4;
        display: flex;
        justify-content: space-between;
        min-height: 56px;
        padding: 12px 16px;
    }

    .crm-panel-title {
        color: #344054;
        font-size: 14px;
        font-weight: 800;
        margin: 0;
    }

    .crm-result-count {
        color: #667085;
        font-size: 13px;
    }

    .crm-table {
        margin-bottom: 0;
        table-layout: fixed;
    }

    .crm-table th {
        background: #f8fafc;
        border-bottom: 1px solid #e8edf4;
        border-top: 0;
        color: #667085;
        font-size: 11px;
        letter-spacing: 0;
        padding: 11px 14px;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    .crm-table td {
        border-top: 1px solid #edf1f6;
        color: #344054;
        font-size: 13px;
        padding: 13px 14px;
        vertical-align: middle;
    }

    .crm-primary {
        color: #111827;
        font-size: 14px;
        font-weight: 800;
    }

    .crm-muted {
        color: #667085;
        font-size: 12px;
    }

    .crm-link {
        color: #2563eb;
        font-weight: 700;
    }

    .crm-email-line {
        align-items: center;
        display: flex;
        gap: 6px;
        min-width: 0;
    }

    .crm-email-line .crm-link {
        overflow-wrap: anywhere;
    }

    .crm-reply-link {
        color: #dc2626;
        flex: 0 0 auto;
        font-size: 12px;
        line-height: 1;
    }

    .crm-reply-link:hover {
        color: #991b1b;
        text-decoration: none;
    }

    .crm-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        padding: 7px 10px;
        white-space: nowrap;
    }

    .crm-status-lead_mql { background: #e8f1ff; color: #175199; }
    .crm-status-sql { background: #e9f8ef; color: #1f7a3f; }
    .crm-status-prospect { background: #fff4df; color: #906013; }
    .crm-status-customer { background: #edf7f6; color: #0f6f69; }
    .crm-status-deleted { background: #f1f3f5; color: #5c636a; }

    .crm-spam-chip {
        border-radius: 999px;
        display: inline-flex;
        font-size: 11px;
        font-weight: 800;
        margin-top: 6px;
        padding: 5px 8px;
        white-space: nowrap;
    }

    .crm-spam-suspected { background: #fff1f2; color: #be123c; }
    .crm-spam-confirmed { background: #f1f3f5; color: #495057; }
    .crm-spam-not_spam { background: #ecfdf3; color: #027a48; }

    .crm-reasons {
        color: #667085;
        font-size: 11px;
        margin-top: 4px;
    }

    .crm-owner {
        align-items: center;
        display: flex;
        gap: 8px;
        min-width: 0;
    }

    .crm-avatar {
        align-items: center;
        background: #eef2ff;
        border-radius: 50%;
        color: #3730a3;
        display: inline-flex;
        flex: 0 0 30px;
        font-size: 12px;
        font-weight: 800;
        height: 30px;
        justify-content: center;
        width: 30px;
    }

    .crm-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .crm-action-form {
        align-items: center;
        display: inline-flex;
        gap: 6px;
        margin: 0;
    }

    .crm-action-form select {
        font-size: 12px;
        height: 32px;
        max-width: 154px;
        min-width: 126px;
    }

    .crm-icon-btn {
        align-items: center;
        display: inline-flex;
        height: 32px;
        justify-content: center;
        padding: 0;
        width: 34px;
    }

    .crm-empty {
        color: #667085;
        padding: 42px 18px;
        text-align: center;
    }

    .crm-pagination {
        align-items: center;
        display: flex;
        justify-content: flex-end;
        padding: 14px 16px;
    }

    @media (max-width: 991px) {
        .crm-topbar {
            display: block;
        }

        .crm-switcher {
            margin-top: 12px;
        }

        .crm-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .crm-page {
            padding-left: 12px;
            padding-right: 12px;
        }

        .crm-metrics {
            grid-template-columns: 1fr;
        }

        .crm-panel-head {
            align-items: flex-start;
            display: block;
        }

        .crm-result-count {
            margin-top: 4px;
        }
    }
</style>
