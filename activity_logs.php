<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$logs = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
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
    </style>
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Activity Logs</h2>
        <a href="dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
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
</body>
</html>