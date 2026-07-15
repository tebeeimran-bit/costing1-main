@extends('layouts.app')

@section('title', 'Profile User')

@section('page-title', 'Profile User')

@section('breadcrumb')
    <span>Profile User</span>
@endsection

@section('content')
    <div class="card" style="max-width: 760px; margin: 0 auto;">
        <div class="form-section-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            Profile User
        </div>

        <div style="padding: 1.25rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="
                    width: 68px;
                    height: 68px;
                    border-radius: 20px;
                    background: #2563eb;
                    color: #ffffff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.75rem;
                    font-weight: 800;
                    flex-shrink: 0;
                ">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>

                <div>
                    <h2 style="margin: 0; font-size: 1.35rem; color: #1e293b;">
                        {{ auth()->user()->name ?? 'User' }}
                    </h2>

                    <p style="margin: 0.25rem 0 0; color: #64748b;">
                        {{ auth()->user()->email ?? '-' }}
                    </p>

                    <div style="
                        display: inline-flex;
                        margin-top: 0.5rem;
                        padding: 0.25rem 0.65rem;
                        border-radius: 999px;
                        background: #dbeafe;
                        color: #1d4ed8;
                        font-size: 0.75rem;
                        font-weight: 700;
                        text-transform: uppercase;
                    ">
                        {{ auth()->user()->role ?? 'user' }}
                    </div>
                </div>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 180px; padding: 0.9rem 1rem; background: #f8fafc; font-weight: 700; color: #334155;">
                            Nama
                        </td>
                        <td style="padding: 0.9rem 1rem; color: #334155;">
                            {{ auth()->user()->name ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 180px; padding: 0.9rem 1rem; background: #f8fafc; font-weight: 700; color: #334155; border-top: 1px solid #e2e8f0;">
                            Email
                        </td>
                        <td style="padding: 0.9rem 1rem; color: #334155; border-top: 1px solid #e2e8f0;">
                            {{ auth()->user()->email ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td style="width: 180px; padding: 0.9rem 1rem; background: #f8fafc; font-weight: 700; color: #334155; border-top: 1px solid #e2e8f0;">
                            Role
                        </td>
                        <td style="padding: 0.9rem 1rem; color: #334155; border-top: 1px solid #e2e8f0;">
                            {{ auth()->user()->role ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>

            <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem;">
                <a href="{{ route('dashboard', absolute: false) }}" class="btn btn-primary">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
