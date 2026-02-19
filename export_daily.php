<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="daily_breakdown_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array('Date', 'Total Members', 'Total Revenue'));

$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : date('n');
$type = isset($_GET['type']) ? $_GET['type'] : '';

$params = [];
$types = '';
$join_sql = $type ? "JOIN members m ON p.member_id = m.id" : "";
$where_sql = $type ? "WHERE m.membership_type = ?" : "WHERE 1";
if($type) { $params[] = $type; $types .= 's'; }

$sql = "SELECT DATE(p.payment_date) as date, COUNT(DISTINCT p.member_id) as count, SUM(p.amount) as revenue 
        FROM payments p $join_sql $where_sql 
        AND YEAR(p.payment_date) = ? AND MONTH(p.payment_date) = ? 
        GROUP BY DATE(p.payment_date) ORDER BY date DESC";

$params[] = $year;
$params[] = $month;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>