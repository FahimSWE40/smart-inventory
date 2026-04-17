<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
requireRole('manager');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $sku = trim($_POST['sku']);
        $category = trim($_POST['category']);
        $price = (float)$_POST['unit_price'];
        $stock = (int)$_POST['current_stock'];
        $min_stock = (int)$_POST['min_stock_level'];
        $lead_time = (int)$_POST['lead_time_days'];
        $uid = getUserId();

        $stmt = $conn->prepare("INSERT INTO products (name, sku, category, unit_price, current_stock, min_stock_level, lead_time_days, created_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssdiiis", $name, $sku, $category, $price, $stock, $min_stock, $lead_time, $uid);
        if ($stmt->execute()) {
            $success = "Product '$name' added.";
        } else {
            $error = "Failed — SKU may already exist.";
        }
    }

    if ($_POST['action'] === 'update') {
        $pid = (int)$_POST['product_id'];
        $name = trim($_POST['name']);
        $sku = trim($_POST['sku']);
        $category = trim($_POST['category']);
        $price = (float)$_POST['unit_price'];
        $min_stock = (int)$_POST['min_stock_level'];
        $lead_time = (int)$_POST['lead_time_days'];

        $stmt = $conn->prepare("UPDATE products SET name=?, sku=?, category=?, unit_price=?, min_stock_level=?, lead_time_days=? WHERE id=?");
        $stmt->bind_param("sssdiis", $name, $sku, $category, $price, $min_stock, $lead_time, $pid);
        $stmt->execute() ? $success = "Updated." : $error = "Failed.";
    }

    if ($_POST['action'] === 'delete') {
        $pid = (int)$_POST['product_id'];
        $conn->prepare("DELETE FROM products WHERE id = ?")->bind_param("i", $pid);
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $success = "Product deleted.";
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY name ASC");

$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $estmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $estmt->bind_param("i", $eid);
    $estmt->execute();
    $editing = $estmt->get_result()->fetch_assoc();
}

renderNav('products');
?>

<div class="page-header">
    <h1>Products</h1>
    <p>Manage inventory products</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="form-card">
    <h3 style="margin-bottom:20px"><?= $editing ? 'Edit Product' : 'Add Product' ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="product_id" value="<?= $editing['id'] ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" required value="<?= htmlspecialchars($editing['sku'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?= htmlspecialchars($editing['category'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Unit Price ($)</label>
                <input type="number" name="unit_price" step="0.01" value="<?= $editing['unit_price'] ?? '0' ?>">
            </div>
        </div>
        <div class="form-row">
            <?php if (!$editing): ?>
            <div class="form-group">
                <label>Initial Stock</label>
                <input type="number" name="current_stock" value="0">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Min Stock Level</label>
                <input type="number" name="min_stock_level" value="<?= $editing['min_stock_level'] ?? '10' ?>">
            </div>
        </div>
        <div class="form-group" style="max-width:calc(50% - 8px)">
            <label>Lead Time (days)</label>
            <input type="number" name="lead_time_days" value="<?= $editing['lead_time_days'] ?? '7' ?>">
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add Product' ?></button>
            <?php if ($editing): ?><a href="manage_products.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="toolbar">
    <div class="section-title">All Products <span class="count"><?= $products->num_rows ?></span></div>
    <input type="text" id="searchProducts" class="search-input" placeholder="Search...">
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="productsTable">
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($p = $products->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($p['name']) ?></td>
                <td class="mono"><?= $p['sku'] ?></td>
                <td><?= $p['category'] ?></td>
                <td class="mono">$<?= number_format($p['unit_price'], 2) ?></td>
                <td class="mono <?= $p['current_stock'] <= $p['min_stock_level'] ? 'text-red' : 'text-green' ?>"><?= $p['current_stock'] ?></td>
                <td>
                    <div class="btn-group">
                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
