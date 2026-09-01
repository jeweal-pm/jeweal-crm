<style>
    .crm-bulk-bar {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid #e4e9f1;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: space-between;
        min-height: 58px;
        padding: 10px 16px;
    }

    .crm-bulk-summary {
        align-items: baseline;
        display: flex;
        gap: 8px;
    }

    .crm-bulk-count {
        color: #1f2937;
        font-size: 13px;
        font-weight: 800;
    }

    .crm-bulk-help,
    .crm-bulk-feedback {
        color: #667085;
        font-size: 12px;
    }

    .crm-bulk-feedback {
        flex-basis: 100%;
        min-height: 0;
    }

    .crm-bulk-feedback:not(:empty) {
        color: #b42318;
        margin-top: -4px;
    }

    .crm-bulk-controls,
    .crm-bulk-target {
        align-items: center;
        display: flex;
        gap: 8px;
    }

    .crm-bulk-controls .form-control {
        font-size: 12px;
        height: 34px;
        min-width: 154px;
    }

    .crm-bulk-apply {
        min-height: 34px;
        min-width: 82px;
    }

    .crm-select-cell {
        text-align: center;
        width: 44px;
    }

    .crm-select-checkbox {
        cursor: pointer;
        height: 16px;
        margin: 0;
        vertical-align: middle;
        width: 16px;
    }

    .crm-row-selected td {
        background: #f1f6ff;
    }

    @media (max-width: 767px) {
        .crm-bulk-bar,
        .crm-bulk-controls {
            align-items: stretch;
            flex-direction: column;
        }

        .crm-bulk-controls,
        .crm-bulk-controls .form-control,
        .crm-bulk-target,
        .crm-bulk-target .form-control,
        .crm-bulk-apply {
            width: 100%;
        }
    }
</style>
