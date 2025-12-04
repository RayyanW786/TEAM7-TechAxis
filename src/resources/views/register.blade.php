@extends('layouts.main')
@section('title', 'Tech Axis - Register')
@push('styles')
    <!-- Fonts we are using across the website -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto:wght@300;400;500&display=swap"
        rel="stylesheet">

    <!-- Styles used for the account page -->
    <link rel="stylesheet" href="{{ asset('css/account.css') }}">
@endpush

@section('content')
    <div class="account-page">
        <div class="account-panel">
            <h1 class="account-heading">Register</h1>

            <div class="account-columns">
                <!-- REGISTER Card (Middle) -->
                <section class="account-card account-card-register">
                    <div class="account-card-header">Create Account</div>
                    <div class="account-card-body equal-height">

                        <!-- Basic Register Form (Name, Email, Password, Confirm Password) -->
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
                                <input id="name" class="register-input" type="text" name="name" required>
                            </div>

                            <div class="register-field-row">
                                <label for="email" class="register-label">Email</label>
                                <input id="email" class="register-input" type="email" name="email" required>
                            </div>

                            <div class="register-field-row">
                                <label for="password" class="register-label">Password</label>
                                <input id="password" class="register-input" type="password" name="password" required>
                            </div>

                            <!-- Confirm password will only show if password has something written in there -->
                            <div class="register-field-row" id="confirm-row">
                                <label class="register-label">Confirm Password</label>
                                <input id="password_confirmation" class="register-input" type="password"
                                    name="password_confirmation" required>
                            </div>
                            <div class="register-field-row">
                                <div id="admin_code_row">
                                    <label for="admin_code" class="register-label">Admin Code</label>
                                    <input type="text" class="register-input" name="admin_code" id="admin_code">
                                </div>
                            </div>

                            <button type="submit" class="register-button">
                                REGISTER
                            </button>

                            <!-- Error text appears if passwords don't match -->
                            <p id="register-error" class="register-error"></p>
                        </form>
                        <p class="JUSTCHECKING">Already have an Account? <a href="{{ route('login.page') }}">Log In</a></p>

                    </div>
                </section>

            </div>
        </div>
    </div>
@endsection

@push('scripts')

    <script>
        // show & hide confirm password + a very simple password check
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('password');
            const confirmRow = document.getElementById('confirm-row');
            const confirmInput = document.getElementById('password_confirmation');
            const registerForm = document.getElementById('register-form');
            const errorMessageEl = document.getElementById('register-error');

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
                    const confirmValue = confirmInput.value.trim();

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

        const roleSelect = document.getElementById('role');
        const adminCodeRow = document.getElementById('admin_code_row');
        function toggleAdminCodeRow() {
            if (roleSelect.value === 'admin') {
                adminCodeRow.style.display = 'block';
            } else {
                adminCodeRow.style.display = 'none';
            }
        }
        roleSelect.addEventListener('change', toggleAdminCodeRow);
        toggleAdminCodeRow();
    </script>
@endpush