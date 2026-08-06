<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light only">
    <title>@yield('title', 'Sistem Costing Manufaktur') - Dharma Electrindo Mfg</title>
    {{-- Load Google Fonts asynchronously so it never blocks page rendering --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"></noscript>
    {{-- Critical CSS inlined for instant first paint --}}
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;line-height:1.6}
        #page-loading-overlay{position:fixed;inset:0;background:rgba(15,23,42,.32);backdrop-filter:blur(1.5px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;transition:opacity .3s ease}
        #page-loading-overlay.hidden{opacity:0;pointer-events:none}
        .loading-card{min-width:210px;max-width:min(88vw,320px);border-radius:14px;background:rgba(255,255,255,.96);border:1px solid #dbe4f2;box-shadow:0 18px 45px rgba(15,23,42,.22);padding:1rem 1.1rem;text-align:center;animation:lcPopIn .2s ease}
        .loading-spinner{width:42px;height:42px;margin:0 auto .65rem;border-radius:999px;border:3px solid #dbeafe;border-top-color:#2563eb;border-right-color:#60a5fa;animation:sr .8s linear infinite}
        .loading-text{margin:0;color:#1e293b;font-size:.9rem;font-weight:600;letter-spacing:.01em}
        @keyframes sr{to{transform:rotate(360deg)}}
        @keyframes lcPopIn{from{opacity:0;transform:translateY(6px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
    </style>
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    <style>
        .project-selection-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            height: 38px;
            padding: 0 0.85rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.13);
            color: #ffffff;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 850;
            white-space: nowrap;
            backdrop-filter: blur(10px);
            transition: background 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
        }

        .project-selection-button:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.38);
            transform: translateY(-1px);
        }

        .project-selection-button svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        @media (max-width: 920px) {
            .project-selection-button span {
                display: none;
            }

            .project-selection-button {
                width: 38px;
                padding: 0;
            }
        }
    </style>
    <style>
        /* Fix Database submenu item clipping when menu is long */
        .sidebar-dropdown.open .sidebar-submenu {
            max-height: 1200px !important;
            overflow: visible !important;
        }

        .sidebar-submenu {
            padding-bottom: 0.5rem;
        }

        .sidebar-submenu .sidebar-nav-item {
            min-height: 38px;
        }

        .sidebar-nav {
            padding-bottom: 7rem;
        }
    </style>
</head>

<body>
    <!-- Page Loading Overlay -->
    <div id="page-loading-overlay">
        <div class="loading-card">
            <div class="loading-spinner"></div>
            <p class="loading-text">Memuat halaman...</p>
        </div>
    </div>

    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z" />
                            <path d="M2 17l10 5 10-5" />
                            <path d="M2 12l10 5 10-5" />
                        </svg>
                    </div>
                    <div class="sidebar-logo-text">
                        <span class="sidebar-logo-title">Costing System</span>
                        <span class="sidebar-logo-subtitle">Dharma Electrindo Mfg</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="sidebar-nav-section">
                    <div class="sidebar-nav-title">Menu Utama</div>
                        <a href="{{ route('dashboard', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('project', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('project') || request()->routeIs('tracking-documents.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18" />
                            <path d="M7 14l3-3 3 2 4-5" />
                        </svg>
                        <span>Project</span>
                    </a>
                    @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'admin_control_project'], true))
                    <a href="{{ route('control-project.a00.index', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('control-project.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16h16V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
                        <span>Control Project — A00</span>
                     </a>
                     @endif
                     @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'document_control'], true))
                     <a href="{{ route('document-control.inbox', absolute: false) }}"
                         class="sidebar-nav-item {{ request()->routeIs('document-control.*') ? 'active' : '' }}">
                         <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                             <path d="M9 3H5a2 2 0 0 0-2 2v16h16a2 2 0 0 0 2-2v-4"/><path d="M9 3v6h6"/><path d="M9 3h6l6 6v2"/><path d="M7 14h8M7 18h8"/>
                         </svg>
                         <span>Document Control</span>
                     </a>
                     @endif
                     @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'admin_costing'], true))
                     <a href="{{ route('breakdown.inbox', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('breakdown.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 9h16M9 9v11"/></svg>
                        <span>Inbox Breakdown</span>
                    </a>
                    @endif
                    @if(auth()->check() && in_array(auth()->user()->role, ['admin', 'admin_costing', 'editor', 'coordinator_costing'], true))
                    <a href="{{ route('costing.inbox', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('costing.inbox') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 9h16"/><path d="M9 9v11"/><path d="M13 13h4M13 17h4"/></svg>
                        <span>Inbox Costing</span>
                    </a>
                    <a href="{{ route('new-part-request.inbox', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('new-part-request.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/><circle cx="18" cy="18" r="4" fill="white"/><path d="M18 16v4M16 18h4"/></svg>
                        <span>Inbox New Part Request</span>
                    </a>
                    @endif
                    <a href="{{ route('marketing.cogm-inbox', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('marketing.cogm-inbox') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16v16H4z" />
                            <path d="M4 7l8 6 8-6" />
                        </svg>
                        <span>Inbox Marketing</span>
                    </a>
                    </div>
                <div class="sidebar-nav-section">
                    <div class="sidebar-nav-title">Input Data</div>
