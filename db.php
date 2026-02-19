<?php
session_start();
$conn = new mysqli("localhost", "root", "", "gym_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Auto-create tables if they don't exist (Fix for missing tables)
$conn->query("CREATE TABLE IF NOT EXISTS membership_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_unit ENUM('Month', 'Day') NOT NULL DEFAULT 'Month'
)");

$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

function logActivity($conn, $admin_id, $action, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $admin_id, $action, $description);
    $stmt->execute();
}
?>
