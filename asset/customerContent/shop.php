<?php

include_once '../api/branch.php';
include_once '../api/user.php';

$role = $_SESSION['role'];
$branchID = $_SESSION['branch_id'];
$userID = $_SESSION['user_id'];
$savedAddress = 'No saved address';
$savedLat = '---';
$savedLng = '---';

if ($role === 'Cashier') {
    $address = getBranchAddress($conn, $branchID);
    $savedAddress = $address['Address'];
    $savedLat = $address['Latitude'];
    $savedLng = $address['Longitude'];
} else {
    $address = getUserAddress($conn, $userID);
    $savedAddress = $address['Address'];
    $savedLat = $address['Latitude'];
    $savedLng = $address['Longitude'];
}

?>

<div class="main-section h-100 w-100">
    <!-- SHOP CONTAINER: Takes full viewport height minus standard header buffers -->
    <div id="shopLayout" class="shop-container d-flex w-100">

        <!-- LEFT AREA: Main Content (Branches Grid) -->
        <div class="shop-main-content flex-grow-1 pe-2">
            <div class="shop-section-header bg-main sticky-top d-flex justify-content-between align-items-center border-bottom">
                <h2 class="py-3 m-0 fw-bold text-navy"><i class="bi bi-shop me-2"></i>Shop Branches</h2>
                <div class="header-buttons">

                    <button id="sidebar-cart" class="btn sidebar-cart btn-gas-outline-primary" onclick="toggleCartSidebar('cartSidebar')">
                        <i class="bi bi-cart"></i>
                    </button>
                </div>
            </div>

            <div class="shop-section-body">
                <!-- Preloader Component Wrapper -->
                <div class="text-center py-5 m-3 border rounded bg-white shadow-sm" id="shopLoader" style="display: block;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted mb-0 fw-semibold">Syncing store layout, please wait...</p>
                </div>

                <!-- Main Render Area -->
                <div id="sectionCard" class="d-flex flex-column w-100">
                    <input type="hidden" id="roleHiddenInput" value="<?php echo $_SESSION['role'] ?>">
                    <input type="hidden" id="savedLatHiddenInput" value="<?php echo $savedLat ?>">
                    <input type="hidden" id="savedLongHiddenInput" value="<?php echo $savedLng ?>">


                    <div class="w-100" id="selectBranchDisplay">
                        <h3 class="py-2 text-gas-cyan border-bottom">Select Branch <i class="bi bi-shop"></i></h3>

                        <div id="branchesContainer" class="w-100">
                            <!-- Dynamic Javascript layout cards generate here -->
                        </div>
                    </div>

                    <div class="w-100" id="selectedBranchDisplay">
                        <div class="d-flex justify-content-between align-items-center border-bottom p-2">
                            <div>
                                <input type="hidden" id="selectedBranchID" value="">
                                <h3 class="text-gas-cyan" id="selectedBranchNameDisplay"><i class="bi bi-shop me-1"></i></h3>
                                <p class="text-dark-50 m-0" id="selectedBranchAddressDisplay"></p>
                            </div>
                            <button type="button" class="btn btn-outline-danger backBtn">
                                <i class="bi bi-arrow-left me-2"></i>Back
                            </button>
                        </div>
                        <input type="hidden" id="selectedBranchIDHiddenInput" value="">

                        <div id="shopItemContainer" class="text-center w-100">
                            <!-- Dynamic Javascript layout cards generate here -->
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- RIGHT AREA: Fixed Dynamic Action Sidebar -->
        <div class="shop-sidebar-content d-flex flex-column rounded-3 bg-dark-navy m-1">



            <!-- FIX 1: Added d-none (instead of style="display:none") and set d-flex flex-column h-100 -->
            <div id="cartSidebar" class="cart-sidebar d-flex flex-column h-100 <?php ($role === 'Cashier') ? 'd-none' : ''; ?>">
                <!-- FIX 2: Added h-100 here to ensure it fills the entire sidebar column wrapper depth -->
                <div class="d-flex flex-column flex-grow-1 border-start h-100">

                    <div class="shop-sidebar-header d-flex align-items-center justify-content-center border-bottom text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="bi bi-cart-check me-2"></i>Cart
                        </h6>
                    </div>

                    <div class="shop-sidebar-main d-flex flex-column flex-grow-1 text-white overflow-hidden">
                        <!-- This container will now scale out and maximize all available screen real estate -->
                        <div class="flex-grow-1 overflow-auto p-2 pb-0">
                            <div class="cart-empty-message text-center text-white-50 py-4" id="cartEmptyMessage">
                                <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                <p class="mb-0">No items selected</p>
                                <small>Select products from the shop</small>
                            </div>
                            <ul class="cart-items-list list-group" id="cartItemsList">
                                <!-- Dynamic cart items will be inserted here -->
                            </ul>
                        </div>

                        <!-- Sticky Metrics & Controls Footer Area Layout Block -->
                        <div class="mt-auto bg-dark-buffer">
                            <div class="d-flex border-top align-items-center justify-content-between mb-1 px-2 pt-2">
                                <h6 class="m-0">Item/s:</h6>
                                <h6 class="m-0 fw-bold text-gas-cyan"><span id="itemCount">0</span></h6>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-1 px-2">
                                <h6 class="m-0">Total:</h6>
                                <h6 class="m-0 fw-bold text-gas-cyan">₱ <span id="total">00.00</span></h6>
                            </div>

                            <div class="mb-2 border-top px-2 pt-2">
                                <label for="customerName" class="form-label small mb-0 text-light">Customer Name:</label>
                                <input type="text" class="form-control form-control-sm" id="customerName" placeholder="(Optional)">
                            </div>

                            <div class="px-2 mb-2">
                                <label for="paymentMethod" class="form-label small mb-0 text-light">Select Payment:</label>
                                <select class="form-select form-select-sm" id="paymentMethod">
                                    <option value="Cash On Delivery">Cash On Delivery</option>
                                    <option value="Cash">Cash</option>
                                    <option value="" disabled>Gcash (coming soon)</option>
                                    <option value="" disabled>Paymaya (coming soon)</option>
                                </select>
                            </div>

                            <div class="px-2 mb-2">
                                <span class="small d-block" style="font-size:0.7rem;">DELIVERY ADDRESS</span>
                                <div class="deliveryAddressBox" title="<?php echo $savedAddress; ?>">
                                    <div class="p-2">
                                        <p class="m-0 fw-bold text-light text-truncate-2-lines">
                                            <i class="bi bi-geo-alt m-0 me-1"></i>
                                            <span id="deliveryAddress"><?php echo $savedAddress; ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="shop-sidebar-footer p-2 bg-dark">
                        <button class="btn btn-sm btn-gas-primary w-100 py-2 fw-semibold" id="placeOrderBtn" onclick="placeOrderBtn()" disabled>
                            <i class="bi bi-check2-circle me-1"></i> Place Order
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="../js/shop.js"></script>