<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Automatically update expired members
$conn->query("UPDATE members SET status = 'Expired' WHERE end_date < CURDATE() AND status = 'Active'");

$type = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        @media (max-width: 576px) {
            /* Hide table headers */
            .table thead { display: none; }
            
            /* Card styling for rows */
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: #ffffff;
                color: #000000;
                border: 1px solid #dee2e6;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            /* Flex layout for cells */
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.8rem 1rem;
                border-bottom: 1px solid #f0f0f0;
                text-align: right;
            }
            
            /* Labels for data */
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #666;
                text-align: left;
            }
            
            /* Name styling (Card Header) */
            .table tbody td:first-child {
                background: rgba(220, 53, 69, 0.1);
                justify-content: center;
                font-size: 1.1rem;
                border-radius: 12px 12px 0 0;
                border-bottom: 1px solid rgba(220, 53, 69, 0.2);
            }
            .table tbody td:first-child::before { display: none; }
            
            /* Action styling (Card Footer) */
            .table tbody td:last-child {
                border-bottom: none;
                padding: 1rem;
                justify-content: stretch;
            }
            .table tbody td:last-child::before { display: none; }
            .table tbody td:last-child .btn { width: 100%; }
        }
    </style>
</head>

<body class="text-white">
<div class="container-xl my-3">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2>Gym Members <span class="badge bg-secondary fs-5"><?= $result->num_rows ?></span></h2>
        <div class="d-flex gap-2 flex-wrap">
            <a href="add_member.php" class="btn btn-danger">+ Add Member</a>
            <a href="dashboard.php" class="btn btn-dark text-white">Dashboard</a>
            <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
        </div>
    </div>

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search Name or Birth Date..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-12 col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Regular" <?= $type == 'Regular' ? 'selected' : '' ?>>Regular</option>
                    <option value="Student" <?= $type == 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Walk-in Regular" <?= $type == 'Walk-in Regular' ? 'selected' : '' ?>>Walk-in Regular</option>
                    <option value="Walk-in Student" <?= $type == 'Walk-in Student' ? 'selected' : '' ?>>Walk-in Student</option>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-dark">
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
                        $start = new DateTime($row['start_date']);
                        $end = new DateTime($row['end_date']);
                        $now = new DateTime('today');
                        
                        if($start > $now){
                            $diff = $start->diff($end);
                        } else {
                            $diff = $now->diff($end);
                        }
                        
                        $days = (int)$diff->format('%r%a');
                        if($days >= 0) $days = $days + 1;
                    ?>
                    <tr>
                        <td data-label="Name">
                            <a href="view_member.php?id=<?= $row['id'] ?>" class="text-danger text-decoration-none fw-bold">
                                <?= htmlspecialchars($row['full_name']) ?>
                            </a>
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
                        <td data-label="Action">
                            <a href="edit_member.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-dark">Edit</a>
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
