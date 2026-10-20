<div class="main-content flex-grow-1 px-3">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold">
            <i class="bi bi-person-lock me-2"></i>Password
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
                        <i class="bi bi-person-lock me-2"></i>
                        Log in Credentials
                    </h4>

                    <button type="button" class="btn btn-sm btn-warning" onclick="editAccountModal()">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Account
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col col-12">
                        <label for="email" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Email</p>
                        </label>
                        <input type="email" class="form-control" id="email" disabled >
                    </div>
                    <div class="col col-md-6">
                        <label for="username" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Username</p>
                        </label>
                        <input type="text" class="form-control" id="username" disabled >
                    </div>

                    <div class="col col-md-6">
                        <label for="password" class="form-label m-0">
                            <p class="fs-6 m-0 fw-bold text-black-50">Password</p>
                        </label>
                        <input type="password" class="form-control" id="password" disabled >
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accountCredentialsModal" aria-labelledby="accountCredentialsModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-xl modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="accountCredentialsModalLabel">Inventory Modal</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="accountCredentialsModalBody">
                <div class="text-center py-5 modalCard rounded" id="accountCredentialsModalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading inventory details...</p>
                </div>

                <div class="container rounded-4" id="accountCredentialsModalContentArea" style="display: none;">
                    <form action="" method="post">
                        <div class="row g-3">
                            <div class="col col-12">
                                <label for="form_email" class="form-label m-0">
                                    <p class="fs-6 m-0 fw-bold text-black-50">Email</p>
                                </label>
                                <input type="form_email" class="form-control" name="form_email" id="form_email" required >
                            </div>
                            <div class="col col-md-6">
                                <label for="form_username" class="form-label m-0">
                                    <p class="fs-6 m-0 fw-bold text-black-50">Username</p>
                                </label>
                                <input type="text" class="form-control" name="form_username" id="form_username" required >
                            </div>

                            <div class="col col-md-6">
                                <label for="password" class="form-label m-0">
                                    <p class="fs-6 m-0 fw-bold text-black-50">Password</p>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="form_password" id="form_password" required>
                                    <button type="button" class="btn btn-outline-warning" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer" id="accountCredentialsModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="accountCredentialsFormSubmitBtn" onclick="accountCredentialsModalSubmitBtn()">
                    <i class="bi bi-check2-circle me-1"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="accountCredentialsConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="invConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold" id="accountCredentialsConfirmLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Changes
                </h5>
            </div>
            <div class="modal-body py-3 text-start">
                <p class="m-0 fs-6">Are you sure you want to update your account credentials? This action modifies your account credentials instantly.</p>
            </div>
            <div class="modal-footer border-top border-secondary p-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="accountCredentialsConfirmSaveBtn" class="btn btn-sm btn-warning">Yes, Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    let accountData = [];

    async function refreshAccountCredentials() {
        const loadingCard = document.getElementById("loadingCard");
        const sectionCard = document.getElementById("sectionCard");

        loadingCard.style.display = "block";
        sectionCard.style.display = "none";

        try {
            const response = await fetch(`../api/user.php?`);

            if (!response.ok) throw new Error("Failed to load inventory.");

            accountData = await response.json();

            document.getElementById("email").value = accountData.Email || '';
            document.getElementById("username").value = accountData.Username || '';
            document.getElementById("password").value = accountData.Password || '';

        } catch (error) {
            showAlertBanner("Failed to load account data.", "danger");
        } finally {
            loadingCard.style.display = "none";
            sectionCard.style.display = "block";
        }
    }

    document.addEventListener("DOMContentLoaded", refreshAccountCredentials);

    function editAccountModal() {
        const loaderArea = document.getElementById('accountCredentialsModalLoaderArea');
        const contentArea = document.getElementById('accountCredentialsModalContentArea');
        
        if (loaderArea) loaderArea.style.display = 'block';
        if (contentArea) contentArea.style.display = 'none';

        const modalEl = document.getElementById("accountCredentialsModal");
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();           
             
        if (accountData) {
            setTimeout(() => {
                document.getElementById("form_email").value = accountData.Email || '';
                document.getElementById("form_username").value = accountData.Username || '';
                document.getElementById("form_password").value = accountData.Password || '';

                if (loaderArea) loaderArea.style.display = 'none';
                if (contentArea) contentArea.style.display = 'block';
            }, 500);
        } else {
            if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture inventory info.</p>';
        }
    }

    function accountCredentialsModalSubmitBtn() {
        const editModalEl = document.getElementById("accountCredentialsModal");
        const formEl = document.querySelector("#accountCredentialsModalBody form");
        if (!formEl) return;

        if (!formEl.checkValidity()) {
            formEl.reportValidity();
            return;
        }
        const editModalInstance = bootstrap.Modal.getInstance(editModalEl);
        const confirmModalEl = document.getElementById("accountCredentialsConfirmModal");

        if (confirmModalEl) {
            const saveBtn = document.getElementById("accountCredentialsConfirmSaveBtn");
            if (saveBtn) {
                saveBtn.onclick = function () {
                    confirmAccountCredentialsUpdate();
                };
            }

            if (editModalInstance) editModalInstance.hide();

            const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl) || new bootstrap.Modal(confirmModalEl);

            confirmModalInstance.show();
        }
    }

    function confirmAccountCredentialsUpdate() {
        const confirmModalEl = document.getElementById("accountCredentialsConfirmModal");
        const formEl = document.querySelector("#accountCredentialsModal form");

        if (!confirmModalEl || !formEl) return;

        const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);
        const confirmBtn = document.getElementById("accountCredentialsConfirmConfirmSaveBtn");

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Updating...`;
        }

        const formData = new FormData(formEl);
        const userID = accountData.UserID;

        fetch(`../api/user.php?user_id=${userID}`, {
            method: "POST",
            body: formData,
        })
            .then((response) => {
                if (!response.ok)
                    throw new Error("Server returned network communication error.");
                    return response.json();
                })
            .then((result) => {
                if (confirmModalInstance) confirmModalInstance.hide();

                setTimeout(() => {
                    if (result.success) {
                        showAlertBanner(result.message, "success");
                        refreshAccountCredentials();
                    } else {
                        showAlertBanner(result.message, "danger");
                    }

                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = "Yes, Update";
                    }
                }, 300);
            })
            .catch((error) => {
                if (confirmModalInstance) confirmModalInstance.hide();
                console.log(error.message);
                setTimeout(() => {
                    showAlertBanner(
                        error.message || "System busy. Request processing failed.",
                        "danger",
                    );
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = "Yes, Update";
                    }
                }, 300);
            });
    }

    function togglePassword() {
        const password = document.getElementById("form_password");
        const icon = document.getElementById("passwordToggleIcon");

        const isPassword = password.type === "password";

        password.type = isPassword ? "text" : "password";
        icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
    }

</script>