<div class="sidebar-dropdown">
                        <button class="sidebar-nav-item sidebar-dropdown-toggle" onclick="toggleDropdown(this)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                                <ellipse cx="12" cy="5" rx="9" ry="3" />
                            </svg>
                            <span>Database</span>
                            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2"
                                style="width: 16px; height: 16px; margin-left: auto; transition: transform 0.2s;">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </button>
                        <div class="sidebar-submenu">
                            <a href="{{ route('database.parts', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.parts') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                </svg>
                                <span>Part</span>
                            </a>
                            @if(Route::has('database.wires'))
                                <a href="{{ route('database.wires', absolute: false) }}"
                                    class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.wires*') ? 'active' : '' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 3v6" />
                                        <path d="M18 3v6" />
                                        <path d="M6 21v-6" />
                                        <path d="M18 21v-6" />
                                        <path d="M8 9h8" />
                                        <path d="M8 15h8" />
                                    </svg>
                                    <span>Wire</span>
                                </a>
                            @endif
                            <a href="{{ route('database.tubes', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.tubes') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M7 4h10" />
                                    <path d="M7 20h10" />
                                    <path d="M9 4v16" />
                                    <path d="M15 4v16" />
                                    <path d="M9 9h6" />
                                    <path d="M9 15h6" />
                                </svg>
                                <span>Tubes</span>
                            </a>
<a href="{{ route('database.customers', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.customers') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                <span>Customer</span>
                            </a>
                            <a href="{{ route('database.business-categories', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.business-categories*') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 17h10" />
                                </svg>
                                <span>Business Categories</span>
                            </a>
                            <a href="{{ route('database.plants', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.plants*') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 21h18" />
                                    <path d="M5 21V8l7-5 7 5v13" />
                                    <path d="M9 21v-6h6v6" />
                                </svg>
                                <span>Plant</span>
                            </a>
                            <a href="{{ route('database.pics', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.pics*') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="8.5" cy="7" r="4" />
                                    <path d="M20 8v6" />
                                    <path d="M23 11h-6" />
                                </svg>
                                <span>PIC</span>
                            </a>
                            <a href="{{ route('database.cycle-time-templates', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('database.cycle-time-templates*') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <polyline points="12 6 12 12 16 14" />
                                </svg>
                                <span>Cycle Time</span>
                            </a>
