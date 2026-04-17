<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
require_once '../includes/predict_engine.php';
requireRole('staff');

$engine = new PredictEngine($conn);
$predictions = $engine->generateAllPredictions();

renderNav('inventory');
?>

<div class="page-header">
    <h1>Inventory Overview</h1>
    <p>Current stock levels and status indicators</p>
</div>

<div class="toolbar">
    <div class="section-title">All Products <span class="count"><?= count($predictions) ?></span></div>
    <div style="display:flex;gap:12px;align-items:center">
        <input type="text" id="searchProducts" class="search-input" placeholder="Search products...">
        <div class="filter-group">
            <button class="filter-btn active" onclick="filterPredictions('all')">All</button>
            <button class="filter-btn" onclick="filterPredictions('critical')">Critical</button>
            <button class="filter-btn" onclick="filterPredictions('warning')">Warning</button>
        </div>
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
                <div class="pred-stat-label">In Stock</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['product']['current_stock'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Min Level</div>
                <div class="pred-stat-value text-muted"><?= $pred['product']['min_stock_level'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Days Left</div>
                <div class="pred-stat-value <?= $pred['status'] ?>"><?= $pred['days_until_stockout'] >= 999 ? '∞' : $pred['days_until_stockout'] ?></div>
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
