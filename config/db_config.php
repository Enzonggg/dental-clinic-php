<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem";  // Changed from dental_clinic to clinicdentalsystem

// First check if the database exists, if not redirect to setup
$temp_conn = new mysqli($host, $username, $password);
if (!$temp_conn->select_db($database)) {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../setup_database.php");
    exit;
}
$temp_conn->close();

// Now try to connect with the database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if users table exists, if not redirect to setup
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../setup_database.php");
    exit;
}

// Set proper paths for the application
define('BASE_URL', '/clinicdentalsystem');
?>
