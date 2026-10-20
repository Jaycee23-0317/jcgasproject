<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-people me-2"></i>Customers</h2>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading system branch layout networks...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <table id="customersTable" class="display">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const customersTable = new DataTable("#customersTable", {
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
                data: 'FullName'
            },
            {
                data: 'Email',
            },
            {
                data: 'ContactNumber'
            },
            {
                data: 'Address'
            },
            {
                data: 'CreatedAt',
                render: function(data) {
                    return data ? data.split(' ')[0] : '--'; // Formats timestamp to YYYY-MM-DD
                }
            }
        ]
    });

    document.addEventListener("DOMContentLoaded", () => {
        loadBranchData();
    });

    async function loadBranchData() {
        document.getElementById("loadingCard").style.display = "block";
        document.getElementById("sectionCard").style.display = "none";

        try {
            const response = await fetch('../api/user.php?getAllCustomerByAdmin');
            if (!response.ok) throw new Error(`HTTP Error Status Code: ${response.status}`);

            const payload = await response.json();

            console.log(payload)

            customersTable.clear().rows.add(payload);

        } catch (error) {
            showAlertBanner(error, 'danger');
        } finally {
            customersTable.draw();

            document.getElementById("loadingCard").style.display = "none";
            document.getElementById("sectionCard").style.display = "block";
        }
    }
</script>