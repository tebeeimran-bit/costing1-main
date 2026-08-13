    <style>
        .dashboard-filter-card {
            background: #ffffff;
            border: 1px solid #dbe4f2;
            border-radius: 16px;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .dashboard-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
            gap: 0.75rem;
            align-items: end;
        }

        .dashboard-filter-field label {
            display: block;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 850;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .dashboard-filter-input {
            width: 100%;
            border: 1px solid #cfe0f5;
            border-radius: 10px;
            padding: 0.62rem 0.72rem;
            font-size: 0.80rem;
            font-weight: 750;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            height: 39px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .dashboard-filter-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .dashboard-filter-btn {
            height: 39px;
            border: 0;
            border-radius: 10px;
            padding: 0 1rem;
            background: #2563eb;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.22);
            white-space: nowrap;
        }

        .dashboard-filter-btn:hover {
            background: #1d4ed8;
        }

        .dashboard-summary-stack {
            grid-template-columns: minmax(0, 1fr);
        }

        .status-overview-grid {
            display: grid;
            grid-template-columns: 180px minmax(250px, .8fr) minmax(420px, 1.4fr);
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .status-insight-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .status-insight-card {
            min-height: 86px;
            padding: .85rem;
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            background: #f8fafc;
        }

        .potential-origin {
            position: relative;
            cursor: help;
            border-radius: 7px;
            transition: background-color .15s ease, box-shadow .15s ease;
        }

        .potential-origin:hover {
            background: #fff7cc;
            box-shadow: inset 0 0 0 2px #facc15;
            z-index: 20;
        }

        .potential-origin:hover::after {
            content: attr(data-origin);
            position: absolute;
            right: 8px;
            bottom: calc(100% - 4px);
            width: min(430px, 75vw);
            padding: 11px 13px;
            border-radius: 9px;
            background: #0f172a;
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.55;
            text-align: left;
            white-space: pre-line;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .25);
            pointer-events: none;
        }

        @media (max-width: 1050px) {
            .status-overview-grid {
                grid-template-columns: 180px minmax(220px, 1fr);
            }

            .status-insight-grid {
                grid-column: 1 / -1;
            }
        }

        .dashboard-status-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dashboard-status-column {
            padding: 12px 14px;
            border-top: 1px solid var(--slate-200);
        }

        .dashboard-status-column + .dashboard-status-column {
            border-left: 1px solid var(--slate-200);
        }

        @media (max-width: 720px) {
            .status-overview-grid {
                grid-template-columns: 1fr;
            }

            .status-insight-grid {
                grid-column: auto;
                grid-template-columns: 1fr;
            }

            .dashboard-status-columns {
                grid-template-columns: 1fr;
            }

            .dashboard-status-column + .dashboard-status-column {
                border-left: 0;
            }
        }

        @media (max-width: 1180px) {
            .dashboard-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-filter-btn {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .dashboard-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
