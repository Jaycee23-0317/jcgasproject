<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-shop me-2"></i>All Branches</h2>
        <button class="btn btn-primary" id="addBranchBtn" onclick="openAddAndEditBranchModal('add')">
            <i class="bi bi-plus-circle"></i> Add Branch
        </button>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading system branch layout networks...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <div id="branchesContainer"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAndEditBranchModal" tabindex="-1" aria-labelledby="addAndEditBranchModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAndEditBranchModalLabel">
                    <i class="bi bi-shop me-2"></i>Add Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="editBranchForm" action="" method="POST">
                    <input type="hidden" name="branch_id" id="editBranchIDInput">
                    <input type="hidden" name="latitude" id="editLatitude">
                    <input type="hidden" name="longitude" id="editLongitude">

                    <div class="row row-cols-2 g-3 border-bottom pb-3">
                        <div class="col">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="branch_name" id="editBranchNameInput" required>
                                <label for="editBranchNameInput">Branch Name</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="contact_number" id="editContactNumberInput" required>
                                <label for="editContactNumberInput">Contact Number</label>
                            </div>
                        </div>

                        <div class="col">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" id="editEmailInput" placeholder="name@example.com" required>
                                <label for="editEmailInput">Email address</label>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cols-2 g-3 border-bottom py-3">
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold m-0 mb-1" style="font-size:0.75rem;">
                                Branch Address Search
                            </label>

                            <div class="input-group">
                                <button class="btn btn-primary" type="button" id="btnLocateAddress" onclick="searchTypedAddressOnMap()">
                                    <i class="bi bi-search-heart me-1"></i> Locate
                                </button>

                                <div class="form-floating flex-grow-1">
                                    <input type="text" class="form-control" name="address" id="editAddressInput" placeholder="Enter branch address" required>
                                    <label for="editAddressInput">Address</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr>

                            <h3>
                                <i class="bi bi-geo-alt me-2 text-primary"></i>Exact Location
                            </h3>

                            <p class="text-muted small mb-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Search an address or drag the pin to the exact branch location.
                            </p>

                            <div id="pinMapContainer" style="height: 200px; width: 100%;"></div>

                            <div class="card mt-3">
                                <div class="card-body py-2">
                                    <p class="small text-muted mb-1">
                                        <i class="bi bi-crosshair me-1"></i>Selected Location:
                                    </p>

                                    <p class="small fw-semibold mb-1" id="pinMapPreviewAddress">
                                        Search an address or drag the pin to set the exact location.
                                    </p>

                                    <p class="text-muted m-0">
                                        <i class="bi bi-geo me-1"></i>
                                        <span id="pinMapCoords">--</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer" id="addAndEditBranchModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                <button type="button" class="btn btn-primary" id="saveBranchBtn" onclick="updateBranch()">
                    <i class="bi bi-plus-circle me-1"></i>Add Branch
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="branchConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="invConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold" id="invConfirmLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Confirm Changes
                </h5>
            </div>

            <div class="modal-body py-3 text-start">
                <p class="m-0 fs-6" id="branchConfirmMessage">
                    Are you sure you want to add this branch?
                </p>
            </div>

            <div class="modal-footer border-top border-secondary p-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" id="confirmUpdateBtn" class="btn btn-sm btn-warning" onclick="confirmUpdateBtn()">
                    Yes, Add
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const activeLeafletMapsRegistry = {};
let branchesData = [];
let editPinMapInstance = null;
let editPinMarkerInstance = null;
let currentBranchModalMode = "add";
let branchReverseGeocodeController = null;

document.addEventListener("DOMContentLoaded", loadBranchData);

async function loadBranchData() {
    const loadingCard = document.getElementById("loadingCard");
    const sectionCard = document.getElementById("sectionCard");

    loadingCard.style.display = "block";
    sectionCard.style.display = "none";

    try {
        const response = await fetch("../api/branch.php");

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        branchesData = Array.isArray(data) ? data : [];

        loadBranchCards();
    } catch (error) {
        console.error(error);
        showAlertBanner("Could not refresh branch data. Please try again.", "danger");
    } finally {
        loadingCard.style.display = "none";
        sectionCard.style.display = "block";
    }
}

