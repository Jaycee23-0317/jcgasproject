<?php
include_once '../asset/header/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <!-- <link rel="stylesheet" href="../css/login.css"> -->
</head>

<body class="bg-dark-navy">
    <div id="alertBannerContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1060; width: 350px;"></div>

    <div class="min-vh-100 d-flex justify-content-center align-items-center p-3">
        <div class="card shadow border-0 rounded-4 p-4 p-md-5" style="width: 100%; max-width: 420px;">
            <div class="text-center mb-4">
                <img 
                    src="../asset/img/logo/gas_trnsprnt.png" 
                    alt="JC Gas Logo"
                    class="img-fluid mb-3"
                    style="width: 80px; height: 80px; object-fit: contain;" >
                <h3 class="fw-bold mb-1">JC Gas</h3>
                <p class="text-muted mb-0">Login to your account</p>
            </div>

            <form id="loginForm">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">
                        Username
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>

                        <input 
                            type="text" 
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Enter username"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">
                        Password
                    </label>

                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input 
                            type="password" 
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <div class="d-grid mb-4">
                    <button 
                        type="submit" 
                        class="btn btn-primary btn-lg rounded-3"
                        id="loginBtn"
                    >
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login
                    </button>
                </div>

            </form>
            <p class="text-center text-muted mb-0">Don't have an account?
                <a href="registration.php" class="text-decoration-none fw-semibold">
                    Register
                </a>
            </p>
        </div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {

            e.preventDefault();

            const form = this;
            const formData = new FormData(form);

            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');

            // Remove previous validation errors
            usernameField.classList.remove('is-invalid');
            passwordField.classList.remove('is-invalid');

            // Disable button while logging in
            loginBtn.disabled = true;
            loginBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Logging in...
            `;

            fetch('../php/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showAlertBanner(data.message, 'danger');
                    usernameField.classList.add('is-invalid');
                    passwordField.classList.add('is-invalid');

                    loginBtn.disabled = false;
                    loginBtn.innerHTML = `
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        Login
                    `;
                }
            })
            .catch(error => {
                showAlertBanner(
                    'An error occurred. Please try again.',
                    'danger'
                );

                usernameField.classList.add('is-invalid');
                passwordField.classList.add('is-invalid');

                loginBtn.disabled = false;
                loginBtn.innerHTML = `
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Login
                `;
            });
        });

        document.getElementById('username').addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });

        document.getElementById('password').addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
        </script>
    <script src="../js/bannerAlert.js"></script>

</body>

</html>