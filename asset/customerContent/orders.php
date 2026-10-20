<?php
require_once '../api/activity_logs.php';
?>

<div class="main-content flex-grow-1 px-3">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Orders</h2>

        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm">
            <div class="d-flex align-items-center gap-1">
                <label for="startDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">From:</label>
                <input type="date" id="startDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label for="endDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">To:</label>
                <input type="date" id="endDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="loadOrdersData()"><i class="bi bi-filter-left me-1"></i>Filter</button>
        </div>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading system branch layout networks...</p>
        </div>

        <div id="sectionCard" style="display: block;">
            <div class="section-card">
                <table id="ordersTable" class="display">
                    <thead>
                        <tr>
                            <th>Order ID #</th>
                            <th>Transaction #</th>
                            <th>Total</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Order Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="../js/receipt.js"></script>
<script src="../js/order.js"></script>

<script>
    const ordersTable = new DataTable("#ordersTable", {
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
                data: 'OrderGroupID'
            },
            {
                data: 'TransactionID',
            },
            {
                data: 'TotalAmount'
            },
            {
                data: 'OrderType'
            },
            {
                data: 'Status'
            },
            {
                data: 'OrderDate',
                render: function(data) {
                    if (!data) return 'N/A';
                    const date = new Date(data);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row, meta) {
                    let actionButtons = `
                        <button class="btn btn-outline-secondary me-1" onclick="printReceiptBtn(${meta.row})" title="Print Receipt">
                            <i class="bi bi-receipt"></i>
                        </button>
                        <button class="btn btn-outline-success me-1" onclick="viewOrderDetails(${meta.row})" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    `;
                    const currentStatus = row.Status ? row.Status.trim() : '';

                    if (currentStatus === 'Pending Review') {
                        actionButtons += `
                            <button class="btn btn-outline-danger" onclick="updateOrderStatus(${row.OrderGroupID}, 'Cancelled', '${currentStatus}')" title="Canceled Order">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        `;
                    }
                    return `<div class="btn-group btn-group-sm">${actionButtons}</div>`;
                }
            }
        ]
    });

    document.addEventListener("DOMContentLoaded", () => {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        const formatDate = (date) => date.toISOString().split('T')[0];
        
        document.getElementById("startDateInput").value = formatDate(today);
        document.getElementById("endDateInput").value = formatDate(today);

        loadOrdersData();
    });

    async function loadOrdersData() {
        document.getElementById("loadingCard").style.display = "block";
        document.getElementById("sectionCard").style.display = "none";

        const userID = <?php echo $_SESSION['user_id']; ?>;
        const startDate = document.getElementById("startDateInput").value;
        const endDate = document.getElementById("endDateInput").value;

        try {
            const response = await fetch(`../api/order.php?userID=${userID}&startDate=${startDate}&endDate=${endDate}`);
            if (!response.ok) throw new Error(`HTTP Error Status Code: ${response.status}`);

            const payload = await response.json();

            console.log(payload)

            ordersTable.clear().rows.add(payload);

        } catch (error) {
            showAlertBanner(error, 'danger');
        } finally {
            ordersTable.draw();

            document.getElementById("loadingCard").style.display = "none";
            document.getElementById("sectionCard").style.display = "block";
        }
    }
</script>