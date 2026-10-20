<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-graph-up me-2"></i>Reports</h2>

        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm">
            <div class="d-flex align-items-center gap-1">
                <label for="startDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">From:</label>
                <input type="date" id="startDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label for="endDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">To:</label>
                <input type="date" id="endDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshReportMetrics()"><i class="bi bi-filter-left me-1"></i>Filter</button>
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
            <div class="row g-2 text-nowrap">
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-cart-check text-primary me-1"></i> Online orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="onlineOrdersCount">---</h3>
                        <p class="text-success m-0" id="onlineOrdersRevenue">---</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-cart-check text-primary me-1"></i> Walk-In orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="walkInOrdersCount">---</h3>
                        <p class="text-success m-0" id="walkInOrdersRevenue">---</p>
                    </div>
                </div>
            </div>

            <div class="section-card mt-4 p-3 border rounded shadow-sm">
                <canvas id="salesChart" height="100"></canvas>
            </div>
            <div class="section-card mt-4 p-3 border rounded shadow-sm">
                <div class="section-card-header d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 text-gas-cyan"><i class="bi bi-trophy me-2"></i>Top Selling Products</h3>
                </div>
                <div class="table-responsive">
                    <table id="topSellingTable" class="display table table-striped w-100">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Total Sold</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables instance handles injecting dynamic rows directly -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="section-card mt-4 p-3 border rounded shadow-sm">
                <div class="section-card-header d-flex justify-content-between align-items-center mb-3">
                    <h3 class="m-0 text-gas-cyan"><i class="bi bi-shop me-2"></i>Branch Performance Summary</h3>
                </div>
                <div class="table-responsive">
                    <table id="reportTable" class="display table table-striped w-100">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let topSellingTableInstance;
    let reportTableInstance;
    let salesChartInstance = null; // Track chart instance globally to allow structural destruction loops

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Establish calendar fields to default to the current date parameter boundary string
        const currentTodayString = new Date().toISOString().split('T')[0];
        document.getElementById("startDateInput").value = currentTodayString;
        document.getElementById("endDateInput").value = currentTodayString;

        // 2. Pre-configure Top Selling Products DataTable Instance Mapping Definitions
        topSellingTableInstance = new DataTable("#topSellingTable", {
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
                    data: 'ProductName',
                    className: 'fw-semibold'
                },
                {
                    data: 'Size'
                },
                {
                    data: 'TotalSold',
                    className: 'text-center fw-bold'
                },
                {
                    data: 'TotalRevenue',
                    className: 'text-success fw-bold',
                    render: function(data) {
                        return `₱${Number(data).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }
                }
            ]
        });

        // 3. Pre-configure Branch Performance Summary DataTable Instance Mapping Definitions
        reportTableInstance = new DataTable("#reportTable", {
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
                        return `₱${Number(data).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    }
                }
            ]
        });

        // 4. Trigger initial dataset loader metrics processing trace
        refreshReportMetrics();
    });

    function refreshReportMetrics() {
        const loader = document.getElementById("loadingCard");
        const contentArea = document.getElementById("sectionCard");

        const startDate = document.getElementById("startDateInput").value;
        const endDate = document.getElementById("endDateInput").value;

        loader.style.display = "block";
        contentArea.style.display = "none";

        // 5. Dispatch variables via GET query parameters trace to backend report API
        fetch(`../php/report_data.php?start_date=${startDate}&end_date=${endDate}`)
            .then(response => {
                if (!response.ok) throw new Error("Report data trace telemetry extraction failed.");
                return response.json();
            })
            .then(data => {
                // 6. Update online/walk-in metric summary stat blocks
                document.getElementById("onlineOrdersCount").textContent = `${data.summary.online_count} orders`;
                document.getElementById("onlineOrdersRevenue").textContent = `₱${Number(data.summary.online_revenue).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                document.getElementById("walkInOrdersCount").textContent = `${data.summary.walkin_count} orders`;
                document.getElementById("walkInOrdersRevenue").textContent = `₱${Number(data.summary.walkin_revenue).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                // 7. Inject datasets arrays into DataTables tracking configurations
                if (topSellingTableInstance && Array.isArray(data.top_products)) {
                    topSellingTableInstance.clear().rows.add(data.top_products).draw();
                }
                if (reportTableInstance && Array.isArray(data.branch_performance)) {
                    reportTableInstance.clear().rows.add(data.branch_performance).draw();
                }

                // 8. UNBREAKABLE CHART UPDATE ROUTINE: Destroy active layouts before rewriting new parameters
                if (salesChartInstance !== null) {
                    salesChartInstance.destroy();
                }

                const ctx = document.getElementById('salesChart').getContext('2d');
                salesChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.chart.dates,
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: data.chart.revenues,
                            borderColor: '#FF6600',
                            backgroundColor: 'rgba(255,102,0,0.1)',
                            fill: true,
                            tension: 0.3
                        }, {
                            label: 'Orders',
                            data: data.chart.orderCounts,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40,167,69,0.1)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                position: 'left'
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });

                // Toggle animation layouts smoothly
                setTimeout(() => {
                    loader.style.display = "none";
                    contentArea.style.display = "block";
                }, 200);
            })
            .catch(error => {
                console.error("Report loader component breakdown exception tracing:", error);
                loader.innerHTML = `<p class="text-danger p-4"><i class="bi bi-exclamation-octagon me-1"></i> Failed to aggregate database report sets.</p>`;
            });
    }
</script>