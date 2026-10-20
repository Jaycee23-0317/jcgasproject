<?php
$branchesResult = $conn->query("SELECT BranchID, BranchName FROM branches ORDER BY BranchID");
$branches = [];
while ($row = $branchesResult->fetch_assoc()) {
    $branches[] = $row;
}

?>

<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Stock List</h2>

        <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0 p-2 rounded shadow-sm">
            <div class="d-flex align-items-center gap-1">
                <select id="filterBranchInput" class="form-select form-select-sm text-dark border-0">
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['BranchID']; ?>">
                            <?php echo htmlspecialchars($b['BranchName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-primary px-3" onclick="refreshInventoryData()"><i class="bi bi-filter-left me-1"></i>Filter</button>
        </div>
    </div>
    <div class="main-section-body">
        <div class="text-center py-5 section-card rounded" id="loadingCard">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading please wait...</p>
        </div>

        <div id="sectionCard" style="display: none" ;>
            <div class="section-card">
                <div class="section-card-header d-flex justify-content-between align-items-center">
                    <h2 class="m-0"><i class="bi bi-fire me-2"></i>Items</h2>
                </div>
                <table id="inventoryTable" class="display">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Stock</th>
                            <th>Price</th>
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

<div class="modal fade" id="inventoryModal" aria-labelledby="inventoryModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-xl modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="inventoryModalLabel">Inventory Modal</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="inventoryModalBody">
                <div class="text-center py-5 modalCard rounded" id="inventoryModalLoaderArea">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading inventory details...</p>
                </div>

                <div class="container rounded-4" id="inventoryModalContentArea" style="display: none;">
                    <form action="" method="post">
                        <input type="hidden" name="inventory_id" id="inventory_id" value="">
                        <input type="hidden" name="branch_id" id="branch_id" value="">
                        <input type="hidden" name="product_id" id="product_id" value="">
                        <div class="row g-3">
                            <div class="col col-12 text-center">
                                <div class="form-floating">
                                    <input type="text" name="branch_name" id="branch_name" value="" class="form-control" disabled>
                                    <label for="branch_name">Branch Name</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="brand" id="brand" value="" class="form-control" disabled>
                                    <label for="brand">Product Brand</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="text" name="product_name" id="product_name" value="" class="form-control" disabled>
                                    <label for="product_name">Product Name</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="number" name="stock" id="stock" value="" class="form-control">
                                    <label for="stock">Stock</label>
                                </div>
                            </div>
                            <div class="col col-md-6 text-center">
                                <div class="form-floating">
                                    <input type="number" name="price" id="price" value="" class="form-control">
                                    <label for="price">Price</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer" id="inventoryModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="productFormSubmitBtn" onclick="inventoryModalSubmitBtn()">
                    <i class="bi bi-check2-circle me-1"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryConfirmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="invConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold" id="invConfirmLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Changes
                </h5>
            </div>
            <div class="modal-body py-3 text-start">
                <p class="m-0 fs-6">Are you sure you want to update this product's branch inventory records? This action modifies active stock tracks instantly.</p>
            </div>
            <div class="modal-footer border-top border-secondary p-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmInvSaveBtn" class="btn btn-sm btn-warning">Yes, Update</button>
            </div>
        </div>
    </div>
</div>

<script>
    const inventoryTable = new DataTable("#inventoryTable", {
        responsive: true,
        scrollCollapse: true,
        scroller: true,
        scrollY: 500,
        layout: {
            topStart: {
                pageLength: {
                    menu: [5, 10, 25, 50]
                },
                buttons: ["print"]
            }
        },
        columns: [
            { data: "Brand" },
            { data: "ProductName" },
            { data: "Size" },
            {
                data: "Stock",
                render: data => {
                    const stock = Number(data);
                    if (stock <= 0) return '<span class="badge bg-danger">Out of stock</span>';
                    if (stock < 10) return `<span class="badge bg-warning text-dark">${stock} Low Stock</span>`;
                    return `<span class="fw-semibold">${stock} available</span>`;
                }
            },
            {
                data: "Price",
                render: data => `₱${Number(data).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row, meta) => `<button type="button" class="btn btn-sm btn-warning bi bi-pencil-square" title="Edit Inventory" onclick="openInventoryEditModal(${meta.row})"></button>`
            }
        ]
    });

    async function refreshInventoryData() {
        const branchID = document.getElementById("filterBranchInput").value;
        const loadingCard = document.getElementById("loadingCard");
        const sectionCard = document.getElementById("sectionCard");

        loadingCard.style.display = "block";
        sectionCard.style.display = "none";

        try {
            const response = await fetch(`../api/branchInventory.php?branchId=${branchID}`);

            if (!response.ok) throw new Error("Failed to load inventory.");

            const data = await response.json();

            inventoryTable.clear().rows.add(data);
        } catch (error) {
            console.error("Error fetching inventory details:", error);
            showAlertBanner("Failed to load inventory data.", "danger");
        } finally {
            inventoryTable.draw();

            loadingCard.style.display = "none";
            sectionCard.style.display = "block";
        }
    }

    document.addEventListener("DOMContentLoaded", refreshInventoryData);

    function openInventoryEditModal(rowIndex) {
        const loaderArea = document.getElementById('inventoryModalLoaderArea');
        const contentArea = document.getElementById('inventoryModalContentArea');
        
        if (loaderArea) loaderArea.style.display = 'block';
        if (contentArea) contentArea.style.display = 'none';

        const modalEl = document.getElementById("inventoryModal");
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();           
             
        const inventoryData = inventoryTable.row(rowIndex).data();
    
        console.log(inventoryData);

        if (inventoryData) {
            setTimeout(() => {
                document.getElementById("inventory_id").value = inventoryData.InventoryID;
                document.getElementById("branch_id").value = inventoryData.BranchID;
                document.getElementById("product_id").value = inventoryData.ProductID;
                document.getElementById("branch_name").value = inventoryData.BranchName;
                document.getElementById("brand").value = inventoryData.Brand;
                document.getElementById("product_name").value = inventoryData.ProductName;
                document.getElementById("stock").value = inventoryData.Stock;
                document.getElementById("price").value = inventoryData.Price;

                if (loaderArea) loaderArea.style.display = 'none';
                if (contentArea) contentArea.style.display = 'block';
            }, 500);
        } else {
            if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture inventory info.</p>';
        }
    }

    function inventoryModalSubmitBtn() {
        const editModalEl = document.getElementById("inventoryModal");
        const formEl = document.querySelector("#inventoryModalBody form");
        if (!formEl) return;

        if (!formEl.checkValidity()) {
            formEl.reportValidity();
            return;
        }
        const editModalInstance = bootstrap.Modal.getInstance(editModalEl);
        const confirmModalEl = document.getElementById("inventoryConfirmModal");

        if (confirmModalEl) {
            const saveBtn = document.getElementById("confirmInvSaveBtn");
            if (saveBtn) {
                saveBtn.onclick = function () {
                    confirmInventoryUpdate();
                };
            }

            if (editModalInstance) editModalInstance.hide();

            const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl) || new bootstrap.Modal(confirmModalEl);

            confirmModalInstance.show();
        }
    }

    function confirmInventoryUpdate() {
        console.log("hi");
        const confirmModalEl = document.getElementById("inventoryConfirmModal");
        const formEl = document.querySelector("#inventoryModalBody form");

        if (!confirmModalEl || !formEl) return;

        const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);
        const confirmBtn = document.getElementById("confirmInvSaveBtn");

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Updating...`;
        }

        const formData = new FormData(formEl);
        const inventoryID = formData.get("inventory_id");
        console.log(inventoryID);

        fetch(`../api/branchInventory.php?inventoryID=${inventoryID}`, {
            method: "POST",
            body: formData,
        })
            .then((response) => {
                if (!response.ok)
                    throw new Error("Server returned network communication error.");
                    return response.json();
                })
            .then((result) => {
                if (confirmModalInstance) confirmModalInstance.hide();

                setTimeout(() => {
                    if (result.success) {
                        showAlertBanner(result.message, "success");
                        refreshInventoryData();
                    } else {
                        showAlertBanner(result.message, "danger");
                    }

                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = "Yes, Update";
                    }
                }, 300);
            })
            .catch((error) => {
                if (confirmModalInstance) confirmModalInstance.hide();

                setTimeout(() => {
                    showAlertBanner(
                    error.message || "System busy. Request processing failed.",
                    "danger",
                    );
                    if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Yes, Update";
                    }
                }, 300);
            });
        }

</script>