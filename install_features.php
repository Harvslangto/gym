<?php
include "db.php";

// 1. Create Membership Types Table
$sql_types = "CREATE TABLE IF NOT EXISTS membership_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_unit ENUM('Month', 'Day') NOT NULL DEFAULT 'Month'
)";

if ($conn->query($sql_types) === TRUE) {
    echo "Table 'membership_types' created successfully.<br>";
    
    // Insert default data if empty
    $check = $conn->query("SELECT count(*) as count FROM membership_types");
    if($check->fetch_assoc()['count'] == 0){
        $conn->query("INSERT INTO membership_types (type_name, price, duration_unit) VALUES 
            ('Regular', 999.00, 'Month'),
            ('Student', 799.00, 'Month'),
            ('Walk-in Regular', 69.00, 'Day'),
            ('Walk-in Student', 59.00, 'Day')
        ");
        echo "Default membership types inserted.<br>";
    }
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// 2. Create Activity Logs Table
$sql_logs = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_logs) === TRUE) {
    echo "Table 'activity_logs' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

echo "<br><strong>Setup Complete! You can now delete this file.</strong>";
echo "<br><a href='index.php'>Go to Home</a>";
?>