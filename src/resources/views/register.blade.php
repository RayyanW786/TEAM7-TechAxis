@extends('layouts.main')

@section('title', 'Tech Axis - Register')

@push('styles')
    <!-- Fonts we are using across the website -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Styles used for the account page -->
    <link rel="stylesheet" href="{{ asset('css/account.css') }}">
@endpush

@section('content')
    <div class="account-page">
        <div class="account-panel">
            <h1 class="account-heading">Register</h1>

            <div class="account-columns account-columns-center">
                <section class="account-card account-card-register register-card-single">
                    <div class="account-card-header">Account Details</div>

                    <div class="account-card-body equal-height">
                        <form id="register-form" method="POST" action="{{ route('register') }}">
                            @csrf

                            @if ($errors->any())
                                <div class="error-messages">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="register-field-row">
                                <label for="role" class="register-label">Registering As</label>
                                <select id="role" class="register-input" name="role" required>
                                    <option value="customer">Customer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div class="register-field-row">
                                <label for="name" class="register-label">Full Name</label>
                                <input id="name" class="register-input" type="text" name="name" required autocomplete="name">
                            </div>

                            <div class="register-field-row">
                                <label for="email" class="register-label">Email</label>
                                <input id="email" class="register-input" type="email" name="email" required autocomplete="email">
                            </div>

                            <div class="register-field-row">
                                <label for="password" class="register-label">Password</label>
                                <input id="password" class="register-input" type="password" name="password" required autocomplete="new-password">
                            </div>

                            <!-- Live password checklist -->
                            <div id="pw-checklist" class="pw-checklist" aria-live="polite">
                                <div class="pw-checklist-title">Password Must Include:</div>
                                <ul>
                                    <li class="pw-rule not-met" id="rule-len"><span class="pw-icon"></span> At least 8 characters</li>
                                    <li class="pw-rule not-met" id="rule-upper"><span class="pw-icon"></span> At least one uppercase letter</li>
                                    <li class="pw-rule not-met" id="rule-lower"><span class="pw-icon"></span> At least one lowercase letter</li>
                                    <li class="pw-rule not-met" id="rule-num"><span class="pw-icon"></span> At least one number</li>
                                    <li class="pw-rule not-met" id="rule-sym"><span class="pw-icon"></span> At least one symbol</li>
                                </ul>
                            </div>

                            <div class="register-field-row" id="confirm-row">
                                <label for="password_confirmation" class="register-label">Confirm Password</label>
                                <input id="password_confirmation" class="register-input" type="password" name="password_confirmation" required autocomplete="new-password">
                            </div>

                            <div class="register-field-row" id="admin_code_row">
                                <label for="admin_code" class="register-label">Admin Code</label>
                                <input id="admin_code" class="register-input" type="text" name="admin_code">
                            </div>

                            <button type="submit" class="register-button">REGISTER</button>
                        </form>

                        <p class="JUSTCHECKING">
                            Already have an Account? <a href="{{ route('login.page') }}">Log In</a>
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const roleSelect = document.getElementById('role');
            const adminCodeRow = document.getElementById('admin_code_row');

            const passwordInput = document.getElementById('password');
            const confirmRow = document.getElementById('confirm-row');
            const confirmInput = document.getElementById('password_confirmation');

            const registerForm = document.getElementById('register-form');

            const checklist = document.getElementById('pw-checklist');
            const ruleLen = document.getElementById('rule-len');
            const ruleUpper = document.getElementById('rule-upper');
            const ruleLower = document.getElementById('rule-lower');
            const ruleNum = document.getElementById('rule-num');
            const ruleSym = document.getElementById('rule-sym');

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
                setRule(ruleLen, r.len);
                setRule(ruleUpper, r.upper);
                setRule(ruleLower, r.lower);
                setRule(ruleNum, r.num);
                setRule(ruleSym, r.sym);
            };

            if (confirmRow) confirmRow.style.display = 'none';

            const toggleAdminCodeRow = () => {
                if (!roleSelect || !adminCodeRow) return;
                adminCodeRow.style.display = roleSelect.value === 'admin' ? 'flex' : 'none';
            };

            if (roleSelect) {
                roleSelect.addEventListener('change', toggleAdminCodeRow);
                toggleAdminCodeRow();
            }

            let pwTimer = null;

            if (passwordInput && confirmRow && confirmInput && checklist) {
                passwordInput.addEventListener('input', () => {
                    clearTimeout(pwTimer);

                    pwTimer = setTimeout(() => {
                        const pw = passwordInput.value.trim();

                        if (pw.length > 0) {
                            checklist.classList.add('is-open');
                            confirmRow.style.display = 'flex';
                            updateChecklist(pw);
                        } else {
                            checklist.classList.remove('is-open');
                            confirmRow.style.display = 'none';
                            confirmInput.value = '';
                            passwordInput.setCustomValidity('');
                            confirmInput.setCustomValidity('');
                            return;
                        }

                        passwordInput.setCustomValidity(
                            isStrongPassword(pw) ? '' : 'Password does not meet requirements.'
                        );
                    }, 100);
                });

                confirmInput.addEventListener('input', () => {
                    const confirmVal = confirmInput.value;

                    if (confirmVal.length === 0) {
                        confirmInput.setCustomValidity('');
                        return;
                    }

                    confirmInput.setCustomValidity(
                        passwordInput.value === confirmVal ? '' : 'Passwords do not match.'
                    );
                });
            }

            if (registerForm) {
                registerForm.addEventListener('submit', (e) => {
                    if (!registerForm.checkValidity()) {
                        e.preventDefault();
                        registerForm.reportValidity();
                    }
                });
            }
        });
    </script>
@endpush