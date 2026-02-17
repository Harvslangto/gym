<?php
include "db.php";

if(isset($_SESSION['admin_id'])){
    header("Location: index.php");
    exit;
}

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if($password == $row['password']){
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $row['id'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Incorrect password";
        }
    } else {
        $error = "User not found";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        body {
            background: linear-gradient(135deg, #000000, #4a0000);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 { font-family: 'Montserrat', sans-serif; }
        .login-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #4a0000;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(220, 53, 69, 0.2);
            color: white;
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
        }
        .form-control {
            background: #2b2b2b;
            border: 1px solid #444;
            color: white;
        }
        .form-control:focus {
            background: #2b2b2b;
            color: white;
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .form-control::placeholder {
            color: #bbb;
            opacity: 1;
        }
        .btn-login {
            background: linear-gradient(45deg, #8b0000, #dc3545);
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            background: linear-gradient(45deg, #dc3545, #ff4d4d);
        }
        @media (max-width: 576px) {
            .login-card { padding: 1.5rem; }
            .login-logo { width: 120px !important; height: 120px !important; }
            h3 { font-size: 1.5rem; }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="logo/logo.jpg" alt="Logo" class="rounded-circle login-logo" style="width: 180px; height: 180px; object-fit: cover; margin-bottom: 15px;">
            <h3 class="mt-2">TRIZEN FITNESS HUB</h3>
            <p class="text-secondary">Admin</p>
        </div>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger bg-transparent text-danger border-danger'>$error</div>"; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="form-label text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>
            <button name="login" class="btn btn-login w-100 py-2 text-white">LOGIN</button>
        </form>
        <div class="text-center mt-4">
            <small class="text-secondary">&copy; <?= date('Y') ?> Trizen Fitness Hub</small>
        </div>
    </div>
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