<div class="modal fade" id="orderModalDetails" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalDetailsLabel">
                    <i class="bi bi-receipt me-2"></i>OrderID #<span id="modalOrderGroupIDDisplay"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="modalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
                <div class="container" id="modalContentArea" style="display: none;">
                    <div class="row text-center row-cols-2 row-cols-lg-3 g-2 g-lg-3 mb-2 p-3 bg-light rounded">
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Order ID #</span>
                            <strong id="modalOrderIDDisplay"></strong>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Customer Name</span>
                            <strong id="modalCustomerDisplay"> HELLO </strong>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Transaction ID #</span>
                            <strong id="modalTransactionIDDisplay"></strong>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Type</span>
                            <span id="modalOrdertypeDisplay" class="fw-bold"></span>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Status</span>
                            <span id="modalStatusDisplay" class="fw-bold"></span>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Transaction Date</span>
                            <span id="modalDateDisplay" class="fw-bold"></span>
                        </div>
                        <div class="col m-0 col-6 col-md-4 p-2">
                            <span class="text-muted d-block small">Payment Method</span>
                            <span id="modalPaymentMethodDisplay" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="row text-center border mb-2">
                        <div class="col m-0 col-12 p-2" id="cancelReasonCol" style="display: none">
                            <span class="text-muted d-block small">Cancel Reason</span>
                            <span id="modalCancelReasonDisplay" class="fw-bold"></span>
                        </div>
                    </div>

                    <h6><i class="bi bi-box-seam me-2"></i>Ordered Items</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th scope="col">Product Item</th>
                                    <th scope="col">Size</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Qty</th>
                                    <th scope="col">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="table-group-divider" id="modalItemsTableBody">
                                <!-- Injected dynamically by javascript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total Bill Amount:</th>
                                    <th class="text-end text-success fs-5" id="modalTotalDisplay"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <h6 class="review-section-title mt-3"><i class="bi bi-truck me-2"></i>Delivery Address</h6>
                    <div class="review-delivery-box p-3 bg-light rounded border mb-3">
                        <div class="delivery-address-text text-dark" id="orderDeliveryAddress">--</div>
                        <div class="delivery-address-coords small text-dark-50 mt-1">
                            <i class="bi bi-geo-alt me-1"></i><span id="orderDeliveryCoords">--</span>
                        </div>
                    </div>
                    <div class="review-map-loading text-center py-3" id="orderDeliveryMapLoading">
                        <div class="spinner-border spinner-border-sm text-warning" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0 small text-white-50">Loading delivery map...</p>
                    </div>
                    <div id="deliveryMapAddress" class="order-review-map"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>