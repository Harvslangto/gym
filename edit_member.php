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

$is_walk_in = strpos($member['membership_type'], 'Walk-in') !== false;
$months_duration = get_membership_duration_in_months_or_days($member['start_date'], $member['end_date'], $is_walk_in);
if($months_duration < 1) $months_duration = 1;

if(isset($_POST['update'])){
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $birth_date = $_POST['birth_date'];
    if(empty($birth_date) || $birth_date == '0000-00-00' || $birth_date == '1970-01-01'){
        $birth_date = $member['birth_date'];
    }
    $gender = $_POST['gender'];
    $membership_type = $_POST['membership_type'];
    $amount = $_POST['amount'];
    $start = $_POST['start']; 
    $months = (int)$_POST['months'];
    $status = $_POST['status'];

    $is_walk_in_post = strpos($membership_type, 'Walk-in') !== false;
    
    $start_dt = new DateTime($start);
    if($is_walk_in_post){
        $start_dt->modify('+' . ($months - 1) . ' days');
    } else {
        $days = $months * 30;
        $start_dt->modify('+' . ($days - 1) . ' days');
    }
    $end = $start_dt->format('Y-m-d');

    // Validate dates to prevent 0000-00-00 errors
    if(empty($start) || $start == '0000-00-00'){
        $start = $member['start_date'];
    }
    if(empty($end) || $end == '0000-00-00'){
        $end = $member['end_date'];
    }

    $photo_path = $member['photo'];
    if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if($check !== false) {
            $target_dir = "uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir);
            $ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $photo_path = $target_dir . uniqid() . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
        }
    }

    $update = $conn->prepare("UPDATE members SET full_name=?, contact_number=?, address=?, birth_date=?, gender=?, membership_type=?, amount=?, start_date=?, end_date=?, status=?, photo=? WHERE id=?");
    $update->bind_param("ssssssdssssi", $name, $contact, $address, $birth_date, $gender, $membership_type, $amount, $start, $end, $status, $photo_path, $id);
    
    if($update->execute()){
        echo "<script>alert('Member Updated!'); window.location='view_member.php?id=$id';</script>";
    } else {
        echo "<div class='alert alert-danger'>Error updating member</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
        .premium-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #4a0000;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(220, 53, 69, 0.15);
            color: white;
        }
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #dc3545;
            color: white;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .form-label {
            color: #aaa;
            font-size: 0.9rem;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #dc3545;
        }
        /* Fix for date inputs in dark mode */
        input[type="date"] {
            color-scheme: dark;
        }
        .form-control::placeholder {
            color: #aaa;
            opacity: 1;
        }
        option {
            background-color: #222;
            color: white;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh;">
<div class="container-xl mt-3 mt-md-5">
    <div class="card premium-card" style="max-width: 800px; margin: auto;">
        <div class="card-header border-0 bg-transparent pt-4 px-4">
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-pencil-square"></i> Edit Member</h4>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['full_name']) ?>" required placeholder="Enter Full Name" pattern="[A-Za-z\s\.\-]+" title="Only letters are allowed" oninput="this.value = this.value.replace(/[^A-Za-z\s\.\-]/g, '')">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($member['contact_number']) ?>" required placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" required placeholder="Enter Complete Address"><?= htmlspecialchars($member['address']) ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= $member['birth_date'] ?>" required placeholder="Select Date of Birth">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sex</label>
                        <select name="gender" class="form-select" required>
                            <option value="" disabled>Select Sex</option>
                            <option value="Male" <?= $member['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $member['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= $member['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Membership Type</label>
                        <select name="membership_type" id="membership_type" class="form-select" onchange="calculateAmount(true)">
                            <option value="Regular" data-price="999" <?= ($member['membership_type'] ?? '') == 'Regular' ? 'selected' : '' ?>>Regular (₱999/mo)</option>
                            <option value="Student" data-price="799" <?= ($member['membership_type'] ?? '') == 'Student' ? 'selected' : '' ?>>Student (₱799/mo)</option>
                            <option value="Walk-in Regular" data-price="69" <?= ($member['membership_type'] ?? '') == 'Walk-in Regular' ? 'selected' : '' ?>>Walk-in Regular (₱69/day)</option>
                            <option value="Walk-in Student" data-price="59" <?= ($member['membership_type'] ?? '') == 'Walk-in Student' ? 'selected' : '' ?>>Walk-in Student (₱59/day)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" id="duration_label">Months</label>
                        <input type="number" name="months" id="months" class="form-control" value="<?= $months_duration ?>" min="1" required onchange="calculateAmount(true)">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start" id="start_date" class="form-control" value="<?= $member['start_date'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end" id="end_date" class="form-control" value="<?= $member['end_date'] ?>" required readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="amount" id="amount" class="form-control" value="<?= $member['amount'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $member['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Expired" <?= $member['status'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                    <?php if(!empty($member['photo'])): ?>
                        <div class="mt-2">
                            <small class="text-secondary">Current Photo:</small><br>
                            <img src="<?= $member['photo'] ?>" class="rounded border border-secondary mt-1" style="width: 60px; height: 60px; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="view_member.php?id=<?= $id ?>" class="btn btn-dark px-4">Cancel</a>
                    <button name="update" class="btn btn-danger">Update Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="js/main.js"></script>
<script>
    flatpickr("#birth_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        maxDate: "today"
    });
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        onChange: function(selectedDates, dateStr, instance) {
            calculateAmount(true);
        }
    });
    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y"
    });

    document.addEventListener("DOMContentLoaded", function() {
        calculateAmount(true);
    });

    document.getElementById('membership_type').addEventListener('change', function() {
        calculateAmount(true);
    });

    document.getElementById('months').addEventListener('input', function() {
        calculateAmount(true);
    });
</script>
</body>
</html>