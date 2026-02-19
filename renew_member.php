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
    $amount = $_POST['amount'];
    $start = $_POST['start']; // New start date

    $stmt_check = $conn->prepare("SELECT duration_unit FROM membership_types WHERE type_name = ?");
    $stmt_check->bind_param("s", $membership_type);
    $stmt_check->execute();
    $type_info = $stmt_check->get_result()->fetch_assoc();
    $is_walk_in = ($type_info && $type_info['duration_unit'] == 'Day');

    // For walk-ins, duration is always 1 day, regardless of input
    $months = $is_walk_in ? 1 : (int)$_POST['months'];
    
    $start_dt = new DateTime($start);
    if($is_walk_in){
        $start_dt->modify('+' . ($months - 1) . ' days');
    } else {
        $start_dt->modify('+' . $months . ' months')->modify('-1 day');
    }
    $end = $start_dt->format('Y-m-d');

    $conn->begin_transaction();
    try {
        // Update member record
        $update = $conn->prepare("UPDATE members SET membership_type=?, amount=?, start_date=?, end_date=?, status='Active' WHERE id=?");
        $update->bind_param("sdssi", $membership_type, $amount, $start, $end, $id);
        if (!$update->execute()) throw new Exception($conn->error);

        // Insert new payment record
        $stmt_pay = $conn->prepare("INSERT INTO payments (member_id, amount, payment_date) VALUES (?, ?, ?)");
        $stmt_pay->bind_param("ids", $id, $amount, $start);
        if (!$stmt_pay->execute()) throw new Exception($conn->error);

        $conn->commit();
        logActivity($conn, $_SESSION['admin_id'], 'Renew Member', "Renewed membership for: " . $member['full_name']);
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error renewing member: " . $e->getMessage();
    }
}

// Fetch types
$types_result = $conn->query("SELECT * FROM membership_types");
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
        option {
            background-color: #222;
            color: #000;
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
    <div class="card premium-card" style="max-width: 600px; margin: auto;">
        <div class="card-header border-0 bg-transparent pt-4 px-4">
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-arrow-repeat"></i> Renew Membership: <?= htmlspecialchars($member['full_name']) ?></h4>
        </div>
        <div class="card-body p-3 p-md-4">
            <?php if(isset($error)): ?><div class='alert alert-danger'><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start" id="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calculateAmount()">
                </div>
                <div class="mb-3">
                    <label class="form-label">Membership Type</label>
                    <select name="membership_type" id="membership_type" class="form-select" onchange="calculateAmount()">
                        <?php while($t = $types_result->fetch_assoc()): 
                            $is_wi = ($t['duration_unit'] == 'Day') ? '1' : '0';
                            $unit_lbl = ($t['duration_unit'] == 'Day') ? '/day' : '/mo';
                            $selected = ($member['membership_type'] == $t['type_name']) ? 'selected' : '';
                        ?>
                            <option value="<?= $t['type_name'] ?>" data-price="<?= $t['price'] ?>" data-is-walk-in="<?= $is_wi ?>" <?= $selected ?>>
                                <?= $t['type_name'] ?> (₱<?= $t['price'] . $unit_lbl ?>)
                            </option>
                        <?php endwhile; ?>
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
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="view_member.php?id=<?= $id ?>" class="btn btn-dark px-4">Cancel</a>
                    <button name="renew" class="btn btn-danger">Confirm Renewal</button>
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