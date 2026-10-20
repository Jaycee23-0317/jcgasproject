"use strict";

const API = {
    sendOtp: "../php/send_otp.php",
    verifyOtp: "../php/verify_otp.php",
    createAccount: "../php/create_account.php",
    saveProfile: "../api/user.php?createAccount"
};

let map = null;
let deliveryMarker = null;
let verifiedEmail = "";
let devOtp = "";

const $ = (id) => document.getElementById(id);

function setLoading(button, loading, text, icon = "") {
    button.disabled = loading;

    button.innerHTML = loading
        ? `<span class="spinner-border spinner-border-sm me-2"></span>${text}`
        : `<i class="${icon} me-2"></i>${text}`;

}

function showInlineAlert(alertId, messageId, message, type = "danger") {
    const alert = $(alertId);
    const messageElement = $(messageId);

    if (!alert || !messageElement) return;

    alert.className =
        `alert alert-${type} alert-dismissible fade show mt-2 py-2 px-3 mb-0`;

    messageElement.textContent = message;
    alert.hidden = false;
}

function hideInlineAlert(alertId) {
    const alert = $(alertId);

    if (alert) { alert.hidden = true; }
}

function hideAllAlerts() {
    [
        "emailAlert",
        "otpAlert",
        "usernameAlert",
        "passwordAlert",
        "confirmPasswordAlert"
    ].forEach(hideInlineAlert);
}

function showBanner(message, type = "success") {
    const container = $("alertBannerContainer");

    if (!container) return;

    const icon =
        type === "success"
            ? "bi-check-circle-fill"
            : "bi-exclamation-triangle-fill";

    const alert = document.createElement("div");

    alert.className = `alert alert-${type} alert-dismissible fade show shadow d-flex align-items-center mb-2`;

    alert.innerHTML = `
        <i class="bi ${icon} me-2 fs-5"></i>
        <div>${message}</div>
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>
    `;

    container.appendChild(alert);

    setTimeout(() => {
        if (alert.isConnected) {
            bootstrap.Alert.getOrCreateInstance(alert).close();
        }
    }, 3500);
}

hideAllAlerts();

async function fetchJson(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        credentials: "same-origin"
    });

    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);

    } catch (error) {
        console.error("Invalid JSON from:", url);
        console.error(text);

        throw new Error(
            "The server returned an invalid response. Check the PHP error log."
        );
    }

    if (!response.ok) {
        throw new Error(
            data.message || `Server error (${response.status}).`
        );
    }

    return data;
}

function showStep(step) {
    $("step1Form").hidden = step !== 1;
    $("step2Form").hidden = step !== 2;
    $("step3Form").hidden = step !== 3;

    if (step === 3) {
        setTimeout(() => {
            initializeMap();
            if (map) { map.invalidateSize(); }
        }, 100);
    }
}

async function sendOtp() {
    const email = $("email").value.trim().toLowerCase();
    hideInlineAlert("emailAlert");

    if (!email) {
        showInlineAlert(
            "emailAlert",
            "emailAlertMsg",
            "Please enter your Gmail address."
        );

        return;
    }


    if (!/^[a-z0-9._%+-]+@gmail\.com$/i.test(email)) {
        showInlineAlert(
            "emailAlert",
            "emailAlertMsg",
            "Please enter a valid Gmail address ending in @gmail.com."
        );

        return;
    }

    const button = $("sendOtpBtn");

    setLoading(
        button,
        true,
        "Sending...",
        "bi bi-envelope-check"
    );

    try {
        const formData = new FormData();
        formData.append("email", email);
        const data = await fetchJson(API.sendOtp, {
            method: "POST",
            body: formData
        });

        if (!data.success) {
            showInlineAlert(
                "emailAlert",
                "emailAlertMsg",
                data.message || "Unable to send OTP."
            );

            return;
        }

        verifiedEmail = email;
        devOtp = data.dev_otp || "";

        showOtpModal(
            email,
            devOtp,
            data.message
        );
    } catch (error) {
        console.error(error);
        showInlineAlert(
            "emailAlert",
            "emailAlertMsg",
            error.message || "Could not reach the server."
        );
    } finally {
        setLoading(
            button,
            false,
            "Send OTP Code",
            "bi bi-envelope-check"
        );
    }
}

