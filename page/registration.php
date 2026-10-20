<?php
include_once '../asset/header/header.php';
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="../css/registration.css">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

</head>

<body>
    <div id="alertBannerContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1060; width: 350px;"></div>

    <div class="card mt-5">
        <h2 class="register-title text-center">
            <i class="bi bi-person-plus me-2"></i>
            Create Account
        </h2>

        <form id="step1Form">
            <p class="subtitle text-center"> Step 1 of 3 — Verify your Gmail </p>

            <div class="input-group mb-3">
                <label for="email">Gmail Account</label>
                <input type="email" id="email" name="email" placeholder="you@gmail.com" autocomplete="email" required>
                <div id="emailAlert" class="registration-alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    <span id="emailAlertMsg"></span>
                </div>
            </div>

            <button type="button" class="btn-register" id="sendOtpBtn">
                <i class="bi bi-envelope-check me-2"></i>
                Send OTP Code
            </button>

            <div id="otpSection" class="mt-3" hidden>
                <div class="otp-info-box">
                    <i class="bi bi-shield-lock-fill me-2"></i>
                    We sent a 6-digit verification code to
                    <strong id="otpEmailDisplay"></strong>
                </div>

                <div class="input-group">
                    <label for="otp">Enter OTP Code</label>
                    <input type="text" id="otp" name="otp" placeholder="6-digit code" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
                    <div id="otpAlert" class="registration-alert">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        <span id="otpAlertMsg"></span>
                    </div>
                </div>

                <div id="devOtpBox" class="alert alert-warning small" hidden >
                    <strong>Local testing OTP:</strong>
                    <span id="devOtpValue" class="fw-bold"></span>
                </div>

                <button type="button" class="btn-register mt-2" id="verifyOtpBtn" >
                    <i class="bi bi-check-circle me-2"></i>
                    Verify Gmail
                </button>

                <p class="text-center small mt-2 mb-0">
                    <a href="#" id="resendOtpBtn">
                        Resend code
                    </a>
                </p>
            </div>
        </form>

        <form id="step2Form" hidden>
            <p class="subtitle text-center">Step 2 of 3 — Create your username & password</p>

            <div class="otp-info-box">
                <i class="bi bi-patch-check-fill me-2 text-success"></i>
                Gmail
                <strong id="verifiedEmailDisplay"></strong>
                is verified.
            </div>

            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" autocomplete="username" minlength="3" maxlength="50" required >

                <div id="usernameAlert" class="registration-alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    <span id="usernameAlertMsg"></span>
                </div>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="new-password" minlength="4" required >

                <div id="passwordAlert" class="registration-alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    <span id="passwordAlertMsg"></span>
                </div>
            </div>

            <div class="input-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" placeholder="Re-enter password" autocomplete="new-password" required>

                <div id="confirmPasswordAlert" class="registration-alert">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    <span id="confirmPasswordAlertMsg"></span>
                </div>
            </div>

            <button type="button" class="btn-register" id="createAccountBtn" >
                <i class="bi bi-person-check me-2"></i>
                Continue
            </button>
        </form>

        <form id="step3Form" hidden>
            <p class="subtitle text-center">Step 3 of 3 — Complete your profile</p>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="fullName" id="fullName" placeholder="Full Name" autocomplete="name" maxlength="100" required >
                <label for="fullName"> Full Name </label>
            </div>

            <div class="form-floating mb-3">
                <input type="tel" class="form-control" name="contactNumber" id="contactNumber" placeholder="0917 123 4567" autocomplete="tel" maxlength="20" required >
                <label for="contactNumber"> Contact Number</label>
            </div>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" name="address" id="address" placeholder="Complete Address" autocomplete="street-address" maxlength="255" required >
                <label for="address">Complete Address </label>
            </div>

            <input type="hidden" name="latitude" id="lat_input" >
            <input type="hidden" name="longitude" id="lng_input" >

            <button type="button" class="btn-lookup" id="locateBtn" >
                <i class="bi bi-geo-alt-fill me-2"></i>
                Locate current address
            </button>

            <div class="mt-3">
                <label class="fw-bold mb-1">
                    <i class="bi bi-info-circle me-1"></i>
                    Verify your house location. Drag the pin if needed.
                </label>
                <div id="delivery_map"></div>
            </div>

            <div class="preview-card mt-3" id="confirmationPreview" >
                <div class="fw-bold text-success mb-1">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Delivery Location
                </div>

                <div class="text-muted" style="font-size: 0.8rem;" >
                    <strong>Address:</strong>
                    <span id="preview_complete_address">Enter your address or locate your current position.</span>
                    <br>
                    <strong>GPS Coordinates:</strong>
                    <span id="preview_coords">Not selected</span>
                </div>
            </div>

            <button type="button" class="btn-register mt-3" id="saveAccountBtn" >
                <i class="bi bi-check-circle me-2"></i>
                Create Account
            </button>
        </form>

        <p class="link-text text-center mt-3">
            Already have an account?
            <a href="loginPage.php">
                Login
            </a>
        </p>
    </div>

    <div class="modal fade" id="otpSentModal" tabindex="-1" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;" >
                <div class="modal-header"
                    style="
                        background: linear-gradient(135deg, #270c5e, #2c1aae);
                        color: #fff;
                        border: none;
                    " >
                    <h5 class="modal-title">
                        <i class="bi bi-envelope-check-fill me-2"></i>
                        OTP Code Sent
                    </h5>
                </div>

                <div class="modal-body text-center pt-4">
                    <div class="display-6 text-success mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <p class="mb-1">
                        We've sent a
                        <strong>6-digit verification code</strong>
                        to:
                    </p>

                    <p class="fw-bold fs-5" id="otpModalEmail" style="color: #280cff;"></p>
                    <p class="text-muted small" id="otpModalMessage" >
                        Please check your inbox and spam folder.
                    </p>

                    <div id="otpModalDevBox" class="alert alert-warning mt-3 mb-0" hidden >
                        <div class="small fw-bold mb-1">
                            <i class="bi bi-info-circle me-1"></i>
                            Local testing OTP
                        </div>

                        <div id="otpModalDevValue" class="fw-bold"
                            style="
                                font-size: 1.6rem;
                                letter-spacing: 6px;
                                color: #664d03;
                            ">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary" id="otpModalEnterBtn" >
                        <i class="bi bi-keyboard me-1"></i>
                        Enter OTP Code
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" ></script>
    <script src="../js/registrationPage.js"></script>
</body>
</html>