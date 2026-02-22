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

// Automatically update expired members
checkAndLogExpirations($conn, $_SESSION['admin_id']);

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

$back_url = 'index.php'; // Default back URL
if (isset($_GET['return_to']) && !empty($_GET['return_to'])) {
    // Basic validation to prevent open redirect vulnerabilities
    $decoded_url = urldecode($_GET['return_to']);
    // Ensure it is a relative path and does not contain a scheme (like javascript:)
    if (parse_url($decoded_url, PHP_URL_SCHEME) === null && parse_url($decoded_url, PHP_URL_HOST) === null) {
        $back_url = $decoded_url;
    }
}

$is_walk_in_member = strpos($member['membership_type'], 'Walk-in') !== false;
$since_label = $is_walk_in_member ? "First Visit" : "Member Since";

// Get Member Since (Earliest Payment Date)
$stmt_since = $conn->prepare("SELECT MIN(payment_date) as member_since FROM payments WHERE member_id = ?");
$stmt_since->bind_param("i", $id);
$stmt_since->execute();
$since_row = $stmt_since->get_result()->fetch_assoc();
$member_since = $since_row['member_since'] ? date('M d, Y', strtotime($since_row['member_since'])) : date('M d, Y', strtotime($member['start_date']));

// Calculate Age
$age = "N/A";
if(!empty($member['birth_date'])){
    $dob = new DateTime($member['birth_date']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;
}

// Calculate Remaining Days
$end_date = new DateTime($member['end_date']);
$now = new DateTime('today');

// The difference should always be calculated from today to the end date.
$diff = $now->diff($end_date);
$remaining_days = (int)$diff->format('%r%a');
if($member['status'] == 'Active' && $remaining_days >= 0) $remaining_days++; // Add 1 to be inclusive of the end day

// Fetch Payment History
$stmt_pay = $conn->prepare("SELECT * FROM payments WHERE member_id = ? ORDER BY payment_date DESC");
$stmt_pay->bind_param("i", $id);
$stmt_pay->execute();
$payments = $stmt_pay->get_result();
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
        body {
            background: linear-gradient(135deg, #000000, #4a0000);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
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
        @media (min-width: 768px) {
            .container-xl {
                margin: auto !important;
            }
        }
    </style>
</head>
<body>
<div class="container-xl my-3">
    <div class="card premium-card" style="max-width: 900px; margin: auto;">
        <div class="card-header border-0 bg-transparent d-block d-md-flex justify-content-md-between align-items-md-center pt-4 px-4">
            <h4 class="mb-3 mb-md-0 text-danger fw-bold"><i class="bi bi-person-vcard"></i> Member Profile</h4>
            <div class="d-flex gap-2 flex-wrap justify-content-between justify-content-md-end">
                <a href="renew_member.php?id=<?= $member['id'] ?>" class="btn btn-danger"><i class="bi bi-arrow-repeat"></i> Renew</a>
                <a href="edit_member.php?id=<?= $member['id'] ?>" class="btn btn-light"><i class="bi bi-pencil"></i> Edit</a>
                <button type="button" class="btn btn-dark" onclick="confirmDelete(<?= $member['id'] ?>)"><i class="bi bi-trash"></i> Delete</button>
                <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back</a>
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
                    <p class="text-secondary mb-3"><small><?= $since_label ?>: <?= $member_since ?></small></p>
                    <div class="mb-4">
                        <span class="badge bg-<?= $member['status'] == 'Active' ? 'success' : 'secondary' ?> px-4 py-2 rounded-pill">
                            <?= $member['status'] ?>
                        </span>
                    </div>
                    <div class="p-3 rounded bg-dark mb-3 border border-secondary">
                        <small class="text-light d-block">Days remaining</small>
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

                <div class="col-12 mt-4">
                    <h5 class="text-secondary mb-3 border-bottom border-secondary pb-2">Payment History</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                            <thead>
                                <tr>
                                    <th class="text-secondary">Date</th>
                                    <th class="text-secondary">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($pay = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                                    <td class="text-success fw-bold">₱<?= number_format($pay['amount'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Delete Member?</h4>
                <p class="text-secondary mb-4">This will permanently remove the member and all their payment history. This cannot be undone.</p>
                <div class="d-grid gap-2">
                    <form method="POST" action="delete_member.php" id="deleteForm">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn btn-danger w-100">Yes, Delete Permanently</button>
                    </form>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    myModal.show();
}
</script>
<style>
body.light-mode { background: #f8f9fa !important; color: #212529 !important; }
body.light-mode .card.bg-dark, body.light-mode .premium-card, body.light-mode .login-card { background-color: #fff !important; color: #212529 !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
body.light-mode .bg-dark { background-color: #f8f9fa !important; color: #212529 !important; }
body.light-mode .border-secondary { border-color: #dee2e6 !important; }
body.light-mode .table-dark { background-color: #fff !important; color: #212529 !important; }
body.light-mode .table-dark th { background-color: #343a40 !important; color: #fff !important; }
body.light-mode .table-dark td { background-color: #fff !important; color: #212529 !important; border-color: #dee2e6 !important; }
body.light-mode .form-control, body.light-mode .form-select, body.light-mode .input-group-text, body.light-mode .detail-item { background-color: #fff !important; color: #212529 !important; border-color: #ced4da !important; }
body.light-mode .form-control:focus, body.light-mode .form-select:focus { border-color: #dc3545 !important; }
body.light-mode .text-white { color: #212529 !important; }
body.light-mode .text-light, body.light-mode .text-muted { color: #6c757d !important; }
body.light-mode .btn-outline-light { color: #212529; border-color: #212529; }
body.light-mode .btn-outline-light:hover { color: #fff; background-color: #212529; }
body.light-mode .card.bg-success, body.light-mode .card.bg-danger, body.light-mode .card.bg-secondary { color: #fff !important; }
body.light-mode .form-label { color: #212529 !important; }
body.light-mode .detail-label { color: #6c757d !important; }
body.light-mode option { background-color: #fff !important; color: #212529 !important; }
</style>
<script>
const themeBtn = document.createElement('button');
themeBtn.className = 'btn btn-dark position-fixed bottom-0 end-0 m-3 rounded-circle shadow';
themeBtn.style.width = '50px'; themeBtn.style.height = '50px'; themeBtn.style.zIndex = '9999';
themeBtn.innerHTML = '<i class="bi bi-sun-fill"></i>';
themeBtn.onclick = () => { document.body.classList.toggle('light-mode'); const isLight = document.body.classList.contains('light-mode'); localStorage.setItem('theme', isLight ? 'light' : 'dark'); themeBtn.innerHTML = isLight ? '<i class="bi bi-moon-fill"></i>' : '<i class="bi bi-sun-fill"></i>'; themeBtn.className = isLight ? 'btn btn-light position-fixed bottom-0 end-0 m-3 rounded-circle shadow border' : 'btn btn-dark position-fixed bottom-0 end-0 m-3 rounded-circle shadow'; };
document.body.appendChild(themeBtn);
if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); themeBtn.innerHTML = '<i class="bi bi-moon-fill"></i>'; themeBtn.className = 'btn btn-light position-fixed bottom-0 end-0 m-3 rounded-circle shadow border'; }
</script>
</body>
</html>