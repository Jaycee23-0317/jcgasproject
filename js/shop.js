let cart = {};
let total = 0;
let products = {};
let selectedBranchID = 0;

let pendingOrderGroupId = null;

let reviewMap = null;
let reviewMapInitialized = false;
let reviewMapMarker = null; 

document.addEventListener('DOMContentLoaded', () => {
    const role = document.getElementById('roleHiddenInput').value;

    if (role === 'Cashier') {
        const branchID = document.getElementById('branchIDHiddenInput').value;
        selectedBranchID = branchID;

        if (selectedBranchID > 0) {
            incomingOrders(selectedBranchID);
            loadBranchProducts(selectedBranchID);
        }
    } else {
        loadBranch();
    }
});

function loadBranch() {
    const shopLoader = document.getElementById("shopLoader");
    const branchesContainer = document.getElementById("branchesContainer");
    const selectBranchDisplay = document.getElementById("selectBranchDisplay");
    const selectedBranchDisplay = document.getElementById("selectedBranchDisplay");

    // Safety guard clause
    if (!branchesContainer) return;

    // Reset container and turn on visual loader spinner
    branchesContainer.innerHTML = "";
    if (shopLoader) shopLoader.style.display = "block";
    if (selectBranchDisplay) selectBranchDisplay.style.display = "none";
    if (selectedBranchDisplay) selectedBranchDisplay.style.display = "none";

    // 1. Fetch data from your PHP API backend endpoint
    fetch(`../api/branch.php`)
        .then((response) => {
            // Check if network request actually succeeded
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json(); // 2. Convert raw string stream to clean JS Object/Array
        })
        .then((data) => {
            // Handle variations in API outputs (adjust if your PHP sends just a raw array or {"data": [...]})
            const branches = Array.isArray(data) ? data : (data.data || []);

            if (branches.length > 0) {
                // 3. Proper JavaScript array iteration layout
                branches.forEach((branch) => {
                    const cardDiv = document.createElement("div");
                    cardDiv.className = "card mb-1 btn-branch";
                    cardDiv.style.cursor = "pointer";

                    // 4. Using the single iterator item "branch" instead of the collective "branches"
                    cardDiv.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center p-2">
                            <h5 class="m-0">${branch.BranchName}</h5>
                            <p class="m-0 text-muted">${branch.Address}</p>
                        </div>
                    `;

                    // Click event triggers your branch selection dashboard sequence
                    cardDiv.addEventListener("click", () => {
                        selectBranch(branch.BranchID, branch.BranchName, branch.Address);
                    });

                    // 5. Fixed variable name constraint to match top selector reference definition
                    branchesContainer.appendChild(cardDiv);
                });
            } else {
                // Optional Placeholder when database records return completely empty
                branchesContainer.innerHTML = `<p class="text-center p-3 text-muted">No operational branches located.</p>`;
            }
        })
        .catch((error) => {
            console.error("Failed executing branch payload pull request:", error);
            branchesContainer.innerHTML = `<p class="text-center p-3 text-danger">Failed to load branches.</p>`;
        })
        .finally(() => {
            // Always shut off loading spinner states cleanly upon script process conclusions
            if (shopLoader) {
                shopLoader.style.setProperty("display", "none", "important");
            }
            if (selectBranchDisplay) {
                selectBranchDisplay.style.setProperty("display", "block", "important");
            }
        });
}

function selectBranch(branchID, branchName, branchAddress) {
    // Hide the Selection panel, Show the Selected panel
    document.getElementById("selectBranchDisplay").style.display = "none";
    document.getElementById("selectedBranchDisplay").style.display = "block";

    // Map the selected branch details to the new display card
    document.getElementById("selectedBranchIDHiddenInput").value = branchID;
    document.getElementById("selectedBranchNameDisplay").innerHTML = `<i class="bi bi-shop me-1"></i> ${branchName}`;
    document.getElementById("selectedBranchAddressDisplay").textContent = branchAddress;

    selectedBranchID = branchID;
    // Fire your products loader API
    loadBranchProducts(selectedBranchID);
}

function backToSelectBranch() {
    cart = {};
    total = 0;
    products = {};
    selectedBranchID = 0;

    loadBranch();
    updateCartItemsList(cart);
    // const abandonModalElement = document.getElementById('abandonCartModal');
    // if (!abandonModalElement) return;
    // const abandonCartModal = new bootstrap.Modal(abandonModalElement);
    // const confirmAbandonBtn = document.getElementById('btnConfirmAbandon');

    // if (Object.keys(cart).length > 0) {
    //     abandonCartModal.show();
    // } else {
    //     loadBranch();
    // }

    // confirmAbandonBtn.addEventListener('click', () => {
    //     cart = {};
    //     total = 0;
    //     products = {};
    //     selectedBranchID = 0;

    //     loadBranch();
    // });

}

document.addEventListener("DOMContentLoaded", () => {
    const confirmAbandonBtn = document.getElementById("btnConfirmAbandon");

    let targetNavigationAction = null;
    let confirmedAbandon = false;

    function getAbandonModal() {
        const modalElement = document.getElementById("abandonCartModal");

        if (!modalElement) { return null; }

        return bootstrap.Modal.getOrCreateInstance(
            modalElement
        );
    }

    function hasCartItems() {
        return typeof cart !== "undefined" &&
            Object.keys(cart).length > 0;
    }

    document.querySelectorAll(".navbar-nav .nav-link")
        .forEach(link => {
            link.addEventListener("click", function (event) {
                if (!hasCartItems()) {
                    return;
                }

                event.preventDefault();

                targetNavigationAction = () => {
                    window.location.href = this.href;
                };

                confirmedAbandon = false;

                const modal = getAbandonModal();

                if (modal) {  modal.show(); }
            });
        });

    const backBtn = document.querySelector(".backBtn");

    if (backBtn) {
        backBtn.addEventListener("click", function (event) {
            if (!hasCartItems()) {
                backToSelectBranch();
                return;
            }

            event.preventDefault();

            targetNavigationAction = () => {
                backToSelectBranch();
            };
            confirmedAbandon = false;
            const modal = getAbandonModal();

            if (modal) {  modal.show(); }
        });
    }

    confirmAbandonBtn?.addEventListener(
        "click",
        () => {
            if (!targetNavigationAction) {  return; }

            const action = targetNavigationAction;

            targetNavigationAction = null;
            confirmedAbandon = true;

            const modalElement =
                document.getElementById(
                    "abandonCartModal"
                );
            const modal =
                bootstrap.Modal.getInstance(
                    modalElement
                );
            if (modal) {
                modalElement.addEventListener(
                    "hidden.bs.modal",
                    function handleHidden() {
                        modalElement.removeEventListener(
                            "hidden.bs.modal",
                            handleHidden
                        );

                        action();

                    }
                );
                modal.hide();
            } else {
                action();
            }
        }
    );

    const modalElement =
        document.getElementById(
            "abandonCartModal"
        );

    modalElement?.addEventListener(
        "hidden.bs.modal",
        () => {
            if (!confirmedAbandon) {
                targetNavigationAction = null;
            }
            confirmedAbandon = false;
        }
    );
});

function loadBranchProducts(branchID) {
    const shopItemContainer = document.getElementById('shopItemContainer');
    const shopLoader = document.getElementById("shopLoader");

    if (!shopItemContainer) return;

    shopItemContainer.innerHTML = "";
    shopLoader.style.display = "block";

    fetch(`../php/get_products.php?branch_id=${branchID}`)
        .then(response => response.json())
        .then(groupedProducts => {
            shopLoader.style.setProperty("display", "none", "important");

            if (groupedProducts.error || Object.keys(groupedProducts).length === 0) {
                shopItemContainer.innerHTML = `<p class="text-muted p-4">No products available or an error occurred.</p>`;
                return;
            }

            // Loop through each grouped Brand Category block
            for (const [brandName, brandProducts] of Object.entries(groupedProducts)) {

                // Create the brand wrapper element
                const brandWrapper = document.createElement("div");
                brandWrapper.className = "rounded border mb-1 text-start"; // default align to left

                // 1. Build the Brand Header Container
                brandWrapper.innerHTML = `
                <div class="row p-2">
                    <div class="col-12">
                        <h2 class="fw-bold border-bottom border-secondary m-0">
                            <i class="bi bi-tag me-2"></i>${brandName.toUpperCase()}
                        </h2>
                    </div>
                </div>
            `;

                // 2. Build the Product Grid Container for this specific brand
                const gridContainer = document.createElement("div");
                gridContainer.className = "row row-cols-2 row-cols-md-3 row-cols-lg-4 mb-3 g-3 p-2";

                // 3. Iterate through cards tied strictly to this brand array
                brandProducts.forEach(product => {
                    const isOutOfStock = (Number(product.Stock) <= 0);
                    const isLowStock = (!isOutOfStock && Number(product.Stock) < 10);

                    // Format currency representation
                    const formattedPrice = Number(product.Price).toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    // Manage dynamic conditional style class naming strings
                    const disabledClass = isOutOfStock ? 'disabled' : '';
                    let stockClass = 'text-white-50';
                    if (isOutOfStock) stockClass = 'out-of-stock-text';
                    else if (isLowStock) stockClass = 'low-stock';

                    const stockText = isOutOfStock ? 'Out of stock' : `${product.Stock} available`;

                    // Generate safe title descriptions for data-attributes
                    const safeNameString = `${product.ProductName}`.replace(/"/g, '&quot;');

                    // Image validation setup
                    let imageHTML = `
                    <div class="card-img-top product-no-image mb-3">
                        <i class="bi bi-image"></i>
                        <span>No image uploaded</span>
                    </div>
                `;

                    if (product.Image) {
                        imageHTML = `
                        <div class="card-img-top mb-3">
                            <img src="../asset/img/product/${product.Image}" alt="Product Image" style="width:100%; height:120px; object-fit:contain;">
                        </div>
                    `;
                    }

                    const colDiv = document.createElement("div");
                    colDiv.className = "col";
                    colDiv.innerHTML = `
                    <div class="card shop-card bg-dark-navy p-3 text-center ${disabledClass}"
                        data-id="${product.ProductID}"
                        data-brand="${product.Brand}"
                        data-name="${safeNameString}"
                        data-size="${product.Size}"
                        data-price="${product.Price}"
                        data-max="${product.Stock}">

                        ${imageHTML}

                        <h5 class="text-white text-nowrap">${product.Size} LPG</h5>
                        <h6 class="text-gas-cyan">₱${formattedPrice}</h6>

                        <p class="mb-1 ${stockClass}">
                            ${stockText}
                        </p>

                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button"
                                class="btn btn-gas-outline-primary"
                                onclick="adjustQty(${product.ProductID}, -1)"
                                ${isOutOfStock ? 'disabled' : ''}>−</button>

                            <input type="number"
                                id="qty-${product.ProductID}"
                                value="0"
                                min="0"
                                max="${product.Stock}"
                                class="qty-display form-control form-control-sm mx-2 no-spinners"
                                style="width: 60px"
                                onchange="syncCartFromInputs()"
                                ${isOutOfStock ? 'disabled' : ''}>

                            <button type="button"
                                class="btn btn-gas-outline-primary"
                                onclick="adjustQty(${product.ProductID}, 1)"
                                ${isOutOfStock ? 'disabled' : ''}>+</button>
                        </div>
                        <input type="hidden" id="price-${product.ProductID}" value="${product.Price}">
                    </div>
                `;

                    gridContainer.appendChild(colDiv);
                });

                // Attach grid into wrapper container, then insert into display container
                brandWrapper.appendChild(gridContainer);
                shopItemContainer.appendChild(brandWrapper);
            }
        })
        .catch(error => {
            shopLoader.style.setProperty("display", "none", "important");
            shopItemContainer.innerHTML = `<p class="text-danger p-4">Error connecting to server. Please try again.</p>, ${error}`;
        });
}

function syncCartFromInputs() {
    cart = {}; // Reset the cart data before rebuilding it

    document.querySelectorAll('input.qty-display').forEach(input => {
        const id = input.id.replace('qty-', '');
        let qty = parseInt(input.value) || 0;
        const maxStock = parseInt(input.max) || 0;

        if (qty > maxStock) {
            qty = maxStock;
            input.value = maxStock; // Force the physical text box on screen to snap back down
        }

        if (qty < 0) {
            qty = 0;
            input.value = 0;
        }

        if (qty > 0) {
            const productCard = input.closest('.shop-card');
            if (productCard) {
                const name = productCard.dataset.name;
                const price = parseFloat(productCard.dataset.price) || 0;
                const size = productCard.dataset.size;
                cart[id] = { name, size, qty, price };
            }
        }
    });

    const tabCartBtn = document.getElementById('sidebar-cart');
    const cartSideBar = document.getElementById('cartSidebar');
    const layout = document.getElementById('shopLayout');
    const isLayoutCollapsed = layout ? layout.classList.contains('collapsed') : false;

    if (tabCartBtn && cartSideBar) {
        if (cartSideBar.classList.contains("d-none") || isLayoutCollapsed) {
            toggleCartSidebar('cartSidebar');
        }
    }
    
    updateCart();
}

function updateCart() {
    let itemCount = 0;
    total = 0;

    Object.values(cart).forEach(item => {
        itemCount += item.qty;
        total += item.price * item.qty;
    });

    document.getElementById('itemCount').textContent = Object.keys(cart).length
    document.getElementById('total').textContent = total.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    // 2. Validate Payment Selection
    // const paymentMethodEl = document.getElementById('paymentMethod');
    // const paymentethod = paymentMethodEl ? paymentMethodEl.value : '';
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    
    if (placeOrderBtn) {
        placeOrderBtn.disabled = itemCount === 0;
    }

    updateCartItemsList(cart);
}

function adjustQty(productId, change) {
    const input = document.getElementById(`qty-${productId}`);
    if (!input) return;

    let currentQty = parseInt(input.value) || 0;
    let newQty = currentQty + change;

    const maxStock = parseInt(input.max) || 0;
    if (newQty < 0) newQty = 0;
    if (newQty > maxStock) newQty = maxStock;

    input.value = newQty;

    syncCartFromInputs();
}

function updateCartItemsList(cartData) {
    const cartListEl = document.getElementById('cartItemsList');
    const emptyMsgEl = document.getElementById('cartEmptyMessage');
    
    if (!cartListEl) return;

    cartListEl.innerHTML = '';

    const cartEntries = Object.entries(cartData);

    if (cartEntries.length === 0) {
        if (emptyMsgEl) emptyMsgEl.style.setProperty('display', 'block', 'important');
        return;
    } else {
        if (emptyMsgEl) emptyMsgEl.style.setProperty('display', 'none', 'important');
    }

    cartEntries.forEach(([id, item]) => {
        const itemLineTotal = item.price * item.qty;
        
        const li = document.createElement('li');
        li.className = "list-group-item d-flex flex-column flex-grow-1 align-items-center bg-transparent text-white p-3 mb-2";
        
        li.innerHTML = `
            <div class="d-flex align-items-center w-100">
                <h6 class="mb-0 text-truncate fw-semibold text-light" style="max-width: 180px;">${item.name} - ${item.size}</h6>
            </div>
            
            <div class="d-flex justify-content-between align-items-center w-100">
                <div><small class="text-cart-muted">₱${item.price.toFixed(2)} each</small></div>
                <div class="mt-1 fw-bold text-gas-cyan">₱${itemLineTotal.toFixed(2)}</div>
            </div>

            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="btn-group btn-group-sm bg-dark sidebar-qty-group gap-3">
                    <button type="button" class="btn btn-dark text-white px-2 py-0 border-0" onclick="adjustQty(${id}, -1)">−</button>
                    <span class="px-2 align-self-center text-center fw-bold text-info" style="min-width: 25px;">${item.qty}</span>
                    <button type="button" class="btn btn-dark text-white px-2 py-0 border-0" onclick="adjustQty(${id}, 1)">+</button>
                </div>
                
                <!-- Direct Trash/Delete Button Action -->
                <button type="button" class="btn btn-sm btn-outline-danger border-0 p-1 lh-1" onclick="removeItemFromCart(${id})" title="Remove item">
                    <i class="bi bi-trash3 fs-5"></i>
                </button>
            </div>
        `;
        
        cartListEl.appendChild(li);
    });
}

function removeItemFromCart(productId) {
    const input = document.getElementById(`qty-${productId}`);
    if (input) {
        input.value = 0; // Reset main grid input value visual state
        
        // Fix up the main grid button state classes
        const container = input.closest('.shop-card');
        if (container) {
            const minusBtn = container.querySelector('.btn-qty.minus');
            if (minusBtn) minusBtn.classList.add('zero');
        }
    }
    
    // Wipe out the index completely from the tracking state memory object
    delete cart[productId];
    
    // Refresh calculations and update the visual list
    updateCart();
}

// Optional Event Listener: Updates button statuses when payment dropdown changes
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('paymentMethod')?.addEventListener('change', updateCart);
});

function placeOrderBtn() {
    const loaderArea = document.getElementById('reviewOrderModalLoaderArea');
    const contentArea = document.getElementById('reviewOrderModalContentArea');
    const mapViewElement = document.getElementById('reviewDeliveryMapAddress');

    const paymentMethod = document.getElementById('paymentMethod').value;

    const addressText = document.getElementById('deliveryAddress').textContent;
    const lat = document.getElementById('savedLatHiddenInput').value;
    const lng = document.getElementById('savedLongHiddenInput').value;

    if (loaderArea) loaderArea.style.display = 'block';
    if (contentArea) contentArea.style.display = 'none';

    const modalElement = document.getElementById('reviewOrderModal');
    if (!modalElement) {
        if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture order info.</p>';
        return;
    }

    // FIX: Prevents layout breaking by fetching or safely assembling an instance reference
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    bsModal.show();

    setTimeout(() => {
        const itemsBody = document.getElementById('reviewOrderItemsBody');
        let itemsHtml = '';
        let total = 0;

        Object.entries(cart).forEach(([id, item]) => {
            const subtotal = item.qty * item.price;
            total += subtotal;
            itemsHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td>${item.size}</td>
                    <td class="text-center">${item.qty}</td>
                    <td class="text-end">₱ ${item.price.toFixed(2)}</td>
                    <td class="text-end">₱ ${subtotal.toFixed(2)}</td>
                </tr>
            `;
        });

        if (itemsBody) itemsBody.innerHTML = itemsHtml;
        
        const reviewTotalEl = document.getElementById('reviewOrderTotal');
        if (reviewTotalEl) {
            reviewTotalEl.innerText = '₱' + total.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        const paymentEl = document.getElementById('reviewOrderPayment');
        if (paymentEl) paymentEl.innerText = paymentMethod;

        const addressEl = document.getElementById('reviewDeliveryMapAddressText');
        if (addressEl) addressEl.innerText = addressText;
        
        const latEl = document.getElementById('reviewDeliveryLat');
        if (latEl) latEl.innerText = lat;
        
        const lngEl = document.getElementById('reviewDeliveryLong');
        if (lngEl) lngEl.innerText = lng;

        // Ready to load your custom rendering script logic here:
        renderReviewDeliveryAddress(lat, lng);

        if (loaderArea) loaderArea.style.display = 'none';
        if (contentArea) contentArea.style.display = 'block';

    }, 1000);
}


function confirmOrderBtn() {
    const total = document.getElementById('reviewOrderTotal').innerText;
    const payment = document.getElementById('reviewOrderPayment').innerText;
    const address = document.getElementById('reviewDeliveryMapAddressText').innerText;

    const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewOrderModal'));
    if (reviewModal) reviewModal.hide();

    document.getElementById('confirmOrderModalPayment').innerText = payment;
    document.getElementById('confirmOrderModalTotal').innerText = total;
    document.getElementById('confirmOrderModalAddress').innerText = address;

    const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmOrderModal'));
    confirmModal.show();
}

function proceedOrderSubmission() {
    // showLoading('Placing your order...');

    const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmOrderModal'));
    const btn = document.getElementById('confirmOrderModalConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1 text-gas-cyan"></span>Placing order...';
    
    const customerName = document.getElementById('customerName').value;
    const deliveryAddress = document.getElementById('reviewDeliveryMapAddressText').innerText;
    const deliveryLatitude = document.getElementById('reviewDeliveryLat').innerText;
    const deliveryLongitude = document.getElementById('reviewDeliveryLong').innerText;
    const totalAmount = document.getElementById('reviewOrderTotal').innerText;
    const paymentMethod = document.getElementById('reviewOrderPayment').innerText;

    const formData = new FormData();
    formData.append('branch_id', selectedBranchID);
    formData.append('customer_name', customerName);
    formData.append('OrderItems', JSON.stringify(cart));
    formData.append('payment_method', paymentMethod);
    formData.append('delivery_address', deliveryAddress);
    formData.append('delivery_latitude', deliveryLatitude);
    formData.append('delivery_longitude', deliveryLongitude);
    formData.append('total_amount', totalAmount);

    fetch('../api/order.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // hideLoading();
        if (data.success) {
            confirmModal.hide();

            showAlertBanner(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showAlertBanner(data.message, 'danger');
        }
    })
    .catch(err => {
        // hideLoading();
        showAlertBanner('A network error occurred. Please try again.', 'danger');
    });
}

function renderReviewDeliveryAddress(latValue, lngValue) {
    // Clean up previous map instance to prevent "Map container already initialized" errors
    if (reviewMap) {
        reviewMap.remove();
        reviewMap = null;
        reviewMapMarker = null;
    }

    // Convert string inputs safely into floating-point numbers
    const lat = parseFloat(latValue);
    const lng = parseFloat(lngValue);

    // Guard statement: Stop execution if coordinates are corrupt or missing
    if (isNaN(lat) || isNaN(lng)) {
        console.error("Invalid coordinate tokens provided to Leaflet renderer.");
        return;
    }

    setTimeout(() => {
        try {
            // 1. Initialize the map frame setup
            reviewMap = L.map('reviewDeliveryMapAddress').setView([lat, lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(reviewMap);

            // 2. CREATE A DRAGGABLE MARKER PIN
            reviewMapMarker = L.marker([lat, lng], { draggable: true }).addTo(reviewMap);

            // 3. LISTEN FOR PIN DROPS
            reviewMapMarker.on('dragend', function (event) {
                const pos = reviewMapMarker.getLatLng();

                document.getElementById('reviewDeliveryLat').innerText = pos.lat.toFixed(6);
                document.getElementById('reviewDeliveryLong').innerText = pos.lng.toFixed(6);
                
                reverseGeocodeMapPin(pos.lat, pos.lng);
            });

            reviewMap.invalidateSize();

        } catch (asyncError) {
            console.error("Delayed review map initialization loop failure:", asyncError);
        }
    }, 200);
}

function reverseGeocodeMapPin(lat, lng) {
        const revUrl = `https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${lat}&lon=${lng}`;
        fetch(revUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const addressText = (data && data.display_name) ? data.display_name : `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                // UPDATE 1: Updates your main master editable address text input field instantly
                document.getElementById('reviewDeliveryMapAddressText').innerText = addressText;
            })
            .catch(err => {
                console.error('Pin reverse geocoding processing error logs:', err);
                const fallbackCoordsText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;

                document.getElementById('editAddressInput').value = fallbackCoordsText;
                document.getElementById('pinMapPreviewAddress').innerText = fallbackCoordsText;
            });
    }

function resetReviewMapToSavedState() {
    // 1. Grab the original baseline coordinates stored inside your layout's hidden inputs
    const originalLat = document.getElementById('savedLatHiddenInput').value;
    const originalLng = document.getElementById('savedLongHiddenInput').value;
    const originalAddress = document.getElementById('deliveryAddress').textContent;

    // Guard Statement: If they open an empty layout without location cache properties, exit early
    if (!originalLat || !originalLng) {
        console.warn("No baseline location coordinates found in memory form variables.");
        return;
    }

    // 2. Re-render the map using your function — it will handle the cleanup automatically!
    renderReviewDeliveryAddress(originalLat, originalLng);

    // 3. Reset all text fields inside the modal back to the original values instantly
    const reviewLatEl = document.getElementById('reviewDeliveryLat');
    const reviewLongEl = document.getElementById('reviewDeliveryLong');
    const addressTextEl = document.getElementById('reviewDeliveryMapAddressText');

    if (reviewLatEl) reviewLatEl.innerText = parseFloat(originalLat).toFixed(6);
    if (reviewLongEl) reviewLongEl.innerText = parseFloat(originalLng).toFixed(6);
    if (addressTextEl) addressTextEl.innerText = originalAddress;
}

function incomingOrders(branchID) {
    const incomingOrdersContent = document.getElementById("incomingOrdersContainer");
    const countBadge = document.getElementById("pendingCount");
    const displayTarget = document.getElementById("pendingOrdersDisplay");

    if (!incomingOrdersContent) return;

    incomingOrdersContent.innerHTML = `<div class="text-center p-3 text-muted">Loading pending orders...</div>`;

    fetch(`../api/order.php?requestingAllPendingReviewStatusByBranchID=${branchID}`)
        .then(response => response.json())
        .then(pendingOrders => {

            incomingOrdersContent.innerHTML = "";

            let totalCount = 0;
            if (Array.isArray(pendingOrders)) {
                totalCount = pendingOrders.length;
            }

            if (countBadge) {
                if (totalCount > 0) {
                    displayTarget.innerHTML = totalCount;
                    displayTarget.style.display = "inline-block";
                    countBadge.textContent = totalCount;
                    countBadge.style.display = "inline-block"; // Show badge if orders exist
                } else {
                    displayTarget.style.display = "none"; // Hide badge if 0 orders
                    countBadge.style.display = "none"; // Hide badge if 0 orders
                }
            }

            if (pendingOrders.error || totalCount === 0) {
                incomingOrdersContent.innerHTML = `
                    <div class="empty-orders text-center p-4">
                        <i class="bi bi-inbox text-muted" style="font-size: 2.5rem;"></i>
                        <p class="text-muted mt-2">No new order/s</p>
                    </div>
                `;
                return;
            }

            // 4. JAVASCRIPT LOOP: Iterate through cards tied strictly to the pending orders list
            pendingOrders.forEach(order => {
                
                // Format transaction date representation (Simulates PHP date formatting)
                const orderDateObj = new Date(order.OrderDate);
                const formattedTime = orderDateObj.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

                // Format currency representation
                const formattedPrice = Number(order.TotalAmount).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                // Handle string data modifications securely (Simulates PHP htmlspecialchars & substr)
                const safeOrderId = order.OrderID ? String(order.OrderID).replace(/"/g, '&quot;') : 'N/A';
                const safeTransactionId = order.TransactionID ? String(order.TransactionID).replace(/"/g, '&quot;') : 'N/A';
                const safeCustomer = order.CustomerName ? String(order.CustomerName).replace(/"/g, '&quot;') : 'Valued Customer';
                const safeGroupId = order.OrderGroupID ? String(order.OrderGroupID).replace(/"/g, '&quot;') : '';

                // Handle variable layout elements dynamically
                let addressHTML = "";
                if (order.DeliveryAddress) {
                    const truncatedAddress = order.DeliveryAddress.substring(0, 60).replace(/"/g, '&quot;');
                    addressHTML = `<p class="address-info m-0 text-white-50 text-truncate-2-lines"><i class="bi bi-geo-alt me-1"></i>${truncatedAddress}</p>`;
                }

                // Assemble structural HTML block element object container
                const orderCard = document.createElement("div");
                orderCard.className = "card orders-card mb-2 p-2 bg-dark-navy";
                
                orderCard.innerHTML = `
                    <div class="card-header bg-dark-navy">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-gas-cyan">Order #${safeOrderId}</span>
                            <span class="text-gas-cyan text-white-50 small">${formattedTime}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-gas-cyan">Transaction #</span>
                            <span class="fw-bold text-gas-cyan">${safeTransactionId}</span>
                        </div>
                    </div>
                    <div class="card-body text-start">
                        <div class="group-total mb-2 fw-semibold text-gas-cyan">₱${formattedPrice} (${order.ItemCount} items)</div>
                        <p class="customer-info m-0 text-white-50"><i class="bi bi-person me-1"></i>${safeCustomer}</p>
                        ${addressHTML}
                    </div>
                    <button type="button" class="btn btn-gas-primary btn-sm w-100 rounded-bottom rounded-0" onclick="acceptOrder('${safeGroupId}', '${safeTransactionId}')">
                        <i class="bi bi-check-circle me-1"></i>Accept Order
                    </button>
                `;

                incomingOrdersContent.appendChild(orderCard);
            });
        })
        .catch(error => {
            console.error("Failed to load incoming orders:", error);
            incomingOrdersContent.innerHTML = `<div class="text-danger text-center p-3">Failed to load order streams.</div>`;
        });
}

function acceptOrder(orderID, transactionID) {
    console.log(orderID);

    pendingOrderGroupId = orderID;

    document.getElementById('confirmOrderGroupIdTextDisplay').innerText = `Transaction #: ${transactionID}`;

    document.getElementById('confirmNextStatus').innerText =
        "Are you sure you want to accept the order?";

    document.getElementById('confirmNextStatusDisplay').innerText = 'Order Placed';
    
    const confirmModalElement = document.getElementById('statusConfirmModal');
    const bsConfirmModal = new bootstrap.Modal(confirmModalElement);

    bsConfirmModal.show();
}

function confirmOrderStatusSubmitBtn() {

    const btn = document.getElementById('btnConfirmStatusSubmit');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

    const modalElement = document.getElementById('statusConfirmModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);

    fetch('../api/order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            orderGroupID: pendingOrderGroupId,
            status: 'Order Placed',
            oldStatus: 'Pending Review'
        })
    })
    .then(response => response.json())
    .then(result => {

        if (result.success) {

            if (modalInstance) {
                modalInstance.hide();
            }

            showAlertBanner(result.message, 'success');

            incomingOrders(selectedBranchID);
            // location.reload();

        } else {

            showAlertBanner(
                'Failed to update status: ' + result.error,
                'danger'
            );
        }

        btn.disabled = false;
        btn.innerHTML = 'Yes, Update It';
    })
    .catch(error => {

        console.error('Update operation failure:', error);

        showAlertBanner(
            'A network routing crash occurred.',
            'danger'
        );

        btn.disabled = false;
        btn.innerHTML = 'Yes, Update It';
    });
}