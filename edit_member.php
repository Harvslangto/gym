<?php
include "db.php";
include "includes/calculations.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if(!$member){
    echo "Member not found";
    exit;
}

// Check if current member type is walk-in based on DB
$stmt_check = $conn->prepare("SELECT duration_unit FROM membership_types WHERE type_name = ?");
$stmt_check->bind_param("s", $member['membership_type']);
$stmt_check->execute();
$type_info = $stmt_check->get_result()->fetch_assoc();
$is_walk_in = ($type_info && $type_info['duration_unit'] == 'Day');

$months_duration = get_membership_duration_in_months_or_days($member['start_date'], $member['end_date'], $is_walk_in);
if($months_duration < 1) $months_duration = 1;

if(isset($_POST['update'])){
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $birth_date = $_POST['birth_date'];
    if(empty($birth_date) || $birth_date == '0000-00-00' || $birth_date == '1970-01-01'){
        $birth_date = $member['birth_date'];
    }
    $gender = $_POST['gender'];
    $membership_type = $_POST['membership_type'];
    $amount = $_POST['amount'];
    $start = $_POST['start']; 
    $status = $_POST['status'];

    // Validate dates to prevent 0000-00-00 errors
    if(empty($start) || $start == '0000-00-00'){
        $start = $member['start_date'];
    }

    // Check type again for POST data
    $stmt_check->bind_param("s", $membership_type);
    $stmt_check->execute();
    $type_info_post = $stmt_check->get_result()->fetch_assoc();
    $is_walk_in_post = ($type_info_post && $type_info_post['duration_unit'] == 'Day');

    // For walk-ins, duration is always 1 day, regardless of input
    $months = $is_walk_in_post ? 1 : (int)$_POST['months'];
    
    $start_dt = new DateTime($start);
    if($is_walk_in_post){
        $start_dt->modify('+' . ($months - 1) . ' days');
    } else {
        $start_dt->modify('+' . $months . ' months')->modify('-1 day');
    }
    $end = $start_dt->format('Y-m-d');

    if(empty($end) || $end == '0000-00-00'){
        $end = $member['end_date'];
    }

    $photo_path = $member['photo'];
    if(isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])){
        $data = $_POST['cropped_image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); 
            $data = base64_decode($data);
            
            if($data !== false){
                $target_dir = "uploads/";
                if(!is_dir($target_dir)) mkdir($target_dir);
                $photo_path = $target_dir . uniqid() . "." . $type;
                file_put_contents($photo_path, $data);
            }
        }
    } elseif(isset($_FILES['photo']['name']) && $_FILES['photo']['name'] != ""){
        $check = getimagesize($_FILES["photo"]["tmp_name"]);
        if($check !== false) {
            $target_dir = "uploads/";
            if(!is_dir($target_dir)) mkdir($target_dir);
            $ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $photo_path = $target_dir . uniqid() . "." . $ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
        }
    }

    $conn->begin_transaction();
    try {
        $update = $conn->prepare("UPDATE members SET full_name=?, contact_number=?, address=?, birth_date=?, gender=?, membership_type=?, amount=?, start_date=?, end_date=?, status=?, photo=? WHERE id=?");
        $update->bind_param("ssssssdssssi", $name, $contact, $address, $birth_date, $gender, $membership_type, $amount, $start, $end, $status, $photo_path, $id);
        if (!$update->execute()) throw new Exception($conn->error);

        // Update the corresponding payment record to reflect the corrected amount/date.
        // This assumes the original payment is tied to the original start date.
        $old_start = $member['start_date'];
        $pay_update = $conn->prepare("UPDATE payments SET amount = ?, payment_date = ? WHERE member_id = ? AND payment_date = ? LIMIT 1");
        $pay_update->bind_param("dsis", $amount, $start, $id, $old_start);
        $pay_update->execute(); // We don't throw an error here, as the payment might not exist to be updated.

        $conn->commit();
        logActivity($conn, $_SESSION['admin_id'], 'Edit Member', "Updated member: $name");

        // Re-fetch updated member data so the form shows new info
        $stmt_refetch = $conn->prepare("SELECT * FROM members WHERE id = ?");
        $stmt_refetch->bind_param("i", $id);
        $stmt_refetch->execute();
        $member = $stmt_refetch->get_result()->fetch_assoc();
        $months_duration = (int)$_POST['months'];
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<div class='alert alert-danger'>Error updating member: " . $e->getMessage() . "</div>";
    }
}

