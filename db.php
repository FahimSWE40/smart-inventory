<?php
// ============================================
// DATABASE CONNECTION — Smart Inventory Predictor
// ============================================
// Edit these values to match your XAMPP/WAMP setup

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // default XAMPP has no password
define('DB_NAME', 'smart_inventory');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$conn->select_db(DB_NAME);

// Set charset
$conn->set_charset("utf8mb4");
