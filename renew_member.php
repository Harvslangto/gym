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
        $success = true;
    } else {
        $error = "Error renewing member.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Renew Membership</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
        body {
            background: linear-gradient(135deg, #000000, #4a0000);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 768px) {
            .container-xl {
                margin: auto !important;
            }
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body>
<div class="container-xl my-3">
    <div class="card shadow-sm" style="max-width: 500px; margin: auto;">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">Renew Membership: <?= htmlspecialchars($member['full_name']) ?></h4>
        </div>
        <div class="card-body">
            <?php if(isset($error)): ?><div class='alert alert-danger'><?= $error ?></div><?php endif; ?>
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

<?php if(isset($success) && $success): ?>
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Success!</h4>
                <p class="text-secondary mb-4">Membership has been renewed successfully.</p>
                <button type="button" class="btn btn-danger w-100" onclick="window.location='view_member.php?id=<?= $id ?>'">Okay</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        <?php if(isset($success) && $success): ?>
        var myModal = new bootstrap.Modal(document.getElementById("successModal"));
        myModal.show();
        <?php endif; ?>
    });
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