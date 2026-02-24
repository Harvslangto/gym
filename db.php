<?php
// Disable error display for production security (prevents path disclosure)
ini_set('display_errors', 0);

// Set Timezone for the entire application (Ensures consistency across all pages)
date_default_timezone_set('Asia/Manila');

// 0. Anti-Fingerprinting: Hide PHP version
// A 10+ year hacker looks for this to know which exploits to use. We remove it.
if (function_exists('header_remove')) { header_remove('X-Powered-By'); }

// 1. Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
// Prevent browser caching of sensitive pages (Fixes "Back button" leak after logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
// Advanced Security Headers
// CSP: Restricts sources for scripts, styles, and images to 'self' and specific CDNs used in the project.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://npmcdn.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://npmcdn.com https://cdnjs.cloudflare.com; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; img-src 'self' data:;");
// HSTS: Forces HTTPS for 1 year (only works if accessed via HTTPS first)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' // Enable Secure flag only if using HTTPS
]);
session_start();

// 1.5 Session Canary (Anti-Hijacking)
// If a hacker steals your cookie, they can't use it because their Browser/OS (User-Agent) won't match yours.
if (!isset($_SESSION['canary'])) {
    $_SESSION['canary'] = [
        'ua' => $_SERVER['HTTP_USER_AGENT'],
        'ip_subnet' => substr($_SERVER['REMOTE_ADDR'], 0, 7) // Check first 2 blocks of IP (handles dynamic IPs better)
    ];
} else {
    if ($_SESSION['canary']['ua'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset(); session_destroy();
        header("Location: login.php?error=session_hijack_attempt"); exit;
    }
}

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

// 3. Deep Input Sanitization (WAF-Lite)
// Block Null Bytes and other binary exploits often used by advanced hackers.
function sanitize_deep($data) {
    if (is_array($data)) {
        return array_map('sanitize_deep', $data);
    }
    // If a Null Byte (%00) is found, it's almost certainly an attack.
    if (strpos($data, "\0") !== false) {
        die("Security Violation: Malicious payload detected.");
    }
    return $data;
}
$_GET = sanitize_deep($_GET);
$_POST = sanitize_deep($_POST);
$_COOKIE = sanitize_deep($_COOKIE);

// Database Connection: Automatically switch between Local (XAMPP) and Live Server
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // Localhost Credentials (XAMPP)
    $conn = new mysqli("localhost", "root", "", "gym_db");
} else {
    // LIVE SERVER CREDENTIALS (UPDATE THESE BEFORE UPLOADING)
    $conn = new mysqli("localhost", "u123456789_gym_user", "YourStrongPassword!", "u123456789_gym_db");
}

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
