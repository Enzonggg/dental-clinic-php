<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem";

// Create connection
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Password Reset</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
               text-decoration: none; border-radius: 4px; margin-top: 20px; }
        code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Admin Password Reset Tool</h1>";

// Check if the database exists
if (!$conn->select_db($database)) {
    echo "<div class='card error'>
            <h2>Database Not Found</h2>
            <p>The database '{$database}' does not exist. Creating it now...</p>
          </div>";
    
    // Create database
    if ($conn->query("CREATE DATABASE {$database}")) {
        echo "<div class='card success'>
                <h2>Database Created</h2>
                <p>Successfully created database '{$database}'</p>
              </div>";
        $conn->select_db($database);
    } else {
        echo "<div class='card error'>
                <h2>Database Creation Failed</h2>
                <p>Error creating database: {$conn->error}</p>
              </div>";
        exit;
    }
}

// Make sure we're connected to the database
$conn->select_db($database);

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    echo "<div class='card error'>
            <h2>Users Table Not Found</h2>
            <p>The 'users' table does not exist. Creating it now...</p>
          </div>";
    
    // Create users table
    $create_table_sql = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
        specialization VARCHAR(100) NULL,
        phone VARCHAR(20) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table_sql)) {
        echo "<div class='card success'>
                <h2>Table Created</h2>
                <p>Successfully created 'users' table</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Table Creation Failed</h2>
                <p>Error creating table: {$conn->error}</p>
              </div>";
        exit;
    }
}

// Define admin credentials
$admin_email = "admin@gmail.com";
$admin_password = "admin123";
$admin_name = "Admin User";
$admin_role = "admin";

// Generate password hash with consistent options
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin exists
$check_sql = "SELECT * FROM users WHERE email = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $admin_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Admin exists, update password
    $admin = $check_result->fetch_assoc();
    $admin_id = $admin['id'];
    
    echo "<div class='card success'>
            <h2>Admin User Found</h2>
            <p>Admin user found with ID: {$admin_id}</p>
            <p>Current password hash: <code>" . substr($admin['password'], 0, 30) . "...</code></p>
          </div>";
    
    // Delete the admin user to avoid any issues with unique constraints
    $delete_sql = "DELETE FROM users WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $admin_id);
    
    if ($delete_stmt->execute()) {
        echo "<div class='card success'>
                <h2>Admin User Removed</h2>
                <p>Successfully removed existing admin user for clean recreation.</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Admin Removal Failed</h2>
                <p>Failed to remove existing admin: {$conn->error}</p>
              </div>";
    }
}

// Create fresh admin account
$insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssss", $admin_name, $admin_email, $hashed_password, $admin_role);

if ($insert_stmt->execute()) {
    echo "<div class='card success'>
            <h2>Admin Account Created</h2>
            <p>Successfully created admin account with fresh credentials.</p>
            <p>New password hash: <code>" . substr($hashed_password, 0, 30) . "...</code></p>
          </div>";
} else {
    echo "<div class='card error'>
            <h2>Admin Creation Failed</h2>
            <p>Failed to create admin account: {$conn->error}</p>
          </div>";
}

// Verify login works
echo "<div class='card'>
        <h2>Password Verification Test</h2>";

// Get the admin user again
$verify_sql = "SELECT * FROM users WHERE email = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("s", $admin_email);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows > 0) {
    $admin = $verify_result->fetch_assoc();
    
    // Test password verification
    $verification_result = password_verify($admin_password, $admin['password']);
    
    echo "<p>Admin user found with ID: {$admin['id']}</p>";
    echo "<p>Password verification test: " . ($verification_result ? "<span class='success'>✅ SUCCESS</span>" : "<span class='error'>❌ FAILED</span>") . "</p>";
    
    if (!$verification_result) {
        echo "<p class='error'>Something is still wrong with the password verification.</p>";
        echo "<p>Debug info:</p>";
        echo "<ul>";
        echo "<li>Admin password (plain): {$admin_password}</li>";
        echo "<li>Stored hash: {$admin['password']}</li>";
        echo "<li>Freshly generated hash: {$hashed_password}</li>";
        echo "<li>PHP version: " . phpversion() . "</li>";
        echo "</ul>";
    }
} else {
    echo "<p class='error'>Failed to find admin user after creation!</p>";
}

echo "</div>";

// Show login credentials
echo "<div class='info'>
        <h3>Admin Login Credentials:</h3>
        <p><strong>Email:</strong> {$admin_email}</p>
        <p><strong>Password:</strong> {$admin_password}</p>
        <p>Use these credentials to log in to the admin dashboard.</p>
      </div>";

echo "<div class='card'>
        <h2>Next Steps</h2>
        <p>1. Try logging in with the credentials above</p>
        <p>2. If login still fails, try running <a href='setup_database.php'>setup_database.php</a> to fully reset the database</p>
        <p>3. Make sure your PHP version is compatible (current version: " . phpversion() . ")</p>
      </div>";

echo "<a href='auth/login.php' class='btn'>Go to Login Page</a>
    </div>
</body>
</html>";

$conn->close();
?>
