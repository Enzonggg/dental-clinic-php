<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem";  // Changed from dental_clinic to clinicdentalsystem

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Database Repair</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; 
               text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn-danger { background: #f44336; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Complete Database Repair Tool</h1>";

// Try to connect to database
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    echo "<div class='card error'>
            <h2>Connection Failed</h2>
            <p>Could not connect to MySQL: {$conn->connect_error}</p>
          </div>";
    exit;
}

// Check if database exists
$db_exists = $conn->select_db($database);

if (!$db_exists) {
    echo "<div class='card warning'>
            <h2>Database Does Not Exist</h2>
            <p>The database '{$database}' does not exist. We need to create it.</p>
          </div>";
    
    // Create the database
    if ($conn->query("CREATE DATABASE {$database}")) {
        echo "<div class='card success'>
                <h2>Database Created</h2>
                <p>Successfully created database '{$database}'.</p>
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

// Now connected to the database
$db_conn = new mysqli($host, $username, $password, $database);

// Check if users table exists
$table_check = $db_conn->query("SHOW TABLES LIKE 'users'");
$table_exists = $table_check->num_rows > 0;

if (!$table_exists) {
    echo "<div class='card warning'>
            <h2>Users Table Missing</h2>
            <p>The 'users' table doesn't exist. Creating table with correct structure.</p>
          </div>";
    
    // Create users table with the proper 'name' column
    $create_sql = "CREATE TABLE users (
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
    
    if ($db_conn->query($create_sql)) {
        echo "<div class='card success'>
                <h2>Users Table Created</h2>
                <p>Successfully created 'users' table with correct structure.</p>
              </div>";
    } else {
        echo "<div class='card error'>
                <h2>Table Creation Failed</h2>
                <p>Error creating users table: {$db_conn->error}</p>
              </div>";
    }
} else {
    echo "<div class='card success'>
            <h2>Users Table Exists</h2>
            <p>The 'users' table already exists. Checking column structure...</p>
          </div>";
    
    // Check column structure
    $columns = $db_conn->query("DESCRIBE users");
    $column_names = [];
    while ($col = $columns->fetch_assoc()) {
        $column_names[] = $col['Field'];
    }
    
    $has_name = in_array('name', $column_names);
    $has_full_name = in_array('full_name', $column_names);
    
    echo "<div class='card'>";
    echo "<h2>Column Structure Analysis</h2>";
    echo "<p>Found: " . implode(', ', $column_names) . "</p>";
    echo "<p>'name' column exists: " . ($has_name ? "Yes" : "No") . "</p>";
    echo "<p>'full_name' column exists: " . ($has_full_name ? "Yes" : "No") . "</p>";
    echo "</div>";
    
    if (!$has_name && !$has_full_name) {
        // Neither column exists - add 'name'
        $add_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id";
        if ($db_conn->query($add_sql)) {
            echo "<div class='card success'>
                    <h2>Column Added</h2>
                    <p>Successfully added 'name' column to users table.</p>
                  </div>";
        } else {
            echo "<div class='card error'>
                    <h2>Column Addition Failed</h2>
                    <p>Error adding column: {$db_conn->error}</p>
                  </div>";
        }
    } else if (!$has_name && $has_full_name) {
        // Only full_name exists - rename to name
        $rename_sql = "ALTER TABLE users CHANGE COLUMN full_name name VARCHAR(100) NOT NULL";
        if ($db_conn->query($rename_sql)) {
            echo "<div class='card success'>
                    <h2>Column Renamed</h2>
                    <p>Successfully renamed 'full_name' column to 'name'.</p>
                  </div>";
        } else {
            echo "<div class='card error'>
                    <h2>Column Rename Failed</h2>
                    <p>Error renaming column: {$db_conn->error}</p>
                  </div>";
        }
    } else if ($has_name && $has_full_name) {
        // Both exist - this is unusual but we'll keep 'name'
        echo "<div class='card warning'>
                <h2>Duplicate Columns</h2>
                <p>Both 'name' and 'full_name' columns exist. The code will use 'name' column.</p>
              </div>";
    } else {
        // Only name exists - perfect!
        echo "<div class='card success'>
                <h2>Column Structure Correct</h2>
                <p>The 'name' column already exists and is correctly set up.</p>
              </div>";
    }
}

// Final verification
$verify = $db_conn->query("SHOW COLUMNS FROM users LIKE 'name'");
if ($verify->num_rows > 0) {
    echo "<div class='card success'>
            <h2>Final Verification Passed</h2>
            <p>The 'name' column exists in the users table. Your database is now correctly configured.</p>
          </div>";
} else {
    echo "<div class='card error'>
            <h2>Final Verification Failed</h2>
            <p>The 'name' column still doesn't exist. Please consider the nuclear option below.</p>
          </div>";
}

// Add admin user if needed
$admin_check = $db_conn->query("SELECT * FROM users WHERE email = 'admin@gmail.com'");
if ($admin_check->num_rows == 0) {
    $admin_name = "Admin User";
    $admin_email = "admin@gmail.com";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Use admin123 consistently
    $admin_role = "admin";
    
    $admin_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $admin_stmt = $db_conn->prepare($admin_sql);
    $admin_stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $admin_role);
    
    if ($admin_stmt->execute()) {
        echo "<div class='card success'>
                <h2>Admin User Created</h2>
                <p>Created default admin user (admin@gmail.com / admin123)</p>
              </div>";
    }
} else {
    // Update admin password to ensure it's correct
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_update = "UPDATE users SET password = ? WHERE email = 'admin@gmail.com'";
    $admin_update_stmt = $db_conn->prepare($admin_update);
    $admin_update_stmt->bind_param("s", $admin_password);
    if ($admin_update_stmt->execute()) {
        echo "<div class='card success'>
                <h2>Admin Password Updated</h2>
                <p>Updated admin password to ensure consistency (admin@gmail.com / admin123)</p>
              </div>";
    }
}

// Close connections
$db_conn->close();
$conn->close();

echo "<div class='card'>
        <h2>Next Steps</h2>
        <ol>
            <li>Try to use the registration page now</li>
            <li>If issues persist, you can use the nuclear option below to completely reset your database</li>
            <li>After fixing the database, access the application at <a href='index.php'>http://localhost/clinicdentalsystem/</a></li>
        </ol>
        <a href='auth/register.php' class='btn'>Try Registration Now</a>
        <a href='fix_db.php?nuke=1' class='btn btn-danger' onclick=\"return confirm('WARNING: This will delete ALL your data and recreate the database from scratch. Are you sure?');\">Nuclear Option: Reset Database</a>
      </div>
    </div>
</body>
</html>";

// Nuclear option - completely recreate the database if requested
if (isset($_GET['nuke']) && $_GET['nuke'] == '1') {
    $conn = new mysqli($host, $username, $password);
    
    // Drop the database
    $conn->query("DROP DATABASE IF EXISTS {$database}");
    
    // Redirect to setup_database.php to recreate everything
    header("Location: setup_database.php");
    exit;
}
?>
