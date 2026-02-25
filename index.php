<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Automatically update expired members
checkAndLogExpirations($conn, $_SESSION['admin_id']);

$type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch membership types for filter dropdown
$types_result = $conn->query("SELECT type_name FROM membership_types ORDER BY type_name");

if($type && $search){
    $stmt = $conn->prepare("SELECT * FROM members WHERE membership_type = ? AND (full_name LIKE ? OR birth_date LIKE ?) ORDER BY id DESC");
    $searchTerm = "%$search%";
    $stmt->bind_param("sss", $type, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif($type){
    $stmt = $conn->prepare("SELECT * FROM members WHERE membership_type = ? ORDER BY id DESC");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif($search){
    $stmt = $conn->prepare("SELECT * FROM members WHERE full_name LIKE ? OR birth_date LIKE ? ORDER BY id DESC");
    $searchTerm = "%$search%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM members ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trizen Fitness Hub</title>
    <link rel="icon" href="logo/logo.jpg" type="image/jpeg">
    <link rel="apple-touch-icon" href="logo/logo.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        @media (max-width: 576px) {
            h2 { font-size: 1.5rem; }
        }
        body { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #000000, #4a0000);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Russo One', sans-serif; letter-spacing: 1px; }
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
        .form-control::placeholder { color: #aaa; }
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
        
        @media (max-width: 576px) {
            /* Disable horizontal scroll on mobile and rely on the card layout */
            .table-responsive {
                overflow-x: hidden;
            }

            /* Hide table headers */
            .table thead { display: none; }
            
            /* Card styling for rows */
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: rgba(255, 255, 255, 0.05);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            /* Flex layout for cells */
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.8rem 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                text-align: right;
            }
            
            /* Labels for data */
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #aaa;
                text-align: left;
            }
            
            /* Name styling (Card Header) */
            .table tbody td:first-child {
                background: rgba(220, 53, 69, 0.2);
                justify-content: center;
                font-size: 1.1rem;
                border-radius: 12px 12px 0 0;
                border-bottom: 1px solid rgba(220, 53, 69, 0.3);
                color: white;
            }
            .table tbody td:first-child::before { display: none; }
            
            /* Action styling (Card Footer) */
            .table tbody .actions-cell {
                border-bottom: none;
                padding: 1rem;
                justify-content: center;
            }
            .table tbody .actions-cell::before { display: none; }
            .table tbody .actions-cell .btn { flex-grow: 1; }

            .table-hover tbody tr:hover {
                transform: scale(1.02);
                transition: transform 0.2s ease-in-out;
                cursor: pointer;
            }
        }
    </style>
</head>

<body class="text-white">
<div class="container-xl my-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="text-uppercase" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Gym Members <span class="badge bg-danger fs-6 align-middle ms-2" style="box-shadow: 0 0 10px rgba(220,53,69,0.5);"><?= $result->num_rows ?></span></h2>
        <div class="d-flex gap-2 flex-wrap">
            <a href="add_member.php" class="btn btn-danger">+ Add Member</a>
            <a href="dashboard.php" class="btn btn-dark text-white">Dashboard</a>
            <button type="button" class="btn btn-outline-light shadow-sm" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Logout"><i class="bi bi-power"></i></button>
        </div>
    </div>

    <form method="GET" class="mb-3" autocomplete="off">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Name or Birth Date..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php if(isset($types_result) && $types_result->num_rows > 0) { while($t = $types_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($t['type_name']) ?>" <?= $type == $t['type_name'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                    <?php endwhile; } ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-danger">Search</button>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-outline-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card premium-card">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Remaining</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php
                        $end = new DateTime($row['end_date']);
                        $start = new DateTime($row['start_date']);
                        $now = new DateTime('today');
                        // If membership hasn't started, calculate from start date
                        $base = ($now < $start) ? $start : $now;
                        $diff = $base->diff($end);
                        $days = (int)$diff->format('%r%a');
                        if($row['status'] == 'Active' && $days >= 0) $days++;
                    ?>
                    <tr onclick="window.location='view_member.php?id=<?= $row['id'] ?>';" style="cursor: pointer;">
                        <td data-label="Name">
                            <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                                <?php if(!empty($row['photo']) && file_exists($row['photo'])): ?>
                                    <img src="<?= $row['photo'] ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #dc3545;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-dark text-danger d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; border: 2px solid #444; font-weight: bold; flex-shrink: 0;">
                                        <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></span>
                            </div>
                        </td>
                        <td data-label="Type"><?= htmlspecialchars($row['membership_type']) ?></td>
                        <td data-label="Start Date"><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                        <td data-label="End Date"><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                        <td data-label="Remaining">
                            <?php if($days >= 0): ?>
                                <span class="fw-bold"><?= $days ?> days</span>
                            <?php else: ?>
                                <span class="text-muted">0 days</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <?php if($row['status'] == 'Active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= $row['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell" data-label="Action">
                            <button type="button" class="btn btn-sm btn-dark" onclick="event.stopPropagation(); confirmEdit(<?= $row['id'] ?>)">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
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

<!-- Edit Confirmation Modal -->
<div class="modal fade" id="editConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-pencil-square text-warning" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Edit Member</h4>
                <p class="text-secondary mb-4">Are you sure you want to edit this member?</p>
                <div class="d-grid gap-2">
                    <a href="#" id="confirmEditBtn" class="btn btn-danger">Yes, Edit</a>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmEdit(id) {
    document.getElementById('confirmEditBtn').href = 'edit_member.php?id=' + id;
    var myModal = new bootstrap.Modal(document.getElementById('editConfirmModal'));
    myModal.show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
body.light-mode { background: #f8f9fa !important; color: #212529 !important; }
body.light-mode .card.bg-dark, body.light-mode .premium-card, body.light-mode .login-card { background-color: #fff !important; color: #212529 !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
body.light-mode .bg-dark { background-color: #f8f9fa !important; color: #212529 !important; }
body.light-mode .border-secondary { border-color: #dee2e6 !important; }
body.light-mode .table-dark { background-color: #fff !important; color: #212529 !important; }
body.light-mode .table-dark th { background-color: #e9ecef !important; color: #212529 !important; border-color: #dee2e6 !important; }
body.light-mode .table-dark td { background-color: #fff !important; color: #212529 !important; border-color: #dee2e6 !important; }
body.light-mode .form-control, body.light-mode .form-select, body.light-mode .input-group-text, body.light-mode .detail-item { background-color: #fff !important; color: #212529 !important; border-color: #ced4da !important; }
body.light-mode .form-control:focus, body.light-mode .form-select:focus { border-color: #dc3545 !important; }
body.light-mode.text-white { color: #212529 !important; }
body.light-mode .text-light, body.light-mode .text-muted { color: #6c757d !important; }
body.light-mode .btn-outline-light { color: #212529; border-color: #212529; }
body.light-mode .btn-outline-light:hover { color: #fff; background-color: #212529; }
body.light-mode .card.bg-success, body.light-mode .card.bg-danger, body.light-mode .card.bg-secondary { color: #fff !important; }
body.light-mode .form-label { color: #212529 !important; }
body.light-mode .detail-label { color: #6c757d !important; }
body.light-mode option { background-color: #fff !important; color: #212529 !important; }
body.light-mode .table { color: #212529 !important; }
body.light-mode .table tr, body.light-mode .table th, body.light-mode .table td { color: #212529 !important; }
body.light-mode .table-striped > tbody > tr:nth-of-type(odd) > * { color: #212529 !important; }
body.light-mode .table thead th { background-color: #f8f9fa !important; color: #212529 !important; border-bottom-color: #dee2e6 !important; }
body.light-mode .table tbody td { border-bottom-color: #dee2e6 !important; }
body.light-mode .table-hover tbody tr:hover { background-color: rgba(0,0,0,0.05) !important; color: #212529 !important; }
@media (max-width: 576px) {
    body.light-mode .table tbody tr { background: #fff !important; border: 1px solid #dee2e6 !important; }
    body.light-mode .table tbody td:first-child { background: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; border-bottom-color: rgba(220, 53, 69, 0.2) !important; }
}
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
