<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

if(isset($_POST['clear_logs'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("CSRF validation failed.");
    }

    $conn->query("TRUNCATE TABLE activity_logs");
    logActivity($conn, $_SESSION['admin_id'], 'System', 'Activity logs cleared by admin');
    $qs = http_build_query($_GET);
    header("Location: activity_logs.php" . ($qs ? "?$qs" : ""));
    exit;
}

$logs = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");

$dashboard_qs = http_build_query([
    'type' => $_GET['type'] ?? '',
    'year' => $_GET['year'] ?? '',
    'month' => $_GET['month'] ?? '',
    'day' => $_GET['day'] ?? ''
]);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh; color: white; }
        h1, h2, h3 { font-family: 'Russo One', sans-serif; }
        .premium-card { background: rgba(20, 20, 20, 0.95); border: 1px solid #4a0000; border-radius: 15px; }
        .table-dark { --bs-table-bg: transparent; }
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
    </style>
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Activity Logs</h2>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">Clear Logs</button>
            <a href="dashboard.php?<?= $dashboard_qs ?>" class="btn btn-dark border-secondary shadow-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="card premium-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Action</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td class="text-nowrap"><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                            <td><span class="badge bg-danger"><?= htmlspecialchars($row['action']) ?></span></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Clear All Logs?</h4>
                <p class="text-secondary mb-4">Are you sure you want to clear all activity logs? This cannot be undone.</p>
                <div class="d-grid gap-2">
                    <form method="POST" class="w-100">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="clear_logs" class="btn btn-danger w-100">Yes, Clear Logs</button>
                    </form>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>