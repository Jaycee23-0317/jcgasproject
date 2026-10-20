let reviewMap = null;
let reviewMapInitialized = false;
    
function viewOrderDetails(orderID) {
    const loaderArea = document.getElementById('modalLoaderArea');
    const contentArea = document.getElementById('modalContentArea');
    const mapViewElement = document.getElementById('deliveryMapAddress');
    const loadingSpinnerElement = document.getElementById('orderDeliveryMapLoading');
    
    if (loaderArea) loaderArea.style.display = 'block';
    if (contentArea) contentArea.style.display = 'none';

    const myModalEl = document.getElementById('orderModalDetails');
    const modalInstance = bootstrap.Modal.getOrCreateInstance(myModalEl);
    modalInstance.show();

    const orderData = orderDataTable.row(orderID).data();
    const addressText = orderData.DeliveryAddress;
    const lat = orderData.DeliveryLatitude;
    const lng = orderData.DeliveryLongitude;

    if (orderData) {
        setTimeout(() => {
            document.getElementById('modalOrderGroupIDDisplay').innerText = orderData.OrderGroupID;
            document.getElementById('modalOrderIDDisplay').innerText = orderData.OrderID || 'N/A';
            document.getElementById('modalCustomerDisplay').innerText = orderData.CustomerName || 'Guest';
            document.getElementById('modalTransactionIDDisplay').innerText = orderData.TransactionID;
            document.getElementById('modalOrdertypeDisplay').innerText = orderData.OrderType;
            document.getElementById('modalStatusDisplay').innerText = orderData.Status;
            document.getElementById('modalPaymentMethodDisplay').innerText = orderData.PaymentMethod;

            const orderDate = new Date(orderData.OrderDate);
            document.getElementById('modalDateDisplay').innerText = orderDate.toLocaleDateString() + ' ' + orderDate.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalTotalDisplay').innerText = `$${parseFloat(orderData.TotalAmount).toFixed(2)}`;

            const itemsTableBody = document.getElementById('modalItemsTableBody');
            itemsTableBody.innerHTML = '';

            if (orderData.items && orderData.items.length > 0) {
                orderData.items.forEach(item => {
                    // Calculate a safe mathematical subtotal fallback calculation parameter rule
                    const itemPrice = parseFloat(item.OrderedPrice || 0);
                    const itemQty = parseInt(item.Quantity || 0);
                    const itemSubtotal = parseFloat(item.Subtotal || (itemPrice * itemQty));

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>
                        <div class="fw-bold">${item.ProductName}</div>
                        <span class="text-muted small">ID: #${item.ProductID}</span>
                    </td>
                    <td class="text-center"><span class="badge bg-light text-dark border">${item.Size || 'N/A'}</span></td>
                    <td class="text-end">$${itemPrice.toFixed(2)}</td>
                    <td class="text-center fw-bold">${itemQty}</td>
                    <td class="text-end fw-bold text-dark">$${itemSubtotal.toFixed(2)}</td>
                `;
                    itemsTableBody.appendChild(tr);
                });
            } else {
                itemsTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No products recorded for this order transaction.</td></tr>`;
            }

            document.getElementById('orderDeliveryAddress').innerText = addressText;
            document.getElementById('orderDeliveryCoords').innerText = (lat && lng) ? `${lat}, ${lng}` : '--';

            renderDeliveryAddress(lat, lng);

            if (loaderArea) loaderArea.style.display = 'none';
            if (contentArea) contentArea.style.display = 'block'; // grid matching flex formatting rows
        }, 500); 
    } else {
        // Fallback error messaging context handler
        if (loaderArea) loaderArea.innerHTML = '<p class="text-danger">Failed to capture order info.</p>';
    }
}

function renderDeliveryAddress(latValue, lngValue) {
    const mapViewElement = document.getElementById('deliveryMapAddress');
    const loadingSpinnerElement = document.getElementById('orderDeliveryMapLoading');

    if (!mapViewElement) {
        if (loadingSpinnerElement) loadingSpinnerElement.style.display = 'none';
        return;
    }

    if (reviewMap) {
        reviewMap.remove();
        reviewMap = null;
        reviewMapInitialized = false;
    }

    try {
        setTimeout(() => {
            try {
                reviewMap = L.map('deliveryMapAddress').setView([latValue, lngValue], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(reviewMap);

                L.marker([latValue, lngValue]).addTo(reviewMap);
                reviewMapInitialized = true;
                console.log(reviewMap);

                if (loadingSpinnerElement) {
                    loadingSpinnerElement.style.display = 'none';
                }

                reviewMap.invalidateSize();

            } catch (asyncError) {
                console.error("Delayed map initialization failure:", asyncError);
                if (loadingSpinnerElement) {
                    loadingSpinnerElement.innerHTML = `<span class='text-danger small'><i class='bi bi-x-circle'></i> Map error.</span>`;
                }
            }
        }, 200);

    } catch (mapError) {
        console.error("Map cleanup runtime failure:", mapError);
    }
}

function showPageLoading(text) {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (!overlay) return;
    if (text) document.getElementById('pageLoadingText').innerText = text;
    overlay.style.display = 'flex';
}

function hidePageLoading() {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (overlay) overlay.style.display = 'none';
}