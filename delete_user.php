<?php
include 'templates/header.php';

// Only admins can delete users
if ($_SESSION['role'] != 'admin') {
    echo "<p>Access Denied!</p>";
    exit;
}

// Check if ID is provided
if(!isset($_GET['id'])) {
    echo "<p>User ID not specified!</p>";
    exit;
}

$user_id = (int)$_GET['id'];

// Prevent admin from deleting themselves
if($user_id == $_SESSION['user_id']){
    echo "<p>You cannot delete your own account!</p>";
    echo "<a href='users.php'>Go back</a>";
    exit;
}

// Delete user
$stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
if($stmt->execute()){
    echo "<p style='color:green;'>User deleted successfully!</p>";
    echo "<a href='users.php'>Go back to Users List</a>";
} else {
    echo "<p style='color:red;'>Failed to delete user.</p>";
    echo "<a href='users.php'>Go back to Users List</a>";
}
?>
<?php include 'templates/footer.php'; ?>
