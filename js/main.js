// ============================================
// SMART INVENTORY PREDICTOR — Main JS
// ============================================

// Search/filter table rows
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const table = document.getElementById(tableId);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Filter prediction cards by status
function filterPredictions(status) {
    const cards = document.querySelectorAll('.pred-card');
    const buttons = document.querySelectorAll('.filter-btn');

    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    cards.forEach(card => {
        if (status === 'all') {
            card.style.display = '';
        } else {
            card.style.display = card.dataset.status === status ? '' : 'none';
        }
    });
}

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Auto-dismiss alerts after 4 seconds
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Initialize table filters if they exist
    filterTable('searchUsers', 'usersTable');
    filterTable('searchProducts', 'productsTable');
    filterTable('searchStock', 'stockTable');
});
