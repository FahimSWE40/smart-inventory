<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
require_once '../includes/predict_engine.php';
requireRole('admin');

$engine = new PredictEngine($conn);
$stats = $engine->getSummaryStats();

// User counts
$user_counts = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$users_by_role = [];
while ($r = $user_counts->fetch_assoc()) {
    $users_by_role[$r['role']] = $r['cnt'];
}
$total_users = array_sum($users_by_role);

// Recent movements
$recent = $conn->query("
    SELECT sm.*, p.name as product_name, p.sku, u.name as user_name
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN users u ON sm.logged_by = u.id
    ORDER BY sm.created_at DESC LIMIT 8
");

renderNav('dashboard');
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>System overview and inventory intelligence</p>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= $stats['total_products'] ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Critical Stock</div>
        <div class="stat-value"><?= $stats['critical'] ?></div>
        <div class="stat-sub">Stockout within 7 days</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Warning</div>
        <div class="stat-value"><?= $stats['warning'] ?></div>
        <div class="stat-sub">Stockout within 30 days</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Healthy</div>
        <div class="stat-value"><?= $stats['healthy'] ?></div>
        <div class="stat-sub">30+ days of stock</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-sub"><?= ($users_by_role['admin'] ?? 0) ?> admin, <?= ($users_by_role['manager'] ?? 0) ?> mgr, <?= ($users_by_role['staff'] ?? 0) ?> staff</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Inventory Value</div>
        <div class="stat-value">$<?= number_format($stats['total_inventory_value'], 0) ?></div>
        <div class="stat-sub">Total stock value</div>
    </div>
</div>

<!-- Critical items -->
<?php
$critical_items = array_filter($stats['predictions'], fn($p) => $p['status'] === 'critical');
if (count($critical_items) > 0):
?>
<div class="section-title text-red" style="margin-top: 12px;">
    &#9888; Critical Items <span class="count"><?= count($critical_items) ?></span>
</div>
<div class="predictions-grid">
    <?php foreach ($critical_items as $pred): ?>
    <div class="pred-card" data-status="critical">
        <div class="pred-card-header">
            <div>
                <div class="pred-product-name"><?= htmlspecialchars($pred['product']['name']) ?></div>
                <div class="pred-sku"><?= htmlspecialchars($pred['product']['sku']) ?></div>
            </div>
            <div class="status-dot critical"></div>
        </div>
        <div class="pred-stats">
            <div class="pred-stat">
                <div class="pred-stat-label">Stock</div>
                <div class="pred-stat-value critical"><?= $pred['product']['current_stock'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Days Left</div>
                <div class="pred-stat-value critical"><?= $pred['days_until_stockout'] ?></div>
            </div>
            <div class="pred-stat">
                <div class="pred-stat-label">Order Qty</div>
                <div class="pred-stat-value"><?= $pred['suggested_order_qty'] ?></div>
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
<?php endif; ?>

<!-- Recent Movements -->
<div class="section-title" style="margin-top: 12px;">Recent Stock Movements</div>
<div class="data-table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Reason</th>
                <th>By</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($mv = $recent->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong style="color:var(--text-primary)"><?= htmlspecialchars($mv['product_name']) ?></strong>
                    <span class="text-muted mono" style="font-size:12px;margin-left:6px"><?= $mv['sku'] ?></span>
                </td>
                <td>
                    <span class="<?= $mv['type'] === 'in' ? 'text-green' : 'text-red' ?> mono" style="font-weight:600">
                        <?= $mv['type'] === 'in' ? '&#9650; IN' : '&#9660; OUT' ?>
                    </span>
                </td>
                <td class="mono"><?= $mv['quantity'] ?></td>
                <td><?= htmlspecialchars($mv['reason']) ?></td>
                <td><?= htmlspecialchars($mv['user_name'] ?? 'System') ?></td>
                <td class="text-muted"><?= date('M d, H:i', strtotime($mv['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
