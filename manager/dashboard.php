<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
require_once '../includes/predict_engine.php';
requireRole('manager');

$engine = new PredictEngine($conn);
$stats = $engine->getSummaryStats();

renderNav('dashboard');
?>

<div class="page-header">
    <h1>Manager Dashboard</h1>
    <p>Inventory overview and stock intelligence</p>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Critical Stock</div>
        <div class="stat-value"><?= $stats['critical'] ?></div>
        <div class="stat-sub">Needs immediate attention</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Warning</div>
        <div class="stat-value"><?= $stats['warning'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Inventory Value</div>
        <div class="stat-value">$<?= number_format($stats['total_inventory_value'], 0) ?></div>
    </div>
</div>

<?php
$critical_items = array_filter($stats['predictions'], fn($p) => $p['status'] !== 'healthy');
usort($critical_items, fn($a, $b) => $a['days_until_stockout'] - $b['days_until_stockout']);
?>

<div class="section-title">Items Requiring Attention <span class="count"><?= count($critical_items) ?></span></div>

<?php if (count($critical_items) > 0): ?>
<div class="predictions-grid">
    <?php foreach ($critical_items as $pred): ?>
    <div class="pred-card" data-status="<?= $pred['status'] ?>">
        <div class="pred-card-header">
            <div>
                <div class="pred-product-name"><?= htmlspecialchars($pred['product']['name']) ?></div>
                <div class="pred-sku"><?= htmlspecialchars($pred['product']['sku']) ?></div>
            </div>
            <div class="status-dot <?= $pred['status'] ?>"></div>
        </div>
        <div class="pred-stats">
            <div class="pred-stat">
                <div class="pred-stat-label">Stock</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['product']['current_stock'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Days Left</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['days_until_stockout'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Order Qty</div>
                <div class="pred-stat-value text-blue"><?= $pred['suggested_order_qty'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Trend</div>
                <span class="trend-badge <?= $pred['trend'] ?>"><?= ucfirst($pred['trend']) ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <h3>All Clear!</h3>
    <p>No products need attention right now.</p>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
