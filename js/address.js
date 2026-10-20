
let pinMap = null;
let pinMarker = null;
let pinMapInitialized = false;

function useSavedAddress() {
    const addressText = document.getElementById('savedAddressText').innerText;
    const coordsText = document.getElementById('savedAddressCoords').innerText;

    document.getElementById('currentDeliveryAddressText').innerText = addressText;
    document.getElementById('currentDeliveryCoords').innerText = coordsText;

    document.getElementById('deliveryAddressText').value = addressText;

    const parts = coordsText.split(',').map(s => s.trim());
    if (parts.length === 2) {
        document.getElementById('deliveryLatitude').value = parseFloat(parts[0]) || '';
        document.getElementById('deliveryLongitude').value = parseFloat(parts[1]) || '';
    }

    setActiveDeliveryBtn('home');
    updateCustomerCart();
}

function setActiveDeliveryBtn(source) {
    const homeBtn = document.getElementById('homeAddressBtn');
    const pinBtn = document.getElementById('pinMapBtn');
    if (homeBtn) homeBtn.classList.toggle('active', source === 'home');
    if (pinBtn) pinBtn.classList.toggle('active', source === 'pin');
}

function applyDeliveryAddress(addressText, lat, lng) {
    if (!addressText) addressText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('currentDeliveryAddressText').innerText = addressText;
    document.getElementById('currentDeliveryCoords').innerText = `${lat}, ${lng}`;

    document.getElementById('deliveryAddressText').value = addressText;
    document.getElementById('deliveryLatitude').value = lat;
    document.getElementById('deliveryLongitude').value = lng;

    setActiveDeliveryBtn('pin');
    updateCustomerCart();
}

function openPinMapModal() {
    const modalEl = document.getElementById('pinMapModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    if (pinMap) {
        pinMap.remove();
        pinMap = null;
        pinMarker = null;
        pinMapInitialized = false;
    }

    document.getElementById('pinMapLoading').style.display = 'block';
    const mapEl = document.getElementById('pinMapModalMap');
    document.getElementById('pinMapModalAddressText').innerText = 'Drag the pin to set your delivery address.';
    document.getElementById('pinMapModalCoords').innerText = '--';

    modal.show();

    modalEl.addEventListener('shown.bs.modal', function onShown() {
        modalEl.removeEventListener('shown.bs.modal', onShown);
        mapEl.style.display = 'block';
        setTimeout(() => {
            initPinMapModal();
            if (pinMap) pinMap.invalidateSize();
            document.getElementById('pinMapLoading').style.display = 'none';
        }, 150);
    });
}

function initPinMapModal() {
    const mapEl = document.getElementById('pinMapModalMap');
    if (!mapEl || typeof L === 'undefined' || pinMapInitialized) return;

    let baseLat = 15.1441;
    let baseLng = 120.5887;
    const coordsEl = document.getElementById('savedAddressCoords');
    if (coordsEl) {
        const parts = coordsEl.innerText.split(',').map(s => s.trim());
        if (parts.length === 2) {
            const lat = parseFloat(parts[0]);
            const lng = parseFloat(parts[1]);
            if (!isNaN(lat)) baseLat = lat;
            if (!isNaN(lng)) baseLng = lng;
        }
    }

    pinMap = L.map('pinMapModalMap').setView([baseLat, baseLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(pinMap);

    pinMarker = L.marker([baseLat, baseLng], { draggable: true }).addTo(pinMap);

    reverseGeocodePinModal(baseLat, baseLng);

    pinMarker.on('dragend', function() {
        const pos = pinMarker.getLatLng();
        document.getElementById('pinMapModalCoords').innerText = `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`;
        reverseGeocodePinModal(pos.lat, pos.lng);
    });

    pinMapInitialized = true;
}

function reverseGeocodePinModal(lat, lng) {
    const revUrl = `https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&lat=${lat}&lon=${lng}`;
    fetch(revUrl, {
        headers: { 'Accept': 'application/json' }
    })
        .then(response => response.json())
        .then(data => {
            const textEl = document.getElementById('pinMapModalAddressText');
            if (!textEl) return;
            const addressText = (data && data.display_name) ? data.display_name : `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            textEl.innerText = addressText;
        })
        .catch(err => {
            console.error('Pin reverse geocoding error:', err);
            const textEl = document.getElementById('pinMapModalAddressText');
            if (textEl) textEl.innerText = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        });
}

function confirmPin() {
    if (!pinMarker) return;
    const pos = pinMarker.getLatLng();
    const lat = pos.lat.toFixed(8);
    const lng = pos.lng.toFixed(8);
    const addressText = document.getElementById('pinMapModalAddressText').innerText;

    applyDeliveryAddress(addressText, lat, lng);

    // Close the pin modal
    const pinModal = bootstrap.Modal.getInstance(document.getElementById('pinMapModal'));
    if (pinModal) pinModal.hide();
}

