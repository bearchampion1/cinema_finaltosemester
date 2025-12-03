<?php
// Authentication helpers for admin area
require_once __DIR__ . '/../config.php';
session_start();

// Ensure variable exists
if (!isset($ADMIN_TEST_MODE)) $ADMIN_TEST_MODE = false;

function is_admin_logged_in() {
    return !empty($_SESSION['is_admin']);
}

function require_admin() {
    global $ADMIN_TEST_MODE;
    if ($ADMIN_TEST_MODE) {
        // In test mode, allow access without credentials
        $_SESSION['is_admin'] = true;
        return;
    }
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function ensure_admin_table_exists(PDO $pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
}

function check_if_password_set($pdo, $username) {
    $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE username = :u');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && !empty($row['password_hash']);
}

?>
