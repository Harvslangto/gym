<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Automatically update expired members
checkAndLogExpirations($conn, $_SESSION['admin_id']);

$type = isset($_GET['type']) ? $_GET['type'] : '';

// Consolidate member counts into a single query
$counts_sql = "SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active, 
    SUM(CASE WHEN status = 'Expired' THEN 1 ELSE 0 END) as expired 
    FROM members";
$stmt_counts = $conn->prepare($counts_sql);
$stmt_counts->execute();
$counts = $stmt_counts->get_result()->fetch_assoc();
$total = $counts['total'] ?? 0;
$active = $counts['active'] ?? 0;
$expired = $counts['expired'] ?? 0;

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

// Fetch income for the selected year
$income_sql = "SELECT SUM(amount) as sum FROM payments WHERE YEAR(payment_date) = ?";
$stmt_income = $conn->prepare($income_sql);
$stmt_income->bind_param("i", $selected_year);
$stmt_income->execute();
$total_income = $stmt_income->get_result()->fetch_assoc()['sum'];
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('n');

// If any filter is set (i.e. the URL has query parameters), use the 'day' from the filter.
// An empty 'day' means 'All Days'.
// If no filters are set (initial page load), default to showing only today's data.
if (empty($_GET)) {
    $selected_day = date('j');
} else {
    $selected_day = isset($_GET['day']) ? $_GET['day'] : '';
}

$daily_breakdown_title_date = date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year));
if (empty($_GET) && $selected_day) { // On initial load, the title should reflect 'Today'.
    $daily_breakdown_title_date = 'Today, ' . date('F j, Y'); 
} elseif ($selected_day) { // If a specific day is filtered.
    $daily_breakdown_title_date = date('F j, Y', mktime(0, 0, 0, $selected_month, $selected_day, $selected_year));
}

// Yearly Stats (unfiltered by type)
$yearly_sql = "SELECT YEAR(payment_date) as year, COUNT(DISTINCT member_id) as count, SUM(amount) as revenue FROM payments GROUP BY YEAR(payment_date) ORDER BY year DESC";
$stmt_y = $conn->prepare($yearly_sql);
$stmt_y->execute();
$yearly_stats = $stmt_y->get_result();

// Monthly Stats (unfiltered by type, but filtered by selected year)
$monthly_sql = "SELECT MONTH(payment_date) as month, COUNT(DISTINCT member_id) as count, SUM(amount) as revenue FROM payments WHERE YEAR(payment_date) = ? GROUP BY MONTH(payment_date) ORDER BY month DESC";
$stmt_m = $conn->prepare($monthly_sql);
$stmt_m->bind_param("i", $selected_year);
$stmt_m->execute();
$monthly_stats = $stmt_m->get_result();

// Daily Stats (filtered by type, year, month, and optionally day)
$params = [];
$types = '';
$join_sql = $type ? "JOIN members m ON p.member_id = m.id" : "";
$where_sql = $type ? "WHERE m.membership_type = ?" : "WHERE 1";
if($type) { $params[] = $type; $types .= 's'; }

$daily_sql = "SELECT DATE(p.payment_date) as date, COUNT(DISTINCT p.member_id) as count, SUM(p.amount) as revenue FROM payments p $join_sql $where_sql AND YEAR(p.payment_date) = ? AND MONTH(p.payment_date) = ?";
$daily_params = $params;
$daily_params[] = $selected_year;
$daily_params[] = $selected_month;
$daily_types = $types . 'ii';

if($selected_day){
    $daily_sql .= " AND DAY(p.payment_date) = ?";
    $daily_params[] = $selected_day;
    $daily_types .= 'i';
}
$daily_sql .= " GROUP BY DATE(p.payment_date) ORDER BY date DESC";
$stmt_d = $conn->prepare($daily_sql);
$stmt_d->bind_param($daily_types, ...$daily_params);
$stmt_d->execute();
$daily_stats = $stmt_d->get_result();

