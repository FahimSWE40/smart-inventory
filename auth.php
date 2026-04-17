<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user role
function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Get current user name
function getUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

// Get current user id
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Require login — redirect to index if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /smart-inventory/index.php");
        exit();
    }
}

// Require specific role(s)
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array(getUserRole(), $roles)) {
        header("Location: /smart-inventory/index.php?error=unauthorized");
        exit();
    }
}

// Login user
function loginUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (!$user['is_active']) {
            return "Account is deactivated. Contact admin.";
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
    }
    return "Invalid email or password.";
}

// Logout
function logoutUser() {
    session_destroy();
    header("Location: /smart-inventory/index.php");
    exit();
}

// Get role-based dashboard URL
function getDashboardURL() {
    $role = getUserRole();
    switch ($role) {
        case 'admin':   return '/smart-inventory/admin/dashboard.php';
        case 'manager': return '/smart-inventory/manager/dashboard.php';
        case 'staff':   return '/smart-inventory/staff/dashboard.php';
        default:        return '/smart-inventory/index.php';
    }
}
?>
