/**
 * College Notes Management System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 2. Responsive Sidebar Toggle for mobile/tablet
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebarWrapper');
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');

    // Create backdrop overlay if needed
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay && sidebarWrapper) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    if (sidebarToggle && sidebarWrapper) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebarWrapper.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (sidebarCloseBtn && sidebarWrapper) {
        sidebarCloseBtn.addEventListener('click', () => {
            sidebarWrapper.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebarWrapper.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // 3. Confirm Delete Prompts
    const deleteForms = document.querySelectorAll('.confirm-delete-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const item = form.getAttribute('data-item-name') || 'this item';
            if (!confirm(`Are you sure you want to permanently delete ${item}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });
});
