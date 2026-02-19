<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['date']) || empty($_GET['date'])){
    header("Location: dashboard.php");
    exit;
}

$date = $_GET['date'];
$formatted_date = date('F j, Y', strtotime($date));

$stmt = $conn->prepare("
    SELECT m.id, m.full_name, m.membership_type, p.amount 
    FROM payments p 
    JOIN members m ON p.member_id = m.id 
    WHERE p.payment_date = ? 
    ORDER BY m.full_name ASC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Members on <?= htmlspecialchars($formatted_date) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
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
        h1, h2, h3, h4, h5, h6 { font-family: 'Russo One', sans-serif; letter-spacing: 1px; }
        .premium-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #4a0000;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(220, 53, 69, 0.15);
            color: white;
            overflow: hidden;
        }
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
        .btn-outline-light { border-width: 2px; font-weight: 600; transition: all 0.3s ease; }
        .btn-outline-light:hover { background: rgba(255,255,255,0.1); color: white; border-color: white; transform: translateY(-2px); }
    </style>
</head>
<body class="text-white">
<div class="container-xl my-3">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="text-uppercase" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Members on <?= htmlspecialchars($formatted_date) ?></h2>
        <a href="dashboard.php" class="btn btn-outline-light"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="card premium-card">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Membership Type</th>
                        <th>Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="view_member.php?id=<?= $row['id'] ?>&return_to=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="text-danger text-decoration-none fw-bold">
                                <?= htmlspecialchars($row['full_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($row['membership_type']) ?></td>
                        <td>₱<?= htmlspecialchars(number_format($row['amount'], 2)) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center text-muted">No members found for this date.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
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
body.light-mode .btn-outline-light { color: #212529; border-color: #212529; }
body.light-mode .btn-outline-light:hover { color: #fff; background-color: #212529; }
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