<?php
include "db.php";
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])){
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("CSRF validation failed.");
    }

    $id = $_POST['id'];
    
    // Get name for logging
    $stmt = $conn->prepare("SELECT full_name, photo FROM members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if($member){
        // Delete photo if exists
        if(!empty($member['photo']) && file_exists($member['photo'])){
            unlink($member['photo']);
        }

        // Delete payments first (foreign key constraint logic, though we don't have strict FKs defined in SQL, it's good practice)
        $del_pay = $conn->prepare("DELETE FROM payments WHERE member_id = ?");
        $del_pay->bind_param("i", $id);
        $del_pay->execute();
        
        // Delete member
        $del_mem = $conn->prepare("DELETE FROM members WHERE id = ?");
        $del_mem->bind_param("i", $id);
        $del_mem->execute();
        
        logActivity($conn, $_SESSION['admin_id'], 'Delete Member', "Deleted member: " . $member['full_name']);
    }
}

header("Location: index.php");
exit;
?>