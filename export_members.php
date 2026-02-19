<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="members_list_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Full Name', 'Contact', 'Address', 'Birth Date', 'Gender', 'Membership Type', 'Status', 'Start Date', 'End Date'));

$query = "SELECT id, full_name, contact_number, address, birth_date, gender, membership_type, status, start_date, end_date FROM members ORDER BY id DESC";
$result = $conn->query($query);

while($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

logActivity($conn, $_SESSION['admin_id'], 'Export', 'Exported member list to CSV');
fclose($output);
exit;
?>