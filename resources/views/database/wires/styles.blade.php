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
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1rem;
            border: none;
            background: #dc2626;
            color: white;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .rates-matrix {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 1rem;
        }

        .rates-matrix th,
        .rates-matrix td {
            border: 1px solid #cbd5e1;
            text-align: center;
            padding: 0.3rem 0.25rem;
        }

        .rates-currency {
            background: #ffe800;
            color: #111827;
            font-weight: 800;
            width: 72px;
        }

        .rates-number {
            background: #ffffff;
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .rates-month {
            background: #ffe800;
            color: #111827;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .rates-lme-title {
            background: #0f4d73;
            color: #ffef00;
            font-weight: 800;
            font-size: 0.85rem;
            letter-spacing: 0.02em;
        }

        .rates-lme-active {
            background: #9ac2d3;
            color: #c30000;
            font-size: 1.7rem;
            font-weight: 800;
            line-height: 1.05;
        }

        .rates-lme-reference {
            background: #9ac2d3;
            color: #c30000;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.05;
        }

        .rates-spacer {
            background: #e2e8f0;
            width: 72px;
        }

        .wire-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 1rem;
        }

        .wire-modal.is-hidden {
            display: none;
        }

        .wire-modal-content {
            width: min(640px, 100%);
            background: #fff;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .wire-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .wire-modal-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .wire-form {
            padding: 1.25rem;
            display: grid;
            gap: 0.9rem;
        }

        .wire-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-top: 0.25rem;
        }
    </style>
