<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem";

// Create connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Admin Account Setup</h1>";
echo "<div style='margin: 20px; font-family: Arial, sans-serif;'>";

// Check if admin exists
$admin_check = "SELECT * FROM users WHERE email = 'admin@gmail.com' AND role = 'admin'";
$result = $conn->query($admin_check);

// Define admin details
$admin_name = "Admin User";
$admin_email = "admin@gmail.com";
$admin_password = "admin123";
$admin_role = "admin"; // Explicitly set as admin

// Create or update admin
if ($result->num_rows == 0) {
    // Admin does not exist, create it
    echo "<p>Creating new admin user...</p>";
    
    // Important: Use PASSWORD_DEFAULT consistently
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $create_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $create_stmt = $conn->prepare($create_sql);
    $create_stmt->bind_param("ssss", $admin_name, $admin_email, $hashed_password, $admin_role);
    
    if ($create_stmt->execute()) {
        echo "<p>✅ Admin user created successfully.</p>";
    } else {
        echo "<p>❌ Failed to create admin user: {$conn->error}</p>";
    }
} else {
    // Admin exists, update password
    echo "<p>Admin user found. Updating password...</p>";
    
    // Important: Use PASSWORD_DEFAULT consistently
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $update_sql = "UPDATE users SET password = ?, name = ?, role = ? WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssss", $hashed_password, $admin_name, $admin_role, $admin_email);
    
    if ($update_stmt->execute()) {
        echo "<p>✅ Admin password updated successfully.</p>";
    } else {
        echo "<p>❌ Failed to update admin password: {$conn->error}</p>";
    }
}

// Verify the password hash works
$verify_sql = "SELECT * FROM users WHERE email = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("s", $admin_email);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$admin = $verify_result->fetch_assoc();

// Test password verification
$verify_password = password_verify($admin_password, $admin['password']);
echo "<p>Password verification test: " . ($verify_password ? "✅ PASSED" : "❌ FAILED") . "</p>";
echo "<p>Current hash: " . substr($admin['password'], 0, 20) . "...</p>";

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Admin Login Credentials:</h3>";
echo "<p><strong>Email:</strong> {$admin_email}</p>";
echo "<p><strong>Password:</strong> {$admin_password}</p>";
echo "</div>";

echo "<p><a href='auth/login.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
echo "</div>";

$conn->close();
?>
