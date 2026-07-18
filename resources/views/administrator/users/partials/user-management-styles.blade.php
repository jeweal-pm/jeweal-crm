<style>
    :root {
        --crm-user-bg: #f6f8fb;
        --crm-user-surface: #ffffff;
        --crm-user-border: #d9e1ec;
        --crm-user-muted: #667085;
        --crm-user-text: #172033;
        --crm-user-primary: #1f6feb;
        --crm-user-primary-dark: #1750b8;
        --crm-user-success: #067647;
        --crm-user-warning: #b54708;
        --crm-user-danger: #b42318;
    }

    body.som-pos {
        background: var(--crm-user-bg);
        color: var(--crm-user-text);
        font-family: Inter, "Source Sans Pro", Arial, sans-serif;
    }

    .user-crm-shell {
        background: var(--crm-user-bg);
        min-height: calc(100vh - 92px);
        padding: 24px 20px 40px;
    }

    .user-crm-container {
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .user-crm-topbar,
    .user-crm-toolbar,
    .user-crm-panel,
    .user-crm-form-panel {
        background: var(--crm-user-surface);
        border: 1px solid var(--crm-user-border);
        border-radius: 8px;
    }

    .user-crm-topbar {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
        padding: 18px 20px;
        margin-bottom: 16px;
    }

    .user-crm-title h1 {
        font-size: 24px;
        line-height: 1.25;
        margin: 0;
        font-weight: 700;
        letter-spacing: 0;
    }

    .user-crm-subtitle {
        margin-top: 4px;
        color: var(--crm-user-muted);
        font-size: 14px;
    }

    .user-crm-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .user-crm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        border-radius: 8px;
        border: 1px solid var(--crm-user-border);
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 600;
        color: var(--crm-user-text);
        background: #fff;
    }

    .user-crm-btn:hover {
        text-decoration: none;
        color: var(--crm-user-text);
        background: #f8fafc;
    }

    .user-crm-btn-primary {
        background: var(--crm-user-primary);
        border-color: var(--crm-user-primary);
        color: #fff;
    }

    .user-crm-btn-primary:hover {
        background: var(--crm-user-primary-dark);
        color: #fff;
    }

    .user-crm-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .user-crm-metric {
        background: var(--crm-user-surface);
        border: 1px solid var(--crm-user-border);
        border-radius: 8px;
        padding: 14px 16px;
    }

    .user-crm-metric-label {
        color: var(--crm-user-muted);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .user-crm-metric-value {
        margin-top: 8px;
        font-size: 26px;
        line-height: 1;
        font-weight: 700;
    }

    .user-crm-toolbar {
        padding: 14px;
        margin-bottom: 16px;
    }

    .user-crm-toolbar .form-group {
        margin-bottom: 0;
    }

    .user-crm-toolbar label,
    .user-crm-form label {
        font-size: 12px;
        font-weight: 700;
        color: #344054;
        margin-bottom: 6px;
    }

    .user-crm-toolbar .form-control,
    .user-crm-form .form-control {
        border-radius: 8px;
        border-color: var(--crm-user-border);
        min-height: 38px;
        font-size: 14px;
    }

    .user-crm-panel {
        overflow: hidden;
    }

    .user-crm-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid var(--crm-user-border);
    }

    .user-crm-panel-title {
        font-size: 16px;
        margin: 0;
        font-weight: 700;
    }

    .user-crm-count {
        color: var(--crm-user-muted);
        font-size: 13px;
    }

    .user-crm-table {
        margin-bottom: 0;
    }

    .user-crm-table th {
        border-top: 0;
        border-bottom: 1px solid var(--crm-user-border);
        color: #475467;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        background: #f8fafc;
        padding: 12px 16px;
    }

    .user-crm-table td {
        border-top: 1px solid #edf1f7;
        vertical-align: middle;
        padding: 14px 16px;
        font-size: 14px;
    }

    .user-crm-person {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 220px;
    }

    .user-crm-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eaf1ff;
        color: var(--crm-user-primary);
        font-weight: 800;
        flex: 0 0 auto;
    }

    .user-crm-primary {
        font-weight: 700;
        color: var(--crm-user-text);
    }

    .user-crm-muted {
        color: var(--crm-user-muted);
        font-size: 13px;
    }

    .user-crm-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 700;
        background: #eef4ff;
        color: #175cd3;
    }

    .user-crm-status-active {
        background: #ecfdf3;
        color: var(--crm-user-success);
    }

    .user-crm-status-inactive {
        background: #fff4ed;
        color: var(--crm-user-warning);
    }

    .user-crm-empty {
        text-align: center;
        color: var(--crm-user-muted);
        padding: 32px;
    }

    .user-crm-form-panel {
        padding: 20px;
    }

    .user-crm-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .user-crm-form-full {
        grid-column: 1 / -1;
    }

    .user-crm-switch-row {
        min-height: 38px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-crm-alert {
        border-radius: 8px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        padding: 12px 14px;
        margin-bottom: 16px;
    }

    @media (max-width: 991.98px) {
        .user-crm-topbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .user-crm-metrics,
        .user-crm-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .user-crm-shell {
            padding: 16px 12px 28px;
        }

        .user-crm-metrics,
        .user-crm-form-grid {
            grid-template-columns: 1fr;
        }

        .user-crm-actions {
            width: 100%;
        }

        .user-crm-btn {
            width: 100%;
        }
    }
</style>
