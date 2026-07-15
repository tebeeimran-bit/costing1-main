<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light only">
    <title>Pilih Menu Utama - Costing System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    </noscript>
    <style>
        html { color-scheme: light only !important; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 125vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f172a;
            background:
                radial-gradient(circle at 50% 18%, rgba(37, 99, 235, 0.11), transparent 34%),
                linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
            color-scheme: light only;
            zoom: 0.8;
        }
        @supports not (zoom: 1) {
            body {
                width: 125%;
                transform: scale(0.8);
                transform-origin: top left;
            }
        }
        .project-selection-page { min-height: 125vh; display: flex; flex-direction: column; }
        .topbar {
            height: 106px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 52%, #3b82f6 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            padding: 0 3rem;
            box-shadow: 0 10px 32px rgba(30, 64, 175, 0.22);
            position: relative;
            z-index: 2;
        }
        .topbar-inner { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .brand { display: flex; align-items: center; gap: 0.9rem; }
        .brand-icon {
            width: 48px; height: 48px; border-radius: 15px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(10px);
        }
        .brand-icon svg { width: 28px; height: 28px; color: #ffffff; }
        .brand-title { color: #ffffff; font-size: 1.35rem; font-weight: 900; letter-spacing: -0.035em; line-height: 1.05; }
        .brand-subtitle { color: rgba(255, 255, 255, 0.82); font-size: 0.84rem; font-weight: 520; margin-top: 0.2rem; }
        .user-area { display: flex; align-items: center; gap: 0.8rem; }
        .user-avatar {
            width: 44px; height: 44px; border-radius: 999px;
            background: #2563eb; border: 1px solid rgba(255,255,255,.24);
            display: inline-flex; align-items: center; justify-content: center;
            color: #ffffff; font-weight: 900; font-size: 1.2rem;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
        }
        .user-name { font-size: 1rem; font-weight: 850; color: #ffffff; line-height: 1; }
        .logout-form { display: inline-flex; }
        .logout-button {
            margin-left: 0.15rem; border: 0; background: transparent; color: rgba(255,255,255,.92);
            cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
            padding: 0.35rem; border-radius: 9px; transition: background 0.18s ease;
        }
        .logout-button:hover { background: rgba(255,255,255,.12); }
        .logout-button svg { width: 18px; height: 18px; }
        .main { flex: 1; display: flex; justify-content: center; padding: 4.4rem 1.5rem 3rem; }
        .content-shell { width: min(1060px, 100%); text-align: center; }
        .welcome-title {
            color: #0f172a; font-size: clamp(2rem, 3vw, 2.55rem);
            font-weight: 900; letter-spacing: -0.06em; line-height: 1.08; margin-bottom: 0.75rem;
        }
        .welcome-subtitle { color: #64748b; font-size: 1.1rem; font-weight: 520; margin-bottom: 3.3rem; }
        .menu-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2rem; align-items: stretch; }
        .menu-card {
            min-height: 360px; background: rgba(255,255,255,.92);
            border: 1px solid #dbe4f2; border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            padding: 2.8rem 2rem 2rem; text-decoration: none; color: inherit;
            position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }
        .menu-card::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 50% 0%, rgba(37,99,235,.06), transparent 42%),
                        linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.88));
            pointer-events: none;
        }
        .menu-card.available:hover { transform: translateY(-5px); border-color: #93c5fd; box-shadow: 0 24px 54px rgba(15, 23, 42, 0.13); }
        .menu-card.disabled { cursor: default; opacity: .94; }
        .menu-icon {
            width: 92px; height: 92px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            position: relative; z-index: 1; margin-bottom: 2.2rem;
        }
        .menu-icon svg { width: 44px; height: 44px; }
        .menu-icon.blue { color: #2563eb; background: linear-gradient(135deg, #dbeafe 0%, #eef5ff 100%); }
        .menu-icon.purple { color: #7c3aed; background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%); }
        .menu-title {
            position: relative; z-index: 1; font-size: 1.45rem; font-weight: 900;
            letter-spacing: -0.045em; line-height: 1.16; margin-bottom: 1.1rem;
        }
        .menu-title.blue { color: #1d4ed8; }
        .menu-title.purple { color: #7c3aed; }
        .menu-desc {
            position: relative; z-index: 1; color: #475569; font-size: 1rem;
            font-weight: 520; line-height: 1.65; max-width: 380px; margin: 0 auto 2rem;
        }
        .menu-action {
            position: relative; z-index: 1; width: 100%; margin-top: auto; height: 56px; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem;
            font-size: 1rem; font-weight: 850; text-decoration: none;
        }
        .menu-action.primary {
            color: #ffffff; background: linear-gradient(135deg, #1e40af 0%, #2563eb 56%, #1d4ed8 100%);
            box-shadow: 0 12px 22px rgba(37, 99, 235, 0.26);
        }
        .menu-action.coming-soon { color: #7c3aed; background: #f4efff; border: 1px solid #e9d5ff; }
        .menu-action svg { width: 20px; height: 20px; }
        .notice {
            width: min(760px, 100%); margin: 2.8rem auto 0; padding-top: 1.45rem;
            border-top: 1px solid #dbe4f2; color: #64748b;
            display: flex; align-items: center; justify-content: center; gap: 0.55rem;
            font-size: 0.9rem; font-weight: 520;
        }
        .notice svg { width: 18px; height: 18px; color: #64748b; }
        .footer {
            border-top: 1px solid #dbe4f2; text-align: center; color: #64748b;
            font-size: 0.9rem; font-weight: 520; padding: 1.4rem 1rem; background: rgba(255,255,255,.5);
        }
        @media (max-width: 860px) {
            .topbar { height: auto; padding: 1rem 1.25rem; }
            .topbar-inner { align-items: flex-start; }
            .user-name { display: none; }
            .main { padding-top: 2.4rem; }
            .welcome-subtitle { margin-bottom: 2rem; }
            .menu-grid { grid-template-columns: 1fr; gap: 1rem; }
            .menu-card { min-height: auto; padding: 2rem 1.25rem 1.25rem; }
        }
    </style>
</head>

<body>
    <div class="project-selection-page">
        <header class="topbar">
            <div class="topbar-inner">
                <div class="brand">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.15">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div>
                        <div class="brand-title">Costing System</div>
                        <div class="brand-subtitle">Dharma Electrindo Mfg</div>
                    </div>
                </div>

                <div class="user-area">
                    <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
                    <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>

                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-button" title="Logout">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="main">
            <section class="content-shell">
                <h1 class="welcome-title">Selamat datang, {{ auth()->user()->name ?? 'Admin' }}</h1>
                <p class="welcome-subtitle">Silakan pilih menu utama untuk melanjutkan</p>

                <div class="menu-grid">
                    <a href="{{ route('dashboard', absolute: false) }}" class="menu-card available">
                        <div class="menu-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.05">
                                <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1"/>
                                <rect x="3" y="6" width="18" height="15" rx="2"/>
                                <path d="M3 12h18"/>
                            </svg>
                        </div>

                        <h2 class="menu-title blue">Costing Project</h2>
                        <p class="menu-desc">Kelola dan analisa costing project, dokumen, dan seluruh data terkait project.</p>

                        <span class="menu-action primary">
                            Masuk ke Costing Project
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                <path d="M5 12h14"/>
                                <path d="M13 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>

                    <div class="menu-card disabled">
                        <div class="menu-icon purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.05">
                                <path d="M5 20V10h4v10H5z"/>
                                <path d="M10 20V4h4v16h-4z"/>
                                <path d="M15 20V7h4v13h-4z"/>
                            </svg>
                        </div>

                        <h2 class="menu-title purple">Costing Product Performance</h2>
                        <p class="menu-desc">Pantau dan analisa performa produk berdasarkan data costing.</p>

                        <span class="menu-action coming-soon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.15">
                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
                            </svg>
                            Coming Soon
                        </span>
                    </div>
                </div>

                <div class="notice">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <span>Menu baru akan segera tersedia.</span>
                </div>
            </section>
        </main>

        <footer class="footer">&copy; {{ date('Y') }} Dharma Electrindo Mfg. All rights reserved.</footer>
    </div>
</body>
</html>
