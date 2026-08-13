    <style>
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--blue-700) 0%, var(--blue-800) 100%);
            transform: translateY(-2px);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-edit {
            background: var(--blue-100);
            color: var(--blue-600);
        }

        .btn-edit:hover {
            background: var(--blue-200);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .aksi-col,
        .aksi-cell {
            width: 110px;
            min-width: 110px;
            text-align: center;
        }

        .material-table-container {
            overflow-x: hidden;
            width: 100%;
        }

        .material-table-container .data-table {
            table-layout: fixed;
            width: 100%;
        }

        .material-table-container .data-table th,
        .material-table-container .data-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .aksi-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            flex-wrap: nowrap;
            width: 100%;
        }

        .aksi-actions form {
            margin: 0;
        }

        .material-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .material-modal.is-hidden {
            display: none;
        }

        .material-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
        }

        .material-modal-dialog {
            position: relative;
            width: min(980px, 100%);
            max-height: calc(100vh - 2rem);
            overflow: auto;
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 0.75rem;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.28);
            padding: 1rem 1rem 1.25rem;
        }

        .material-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .material-modal-title {
            margin: 0;
            font-size: 1rem;
            color: var(--slate-800);
            font-weight: 700;
        }

        .material-modal-close {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            border: 1px solid var(--slate-300);
            background: #fff;
            color: var(--slate-700);
            cursor: pointer;
            font-size: 1.25rem;
            line-height: 1;
        }

        .material-errors {
            margin-bottom: 0.75rem;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        .material-errors ul {
            margin: 0;
            padding-left: 1rem;
        }

        .material-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .material-span-2 {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .form-group label {
            font-size: 0.83rem;
            color: var(--slate-700);
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1px solid var(--slate-300);
            border-radius: 0.5rem;
            padding: 0.6rem 0.75rem;
            font-size: 0.85rem;
            color: var(--slate-800);
            background: #fff;
        }

        .material-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 0.9rem;
            padding-top: 0.9rem;
            border-top: 1px solid var(--slate-200);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--slate-300);
            background: #fff;
            color: var(--slate-700);
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }

        .parts-pagination {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }

        .parts-page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 0.55rem;
            border: 1px solid var(--slate-300);
            border-radius: 0.45rem;
            background: #fff;
            color: var(--slate-700);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .parts-page-link.is-active {
            background: var(--blue-600);
            border-color: var(--blue-600);
            color: #fff;
        }

        .parts-page-dots {
            color: var(--slate-500);
            font-size: 0.8rem;
            padding: 0 0.15rem;
        }

        @media (max-width: 768px) {
            .parts-search-input {
                min-width: 0 !important;
                width: 100%;
            }

            .material-table-container .data-table th,
            .material-table-container .data-table td {
                padding: 0.45rem 0.4rem;
                font-size: 0.73rem;
                line-height: 1.2;
            }

            .btn-action {
                width: 30px;
                height: 30px;
            }

            .aksi-col,
            .aksi-cell {
                width: 85px;
                min-width: 85px;
            }

            .material-form-grid {
                grid-template-columns: 1fr;
            }

            .material-span-2 {
                grid-column: span 1;
            }
        }
    </style>
