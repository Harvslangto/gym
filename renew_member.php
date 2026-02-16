<?php
include "db.php";
include "includes/calculations.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if(!$member){
    echo "Member not found";
    exit;
}

if(isset($_POST['renew'])){
    $membership_type = $_POST['membership_type'];
    $months = (int)$_POST['months'];
    $amount = $_POST['amount'];
    $start = $_POST['start']; // New start date

    $is_walk_in = strpos($membership_type, 'Walk-in') !== false;
    
    $start_dt = new DateTime($start);
    if($is_walk_in){
        $start_dt->modify('+' . ($months - 1) . ' days');
    } else {
        $days = $months * 30;
        $start_dt->modify('+' . ($days - 1) . ' days');
    }
    $end = $start_dt->format('Y-m-d');

    // Update member record
    $update = $conn->prepare("UPDATE members SET membership_type=?, amount=?, start_date=?, end_date=?, status='Active' WHERE id=?");
    $update->bind_param("sdssi", $membership_type, $amount, $start, $end, $id);
    
    if($update->execute()){
        $stmt_pay = $conn->prepare("INSERT INTO payments (member_id, amount, payment_date) VALUES (?, ?, ?)");
        $stmt_pay->bind_param("ids", $id, $amount, $start);
        $stmt_pay->execute();
        echo "<script>alert('Membership Renewed Successfully!'); window.location='view_member.php?id=$id';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error renewing member</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Renew Membership</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body style="background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh;">
<div class="container-xl mt-3 mt-md-5">
    <div class="card shadow-sm" style="max-width: 500px; margin: auto;">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">Renew Membership: <?= htmlspecialchars($member['full_name']) ?></h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start" id="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calculateAmount()">
                </div>
                <div class="mb-3">
                    <label class="form-label">Membership Type</label>
                    <select name="membership_type" id="membership_type" class="form-select" onchange="calculateAmount()">
                        <option value="Regular" data-price="999" <?= $member['membership_type'] == 'Regular' ? 'selected' : '' ?>>Regular (₱999/mo)</option>
                        <option value="Student" data-price="799" <?= $member['membership_type'] == 'Student' ? 'selected' : '' ?>>Student (₱799/mo)</option>
                        <option value="Walk-in Regular" data-price="69" <?= $member['membership_type'] == 'Walk-in Regular' ? 'selected' : '' ?>>Walk-in Regular (₱69/day)</option>
                        <option value="Walk-in Student" data-price="59" <?= $member['membership_type'] == 'Walk-in Student' ? 'selected' : '' ?>>Walk-in Student (₱59/day)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" id="duration_label">Months</label>
                    <input type="number" name="months" id="months" class="form-control" value="1" min="1" required onchange="calculateAmount()">
                </div>
                <div class="mb-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Total Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="amount" id="amount" class="form-control" readonly>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button name="renew" class="btn btn-danger">Confirm Renewal</button>
                    <a href="view_member.php?id=<?= $id ?>" class="btn btn-dark">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="js/main.js"></script>
<script>
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        defaultDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            calculateAmount();
        }
    });

    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
    });

    document.addEventListener("DOMContentLoaded", function() {
        calculateAmount();
    });
</script>
</body>
</html>