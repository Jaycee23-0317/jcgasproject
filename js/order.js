let reviewMap = null;
let reviewMapInitialized = false;
let pendingOrderGroupId = null;
let pendingOldStatus = '';
let pendingNextStatus = '';
let cancelledReason = '';
    
function viewOrderDetails(orderID) {
    const loaderArea = document.getElementById('modalLoaderArea');
    const contentArea = document.getElementById('modalContentArea');
    
    if (loaderArea) loaderArea.style.display = 'block';
    if (contentArea) contentArea.style.display = 'none';
    document.getElementById('cancelReasonCol').style.display = 'none';

    const myModalEl = document.getElementById('orderModalDetails');
    const modalInstance = bootstrap.Modal.getOrCreateInstance(myModalEl);
    modalInstance.show();

    const orderData = ordersTable.row(orderID).data();
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

            if (orderData.Status === 'Cancelled') {
                document.getElementById('cancelReasonCol').style.display = 'block';
                document.getElementById('modalCancelReasonDisplay').innerText = orderData.CancelReason;
            }

            console.log(orderData);

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

function updateOrderStatus(orderGroupId, nextStatus, oldStatus) {
    pendingOrderGroupId = orderGroupId;
    pendingNextStatus = nextStatus;
    pendingOldStatus = oldStatus;

    document.getElementById('confirmOrderGroupIdDisplay').innerText = orderGroupId;

    const reasonContainer = document.getElementById('cancelReasonContainer');
    const reasonInput = document.getElementById('cancelledReason');

    // Reset reason field
    reasonInput.value = '';
    reasonInput.classList.remove('is-invalid');

    if (nextStatus === 'Order Placed') {
        document.getElementById('confirmNextStatus').innerText =
            "Are you sure you want to accept the order?";
    } 
    else if (nextStatus === 'Out for Delivery') {
        document.getElementById('confirmNextStatus').innerText =
            "Are you sure you want to dispatch this order?";
    } 
    else if (nextStatus === 'Completed') {
        document.getElementById('confirmNextStatus').innerText =
            "Are you sure you want to complete this order?";
    } 
    else if (nextStatus === 'Cancelled') {
        document.getElementById('confirmNextStatus').innerText =
            "Are you sure you want to cancel this order?";

        reasonContainer.style.display = 'block';
    } 
    else {
        document.getElementById('confirmNextStatus').innerText =
            "Are you sure you want to update order status?";
    }

    // Hide reason field for anything other than Cancelled
    if (nextStatus !== 'Cancelled') {
        reasonContainer.style.display = 'none';
    }

    document.getElementById('confirmNextStatusDisplay').innerText = nextStatus;

    const confirmModalElement = document.getElementById('statusConfirmModal');
    const bsConfirmModal = new bootstrap.Modal(confirmModalElement);

    bsConfirmModal.show();
}

function confirmOrderStatusSubmitBtn() {
    if (!pendingOrderGroupId || !pendingNextStatus) return;

    const btn = document.getElementById('btnConfirmStatusSubmit');
    const reasonInput = document.getElementById('cancelledReason');

    let cancelledReason = '';

    // Validate cancellation reason
    if (pendingNextStatus === 'Cancelled') {
        cancelledReason = reasonInput.value.trim();

        if (!cancelledReason) {
            reasonInput.classList.add('is-invalid');
            reasonInput.focus();
            return;
        }

        reasonInput.classList.remove('is-invalid');
    }

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
            status: pendingNextStatus,
            oldStatus: pendingOldStatus,
            cancelReason: cancelledReason
        })
    })
    .then(response => response.json())
    .then(result => {

        if (result.success) {

            if (modalInstance) {
                modalInstance.hide();
            }

            showAlertBanner(result.message, 'success');

            loadOrdersData();

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

function printReceiptBtn(rowIndex) {
    const orderData = ordersTable.row(rowIndex).data();

    if (!orderData) {
        showAlertBanner(
            'Unable to find the order information.',
            'danger'
        );
        return;
    }
    printReceipt(orderData);
}