function showOtpModal(email, otp, message) {
    $("otpModalEmail").textContent = email;
    $("otpModalMessage").textContent =
        message || "Please check your inbox and spam folder.";

    if (otp) {
        $("otpModalDevValue").textContent = otp;
        $("otpModalDevBox").hidden = false;
    } else {
        $("otpModalDevBox").hidden = true;
    }

    const modalElement = $("otpSentModal");
    const modal =
        bootstrap.Modal.getOrCreateInstance(modalElement, {
            backdrop: "static",
            keyboard: false
        });

    $("otpModalEnterBtn").onclick = () => {
        modal.hide();
        $("otpSection").hidden = false;
        $("otpEmailDisplay").textContent = email;

        if (otp) {
            $("devOtpValue").textContent = otp;
            $("devOtpBox").hidden = false;
        } else {
            $("devOtpBox").hidden = true;
        }
        $("otp").focus();
    };

    modal.show();
}

$("resendOtpBtn").addEventListener("click", (event) => {
    event.preventDefault();
    sendOtp();
});

async function verifyOtp() {
    const otp = $("otp").value.trim();
    const email = $("email").value.trim().toLowerCase();
    hideInlineAlert("otpAlert");

    if (!/^\d{6}$/.test(otp)) {
        showInlineAlert(
            "otpAlert",
            "otpAlertMsg",
            "Please enter the 6-digit OTP code."
        );

        return;
    }

    const button = $("verifyOtpBtn");

    setLoading(
        button,
        true,
        "Verifying...",
        "bi bi-check-circle"
    );

    try {
        const formData = new FormData();
        formData.append("email", email);
        formData.append("otp", otp);

        const data = await fetchJson(API.verifyOtp, {
            method: "POST",
            body: formData
        });

        if (!data.success) {
            showInlineAlert(
                "otpAlert",
                "otpAlertMsg",
                data.message || "Invalid OTP."
            );

            return;
        }

        verifiedEmail = email;
        $("verifiedEmailDisplay").textContent = email;
        showStep(2);
        showBanner(
            "Gmail verified successfully.",
            "success"
        );
        $("username").focus();
    } catch (error) {
        console.error(error);
        showInlineAlert(
            "otpAlert",
            "otpAlertMsg",
            error.message || "Could not reach the server."
        );
    } finally {
        setLoading(
            button,
            false,
            "Verify Gmail",
            "bi bi-check-circle"
        );
    }
}

