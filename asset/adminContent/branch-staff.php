<?php
    $branchesResult = $conn->query("SELECT BranchID, BranchName FROM branches ORDER BY BranchID");
    $branches = [];
    while ($row = $branchesResult->fetch_assoc()) {
        $branches[] = $row;
    }
?>

<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-shop me-2"></i>Branch Staff</h2>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading system branch layout networks...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <table id="branchStaffTable" class="display">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="branchStaffModal" aria-labelledby="branchStaffModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-xl modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="branchStaffModalLabel">Edit Staff Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="branchStaffModalBody">
                <div class="text-center py-5 modalCard rounded" id="branchStaffModalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading staff details...</p>
                </div>

                <div class="container rounded-4" id="branchStaffModalContentArea" style="display: none;">
                    <form action="" method="post">
                        <input type="hidden" name="user_id" id="user_id" value="">
                        <input type="hidden" name="branch_name" id="branch_name" value="">
                        <div class="row g-3">
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="full_name" id="full_name" value="" class="form-control" disabled>
                                    <label for="full_name">Full Name</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="email" id="email" value="" class="form-control" disabled>
                                    <label for="email">Email</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="contact_number" id="contact_number" value="" class="form-control" disabled>
                                    <label for="contact_number">Contact Number</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="address" id="address" value="" class="form-control" disabled>
                                    <label for="address">Address</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <label for="role" class="col-sm-2 col-form-label">Role:</label>
                            <div class="col-sm-10">
                                <select class="form-select" name="role" id="role" disabled>
                                    <option value="Cashier">Cashier</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <label for="branch_id" class="col-sm-2 col-form-label">Assigned Branch :</label>
                            <div class="col-sm-10">
                                <select class="form-select" name="branch_id" id="branch_id">
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?php echo $b['BranchID']; ?>">
                                            <?php echo htmlspecialchars($b['BranchName']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="user_name" id="user_name" value="" class="form-control" required>
                                    <label for="user_name">User name</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="input-group">
                                    <div class="form-floating">
                                        <input type="password" name="password" id="password" value="" class="form-control" required>
                                        <label for="password">New Password</label>
                                    </div>
                                    <button type="button" class="btn btn-outline-warning" onclick="togglePassword()">
                                        <i class="bi bi-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer" id="branchStaffModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="branchStaffFormSubmitBtn" onclick="branchStaffModalSubmitBtn()">
                    <i class="bi bi-check2-circle me-1"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="branchStaffConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="invConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold" id="branchStaffConfirmLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Changes
                </h5>
            </div>
            <div class="modal-body py-3 text-start">
                <p class="m-0 fs-6">Are you sure you want to update this staff records? This action modifies staff credentials instantly.</p>
            </div>
            <div class="modal-footer border-top border-secondary p-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="branchStaffConfirmModalSaveBtn" class="btn btn-sm btn-warning">Yes, Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    const branchStaffTable = new DataTable("#branchStaffTable", {
        responsive: true,
        scrollCollapse: true,
        scroller: true,
        scrollY: 400,
        layout: {
            topStart: {
                buttons: ["print"]
            }
        },
        columns: [{
                data: 'FullName'
            },
            {
                data: 'BranchName',
                render: function(data) {
                    return data ? data : '<span class="text-muted italic">Unassigned</span>';
                }
            },
            {
                data: 'Role'
            },
            {
                data: 'CreatedAt',
                render: function(data) {
                    return data ? data.split(' ')[0] : '--'; // Formats timestamp to YYYY-MM-DD
                }
            },
            {
                data: null,
                render: function(data, type, row, meta) {
                    return `<button class="btn btn-sm btn-outline-primary" title="Edit Staff" onclick="openBranchStaffEditModal(${meta.row})"><i class="bi bi-pencil"></i></button>`;
                }
            }
        ]
    });

    document.addEventListener("DOMContentLoaded", loadBranchData);

    async function loadBranchData() {
        document.getElementById("loadingCard").style.display = "block";
        document.getElementById("sectionCard").style.display = "none";

        try {
            const response = await fetch('../api/user.php?getBranchStaffByAdmin');
            if (!response.ok) throw new Error(`HTTP Error Status Code: ${response.status}`);

            const payload = await response.json();

            console.log(payload)

            branchStaffTable.clear().rows.add(payload);

        } catch (error) {
            showAlertBanner(error, 'danger');
        } finally {
            branchStaffTable.draw();

            document.getElementById("loadingCard").style.display = "none";
            document.getElementById("sectionCard").style.display = "block";
        }
    }

    function openBranchStaffEditModal(rowIndex) {
        const loaderArea = document.getElementById('branchStaffModalLoaderArea');
        const contentArea = document.getElementById('branchStaffModalContentArea');
        
        if (loaderArea) loaderArea.style.display = 'block';
        if (contentArea) contentArea.style.display = 'none';

        const modalEl = document.getElementById("branchStaffModal");
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();           
             
        const branchStaffData = branchStaffTable.row(rowIndex).data();
    
        console.log(branchStaffData);

        if (branchStaffData) {
            setTimeout(() => {
                document.getElementById("user_id").value = branchStaffData.UserID;
                document.getElementById("branch_name").value = branchStaffData.BranchName;

                document.getElementById("full_name").value = branchStaffData.FullName;
                document.getElementById("email").value = branchStaffData.Email;
                document.getElementById("contact_number").value = branchStaffData.ContactNumber;
                document.getElementById("address").value = branchStaffData.Address;

                document.getElementById("branch_id").value = branchStaffData.BranchID;

                document.getElementById("user_name").value = branchStaffData.Username;
                document.getElementById("password").value = '';

                if (loaderArea) loaderArea.style.display = 'none';
                if (contentArea) contentArea.style.display = 'block';
            }, 500);
        } else {
            if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture inventory info.</p>';
        }
    }

    function branchStaffModalSubmitBtn() {
        const editModalEl = document.getElementById("branchStaffModal");
        const formEl = document.querySelector("#branchStaffModalBody form");
        if (!formEl) return;

        if (!formEl.checkValidity()) {
            formEl.reportValidity();
            return;
        }
        const editModalInstance = bootstrap.Modal.getInstance(editModalEl);
        const confirmModalEl = document.getElementById("branchStaffConfirmModal");

        if (confirmModalEl) {
            const saveBtn = document.getElementById("branchStaffConfirmModalSaveBtn");
            if (saveBtn) {
                saveBtn.onclick = function () {
                    confirmBranchStaffUpdate();
                };
            }

            if (editModalInstance) editModalInstance.hide();

            const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl) || new bootstrap.Modal(confirmModalEl);

            confirmModalInstance.show();
        }
    }

    function confirmBranchStaffUpdate() {
        const confirmModalEl = document.getElementById("branchStaffConfirmModal");
        const formEl = document.querySelector("#branchStaffModalBody form");

        if (!confirmModalEl || !formEl) return;

        const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);
        const confirmBtn = document.getElementById("branchStaffConfirmModalSaveBtn");

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Updating...`;
        }

        const formData = new FormData(formEl);
        const userID = formData.get("user_id");

        fetch(`../api/user.php?userId=${userID}`, {
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
                        refreshInventoryData();
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
        const password = document.getElementById("password");
        const icon = document.getElementById("passwordToggleIcon");

        const isPassword = password.type === "password";

        password.type = isPassword ? "text" : "password";
        icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
    }
</script>