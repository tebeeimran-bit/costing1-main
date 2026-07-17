<style>
        /* Hide spin buttons for chrome/safari/edge/opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hide spin buttons for firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }

        .form-page {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            min-width: 0;
        }

        .form-page .form-section {
            margin-bottom: 0;
        }

        .form-page .card {
            border: 1px solid var(--slate-200);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .form-page .form-grid {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }

        .form-page .form-grid-2 {
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            gap: 1.5rem;
            align-items: start;
        }

        .form-page .param-grid,
        .form-page .cost-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .form-page .form-group,
        .form-page .form-grid > *,
        .form-page .form-grid-2 > * {
            min-width: 0;
        }

        .form-page .form-input,
        .form-page .form-select {
            width: 100%;
        }

        .form-page .quantity-with-options {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.35rem;
            width: 100%;
            min-width: 0;
        }

        .form-page .quantity-with-options .quantity-value {
            flex: 1 1 96px;
            min-width: 96px;
        }

        .form-page .quantity-with-options .quantity-uom {
            flex: 0 0 82px;
            min-width: 82px;
        }

        .form-page .quantity-with-options .quantity-basis {
            flex: 0 0 118px;
            min-width: 118px;
        }

        .form-page .quantity-with-options .quantity-uom,
        .form-page .quantity-with-options .quantity-basis {
            font-size: 0.75rem;
            padding-left: 0.45rem;
            padding-right: 1.5rem;
        }

        .form-page .calc-box {
            margin-top: 1rem !important;
        }

        .form-page .material-table-container {
            max-width: 100%;
            overflow: auto;
            border: 1px solid var(--slate-200);
            border-radius: 1rem;
            background: white;
        }

        .form-page .material-table {
            min-width: 1360px;
        }

        .form-page .material-table th {
            position: static;
            padding: 0.65rem 0.45rem;
            font-size: 0.65rem;
        }

        .form-page .material-table td {
            padding: 0.4rem 0.35rem;
        }

        .form-page .material-table .form-input,
        .form-page .material-table .form-select {
            min-width: 0;
            padding: 0.5rem 0.6rem;
            font-size: 0.75rem;
        }

        .form-page .material-table .part-no {
            min-width: 120px;
        }

        .form-page .material-table .id-code,
        .form-page .material-table .pro-code,
        .form-page .material-table .supplier {
            min-width: 96px;
        }

        .form-page .material-table .part-name {
            min-width: 160px;
        }

        .form-page .material-table .qty-req,
        .form-page .material-table .qty-moq,
        .form-page .material-table .amount1,
        .form-page .material-table .unit-price-basis,
        .form-page .material-table .import-tax {
            min-width: 84px;
            width: 84px !important;
        }

        .form-page .material-table .unit,
        .form-page .material-table .currency,
        .form-page .material-table .cn-type {
            min-width: 74px;
        }

        .material-row-no-header,
        .material-row-no-cell {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .material-row-no-header input,
        .material-row-no-cell input {
            width: 14px;
            height: 14px;
            cursor: pointer;
            accent-color: #2563eb;
        }

        .material-row-number {
            min-width: 1.2rem;
            text-align: right;
            display: inline-block;
        }

        .material-header-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .material-filter-btn {
            width: 18px;
            height: 18px;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            cursor: pointer;
            padding: 0;
        }

        .material-filter-btn.is-active {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1d4ed8;
        }

        .material-filter-popup {
            position: fixed;
            z-index: 1200;
            width: 280px;
            max-height: 460px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.2);
            display: none;
            overflow: hidden;
        }

        .material-filter-popup.show {
            display: block;
        }

        .material-filter-popup-head {
            padding: 0.45rem 0.6rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.74rem;
            font-weight: 700;
            color: #1e293b;
            background: #f1f5f9;
        }

        .material-filter-popup-search {
            padding: 0.45rem 0.6rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .material-filter-popup-sort {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.2rem;
            padding: 0.35rem 0.35rem 0.2rem;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .material-filter-popup-sort .btn {
            justify-content: flex-start;
            padding: 0.28rem 0.45rem;
            font-size: 0.74rem;
            border-radius: 3px;
            border: 1px solid transparent;
            background: transparent;
            color: #0f172a;
        }

        .material-filter-popup-sort .btn.is-active {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1d4ed8;
        }

        .material-filter-popup-sort .btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .material-filter-separator {
            border-top: 1px solid #e2e8f0;
            margin: 0.2rem 0.35rem;
        }

        .material-filter-clear-line {
            padding: 0 0.5rem 0.35rem;
        }

        .material-filter-clear-line .btn {
            width: 100%;
            justify-content: flex-start;
            border-radius: 3px;
            background: transparent;
            border: 1px solid transparent;
            font-size: 0.73rem;
            color: #0f172a;
            padding: 0.28rem 0.45rem;
        }

        .material-filter-clear-line .btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .material-filter-popup-search input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 2px;
            padding: 0.32rem 0.45rem;
            font-size: 0.74rem;
        }

        .material-filter-popup-list {
            max-height: 230px;
            overflow: auto;
            padding: 0.4rem 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 0.22rem;
        }

        .material-filter-popup-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.74rem;
            color: #334155;
            line-height: 1.25;
            word-break: break-word;
        }

        .material-filter-popup-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            padding: 0.45rem 0.65rem 0.55rem;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .material-filter-popup-actions .btn {
            padding: 0.32rem 0.65rem;
            font-size: 0.73rem;
        }

        /* Highlight import-sensitive pricing columns: Amount 1 .. Import Tax */
        .form-page .material-table thead th:nth-child(8),
        .form-page .material-table thead th:nth-child(9),
        .form-page .material-table thead th:nth-child(10),
        .form-page .material-table thead th:nth-child(11),
        .form-page .material-table thead th:nth-child(12),
        .form-page .material-table thead th:nth-child(13),
        .form-page .material-table thead th:nth-child(14) {
            background: #fff5cc;
        }

        .form-page .material-table tbody td:nth-child(8),
        .form-page .material-table tbody td:nth-child(9),
        .form-page .material-table tbody td:nth-child(10),
        .form-page .material-table tbody td:nth-child(11),
        .form-page .material-table tbody td:nth-child(12),
        .form-page .material-table tbody td:nth-child(13),
        .form-page .material-table tbody td:nth-child(14) {
            background: #fffdf0;
        }

        .form-page .cycle-table-container {
            max-width: 100%;
            overflow: auto;
            border: 1px solid var(--slate-200);
            border-radius: 1rem;
            background: white;
        }

        .form-page .cycle-table {
            min-width: 1100px;
        }

        .form-page .cycle-table th {
            position: static;
            padding: 0.65rem 0.45rem;
            font-size: 0.65rem;
        }

        .form-page .cycle-table td {
            padding: 0.4rem 0.35rem;
        }

        .form-page .cycle-table .form-input,
        .form-page .cycle-table .form-select {
            min-width: 0;
            padding: 0.5rem 0.6rem;
            font-size: 0.75rem;
        }

        .form-page .cycle-table .ct-process {
            min-width: 260px;
        }

        .form-page .cycle-table .ct-qty,
        .form-page .cycle-table .ct-hour,
        .form-page .cycle-table .ct-sec,
        .form-page .cycle-table .ct-sec-per,
        .form-page .cycle-table .ct-cost-sec,
        .form-page .cycle-table .ct-cost-unit {
            min-width: 110px;
            text-align: right;
        }

        .form-page .cycle-table .ct-cost-sec,
        .form-page .cycle-table .ct-cost-unit {
            background: #a3e635;
            font-weight: 600;
            color: #1f2937;
        }

        .form-page .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .form-page .form-section-title {
            justify-content: flex-start;
            gap: 0.55rem;
        }

        .form-page .section-actions {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .form-page .section-actions + .section-toggle {
            margin-left: 0.5rem !important;
        }

        .form-page .section-toggle {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 1px solid var(--slate-200);
            border-radius: 0.5rem;
            background: #fff;
            color: var(--slate-600);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-page .section-toggle:hover {
            border-color: var(--blue-300);
            color: var(--blue-700);
            background: var(--blue-50);
        }

        .form-page .btn + .section-toggle {
            margin-left: 0.5rem;
        }

        .form-page .section-toggle svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s ease;
        }

        .confirm-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1000;
        }

        .confirm-modal.is-hidden {
            display: none;
        }

        .confirm-modal-card {
            width: min(460px, 100%);
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 0.95rem;
            box-shadow: 0 24px 52px rgba(15, 23, 42, 0.24);
            overflow: hidden;
        }

        .confirm-modal-head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border-bottom: 1px solid var(--slate-200);
        }

        .confirm-modal-icon {
            width: 30px;
            height: 30px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #9a6b00;
            background: #fff7da;
            border: 1px solid #f7d37a;
            flex-shrink: 0;
        }

        .confirm-modal-title {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--slate-800);
            margin: 0;
        }

        .confirm-modal-body {
            padding: 1rem 1.1rem 1.15rem;
            color: var(--slate-700);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .confirm-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            padding: 0 1.1rem 1.1rem;
        }

        .form-page .form-section.is-collapsed .section-toggle svg {
            transform: rotate(-90deg);
        }

        .form-page .form-section.is-collapsed > :not(.form-section-title) {
            display: none;
        }

        @media (min-width: 1081px) {
            .form-page .quantity-group {
                grid-column: 6 / span 2;
                grid-row: 1;
            }

            .form-page .project-life-group {
                grid-column: 1;
                grid-row: 2;
            }

            .form-page .plant-group {
                grid-column: 2;
                grid-row: 2;
            }

            .form-page .period-group {
                grid-column: 3;
                grid-row: 2;
            }

            .form-page .form-grid.project-info-grid {
                grid-template-columns: repeat(7, minmax(0, 1fr));
            }

            .form-page .pic-marketing-group {
                grid-column: 4;
                grid-row: 2;
            }

            .form-page .pic-engineering-group {
                grid-column: 5;
                grid-row: 2;
            }
        }

        @media (max-width: 1400px) {
            .form-page .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            }

            .form-page .material-table {
                min-width: 1240px;
            }

            .form-page .cycle-table {
                min-width: 1040px;
            }
        }

        @media (max-width: 1080px) {
            .form-page .form-grid-2,
            .form-page .param-grid,
            .form-page .cost-grid {
                grid-template-columns: 1fr !important;
            }

            .form-page .quantity-with-options {
                gap: 0.4rem;
            }
        }

        @media (max-width: 768px) {
            .form-page {
                gap: 1rem;
            }

            .form-page .card {
                padding: 1rem;
            }

            .form-page .material-table {
                min-width: 1120px;
            }

            .form-page .cycle-table {
                min-width: 980px;
            }

            .form-page .form-actions {
                flex-direction: column-reverse;
            }

    
        .form-page .form-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }

    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }
    .toast-container .alert {
        pointer-events: auto;
        min-width: 300px;
        max-width: 450px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 0;
        opacity: 0;
        transform: translateX(120%);
        animation: toast-slide-in 0.3s ease-out forwards;
        cursor: pointer;
    }
    .toast-container .alert.toast-fadeOut {
        animation: toast-fade-out 0.3s ease-in forwards !important;
    }
    @keyframes toast-slide-in {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes toast-fade-out {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(120%); opacity: 0; }
    }
    .form-page .costing-resume-preview {
        margin-top: 1.5rem;
        border: 1px solid #c8d7ec;
        background: #f8fafc;
        overflow-x: auto;
        padding: 1rem;
    }

    .form-page .costing-resume-sheet {
        display: grid;
        grid-template-columns: 92px 1fr;
        width: min(940px, 100%);
        min-height: 720px;
        margin: 0 auto;
        border: 1px solid #0f172a;
        background: #ffffff;
    }

    .form-page .costing-resume-side {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #d9d9d9;
        border-right: 1px solid #0f172a;
        color: #020617;
        font-size: 2.35rem;
        font-weight: 900;
        letter-spacing: 0;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        text-align: center;
    }

    .form-page .costing-resume-main {
        min-width: 0;
        padding: 0 0 1.25rem;
    }

    .form-page .costing-resume-title {
        background: #0b3478;
        color: #ffffff;
        text-align: center;
        font-size: 1.42rem;
        font-weight: 800;
        line-height: 1.2;
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .form-page .costing-resume-reset {
        border: 1px solid rgba(255, 255, 255, 0.42);
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        padding: 0.25rem 0.55rem;
        font-size: 0.72rem;
        font-weight: 700;
        cursor: pointer;
    }

    .form-page .costing-resume-reset:hover {
        background: rgba(255, 255, 255, 0.24);
    }

    .form-page .costing-resume-editable {
        border-radius: 4px;
        outline: 1px dashed transparent;
        outline-offset: 2px;
        cursor: text;
    }

    .form-page .costing-resume-editable:hover,
    .form-page .costing-resume-editable:focus {
        background: #fff7ed;
        outline-color: #f59e0b;
    }

    .form-page .costing-resume-top {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) minmax(260px, 0.85fr);
        gap: 1rem;
        padding: 1rem 1.15rem 0.75rem;
        align-items: start;
    }

    .form-page .costing-resume-info {
        display: grid;
        grid-template-columns: 112px 12px 1fr;
        gap: 0.25rem 0.45rem;
        font-size: 0.92rem;
        color: #020617;
        line-height: 1.55;
    }

    .form-page .costing-resume-rate {
        border: 1px solid #0f172a;
        font-size: 0.92rem;
        background: #ffffff;
    }

    .form-page .costing-resume-rate-title {
        background: #9db8d7;
        border-bottom: 1px solid #0f172a;
        font-weight: 800;
        padding: 0.22rem 0.45rem;
        text-transform: uppercase;
    }

    .form-page .costing-resume-rate-row {
        display: grid;
        grid-template-columns: 1fr 46px;
        gap: 0.5rem;
        padding: 0.18rem 0.45rem;
        border-bottom: 1px solid #0f172a;
        text-align: right;
    }

    .form-page .costing-resume-rate-row:last-child {
        border-bottom: 0;
    }

    .form-page .costing-resume-labour {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        padding-top: 0.5rem;
        font-size: 0.92rem;
    }

    .form-page .costing-resume-table-wrap,
    .form-page .costing-resume-summary {
        padding: 0 1rem;
    }

    .form-page .costing-resume-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.92rem;
        color: #020617;
    }

    .form-page .costing-resume-table th {
        background: #9db8d7;
        padding: 0.42rem 0.5rem;
        font-weight: 800;
        text-align: center;
    }

    .form-page .costing-resume-table td {
        padding: 0.36rem 0.5rem;
        vertical-align: middle;
    }

    .form-page .costing-resume-table .section-row td {
        border-top: 1px solid #0f172a;
        font-weight: 800;
        padding-top: 0.45rem;
    }

    .form-page .costing-resume-table .text-right,
    .form-page .costing-resume-summary .text-right {
        text-align: right;
    }

    .form-page .costing-resume-summary {
        margin-top: 0.7rem;
    }

    .form-page .costing-resume-summary-row {
        display: grid;
        grid-template-columns: 1fr 70px 140px 72px;
        align-items: center;
        min-height: 36px;
        border-top: 1px solid #0f172a;
        font-size: 0.92rem;
    }

    .form-page .costing-resume-summary-row.band {
        background: #d9d9d9;
        font-weight: 800;
    }

    .form-page .costing-resume-summary-row.total {
        background: #9db8d7;
        font-weight: 900;
        min-height: 48px;
    }

    .form-page .costing-resume-summary-row span {
        padding: 0.42rem 0.5rem;
    }

    .form-page .costing-resume-metrics {
        margin: 0.8rem 1rem 0;
        border-top: 1px solid #0f172a;
        font-size: 0.92rem;
    }

    .form-page .costing-resume-metric-row {
        display: grid;
        grid-template-columns: 1fr 54px 210px;
        min-height: 42px;
        align-items: center;
        border-bottom: 1px solid #0f172a;
    }

    .form-page .costing-resume-metric-row span {
        padding: 0.42rem 0.5rem;
    }

    .form-page .costing-resume-empty {
        color: #64748b;
    }

    @media (max-width: 1080px) {
        .form-page .costing-resume-sheet {
            grid-template-columns: 1fr;
        }

        .form-page .costing-resume-side {
            min-height: 54px;
            writing-mode: horizontal-tb;
            transform: none;
            border-right: 0;
            border-bottom: 1px solid #0f172a;
            font-size: 1.4rem;
        }

        .form-page .costing-resume-top {
            grid-template-columns: 1fr;
        }

        .form-page .costing-resume-summary-row,
        .form-page .costing-resume-metric-row {
            grid-template-columns: 1fr auto auto;
        }
    }
</style>
<style>
.costing-freeze{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:0 0 12px;padding:13px 16px;border:1px solid #f2c86f;border-radius:12px;background:linear-gradient(110deg,#fff8e5,#fffdf7);color:#754d00}.costing-freeze b,.costing-freeze span{display:block}.costing-freeze b{font-size:10px;letter-spacing:.08em}.costing-freeze div span{font-size:9px}.costing-freeze>span{padding:5px 8px;border-radius:7px;background:#f5c451;color:#573900;font-size:8px;font-weight:900}.import-safety{margin-bottom:12px;padding:11px 14px;border:1px solid #cbe0ef;border-radius:11px;background:#f8fcff;font-size:10px}.import-safety summary{color:#176598;font-weight:850;cursor:pointer}.import-safety>div{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-top:1px solid #deebf4}.import-safety b,.import-safety small{display:block}.import-safety small{color:#75899b}.import-safety button{padding:6px 9px;border:1px solid #f2b8b5;border-radius:7px;background:#fff1f0;color:#bc312b;font-size:8px;font-weight:850}
</style>
