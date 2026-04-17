<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
require_once '../includes/predict_engine.php';
requireRole('staff');

$engine = new PredictEngine($conn);
$stats = $engine->getSummaryStats();

// Recent movements by this staff
$uid = getUserId();
$my_movements = $conn->query("
    SELECT sm.*, p.name as product_name, p.sku
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    WHERE sm.logged_by = $uid
    ORDER BY sm.created_at DESC LIMIT 10
");

// Today's movement count
$today_count = $conn->query("
    SELECT COUNT(*) as cnt FROM stock_movements
    WHERE logged_by = $uid AND DATE(created_at) = CURDATE()
")->fetch_assoc()['cnt'];

renderNav('dashboard');
?>

<div class="page-header">
    <h1>Staff Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars(getUserName()) ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Low Stock Alerts</div>
        <div class="stat-value"><?= $stats['critical'] + $stats['warning'] ?></div>
        <div class="stat-sub"><?= $stats['critical'] ?> critical, <?= $stats['warning'] ?> warning</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">My Logs Today</div>
        <div class="stat-value"><?= $today_count ?></div>
        <div class="stat-sub">Stock movements logged</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Inventory Value</div>
        <div class="stat-value">$<?= number_format($stats['total_inventory_value'], 0) ?></div>
    </div>
</div>

<!-- Low stock alerts -->
<?php
$alerts = array_filter($stats['predictions'], fn($p) => $p['status'] !== 'healthy');
usort($alerts, fn($a, $b) => $a['days_until_stockout'] - $b['days_until_stockout']);
if (count($alerts) > 0):
?>
<div class="section-title text-red">&#9888; Low Stock Alerts <span class="count"><?= count($alerts) ?></span></div>
<div class="data-table-wrapper" style="margin-bottom:32px">
    <table class="data-table">
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Stock</th><th>Days Left</th><th>Status</th></tr>
        </thead>
        <tbody>
            <?php foreach ($alerts as $a): ?>
            <tr>
                <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($a['product']['name']) ?></td>
                <td class="mono"><?= $a['product']['sku'] ?></td>
                <td class="mono <?= $a['status'] === 'critical' ? 'text-red' : 'text-yellow' ?>"><?= $a['product']['current_stock'] ?></td>
                <td class="mono <?= $a['status'] === 'critical' ? 'text-red' : 'text-yellow' ?>"><?= $a['days_until_stockout'] ?></td>
                <td>
                    <span class="status-dot <?= $a['status'] ?>" style="display:inline-block;vertical-align:middle;margin-right:6px"></span>
                    <?= ucfirst($a['status']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- My Recent Activity -->
<div class="section-title">My Recent Activity</div>
<?php if ($my_movements->num_rows > 0): ?>
<div class="data-table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php while ($m = $my_movements->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong style="color:var(--text-primary)"><?= htmlspecialchars($m['product_name']) ?></strong>
                    <span class="mono text-muted" style="font-size:12px;margin-left:4px"><?= $m['sku'] ?></span>
                </td>
                <td>
                    <span class="<?= $m['type'] === 'in' ? 'text-green' : 'text-red' ?> mono" style="font-weight:600">
                        <?= $m['type'] === 'in' ? '&#9650; IN' : '&#9660; OUT' ?>
                    </span>
                </td>
                <td class="mono"><?= $m['quantity'] ?></td>
                <td><?= htmlspecialchars($m['reason']) ?></td>
                <td class="text-muted"><?= date('M d, H:i', strtotime($m['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="empty-state">
    <h3>No Activity Yet</h3>
    <p>Start logging stock movements from the <a href="log_stock.php">Log Stock</a> page.</p>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
