@auth
    <style>
        .sidebar-user-card {
            padding: 0.85rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
        }

        .sidebar-user-profile-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: inherit;
            text-decoration: none;
            margin-bottom: 0.75rem;
        }

        .sidebar-user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            color: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .sidebar-user-meta {
            min-width: 0;
            flex: 1;
        }

        .sidebar-user-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.95);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-role {
            margin-top: 0.15rem;
            font-size: 0.62rem;
            color: rgba(255, 255, 255, 0.55);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-email {
            margin-top: 0.15rem;
            font-size: 0.62rem;
            color: rgba(255, 255, 255, 0.45);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .sidebar-user-action-btn,
        .sidebar-user-action-link {
            width: 100%;
            min-height: 34px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.68rem;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .sidebar-user-action-btn:hover,
        .sidebar-user-action-link:hover {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
        }

        .sidebar-user-logout-btn:hover {
            background: rgba(239, 68, 68, 0.22);
            border-color: rgba(239, 68, 68, 0.35);
        }

        .sidebar-user-action-btn svg,
        .sidebar-user-action-link svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        .sidebar-user-logout-form {
            margin: 0;
        }
    </style>

    <div class="sidebar-user-card">
        <a href="{{ route('profile.show', absolute: false) }}" class="sidebar-user-profile-link">
            <div class="sidebar-user-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>

            <div class="sidebar-user-meta">
                <div class="sidebar-user-name">
                    {{ auth()->user()->name ?? 'User' }}
                </div>

                <div class="sidebar-user-role">
                    {{ auth()->user()->role ?? 'user' }}
                </div>

                <div class="sidebar-user-email">
                    {{ auth()->user()->email ?? '-' }}
                </div>
            </div>
        </a>

        <div class="sidebar-user-actions">
            <a href="{{ route('profile.show', absolute: false) }}" class="sidebar-user-action-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                Profile
            </a>

            <form method="POST" action="{{ route('logout') }}" class="sidebar-user-logout-form">
                @csrf

                <button type="submit" class="sidebar-user-action-btn sidebar-user-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </div>
@endauth