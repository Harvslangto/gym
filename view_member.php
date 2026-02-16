<?php
include "db.php";
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
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "Member not found.";
    exit;
}

$member = $result->fetch_assoc();

// Calculate Age
$age = "N/A";
if(!empty($member['birth_date'])){
    $dob = new DateTime($member['birth_date']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Calculate Remaining Days
$start_date = new DateTime($member['start_date']);
$end_date = new DateTime($member['end_date']);
$now = new DateTime('today');

if($start_date > $now){
    $diff = $start_date->diff($end_date);
} else {
    $diff = $now->diff($end_date);
}

$remaining_days = (int)$diff->format('%r%a');
if($remaining_days >= 0) $remaining_days++;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        .profile-img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border: 3px solid #dc3545;
            box-shadow: 0 0 20px rgba(220, 53, 69, 0.3);
        }
        @media (max-width: 576px) {
            .profile-img {
                width: 120px;
                height: 120px;
            }
        }
        .detail-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.2s;
        }
        .detail-item:hover {
            transform: translateY(-2px);
            border-color: #dc3545;
        }
        .detail-label {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: block;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .profile-sidebar {
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        @media (max-width: 767.98px) {
            .profile-sidebar {
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                margin-bottom: 1.5rem;
                padding-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh;">
<div class="container-xl mt-3 mt-md-5">
    <div class="card premium-card" style="max-width: 900px; margin: auto;">
        <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center pt-4 px-4 flex-wrap gap-3">
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-person-vcard"></i> Member Profile</h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="renew_member.php?id=<?= $member['id'] ?>" class="btn btn-danger"><i class="bi bi-arrow-repeat"></i> Renew</a>
                <a href="edit_member.php?id=<?= $member['id'] ?>" class="btn btn-outline-light"><i class="bi bi-pencil"></i> Edit</a>
                <a href="index.php" class="btn btn-dark"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <div class="row">
                <div class="col-md-4 text-center profile-sidebar">
                    <div class="mb-3">
                        <?php if(!empty($member['photo']) && file_exists($member['photo'])): ?>
                            <img src="<?= $member['photo'] ?>" class="rounded-circle profile-img">
                        <?php else: ?>
                            <div class="rounded-circle bg-dark text-danger d-flex align-items-center justify-content-center mx-auto profile-img" style="font-size: 60px; border-color: #444;">
                                <?= strtoupper(substr($member['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="fw-bold mb-2"><?= htmlspecialchars($member['full_name']) ?></h3>
                    <div class="mb-4">
                        <span class="badge bg-<?= $member['status'] == 'Active' ? 'success' : 'secondary' ?> px-4 py-2 rounded-pill">
                            <?= $member['status'] ?>
                        </span>
                    </div>
                    <div class="p-3 rounded bg-dark mb-3 border border-secondary">
                        <small class="text-muted d-block">Days remaining</small>
                        <?php if($remaining_days >= 0): ?>
                            <h2 class="text-success fw-bold mb-0"><?= $remaining_days ?></h2>
                        <?php else: ?>
                            <h2 class="text-danger fw-bold mb-0">0</h2>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-8 ps-md-4">
                    <h5 class="text-secondary mb-3 border-bottom border-secondary pb-2">Personal Information</h5>
                    <div class="row">
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-telephone text-danger"></i> Contact</span><div class="detail-value"><?= htmlspecialchars($member['contact_number']) ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-gender-ambiguous text-danger"></i> Sex</span><div class="detail-value"><?= htmlspecialchars($member['gender'] ?? 'N/A') ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-calendar-event text-danger"></i> Date of Birth</span><div class="detail-value"><?= $member['birth_date'] ? date('M d, Y', strtotime($member['birth_date'])) : 'N/A' ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-hourglass text-danger"></i> Age</span><div class="detail-value"><?= $age ?></div></div></div>
                        <div class="col-12"><div class="detail-item"><span class="detail-label"><i class="bi bi-geo-alt text-danger"></i> Address</span><div class="detail-value"><?= htmlspecialchars($member['address'] ?? 'N/A') ?></div></div></div>
                    </div>

                    <h5 class="text-secondary mb-3 mt-2 border-bottom border-secondary pb-2">Membership Details</h5>
                    <div class="row">
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-card-heading text-danger"></i> Type</span><div class="detail-value"><?= htmlspecialchars($member['membership_type'] ?? 'N/A') ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-cash text-danger"></i> Amount Paid</span><div class="detail-value">₱<?= number_format($member['amount'] ?? 0, 2) ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-calendar-check text-danger"></i> Start Date</span><div class="detail-value"><?= date('M d, Y', strtotime($member['start_date'])) ?></div></div></div>
                        <div class="col-md-6"><div class="detail-item"><span class="detail-label"><i class="bi bi-calendar-x text-danger"></i> End Date</span><div class="detail-value"><?= date('M d, Y', strtotime($member['end_date'])) ?></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>