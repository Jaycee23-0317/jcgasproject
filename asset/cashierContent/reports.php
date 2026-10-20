<div class="main-content flex-grow-1 px-3">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-graph-up me-2"></i>Reports</h2>

        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm">
            <button type="button" class="btn btn-sm btn-dark px-3 me-2" onclick="printReport()">
                <i class="bi bi-printer me-1"></i>
                Print
            </button>

            <div class="d-flex align-items-center gap-1">
                <label for="startDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">From:</label>
                <input type="date" id="startDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label for="endDateInput" class="small text-muted m-0 text-uppercase fw-semibold" style="font-size: 0.75rem;">To:</label>
                <input type="date" id="endDateInput" class="form-control form-control-sm text-dark border-0 text-center" style="width: 140px;">
            </div>
            
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshReportsData()"><i class="bi bi-filter-left me-1"></i>Filter</button>
        </div>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading please wait...</p>
        </div>

        <div id="sectionCard" style="display: block;">
            <div class="print-report-header">
                <h1>JC Gas Sales Report</h1>

                <p class="mb-1">
                    <strong>Report Period:</strong>
                    <span id="printDateRange">-</span>
                </p>

                <p class="mb-0">
                    <strong>Generated:</strong>
                    <span id="printGeneratedDate">-</span>
                </p>
            </div>

            <div class="row row-cols-2 row-cols-lg-4 g-2 g-lg-3">
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-cash-coin text-primary me-1"></i>Total Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalOrders">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-boxes text-primary me-1"></i>Total Completed Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalCompletedOrders">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-boxes text-primary me-1"></i>Total Pending Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalPendingOrders">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-boxes text-primary me-1"></i>Total Cancelled Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalCancelledOrders">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-boxes text-primary me-1"></i>Total Stocks</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalStocks">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-cash-coin text-primary me-1"></i>Total Cash Sales</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalCashSales">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-credit-card text-primary me-1"></i>Total Other Sales</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalOtherSales">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-credit-card text-primary me-1"></i>Total Completed Sold Items</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalSoldItems">0</h3>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="section-card mb-3 p-3 border rounded shadow-sm">
                        <h6 class="mb-2 fw-bold">Total Orders</h6>

                        <div class="report-pie-chart-container">
                            <canvas id="reportsOrdersStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="section-card mb-3 p-3 border rounded shadow-sm">
                        <h6 class="mb-2 fw-bold">Total Sales</h6>

                        <div class="report-pie-chart-container">
                            <canvas id="reportsTotalSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card mb-3 p-3 border rounded shadow-sm" style="position: relative; height: 350px;">
                <h6 class="mb-2 fw-bold">Total Orders Revenue</h6>
                <div style="position: relative; height: 305px;">
                    <canvas id="reportsChart"></canvas>
                </div>
            </div>

            <div class="section-card mb-3 p-3 border rounded shadow-sm" style="position: relative; height: 350px;">
                <h6 class="mb-2 fw-bold">Total Sold Items</h6>
                <div style="position: relative; height: 305px;">
                    <canvas id="reportsSoldItemChart"></canvas>
                </div>
            </div>

            <div class="section-card mb-3 p-3 border rounded shadow-sm" style="position: relative; height: 350px;">
                <h6 class="mb-2 fw-bold">Total Sold Items Per Name</h6>
                <div style="position: relative; height: 305px;">
                    <canvas id="reportsSoldItemsPerNameChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let reportsChartInstance = null;
    let reportsOrdersStatusChartInstance = null;
    let reportsTotalSalesChartInstance = null;
    let reportsSoldItemChartInstance = null;
    let reportsSoldItemsPerNameChartInstance = null;

    let isMobile = window.innerWidth <= 576;

    window.addEventListener('resize', function () {
        const newIsMobile = window.innerWidth <= 576;

        if (newIsMobile !== isMobile) {
            isMobile = newIsMobile;

            refreshReportsData();
        }
    });

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Set default dates automatically on page load (First day of month to Today)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        // Format helper function: YYYY-MM-DD
        const formatDate = (date) => date.toISOString().split('T')[0];
        
        document.getElementById("startDateInput").value = formatDate(firstDay);
        document.getElementById("endDateInput").value = formatDate(today);


        // 2. Perform the initial data load instantly
        refreshReportsData();
    });

    async function refreshReportsData() {
        const loadingCard = document.getElementById("loadingCard");
        const sectionCard = document.getElementById("sectionCard");
        const startDate = document.getElementById("startDateInput").value;
        const endDate = document.getElementById("endDateInput").value;

        // Safety structural validation
        if (!loadingCard || !sectionCard) return;

        // Step A: Trigger Loading States
        loadingCard.style.display = "block";
        sectionCard.style.display = "none";

        try {
            // Step B: AJAX Fetch Query payload submission using URL query arguments
            const response = await fetch(`../php/get_cashier_report_data.php?startDate=${startDate}&endDate=${endDate}`);

            if (!response.ok) {
                throw new Error(`HTTP network error! Status Code: ${response.status}`);
            }

            const data = await response.json();
            console.log("Dashboard Payload Data:", data);

            if (data.error) {
                throw new Error(data.error);
            }

            // Step C: Map payload elements cleanly into individual metric card counters
            // Using fallback || 0 configurations to ensure zero states look professional
            document.getElementById("totalCashSales").textContent = 
                '₱' + Number(data.metrics.totalCashSales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                
            document.getElementById("totalOtherSales").textContent = 
                '₱' + Number(data.metrics.totalOtherSales || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                
            document.getElementById("totalCompletedOrders").textContent = 
                Number(data.metrics.totalCompletedOrders || 0).toLocaleString('en-US');

            document.getElementById("totalSoldItems").textContent = 
                Number(data.metrics.totalSoldItems || 0).toLocaleString('en-US');

            document.getElementById("totalPendingOrders").textContent = 
                Number(data.metrics.totalPendingOrders || 0).toLocaleString('en-US');
                
            document.getElementById("totalCancelledOrders").textContent = 
                Number(data.metrics.totalCancelledOrders || 0).toLocaleString('en-US');

            document.getElementById("totalOrders").textContent = 
                Number(data.metrics.totalOrders || 0).toLocaleString('en-US');
                
            document.getElementById("totalStocks").textContent = 
                Number(data.metrics.totalStocks || 0).toLocaleString('en-US') + ' units';

            // Step D: Render or Update the Combo Chart Canvas layout 
            renderReportChart(data);
            renderSoldItemChart(data);
            renderOrdersStatusChart(data);
            renderTotalSalesChart(data);
            renderSoldItemsPerNameChart(data);

        } catch (error) {
            console.error("Dashboard calculation sequence tracking processing error:", error);
            if (typeof showAlertBanner === "function") {
                showAlertBanner(`Failed loading data: ${error.message}`, 'danger');
            } else {
                alert(`Error: ${error.message}`);
            }
        } finally {
            // Step E: Gracefully swap visibility layouts regardless of process conclusions
            loadingCard.style.display = "none";
            sectionCard.style.display = "block";
        }
    }

    function renderReportChart(data) {
        const ctx = document.getElementById('reportsChart').getContext('2d');
        
        // Completely wipes out previous layouts to avoid ghost overlapping data hover bugs
        if (reportsChartInstance) {
            reportsChartInstance.destroy();
        }

        // Guard clause if no chart historical dates exist in response payload range
        if (!data.chart || !data.chart.dates || data.chart.dates.length === 0) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            return;
        }

        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 300);
        revenueGradient.addColorStop(0, 'rgba(0, 210, 252, 0.4)');  
        revenueGradient.addColorStop(1, 'rgba(0, 210, 252, 0.02)'); 

        reportsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.chart.dates,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Revenue (₱)',
                        data: data.chart.revenues,
                        backgroundColor: revenueGradient,
                        borderColor: '#00d2fc', 
                        borderWidth: 2,
                        borderRadius: 4,        
                        yAxisID: 'y'
                    }, 
                    {
                        type: 'line',
                        label: 'Orders Count',
                        data: data.chart.orderCounts,
                        borderColor: '#ff6600',       
                        backgroundColor: '#ff6600',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ff6600',
                        pointHoverRadius: 6,
                        tension: 0.2,                 
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 10, usePointStyle: true }
                    },
                    tooltip: {
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.datasetIndex === 0) {
                                    label += '₱' + Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                } else {
                                    label += Number(context.raw).toLocaleString('en-US') + ' orders';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + (value >= 1000 ? (value / 1000) + 'k' : value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    function renderSoldItemChart(data) {
        const ctx = document
            .getElementById('reportsSoldItemChart')
            .getContext('2d');

        if (reportsSoldItemChartInstance) {
            reportsSoldItemChartInstance.destroy();
        }

        if (
            !data.soldItemChart ||
            !data.soldItemChart.dates ||
            data.soldItemChart.dates.length === 0
        ) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            return;
        }

        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 300);

        revenueGradient.addColorStop(0,'rgba(0, 210, 252, 0.4)');
        revenueGradient.addColorStop(1,'rgba(0, 210, 252, 0.02)');

        reportsSoldItemChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.soldItemChart.dates,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Revenue (₱)',
                        data: data.soldItemChart.revenues,
                        backgroundColor: revenueGradient,
                        borderColor: '#00d2fc',
                        borderWidth: 2,
                        borderRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Sold Items',
                        data: data.soldItemChart.soldItems,
                        borderColor: '#ff6600',
                        backgroundColor: '#ff6600',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#ff6600',
                        pointHoverRadius: 6,
                        tension: 0.2,
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }

                                if (context.datasetIndex === 0)
                                {
                                    label +=
                                        '₱' +
                                        Number(
                                            context.raw
                                        ).toLocaleString(
                                            'en-US',
                                            {
                                                minimumFractionDigits: 2
                                            }
                                        );
                                } else {
                                    label +=
                                        Number(
                                            context.raw
                                        ).toLocaleString(
                                            'en-US'
                                        ) +
                                        ' items';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' +
                                    (
                                        value >= 1000
                                            ? (value / 1000) + 'k'
                                            : value
                                    );
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + ' items';
                            }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }

    function renderOrdersStatusChart(data) {
        const canvas = document.getElementById(
            'reportsOrdersStatusChart'
        );

        if (!canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');

        if (reportsOrdersStatusChartInstance) {
            reportsOrdersStatusChartInstance.destroy();
        }

        if (
            !data.ordersStatusChart ||
            !data.ordersStatusChart.labels ||
            data.ordersStatusChart.labels.length === 0
        ) {
            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );
            return;
        }

        console.log(isMobile);

        reportsOrdersStatusChartInstance = new Chart(ctx, {
            type: 'pie',

            data: {
                labels: data.ordersStatusChart.labels,

                datasets: [
                    {
                        data: data.ordersStatusChart.counts,

                        backgroundColor: [
                            '#ffc107',
                            '#0d6efd',
                            '#fd7e14',
                            '#198754',
                            '#dc3545',
                            '#6f42c1'
                        ],

                        borderColor: '#ffffff',
                        borderWidth: 2
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                layout: {
                    padding: 5
                },

                plugins: {
                    legend: {
                        position: isMobile
                            ? 'bottom'
                            : 'right',

                        align: 'center',

                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,

                            padding: isMobile ? 8 : 10,

                            font: {
                                size: isMobile ? 10 : 11
                            },

                            generateLabels: function(chart) {
                                const chartData =
                                    chart.data;

                                return chartData.labels.map(
                                    function(label, index) {
                                        const value =
                                            Number(
                                                chartData
                                                    .datasets[0]
                                                    .data[index]
                                            );

                                        return {
                                            text:
                                                label +
                                                ' — ' +
                                                value.toLocaleString(
                                                    'en-US'
                                                ) +
                                                ' orders',

                                            fillStyle:
                                                chartData
                                                    .datasets[0]
                                                    .backgroundColor[
                                                    index
                                                ],

                                            strokeStyle:
                                                chartData
                                                    .datasets[0]
                                                    .borderColor,

                                            lineWidth:
                                                chartData
                                                    .datasets[0]
                                                    .borderWidth,

                                            hidden: false,

                                            index: index
                                        };
                                    }
                                );
                            }
                        }
                    },

                    tooltip: {
                        padding: 10,

                        backgroundColor:
                            'rgba(15, 23, 42, 0.95)',

                        callbacks: {
                            label: function(context) {
                                const label =
                                    context.label || '';

                                const value =
                                    Number(context.raw)
                                        .toLocaleString(
                                            'en-US'
                                        );

                                return (
                                    label +
                                    ': ' +
                                    value +
                                    ' orders'
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    function renderTotalSalesChart(data) {
        const canvas = document.getElementById(
            'reportsTotalSalesChart'
        );

        if (!canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');

        if (reportsTotalSalesChartInstance) {
            reportsTotalSalesChartInstance.destroy();
        }

        if (
            !data.totalSalesChart ||
            !data.totalSalesChart.labels ||
            data.totalSalesChart.labels.length === 0
        ) {
            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );
            return;
        }

        reportsTotalSalesChartInstance = new Chart(ctx, {
            type: 'pie',

            data: {
                labels: data.totalSalesChart.labels,

                datasets: [
                    {
                        data: data.totalSalesChart.sales,

                        backgroundColor: [
                            '#00d2fc',
                            '#ff6600',
                            '#198754',
                            '#ffc107',
                            '#6f42c1',
                            '#dc3545'
                        ],

                        borderColor: '#ffffff',
                        borderWidth: 2
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                layout: {
                    padding: 5
                },

                plugins: {
                    legend: {
                        position: isMobile
                            ? 'bottom'
                            : 'right',

                        align: 'center',

                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,

                            padding: isMobile ? 8 : 10,

                            font: {
                                size: isMobile ? 10 : 11
                            },

                            generateLabels: function(chart) {
                                const chartData =
                                    chart.data;

                                return chartData.labels.map(
                                    function(label, index) {
                                        const value =
                                            Number(
                                                chartData
                                                    .datasets[0]
                                                    .data[index]
                                            );

                                        return {
                                            text:
                                                label +
                                                ' — ₱' +
                                                value.toLocaleString(
                                                    'en-US',
                                                    {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2
                                                    }
                                                ),

                                            fillStyle:
                                                chartData
                                                    .datasets[0]
                                                    .backgroundColor[
                                                    index
                                                ],

                                            strokeStyle:
                                                chartData
                                                    .datasets[0]
                                                    .borderColor,

                                            lineWidth:
                                                chartData
                                                    .datasets[0]
                                                    .borderWidth,

                                            hidden: false,

                                            index: index
                                        };
                                    }
                                );
                            }
                        }
                    },

                    tooltip: {
                        padding: 10,

                        backgroundColor:
                            'rgba(15, 23, 42, 0.95)',

                        callbacks: {
                            label: function(context) {
                                const label =
                                    context.label || '';

                                const value =
                                    Number(context.raw)
                                        .toLocaleString(
                                            'en-US',
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        );

                                return (
                                    label +
                                    ': ₱' +
                                    value
                                );
                            }
                        }
                    }
                }
            }
        });
    }

    function renderSoldItemsPerNameChart(data) {
        const canvas = document.getElementById('reportsSoldItemsPerNameChart');

        if (!canvas) { return; }

        const ctx = canvas.getContext('2d');

        if (reportsSoldItemsPerNameChartInstance) {
            reportsSoldItemsPerNameChartInstance.destroy();
        }

        if (
            !data.soldItemsPerNameChart ||
            !data.soldItemsPerNameChart.labels ||
            data.soldItemsPerNameChart.labels.length === 0
        ) {
            ctx.clearRect(
                0,
                0,
                canvas.width,
                canvas.height
            );

            return;
        }

        const labels = data.soldItemsPerNameChart.labels;

        const rawDatasets = data.soldItemsPerNameChart.datasets;

        const statusColors = {
            'Completed': '#198754',
            'Order Placed': '#0d6efd',
            'Out for Delivery': '#fd7e14',
            'Pending Review': '#ffc107',
            'Cancelled': '#dc3545',
            'Voided': '#6c757d'
        };

        const chartDatasets =
            rawDatasets.map(function(dataset) {
                const color =
                    statusColors[dataset.status] ||
                    '#6f42c1';

                return {
                    label: dataset.status,
                    data: dataset.values,
                    backgroundColor: color,
                    borderColor: color,
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 45
                };
            });

        reportsSoldItemsPerNameChartInstance =
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: chartDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: isMobile
                                ? 'bottom'
                                : 'top',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                padding: 10,
                                font: {
                                    size: isMobile
                                        ? 10
                                        : 11
                                }
                            }
                        },
                        tooltip: {
                            padding: 10,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            callbacks: {
                                label: function(context) {
                                    const value =
                                        Number(
                                            context.raw
                                        ).toLocaleString(
                                            'en-US'
                                        );

                                    return (
                                        context.dataset.label +
                                        ': ' +
                                        value +
                                        ' items'
                                    );
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                callback: function(value) {
                                    return Number(value)
                                        .toLocaleString(
                                            'en-US'
                                        );
                                }
                            },
                            title: {
                                display: true,
                                text: 'Items Sold'
                            }
                        }
                    }
                }
            });
    }

    function printReport() {
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        const printDateRange = document.getElementById('printDateRange');
        const printGeneratedDate = document.getElementById('printGeneratedDate');

        if (startDate && endDate) {
            printDateRange.textContent =
                formatPrintDate(startDate) +
                ' to ' +
                formatPrintDate(endDate);
        } else {
            printDateRange.textContent = 'All Dates';
        }

        const now = new Date();

        printGeneratedDate.textContent = now.toLocaleString('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        window.print();
    }

    function formatPrintDate(dateString) {
        if (!dateString) {
            return '-';
        }

        const date = new Date(dateString + 'T00:00:00');

        return date.toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

</script>