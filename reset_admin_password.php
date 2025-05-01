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

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reset Admin Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
               text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Admin Password Reset Tool</h1>";

// Check if admin exists
$admin_check = "SELECT * FROM users WHERE email = 'admin@gmail.com' AND role = 'admin'";
$result = $conn->query($admin_check);

if ($result->num_rows == 0) {
    // Admin does not exist, create it
    echo "<div class='card error'>
            <h2>Admin User Not Found</h2>
            <p>Creating new admin user...</p>
          </div>";
    
    $admin_name = "Admin User";
    $admin_email = "admin@gmail.com";
    // Important: Use PASSWORD_DEFAULT for compatibility
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_role = "admin";
    
    $create_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $create_stmt = $conn->prepare($create_sql);
    $create_stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $admin_role);
    
    if ($create_stmt->execute()) {
        echo "<div class='card success'>
                <h2>Admin User Created</h2>
                <p>Successfully created new admin user.</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Creation Failed</h2>
                <p>Failed to create admin user: {$conn->error}</p>
              </div>";
    }
} else {
    // Admin exists, update password
    echo "<div class='card success'>
            <h2>Admin User Found</h2>
            <p>Updating admin password...</p>
          </div>";
    
    // Important: Use PASSWORD_DEFAULT for compatibility
    $new_password = password_hash("admin123", PASSWORD_DEFAULT);
    
    $update_sql = "UPDATE users SET password = ? WHERE email = 'admin@gmail.com' AND role = 'admin'";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $new_password);
    
    if ($update_stmt->execute()) {
        echo "<div class='card success'>
                <h2>Password Reset Successful</h2>
                <p>The admin password has been reset successfully.</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Reset Failed</h2>
                <p>Failed to reset admin password: {$conn->error}</p>
              </div>";
    }
}

// Verify admin
$verify_sql = "SELECT * FROM users WHERE email = 'admin@gmail.com' AND role = 'admin'";
$verify_result = $conn->query($verify_sql);

if ($verify_result->num_rows > 0) {
    $admin = $verify_result->fetch_assoc();
    $sample_password = "admin123";
    
    // Test password verification with different hashing algorithms
    $verify_default = password_verify($sample_password, $admin['password']);
    
    echo "<div class='card'>
            <h2>Verification Results</h2>
            <p>Admin ID: {$admin['id']}</p>
            <p>Admin Name: {$admin['name']}</p>
            <p>Password verification test: " . ($verify_default ? "✅ Success" : "❌ Failed") . "</p>
            <p>Password hash: " . substr($admin['password'], 0, 30) . "...</p>
          </div>";
    
    if ($verify_default) {
        echo "<div class='card success'>
                <h2>Password Reset Complete</h2>
                <p>The admin password has been successfully reset and verified.</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Password Verification Failed</h2>
                <p>The password reset completed but verification failed. This might indicate a compatibility issue with the password hashing algorithm.</p>
              </div>";
    }
} else {
    echo "<div class='card error'>
            <h2>Verification Failed</h2>
            <p>Admin user not found after reset attempt.</p>
          </div>";
}

$conn->close();

echo "<div class='info'>
        <h3>Admin Login Credentials:</h3>
        <p><strong>Email:</strong> admin@gmail.com</p>
        <p><strong>Password:</strong> admin123</p>
      </div>
      
      <a href='auth/login.php' class='btn'>Try Login Now</a>
    </div>
</body>
</html>";
?>
