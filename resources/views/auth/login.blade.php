<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light only">
    <title>Login - Costing System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
    <style>
        html { color-scheme: light only !important; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #3b82f6 100%) !important;
            padding: 1rem;
            color-scheme: light only;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .login-logo-icon svg {
            width: 32px;
            height: 32px;
            color: #fff;
        }
        .login-logo h1 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .login-logo p {
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .login-card {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .login-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b !important;
            margin-bottom: 0.25rem;
        }
        .login-card .subtitle {
            color: #64748b !important;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #334155 !important;
            margin-bottom: 0.375rem;
        }
        .form-input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 10px;
            font-size: 0.875rem;
            font-family: inherit;
            color: #1e293b !important;
            background: #f8fafc !important;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: #2563eb !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .form-input::placeholder {
            color: #94a3b8;
        }
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-checkbox input {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
        }
        .form-checkbox label {
            font-size: 0.8125rem;
            color: #475569;
            cursor: pointer;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
            transform: translateY(-1px);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.625rem 0.875rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .error-message svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <div class="login-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <h1>Costing System</h1>
            <p>Dharma Electrindo Mfg</p>
        </div>

        <div class="login-card">
            <h2>Login</h2>
            <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>

            @if ($errors->any())
                <div class="error-message">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="login">Email atau Nama</label>
                    <input type="text" id="login" name="login" class="form-input"
                        placeholder="admin@company.com atau Admin"
                        value="{{ old('login') }}" required autofocus autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input"
                        placeholder="Masukkan password" required>
                </div>

                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Ingat saya</label>
                    </div>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>

        <div class="login-footer">
            &copy; {{ date('Y') }} Dharma Electrindo Mfg. All rights reserved.
        </div>
    </div>
</body>
</html>
