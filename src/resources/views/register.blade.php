@extends('layouts.main')
@section('title', 'Register - Tech Axis')

@push('styles')
    <link href="{{ asset('css/register.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="card register-card">
        <h2>Register</h2>

        @if ($errors->any())
            <div class="error-messages">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="register-form" method="POST" action="{{ route('register') }}" novalidate>
            @csrf

            <label for="role">Registering As</label>
            <select id="role" name="role" required>
                <option value="customer" @selected(old('role', 'customer') === 'customer')>Customer</option>
                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
            </select>

            <label for="name">Full Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name">

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">

            <div id="pw-checklist" class="pw-checklist" aria-live="polite">
                <div class="pw-checklist-title">Password must include:</div>
                <ul>
                    <li class="pw-rule not-met" id="rule-len">At least 8 characters</li>
                    <li class="pw-rule not-met" id="rule-upper">At least one uppercase letter</li>
                    <li class="pw-rule not-met" id="rule-lower">At least one lowercase letter</li>
                    <li class="pw-rule not-met" id="rule-num">At least one number</li>
                    <li class="pw-rule not-met" id="rule-sym">At least one symbol</li>
                </ul>
            </div>

            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <div id="admin-code-wrap" class="admin-code-wrap d-none">
                <label for="admin_code">Admin Code</label>
                <input id="admin_code" type="text" name="admin_code" value="{{ old('admin_code') }}">
            </div>

            <button type="submit">Create Account</button>
        </form>

        <p class="auth-alt">Already have an account? <a href="{{ route('login.page') }}">Log In</a></p>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.getElementById('role');
            const adminCodeWrap = document.getElementById('admin-code-wrap');
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const registerForm = document.getElementById('register-form');

            const setRule = (el, met) => {
                if (!el) return;
                el.classList.toggle('is-met', met);
                el.classList.toggle('not-met', !met);
            };

            const passwordRules = (pw) => ({
                len: pw.length >= 8,
                upper: /[A-Z]/.test(pw),
                lower: /[a-z]/.test(pw),
                num: /[0-9]/.test(pw),
                sym: /[^A-Za-z0-9]/.test(pw),
            });

            const isStrongPassword = (pw) => {
                const r = passwordRules(pw);
                return r.len && r.upper && r.lower && r.num && r.sym;
            };

            const updateChecklist = (pw) => {
                const r = passwordRules(pw);
                setRule(document.getElementById('rule-len'), r.len);
                setRule(document.getElementById('rule-upper'), r.upper);
                setRule(document.getElementById('rule-lower'), r.lower);
                setRule(document.getElementById('rule-num'), r.num);
                setRule(document.getElementById('rule-sym'), r.sym);
            };

            const toggleAdminCodeRow = () => {
                if (!roleSelect || !adminCodeWrap) return;
                adminCodeWrap.classList.toggle('d-none', roleSelect.value !== 'admin');
            };

            const updatePasswordValidity = () => {
                const pw = passwordInput.value;
                updateChecklist(pw);
                passwordInput.setCustomValidity(
                    isStrongPassword(pw) ? '' : 'Password must include uppercase, lowercase, number, symbol, and be 8+ chars.'
                );
            };

            const updateConfirmValidity = () => {
                const confirmVal = confirmInput.value;
                if (confirmVal.length === 0) {
                    confirmInput.setCustomValidity('');
                    return;
                }

                confirmInput.setCustomValidity(
                    passwordInput.value === confirmVal ? '' : 'Passwords do not match.'
                );
            };

            roleSelect?.addEventListener('change', toggleAdminCodeRow);
            passwordInput?.addEventListener('input', () => {
                updatePasswordValidity();
                updateConfirmValidity();
            });
            confirmInput?.addEventListener('input', updateConfirmValidity);

            registerForm?.addEventListener('submit', (e) => {
                updatePasswordValidity();
                updateConfirmValidity();
                if (!registerForm.checkValidity()) {
                    e.preventDefault();
                    registerForm.reportValidity();
                }
            });

            toggleAdminCodeRow();
            updateChecklist(passwordInput?.value ?? '');
        });
    </script>
@endpush
