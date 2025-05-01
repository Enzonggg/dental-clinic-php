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

// Check column structure
$check_query = "SHOW COLUMNS FROM users LIKE 'name'";
$name_exists = $conn->query($check_query)->num_rows > 0;

$check_query = "SHOW COLUMNS FROM users LIKE 'full_name'";
$full_name_exists = $conn->query($check_query)->num_rows > 0;

if ($name_exists) {
    echo "<p>✅ The 'name' column already exists in the users table.</p>";
} else {
    // If full_name exists, rename it to name
    if ($full_name_exists) {
        $alter_sql = "ALTER TABLE users CHANGE full_name name VARCHAR(100) NOT NULL";
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p>✅ Successfully renamed column 'full_name' to 'name'</p>";
        } else {
            echo "<p>❌ Error renaming column: " . $conn->error . "</p>";
        }
    } else {
        // If neither exists, add name column
        $alter_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id";
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p>✅ Successfully added 'name' column to users table</p>";
        } else {
            echo "<p>❌ Error adding column: " . $conn->error . "</p>";
        }
    }
}

// Verify the fix worked
$check_query = "SHOW COLUMNS FROM users LIKE 'name'";
if ($conn->query($check_query)->num_rows > 0) {
    echo "<p>✅ Database structure verification passed. The 'name' column now exists.</p>";
    echo "<p>You can now use the registration and login features without errors.</p>";
} else {
    echo "<p>❌ Database repair failed. Please contact the system administrator.</p>";
}

$conn->close();

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Return to Homepage</a>";
echo "</div>";
echo "</div>";
?>
