<style>
    /*
     * Resume COGM - layout dibuat mengikuti mockup:
     * - KPI cards 3 kolom di atas
     * - Ringkasan Customer kiri
     * - Detail Project kanan
     * - Project clickable ke Form Costing
     * - Keterangan: Full Price / part belum harga / part estimate
     */
    .resume-cogm-page {
        width: 100%;
        display: grid;
        gap: 1.35rem;
    }

    .resume-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .resume-kpi-card {
        min-height: 94px;
        border-radius: 14px;
        padding: 1.22rem 1.25rem;
        color: #ffffff;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.10);
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .resume-kpi-card::after {
        content: '';
        position: absolute;
        inset: auto -20% -60% auto;
        width: 210px;
        height: 210px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.10);
        pointer-events: none;
    }

    .resume-kpi-label {
        font-size: 0.76rem;
        font-weight: 850;
        letter-spacing: -0.01em;
        opacity: 0.94;
        margin-bottom: 0.35rem;
        position: relative;
        z-index: 1;
    }

    .resume-kpi-value {
        font-size: clamp(1.38rem, 1.9vw, 1.82rem);
        font-weight: 900;
        line-height: 1.08;
        letter-spacing: -0.035em;
        position: relative;
        z-index: 1;
    }

    .resume-two-column {
        display: grid;
        grid-template-columns: 0.82fr 1.68fr;
        gap: 1rem;
        align-items: start;
    }

    .resume-panel {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        overflow: hidden;
        min-width: 0;
    }

    .resume-panel-header {
        min-height: 55px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1rem 0.72rem;
    }

    .resume-panel-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        letter-spacing: -0.015em;
    }

    .resume-panel-hint {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: #64748b;
        font-size: 0.70rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .resume-panel-hint svg {
        width: 13px;
        height: 13px;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .resume-table-wrap {
        padding: 0 1rem 1rem;
        overflow-x: auto;
    }

    .resume-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
        color: #334155;
        border: 1px solid #cfe0f5;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
    }

    .resume-table th {
        background: #2563eb;
        color: #ffffff;
        padding: 0.58rem 0.55rem;
        text-align: left;
        font-size: 0.64rem;
        font-weight: 900;
        white-space: nowrap;
        line-height: 1.2;
    }

    .resume-table td {
        padding: 0.58rem 0.55rem;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        font-size: 0.70rem;
        font-weight: 600;
        color: #334155;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .resume-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .resume-table tbody tr:hover {
        background: #f8fbff;
    }

    .resume-table .num,
    .resume-table .text-right {
        text-align: right;
    }

    .resume-table .text-center {
        text-align: center;
    }

    .customer-table th,
    .customer-table td {
        font-size: 0.66rem;
        padding-left: 0.42rem;
        padding-right: 0.42rem;
    }

    .customer-table th:nth-child(1),
    .customer-table td:nth-child(1) {
        width: 7%;
    }

    .customer-table th:nth-child(2),
    .customer-table td:nth-child(2) {
        width: 36%;
    }

    .customer-table th:nth-child(3),
    .customer-table td:nth-child(3) {
        width: 12%;
    }

    .customer-table th:nth-child(4),
    .customer-table td:nth-child(4) {
        width: 21%;
    }

    .customer-table th:nth-child(5),
    .customer-table td:nth-child(5) {
        width: 24%;
    }

    .customer-table th:nth-child(4),
    .customer-table td:nth-child(4),
    .customer-table th:nth-child(5),
    .customer-table td:nth-child(5) {
        overflow: visible;
        text-overflow: clip;
    }

    .project-table {
        min-width: 1420px;
        font-size: 0.65rem;
    }

    .project-table th,
    .project-table td {
        padding-left: 0.26rem;
        padding-right: 0.26rem;
        font-size: 0.585rem;
    }

    .project-table th:nth-child(1),
    .project-table td:nth-child(1) { width: 3.0%; }

    .project-table th:nth-child(2),
    .project-table td:nth-child(2) { width: 5.6%; }

    .project-table th:nth-child(3),
    .project-table td:nth-child(3) { width: 5.8%; }

    .project-table th:nth-child(4),
    .project-table td:nth-child(4) {
        width: 13.9%;
        overflow: visible;
        text-overflow: clip;
    }

    .project-table th:nth-child(5),
    .project-table td:nth-child(5) { width: 8.4%; }

    .project-table th:nth-child(6),
    .project-table td:nth-child(6) { width: 4.7%; }

    .project-table th:nth-child(7),
    .project-table td:nth-child(7),
    .project-table th:nth-child(8),
    .project-table td:nth-child(8),
    .project-table th:nth-child(9),
    .project-table td:nth-child(9),
    .project-table th:nth-child(10),
    .project-table td:nth-child(10) { width: 6.4%; }

    .project-table th:nth-child(11),
    .project-table td:nth-child(11),
    .project-table th:nth-child(12),
    .project-table td:nth-child(12) { width: 4.7%; }

    .project-table th:nth-child(13),
    .project-table td:nth-child(13) { width: 9.2%; }

    .project-table th:nth-child(14),
    .project-table td:nth-child(14) {
        width: 230px;
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        word-break: normal;
        overflow-wrap: anywhere;
    }

    .project-link {
        display: inline-flex;
        align-items: center;
        gap: 0.22rem;
        max-width: 100%;
        color: #2563eb;
        font-weight: 900;
        text-decoration: underline;
        text-decoration-thickness: 1px;
        text-underline-offset: 2px;
        vertical-align: middle;
    }

    .project-link span {
        display: inline-block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .assy-no-link span {
        max-width: 20ch;
        overflow: visible;
        text-overflow: clip;
    }

    .project-link:hover {
        color: #1d4ed8;
    }

    .project-link svg {
        width: 10px;
        height: 10px;
        flex: 0 0 auto;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.28rem;
        font-size: 0.62rem;
        font-weight: 850;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        display: inline-block;
        flex: 0 0 auto;
    }

    .status-dot.a00 {
        background: #2563eb;
    }

    .status-dot.a04 {
        background: #f97316;
    }

    .status-dot.a05 {
        background: #16a34a;
    }
    .status-dot.submitted{background:#22c55e}.status-dot.updated{background:#f59e0b}
    .submission-update-note{box-sizing:border-box;display:grid;width:100%;gap:.15rem;margin-bottom:.35rem;padding:.42rem .5rem;border:1px solid #fde68a;border-radius:7px;background:#fffbeb;color:#92400e;font-size:.61rem;font-weight:850;line-height:1.35;white-space:normal;overflow-wrap:anywhere}.submission-update-note small{display:block;color:#a16207;font-size:.56rem;font-weight:700;white-space:normal}

    .note-stack {
        display: grid;
        gap: 0.24rem;
        justify-items: start;
        min-width: 0;
        width: 100%;
    }
    .price-status-notes{width:100%;white-space:normal;line-height:1.4;overflow-wrap:anywhere}

    .note-badge {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 0.20rem;
        max-width: 100%;
        border-radius: 999px;
        border: 1px solid transparent;
        padding: 0.18rem 0.34rem;
        font-size: 0.53rem;
        font-weight: 900;
        line-height: 1.12;
        white-space: nowrap;
    }

    .note-badge svg {
        width: 9px;
        height: 9px;
        flex: 0 0 auto;
    }

    .note-badge.full {
        color: #15803d;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .note-badge.missing {
        color: #7e22ce;
        background: #f3e8ff;
        border-color: #e9d5ff;
    }

    .note-badge.estimate {
        color: #c2410c;
        background: #ffedd5;
        border-color: #fed7aa;
    }

    .table-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding-top: 0.85rem;
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .pager {
        display: inline-flex;
        align-items: center;
        gap: 0.34rem;
        flex-wrap: wrap;
    }

    .pager a,
    .pager span {
        min-width: 30px;
        height: 30px;
        padding: 0 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dbe4f2;
        border-radius: 9px;
        text-decoration: none;
        color: #334155;
        background: #ffffff;
        font-size: 0.76rem;
        font-weight: 850;
    }

    .pager .active {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
    }

    .pager .disabled {
        opacity: 0.42;
        pointer-events: none;
    }

    .empty-state {
        padding: 1.25rem;
        text-align: center;
        color: #64748b;
        font-size: 0.84rem;
    }

    @media (max-width: 1500px) {
        .resume-cogm-page {
            width: calc(100% + 8rem);
            margin-left: -4rem;
            margin-right: -4rem;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
        }

        .resume-two-column {
            grid-template-columns: minmax(425px, 0.64fr) minmax(760px, 1.36fr);
        }

        .resume-table-wrap {
            padding-left: 0.65rem;
            padding-right: 0.65rem;
        }
    }

    @media (max-width: 1180px) {
        .resume-two-column {
            grid-template-columns: 1fr;
        }

        .resume-table-wrap {
            overflow-x: auto;
        }

        .project-table {
            min-width: 1180px;
            table-layout: auto;
        }

        .project-table th,
        .project-table td {
            font-size: 0.68rem;
            padding-left: 0.55rem;
            padding-right: 0.55rem;
        }
    }

    @media (max-width: 820px) {
        .resume-kpi-grid {
            grid-template-columns: 1fr;
        }

        .resume-panel-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    .resume-two-column .resume-panel {
        min-width: 0;
    }

    .resume-two-column table {
        width: 100%;
    }

    @media (max-width: 1180px) {
        .resume-two-column {
            grid-template-columns: 1fr;
        }
    }


    .resume-analytics-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 320px;
        gap: 1rem;
        align-items: stretch;
    }

    .resume-chart-panel {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        padding: 1rem 1rem 1.05rem;
        min-width: 0;
        overflow: hidden;
    }

    .resume-chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .resume-chart-title {
        margin: 0;
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        letter-spacing: -0.015em;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .resume-info-dot {
        width: 15px;
        height: 15px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #2563eb;
        font-size: 0.58rem;
        font-weight: 950;
    }

    .resume-period-pill {
        border: 1px solid #dbe4f2;
        background: #f8fafc;
        color: #475569;
        border-radius: 9px;
        padding: 0.42rem 0.58rem;
        font-size: 0.70rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .resume-chart-unit {
        color: #475569;
        font-size: 0.72rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
    }

    .resume-line-chart {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 0.6rem;
        height: 235px;
        align-items: stretch;
    }

    .resume-y-labels {
        display: grid;
        grid-template-rows: repeat(4, 1fr);
        align-items: center;
        justify-items: end;
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 800;
        padding-top: 0.1rem;
    }

    .resume-chart-area {
        position: relative;
        border-left: 1px solid #dbe4f2;
        border-bottom: 1px solid #dbe4f2;
        overflow: hidden;
        background:
            linear-gradient(to top, rgba(226, 232, 240, 0.78) 1px, transparent 1px) 0 0 / 100% 33.333%;
    }

    .resume-chart-svg {
        position: absolute;
        inset: 0;
        overflow: visible;
    }

    .resume-chart-x {
        display: grid;
        grid-template-columns: repeat(var(--count), minmax(0, 1fr));
        gap: 0.35rem;
        margin-left: 3.05rem;
        padding-top: 0.35rem;
        color: #64748b;
        font-size: 0.65rem;
        font-weight: 750;
        text-align: center;
    }

    .resume-chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        justify-content: center;
        margin-top: 0.9rem;
        color: #334155;
        font-size: 0.70rem;
        font-weight: 820;
    }

    .resume-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .resume-legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
    }

    .resume-end-label {
        font-size: 3.2px;
        font-weight: 950;
        paint-order: stroke;
        stroke: #ffffff;
        stroke-width: 0.75px;
        stroke-linejoin: round;
    }

    .resume-insight-card {
        background: #ffffff;
        border: 1px solid #dbe4f2;
        border-radius: 14px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.065);
        padding: 1rem;
        min-width: 0;
        overflow: hidden;
    }

    .resume-insight-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
    }

    .resume-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        flex: 0 0 auto;
    }

    .resume-insight-icon svg {
        width: 22px;
        height: 22px;
    }

    .resume-insight-title {
        color: #0f172a;
        font-size: 0.98rem;
        font-weight: 900;
        margin: 0;
    }

    .resume-insight-list {
        display: grid;
        gap: 0;
    }

    .resume-insight-item {
        display: grid;
        grid-template-columns: 14px minmax(0, 1fr);
        gap: 0.55rem;
        padding: 0.8rem 0;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 760;
        line-height: 1.48;
    }

    .resume-insight-item:last-child {
        border-bottom: 0;
    }

    .resume-insight-bullet {
        width: 8px;
        height: 8px;
        margin-top: 0.36rem;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 4px #dbeafe;
    }

</style>


<style>
    @media (max-width: 1500px) {
        .resume-cogm-page {
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .resume-two-column {
            grid-template-columns: 0.82fr 1.68fr !important;
        }
    }

    @media (max-width: 1180px) {
        .resume-analytics-grid,
        .resume-two-column {
            grid-template-columns: 1fr !important;
        }

        .resume-chart-panel,
        .resume-insight-card {
            overflow-x: auto;
        }
    }
</style>