async function createAccount() {
    const username = $("username").value.trim();
    const password = $("password").value;
    const confirmPassword = $("confirmPassword").value;

    hideInlineAlert("usernameAlert");
    hideInlineAlert("passwordAlert");
    hideInlineAlert("confirmPasswordAlert");

    if (!username) {
        showInlineAlert(
            "usernameAlert",
            "usernameAlertMsg",
            "Please enter a username."
        );

        $("username").focus();
        return;
    }

    if (!/^[a-zA-Z0-9._-]{3,50}$/.test(username)) {
        showInlineAlert(
            "usernameAlert",
            "usernameAlertMsg",
            "Username must be 3–50 characters and may contain letters, numbers, dots, underscores, or hyphens."
        );
        $("username").focus();

        return;
    }

    if (!password) {
        showInlineAlert(
            "passwordAlert",
            "passwordAlertMsg",
            "Please enter a password."
        );
        $("password").focus();

        return;
    }

    if (password.length < 8) {
        showInlineAlert(
            "passwordAlert",
            "passwordAlertMsg",
            "Password must be at least 8 characters."
        );
        $("password").focus();

        return;
    }

    if (password !== confirmPassword) {
        showInlineAlert(
            "confirmPasswordAlert",
            "confirmPasswordAlertMsg",
            "Passwords do not match."
        );
        $("confirmPassword").focus();

        return;
    }

    const button = $("createAccountBtn");

    setLoading(
        button,
        true,
        "Checking...",
        "bi bi-person-check"
    );

    try {
        const formData = new FormData();
        formData.append("email", verifiedEmail);
        formData.append("username", username);
        formData.append("password", password);

        const data = await fetchJson(API.createAccount, {
            method: "POST",
            body: formData
        });

        if (!data.success) {
            showInlineAlert(
                "usernameAlert",
                "usernameAlertMsg",
                data.message || "Unable to continue."
            );

            return;
        }

        showStep(3);
        showBanner(
            "Account details saved. Complete your profile.",
            "success"
        );
        $("fullName").focus();
    } catch (error) {
        console.error(error);
        showInlineAlert(
            "usernameAlert",
            "usernameAlertMsg",
            error.message || "Could not reach the server."
        );
    } finally {
        setLoading(
            button,
            false,
            "Continue",
            "bi bi-person-check"
        );
    }
}

function initializeMap() {
    if (map) return;

    const defaultLat = 14.6760;
    const defaultLng = 120.5360;

    map = L.map("delivery_map").setView(
        [defaultLat, defaultLng],
        13
    );

    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors"
        }
    ).addTo(map);

    deliveryMarker = L.marker(
        [defaultLat, defaultLng],
        {
            draggable: true
        }
    ).addTo(map);

    deliveryMarker.on("dragend", () => {
        const position = deliveryMarker.getLatLng();

        updateCoordinates(
            position.lat,
            position.lng
        );

        reverseGeocode(
            position.lat,
            position.lng
        );
    });

    map.invalidateSize();
}

function updateCoordinates(lat, lng) {
    const latitude = Number(lat).toFixed(8);
    const longitude = Number(lng).toFixed(8);

    $("lat_input").value = latitude;
    $("lng_input").value = longitude;

    $("preview_coords").textContent =
        `${latitude}, ${longitude}`;
}

let geocodeController = null;
async function reverseGeocode(lat, lng) {
    if (geocodeController) { geocodeController.abort(); }

    geocodeController = new AbortController();

    const url =
        "https://nominatim.openstreetmap.org/reverse" +
        `?format=jsonv2&lat=${encodeURIComponent(lat)}` +
        `&lon=${encodeURIComponent(lng)}`;

    try {
        const response = await fetch(url, {
            headers: {
                Accept: "application/json"
            },
            signal: geocodeController.signal
        });

        if (!response.ok) {
            throw new Error("Reverse geocoding failed.");
        }

        const data = await response.json();

        if (!data.display_name) return;

        $("address").value = data.display_name;
        $("preview_complete_address").textContent = data.display_name;
    } catch (error) {
        if (error.name !== "AbortError") {
            console.error(
                "Reverse geocoding error:",
                error
            );
        }
    }
}

function locateCurrentLocation() {
    if (!navigator.geolocation) {
        showBanner(
            "Your browser does not support location services. You can enter your address manually or drag the map marker.",
            "warning"
        );

        return;
    }

    const button = $("locateBtn");

    setLoading(
        button,
        true,
        "Locating...",
        "bi bi-geo-alt-fill"
    );

    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            initializeMap();

            map.setView(
                [lat, lng],
                17
            );

            deliveryMarker.setLatLng(
                [lat, lng]
            );

            updateCoordinates(
                lat,
                lng
            );

            await reverseGeocode(
                lat,
                lng
            );

            showBanner(
                "Your current location has been pinned. You can drag the marker to your exact house location.",
                "success"
            );

            setLoading(
                button,
                false,
                "Locate current address",
                "bi bi-geo-alt-fill"
            );
        },

        (error) => {
            console.error(
                "Geolocation error:",
                error
            );

            let message = "Unable to determine your location. You can enter your address manually or drag the map marker.";

            if (error.code === 1) {
                message = "Location permission was denied. Please allow location access in your browser or enter your address manually.";
            } else if (error.code === 2) {
                message = "Your location is currently unavailable. Please try again or enter your address manually.";
            } else if (error.code === 3) {
                message = "Location request timed out. Please try again.";
            }

            showBanner(
                message,
                "warning"
            );

            setLoading(
                button,
                false,
                "Locate current address",
                "bi bi-geo-alt-fill"
            );
        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
}

