<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

if(isset($_POST['update_price'])){
    $id = $_POST['id'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("UPDATE membership_types SET price = ? WHERE id = ?");
    $stmt->bind_param("di", $price, $id);
    $stmt->execute();
    logActivity($conn, $_SESSION['admin_id'], 'Update Settings', "Updated price for membership type ID: $id to $price");
    $success = "Price updated successfully.";
}

$types = $conn->query("SELECT * FROM membership_types");
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
    </style>
</head>
<body>
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Settings</h2>
        <a href="dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
    </div>

    <?php if(isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

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
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <div class="input-group" style="min-width: 120px; max-width: 150px;">
                                        <span class="input-group-text bg-secondary border-secondary text-white">₱</span>
                                        <input type="number" step="0.01" name="price" class="form-control" value="<?= $row['price'] ?>">
                                    </div>
                            </td>
                            <td>
                                    <button type="submit" name="update_price" class="btn btn-danger">Update</button>
                                </form>
                            </td>
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