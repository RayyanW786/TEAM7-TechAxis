@extends('layouts.app')

@section('content')
<div class="account-page">
    <div class="account-panel">
        <h1 class="account-heading">Account Area</h1>

        <div class="account-columns">

            <!-- LOGIN Card (left Side) -->
            <section class="account-card account-card-login">
                <div class="account-card-header">LOGIN</div>
                <div class="account-card-body equal-height">
                    <p class="account-helper-text">
                        Login section (to be completed by team).
                    </p>
                    <p class="account-helper-text">
                        [LOGIN button] &nbsp; Forgot PW?
                    </p>
                </div>
            </section>

            <!-- REGISTER Card (Middle) -->
            <section class="account-card account-card-register">
                <div class="account-card-header">REGISTER</div>
                <div class="account-card-body equal-height">

                    <!-- Basic Register Form (Name, Email, Password, Confirm Password) -->
                    <form id="register-form" method="POST" action="#">
                        @csrf

                        <div class="register-field-row">
                            <span class="register-label">Name:</span>
                            <input id="name" class="register-input" type="text" name="name" required>
                        </div>

                        <div class="register-field-row">
                            <span class="register-label">Email:</span>
                            <input id="email" class="register-input" type="email" name="email" required>
                        </div>

                        <div class="register-field-row">
                            <span class="register-label">Password:</span>
                            <input id="password" class="register-input" type="password" name="password" required>
                        </div>

                        <!-- Confirm password will only show if password has something written in there -->
                        <div class="register-field-row" id="confirm-row">
                            <span class="register-label">Confirm Password:</span>
                            <input
                                id="password_confirmation"
                                class="register-input"
                                type="password"
                                name="password_confirmation"
                                required
                            >
                        </div>

                        <button type="submit" class="register-button">
                            REGISTER
                        </button>

                        <!-- Error text appears if passwords don't match -->
                        <p id="register-error" class="register-error"></p>
                    </form>

                </div>
            </section>

            <!-- DASHBOARD Card (Right Side) -->
            <section class="account-card account-card-dashboard">
                <div class="account-card-header">DASHBOARD</div>
                <div class="account-card-body equal-height">
                    <p class="account-helper-text">Welcome Back, [User]!</p>
                    <ul class="account-helper-text">
                        <li>Orders</li>
                        <li>Profile</li>
                        <li>Change Password</li>
                    </ul>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection

<script>
// show & hide confirm password + a very simple password check
document.addEventListener('DOMContentLoaded', () => {
    const passwordInput   = document.getElementById('password');
    const confirmRow      = document.getElementById('confirm-row');
    const confirmInput    = document.getElementById('password_confirmation');
    const registerForm    = document.getElementById('register-form');
    const errorMessageEl  = document.getElementById('register-error');

    // Hides confirm row
    if (confirmRow) {
        confirmRow.style.display = 'none';
    }

    // Show confirm box when the user starts typing a password
    if (passwordInput && confirmRow) {
        passwordInput.addEventListener('input', () => {
            const value = passwordInput.value.trim();

            if (value.length > 0) {
                confirmRow.style.display = 'flex';
            } else {
                confirmRow.style.display = 'none';

                if (confirmInput) {
                    confirmInput.value = '';
                }
            }
        });
    }

    // very basic front-end check. The passwords must match
    if (registerForm && passwordInput && confirmInput) {
        registerForm.addEventListener('submit', (e) => {
            if (errorMessageEl) {
                errorMessageEl.textContent = '';
            }

            const passwordValue = passwordInput.value.trim();
            const confirmValue  = confirmInput.value.trim();

            if (passwordValue !== confirmValue) {
                e.preventDefault();

                if (errorMessageEl) {
                    errorMessageEl.textContent = 'Passwords do not match. Please try again.';
                }

                confirmInput.focus();
            }
        });
    }
});
</script>
