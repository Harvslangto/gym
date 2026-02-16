<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if($type){
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM members WHERE membership_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM members WHERE status='Active' AND membership_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $active = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM members WHERE status='Expired' AND membership_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $expired = $stmt->get_result()->fetch_assoc()['count'];

    $stmt = $conn->prepare("SELECT SUM(p.amount) as sum FROM payments p JOIN members m ON p.member_id = m.id WHERE m.membership_type = ?");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $total_income = $stmt->get_result()->fetch_assoc()['sum'];
} else {
    // Count members
    $total = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
    $active = $conn->query("SELECT COUNT(*) as count FROM members WHERE status='Active'")->fetch_assoc()['count'];
    $expired = $conn->query("SELECT COUNT(*) as count FROM members WHERE status='Expired'")->fetch_assoc()['count'];
    $total_income = $conn->query("SELECT SUM(amount) as sum FROM payments")->fetch_assoc()['sum'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
</style>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body class="text-white" style="background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh;">
<div class="container-xl mt-3 mt-md-4">
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="mb-0">Gym Dashboard</h2>
    <a href="logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
</div>

<form method="GET" class="mt-4">
    <div class="row g-2">
        <div class="col-auto">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="Regular" <?= $type == 'Regular' ? 'selected' : '' ?>>Regular</option>
                <option value="Student" <?= $type == 'Student' ? 'selected' : '' ?>>Student</option>
                <option value="Walk-in Regular" <?= $type == 'Walk-in Regular' ? 'selected' : '' ?>>Walk-in Regular</option>
                <option value="Walk-in Student" <?= $type == 'Walk-in Student' ? 'selected' : '' ?>>Walk-in Student</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-dark">Filter</button>
        </div>
    </div>
</form>

<div class="row mt-4">

<div class="col-12 col-sm-6 col-md-3">
<div class="card text-white bg-dark mb-3">
  <div class="card-body">
    <h5 class="card-title">Total Members</h5>
    <p class="card-text"><?= $total ?></p>
  </div>
</div>
</div>

<div class="col-12 col-sm-6 col-md-3">
<div class="card text-white bg-success mb-3">
  <div class="card-body">
    <h5 class="card-title">Active Members</h5>
    <p class="card-text"><?= $active ?></p>
  </div>
</div>
</div>

<div class="col-12 col-sm-6 col-md-3">
<div class="card text-white bg-secondary mb-3">
  <div class="card-body">
    <h5 class="card-title">Expired Members</h5>
    <p class="card-text"><?= $expired ?></p>
  </div>
</div>
</div>

<div class="col-12 col-sm-6 col-md-3">
<div class="card text-white bg-danger mb-3">
  <div class="card-body">
    <h5 class="card-title">Total Income</h5>
    <p class="card-text">₱<?= $total_income ?: 0 ?></p>
  </div>
</div>
</div>

</div>
<a href="index.php" class="btn btn-outline-light mt-3">Go to Member List</a>
</div>
</body>
</html>
