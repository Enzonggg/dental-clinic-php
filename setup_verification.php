<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem";

echo "<h1>Clinic Dental System - Setup Verification</h1>";
echo "<div style='margin: 20px; font-family: Arial, sans-serif;'>";

// Check if database exists
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("<p>❌ Connection failed: " . $conn->connect_error . "</p>");
}

if (!$conn->select_db($database)) {
    echo "<p>❌ Database '$database' does not exist. Please run <a href='setup_database.php'>setup_database.php</a> first.</p>";
    exit;
}

echo "<p>✅ Connection to database successful</p>";

// Check required tables
$required_tables = [
    'users', 'appointments', 'medical_history', 
    'treatments', 'payments'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (count($missing_tables) > 0) {
    echo "<p>❌ Missing tables: " . implode(", ", $missing_tables) . "</p>";
    echo "<p>Please run <a href='setup_database.php'>setup_database.php</a> to create missing tables.</p>";
} else {
    echo "<p>✅ All required tables exist</p>";
}

// Check admin user
$admin_check = $conn->query("SELECT * FROM users WHERE email = 'admin@gmail.com' AND role = 'admin'");
if ($admin_check->num_rows == 0) {
    echo "<p>❌ Admin user (admin@gmail.com) not found!</p>";
    echo "<p>Please run <a href='setup_database.php'>setup_database.php</a> to create the admin user.</p>";
} else {
    echo "<p>✅ Admin user exists</p>";
}

// Check doctor account
$doctor_check = $conn->query("SELECT * FROM users WHERE role = 'doctor'");
if ($doctor_check->num_rows == 0) {
    echo "<p>⚠️ No doctor accounts found</p>";
    echo "<p>You may want to create a doctor account or run <a href='setup_database.php'>setup_database.php</a> to create a test doctor.</p>";
} else {
    echo "<p>✅ Doctor account found</p>";
}

echo "<h2>System Status: " . (count($missing_tables) == 0 && $admin_check->num_rows > 0 ? "READY" : "NEEDS SETUP") . "</h2>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Go to Homepage</a>";
echo "<a href='setup_database.php' style='display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Run Setup</a>";
echo "</div>";

echo "</div>";
$conn->close();
?>