<a href="{{ route('rate-kurs', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('rate-kurs*') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                                <span>Rate & Kurs</span>
                            </a>
                            <a href="{{ route('unpriced-parts', absolute: false) }}"
                                class="sidebar-nav-item sidebar-submenu-item {{ request()->routeIs('unpriced-parts') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                <span>Unpriced Parts</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="sidebar-nav-section">
                    <div class="sidebar-nav-title">Laporan</div>
                    <a href="{{ route('laporan', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('laporan') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                            <line x1="4" y1="22" x2="4" y2="15"/>
                        </svg>
                        <span>Laporan & Export</span>
                    </a>
                    <a href="{{ route('database.project-documents', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('database.project-documents*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <path d="M9 15l2 2 4-4" />
                        </svg>
                        <span>Project Document</span>
                    </a>
                    <a href="{{ route('resume-cogm', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('resume-cogm') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>COGM Resume Analysis</span>
                    </a>
                    <a href="{{ route('analisis-tren', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('analisis-tren') || request()->routeIs('analisis-tren.canceled') || request()->routeIs('analisis-tren.engineering') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        <span>Document Trend Analysis</span>
                    </a>
                    <a href="{{ route('compare.costing', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('compare.costing') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h6"/><path d="M15 6h6"/><path d="M9 6a3 3 0 0 1 6 0"/><path d="M3 18h6"/><path d="M15 18h6"/><path d="M9 18a3 3 0 0 1 6 0"/><path d="M12 6v12"/></svg>
                        <span>Compare Costing</span>
                    </a>
