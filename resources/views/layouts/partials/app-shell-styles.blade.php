<style>
    :root {
        --app-bg: #f5f7fb;
        --app-surface: #ffffff;
        --app-sidebar: #101828;
        --app-sidebar-soft: #1d2939;
        --app-border: #d9e1ec;
        --app-text: #172033;
        --app-muted: #667085;
        --app-primary: #1f6feb;
        --app-primary-dark: #1750b8;
        --app-success: #067647;
        --app-warning: #b54708;
        --app-danger: #b42318;
        --app-shadow: 0 16px 40px rgba(16, 24, 40, 0.08);
    }

    html,
    body {
        min-height: 100%;
        background: var(--app-bg);
        color: var(--app-text);
        font-family: Inter, "Source Sans Pro", Roboto, Arial, sans-serif;
    }

    body.som-pos {
        background: var(--app-bg);
    }

    .crm-app-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 264px minmax(0, 1fr);
        background: var(--app-bg);
    }

    .crm-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        background: var(--app-sidebar);
        color: #ffffff;
        display: flex;
        flex-direction: column;
        padding: 18px 14px;
        overflow-y: auto;
    }

    .crm-sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 10px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 14px;
    }

    .crm-brand-mark {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        background: #ffffff;
        color: var(--app-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        letter-spacing: 0;
    }

    .crm-brand-title {
        font-size: 15px;
        font-weight: 800;
        line-height: 1.2;
    }

    .crm-brand-subtitle {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.62);
        margin-top: 2px;
    }

    .crm-sidebar-section {
        color: rgba(255, 255, 255, 0.48);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 16px 12px 8px;
    }

    .crm-sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .crm-sidebar-link {
        color: rgba(255, 255, 255, 0.78);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
    }

    .crm-sidebar-link:hover,
    .crm-sidebar-link.crm-active {
        color: #ffffff;
        background: var(--app-sidebar-soft);
        text-decoration: none;
    }

    .crm-sidebar-link i {
        width: 18px;
        text-align: center;
        color: rgba(255, 255, 255, 0.68);
    }

    .crm-sidebar-link.crm-active i {
        color: #ffffff;
    }

    .crm-sidebar-footer {
        margin-top: auto;
        padding: 14px 10px 4px;
        color: rgba(255, 255, 255, 0.62);
        font-size: 12px;
    }

    .crm-app-main {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .crm-topnav {
        position: sticky;
        top: 0;
        z-index: 20;
        min-height: 68px;
        background: rgba(255, 255, 255, 0.94);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--app-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 24px;
    }

    .crm-topnav-title {
        font-size: 13px;
        color: var(--app-muted);
        font-weight: 700;
    }

    .crm-topnav-user {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .crm-topnav-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #eaf1ff;
        color: var(--app-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .crm-topnav-name {
        font-size: 14px;
        font-weight: 800;
        line-height: 1.2;
    }

    .crm-topnav-role {
        font-size: 12px;
        color: var(--app-muted);
    }

    .crm-logout {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--app-muted);
        border: 1px solid var(--app-border);
        background: #ffffff;
    }

    .crm-logout:hover {
        color: var(--app-danger);
        text-decoration: none;
        background: #fff5f5;
    }

    .crm-app-content {
        min-width: 0;
        padding: 0;
    }

    .crm-guest-shell {
        min-height: 100vh;
        background: linear-gradient(135deg, #eef4ff 0%, #f8fafc 45%, #ecfdf3 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .crm-page-shell {
        background: var(--app-bg);
        min-height: calc(100vh - 68px);
        padding: 24px 24px 40px;
    }

    .crm-page-container {
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .crm-card {
        background: var(--app-surface);
        border: 1px solid var(--app-border);
        border-radius: 8px;
        box-shadow: none;
    }

    .crm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        border-radius: 8px;
        border: 1px solid var(--app-border);
        background: #ffffff;
        color: var(--app-text);
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 700;
    }

    .crm-btn:hover {
        background: #f8fafc;
        color: var(--app-text);
        text-decoration: none;
    }

    .crm-btn-primary {
        background: var(--app-primary);
        border-color: var(--app-primary);
        color: #ffffff;
    }

    .crm-btn-primary:hover {
        background: var(--app-primary-dark);
        color: #ffffff;
    }

    @media (max-width: 991.98px) {
        .crm-app-shell {
            grid-template-columns: 84px minmax(0, 1fr);
        }

        .crm-sidebar {
            padding: 14px 10px;
        }

        .crm-brand-copy,
        .crm-sidebar-link span,
        .crm-sidebar-section,
        .crm-sidebar-footer {
            display: none;
        }

        .crm-sidebar-link {
            justify-content: center;
            padding: 12px;
        }

        .crm-sidebar-link i {
            width: auto;
        }

        .crm-topnav {
            padding: 12px 16px;
        }
    }

    @media (max-width: 575.98px) {
        .crm-app-shell {
            grid-template-columns: 1fr;
        }

        .crm-sidebar {
            position: static;
            height: auto;
            flex-direction: row;
            align-items: center;
            overflow-x: auto;
        }

        .crm-sidebar-brand,
        .crm-sidebar-footer {
            display: none;
        }

        .crm-sidebar-nav {
            flex-direction: row;
        }

        .crm-topnav-user .crm-user-copy,
        .crm-topnav-title {
            display: none;
        }

        .crm-page-shell {
            padding: 16px 12px 28px;
        }
    }
</style>
