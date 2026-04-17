<?php
require_once 'auth.php';
require_once 'db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: " . getDashboardURL());
    exit();
}

$error = '';

// Handle login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($conn, $email, $password);
    if ($result === true) {
        header("Location: " . getDashboardURL());
        exit();
    } else {
        $error = $result;
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = "You don't have permission to access that page.";
}

if (isset($_GET['registered'])) {
    $success = "Account created! Please log in.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Smart Inventory Predictor</title>
    <link rel="stylesheet" href="/smart-inventory/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <span class="logo-icon">&#9883;</span>
                    <h1>StockAI</h1>
                    <p>Smart Inventory Predictor</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="admin@inventory.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn btn-primary">Sign In &rarr;</button>
                </form>

                <div class="register-link">
                    Don't have an account? <a href="/smart-inventory/register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
