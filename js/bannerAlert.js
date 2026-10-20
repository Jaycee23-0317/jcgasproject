function showAlertBanner(message, type = 'success') {
    const container = document.getElementById('alertBannerContainer');

    console.log(message);
    if (!container) return;

    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow d-flex align-items-center mb-2`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <i class="bi ${icon} me-2 fs-5"></i>
        <div>${message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    container.appendChild(alertDiv);

    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 3000);
}