function loadBranchCards() {
    const container = document.getElementById("branchesContainer");

    if (!container) return;

    Object.values(activeLeafletMapsRegistry).forEach(map => {
        if (map) map.remove();
    });

    Object.keys(activeLeafletMapsRegistry).forEach(key => {
        delete activeLeafletMapsRegistry[key];
    });

    container.innerHTML = "";

    if (branchesData.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No branches found inside the database catalog.</div>';
        return;
    }

    branchesData.forEach(branch => {
        const branchId = branch.BranchID;
        const uniqueMapId = `branchMapInstance_${branchId}`;
        const safeName = branch.BranchName || "Branch";
        const safeContact = branch.ContactNumber || "—";
        const safeAddress = branch.Address || "No saved address";
        const safeEmail = branch.Email || "-";
        const safeLat = parseFloat(branch.Latitude) || 14.5995;
        const safeLng = parseFloat(branch.Longitude) || 120.9842;

        const cardDiv = document.createElement("div");
        cardDiv.className = "section-card mb-3 p-3 border rounded shadow-sm";

        cardDiv.innerHTML = `
            <div class="row">
                <div class="col-12 col-md-5">
                    <h3 class="branch-name text-gas-cyan fw-bold mb-3">
                        <i class="bi bi-shop me-2 text-primary"></i>${safeName}
                    </h3>

                    <p class="mb-1">
                        <i class="bi bi-telephone me-2 text-muted"></i>${safeContact}
                    </p>

                    <p class="mb-1">
                        <i class="bi bi-envelope-at me-2 text-muted"></i>${safeEmail}
                    </p>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-3" onclick="openAddAndEditBranchModal('edit', ${branchId})">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                    </button>
                </div>

                <div class="col-12 col-md-7 mt-3 mt-md-0">
                    <p class="text-muted small mb-2">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>${safeAddress}
                    </p>

                    <div class="map branch-map border rounded bg-secondary-subtle" id="${uniqueMapId}" style="height: 200px; width: 100%;"></div>
                </div>
            </div>
        `;

        container.appendChild(cardDiv);

        try {
            const mapInstance = L.map(uniqueMapId).setView([safeLat, safeLng], 15);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution: "© OpenStreetMap contributors"
            }).addTo(mapInstance);

            const markerInstance = L.marker([safeLat, safeLng]).addTo(mapInstance);

            markerInstance.bindPopup(`
                <strong>${safeName}</strong><br>
                <small class="text-muted">${safeAddress}</small>
            `);

            activeLeafletMapsRegistry[uniqueMapId] = mapInstance;

            setTimeout(() => {
                mapInstance.invalidateSize();
                markerInstance.openPopup();
            }, 350);
        } catch (error) {
            console.error(`Failed to initialize map ${uniqueMapId}:`, error);
        }
    });
}

function clearBranchForm() {
    const form = document.getElementById("editBranchForm");

    if (form) {
        form.reset();
    }

    document.getElementById("editBranchIDInput").value = "";
    document.getElementById("editLatitude").value = "";
    document.getElementById("editLongitude").value = "";
    document.getElementById("editBranchNameInput").value = "";
    document.getElementById("editContactNumberInput").value = "";
    document.getElementById("editEmailInput").value = "";
    document.getElementById("editAddressInput").value = "";
    document.getElementById("pinMapCoords").textContent = "--";
    document.getElementById("pinMapPreviewAddress").textContent = "Search an address or drag the pin to set the exact location.";
}

function destroyEditMap() {
    if (editPinMapInstance) {
        editPinMapInstance.remove();
        editPinMapInstance = null;
        editPinMarkerInstance = null;
    }
}

function initializeBranchMap(lat, lng) {
    const mapContainer = document.getElementById("pinMapContainer");

    if (!mapContainer) return;

    destroyEditMap();

    editPinMapInstance = L.map("pinMapContainer").setView([lat, lng], 16);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap contributors"
    }).addTo(editPinMapInstance);

    editPinMarkerInstance = L.marker([lat, lng], {
        draggable: true
    }).addTo(editPinMapInstance);

    updateBranchCoordinates(lat, lng);

    editPinMarkerInstance.on("dragend", () => {
        const position = editPinMarkerInstance.getLatLng();
        updateBranchCoordinates(position.lat, position.lng);
        reverseGeocodeBranchPin(position.lat, position.lng);
    });

    setTimeout(() => {
        if (editPinMapInstance) {
            editPinMapInstance.invalidateSize();
        }
    }, 300);
}