// Fetch membership types for filter dropdown
$types_result_dash = $conn->query("SELECT type_name FROM membership_types ORDER BY type_name");

// Build query string for persistence
$qs = http_build_query([
    'type' => $type,
    'year' => $selected_year,
    'month' => $selected_month,
    'day' => $selected_day
]);
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link rel="icon" href="logo/logo.jpg" type="image/jpeg">
<link rel="apple-touch-icon" href="logo/logo.jpg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
    @media (max-width: 576px) {
        h2 { font-size: 1.5rem; }
        .stat-card .card-body { padding: 1rem !important; }
        .stat-card h2 { font-size: 1.5rem; }
    }
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
    .form-control::placeholder { color: rgba(255, 255, 255, 0.7); opacity: 1; }
    option { background-color: #fff; color: #000; }
    
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
    <div class="d-flex gap-2 align-items-center">
        <a href="index.php" class="btn btn-danger px-4 fw-bold shadow-sm"><i class="bi bi-people-fill me-2"></i>Back to Members</a>
        <div class="vr mx-2 bg-secondary opacity-50 d-none d-sm-block"></div>
        <a href="settings.php?<?= $qs ?>" class="btn btn-dark border-secondary shadow-sm" title="Settings"><i class="bi bi-gear-fill"></i> <span class="d-none d-md-inline ms-1">Settings</span></a>
        <a href="activity_logs.php?<?= $qs ?>" class="btn btn-dark border-secondary shadow-sm" title="Activity Logs"><i class="bi bi-clock-history"></i> <span class="d-none d-md-inline ms-1">Logs</span></a>
        <button type="button" class="btn btn-outline-light shadow-sm ms-2" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Logout"><i class="bi bi-power"></i></button>
    </div>
</div>

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
                    <h6 class="text-secondary text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1.5px;">Income (<?= $selected_year ?>)</h6>
                    <h2 class="mb-0 fw-bold text-danger" style="font-size: 1.75rem;">₱<?= number_format($total_income ?: 0, 2) ?></h2>
                </div>
                <div class="icon-box">
                    <i class="bi bi-wallet2 text-danger fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card premium-card mb-3">
            <div class="card-header bg-transparent d-flex justify-content-center justify-content-md-between align-items-center flex-wrap gap-3" style="border-bottom: 1px solid #4a0000;">
                <h5 class="mb-0 text-uppercase text-center w-100" style="letter-spacing: 1px;">Daily Breakdown (<?= htmlspecialchars($daily_breakdown_title_date) ?>)</h5>
                <form method="GET" class="w-100" autocomplete="off">
                    <div class="row g-2 justify-content-center align-items-center">
                        <div class="col-12 col-sm-6 col-md-auto">
                            <select name="type" class="form-select" style="background: #fff; color: #000; border: 1px solid #4a0000; text-align: center;">
                                <option value="" disabled hidden <?= !isset($_GET['type']) ? 'selected' : '' ?>>Membership Type</option>
                                <option value="" <?= (isset($_GET['type']) && $type === '') ? 'selected' : '' ?>>All Types</option>
                                <?php if(isset($types_result_dash) && $types_result_dash->num_rows > 0) { while($t = $types_result_dash->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($t['type_name']) ?>" <?= $type == $t['type_name'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="col-6 col-sm-3 col-md-auto">
                            <div class="input-group">
                                <span class="input-group-text" style="background: #fff; color: #000; border: 1px solid #4a0000; border-right: 0;">Year:</span>
                                <select name="year" class="form-select" style="background: #fff; color: #000; border: 1px solid #4a0000; text-align: center;">
                                    <?php foreach($years as $y): ?>
                                        <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3 col-md-auto">
                            <div class="input-group">
                                <span class="input-group-text" style="background: #fff; color: #000; border: 1px solid #4a0000; border-right: 0;">Month:</span>
                                <select name="month" class="form-select" style="background: #fff; color: #000; border: 1px solid #4a0000; text-align: center;">
                                    <?php for($m=1; $m<=12; $m++){ $monthName = date('F', mktime(0, 0, 0, $m, 10)); $selected = ($selected_month == $m) ? 'selected' : ''; echo "<option value='$m' $selected>$monthName</option>"; } ?>
                                </select> 
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto">
                            <select name="day" class="form-select" style="background: #fff; color: #000; border: 1px solid #4a0000; text-align: center;">
                                <option value="">All Days</option>
                                <?php $current_d = date('j'); $current_m = date('n'); $current_y = date('Y'); $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year); for($d = $days_in_month; $d >= 1; $d--){ $sel = ($selected_day == $d) ? 'selected' : ''; $label = ($selected_month == $current_m && $selected_year == $current_y && $d == $current_d) ? "Today" : $d; echo "<option value='$d' $sel>$label</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-auto">
                            <div class="d-grid d-sm-flex gap-2">
                                <button type="submit" class="btn btn-danger">Filter</button>
                                <a href="dashboard.php" class="btn btn-outline-light">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0 text-center align-middle">
                        <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="text-center" style="width: 33.33%">Date</th>
                                <th class="text-center" style="width: 33.33%">Members</th>
                                <th class="text-center" style="width: 33.33%">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($daily_stats->num_rows > 0):
                                while($row = $daily_stats->fetch_assoc()): 
                            ?>
                            <tr style="cursor: pointer;" onclick="window.location='view_daily_members.php?date=<?= $row['date'] ?>&<?= $qs ?>'">
                                <td class="text-center"><?= htmlspecialchars(date('M d, Y', strtotime($row['date']))) ?></td>
                                <td class="text-center"><span class="badge bg-danger rounded-pill"><?= htmlspecialchars($row['count']) ?></span></td>
                                <td class="text-center">₱<?= htmlspecialchars(number_format($row['revenue'], 2)) ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr><td colspan="3" class="text-center text-muted">No data for this month</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card premium-card mb-3">
            <div class="card-header bg-transparent text-center" style="border-bottom: 1px solid #4a0000;">
                <h5 class="mb-0 text-uppercase" style="letter-spacing: 1px;">Monthly Breakdown (<?= htmlspecialchars($selected_year) ?>)</h5>
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
                            <td class="text-center"><?= htmlspecialchars($monthName) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['count']) ?></td>
                            <td class="text-center">₱<?= htmlspecialchars(number_format($row['revenue'], 2)) ?></td>
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
                            <td class="text-center"><?= htmlspecialchars($row['year']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($row['count']) ?></td>
                            <td class="text-center">₱<?= htmlspecialchars(number_format($row['revenue'], 2)) ?></td>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearSelect = document.querySelector('select[name="year"]');
    const monthSelect = document.querySelector('select[name="month"]');
    const daySelect = document.querySelector('select[name="day"]');

    function updateDays() {
        const year = parseInt(yearSelect.value);
        const month = parseInt(monthSelect.value);
        
        // In JS, month is 0-indexed. The day '0' of the next month gives the last day of the current month.
        const daysInMonth = new Date(year, month, 0).getDate();

        const currentDayValue = daySelect.value;
        
        daySelect.innerHTML = '<option value="">All Days</option>';

        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth() + 1; // JS month is 0-indexed, PHP is 1-indexed
        const currentDay = today.getDate();

        for (let d = daysInMonth; d >= 1; d--) {
            const option = document.createElement('option');
            option.value = d;

            if (year === currentYear && month === currentMonth && d === currentDay) {
                option.textContent = 'Today';
            } else {
                option.textContent = d;
            }
            daySelect.appendChild(option);
        }

        if (currentDayValue && currentDayValue <= daysInMonth) {
            daySelect.value = currentDayValue;
        }
    }

    yearSelect.addEventListener('change', updateDays);
    monthSelect.addEventListener('change', updateDays);
});
</script>
</body>
</html>
