const branchTablesRegistry = {};

const productTable = new DataTable("#productTable", {
  responsive: true,
  scrollCollapse: true,
  scroller: true,
  scrollY: 500,
  layout: {
    topStart: {
      pageLength: {
        menu: [5, 10, 25, 50],
      },
      buttons: ["print"],
    },
  },
  columns: [
    {
      data: "ImageName",
      render: function (data, type, row) {
        if (data && data.trim() !== "") {
          return `
            <div class="d-flex justify-content-center align-items-center p-2">
              <img src="../asset/img/product/${data}" alt="Product Image" class="product-image" style="height: 80px;">
            </div>
          `;
        } else {
          return `
            <div class="product-no-image d-flex flex-column justify-content-center align-items-center bg-dark-subtle border text-muted" style="height: 100px; width: 150px;">
              <i class="bi bi-image fs-3"></i>
              <span style="font-size: 0.8rem;">No image uploaded</span>
            </div>
          `;
        }
      },
    },
    {
      data: "Brand",
    },
    {
      data: "ProductName",
      className: "fw-semibold", // Replicates class="fw-semibold" dynamically
    },
    {
      data: "Size",
    },
    {
      data: null, // 'null' because this column doesn't grab a field value, it creates buttons
      orderable: false, // Disables sorting arrows on action buttons column
      render: function (data, type, row, meta) {
        return `
                        <button type="button" class="btn btn-sm btn-success bi bi-eye" title="View"
                            onclick="openInventoryProductModal('view', '${meta.row}')"></button>
                        <button type="button" class="btn btn-sm btn-warning bi bi-pencil-square" title="Edit"
                            onclick="openInventoryProductModal('edit', '${meta.row}')"></button>
                    `;
      },
    },
  ],
});

async function loadInventoryTable() {
  fetch(`../api/product.php`)
    .then((response) => {
      if (!response.ok) throw new Error("Network validation failed");

      return response.json();
    })
    .then((data) => {
      productTable.clear().rows.add(data).draw();
      console.log(data);
    })
    .catch((error) => {
      console.error("Error fetching inventory details:", error);
    });
}

