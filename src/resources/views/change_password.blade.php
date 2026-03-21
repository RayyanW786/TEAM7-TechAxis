@extends('layouts.main')

@section('title', 'Change Password - Tech Axis')

@push('styles')
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Roboto:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link href="{{ asset('css/change_password.css') }}" rel="stylesheet">
@endpush

@section('content')

    <div class="password-container">
        <h1>Change Password</h1>

        @if($errors->any())
            <div class="error-box">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('success'))
            <div class="success-box">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="password-form">
            @csrf

            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

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

            <button type="submit">Update Password</button>

        </form>
    </div>

@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');

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

            const updatePasswordValidity = () => {
                const pw = passwordInput.value;

                updateChecklist(pw);

                passwordInput.setCustomValidity(
                    isStrongPassword(pw)
                        ? ''
                        : 'Password must include uppercase, lowercase, number, symbol, and be 8+ chars.'
                );
            };

            const updateConfirmValidity = () => {
                const confirmVal = confirmInput.value;

                if (confirmVal.length === 0) {
                    confirmInput.setCustomValidity('');
                    return;
                }

                confirmInput.setCustomValidity(
                    passwordInput.value === confirmVal
                        ? ''
                        : 'Passwords do not match.'
                );
            };

            passwordInput?.addEventListener('input', () => {
                updatePasswordValidity();
                updateConfirmValidity();
            });

            confirmInput?.addEventListener('input', updateConfirmValidity);

        });
    </script>
@endpush