<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
requireRole('manager');

$movements = $conn->query("
    SELECT sm.*, p.name as product_name, p.sku, u.name as user_name
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN users u ON sm.logged_by = u.id
    ORDER BY sm.created_at DESC
    LIMIT 100
");

renderNav('stock');
?>

<div class="page-header">
    <h1>Stock Movement Log</h1>
    <p>History of all inventory movements</p>
</div>

<div class="toolbar">
    <div class="section-title">Recent Movements</div>
    <input type="text" id="searchStock" class="search-input" placeholder="Search...">
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="stockTable">
        <thead>
            <tr>
                <th>Product</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Reason</th>
                <th>Logged By</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($m = $movements->fetch_assoc()): ?>
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
                <td><?= htmlspecialchars($m['user_name'] ?? 'System') ?></td>
                <td class="text-muted"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
