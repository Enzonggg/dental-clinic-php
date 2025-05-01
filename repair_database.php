<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "clinicdentalsystem"; // Changed from dental_clinic to clinicdentalsystem

// Create connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Database Repair Tool</h1>";
echo "<div style='font-family: Arial, sans-serif; margin: 20px;'>";

// Get columns from users table
$result = $conn->query("DESCRIBE users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Check if 'name' column exists
if (in_array('name', $columns)) {
    echo "<p>✅ The 'name' column already exists in the users table.</p>";
} 
// Check if 'full_name' column exists
else if (in_array('full_name', $columns)) {
    // Rename 'full_name' to 'name'
    $sql = "ALTER TABLE users CHANGE COLUMN full_name name VARCHAR(100) NOT NULL";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Successfully renamed column 'full_name' to 'name'.</p>";
    } else {
        echo "<p>❌ Error renaming column: " . $conn->error . "</p>";
    }
} 
// Neither column exists, add 'name'
else {
    $sql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Successfully added 'name' column to users table.</p>";
    } else {
        echo "<p>❌ Error adding column: " . $conn->error . "</p>";
    }
}

// Verify admin user exists
$admin_check = $conn->query("SELECT * FROM users WHERE role = 'admin'");
if ($admin_check->num_rows == 0) {
    // Create admin user
    $admin_name = "Admin User";
    $admin_email = "admin@gmail.com";
    $admin_password = password_hash("admin", PASSWORD_DEFAULT);
    $admin_sql = "INSERT INTO users (name, email, password, role) 
                 VALUES ('$admin_name', '$admin_email', '$admin_password', 'admin')";
    
    if ($conn->query($admin_sql)) {
        echo "<p>✅ Created admin user: admin@gmail.com (password: admin)</p>";
    }
}

// Verify fix
$result = $conn->query("DESCRIBE users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (in_array('name', $columns)) {
    echo "<p>✅ Database repair verification successful!</p>";
    echo "<p>Your database structure has been fixed and should now work with the application code.</p>";
} else {
    echo "<p>❌ Database repair failed. Please contact support.</p>";
}

$conn->close();

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";
echo "</div>";
?>