// Fetch types for dropdown
$types_result = $conn->query("SELECT * FROM membership_types");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Member</title>
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
            <h4 class="mb-0 text-danger fw-bold"><i class="bi bi-pencil-square"></i> Edit Member</h4>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($member['full_name']) ?>" required placeholder="Enter Full Name" pattern="[A-Za-z\s\.\-]+" title="Only letters are allowed" oninput="this.value = this.value.replace(/[^A-Za-z\s\.\-]/g, '')">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($member['contact_number']) ?>" required placeholder="09xxxxxxxxx" pattern="09[0-9]{9}" maxlength="11" title="Please enter a valid 11-digit Philippine mobile number starting with 09" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" required placeholder="Enter Complete Address"><?= htmlspecialchars($member['address']) ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="birth_date" id="birth_date" class="form-control" value="<?= $member['birth_date'] ?>" required placeholder="Select Date of Birth">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Age</label>
                        <input type="text" id="age" class="form-control" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Sex</label>
                        <select name="gender" class="form-select" required>
                            <option value="" disabled>Select Sex</option>
                            <option value="Male" <?= $member['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $member['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= $member['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Membership Type</label>
                        <select name="membership_type" id="membership_type" class="form-select" onchange="calculateAmount(true)">
                            <?php while($t = $types_result->fetch_assoc()): 
                                $is_wi = ($t['duration_unit'] == 'Day') ? '1' : '0';
                                $unit_lbl = ($t['duration_unit'] == 'Day') ? '/day' : '/mo';
                                $selected = ($member['membership_type'] == $t['type_name']) ? 'selected' : '';
                            ?>
                                <option value="<?= $t['type_name'] ?>" data-price="<?= $t['price'] ?>" data-is-walk-in="<?= $is_wi ?>" <?= $selected ?>>
                                    <?= $t['type_name'] ?> (₱<?= $t['price'] . $unit_lbl ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" id="duration_label">Months</label>
                        <input type="number" name="months" id="months" class="form-control" value="<?= $months_duration ?>" min="1" required onchange="calculateAmount(true)">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start" id="start_date" class="form-control" value="<?= $member['start_date'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end" id="end_date" class="form-control" value="<?= $member['end_date'] ?>" required readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="amount" id="amount" class="form-control" value="<?= $member['amount'] ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" <?= $member['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Expired" <?= $member['status'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" id="photo_input" class="d-none" accept="image/*">
                    <input type="hidden" name="cropped_image" id="cropped_image">

                    <div id="photo_upload_area" onclick="document.getElementById('photo_input').click()">
                        <div id="preview_container" class="text-center">
                            <?php if(!empty($member['photo']) && file_exists($member['photo'])): ?>
                                <img id="preview_image" src="<?= $member['photo'] . '?t=' . time() ?>">
                                <p class="text-secondary mb-0 mt-2"><small>Click image to change</small></p>
                            <?php else: ?>
                                <div id="upload_placeholder">
                                    <i class="bi bi-camera-fill" style="font-size: 2rem; color: #aaa;"></i>
                                    <p class="text-secondary mb-0 mt-2">Click to Upload Photo</p>
                                </div>
                                <img id="preview_image" src="" style="display: none;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row g-2 mt-4 justify-content-end">
                    <div class="col-12 col-md-auto"><a href="view_member.php?id=<?= $id ?>" class="btn btn-dark w-100">Cancel</a></div>
                    <div class="col-12 col-md-auto"><button name="update" class="btn btn-danger w-100">Update Member</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if(isset($success) && $success): ?>
<div class="modal fade" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(20, 20, 20, 0.95); border: 1px solid #dc3545; color: white;">
            <div class="modal-body text-center p-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <h4 class="mt-3 fw-bold">Success!</h4>
                <p class="text-secondary mb-4">Member has been updated successfully.</p>
                <button type="button" class="btn btn-danger w-100" onclick="window.location='view_member.php?id=<?= $id ?>'">Okay</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById("successModal"));
        myModal.show();
    });
</script>
<?php endif; ?>

<!-- Image Cropping Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Crop Profile Photo</h5>
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
    function calculateAge() {
        const birthDateInput = document.getElementById('birth_date');
        const ageInput = document.getElementById('age');
        if (birthDateInput.value && birthDateInput.value !== '0000-00-00') {
            const birthDate = new Date(birthDateInput.value);
            if (!isNaN(birthDate.getTime())) {
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                ageInput.value = age;
            }
        } else {
            ageInput.value = '';
        }
    }

    flatpickr("#birth_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            calculateAge();
        }
    });
    flatpickr("#start_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        onChange: function(selectedDates, dateStr, instance) {
            calculateAmount(true);
        }
    });
    flatpickr("#end_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y"
    });

    document.addEventListener("DOMContentLoaded", function() {
        calculateAmount(true);
        calculateAge();
    });

    document.getElementById('membership_type').addEventListener('change', function() {
        calculateAmount(true);
    });

    document.getElementById('months').addEventListener('input', function() {
        calculateAmount(true);
    });

    // Image Cropper Logic
    let cropper;
    const photoInput = document.getElementById('photo_input');
    const cropModalElement = document.getElementById('cropModal');
    const cropModal = new bootstrap.Modal(cropModalElement);
    const imageToCrop = document.getElementById('image_to_crop');
    const cropButton = document.getElementById('crop_button');
    const previewImage = document.getElementById('preview_image');
    const croppedImageInput = document.getElementById('cropped_image');
    const uploadPlaceholder = document.getElementById('upload_placeholder');

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
            aspectRatio: 1,
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
        photoInput.value = '';
    });

    cropButton.addEventListener('click', function() {
        if(cropper) {
            const canvas = cropper.getCroppedCanvas();
            const base64data = canvas.toDataURL('image/jpeg', 1.0);
            croppedImageInput.value = base64data;
            previewImage.src = base64data;
            previewImage.style.display = 'inline-block';
            if(uploadPlaceholder) uploadPlaceholder.style.display = 'none';
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