function openInventoryProductModal(action, rowIndex) {
  const product = productTable.row(rowIndex).data();

  const modalEl = document.getElementById("inventoryProductModal");
  const titleEl = document.getElementById("inventoryProductModalLabel");
  const bodyEl = document.getElementById("inventoryProductModalBody");
  const footerEl = document.getElementById("inventoryProductModalFooter");

  if (action === "view") {
    titleEl.textContent = "Product Details";
    const imageHtml = product.ImageName
      ? '<img src="../asset/img/product/' +
        encodeURIComponent(product.ImageName) +
        '" alt="' +
        (product.ProductName || "Product") +
        '" class="product-image mb-3" style="height: 200px;">'
      : '<div class="product-no-image mb-3" style="height: 200px;"><i class="bi bi-image"></i><span>No image uploaded</span></div>';

    bodyEl.innerHTML =
      '<div class="row g-3">' +
        '<div class="col-12 text-center">' +
          imageHtml +
        '</div>' +
        '<div class="col-12">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Product Image File Name</label>' +
            '<span>' +(product.ImageName ?? 'No Image File Name') + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Product Brand</label>' +
            '<span>' +(product.Brand) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Product Name</label>' +
            '<span>' +(product.ProductName) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Size</label>' +
            '<span>' + (product.Size) + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Added On</label>' +
          '<span>' + formatDate(product.CreatedAt) + '</span>' +
          '</div>' +
        '</div>' +
      '</div>';

    footerEl.innerHTML =
      '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
  }

  else {
    const isEdit = action === "edit";
    titleEl.textContent = isEdit ? "Edit Product" : "Add Product";

    const imageHtml =
      isEdit && product.ImageName
        ? '<img src="../asset/img/product/' +
          encodeURIComponent(product.ImageName) +
          '" alt="Current product image" id="previewProductImage" class="product-image mb-3" style="height: 200px;">'
        : '<div class="product-no-image mb-3" style="height: 200px;" id="previewNoImage"><i class="bi bi-image"></i><span>No image selected</span></div>';

    bodyEl.innerHTML =
      '<form action="" method="POST" enctype="multipart/form-data">' +
      (isEdit
        ? '<input type="hidden" name="product_id" value="' +
          (product.ProductID || "") +
          '">'
        : "") +
      '<div class="row g-3">' +
      '<div class="col-12 text-center">' +
      '<div id="productImagePreview">' +
      imageHtml +
      "</div>" +
      "</div>" +
      '<div class="col-12">' +
          '<div class="product-info-box"><label class="fw-bold d-block small text-muted">Product Image File Name</label>' +
            '<span>' +(product.ImageName ?? 'No Image File Name') + '</span>' +
          '</div>' +
        '</div>' +
      '<div class="col-md-4">' +
      '<label class="form-label fw-bold">Product Brand</label>' +
      '<input type="text" class="form-control" name="product_brand" id="productBrandInput" required maxlength="100" value="' +
      (isEdit ? (product.Brand) : "") +
      '">' +
      "</div>" +
      '<div class="col-md-4">' +
      '<label class="form-label fw-bold">Product Name</label>' +
      '<input type="text" class="form-control" name="product_name" id="productNameInput" required maxlength="100" value="' +
      (isEdit ? (product.ProductName) : "") +
      '">' +
      "</div>" +
      '<div class="col-md-4">' +
      '<label class="form-label fw-bold">Size</label>' +
      '<input type="text" class="form-control" name="size" id="productSizeInput" required maxlength="20" placeholder="e.g. 11kg" value="' +
      (isEdit ? (product.Size) : "") +
      '">' +
      "</div>" +
      '<div class="col-12">' +
      '<label class="form-label fw-bold">Product Image</label>' +
      '<input type="file" class="form-control" name="product_image" id="productImageInput" accept="image/jpeg,image/png,image/gif,image/webp">' +
      '<div class="form-text">Allowed: JPG, PNG, GIF, WEBProduct. Max 5MB.</div>' +
      "</div>" +
      "</form>";

    footerEl.innerHTML =
      '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
      '<button type="button" class="btn btn-primary" id="productFormSubmitBtn" onclick="inventoryProductModalSubmitBtn()"' +
      '><i class="bi bi-check2-circle me-1"></i>' +
      (isEdit ? "Save Changes" : "Save Product") +
      "</button>";

    const fileInput = document.getElementById("productImageInput");
    if (fileInput) {
      fileInput.addEventListener("change", function (e) {
        const file = e.target.files && e.target.files[0];
        const preview = document.getElementById("productImagePreview");
        if (!preview) return;

        if (file) {
          const reader = new FileReader();
          reader.onload = function (ev) {
            preview.innerHTML =
              '<img src="' +
              ev.target.result +
              '" alt="Selected image" class="product-image mb-3" style="height: 200px;">';
          };
          reader.readAsDataURL(file);
        } else {
          // Restore the current/no-image state
          if (product.ImageName) {
            preview.innerHTML =
              '<img src="../asset/img/product/' +
              encodeURIComponent(product.ImageName) +
              '" alt="Current product image" class="product-image mb-3" style="height: 200px;">';
          } else {
            preview.innerHTML =
              '<div class="product-no-image mb-3"><i class="bi bi-image"></i><span>No image selected</span></div>';
          }
        }
      });
    }
  }
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

function inventoryProductModalSubmitBtn() {
  const editModalEl = document.getElementById("inventoryProductModal");
  const formEl = document.querySelector("#inventoryProductModalBody form");
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
        confirmProductUpdate();
      };

    }

    if (editModalInstance) editModalInstance.hide();

    const confirmModalInstance =
      bootstrap.Modal.getInstance(confirmModalEl) ||
      new bootstrap.Modal(confirmModalEl);

    confirmModalInstance.show();
  }
}

function confirmProductUpdate() {
  const confirmModalEl = document.getElementById("inventoryConfirmModal");
  const formEl = document.querySelector("#inventoryProductModalBody form");

  if (!confirmModalEl || !formEl) return;

  const confirmModalInstance = bootstrap.Modal.getInstance(confirmModalEl);
  const confirmBtn = document.getElementById("confirmInvSaveBtn");

  if (confirmBtn) {
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Updating...`;
  }
  const formData = new FormData(formEl);

  fetch("../php/save_product.php", {
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
          loadInventoryTable();
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

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
}