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

echo "<h1>Database Column Fix</h1>";

// Check if either column exists
$check_name = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
$check_fullname = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");

$has_name = $check_name->num_rows > 0;
$has_fullname = $check_fullname->num_rows > 0;

echo "<p>Checking database structure...</p>";
echo "<ul>";
echo "<li>Column 'name' exists: " . ($has_name ? "Yes" : "No") . "</li>";
echo "<li>Column 'full_name' exists: " . ($has_fullname ? "Yes" : "No") . "</li>";
echo "</ul>";

if ($has_name && !$has_fullname) {
    echo "<p>✅ Database structure is correct. The 'name' column exists.</p>";
} 
elseif (!$has_name && $has_fullname) {
    // Rename column from full_name to name
    echo "<p>Attempting to rename 'full_name' column to 'name'...</p>";
    
    if ($conn->query("ALTER TABLE users CHANGE full_name name VARCHAR(100) NOT NULL")) {
        echo "<p>✅ Successfully renamed column from 'full_name' to 'name'</p>";
    } else {
        echo "<p>❌ Error renaming column: " . $conn->error . "</p>";
    }
} 
elseif (!$has_name && !$has_fullname) {
    // Add name column
    echo "<p>Neither column exists. Adding 'name' column...</p>";
    
    if ($conn->query("ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL AFTER id")) {
        echo "<p>✅ Successfully added 'name' column</p>";
    } else {
        echo "<p>❌ Error adding column: " . $conn->error . "</p>";
    }
} 
else {
    // Both columns exist (unlikely but possible)
    echo "<p>Both 'name' and 'full_name' columns exist. This is unusual.</p>";
}

// Verify fix
$final_check = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
if ($final_check->num_rows > 0) {
    echo "<p>✅ Fix verification: The 'name' column now exists!</p>";
    echo "<p><strong>You can now use the registration page.</strong></p>";
} else {
    echo "<p>❌ Fix verification failed. The column is still missing.</p>";
}

$conn->close();

echo "<p><a href='auth/register.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Try Registration Now</a></p>";
?>
