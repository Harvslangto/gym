<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

if(isset($_POST['update_price'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("CSRF validation failed.");
    }

    $id = $_POST['id'];
    $price = $_POST['price'];

    if(!is_numeric($price) || $price < 0){
        $error = "Invalid price format.";
    } else {
        $stmt = $conn->prepare("UPDATE membership_types SET price = ? WHERE id = ?");
        $stmt->bind_param("di", $price, $id);
        $stmt->execute();
        logActivity($conn, $_SESSION['admin_id'], 'Update Settings', "Updated price for membership type ID: $id to $price");
        $success = "Price updated successfully.";
    }
}

if(isset($_POST['change_password'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("CSRF validation failed.");
    }

    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if(!password_verify($current_pass, $res['password'])){
        $error = "Current password is incorrect.";
    } elseif($new_pass !== $confirm_pass){
        $error = "New passwords do not match.";
    } elseif(strlen($new_pass) < 6){
        $error = "Password must be at least 6 characters long.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt_update = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_hash, $_SESSION['admin_id']);
        $stmt_update->execute();
        $password_changed = true;
    }
}

$types = $conn->query("SELECT * FROM membership_types");

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
    <title>Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Russo+One&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh; color: white; }
        h1, h2, h3, h4 { font-family: 'Russo One', sans-serif; }
        .premium-card { background: rgba(20, 20, 20, 0.95); border: 1px solid #4a0000; border-radius: 15px; }
        .form-control { background: rgba(255,255,255,0.1); border: 1px solid #444; color: white; }
        .form-control:focus { background: rgba(255,255,255,0.15); color: white; border-color: #dc3545; box-shadow: none; }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.8); opacity: 1; }
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
        .security-card { background: linear-gradient(145deg, #151515, #0a0a0a); border: 1px solid #333; }
    </style>
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Settings</h2>
        <a href="dashboard.php?<?= $dashboard_qs ?>" class="btn btn-dark border-secondary shadow-sm"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="card premium-card">
        <div class="card-header bg-transparent border-bottom border-secondary p-3">
            <h5 class="mb-0 text-danger">Membership Pricing</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th>Membership Type</th>
                            <th>Duration Unit</th>
                            <th>Current Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $types->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['type_name']) ?></td>
                            <td><?= htmlspecialchars($row['duration_unit']) ?></td>
                            <td>
                                    <div class="input-group" style="min-width: 120px; max-width: 150px;">
                                        <span class="input-group-text bg-secondary border-secondary text-white">₱</span>
                                        <input type="number" step="0.01" id="price_<?= $row['id'] ?>" class="form-control" value="<?= $row['price'] ?>">
                                    </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger" onclick="confirmUpdate(<?= $row['id'] ?>, this)" data-name="<?= htmlspecialchars($row['type_name']) ?>">Update</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card premium-card mt-4 border-0 shadow-lg security-card">
        <div class="card-header bg-transparent border-0 pt-3 text-center">
            <div class="d-inline-block p-2 rounded-circle mb-2" style="background: rgba(220, 53, 69, 0.1); box-shadow: 0 0 15px rgba(220, 53, 69, 0.2);">
                <i class="bi bi-shield-lock-fill text-danger" style="font-size: 2rem;"></i>
            </div>
            <h5 class="mt-1 fw-bold text-uppercase text-white" style="letter-spacing: 2px;">Security Settings</h5>
            <p class="text-white-50 small mb-0">Update your admin credentials securely</p>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="mb-3">
                    <label class="form-label text-uppercase small fw-bold text-white">Current Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-white"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small fw-bold text-white">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-white"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required minlength="6">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small fw-bold text-white">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-white"><i class="bi bi-check-circle-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="6">
                        </div>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" name="change_password" class="btn btn-danger py-2 fw-bold text-uppercase" style="letter-spacing: 1px; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Confirmation Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-currency-exchange text-warning" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Update Price?</h4>
                <p class="text-white-50 mb-4" id="modal_msg">Are you sure you want to update this price?</p>
                <form method="POST">
                    <input type="hidden" name="id" id="modal_id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="price" id="modal_price">
                    <div class="d-grid gap-2">
                        <button type="submit" name="update_price" class="btn btn-danger">Yes, Update</button>
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if(isset($password_changed) && $password_changed): ?>
<div class="modal fade" id="passwordSuccessModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.98); border: 1px solid #198754; color: white; box-shadow: 0 0 30px rgba(25, 135, 84, 0.2);">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem; filter: drop-shadow(0 0 10px rgba(25, 135, 84, 0.5));"></i>
                </div>
                <h3 class="fw-bold mb-3">Password Updated!</h3>
                <p class="text-white-50 mb-4">Your password has been changed successfully.<br>You will be logged out to sign in with your new credentials.</p>
                <button type="button" class="btn btn-success w-100 py-2 fw-bold text-uppercase" onclick="window.location.href='logout.php'">Login Again</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var myModal = new bootstrap.Modal(document.getElementById("passwordSuccessModal"));
    myModal.show();
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmUpdate(id, btn) {
    const name = btn.getAttribute('data-name');
    const price = document.getElementById('price_' + id).value;
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_price').value = price;
    document.getElementById('modal_msg').innerText = `Are you sure you want to update ${name} to ₱${price}?`;
    new bootstrap.Modal(document.getElementById('updateModal')).show();
}
</script>
<style>
body.light-mode { background: #f8f9fa !important; color: #212529 !important; }
body.light-mode .card.bg-dark, body.light-mode .premium-card, body.light-mode .login-card, body.light-mode .security-card { background: #fff !important; color: #212529 !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
body.light-mode .bg-dark { background-color: #f8f9fa !important; color: #212529 !important; }
body.light-mode .border-secondary { border-color: #dee2e6 !important; }
body.light-mode .table-dark { background-color: #fff !important; color: #212529 !important; }
body.light-mode .table-dark th { background-color: #343a40 !important; color: #fff !important; }
body.light-mode .table-dark td { background-color: #fff !important; color: #212529 !important; border-color: #dee2e6 !important; }
body.light-mode .form-control, body.light-mode .form-select, body.light-mode .input-group-text, body.light-mode .detail-item { background-color: #fff !important; color: #212529 !important; border-color: #ced4da !important; }
body.light-mode .form-control:focus, body.light-mode .form-select:focus { border-color: #dc3545 !important; }
body.light-mode .text-white { color: #212529 !important; }
body.light-mode .text-white-50 { color: #6c757d !important; }
body.light-mode .text-light, body.light-mode .text-muted { color: #6c757d !important; }
body.light-mode .btn-outline-light { color: #212529; border-color: #212529; }
body.light-mode .btn-outline-light:hover { color: #fff; background-color: #212529; }
body.light-mode .card.bg-success, body.light-mode .card.bg-danger, body.light-mode .card.bg-secondary { color: #fff !important; }
body.light-mode .form-label { color: #212529 !important; }
body.light-mode .detail-label { color: #6c757d !important; }
body.light-mode option { background-color: #fff !important; color: #212529 !important; }
body.light-mode .form-control::placeholder { color: #6c757d !important; opacity: 1; }
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