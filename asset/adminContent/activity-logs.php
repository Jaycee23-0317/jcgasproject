<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center flex-wrap p-3">
        <h2 class="m-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Activity Logs</h2>
    </div>

    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard" style="display: block;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading system please wait...</p>
        </div>

        <div id="sectionCard" style="display: none;">
            <table id="activityLogsTable" class="display">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Action</th>
                        <th>Entity Type</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="activityLogsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityLogsModalLabel"><i class="bi bi-receipt me-2"></i>Activity Logs</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5 modalCard rounded" id="activityLogsModalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading activity logs...</p>
                </div>
                <div class="container rounded-4 text-center" id="activityLogsModalContentArea" style="display: none;">
                    <div class="row row-cols-2 row-cols-lg-3 g-2 g-lg-3">
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Log ID</h5>
                                <h4 class="text-gas-cyan m-0" id="logID"></h4>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Action</h5>
                                <h4 class="text-gas-cyan m-0" id="action"></h4>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Entity Type</h5>
                                <h4 class="text-gas-cyan m-0" id="entityType"></h4>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Branch Name</h5>
                                <h4 class="text-gas-cyan m-0" id="branchName"></h4>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Created By</h5>
                                <h4 class="text-gas-cyan m-0" id="createdBy"></h4>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="card p-3 border-rounded w-100 h-100">
                                <h5 class="fw=bold text-muted">Created At</h5>
                                <h4 class="text-gas-cyan m-0" id="createdAt"></h4>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <h5 class="fw=bold text-muted">Description</h5>
                        <p class="m-0" id="description"></p>
                    </div>
                    <div class="row mt-2 g-2">
                        <div class="col-6 d-flex">
                            <div class="card w-100 h-100">
                                <h5 class="fw-bold text-muted">Old Data</h5>
                                <div id="oldDataContent"></div>
                            </div>
                        </div>

                        <div class="col-6 d-flex">
                            <div class="card w-100 h-100">
                                <h5 class="fw-bold text-muted">New Data</h5>
                                <div id="newDataContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<Script>
    const activityLogsTable = new DataTable("#activityLogsTable", {
        responsive: true,
        scrollCollapse: true,
        scroller: true,
        scrollY: 350,
        layout: {
            topStart: {
                pageLength: {
                    menu: [10, 25, 50],
                },
                buttons: ["print"]
            }
        },
        columns: [{
                data: 'LogID'
            },
            {
                data: 'Action',
            },
            {
                data: 'EntityType'
            },
            {
                data: 'Description'
            },
            {
                data: 'CreatedBy'
            },
            {
                data: 'CreatedAt',
                render: function(data) {
                    return data ? data.split(' ')[0] : '--'; // Formats timestamp to YYYY-MM-DD
                }
            },
            {
                data: null,
                render: function(data, type, row, meta) {
                    return `<button class="btn btn-sm btn-outline-warning" onclick="viewActivityLogs(${meta.row})"><i class="bi bi-eye"></i></button>`;
                }
            }
        ]
    });

    document.addEventListener("DOMContentLoaded", () => {
        loadActivityLogsData();
    });

    async function loadActivityLogsData() {
        document.getElementById("loadingCard").style.display = "block";
        document.getElementById("sectionCard").style.display = "none";

        try {
            const response = await fetch('../api/activity_logs.php');
            if (!response.ok) throw new Error(`HTTP Error Status Code: ${response.status}`);

            const payload = await response.json();

            console.log(payload)

            activityLogsTable.clear().rows.add(payload.data);

        } catch (error) {
            showAlertBanner(error, 'danger');
        } finally {
            activityLogsTable.draw();

            document.getElementById("loadingCard").style.display = "none";
            document.getElementById("sectionCard").style.display = "block";
        }
    }

    function viewActivityLogs(rowID) {
        const loaderArea = document.getElementById('activityLogsModalLoaderArea');
        const contentArea = document.getElementById('activityLogsModalContentArea');

        if (loaderArea) loaderArea.style.display = 'block';
        if (contentArea) contentArea.style.display = 'none';

        const activityLogsData = activityLogsTable.row(rowID).data();

        const myModalEl = document.getElementById('activityLogsModal');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(myModalEl);
        modalInstance.show();

        if (activityLogsData) {
            setTimeout(() => {
                document.getElementById("logID").innerText = activityLogsData.LogID;
                document.getElementById("action").innerText = activityLogsData.Action;
                document.getElementById("entityType").innerText = activityLogsData.EntityType;
                document.getElementById("branchName").innerText = activityLogsData.BranchName;
                document.getElementById("createdBy").innerText = activityLogsData.CreatedBy;
                document.getElementById("createdAt").innerText = activityLogsData.CreatedAt;
                document.getElementById("description").innerText = activityLogsData.Description;

                if (!activityLogsData.OldData) {
                    document.getElementById("oldDataContent").innerHTML = '<p class="m-0">No Data</p>';
                } else {
                    let contentArea = document.getElementById('oldDataContent');
                    contentArea.innerHTML = '';

                    let oldData = JSON.parse(activityLogsData.OldData);

                    displayData(contentArea, oldData);
                }

                if (!activityLogsData.NewData) {
                    document.getElementById("newDataContent").innerHTML = '<p class="m-0">No Data</p>';
                } else {
                    let contentArea = document.getElementById('newDataContent');
                    contentArea.innerHTML = '';

                    let newData = JSON.parse(activityLogsData.NewData);

                    displayData(contentArea, newData);
                }


                if (loaderArea) loaderArea.style.display = 'none';
                if (contentArea) contentArea.style.display = 'block';
            }, 1000);

        } else {
            if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture order info.</p>';
        }

    }

    function displayData(container, data) {
        for (const [key, value] of Object.entries(data)) {

            // If the key is Items
            if (key === 'Items' && typeof value === 'object') {

                container.innerHTML += `
                    <div class="row d-flex text-center mb-3">
                        <div class="fw-bold">${key}</div>
                        <div class="d-flex">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Object.values(value).map(item => `
                                        <tr>
                                            <td>${item.name ?? '--'}</td>
                                            <td>${item.size ?? '--'}</td>
                                            <td>${item.qty ?? '--'}</td>
                                            <td>${item.price ?? '--'}</td>
                                            <td>${(item.qty * item.price).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;

            } 
            // Normal values
            else if (typeof value !== 'object' || value === null) {

                container.innerHTML += `
                    <div class="row mb-2">
                        <div class="col fw-bold">${key}</div>
                        <div class="col">${value ?? '--'}</div>
                    </div>
                `;

            } 
            // Other nested objects
            else {

                container.innerHTML += `
                    <div class="row mb-2">
                        <div class="col fw-bold">${key}</div>
                        <div class="col">
                            <div class="ms-3">
                `;

                displayData(container, value);

                container.innerHTML += `
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    }
</Script>