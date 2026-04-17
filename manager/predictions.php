<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
require_once '../includes/predict_engine.php';
requireRole('manager');

$engine = new PredictEngine($conn);
$stats = $engine->getSummaryStats();
$predictions = $stats['predictions'];

renderNav('predictions');
?>

<div class="page-header">
    <h1>AI Predictions</h1>
    <p>Stock forecasting and reorder recommendations</p>
</div>

<div class="stats-grid">
    <div class="stat-card red">
        <div class="stat-label">Critical</div>
        <div class="stat-value"><?= $stats['critical'] ?></div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Warning</div>
        <div class="stat-value"><?= $stats['warning'] ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Healthy</div>
        <div class="stat-value"><?= $stats['healthy'] ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Inventory Value</div>
        <div class="stat-value">$<?= number_format($stats['total_inventory_value'], 0) ?></div>
    </div>
</div>

<div class="toolbar">
    <div class="section-title">Forecast <span class="count"><?= count($predictions) ?></span></div>
    <div class="filter-group">
        <button class="filter-btn active" onclick="filterPredictions('all')">All</button>
        <button class="filter-btn" onclick="filterPredictions('critical')">Critical</button>
        <button class="filter-btn" onclick="filterPredictions('warning')">Warning</button>
        <button class="filter-btn" onclick="filterPredictions('healthy')">Healthy</button>
    </div>
</div>

<div class="predictions-grid">
    <?php foreach ($predictions as $pred): ?>
    <div class="pred-card" data-status="<?= $pred['status'] ?>">
        <div class="pred-card-header">
            <div>
                <div class="pred-product-name"><?= htmlspecialchars($pred['product']['name']) ?></div>
                <div class="pred-sku"><?= $pred['product']['sku'] ?> &middot; <?= $pred['product']['category'] ?></div>
            </div>
            <div class="status-dot <?= $pred['status'] ?>"></div>
        </div>
        <div class="pred-stats">
            <div class="pred-stat">
                <div class="pred-stat-label">Stock</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['product']['current_stock'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Daily Use</div>
                <div class="pred-stat-value"><?= $pred['avg_daily_consumption'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Days Left</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['days_until_stockout'] >= 999 ? '∞' : $pred['days_until_stockout'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Order Qty</div>
                <div class="pred-stat-value text-blue"><?= $pred['suggested_order_qty'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Stockout Date</div>
                <div class="pred-stat-value" style="font-size:14px"><?= $pred['days_until_stockout'] >= 999 ? 'N/A' : date('M d, Y', strtotime($pred['predicted_stockout_date'])) ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Trend</div>
                <span class="trend-badge <?= $pred['trend'] ?>">
                    <?= $pred['trend'] === 'increasing' ? '&#9650;' : ($pred['trend'] === 'decreasing' ? '&#9660;' : '&#9644;') ?>
                    <?= ucfirst($pred['trend']) ?>
                </span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php renderFooter(); ?>