</div>
                @if(auth()->check() && auth()->user()->role === 'admin')
                <div class="sidebar-nav-section">
                    <div class="sidebar-nav-title">Administrasi</div>
                    <a href="{{ route('permissions', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('permissions') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="8.5" cy="7" r="4"/>
                            <path d="M17 11l2 2 4-4"/>
                        </svg>
                        <span>Permission</span>
                    </a>
                    <a href="{{ route('assistant.training', absolute: false) }}"
                        class="sidebar-nav-item {{ request()->routeIs('assistant.training') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v18" />
                            <path d="M5 8h14" />
                            <path d="M7 16h10" />
                        </svg>
                        <span>Assistant Training</span>
                    </a>
                </div>
                @endif
            </nav>
            <div class="sidebar-footer" style="padding: 0.75rem 1rem; border-top: 1px solid #e2e8f0; background: rgba(255,255,255,0.96);">
                @auth
                    @php
                        $currentUser = auth()->user();
                        $displayName = $currentUser->name ?? 'User';
                        $displayRole = $currentUser->role ?? 'user';
                        $displayEmail = $currentUser->email ?? '-';
                        $profileUrl = Route::has('profile.show')
                            ? route('profile.show', absolute: false)
                            : '#';
                    @endphp

                    <style>
                        .sidebar-user-module {
                            padding: 0.85rem;
                            border-radius: 16px;
                            background: #ffffff;
                            border: 1px solid #dbeafe;
                            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.10);
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
                            background: #2563eb;
                            color: #ffffff;
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
                            font-weight: 800;
                            color: #0f172a;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }

                        .sidebar-user-role {
                            margin-top: 0.15rem;
                            font-size: 0.62rem;
                            color: #2563eb;
                            text-transform: uppercase;
                            letter-spacing: 0.06em;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            font-weight: 800;
                        }

                        .sidebar-user-email {
                            margin-top: 0.15rem;
                            font-size: 0.62rem;
                            color: #64748b;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }

                        .sidebar-user-actions {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 0.5rem;
                        }

                        .sidebar-user-action-link,
                        .sidebar-user-action-btn {
                            width: 100%;
                            min-height: 34px;
                            border: 1px solid #bfdbfe;
                            border-radius: 10px;
                            background: #eff6ff;
                            color: #1d4ed8;
                            font-size: 0.68rem;
                            font-family: inherit;
                            font-weight: 800;
                            cursor: pointer;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            gap: 0.35rem;
                            text-decoration: none;
                            transition: all 0.15s ease;
                        }

                        .sidebar-user-action-link:hover,
                        .sidebar-user-action-btn:hover {
                            background: #dbeafe;
                            color: #1e40af;
                        }

                        .sidebar-user-logout-btn {
                            background: #fee2e2;
                            border-color: #fecaca;
                            color: #dc2626;
                        }

                        .sidebar-user-logout-btn:hover {
                            background: #fecaca;
                            border-color: #fca5a5;
                            color: #b91c1c;
                        }

                        .sidebar-user-action-link svg,
                        .sidebar-user-action-btn svg {
                            width: 14px;
                            height: 14px;
                            flex-shrink: 0;
                        }

                        .sidebar-user-logout-form {
                            margin: 0;
                        }
                    </style>

                    <div class="sidebar-user-module">
                        <a href="{{ $profileUrl }}" class="sidebar-user-profile-link" @if($profileUrl === '#') onclick="return false;" @endif>
                            <div class="sidebar-user-avatar">
                                {{ strtoupper(substr($displayName, 0, 1)) }}
                            </div>

                            <div class="sidebar-user-meta">
                                <div class="sidebar-user-name">{{ $displayName }}</div>
                                <div class="sidebar-user-role">{{ $displayRole }}</div>
                                <div class="sidebar-user-email">{{ $displayEmail }}</div>
                            </div>
                        </a>

                        <div class="sidebar-user-actions">
                            <a href="{{ $profileUrl }}" class="sidebar-user-action-link" @if($profileUrl === '#') onclick="return false;" @endif>
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

                <p class="sidebar-footer-text" style="margin-top: 0.5rem; color: #64748b;">
                    © {{ date('Y') }} Dharma Electrindo Mfg
                </p>
            </div>
        </aside>

        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <div class="header-left">
                        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="3" y1="12" x2="21" y2="12" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <line x1="3" y1="18" x2="21" y2="18" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="header-title">@yield('page-title', 'Costing Per Product Dashboard')</h1>
                            <span class="header-subtitle">Dharma Electrindo Mfg</span>
                        </div>
                    </div>
                    <div class="header-right">
                        @yield('header-filters')
                        <a href="{{ route('project-selection', absolute: false) }}" class="project-selection-button" title="Kembali ke Pilih Menu Utama">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.15">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            </svg>
                            <span>Menu Utama</span>
                        </a>
                        @include('partials.top-notification-bell')
                        <nav class="nav-tabs">
                            <a href="{{ route('dashboard', absolute: false) }}"
                                class="nav-tab {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" rx="1" />
                                    <rect x="14" y="3" width="7" height="7" rx="1" />
                                    <rect x="14" y="14" width="7" height="7" rx="1" />
                                    <rect x="3" y="14" width="7" height="7" rx="1" />
                                </svg>
                                Dashboard
                            </a>
                            <a href="{{ route('database', absolute: false) }}"
                                class="nav-tab {{ request()->routeIs('database') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                                    <ellipse cx="12" cy="5" rx="9" ry="3" />
                                </svg>
                                Database
                            </a>
                            <a href="{{ route('form', absolute: false) }}"
                                class="nav-tab {{ request()->routeIs('form') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                                Form Costing
                            </a>
                            <a href="{{ route('compare.costing', absolute: false) }}"
                                class="nav-tab {{ request()->routeIs('compare.costing') ? 'active' : '' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h6" />
                                    <path d="M15 6h6" />
                                    <path d="M9 6a3 3 0 0 1 6 0" />
                                    <path d="M3 18h6" />
                                    <path d="M15 18h6" />
                                    <path d="M9 18a3 3 0 0 1 6 0" />
                                    <path d="M12 6v12" />
                                </svg>
                                Compare
                            </a>
                        </nav>
                    </div>
                </div>
            </header>

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <div class="breadcrumb-content">
                    <a href="{{ route('dashboard', absolute: false) }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                    @yield('breadcrumb')
                </div>
            </div>

            <!-- Main Content -->
            <main class="main-content">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="footer">
                <div class="footer-content">
                    <span>&copy; 2025 Dharma Electrindo Mfg. All rights reserved.</span>
                    <span>Sistem Costing Manufaktur v1.0</span>
                </div>
            </footer>
        </div>
    </div>

    <div id="app-confirm-modal" class="app-confirm-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title" onclick="closeAppConfirmOnOverlay(event)">
        <div class="app-confirm-card">
            <div class="app-confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                </svg>
            </div>
            <h3 id="app-confirm-title" class="app-confirm-title">Konfirmasi Aksi</h3>
            <p id="app-confirm-message" class="app-confirm-message">Apakah Anda yakin?</p>
            <div class="app-confirm-actions">
                <button type="button" class="app-confirm-btn app-confirm-btn-secondary" onclick="closeAppConfirm()">Batal</button>
                <button type="button" id="app-confirm-ok" class="app-confirm-btn app-confirm-btn-danger" onclick="executeAppConfirm()">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <div id="app-notify-modal" class="app-confirm-modal is-hidden" role="dialog" aria-modal="true" aria-labelledby="app-notify-title" onclick="closeAppNotifyOnOverlay(event)">
        <div class="app-confirm-card app-notify-card">
            <div class="app-confirm-icon app-notify-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 9v4" />
                    <path d="M12 16h.01" />
                </svg>
            </div>
            <h3 id="app-notify-title" class="app-confirm-title">Informasi</h3>
            <p id="app-notify-message" class="app-confirm-message">Ada informasi untuk Anda.</p>
            <div class="app-confirm-actions app-notify-actions">
                <button type="button" id="app-notify-ok" class="app-confirm-btn app-confirm-btn-primary">OK</button>
            </div>
        </div>
    </div>

    <div id="app-loading-overlay" class="app-loading-overlay is-hidden" role="status" aria-live="polite" aria-label="Memuat data">
        <div class="loading-card">
            <div class="loading-spinner"></div>
            <p id="app-loading-text" class="loading-text">Memuat halaman...</p>
        </div>
    </div>

    

    @include('partials.costing-assistant')

    <script>
        let appConfirmCurrentOnConfirm = null;
        let appNotifyCurrentOnClose = null;
        let appLoadingVisible = false;

        function showAppLoading(message) {
            const overlay = document.getElementById('app-loading-overlay');
            const textNode = document.getElementById('app-loading-text');

            if (!overlay) {
                return;
            }

            if (textNode) {
                textNode.textContent = message || 'Memuat halaman...';
            }

            if (appLoadingVisible) {
                return;
            }

            appLoadingVisible = true;
            overlay.classList.remove('is-hidden');
        }

        function hideAppLoading() {
            const overlay = document.getElementById('app-loading-overlay');
            if (!overlay) {
                return;
            }

            appLoadingVisible = false;
            overlay.classList.add('is-hidden');
        }

        function openAppConfirm(message, onConfirm, options = {}) {
            const modal = document.getElementById('app-confirm-modal');
            const messageNode = document.getElementById('app-confirm-message');
            const titleNode = document.getElementById('app-confirm-title');
            const okButton = document.getElementById('app-confirm-ok');

            messageNode.textContent = message || 'Apakah Anda yakin ingin melanjutkan?';
            titleNode.textContent = options.title || 'Konfirmasi Aksi';
            okButton.textContent = options.buttonLabel || 'Ya, Lanjutkan';
            okButton.classList.toggle('app-confirm-btn-danger', options.tone !== 'primary');
            okButton.classList.toggle('app-confirm-btn-primary', options.tone === 'primary');
            appConfirmCurrentOnConfirm = onConfirm;
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
            okButton.focus();
        }

        function closeAppConfirm() {
            const modal = document.getElementById('app-confirm-modal');
            appConfirmCurrentOnConfirm = null;
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';
        }

        function executeAppConfirm() {
            if (typeof appConfirmCurrentOnConfirm === 'function') {
                const callback = appConfirmCurrentOnConfirm;
                closeAppConfirm();
                callback();
            } else {
                closeAppConfirm();
            }
        }

        function openAppNotify(message, onClose) {
            const modal = document.getElementById('app-notify-modal');
            const messageNode = document.getElementById('app-notify-message');
            const okButton = document.getElementById('app-notify-ok');

            if (!modal || !messageNode || !okButton) {
                window.alert(message || 'Ada informasi untuk Anda.');
                if (typeof onClose === 'function') {
                    onClose();
                }
                return;
            }

            messageNode.textContent = message || 'Ada informasi untuk Anda.';
            appNotifyCurrentOnClose = onClose;
            modal.classList.remove('is-hidden');
            document.body.style.overflow = 'hidden';
            okButton.focus();
        }

        function closeAppNotify() {
            const modal = document.getElementById('app-notify-modal');
            if (!modal) {
                return;
            }

            const onClose = appNotifyCurrentOnClose;
            appNotifyCurrentOnClose = null;
            modal.classList.add('is-hidden');
            document.body.style.overflow = '';

            if (typeof onClose === 'function') {
                onClose();
            }
        }

        function closeAppNotifyOnOverlay(event) {
            if (event.target && event.target.id === 'app-notify-modal') {
                closeAppNotify();
            }
        }

        function closeAppConfirmOnOverlay(event) {
            if (event.target && event.target.id === 'app-confirm-modal') {
                closeAppConfirm();
            }
        }

        function shouldShowLoadingForLink(link, event) {
            if (!(link instanceof HTMLAnchorElement)) {
                return false;
            }

            if (link.dataset.skipLoadingOverlay === 'true' || event.defaultPrevented) {
                return false;
            }

            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return false;
            }

            if (link.target && link.target !== '_self') {
                return false;
            }

            if (link.hasAttribute('download')) {
                return false;
            }

            const hrefValue = link.getAttribute('href') || '';
            const lowerHref = hrefValue.trim().toLowerCase();
            if (!lowerHref || lowerHref === '#' || lowerHref.startsWith('javascript:') || lowerHref.startsWith('mailto:') || lowerHref.startsWith('tel:')) {
                return false;
            }

            try {
                const destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin) {
                    return false;
                }

                const isSamePageAnchor = destination.pathname === window.location.pathname
                    && destination.search === window.location.search
                    && destination.hash;
                if (isSamePageAnchor) {
                    return false;
                }

                return true;
            } catch (_) {
                return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const okButton = document.getElementById('app-confirm-ok');
            const notifyOkButton = document.getElementById('app-notify-ok');

            if (okButton) {
                okButton.addEventListener('click', function () {
                    if (typeof appConfirmCurrentOnConfirm === 'function') {
                        const callback = appConfirmCurrentOnConfirm;
                        closeAppConfirm();
                        callback();
                        return;
                    }

                    closeAppConfirm();
                });
            }

            if (notifyOkButton) {
                notifyOkButton.addEventListener('click', function () {
                    closeAppNotify();
                });
            }

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!form.classList || !form.classList.contains('js-confirm-form')) {
                    return;
                }

                event.preventDefault();
                const message = form.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan?';
                openAppConfirm(message, function () {
                    showAppLoading();
                    HTMLFormElement.prototype.submit.call(form);
                }, {
                    title: form.dataset.confirmTitle || 'Konfirmasi Aksi',
                    buttonLabel: form.dataset.confirmButton || 'Ya, Lanjutkan',
                    tone: form.dataset.confirmTone || 'danger',
                });
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                if (event.defaultPrevented) {
                    return;
                }

                if (form.dataset.skipLoadingOverlay === 'true') {
                    return;
                }

                showAppLoading();
            });

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');
                if (!shouldShowLoadingForLink(link, event)) {
                    return;
                }

                showAppLoading('Memuat halaman...');
            }, true);

            window.addEventListener('beforeunload', function () {
                showAppLoading('Memuat halaman...');
            });

            window.addEventListener('pageshow', function () {
                hideAppLoading();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                const modal = document.getElementById('app-confirm-modal');
                if (!modal.classList.contains('is-hidden')) {
                    closeAppConfirm();
                    return;
                }

                const notifyModal = document.getElementById('app-notify-modal');
                if (notifyModal && !notifyModal.classList.contains('is-hidden')) {
                    closeAppNotify();
                }
            });
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        function toggleDropdown(button) {
            const dropdown = button.closest('.sidebar-dropdown');
            dropdown.classList.toggle('open');
        }
    </script>

    <script>
        function forceHideAllLoadingOverlays() {
            hideAppLoading();

            const pageOverlay = document.getElementById('page-loading-overlay');
            if (pageOverlay) {
                pageOverlay.classList.add('hidden');
                pageOverlay.style.display = 'none';
            }

            const appOverlay = document.getElementById('app-loading-overlay');
            if (appOverlay) {
                appOverlay.classList.add('is-hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', forceHideAllLoadingOverlays);
        window.addEventListener('load', forceHideAllLoadingOverlays);
        window.addEventListener('pageshow', forceHideAllLoadingOverlays);

        // Safety fallback supaya overlay tidak bisa stuck terlalu lama.
        setTimeout(forceHideAllLoadingOverlays, 1500);
    </script>

    @yield('scripts')
</body>

</html>
