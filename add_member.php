<?php 
include "db.php"; 
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

// Fetch membership types from DB
$types_result = $conn->query("SELECT * FROM membership_types");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
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
        @media (min-width: 768px) {
            .container-xl {
                margin: auto !important;
            }
        }
        #photo_upload_area {
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
        }
        #photo_upload_area:hover {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: #dc3545;
        }
        #preview_image {
            width: 150px; height: 150px; object-fit: cover;
            border-radius: 50%; border: 3px solid #dc3545;
        }
    </style>
</head>
<body>
<div class="container-xl my-3">
    <div class="card premium-card" style="max-width: 800px; margin: auto;">
        <div class="card-header border-0 bg-transparent pt-4 px-4">
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-person-plus-fill"></i> Add New Member</h4>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                            <?php while($t = $types_result->fetch_assoc()): 
                                $is_walk_in = ($t['duration_unit'] == 'Day') ? '1' : '0';
                                $unit_label = ($t['duration_unit'] == 'Day') ? '/day' : '/mo';
                            ?>
                                <option value="<?= $t['type_name'] ?>" data-price="<?= $t['price'] ?>" data-is-walk-in="<?= $is_walk_in ?>">
                                    <?= $t['type_name'] ?> (₱<?= $t['price'] . $unit_label ?>)
                                </option>
                            <?php endwhile; ?>
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
                        <input type="file" name="photo" id="photo_input" class="d-none" accept="image/*">
                        <input type="hidden" name="cropped_image" id="cropped_image">
                        
                        <div id="photo_upload_area" onclick="document.getElementById('photo_input').click()">
                            <div id="upload_placeholder">
                                <i class="bi bi-camera-fill" style="font-size: 2rem; color: #aaa;"></i>
                                <p class="text-secondary mb-0 mt-2">Click to Upload Photo</p>
                            </div>
                            <div id="preview_container" class="text-center" style="display:none;">
                                <img id="preview_image" src="">
                                <p class="text-secondary mb-0 mt-2"><small>Click image to change</small></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-2 mt-4 justify-content-end">
                    <div class="col-12 col-md-auto"><a href="index.php" class="btn btn-dark w-100">Cancel</a></div>
                    <div class="col-12 col-md-auto"><button name="save" class="btn btn-danger w-100">Save Member</button></div>
                </div>
            </form>

            <?php
            if(isset($_POST['save'])){
                if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
                    die('<div class="alert alert-danger">CSRF validation failed. Please refresh and try again.</div>');
                }

                $name = $_POST['name'];
                $contact = $_POST['contact'];
                $gender = $_POST['gender'];

                // Strict Backend Validation
                if(!preg_match('/^09[0-9]{9}$/', $contact)){
                    throw new Exception("Invalid contact number. Must be 11 digits starting with 09.");
                }
                if(!in_array($gender, ['Male', 'Female', 'Other'])){
                    throw new Exception("Invalid sex selected.");
                }
                
                $check = $conn->prepare("SELECT id FROM members WHERE full_name = ?");
                $check->bind_param("s", $name);
                $check->execute();
                $dup_res = $check->get_result();

                if($dup_res->num_rows > 0){
                    $existing_id = $dup_res->fetch_assoc()['id'];
                    echo '
                   <div class="modal fade" id="duplicateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
                                <div class="modal-body text-center p-4">
                                    <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3 fw-bold">Member Exists</h4>
                                    <p class="text-secondary mb-4">User already exists. View profile to Renew?</p>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-danger" onclick="window.location.href=\'view_member.php?id=' . $existing_id . '\'">Yes, View Profile</button>
                                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">No, Cancel</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var myModal = new bootstrap.Modal(document.getElementById("duplicateModal"));
                            myModal.show();
                        });
                    </script>';
                } else {
                $start = $_POST['start'];
                $address = $_POST['address'];
                $birth_date = $_POST['birth_date'];
                $membership_type = $_POST['membership_type'];

                // Determine if walk-in based on DB type
                $stmt_type = $conn->prepare("SELECT price, duration_unit FROM membership_types WHERE type_name = ?");
                $stmt_type->bind_param("s", $membership_type);
                $stmt_type->execute();
                $type_data = $stmt_type->get_result()->fetch_assoc();
                $is_walk_in = ($type_data && $type_data['duration_unit'] == 'Day');
                $base_price = $type_data['price'];

                // For walk-ins, duration is always 1 day, regardless of input
                $months = $is_walk_in ? 1 : (int)$_POST['months'];
                if($months < 1) {
                    throw new Exception("Duration must be at least 1.");
                }
                if($months > 60) {
                    throw new Exception("Duration cannot exceed 60 months (5 years).");
                }
                
                $start_dt = new DateTime($start);
                if($is_walk_in){
                    // Logic for days
                    $start_dt->modify('+' . ($months - 1) . ' days');
                } else {
                    $start_dt->modify('+' . $months . ' months')->modify('-1 day');
                }
                $end = $start_dt->format('Y-m-d');

                // Recalculate amount on backend to prevent tampering
                $amount = $base_price * $months;

                // Handle File Upload
                $photo_path = '';
                if(isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])){
                    $data = $_POST['cropped_image'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                        $data = substr($data, strpos($data, ',') + 1);
                        $type = strtolower($type[1]); 
                        if(!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){
                            throw new Exception("Invalid image type.");
                        }
                        $data = base64_decode($data);
                        
                        if($data !== false){
                            $target_dir = "uploads/";
                            if(!is_dir($target_dir)) mkdir($target_dir);
                            $photo_path = $target_dir . bin2hex(random_bytes(16)) . "." . $type;
                            file_put_contents($photo_path, $data);
                        }
                    }
                } elseif(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
                    $check = getimagesize($_FILES["photo"]["tmp_name"]);
                    if($check !== false) {
                        $target_dir = "uploads/";
                        if(!is_dir($target_dir)) mkdir($target_dir);
                        $ext = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        if(!in_array($ext, $allowed_exts)){
                            throw new Exception("Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.");
                        }
                        $photo_path = $target_dir . bin2hex(random_bytes(16)) . "." . $ext;
                        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
                    }
                }

                $conn->begin_transaction();
                try {
                $stmt = $conn->prepare("INSERT INTO members (full_name, contact_number, address, birth_date, gender, membership_type, amount, start_date, end_date, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?)");
                $stmt->bind_param("ssssssdsss", $name, $contact, $address, $birth_date, $gender, $membership_type, $amount, $start, $end, $photo_path);

                    if(!$stmt->execute()){
                        throw new Exception($stmt->error);
                    }

                    $member_id = $stmt->insert_id; 
                    $stmt_pay = $conn->prepare("INSERT INTO payments (member_id, amount, payment_date) VALUES (?, ?, ?)"); 
                    $stmt_pay->bind_param("ids", $member_id, $amount, $start); 
                    if(!$stmt_pay->execute()){
                        throw new Exception($stmt_pay->error);
                    }

                    $conn->commit();
                    logActivity($conn, $_SESSION['admin_id'], 'Add Member', "Added new member: $name ($membership_type)");

                    echo ' <div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true"> <div class="modal-dialog modal-dialog-centered"> <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;"> <div class="modal-body text-center p-4"> <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i> <h4 class="mt-3 fw-bold">Success!</h4> <p class="text-secondary mb-4">Member has been added successfully.</p> <button type="button" class="btn btn-danger w-100" onclick="window.location=\'index.php\'">Okay</button> </div> </div> </div> </div> <script> document.addEventListener("DOMContentLoaded", function() { var myModal = new bootstrap.Modal(document.getElementById("successModal")); myModal.show(); }); </script>';
                } catch (Exception $e) {
                    $conn->rollback();
                    // Only show the message if it's one of our custom exceptions (not raw SQL errors)
                    $msg = $e->getMessage();
                    if(strpos($msg, 'Invalid') === false && strpos($msg, 'CSRF') === false) {
                        $msg = "An error occurred while saving. Please try again.";
                    }
                    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($msg) . "</div>";
                }
                /*
                if($stmt->execute()){ ... } else { ... }
                */
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Crop Image</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="img-container" style="max-height: 60vh; height: 500px; overflow: hidden;">
                    <img id="image_to_crop" src="" style="max-width: 100%; display: block; max-height: 100%;">
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="crop_button">Crop & Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
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

    // Cropper Logic
    let cropper;
    const photoInput = document.getElementById('photo_input');
    const cropModalElement = document.getElementById('cropModal');
    const cropModal = new bootstrap.Modal(cropModalElement);
    const imageToCrop = document.getElementById('image_to_crop');
    const cropButton = document.getElementById('crop_button');
    const previewContainer = document.getElementById('preview_container');
    const previewImage = document.getElementById('preview_image');
    const croppedImageInput = document.getElementById('cropped_image');

    photoInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                imageToCrop.src = e.target.result;
                cropModal.show();
            };
            reader.readAsDataURL(file);
        }
    });

    cropModalElement.addEventListener('shown.bs.modal', function () {
        cropper = new Cropper(imageToCrop, {
            aspectRatio: 1, // Square crop for profile
            viewMode: 1,
            autoCropArea: 1,
            dragMode: 'move',
            responsive: true,
        });
    });

    cropModalElement.addEventListener('hidden.bs.modal', function () {
        if(cropper) {
            cropper.destroy();
            cropper = null;
        }
    });

    cropButton.addEventListener('click', function() {
        if(cropper) {
            const canvas = cropper.getCroppedCanvas();
            const base64data = canvas.toDataURL('image/jpeg', 1.0);
            croppedImageInput.value = base64data;
            previewImage.src = base64data;
            previewContainer.style.display = 'block';
            document.getElementById('upload_placeholder').style.display = 'none';
            cropModal.hide();
        }
    });
</script>
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
