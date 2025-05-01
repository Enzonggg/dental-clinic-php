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

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    echo "<p>❌ Error: The users table doesn't exist. Please run setup_database.php first.</p>";
    exit;
}

// Check if the 'full_name' column exists
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
$full_name_exists = $column_check->num_rows > 0;

// Check if the 'name' column exists
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
$name_exists = $column_check->num_rows > 0;

if ($name_exists) {
    echo "<p>✅ The 'name' column already exists in users table.</p>";
} else {
    // The 'name' column doesn't exist, need to add it
    if ($full_name_exists) {
        // If full_name exists, rename it to name
        $alter_sql = "ALTER TABLE users CHANGE full_name name VARCHAR(100) NOT NULL";
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p>✅ Successfully renamed column 'full_name' to 'name'</p>";
        } else {
            echo "<p>❌ Error renaming column: " . $conn->error . "</p>";
        }
    } else {
        // If neither name nor full_name exists, add name column
        $alter_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id";
        if ($conn->query($alter_sql) === TRUE) {
            echo "<p>✅ Successfully added 'name' column to users table</p>";
        } else {
            echo "<p>❌ Error adding column: " . $conn->error . "</p>";
        }
    }
}

// Verify fix
$column_check = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
if ($column_check->num_rows > 0) {
    echo "<p>✅ Database structure verification passed.</p>";
    echo "<p>Your database structure is now compatible with the application code.</p>";
} else {
    echo "<p>❌ Database repair failed. Please contact support.</p>";
}

$conn->close();

echo "<div style='margin-top: 20px;'>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Return to Homepage</a>";
echo "</div>";
echo "</div>";
?>
