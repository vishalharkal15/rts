<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use JSON file-based storage (no database needed!)
require_once __DIR__ . '/config_json.php';

/*
// MySQL Database configuration (if you have MySQL with password)
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "your_mysql_password_here"; // Set your MySQL password
$DB_NAME = "rts_ticket_system";

// Create database connection
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Optional: set charset to utf8
$mysqli->set_charset("utf8");
*/
?>