function updateBranchCoordinates(lat, lng) {
    document.getElementById("editLatitude").value = Number(lat).toFixed(8);
    document.getElementById("editLongitude").value = Number(lng).toFixed(8);
    document.getElementById("pinMapCoords").textContent = `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
}

function openAddAndEditBranchModal(action, branchId = null) {
    const modalEl = document.getElementById("addAndEditBranchModal");

    if (!modalEl) return;

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    const modalTitle = document.getElementById("addAndEditBranchModalLabel");
    const saveButton = document.getElementById("saveBranchBtn");

    currentBranchModalMode = action;

    if (action === "add") {
        clearBranchForm();
        destroyEditMap();

        modalEl.dataset.mode = "add";
        modalTitle.innerHTML = '<i class="bi bi-shop me-2"></i>Add Branch';
        saveButton.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add Branch';

        modalInstance.show();

        modalEl.addEventListener("shown.bs.modal", function initAddMap() {
            modalEl.removeEventListener("shown.bs.modal", initAddMap);

            initializeBranchMap(14.5995, 120.9842);

            document.getElementById("editLatitude").value = "";
            document.getElementById("editLongitude").value = "";
            document.getElementById("pinMapCoords").textContent = "--";
            document.getElementById("pinMapPreviewAddress").textContent = "Search an address or drag the pin to set the exact location.";
        });

        return;
    }

    if (action === "edit") {
        const targetBranch = branchesData.find(branch => branch.BranchID == branchId);

        if (!targetBranch) {
            console.error("Could not locate branch data for ID:", branchId);
            return;
        }

        modalEl.dataset.mode = "edit";
        modalTitle.innerHTML = '<i class="bi bi-shop me-2"></i>Edit Branch';
        saveButton.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Save Changes';

        document.getElementById("editBranchIDInput").value = targetBranch.BranchID || "";
        document.getElementById("editBranchNameInput").value = targetBranch.BranchName || "";
        document.getElementById("editContactNumberInput").value = targetBranch.ContactNumber || "";
        document.getElementById("editEmailInput").value = targetBranch.Email || "";
        document.getElementById("editAddressInput").value = targetBranch.Address || "";
        document.getElementById("editLatitude").value = targetBranch.Latitude || "";
        document.getElementById("editLongitude").value = targetBranch.Longitude || "";
        document.getElementById("pinMapCoords").textContent = targetBranch.Latitude && targetBranch.Longitude ? `${Number(targetBranch.Latitude).toFixed(6)}, ${Number(targetBranch.Longitude).toFixed(6)}` : "--";
        document.getElementById("pinMapPreviewAddress").textContent = targetBranch.Address || "Drag the pin to set the exact location.";

        destroyEditMap();

        modalInstance.show();

        modalEl.addEventListener("shown.bs.modal", function initEditMap() {
            modalEl.removeEventListener("shown.bs.modal", initEditMap);

            const lat = parseFloat(targetBranch.Latitude) || 14.5995;
            const lng = parseFloat(targetBranch.Longitude) || 120.9842;

            initializeBranchMap(lat, lng);

            document.getElementById("editLatitude").value = targetBranch.Latitude || "";
            document.getElementById("editLongitude").value = targetBranch.Longitude || "";
        });
    }
}

async function reverseGeocodeBranchPin(lat, lng) {
    try {
        if (branchReverseGeocodeController) {
            branchReverseGeocodeController.abort();
        }

        branchReverseGeocodeController = new AbortController();

        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${lat}&lon=${lng}`;

        const response = await fetch(url, {
            headers: {
                Accept: "application/json"
            },
            signal: branchReverseGeocodeController.signal
        });

        if (!response.ok) {
            throw new Error("Reverse geocoding failed.");
        }

        const data = await response.json();
        const address = data.display_name || `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;

        document.getElementById("editAddressInput").value = address;
        document.getElementById("pinMapPreviewAddress").textContent = address;
    } catch (error) {
        if (error.name === "AbortError") return;

        console.error("Reverse geocoding error:", error);
    }
}

async function searchTypedAddressOnMap() {
    const addressInput = document.getElementById("editAddressInput");
    const locateButton = document.getElementById("btnLocateAddress");

    if (!addressInput) return;

    const address = addressInput.value.trim();

    if (!address) {
        showAlertBanner("Please enter an address to locate on the map.", "danger");
        addressInput.focus();
        return;
    }

    const originalButtonHTML = locateButton.innerHTML;
    locateButton.disabled = true;
    locateButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Locating...';

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=1&countrycodes=ph&q=${encodeURIComponent(address)}`;

        const response = await fetch(url, {
            headers: {
                Accept: "application/json"
            }
        });

        if (!response.ok) {
            throw new Error("Address search failed.");
        }

        const results = await response.json();

        if (!results.length) {
            showAlertBanner("Can't find the address. Please enter a more specific address.", "danger");
            return;
        }

        const result = results[0];
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            throw new Error("Invalid coordinates returned.");
        }

        if (!editPinMapInstance) {
            initializeBranchMap(lat, lng);
        } else {
            editPinMapInstance.setView([lat, lng], 17);

            if (editPinMarkerInstance) {
                editPinMarkerInstance.setLatLng([lat, lng]);
            }
        }

        updateBranchCoordinates(lat, lng);

        const resultAddress = result.display_name || address;
        addressInput.value = resultAddress;
        document.getElementById("pinMapPreviewAddress").textContent = resultAddress;

        setTimeout(() => {
            if (editPinMapInstance) {
                editPinMapInstance.invalidateSize();
            }
        }, 200);
    } catch (error) {
        console.error("Address search error:", error);
        showAlertBanner("System mapping service timed out. Try again later.", "danger");
    } finally {
        locateButton.disabled = false;
        locateButton.innerHTML = originalButtonHTML;
    }
}

