<div class="main-content flex-grow-1 px-3">
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
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshDashboard()"><i class="bi bi-filter-left me-1"></i>Filter</button>
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
            <div class="row row-cols-2 row-cols-lg-4 g-2 g-lg-3">
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
                        <h6 class="text-muted"><i class="bi bi-receipt text-primary me-1"></i>Total Orders</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalOrders">0</h3>
                    </div>
                </div>
                <div class="col">
                    <div class="card p-3 shadow-sm rounded border">
                        <h6 class="text-muted"><i class="bi bi-boxes text-primary me-1"></i>Total Stocks</h6>
                        <h3 class="fw-bold text-gas-cyan m-0" id="totalStocks">0</h3>
                    </div>
                </div>
            </div>

            <div class="section-card mt-3 p-3 border rounded shadow-sm" style="position: relative; height: 350px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Global scope variables to preserve canvas tracking elements safely
    let salesChartInstance = null;

    document.addEventListener("DOMContentLoaded", () => {
        // 1. Set default dates automatically on page load (First day of month to Today)
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        // Format helper function: YYYY-MM-DD
        const formatDate = (date) => date.toISOString().split('T')[0];
        
        document.getElementById("startDateInput").value = formatDate(firstDay);
        document.getElementById("endDateInput").value = formatDate(today);

        // 2. Perform the initial data load instantly
        refreshDashboard();
    });

    async function refreshDashboard() {
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
            const response = await fetch(`../php/get_cashier_dashboard_data.php?startDate=${startDate}&endDate=${endDate}`);

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
                
            document.getElementById("totalOrders").textContent = 
                Number(data.metrics.totalOrders || 0).toLocaleString('en-US');
                
            document.getElementById("totalStocks").textContent = 
                Number(data.metrics.totalStocks || 0).toLocaleString('en-US') + ' units';

            // Step D: Render or Update the Combo Chart Canvas layout 
            renderSalesChart(data);

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

    function renderSalesChart(data) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        // Completely wipes out previous layouts to avoid ghost overlapping data hover bugs
        if (salesChartInstance) {
            salesChartInstance.destroy();
        }

        // Guard clause if no chart historical dates exist in response payload range
        if (!data.chart || !data.chart.dates || data.chart.dates.length === 0) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            return;
        }

        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 300);
        revenueGradient.addColorStop(0, 'rgba(0, 210, 252, 0.4)');  
        revenueGradient.addColorStop(1, 'rgba(0, 210, 252, 0.02)'); 

        salesChartInstance = new Chart(ctx, {
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
</script>
