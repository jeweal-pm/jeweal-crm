<style>
    .funnel-page { padding: 22px; color: #172033; }
    .funnel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .funnel-heading h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; letter-spacing: 0; }
    .funnel-heading p { margin: 0; color: #65738b; font-size: 13px; }
    .funnel-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .funnel-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .funnel-stat { min-height: 82px; padding: 14px 16px; background: #fff; border: 1px solid #dce3ec; border-radius: 6px; }
    .funnel-stat span { color: #6b778c; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .funnel-stat strong { display: block; margin-top: 5px; color: #12243a; font-size: 24px; line-height: 1; }
    .funnel-panel { margin-bottom: 16px; overflow: hidden; background: #fff; border: 1px solid #dce3ec; border-radius: 6px; }
    .funnel-panel-head { min-height: 58px; padding: 13px 16px; border-bottom: 1px solid #e5eaf1; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .funnel-panel-title { margin: 0; font-size: 15px; font-weight: 700; }
    .funnel-panel-copy { margin-top: 2px; color: #738096; font-size: 12px; }
    .funnel-panel-body { padding: 16px; }
    .funnel-filter { display: grid; grid-template-columns: minmax(210px, 1.7fr) repeat(5, minmax(125px, 1fr)) auto; gap: 10px; align-items: end; }
    .funnel-page label { display: block; margin-bottom: 5px; color: #435168; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .funnel-page .form-control { min-height: 38px; border-color: #cfd8e5; border-radius: 4px; font-size: 13px; }
    .funnel-page textarea.form-control { min-height: 96px; }
    .funnel-table-wrap { overflow-x: auto; }
    .funnel-table { min-width: 1080px; margin: 0; }
    .funnel-table th { padding: 10px 12px; border-top: 0; border-bottom: 1px solid #dce3ec; background: #f6f8fb; color: #5a687f; font-size: 10px; text-transform: uppercase; white-space: nowrap; }
    .funnel-table td { padding: 12px; border-color: #e7ebf1; vertical-align: middle; font-size: 13px; }
    .funnel-table strong { color: #172033; }
    .funnel-meta { margin-top: 3px; color: #7b879a; font-size: 11px; }
    .funnel-code { display: inline-block; padding: 3px 7px; color: #075f54; background: #e7f6f2; border: 1px solid #c8e9e1; border-radius: 4px; font-family: monospace; font-size: 12px; font-weight: 700; }
    .funnel-badge { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
    .funnel-badge-active, .funnel-badge-customer, .funnel-badge-yes { color: #08704f; background: #e4f7ef; }
    .funnel-badge-draft, .funnel-badge-lead_mql { color: #8a5700; background: #fff3d8; }
    .funnel-badge-closed, .funnel-badge-no { color: #7c3441; background: #f9e8ec; }
    .funnel-badge-sql, .funnel-badge-prospect { color: #1d4f91; background: #e8f1fd; }
    .funnel-inline { display: flex; align-items: center; gap: 6px; }
    .funnel-inline .form-control { min-width: 130px; }
    .funnel-form-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    .funnel-form-grid .span-2 { grid-column: span 2; }
    .funnel-form-grid .full { grid-column: 1 / -1; }
    .funnel-check { display: flex; align-items: center; gap: 7px; min-height: 38px; color: #435168; font-size: 13px; }
    .funnel-help { margin-top: 5px; color: #7b879a; font-size: 11px; }
    .funnel-detail-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; }
    .funnel-detail { min-height: 70px; padding: 13px 16px; border-right: 1px solid #e7ebf1; border-bottom: 1px solid #e7ebf1; }
    .funnel-detail:nth-child(3n) { border-right: 0; }
    .funnel-detail-label { color: #718096; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .funnel-detail-value { margin-top: 5px; color: #172033; font-size: 13px; overflow-wrap: anywhere; }
    .funnel-empty { padding: 52px 20px; text-align: center; color: #8793a5; }
    .funnel-empty i { display: block; margin-bottom: 8px; color: #a8b2c2; font-size: 28px; }
    .funnel-link-row { display: grid; grid-template-columns: minmax(150px, .75fr) minmax(250px, 1.3fr) repeat(3, minmax(90px, .5fr)) minmax(220px, 1fr) auto; gap: 10px; align-items: end; padding: 14px 16px; border-bottom: 1px solid #e7ebf1; }
    .funnel-link-row:last-child { border-bottom: 0; }
    .funnel-copy-field { display: flex; gap: 6px; }
    .funnel-copy-field input { flex: 1; }
    .funnel-page .pagination { margin: 14px 16px; }
    @media (max-width: 1100px) {
        .funnel-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .funnel-filter, .funnel-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .funnel-filter > :first-child, .funnel-form-grid .full { grid-column: 1 / -1; }
        .funnel-link-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 680px) {
        .funnel-page { padding: 14px; }
        .funnel-heading { display: block; }
        .funnel-actions { margin-top: 12px; }
        .funnel-stats, .funnel-filter, .funnel-form-grid, .funnel-detail-grid, .funnel-link-row { grid-template-columns: 1fr; }
        .funnel-filter > :first-child, .funnel-form-grid .span-2, .funnel-form-grid .full { grid-column: auto; }
        .funnel-detail { border-right: 0; }
    }
</style>
