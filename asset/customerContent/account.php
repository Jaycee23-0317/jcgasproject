<div class="main-content flex-grow-1 px-3">

    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold">
            <i class="bi bi-person-badge me-2"></i>Account
        </h2>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;" >
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>

            <p class="mt-2 text-muted">Loading system please wait...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <div class="section-card border rounded shadow-sm">
                <div class="section-card-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h4 class="m-0 text-gas-cyan">
                        <i class="bi bi-person-lines-fill me-2"></i>
                        Personal Information
                    </h4>

                    <button type="button" class="btn btn-sm btn-warning" onclick="editAccount()">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Profile
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col col-md-6">
                        <label for="username" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Username</p>
                        </label>
                        <input type="text" class="form-control" id="username" disabled >
                    </div>

                    <div class="col col-md-6">
                        <label for="email" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Email</p>
                        </label>
                        <input type="email" class="form-control" id="email" disabled >
                    </div>

                    <div class="col col-md-6">
                        <label for="fullName" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Full Name</p>
                        </label>
                        <input type="text" class="form-control" id="fullName" disabled >
                    </div>

                    <div class="col col-md-6">
                        <label for="contactNumber" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Contact Number</p>
                        </label>
                        <input type="text" class="form-control" id="contactNumber" disabled>
                    </div>

                    <div class="col col-md-6">
                        <label for="joinedSince" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Member Since</p>
                        </label>
                        <input type="text" class="form-control" id="joinedSince" disabled >
                    </div>

                    <div class="col col-12">
                        <label for="address" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50"> Address</p>
                        </label>
                        <input type="text" class="form-control" id="address" disabled>
                        <div id="mapAddress" class="map branch-map border rounded bg-secondary-subtle mt-3" style="height: 200px; width: 100%;" ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editAccountModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge me-2"></i>
                    Edit Account
                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" ></button>
            </div>

            <div class="modal-body">
                <div class="text-center py-5 modalCard rounded" id="editAccountModalLoader" >
                    <div class="spinner-border text-primary" role="status" >
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading account details...</p>
                </div>

                <div class="container rounded-4" id="editAccountModalContentArea" style="display: none;">
                    <form id="editAccountForm">
                        <div class="row row-cols-2 g-2">
                            <div class="col">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="fullName" id="editAccountFullName" placeholder="Enter your fullname" >
                                    <label for="editAccountFullName">Full Name</label>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control" name="contactNumber" id="editAccountContactNumber" placeholder="09123456789">
                                    <label for="editAccountContactNumber">Contact Number</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col col-9">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="address" id="editAccountAddress" placeholder="Enter your full address" >
                                    <label for="editAccountAddress">Address</label>
                                </div>
                            </div>

                            <div class="col col-3">
                                <button type="button" class="btn btn-primary w-100" onclick="locateCurrentLocation()" >
                                    <i class="bi bi-geo-alt-fill me-2"></i>
                                    Locate current address
                                </button>
                            </div>

                            <div class="col col-12">
                                <input type="hidden" name="latitude" id="editAccountAddressLatitude">
                                <input type="hidden" name="longitude" id="editAccountAddressLongitude">

                                <div id="editAccountMapAddress" class="map branch-map border rounded bg-secondary-subtle" style="height: 300px; width: 100%;"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-gas-primary" onclick="saveAccount()" >
                    <i class="bi bi-check2-circle me-1"></i>
                    Save Account
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let reviewMap = null;
    let reviewMarker = null;
    let editMap = null;
    let editMarker = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadAccountData();
    });

    async function loadAccountData() {

        const loadingCard = document.getElementById("loadingCard");
        const sectionCard = document.getElementById("sectionCard");

        if (!loadingCard || !sectionCard) {
            return;
        }

        loadingCard.style.display = "block";
        sectionCard.style.display = "none";

        try {
            const response = await fetch('../api/user.php');
            if (!response.ok) {
                throw new Error(
                    `HTTP error! status: ${response.status}`
                );
            }

            const accountData = await response.json();

            if (accountData.error) {
                throw new Error( accountData.error );
            }

            document.getElementById("fullName").value = accountData.FullName || '';
            document.getElementById("contactNumber").value = accountData.ContactNumber || '';
            document.getElementById("email").value = accountData.Email || '';
            document.getElementById("username").value = accountData.Username || '';
            document.getElementById("address").value = accountData.Address || '';

            if (accountData.CreatedAt) {
                const dateObj = new Date(accountData.CreatedAt);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });

                document.getElementById("joinedSince").value = formattedDate;
            }

            renderReviewDeliveryAddress(
                accountData.Latitude,
                accountData.Longitude,
                accountData.Address
            );

        } catch (error) {
            console.error("Error loading account:", error);

            if ( typeof showAlertBanner === "function" ) {
                showAlertBanner(error.message || error, 'danger');
            }

        } finally {
            loadingCard.style.display = "none";
            sectionCard.style.display = "block";
        }
    }

    function renderReviewDeliveryAddress(
        latValue,
        lngValue,
        address = ''
    ) {
        const lat =  parseFloat(latValue);
        const lng = parseFloat(lngValue);
        if ( isNaN(lat) || isNaN(lng) ) {
            console.error( "Invalid coordinates:", latValue, lngValue );
            return;
        }

        if (reviewMap) {
            reviewMap.remove();
            reviewMap = null;
            reviewMarker = null;
        }
        setTimeout(() => {
            try {
                reviewMap =
                    L.map('mapAddress')
                    .setView(
                        [lat, lng],
                        16
                    );

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 19,
                        attribution:
                            '© OpenStreetMap contributors'
                    }
                ).addTo(reviewMap);

                reviewMarker =
                    L.marker(
                        [lat, lng],
                        {
                            draggable: false
                        }
                    )
                    .addTo(reviewMap);

                reviewMarker
                    .bindPopup(`
                        <strong>Delivery Address</strong><br>
                        ${address || 'No address available'}
                    `)
                    .openPopup();

                setTimeout(() => {
                    reviewMap.invalidateSize();
                }, 100);
            } catch (error) {
                console.error(
                    "Review map initialization failed:",
                    error
                );
            }
        }, 200);
    }

    async function editAccount() {
        const editAccountModal = document.getElementById('editAccountModal');
        const loader = document.getElementById('editAccountModalLoader');
        const content = document.getElementById('editAccountModalContentArea');

        loader.style.display = 'block';
        content.style.display = 'none';

        try {
            const response = await fetch('../api/user.php');

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const accountData = await response.json();

            if (accountData.error) {
                throw new Error( accountData.error);
            }

            document.getElementById("editAccountFullName").value = accountData.FullName || '';
            document.getElementById("editAccountContactNumber").value = accountData.ContactNumber || '';
            document.getElementById("editAccountAddress").value = accountData.Address || '';
            document.getElementById("editAccountAddressLatitude").value = accountData.Latitude || '';
            document.getElementById("editAccountAddressLongitude").value = accountData.Longitude || '';

            renderEditAccountMapAddress(
                accountData.Latitude,
                accountData.Longitude,
                accountData.Address
            );
        } catch (error) {
            console.error("Error loading edit account:", error);
            if ( typeof showAlertBanner === "function") {
                showAlertBanner(error.message || error, 'danger');
            }
        } finally {
            loader.style.display = 'none';
            content.style.display = 'block';
        }

        const modal = bootstrap.Modal.getOrCreateInstance(editAccountModal);
        modal.show();

        editAccountModal.addEventListener(
            'shown.bs.modal',
            () => {
                if (editMap) {
                    editMap.invalidateSize();
                }
            },
            {
                once: true
            }
        );
    }

    function renderEditAccountMapAddress(
        latValue,
        lngValue,
        address = ''
    ) {
        const lat = parseFloat(latValue);
        const lng = parseFloat(lngValue);

        if ( isNaN(lat) | isNaN(lng) ) {
            console.error(
                "Invalid edit coordinates:",
                latValue,
                lngValue
            );
            return;
        }

        if (editMap) {
            editMap.remove();
            editMap = null;
            editMarker = null;
        }

        setTimeout(() => {
            try {
                editMap = L.map('editAccountMapAddress').setView([lat, lng], 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution:
                            '© OpenStreetMap contributors'
                    }
                ).addTo(editMap);

                editMarker = L.marker([lat, lng], {
                            draggable: true
                        }
                    )
                    .addTo(editMap);

                editMarker.bindPopup(`<strong>Delivery Address</strong> <br> ${address || 'No address available'}`);

                editMarker.on('dragend', async function () {
                        const position = editMarker.getLatLng();
                        const newLat = position.lat;
                        const newLng = position.lng;

                        console.log('Marker dragged:', newLat, newLng);
                        updateEditCoordinates(newLat,newLng);
                        await reverseGeoCode(newLat,  newLng);
                    }
                );

                setTimeout(() => {
                    editMap.invalidateSize();
                }, 100);
            } catch (error) {
                console.error("Edit map initialization failed:", error);
            }
        }, 200);
    }

    function updateEditCoordinates(lat, lng) {
        document.getElementById('editAccountAddressLatitude').value = parseFloat(lat).toFixed(6);
        document.getElementById('editAccountAddressLongitude').value = parseFloat(lng).toFixed(6);
    }

    function locateCurrentLocation() {
        if (!navigator.geolocation) {
            showAlertBanner("Your web browser does not support automatic location services. Please drag the pin manually.", 'warning');
            return;
        }

        showAlertBanner("Getting your current location...","info");

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const dynamicLat = position.coords.latitude;
                const dynamicLng = position.coords.longitude;
                if (editMap) { editMap.setView([dynamicLat, dynamicLng], 16); }

                if (editMarker) { editMarker.setLatLng([ dynamicLat, dynamicLng ]); }

                updateEditCoordinates(dynamicLat, dynamicLng);

                await reverseGeoCode(dynamicLat, dynamicLng);

                showAlertBanner("Location pinned successfully!", "success");
            },

            (error) => {
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        showAlertBanner("Location tracking access was blocked. Please enable location permissions.", 'danger');
                        break;
                    case error.POSITION_UNAVAILABLE:
                        showAlertBanner("Your location is currently unavailable. Please drag the pin manually.",'danger');
                        break;
                    case error.TIMEOUT:
                        showAlertBanner("Getting your location timed out. Please try again.", 'warning');
                        break;
                    default:
                        showAlertBanner("Failed to acquire your location.",'danger');
                }
            },
            options
        );
    }

    async function reverseGeoCode(lat,lng) {
        const revUrl =
            `https://nominatim.openstreetmap.org/reverse` +
            `?format=json` +
            `&addressdetails=1` +
            `&lat=${lat}` +
            `&lon=${lng}`;

        try {
            const response = await fetch(
                revUrl,
                {
                    headers: {
                        'Accept':
                            'application/json'
                    }
                }
            );

            if (!response.ok) {
                throw new Error(`Reverse geocoding failed: HTTP ${response.status}`);
            }

            const data = await response.json();
            const addressText = data?.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            const addressInput = document.getElementById('editAccountAddress');
            if (addressInput) { addressInput.value = addressText; }

            updateEditCoordinates(lat,lng);

            if (editMarker) {
                editMarker.setPopupContent(`
                        <strong>Delivery Address</strong><br>
                        ${addressText}
                    `);
            }
            return addressText;
        } catch (error) {
            showAlertBanner(`Reverse geocoding failed: ${error}`, 'danger');
            return null;
        }
    }

    async function saveAccount() {
        const form = document.getElementById('editAccountForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('../api/user.php', {
                    method: 'POST',
                    body: formData
                }
            );

            if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }

            const data = await response.json();

            if (data.success) {
                showAlertBanner(data.message, 'success');

                const modalElement = document.getElementById('editAccountModal');
                const modal = bootstrap.Modal.getInstance(modalElement);

                if (modal) { modal.hide(); }

                await loadAccountData();
            } else {
                showAlertBanner(data.message || 'Failed to update account.', 'danger');
            }
        } catch (error) {
            showAlertBanner('A network error occurred. Please try again.', 'danger');
        }
    }
</script>