async function saveAccount() {
    const fullName = $("fullName").value.trim();
    const contactNumber = $("contactNumber").value.trim();
    const address = $("address").value.trim();
    const latitude = $("lat_input").value;
    const longitude = $("lng_input").value;

    if (!fullName) {
        showBanner(
            "Please enter your full name.",
            "danger"
        );
        $("fullName").focus();
        return;
    }

    if (!contactNumber) {
        showBanner(
            "Please enter your contact number.",
            "danger"
        );
        $("contactNumber").focus();

        return;
    }

    if (!address) {
        showBanner(
            "Please enter your complete address.",
            "danger"
        );
        $("address").focus();

        return;
    }

    if (!latitude || !longitude) {
        showBanner(
            "Please use 'Locate current address' or drag the map marker to your house location before creating your account.",
            "warning"
        );

        return;
    }

    const button = $("saveAccountBtn");

    setLoading(
        button,
        true,
        "Creating account...",
        "bi bi-check-circle"
    );

    try {
        const formData = new FormData();

        formData.append(
            "email",
            verifiedEmail
        );

        formData.append(
            "fullName",
            fullName
        );

        formData.append(
            "contactNumber",
            contactNumber
        );

        formData.append(
            "address",
            address
        );

        formData.append(
            "latitude",
            latitude
        );

        formData.append(
            "longitude",
            longitude
        );

        const data =
            await fetchJson(
                API.saveProfile,
                {
                    method: "POST",
                    body: formData
                }
            );

        if (!data.success) {
            showBanner(
                data.message ||
                "Unable to create your account.",
                "danger"
            );

            return;
        }

        showBanner(
            data.message ||
            "Account created successfully!",
            "success"
        );

        setTimeout(() => {
            window.location.href = "loginPage.php";
        }, 1500);

    } catch (error) {
        console.error(error);
        showBanner(
            error.message ||
            "Could not reach the server.",
            "danger"
        );
    } finally {
        setLoading(
            button,
            false,
            "Create Account",
            "bi bi-check-circle"
        );
    }
}

$("sendOtpBtn").addEventListener( "click", sendOtp );

$("verifyOtpBtn").addEventListener( "click", verifyOtp );

$("createAccountBtn").addEventListener( "click", createAccount );

$("saveAccountBtn").addEventListener( "click", saveAccount );

$("locateBtn").addEventListener( "click", locateCurrentLocation );

$("email").addEventListener(
    "input",
    () => hideInlineAlert("emailAlert")
);

$("otp").addEventListener(
    "input",
    () => {
        $("otp").value = $("otp").value.replace(/\D/g, "").slice(0, 6);
        hideInlineAlert("otpAlert");
    }
);

$("username").addEventListener(
    "input",
    () => hideInlineAlert("usernameAlert")
);

$("password").addEventListener(
    "input",
    () => hideInlineAlert("passwordAlert")
);

$("confirmPassword").addEventListener(
    "input",
    () => hideInlineAlert("confirmPasswordAlert")
);

$("email").addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        sendOtp();
    }
});

$("otp").addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        verifyOtp();
    }
});

$("confirmPassword").addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
        event.preventDefault();
        createAccount();
    }
});