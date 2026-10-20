<div class="modal fade" id="reviewOrderModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderReviewModalLabel"><i class="bi bi-receipt me-2"></i>Review Your Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5 modalCard rounded" id="reviewOrderModalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
                <div class="container rounded-4" id="reviewOrderModalContentArea" style="display: none;">
                    <h6 class="text-gas-cyan"><i class="bi bi-bag-check me-2"></i>Order Items</h6>
                    <div class="table-responsive table-bordered align-middle card modalCard mb-2">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Size</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="reviewOrderItemsBody">
                                <!-- Dynamic items -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-semibold">Total:</td>
                                    <td class="text-end fw-bold fs-5 text-gas-cyan" id="reviewOrderTotal">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Payment method -->
                    <div class="card modalCard d-flex flex-row justify-content-between align-items-center p-3 mb-2">
                        <span><i class="bi bi-credit-card me-2"></i>Payment Method:</span>
                        <span class="fw-semibold" id="reviewOrderPayment">--</span>
                    </div>

                    <div class="card modalCard p-3">
                        <div class="d-flex flex-row justify-content-between align-items-center pb-2">
                            <h6 class="text-gas-cyan m-0"><i class="bi bi-truck me-2"></i>Delivery Address</h6>
                            <button type="button" class="btn btn-sm btn-outline-warning" id="btnResetReviewPin" onclick="resetReviewMapToSavedState()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Pin Location
                            </button>
                        </div>

                        <div id="reviewDeliveryMapAddress" class="order-review-map"></div>
                        <div class="mt-2" id="reviewDeliveryMapAddressText">--</div>
                        <div class="small">
                            <i class="bi bi-geo-alt me-1 text-gas-cyan"></i>
                            <span id="reviewDeliveryLat">--</span>,
                            <span id="reviewDeliveryLong">--</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-gas-primary" onclick="confirmOrderBtn()"><i class="bi bi-check2-circle me-1"></i>Confirm & Place Order</button>
            </div>
        </div>
    </div>
</div>