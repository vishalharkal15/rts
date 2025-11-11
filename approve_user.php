<?php
include 'templates/header.php';

// Only admin allowed
if(!isset($_SESSION['role']) || $_SESSION['role']!='admin'){
    echo "Access Denied"; 
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])){
    $id = intval($_POST['user_id']);
    $stmt = $mysqli->prepare("UPDATE users SET status='approved' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Redirect back to admin panel after approval
header("Location: admin_panel.php");
exit;
