<?php 
include "db.php"; 
include "includes/calculations.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
        .premium-card {
            background: rgba(20, 20, 20, 0.95);
            border: 1px solid #4a0000;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(220, 53, 69, 0.15);
            color: white;
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
        .form-label {
            color: #aaa;
            font-size: 0.9rem;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #dc3545;
        }
        /* Fix for date inputs in dark mode */
        input[type="date"] {
            color-scheme: dark;
        }
        .form-control::placeholder {
            color: #aaa;
            opacity: 1;
        }
        option {
            background-color: #222;
            color: white;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #000000, #4a0000); min-height: 100vh;">
<div class="container-xl mt-3 mt-md-5">
    <div class="card premium-card" style="max-width: 800px; margin: auto;">
        <div class="card-header border-0 bg-transparent pt-4 px-4">
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-person-plus-fill"></i> Add New Member</h4>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter Full Name" pattern="[A-Za-z\s\.\-]+" title="Only letters are allowed" oninput="this.value = this.value.replace(/[^A-Za-z\s\.\-]/g, '')">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control" required placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Enter Complete Address" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" placeholder="Select Date of Birth" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sex</label>
                        <select name="gender" class="form-select" required>
                            <option value="" selected disabled>Select Sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start" id="start_date" class="form-control" required placeholder="Select Start Date">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Membership Type</label>
                        <select name="membership_type" id="membership_type" class="form-select" onchange="calculateAmount()">
                            <option value="Regular" data-price="999">Regular (₱999/mo)</option>
                            <option value="Student" data-price="799">Student (₱799/mo)</option>
                            <option value="Walk-in Regular" data-price="69">Walk-in Regular (₱69/day)</option>
                            <option value="Walk-in Student" data-price="59">Walk-in Student (₱59/day)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" id="duration_label">Months</label>
                        <input type="number" name="months" id="months" class="form-control" value="1" min="1" required onchange="calculateAmount()" placeholder="Enter Duration">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end" id="end_date" class="form-control" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Total Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="amount" id="amount" class="form-control" readonly placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="index.php" class="btn btn-dark px-4">Cancel</a>
                    <button name="save" class="btn btn-danger">Save Member</button>
                </div>
            </form>

            <?php
            if(isset($_POST['save'])){
                $name = $_POST['name'];
                $contact = $_POST['contact'];
                $start = $_POST['start'];
                $months = (int)$_POST['months'];
                $address = $_POST['address'];
                $birth_date = $_POST['birth_date'];
                $gender = $_POST['gender'];
                $membership_type = $_POST['membership_type'];
                $amount = $_POST['amount'];

                $is_walk_in = strpos($membership_type, 'Walk-in') !== false;
                
                $start_dt = new DateTime($start);
                if($is_walk_in){
                    $start_dt->modify('+' . ($months - 1) . ' days');
                } else {
                    $days = $months * 30;
                    $start_dt->modify('+' . ($days - 1) . ' days');
                }
                $end = $start_dt->format('Y-m-d');

                // Handle File Upload
                $photo_path = '';
                if(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
                    $check = getimagesize($_FILES["photo"]["tmp_name"]);
                    if($check !== false) {
                        $target_dir = "uploads/";
                        if(!is_dir($target_dir)) mkdir($target_dir);
                        $ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
                        $photo_path = $target_dir . uniqid() . "." . $ext;
                        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
                    }
                }

                $stmt = $conn->prepare("INSERT INTO members (full_name, contact_number, address, birth_date, gender, membership_type, amount, start_date, end_date, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                $stmt->bind_param("ssssssdsss", $name, $contact, $address, $birth_date, $gender, $membership_type, $amount, $start, $end, $photo_path);

                if($stmt->execute()){
                    $member_id = $stmt->insert_id;
                    $stmt_pay = $conn->prepare("INSERT INTO payments (member_id, amount, payment_date) VALUES (?, ?, ?)");
                    $stmt_pay->bind_param("ids", $member_id, $amount, $start);
                    $stmt_pay->execute();
                    echo '
                    <div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
                                <div class="modal-body text-center p-4">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3 fw-bold">Success!</h4>
                                    <p class="text-secondary mb-4">Member has been added successfully.</p>
                                    <button type="button" class="btn btn-danger w-100" onclick="window.location=\'index.php\'">Okay</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var myModal = new bootstrap.Modal(document.getElementById("successModal"));
                            myModal.show();
                        });
                    </script>';
                } else {
                    echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
                }
            }
            ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
    flatpickr("#birth_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        maxDate: "today",
        monthSelectorType: "static"
    });
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        defaultDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            calculateAmount();
        }
    });
    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y"
    });

    document.addEventListener("DOMContentLoaded", function() {
        calculateAmount();
    });
</script>
</body>
</html>
