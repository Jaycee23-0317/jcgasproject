<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>

        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm">
            <div class="d-flex align-items-center gap-1">
                <label for="startDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">From:</label>
                <input type="date" id="startDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label for="endDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">To:</label>
                <input type="date" id="endDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshDashboardMetrics()"><i class="bi bi-filter-left me-1"></i>Filter</button>
        </div>
    </div>
    <div class="main-section-body">

        <div class="text-center py-5 section-card rounded" id="loadingCard">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Analyzing date range telemetry grids...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <div class="row row-cols-2 row-cols-lg-4 g-2 g-lg-3 text-nowrap">
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-box-seam text-primary me-1"></i> Total Stock</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="statTotalStock">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-bag-check text-success me-1"></i> Total Sold Item</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="statTotalSold">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-currency-dollar text-warning me-1"></i> Total Revenue</h6>
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

            <div class="section-card mt-4 p-3 border rounded shadow-sm">
                <div class="section-card-header d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 text-gas-cyan"><i class="bi bi-shop me-2"></i>Branch Performance Summary</h3>
                </div>
                <div class="table-responsive">
                    <table id="branchTable" class="display table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="text-center">Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables instance handles injecting dynamic rows directly -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    let branchDataTableInstance;

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Establish the current date string baseline to auto-populate range inputs
        const todayFormatted = new Date().toISOString().split('T')[0];
        document.getElementById("startDateInput").value = todayFormatted;
        document.getElementById("endDateInput").value = todayFormatted;

        // 2. Pre-initialize the single main Branch Performance DataTable cleanly
        branchDataTableInstance = new DataTable("#branchTable", {
            responsive: true,
            scrollCollapse: true,
            scroller: true,
            scrollY: 400,
            layout: {
                topStart: {
                    buttons: ["print"],
                },
            },
            columns: [{
                    data: 'BranchName',
                    render: function(data) {
                        return `<i class="bi bi-shop text-primary me-2"></i>${data}`;
                    }
                },
                {
                    data: 'OrdersCount',
                    className: 'text-center fw-semibold'
                },
                {
                    data: 'TotalRevenue',
                    className: 'fw-bold text-success',
                    render: function(data) {
                        return `₱ ${Number(data).toLocaleString("en-PH", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    })}`;
                    }
                }
            ]
        });

        // 3. Fire the initial timeline baseline dashboard loading configuration trace
        refreshDashboardMetrics();
    });

    function refreshDashboardMetrics() {
        const loader = document.getElementById("loadingCard");
        const dashboardArea = document.getElementById("sectionCard");

        // Capture values from date components
        const startVal = document.getElementById("startDateInput").value;
        const endVal = document.getElementById("endDateInput").value;

        console.log(startVal);
        console.log(endVal);

        // Toggle loader visibility states cleanly
        loader.style.display = "block";
        dashboardArea.style.display = "none";

        // 4. Dispatch the range query parameters via a clean GET fetch request
        fetch(`../php/dashboard_data.php?start_date=${startVal}&end_date=${endVal}`)
            .then(response => {
                if (!response.ok) throw new Error("Metrics delivery tracking telemetry crashed.");
                return response.json();
            })
            .then(data => {
                // 5. Update summary statistics text nodes in memory instantly
                document.getElementById("statTotalStock").textContent = data.totalStock;
                document.getElementById("statTotalSold").textContent = data.totalSold;
                document.getElementById("statTotalOrders").textContent = data.totalOrders;

                document.getElementById("statTotalRevenue").textContent = `₱${Number(data.totalRevenue).toLocaleString("en-PH", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;

                // 6. Clear and populate rows in the branch performance summary table layout matrix
                if (branchDataTableInstance && Array.isArray(data.branches)) {
                    branchDataTableInstance.clear().rows.add(data.branches).draw();
                }

                // Smoothly complete loading screen visibility transitions
                setTimeout(() => {
                    loader.style.display = "none";
                    dashboardArea.style.display = "block";
                }, 250);
            })
            .catch(error => {
                console.error("Dashboard engine failure details:", error);
                loader.innerHTML = `<p class="text-danger p-4"><i class="bi bi-exclamation-octagon me-1"></i> Failed to retrieve metrics. Verify connections and parameter tracks.</p>`;
            });
    }
</script>