<?php
require_once '../auth.php';
require_once '../db.php';
require_once '../includes/nav.php';
requireRole('admin');

$error = '';
$success = '';

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if (strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $error = "Please fill all fields correctly (password min 6 chars).";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $hashed, $role);
                if ($stmt->execute()) {
                    $success = "User '$name' created successfully.";
                } else {
                    $error = "Failed to create user.";
                }
            }
        }
    }

    if ($_POST['action'] === 'update') {
        $uid = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, is_active=? WHERE id=?");
        $stmt->bind_param("sssii", $name, $email, $role, $is_active, $uid);
        if ($stmt->execute()) {
            $success = "User updated.";
        } else {
            $error = "Update failed.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid === getUserId()) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            if ($stmt->execute()) {
                $success = "User deleted.";
            }
        }
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

// Check if editing
$editing = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $estmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $estmt->bind_param("i", $eid);
    $estmt->execute();
    $editing = $estmt->get_result()->fetch_assoc();
}

renderNav('users');
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <p>Create, edit, and manage system users and roles</p>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Create / Edit Form -->
<div class="form-card">
    <h3 style="margin-bottom:20px;"><?= $editing ? 'Edit User' : 'Create New User' ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?>
            <input type="hidden" name="user_id" value="<?= $editing['id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editing['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <?php if (!$editing): ?>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" <?= $editing ? '' : 'required' ?> placeholder="Min 6 characters">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="staff" <?= ($editing['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="manager" <?= ($editing['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="admin" <?= ($editing['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
        </div>

        <?php if ($editing): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?= $editing['is_active'] ? 'checked' : '' ?>>
                Account Active
            </label>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary"><?= $editing ? 'Update User' : 'Create User' ?></button>
            <?php if ($editing): ?>
                <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="toolbar">
    <div class="section-title">All Users</div>
    <input type="text" id="searchUsers" class="search-input" placeholder="Search users...">
</div>

<div class="data-table-wrapper">
    <table class="data-table" id="usersTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($u['name']) ?></td>
                <td class="mono" style="font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="role-badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="text-green">&#9679; Active</span>
                    <?php else: ?>
                        <span class="text-red">&#9679; Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div class="btn-group">
                        <a href="?edit=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <?php if ($u['id'] !== getUserId()): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this user?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
