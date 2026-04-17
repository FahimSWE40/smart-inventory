<?php
// Shared navigation component
function renderNav($active = '') {
    $role = getUserRole();
    $name = getUserName();
    $dashUrl = getDashboardURL();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Inventory Predictor</title>
    <link rel="stylesheet" href="/smart-inventory/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-icon">&#9883;</span>
                <span class="logo-text">StockAI</span>
            </div>
            <span class="role-badge role-<?= $role ?>"><?= ucfirst($role) ?></span>
        </div>

        <ul class="nav-links">
            <li class="<?= $active === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= $dashUrl ?>">
                    <span class="nav-icon">&#9632;</span> Dashboard
                </a>
            </li>

            <?php if ($role === 'admin'): ?>
            <li class="<?= $active === 'users' ? 'active' : '' ?>">
                <a href="/smart-inventory/admin/manage_users.php">
                    <span class="nav-icon">&#9862;</span> Manage Users
                </a>
            </li>
            <li class="<?= $active === 'products' ? 'active' : '' ?>">
                <a href="/smart-inventory/admin/manage_products.php">
                    <span class="nav-icon">&#9881;</span> Products
                </a>
            </li>
            <li class="<?= $active === 'predictions' ? 'active' : '' ?>">
                <a href="/smart-inventory/admin/predictions.php">
                    <span class="nav-icon">&#9733;</span> Predictions
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'manager'): ?>
            <li class="<?= $active === 'products' ? 'active' : '' ?>">
                <a href="/smart-inventory/manager/manage_products.php">
                    <span class="nav-icon">&#9881;</span> Products
                </a>
            </li>
            <li class="<?= $active === 'predictions' ? 'active' : '' ?>">
                <a href="/smart-inventory/manager/predictions.php">
                    <span class="nav-icon">&#9733;</span> Predictions
                </a>
            </li>
            <li class="<?= $active === 'stock' ? 'active' : '' ?>">
                <a href="/smart-inventory/manager/stock_log.php">
                    <span class="nav-icon">&#8693;</span> Stock Log
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'staff'): ?>
            <li class="<?= $active === 'stock' ? 'active' : '' ?>">
                <a href="/smart-inventory/staff/log_stock.php">
                    <span class="nav-icon">&#8693;</span> Log Stock
                </a>
            </li>
            <li class="<?= $active === 'inventory' ? 'active' : '' ?>">
                <a href="/smart-inventory/staff/inventory.php">
                    <span class="nav-icon">&#9881;</span> Inventory
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer">
            <div class="user-info">
                <span class="user-avatar"><?= strtoupper(substr($name, 0, 1)) ?></span>
                <span class="user-name"><?= htmlspecialchars($name) ?></span>
            </div>
            <a href="/smart-inventory/logout.php" class="logout-btn">Logout &rarr;</a>
        </div>
    </nav>
    <main class="main-content">
<?php
}

function renderFooter() {
?>
    </main>
    <script src="/smart-inventory/js/main.js"></script>
</body>
</html>
<?php
}
?>
