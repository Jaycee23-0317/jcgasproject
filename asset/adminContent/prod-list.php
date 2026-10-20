<div class="main-content flex-grow-1">
    <div class="main-section-header bg-main sticky-top d-flex justify-content-between align-items-center">
        <h2 class="py-3 m-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Product List</h2>
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
                    <button class="btn btn-primary" id="addProductBtn" onclick="openInventoryProductModal('add')"><i class="bi bi-plus-circle"></i> Add Item</button>
                </div>
                <table id="productTable" class="display">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Brand</th>
                            <th>Name</th>
                            <th>Size</th>
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

<div class="modal fade" id="inventoryProductModal" aria-labelledby="inventoryProductModalLabel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-xl modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="inventoryProductModalLabel">Product Modal</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="inventoryProductModalBody">

            </div>
            <div class="modal-footer" id="inventoryProductModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

<script src="../js/inventory.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("loadingCard").style.display = "block";
        document.getElementById("sectionCard").style.display = "none";
        setTimeout(() => {
            loadInventoryTable();
            document.getElementById("loadingCard").style.display = "none";
            document.getElementById("sectionCard").style.display = "block";
        }, 1000);
    });
</script>