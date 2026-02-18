<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Automatically update expired members
$conn->query("UPDATE members SET status = 'Expired' WHERE end_date < CURDATE() AND status = 'Active'");

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

// Get Years for filter
$years_q = $conn->query("SELECT DISTINCT YEAR(payment_date) as year FROM payments ORDER BY year DESC");
$years = [];
if($years_q->num_rows > 0){
    while($y = $years_q->fetch_assoc()){
        $years[] = $y['year'];
    }
} else {
    $years[] = date('Y');
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : $years[0];

// Yearly Stats Query
if($type){
    $stmt_y = $conn->prepare("SELECT YEAR(p.payment_date) as year, COUNT(DISTINCT p.member_id) as count, SUM(p.amount) as revenue FROM payments p JOIN members m ON p.member_id = m.id WHERE m.membership_type = ? GROUP BY YEAR(p.payment_date) ORDER BY year DESC");
    $stmt_y->bind_param("s", $type);
    $stmt_y->execute();
    $yearly_stats = $stmt_y->get_result();
} else {
    $yearly_stats = $conn->query("SELECT YEAR(payment_date) as year, COUNT(DISTINCT member_id) as count, SUM(amount) as revenue FROM payments GROUP BY YEAR(payment_date) ORDER BY year DESC");
}

// Monthly Stats Query
if($type){
    $stmt_m = $conn->prepare("SELECT MONTH(p.payment_date) as month, COUNT(DISTINCT p.member_id) as count, SUM(p.amount) as revenue FROM payments p JOIN members m ON p.member_id = m.id WHERE m.membership_type = ? AND YEAR(p.payment_date) = ? GROUP BY MONTH(p.payment_date) ORDER BY month DESC");
    $stmt_m->bind_param("si", $type, $selected_year);
    $stmt_m->execute();
    $monthly_stats = $stmt_m->get_result();
} else {
    $stmt_m = $conn->prepare("SELECT MONTH(payment_date) as month, COUNT(DISTINCT member_id) as count, SUM(amount) as revenue FROM payments WHERE YEAR(payment_date) = ? GROUP BY MONTH(payment_date) ORDER BY month DESC");
    $stmt_m->bind_param("i", $selected_year);
    $stmt_m->execute();
    $monthly_stats = $stmt_m->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Russo One', sans-serif; letter-spacing: 1px; }
    body {
        background: linear-gradient(135deg, #000000, #4a0000);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .stat-card {
        background: linear-gradient(145deg, #1a1a1a, #2c2c2c);
        border-radius: 15px;
        border: 1px solid #4a0000;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    }
    .icon-box {
        width: 50px; 
        height: 50px; 
        background: rgba(0,0,0,0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .premium-card {
        background: rgba(20, 20, 20, 0.95);
        border: 1px solid #4a0000;
        border-radius: 15px;
        box-shadow: 0 0 30px rgba(220, 53, 69, 0.15);
        color: white;
        overflow: hidden;
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
    option { background-color: #222; color: white; }
    
    /* Premium Button Styles */
    .btn-danger {
        background: linear-gradient(45deg, #8b0000, #dc3545);
        border: none;
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .btn-danger:hover {
        background: linear-gradient(45deg, #dc3545, #ff4d4d);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5);
    }
    .btn-dark {
        background: linear-gradient(145deg, #1a1a1a, #2c2c2c);
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
    }
    .btn-dark:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.5);
        background: linear-gradient(145deg, #2c2c2c, #3d3d3d);
    }
    .btn-outline-light {
        border-width: 2px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-outline-light:hover {
        background: rgba(255,255,255,0.1);
        color: white;
        border-color: white;
        transform: translateY(-2px);
    }

    /* Table Styles */
    .table { color: #e0e0e0; margin-bottom: 0; }
    .table thead th {
        background-color: rgba(220, 53, 69, 0.15);
        color: #ff6b6b;
        border-bottom: 1px solid #4a0000;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
        padding: 1rem;
    }
    .table tbody td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1rem;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: white;
    }
</style>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body class="text-white">
<div class="container-xl my-3">
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h2 class="mb-0 text-uppercase" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Gym Dashboard</h2>
    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
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
            <select name="year" class="form-select">
                <?php foreach($years as $y): ?>
                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-dark">Filter</button>
        </div>
        <div class="col-auto ms-auto">
            <a href="index.php" class="btn btn-outline-light">Go to Member List</a>
        </div>
    </div>
</form>

<div class="row mt-4 mb-4 g-3">
    <!-- Total Members -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card stat-card h-100 border-0">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Total Members</h6>
                    <h2 class="mb-0 fw-bold text-white"><?= $total ?></h2>
                </div>
                <div class="icon-box">
                    <i class="bi bi-people text-danger fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Members -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="border-bottom: 3px solid #198754 !important;">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Active</h6>
                    <h2 class="mb-0 fw-bold text-success"><?= $active ?></h2>
                </div>
                <div class="icon-box">
                    <i class="bi bi-person-check text-success fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Expired Members -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="border-bottom: 3px solid #6c757d !important;">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Expired</h6>
                    <h2 class="mb-0 fw-bold text-secondary"><?= $expired ?></h2>
                </div>
                <div class="icon-box">
                    <i class="bi bi-person-x text-secondary fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Income -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card stat-card h-100 border-0" style="border-bottom: 3px solid #dc3545 !important;">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Total Income</h6>
                    <h2 class="mb-0 fw-bold text-danger">₱<?= number_format($total_income ?: 0, 2) ?></h2>
                </div>
                <div class="icon-box">
                    <i class="bi bi-wallet2 text-danger fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card premium-card mb-3">
            <div class="card-header bg-transparent text-center" style="border-bottom: 1px solid #4a0000;">
                <h5 class="mb-0 text-uppercase" style="letter-spacing: 1px;">Monthly Breakdown (<?= $selected_year ?>)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 33.33%">Month</th>
                            <th class="text-center" style="width: 33.33%">Members</th>
                            <th class="text-center" style="width: 33.33%">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if($monthly_stats->num_rows > 0):
                            while($row = $monthly_stats->fetch_assoc()): 
                                $dateObj   = DateTime::createFromFormat('!m', $row['month']);
                                $monthName = $dateObj->format('F');
                        ?>
                        <tr>
                            <td class="text-center"><?= $monthName ?></td>
                            <td class="text-center"><?= $row['count'] ?></td>
                            <td class="text-center">₱<?= number_format($row['revenue'], 2) ?></td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr><td colspan="3" class="text-center text-muted">No data for this year</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card premium-card mb-3">
            <div class="card-header bg-transparent text-center" style="border-bottom: 1px solid #4a0000;">
                <h5 class="mb-0 text-uppercase" style="letter-spacing: 1px;">Yearly Overview</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 33.33%">Year</th>
                            <th class="text-center" style="width: 33.33%">Members</th>
                            <th class="text-center" style="width: 33.33%">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $yearly_stats->fetch_assoc()): ?>
                        <tr>
                            <td class="text-center"><?= $row['year'] ?></td>
                            <td class="text-center"><?= $row['count'] ?></td>
                            <td class="text-center">₱<?= number_format($row['revenue'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Logout</h4>
                <p class="text-secondary mb-4">Are you sure you want to logout?</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" onclick="window.location.href='logout.php'">Yes, Logout</button>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
body.light-mode { background: #f8f9fa !important; color: #212529 !important; }
body.light-mode .card.bg-dark, body.light-mode .premium-card, body.light-mode .login-card { background-color: #fff !important; color: #212529 !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
body.light-mode.text-white { color: #212529 !important; }
body.light-mode h1, body.light-mode h2, body.light-mode h3, body.light-mode h4, body.light-mode h5, body.light-mode h6 { color: #212529 !important; }
body.light-mode .bg-dark { background-color: #f8f9fa !important; color: #212529 !important; }
body.light-mode .border-secondary { border-color: #dee2e6 !important; }
body.light-mode .table { color: #212529 !important; }
body.light-mode .table tr, body.light-mode .table th, body.light-mode .table td { color: #212529 !important; }
body.light-mode .table thead th { background-color: #f8f9fa !important; color: #212529 !important; border-bottom-color: #dee2e6 !important; }
body.light-mode .table tbody td { border-bottom-color: #dee2e6 !important; }
body.light-mode .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.05) !important; color: #212529 !important; }
body.light-mode .form-control, body.light-mode .form-select, body.light-mode .input-group-text, body.light-mode .detail-item { background-color: #fff !important; color: #212529 !important; border-color: #ced4da !important; }
body.light-mode .card.bg-success, body.light-mode .card.bg-danger, body.light-mode .card.bg-secondary, body.light-mode .card.bg-success h5, body.light-mode .card.bg-danger h5, body.light-mode .card.bg-secondary h5, body.light-mode .card.bg-success .card-text, body.light-mode .card.bg-danger .card-text, body.light-mode .card.bg-secondary .card-text { color: #fff !important; }
body.light-mode .stat-card { background: #fff !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05) !important; }
body.light-mode .icon-box { background: #f8f9fa !important; }
body.light-mode .form-control:focus, body.light-mode .form-select:focus { border-color: #dc3545 !important; }
body.light-mode .text-white { color: #212529 !important; }
body.light-mode .text-light, body.light-mode .text-muted { color: #6c757d !important; }
body.light-mode .btn-outline-light { color: #212529; border-color: #212529; }
body.light-mode .btn-outline-light:hover { color: #fff; background-color: #212529; }
body.light-mode .card.bg-success, body.light-mode .card.bg-danger, body.light-mode .card.bg-secondary { color: #fff !important; }
body.light-mode .form-label { color: #212529 !important; }
body.light-mode .detail-label { color: #6c757d !important; }
body.light-mode option { background-color: #fff !important; color: #212529 !important; }
.card .table th, .card .table td { text-align: center !important; vertical-align: middle !important; }
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
