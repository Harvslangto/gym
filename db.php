<?php
// Disable error display for production security (prevents path disclosure)
ini_set('display_errors', 0);

// 1. Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
// Prevent browser caching of sensitive pages (Fixes "Back button" leak after logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' // Enable Secure flag only if using HTTPS
]);
session_start();

// 2. Session Timeout (30 Minutes)
if (isset($_SESSION['admin_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// TODO: CHANGE THIS FOR PRODUCTION! Use a dedicated user with a strong password.
// Example: $conn = new mysqli("localhost", "gym_user", "StrongPassword123!", "gym_db");
$conn = new mysqli("localhost", "root", "", "gym_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-create tables if they don't exist (Fix for missing tables)
$conn->query("CREATE TABLE IF NOT EXISTS membership_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_unit ENUM('Month', 'Day') NOT NULL DEFAULT 'Month'
)");

$conn->query("CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    birth_date DATE,
    gender VARCHAR(10),
    membership_type VARCHAR(50),
    amount DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    status VARCHAR(20) DEFAULT 'Active',
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create Login Attempts Table for Rate Limiting
$conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
    ip_address VARCHAR(45) PRIMARY KEY,
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default membership types if empty
$chk = $conn->query("SELECT COUNT(*) as count FROM membership_types");
if ($chk && $chk->fetch_assoc()['count'] == 0) {
    $conn->query("INSERT INTO membership_types (type_name, price, duration_unit) VALUES 
        ('Regular', 999.00, 'Month'),
        ('Student', 799.00, 'Month'),
        ('Walk-in Regular', 69.00, 'Day'),
        ('Walk-in Student', 59.00, 'Day')
    ");
}

// Insert default admin if empty (admin / trizen2026)
$chk_admin = $conn->query("SELECT COUNT(*) as count FROM admin");
if ($chk_admin && $chk_admin->fetch_assoc()['count'] == 0) {
    $pass = password_hash('trizen2026', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admin (username, password) VALUES ('admin', '$pass')");
}

function logActivity($conn, $admin_id, $action, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $admin_id, $action, $description);
    $stmt->execute();
}

function checkAndLogExpirations($conn, $admin_id) {
    // Find members who are active but past their end date
    $result = $conn->query("SELECT id, full_name FROM members WHERE end_date < CURDATE() AND status = 'Active'");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $conn->query("UPDATE members SET status = 'Expired' WHERE id = " . $row['id']);
            logActivity($conn, $admin_id, 'System', "Membership expired automatically for: " . $row['full_name']);
        }
    }
}
?>
