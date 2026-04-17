<?php
// ============================================
// DATABASE SETUP — Run this ONCE to create tables
// ============================================
// Visit: http://localhost/smart-inventory/setup.php

require_once 'db.php';

$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','manager','staff') DEFAULT 'staff',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        sku VARCHAR(50) UNIQUE NOT NULL,
        category VARCHAR(100),
        unit_price DECIMAL(10,2) DEFAULT 0.00,
        current_stock INT DEFAULT 0,
        min_stock_level INT DEFAULT 10,
        lead_time_days INT DEFAULT 7,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    "CREATE TABLE IF NOT EXISTS stock_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        type ENUM('in','out') NOT NULL,
        quantity INT NOT NULL,
        reason VARCHAR(255),
        logged_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    "CREATE TABLE IF NOT EXISTS predictions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        avg_daily_consumption DECIMAL(10,2) DEFAULT 0,
        days_until_stockout INT DEFAULT 0,
        predicted_stockout_date DATE,
        suggested_order_qty INT DEFAULT 0,
        trend ENUM('stable','increasing','decreasing') DEFAULT 'stable',
        generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )"
];

$success = true;
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error . "<br>";
        $success = false;
    }
}

// Create default admin account
$admin_email = 'admin@inventory.com';
$check = $conn->query("SELECT id FROM users WHERE email = '$admin_email'");

if ($check->num_rows === 0) {
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('System Admin', '$admin_email', '$hashed', 'admin')");
}

// Insert sample data
$check_products = $conn->query("SELECT COUNT(*) as cnt FROM products");
$row = $check_products->fetch_assoc();

if ((int)$row['cnt'] === 0) {
    // Create a manager and staff user
    $mgr_pass = password_hash('manager123', PASSWORD_DEFAULT);
    $staff_pass = password_hash('staff123', PASSWORD_DEFAULT);

    $conn->query("INSERT IGNORE INTO users (name, email, password, role) VALUES ('Sarah Manager', 'manager@inventory.com', '$mgr_pass', 'manager')");
    $conn->query("INSERT IGNORE INTO users (name, email, password, role) VALUES ('John Staff', 'staff@inventory.com', '$staff_pass', 'staff')");

    // Get admin id
    $admin_res = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
    $admin_id = $admin_res->fetch_assoc()['id'];

    // Sample products
    $products = [
        "('Printer Paper A4', 'PP-101', 'Office Supplies', 12.50, 450, 50, 5, $admin_id)",
        "('Ink Cartridge Black', 'IC-201', 'Office Supplies', 35.00, 23, 10, 7, $admin_id)",
        "('Hand Sanitizer 500ml', 'HS-301', 'Hygiene', 8.75, 120, 30, 3, $admin_id)",
        "('USB Flash Drive 32GB', 'UF-401', 'Electronics', 15.00, 85, 20, 10, $admin_id)",
        "('Notebooks Spiral', 'NS-501', 'Office Supplies', 4.50, 200, 40, 5, $admin_id)",
        "('Wireless Mouse', 'WM-601', 'Electronics', 25.00, 12, 15, 14, $admin_id)",
        "('Cleaning Spray', 'CS-701', 'Hygiene', 6.00, 65, 20, 3, $admin_id)",
        "('Ethernet Cable 2m', 'EC-801', 'Electronics', 9.00, 40, 10, 7, $admin_id)"
    ];

    foreach ($products as $p) {
        $conn->query("INSERT INTO products (name, sku, category, unit_price, current_stock, min_stock_level, lead_time_days, created_by) VALUES $p");
    }

    // Sample stock movements (last 30 days)
    $product_ids = [];
    $res = $conn->query("SELECT id FROM products ORDER BY id");
    while ($r = $res->fetch_assoc()) {
        $product_ids[] = $r['id'];
    }

    $staff_res = $conn->query("SELECT id FROM users WHERE role='staff' LIMIT 1");
    $staff_id = $staff_res->fetch_assoc()['id'];

    // Generate realistic stock out movements over 30 days
    $daily_usage = [15, 3, 8, 4, 10, 1, 5, 3]; // avg daily usage per product
    for ($day = 30; $day >= 1; $day--) {
        $date = date('Y-m-d H:i:s', strtotime("-$day days"));
        foreach ($product_ids as $idx => $pid) {
            $usage = $daily_usage[$idx];
            // Add some variance
            $actual = max(0, $usage + rand(-2, 3));
            if ($actual > 0) {
                $conn->query("INSERT INTO stock_movements (product_id, type, quantity, reason, logged_by, created_at)
                    VALUES ($pid, 'out', $actual, 'Daily usage', $staff_id, '$date')");
            }
        }
        // Occasional restocks
        if ($day % 7 === 0) {
            foreach ($product_ids as $idx => $pid) {
                $restock = $daily_usage[$idx] * 7;
                $conn->query("INSERT INTO stock_movements (product_id, type, quantity, reason, logged_by, created_at)
                    VALUES ($pid, 'in', $restock, 'Weekly restock', $staff_id, '$date')");
            }
        }
    }
}

if ($success) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup Complete</title>";
    echo "<style>
        body { font-family: 'Segoe UI', sans-serif; background: #0a0e17; color: #e0e0e0; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #141925; border: 1px solid #1e2738; border-radius: 16px; padding: 50px; text-align: center; max-width: 500px; }
        h1 { color: #00e5a0; font-size: 28px; }
        p { color: #8892a4; line-height: 1.8; }
        .creds { background: #0d1117; border: 1px solid #1e2738; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: left; }
        .creds code { color: #00e5a0; }
        a { color: #00e5a0; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 20px; padding: 12px 30px; border: 2px solid #00e5a0; border-radius: 8px; transition: all 0.3s; }
        a:hover { background: #00e5a0; color: #0a0e17; }
    </style></head><body>";
    echo "<div class='card'>";
    echo "<h1>&#10003; Setup Complete</h1>";
    echo "<p>Database tables created and sample data inserted.</p>";
    echo "<div class='creds'>";
    echo "<p><strong>Default Accounts:</strong></p>";
    echo "<p>Admin: <code>admin@inventory.com</code> / <code>admin123</code></p>";
    echo "<p>Manager: <code>manager@inventory.com</code> / <code>manager123</code></p>";
    echo "<p>Staff: <code>staff@inventory.com</code> / <code>staff123</code></p>";
    echo "</div>";
    echo "<a href='index.php'>Go to Login &rarr;</a>";
    echo "</div></body></html>";
}

$conn->close();
?>
