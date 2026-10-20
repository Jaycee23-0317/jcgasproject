<div class="modal fade" id="logoutConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-start">
            <div class="modal-header bg-dark-navy border-bottom border-secondary">
                <h5 class="modal-title text-gas-cyan fw-bold" id="logoutModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirm Sign Out
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="m-0 fs-6">Are you sure you want to log out of your session? Any unsaved transaction modifications will be discarded.</p>
            </div>
            <div class="modal-footer border-top border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <!-- Clicking this button triggers the final redirection routine -->
                <button type="button" id="confirmLogoutBtn" class="btn btn-danger px-4">
                    <i class="bi bi-box-arrow-right me-1"></i>Yes, Logout
                </button>
            </div>
        </div>
    </div>
</div>