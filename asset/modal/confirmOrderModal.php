<div class="modal fade" id="confirmOrderModal" tabindex="-1" aria-labelledby="confirmOrderModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white bg-dark-navy">
                <h5 class="modal-title" id="confirmOrderModalLabel"><i class="bi bi-shield-check me-2"></i>Confirm Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-slate-blue">
                <div class="confirm-order-icon text-center mb-3">
                    <i class="bi bi-question-circle"></i>
                </div>
                <p class="confirm-order-text text-center mb-3" id="confirmOrderModalText">
                    Are you sure you want to place this order?
                </p>
                <div class="confirm-order-summary text-white">
                    <div class="confirm-order-summary-row">
                        <span><i class="bi bi-credit-card me-2"></i>Payment:</span>
                        <span class="fw-semibold text-gas-cyan" id="confirmOrderModalPayment">--</span>
                    </div>
                    <div class="confirm-order-summary-row">
                        <span><i class="bi bi-receipt me-2"></i>Total:</span>
                        <span class="fw-bold text-gas-cyan" id="confirmOrderModalTotal">₱0.00</span>
                    </div>
                    <div class="confirm-order-summary-row align-items-start">
                        <span><i class="bi bi-geo-alt me-2"></i>Delivery Address:</span>
                        <span class="fw-semibold text-gas-cyan" id="confirmOrderModalAddress">--</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center gap-2 bg-dark-navy">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left me-1"></i>Go Back
                </button>
                <button type="button" class="btn btn-gas-primary" id="confirmOrderModalConfirmBtn" onclick="proceedOrderSubmission()">
                    <i class="bi bi-check2-circle me-1"></i>Yes, Place Order
                </button>
            </div>
        </div>
    </div>
</div>