function updateBranch() {
    const modalEl = document.getElementById("addAndEditBranchModal");
    const formEl = document.getElementById("editBranchForm");

    if (!formEl || !modalEl) return;

    if (!formEl.checkValidity()) {
        formEl.reportValidity();
        return;
    }

    const latitude = document.getElementById("editLatitude").value.trim();
    const longitude = document.getElementById("editLongitude").value.trim();

    if (!latitude || !longitude) {
        showAlertBanner("Please locate the branch on the map before saving.", "danger");
        return;
    }

    const confirmModalEl = document.getElementById("branchConfirmModal");
    const confirmMessage = document.getElementById("branchConfirmMessage");
    const confirmButton = document.getElementById("confirmUpdateBtn");

    if (currentBranchModalMode === "add") {
        confirmMessage.textContent = "Are you sure you want to add this branch?";
        confirmButton.textContent = "Yes, Add";
    } else {
        confirmMessage.textContent = "Are you sure you want to update this branch's details?";
        confirmButton.textContent = "Yes, Update";
    }

    const mainModalInstance = bootstrap.Modal.getInstance(modalEl);

    if (mainModalInstance) {
        mainModalInstance.hide();
    }

    const confirmModalInstance = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
    confirmModalInstance.show();
}

async function confirmUpdateBtn() {
    const confirmModalEl = document.getElementById("branchConfirmModal");
    const formEl = document.getElementById("editBranchForm");
    const confirmButton = document.getElementById("confirmUpdateBtn");

    if (!confirmModalEl || !formEl) return;

    confirmButton.disabled = true;
    confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    try {
        const formData = new FormData(formEl);

        const response = await fetch("../api/branch.php", {
            method: "POST",
            body: formData
        });

        if (!response.ok) {
            throw new Error("Server returned a network communication error.");
        }

        const result = await response.json();

        bootstrap.Modal.getInstance(confirmModalEl)?.hide();

        if (result.success) {
            showAlertBanner(result.message, "success");
            destroyEditMap();
            await loadBranchData();
        } else {
            showAlertBanner(result.message || "Branch operation failed.", "danger");
        }
    } catch (error) {
        console.error(error);
        bootstrap.Modal.getInstance(confirmModalEl)?.hide();
        showAlertBanner(error.message || "System busy. Request processing failed.", "danger");
    } finally {
        confirmButton.disabled = false;

        if (currentBranchModalMode === "add") {
            confirmButton.innerHTML = "Yes, Add";
        } else {
            confirmButton.innerHTML = "Yes, Update";
        }
    }
}

document.getElementById("addAndEditBranchModal")?.addEventListener("hidden.bs.modal", () => {
    destroyEditMap();
});

document.getElementById("branchConfirmModal")?.addEventListener("hidden.bs.modal", () => {
    const mainModal = document.getElementById("addAndEditBranchModal");

    if (mainModal.classList.contains("show")) {
        bootstrap.Modal.getInstance(mainModal)?.hide();
    }
});
</script>
