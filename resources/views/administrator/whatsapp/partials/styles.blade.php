<style>
    .communication-page { padding: 22px; color: #172033; }
    .communication-page .page-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .communication-page .page-heading h1 { margin: 0 0 3px; font-size: 24px; font-weight: 700; letter-spacing: 0; }
    .communication-page .page-heading p { margin: 0; color: #65738b; font-size: 13px; }
    .communication-page .page-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .communication-page .comm-panel { background: #fff; border: 1px solid #dce3ec; border-radius: 6px; margin-bottom: 16px; overflow: hidden; }
    .communication-page .comm-panel-head { min-height: 58px; padding: 13px 16px; border-bottom: 1px solid #e5eaf1; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .communication-page .comm-panel-title { margin: 0; font-size: 15px; font-weight: 700; }
    .communication-page .comm-panel-copy { color: #738096; font-size: 12px; margin-top: 2px; }
    .communication-page .comm-panel-body { padding: 16px; }
    .communication-page .comm-tabs { display: flex; gap: 4px; border-bottom: 1px solid #dce3ec; margin-bottom: 16px; overflow-x: auto; }
    .communication-page .comm-tab { color: #59677e; padding: 10px 13px; border-bottom: 2px solid transparent; white-space: nowrap; font-size: 13px; font-weight: 600; }
    .communication-page .comm-tab:hover { color: #172033; text-decoration: none; }
    .communication-page .comm-tab.active { color: #08785e; border-bottom-color: #08785e; }
    .communication-page .comm-count { display: inline-flex; min-width: 22px; height: 20px; padding: 0 6px; margin-left: 5px; align-items: center; justify-content: center; background: #eef2f7; border-radius: 10px; font-size: 11px; }
    .communication-page .comm-toolbar { display: flex; align-items: flex-end; gap: 10px; padding: 13px 16px; border-bottom: 1px solid #e5eaf1; background: #f9fbfd; }
    .communication-page .comm-toolbar .form-group { margin: 0; flex: 1; max-width: 420px; }
    .communication-page label { display: block; margin-bottom: 5px; color: #435168; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .communication-page .form-control { min-height: 38px; border-color: #cfd8e5; border-radius: 4px; font-size: 13px; }
    .communication-page textarea.form-control { min-height: 92px; }
    .communication-page .comm-table-wrap { overflow-x: auto; }
    .communication-page .comm-table { min-width: 920px; margin: 0; }
    .communication-page .comm-table th { padding: 10px 13px; border-top: 0; border-bottom: 1px solid #dce3ec; background: #f6f8fb; color: #5a687f; font-size: 10px; text-transform: uppercase; white-space: nowrap; }
    .communication-page .comm-table td { padding: 12px 13px; border-color: #e7ebf1; vertical-align: middle; font-size: 13px; }
    .communication-page .comm-message-preview { max-width: 360px; color: #4d5a70; line-height: 1.4; }
    .communication-page .comm-meta { color: #7b879a; font-size: 11px; margin-top: 3px; }
    .communication-page .comm-status { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
    .communication-page .comm-status-waiting { color: #8a5700; background: #fff3d8; }
    .communication-page .comm-status-sent, .communication-page .comm-status-allowed { color: #08704f; background: #e4f7ef; }
    .communication-page .comm-status-failed, .communication-page .comm-status-blacklisted, .communication-page .comm-status-rate_limited { color: #b42318; background: #feeceb; }
    .communication-page .comm-status-cooldown { color: #8a5700; background: #fff3d8; }
    .communication-page .comm-empty { padding: 54px 20px; text-align: center; color: #8793a5; }
    .communication-page .comm-empty i { display: block; margin-bottom: 8px; color: #a8b2c2; font-size: 28px; }
    .communication-page .comm-stat { color: #526079; font-size: 12px; white-space: nowrap; }
    .communication-page .comm-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 16px; align-items: start; }
    .communication-page .comm-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .communication-page .comm-form-grid .full { grid-column: 1 / -1; }
    .communication-page .comm-help { margin-top: 5px; color: #7b879a; font-size: 11px; }
    .communication-page .comm-secret-state { color: #08704f; font-size: 11px; font-weight: 600; }
    .communication-page .comm-inline-form { display: flex; gap: 8px; align-items: center; }
    .communication-page .comm-rate-row { display: grid; grid-template-columns: minmax(160px, 1.2fr) repeat(3, minmax(105px, .7fr)) auto auto; gap: 10px; align-items: end; padding: 13px 16px; border-bottom: 1px solid #e7ebf1; }
    .communication-page .comm-rate-row:last-child { border-bottom: 0; }
    .communication-page .comm-rate-name { align-self: center; }
    .communication-page .comm-rate-name strong { display: block; font-size: 13px; }
    .communication-page .comm-rate-name span { color: #7b879a; font-size: 11px; }
    .communication-page .comm-checkbox { display: flex; align-items: center; gap: 6px; min-height: 38px; color: #435168; font-size: 12px; }
    .communication-page .pagination { margin: 14px 16px; }
    @media (max-width: 991px) {
        .communication-page .comm-grid { grid-template-columns: 1fr; }
        .communication-page .comm-rate-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .communication-page .comm-rate-name { grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
        .communication-page { padding: 14px; }
        .communication-page .page-heading { display: block; }
        .communication-page .page-actions { margin-top: 12px; }
        .communication-page .comm-form-grid, .communication-page .comm-rate-row { grid-template-columns: 1fr; }
        .communication-page .comm-form-grid .full, .communication-page .comm-rate-name { grid-column: auto; }
        .communication-page .comm-toolbar { align-items: stretch; flex-direction: column; }
        .communication-page .comm-toolbar .form-group { max-width: none; }
    }
</style>
