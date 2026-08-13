    <style>
        .doc-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .doc-status-badge.ada { color: #065f46; background: #d1fae5; }
        .doc-status-badge.belum { color: #92400e; background: #fef3c7; }
        .td-a00 .doc-status-badge.ada { color: #1e40af; background: #dbeafe; }
        .td-a04 .doc-status-badge.ada { color: #991b1b; background: #fee2e2; }
        .td-a05 .doc-status-badge.ada { color: #166534; background: #dcfce7; }
        .td-a00 .doc-status-badge.belum { color: #6b7280; background: #e8edf4; }
        .td-a04 .doc-status-badge.belum { color: #6b7280; background: #f5e6e6; }
        .td-a05 .doc-status-badge.belum { color: #6b7280; background: #e6f0e8; }
        .doc-download-link {
            color: var(--blue-600);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .doc-download-link:hover { text-decoration: underline; }
        .doc-summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .doc-summary-card {
            min-height: 88px;
            padding: 1.25rem;
            border-radius: 12px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.25rem;
            width: 100%;
            box-sizing: border-box;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }
        .doc-summary-card .doc-label { font-size: 0.78rem; font-weight: 600; opacity: 0.9; }
        .doc-summary-card .doc-count { font-size: 1.75rem; font-weight: 800; }
        .doc-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .doc-filter-bar .form-input,
        .doc-filter-bar .form-select { max-width: 280px; }
        @media (max-width: 768px) {
            .doc-summary-cards { grid-template-columns: 1fr; }
        }
        /* Modal */
        .doc-modal {
            position: fixed; inset: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }
        .doc-modal.is-hidden { display: none; }
        .doc-modal-content {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 560px;
            padding: 1.5rem;
        }
        .doc-modal-content.doc-modal-wide {
            max-width: 1180px;
        }
        .doc-modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem;
        }
        .doc-modal-title { font-size: 1.1rem; font-weight: 700; color: var(--slate-800); }
        .doc-modal-close {
            border: 0; background: var(--slate-100); border-radius: 8px;
            padding: 0.4rem; cursor: pointer; color: var(--slate-500);
        }
        .doc-modal-close:hover { background: var(--slate-200); }
        .doc-form-group { margin-bottom: 0.75rem; }
        .doc-form-group label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--slate-600); text-transform: uppercase; margin-bottom: 0.3rem; }
        .doc-form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--slate-200); }
        .doc-section-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .doc-section-col {
            padding: 0.85rem;
            border-radius: 10px;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
        }
        .doc-section-title {
            font-size: 0.78rem; font-weight: 700;
            margin: 0 0 0.65rem; padding-bottom: 0.35rem;
            border-bottom: 2px solid var(--slate-200);
        }
        .doc-section-col:nth-child(1) .doc-section-title { color: #2563eb; border-color: #93c5fd; }
        .doc-section-col:nth-child(2) .doc-section-title { color: #dc2626; border-color: #fca5a5; }
        .doc-section-col:nth-child(3) .doc-section-title { color: #16a34a; border-color: #86efac; }
        .doc-section-col:nth-child(4) .doc-section-title { color: #2563eb; border-color: #93c5fd; }
        .doc-section-col:nth-child(5) .doc-section-title { color: #0f766e; border-color: #5eead4; }
        .btn-action {
            display: inline-flex; align-items: center; justify-content: center;
            border: 0; border-radius: 6px; padding: 0.35rem; cursor: pointer;
            transition: background 0.15s;
        }
        .btn-action.btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-action.btn-edit:hover { background: #bfdbfe; }
        .btn-action.btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-action.btn-delete:hover { background: #fecaca; }
        .delete-modal-body { text-align: center; padding: 1rem 0; }
        .delete-modal-text { font-size: 0.9rem; color: var(--slate-600); margin-bottom: 0.5rem; }
        .delete-modal-name { font-weight: 700; color: var(--slate-800); }
        /* Pagination */
        .doc-pagination .pagination { display: flex; gap: 0.25rem; margin: 0; list-style: none; padding: 0; }
        .doc-pagination .page-item .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 2rem; height: 2rem; padding: 0 0.5rem;
            border-radius: 6px; border: 1px solid var(--slate-200);
            font-size: 0.82rem; font-weight: 600; color: var(--slate-600);
            background: #fff; text-decoration: none; transition: all 0.15s;
        }
        .doc-pagination .page-item .page-link:hover { background: #eff6ff; color: #2563eb; border-color: #93c5fd; }
        .doc-pagination .page-item.active .page-link { background: #2563eb; color: #fff; border-color: #2563eb; }
        .doc-pagination .page-item.disabled .page-link { opacity: 0.4; pointer-events: none; }
        /* Color-coded table columns */
        .th-a00 { background: #2563eb !important; color: #fff !important; }
        .th-a04 { background: #dc2626 !important; color: #fff !important; }
        .th-a05 { background: #16a34a !important; color: #fff !important; }
        .td-a00 { background: #eff6ff; }
        .td-a04 { background: #fef2f2; }
        .td-a05 { background: #f0fdf4; }

        /* Engineering document collection cards */
        .engineering-doc-panel {
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 14px;
            padding: 1.15rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }
        .engineering-doc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .engineering-doc-title {
            margin: 0;
            color: var(--slate-800);
            font-size: 1rem;
            font-weight: 850;
        }
        .engineering-doc-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .btn-folder-storage {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 34px;
            padding: 0.48rem 0.75rem;
            border-radius: 9px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border: 1px solid rgba(37, 99, 235, 0.25);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 900;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
            white-space: nowrap;
        }
        .btn-folder-storage:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
        }
        .engineering-doc-note {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.65rem;
            border-radius: 9px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .engineering-doc-cards {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.85rem;
        }
        .engineering-doc-card {
            min-height: 86px;
            border-radius: 13px;
            border: 1px solid transparent;
            padding: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            overflow: hidden;
        }
        .engineering-doc-card.blue { background: linear-gradient(135deg, #eff6ff, #dbeafe); border-color: #bfdbfe; color: #1d4ed8; }
        .engineering-doc-card.yellow { background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a; color: #b45309; }
        .engineering-doc-card.orange { background: linear-gradient(135deg, #fff7ed, #ffedd5); border-color: #fed7aa; color: #ea580c; }
        .engineering-doc-card.green { background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #bbf7d0; color: #15803d; }
        .engineering-doc-card.red { background: linear-gradient(135deg, #fef2f2, #fee2e2); border-color: #fecaca; color: #dc2626; }
        .engineering-doc-card.purple { background: linear-gradient(135deg, #faf5ff, #f3e8ff); border-color: #e9d5ff; color: #7e22ce; }
        .engineering-doc-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.66);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.5);
            flex: 0 0 auto;
        }
        .engineering-doc-icon svg {
            width: 22px;
            height: 22px;
        }
        .engineering-doc-label {
            font-size: 0.76rem;
            font-weight: 850;
            margin-bottom: 0.18rem;
        }
        .engineering-doc-count {
            font-size: 1.55rem;
            line-height: 1;
            font-weight: 950;
        }
        .engineering-doc-unit {
            font-size: 0.72rem;
            color: var(--slate-600);
            font-weight: 750;
            margin-top: 0.28rem;
        }
        .th-partlist { background: #2563eb !important; color: #fff !important; }
        .th-umh { background: #0f766e !important; color: #fff !important; }
        .td-partlist { background: #f8fafc; }
        .td-umh { background: #f0fdfa; }
        @media (max-width: 1280px) {
            .engineering-doc-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 768px) {
            .engineering-doc-cards { grid-template-columns: 1fr; }
            .engineering-doc-head { align-items: flex-start; flex-direction: column; }
            .engineering-doc-note { white-space: normal; }
        }
    </style>
