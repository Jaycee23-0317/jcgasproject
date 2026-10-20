<?php

$branchesResult = $conn->query("SELECT BranchID, BranchName FROM branches ORDER BY BranchID");
$branches = [];
while ($row = $branchesResult->fetch_assoc()) {
    $branches[] = $row;
}

?>
<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-cart-check me-2"></i>Sales</h2>
    </div>

    <!-- DYNAMIC FILTERS TOOLBAR (Fully Structured with Element IDs) -->
    <div class="d-flex justify-content-end align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm flex-wrap">
        <div class="d-flex align-items-center gap-1">
            <select id="filterBranchInput" class="form-select form-select-sm text-dark border-0">
                <option value="0">All Branches</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?php echo $b['BranchID']; ?>">
                        <?php echo htmlspecialchars($b['BranchName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <select id="filterTypeInput" class="form-select form-select-sm text-dark border-0">
                <option value="">All Types</option>
                <option value="Online">Online</option>
                <option value="Walk-in">Walk-in</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <select id="filterStatusInput" class="form-select form-select-sm text-dark border-0">
                <option value="">All Status</option>
                <option value="Pending Review">Pending Review</option>
                <option value="Order Placed">Order Placed</option>
                <option value="Out For Delivery">Out For Delivery</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <label for="startDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">From:</label>
            <input type="date" id="startDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
        </div>
        <div class="d-flex align-items-center gap-1">
            <label for="endDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">To:</label>
            <input type="date" id="endDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
        </div>
        <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshSalesMetrics()"><i class="bi bi-filter-left me-1"></i>Filter</button>
    </div>

    <div class="main-section-body">

        <!-- Centered Loading Spinner Container -->
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Analyzing system transactional records...</p>
        </div>

        <!-- SECTION CONTENT CONTAINER (Initially Hidden) -->
        <div id="sectionCard" style="display: none;">
            <div class="row g-2 text-nowrap mb-3">
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-cash text-warning me-1"></i> Total Revenue</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="statTotalRevenue">₱0.00</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-receipt text-info me-1"></i> Total Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="statTotalOrders">0</h3>
                    </div>
                </div>
            </div>

            <div class="section-card p-3 border rounded shadow-sm">
                <div class="section-card-header d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 text-gas-cyan"><i class="bi bi-list me-2"></i>Order List</h3>
                </div>
                <div class="table-responsive">
                    <table id="salesTable" class="display table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Transaction ID #</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>TotalAmount</th>
                                <th>Branch</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated dynamically via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    let reviewMap = null;
    let reviewMapInitialized = false;
    let salesDataTableInstance;

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Set calendar fields to default to the current date parameter boundary string
        const currentTodayString = new Date().toISOString().split('T')[0];
        document.getElementById("startDateInput").value = currentTodayString;
        document.getElementById("endDateInput").value = currentTodayString;

        // 2. Pre-configure DataTables frame layout parameters
        salesDataTableInstance = new DataTable("#salesTable", {
            responsive: true,
            scrollCollapse: true,
            scroller: true,
            scrollY: 400,
            layout: {
                topStart: {
                    buttons: ["print"],
                },
            },
            order: [
                [7, 'desc']
            ], // Orders chronologically by default (Date column)
            columns: [{
                    data: 'OrderGroupID',
                },
                {
                    data: 'TransactionID'
                },
                {
                    data: 'OrderType'
                },
                {
                    data: 'Status',
                    render: function(data) {
                        let badgeColor = 'bg-secondary';
                        if (data === 'Pending') badgeColor = 'bg-dark border border-secondary text-white';
                        else if (data === 'Processing') badgeColor = 'bg-warning text-dark';
                        else if (data === 'In-Transit') badgeColor = 'bg-info text-dark';
                        else if (data === 'Completed') badgeColor = 'bg-success text-white';
                        else if (data === 'Cancelled') badgeColor = 'bg-danger text-white';
                        return `<span class="badge ${badgeColor}">${data}</span>`;
                    }
                },
                {
                    data: 'TotalAmount',
                },
                {
                    data: 'BranchName'
                },
                {
                    data: 'CustomerName'
                },
                {
                    data: 'OrderDate',
                    render: function(data) {
                        if (!data) return '';
                        const dateObj = new Date(data);
                        return dateObj.toLocaleDateString('en-PH', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            }) +
                            ' ' + dateObj.toLocaleTimeString('en-PH', {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return `
                        <button type="button" class="btn btn-sm btn-gas-outline-primary bi bi-eye" title="View Order Details"
                            onclick="viewOrderDetails('${meta.row}')"></button>
                    `;
                    }
                }
            ]
        });

        // 3. Trigger baseline initial application load telemetry
        refreshSalesMetrics();
    });

    function refreshSalesMetrics() {
        const loader = document.getElementById("loadingCard");
        const contentArea = document.getElementById("sectionCard");

        // Gather inputs parameters securely from dropdown nodes
        const branchId = document.getElementById("filterBranchInput").value;
        const orderType = document.getElementById("filterTypeInput").value;
        const status = document.getElementById("filterStatusInput").value;
        const startDate = document.getElementById("startDateInput").value;
        const endDate = document.getElementById("endDateInput").value;

        loader.style.display = "block";
        contentArea.style.display = "none";

        // 4. Dispatch variables via GET query parameters trace to backend API
        const apiUrl = `../php/sales_data.php?branch_id=${branchId}&order_type=${orderType}&status=${status}&start_date=${startDate}&end_date=${endDate}`;

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) throw new Error("Sales data loading engine trace failed.");
                return response.json();
            })
            .then(data => {
                // 5. Update summary statistics text counters instantly
                document.getElementById("statTotalOrders").textContent = data.totalOrders;
                document.getElementById("statTotalRevenue").textContent = `₱${Number(data.totalRevenue).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;

                // 6. Push datasets directly into our instance memory grid rows and draw
                if (salesDataTableInstance && Array.isArray(data.orders)) {
                    salesDataTableInstance.clear().rows.add(data.orders).draw();
                }

                setTimeout(() => {
                    loader.style.display = "none";
                    contentArea.style.display = "block";
                }, 200);
            })
            .catch(error => {
                console.error("Sales interface load exception trace:", error);
                loader.innerHTML = `<p class="text-danger p-4"><i class="bi bi-exclamation-octagon me-1"></i> Critical Exception: Failed to extract system transaction logs.</p>`;
            });
    }

    function viewOrderDetails(orderID) {
        const loaderArea = document.getElementById('modalLoaderArea');
        const contentArea = document.getElementById('modalContentArea');
        
        if (loaderArea) loaderArea.style.display = 'block';
        if (contentArea) contentArea.style.display = 'none';

        const myModalEl = document.getElementById('orderModalDetails');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(myModalEl);
        modalInstance.show();

        const orderData = salesDataTableInstance.row(orderID).data();
        const addressText = orderData.DeliveryAddress;
        const lat = orderData.DeliveryLatitude;
        const lng = orderData.DeliveryLongitude;

        console.log(orderData);
        if (orderData) {
            setTimeout(() => {
                document.getElementById('modalOrderGroupIDDisplay').innerText = orderData.OrderGroupID;
                document.getElementById('modalOrderIDDisplay').innerText = orderData.OrderID || 'N/A';
                document.getElementById('modalCustomerDisplay').innerText = orderData.CustomerName || 'Guest';
                document.getElementById('modalTransactionIDDisplay').innerText = orderData.TransactionID;
                document.getElementById('modalOrdertypeDisplay').innerText = orderData.OrderType;
                document.getElementById('modalStatusDisplay').innerText = orderData.Status;
                document.getElementById('modalPaymentMethodDisplay').innerText = orderData.PaymentMethod;

                const orderDate = new Date(orderData.OrderDate);
                document.getElementById('modalDateDisplay').innerText = orderDate.toLocaleDateString() + ' ' + orderDate.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('modalTotalDisplay').innerText = `$${parseFloat(orderData.TotalAmount).toFixed(2)}`;

                const itemsTableBody = document.getElementById('modalItemsTableBody');
                itemsTableBody.innerHTML = '';

                if (orderData.items && orderData.items.length > 0) {
                    orderData.items.forEach(item => {
                        const itemPrice = parseFloat(item.OrderedPrice || 0);
                        const itemQty = parseInt(item.Quantity || 0);
                        const itemSubtotal = parseFloat(item.Subtotal || (itemPrice * itemQty));

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td>
                            <div class="fw-bold">${item.ProductName}</div>
                            <span class="text-muted small">ID: #${item.ProductID}</span>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${item.Size || 'N/A'}</span></td>
                        <td class="text-end">$${itemPrice.toFixed(2)}</td>
                        <td class="text-center fw-bold">${itemQty}</td>
                        <td class="text-end fw-bold text-dark">$${itemSubtotal.toFixed(2)}</td>
                    `;
                        itemsTableBody.appendChild(tr);
                    });
                } else {
                    itemsTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No products recorded for this order transaction.</td></tr>`;
                }

                document.getElementById('orderDeliveryAddress').innerText = addressText;
                document.getElementById('orderDeliveryCoords').innerText = (lat && lng) ? `${lat}, ${lng}` : '--';

                renderDeliveryAddress(lat, lng);

                if (loaderArea) loaderArea.style.display = 'none';
                if (contentArea) contentArea.style.display = 'block'; // grid matching flex formatting rows
            }, 500); 
        } else {
            if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture order info.</p>';
        }
    }

    function renderDeliveryAddress(latValue, lngValue) {
        const mapViewElement = document.getElementById('deliveryMapAddress');
        const loadingSpinnerElement = document.getElementById('orderDeliveryMapLoading');

        if (!mapViewElement) {
            if (loadingSpinnerElement) loadingSpinnerElement.style.display = 'none';
            return;
        }

        if (reviewMap) {
            reviewMap.remove();
            reviewMap = null;
            reviewMapInitialized = false;
        }

        try {
            setTimeout(() => {
                try {
                    reviewMap = L.map('deliveryMapAddress').setView([latValue, lngValue], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(reviewMap);

                    L.marker([latValue, lngValue]).addTo(reviewMap);
                    reviewMapInitialized = true;
                    console.log(reviewMap);

                    if (loadingSpinnerElement) {
                        loadingSpinnerElement.style.display = 'none';
                    }

                    reviewMap.invalidateSize();

                } catch (asyncError) {
                    console.error("Delayed map initialization failure:", asyncError);
                    if (loadingSpinnerElement) {
                        loadingSpinnerElement.innerHTML = `<span class='text-danger small'><i class='bi bi-x-circle'></i> Map error.</span>`;
                    }
                }
            }, 200);

        } catch (mapError) {
            console.error("Map cleanup runtime failure:", mapError);
        }
    }
</script>