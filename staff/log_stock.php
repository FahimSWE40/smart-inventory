<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
requireRole('staff');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $type = $_POST['type'];
    $quantity = (int)$_POST['quantity'];
    $reason = trim($_POST['reason']);
    $uid = getUserId();

    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0.";
    } elseif (!in_array($type, ['in', 'out'])) {
        $error = "Invalid movement type.";
    } else {
        // Check product exists
        $pstmt = $conn->prepare("SELECT id, current_stock, name FROM products WHERE id = ?");
        $pstmt->bind_param("i", $product_id);
        $pstmt->execute();
        $product = $pstmt->get_result()->fetch_assoc();

        if (!$product) {
            $error = "Product not found.";
        } elseif ($type === 'out' && $quantity > $product['current_stock']) {
            $error = "Cannot remove $quantity units. Only {$product['current_stock']} in stock.";
        } else {
            // Insert movement
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, type, quantity, reason, logged_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isisi", $product_id, $type, $quantity, $reason, $uid);

            if ($stmt->execute()) {
                // Update product stock
                if ($type === 'in') {
                    $conn->query("UPDATE products SET current_stock = current_stock + $quantity WHERE id = $product_id");
                } else {
                    $conn->query("UPDATE products SET current_stock = current_stock - $quantity WHERE id = $product_id");
                }
                $type_label = $type === 'in' ? 'added to' : 'removed from';
                $success = "$quantity units $type_label {$product['name']}.";
            } else {
                $error = "Failed to log movement.";
            }
        }
    }
}

$products = $conn->query("SELECT id, name, sku, current_stock FROM products ORDER BY name ASC");

// Recent movements
$uid = getUserId();
$recent = $conn->query("
    SELECT sm.*, p.name as product_name, p.sku
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    WHERE sm.logged_by = $uid
    ORDER BY sm.created_at DESC LIMIT 20
");

renderNav('stock');
?>

<div class="page-header">
    <h1>Log Stock Movement</h1>
    <p>Record stock in (receiving) or stock out (usage/dispatch)</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="form-card">
    <h3 style="margin-bottom:20px">New Movement</h3>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Product</label>
                <select name="product_id" required>
                    <option value="">Select product...</option>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['sku'] ?>) — Stock: <?= $p['current_stock'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" required>
                    <option value="in">&#9650; Stock In (Receiving)</option>
                    <option value="out">&#9660; Stock Out (Usage)</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label>Reason / Note</label>
                <input type="text" name="reason" placeholder="e.g. Daily usage, Restock delivery">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Log Movement &rarr;</button>
    </form>
</div>

<!-- Recent Logs -->
<div class="section-title" style="margin-top:12px">My Recent Logs</div>
<div class="data-table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php if ($recent->num_rows === 0): ?>
            <tr><td colspan="5" class="text-muted" style="text-align:center;padding:30px">No movements logged yet.</td></tr>
            <?php endif; ?>
            <?php while ($m = $recent->fetch_assoc()): ?>
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
                <td class="text-muted"><?= date('M d, Y H:i', strtotime($m['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
