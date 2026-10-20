<div class="modal fade" id="statusConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="statusConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="statusConfirmModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Status Change
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="fs-5 mb-1" id="confirmNextStatus">Are you sure you want to update order status?</p>
                <div class="fw-bold fs-4 text-primary mb-3" id="confirmOrderGroupIdTextDisplay" >Order #<span id="confirmOrderGroupIdDisplay"></span></div>
                <p class="text-muted">This will change the workflow status tag permanently to:</p>
                <span class="badge bg-dark fs-6 px-3 py-2" id="confirmNextStatusDisplay"></span>

                <div id="cancelReasonContainer" class="mt-3" style="display: none;">
                    <label for="cancelledReason" class="form-label fw-bold text-start d-block">
                        Cancellation Reason <span class="text-danger">*</span>
                    </label>

                    <textarea
                        class="form-control"
                        id="cancelledReason"
                        rows="3"
                        placeholder="Enter the reason for cancelling this order..."
                        maxlength="500"></textarea>

                    <div class="invalid-feedback text-start">
                        Please provide a reason for cancelling this order.
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">No, Cancel</button>
                <button type="button" class="btn btn-warning px-4 fw-bold" id="btnConfirmStatusSubmit" onclick="confirmOrderStatusSubmitBtn()">Yes, Update It</button>
            </div>
        </div>
    </div>
</div>