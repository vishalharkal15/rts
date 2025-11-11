<?php
include 'templates/header.php';

if($_SESSION['role'] != 'admin'){
    echo "<p>Access Denied! Only admins can delete tickets.</p>";
    exit;
}

// Delete ticket logic...
