<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Dental Clinic System - Database Setup</h1>";
echo "<div style='margin: 20px; font-family: Arial, sans-serif;'>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS clinicdentalsystem";
if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Database created successfully or already exists</p>";
} else {
    echo "<p>❌ Error creating database: " . $conn->error . "</p>";
}

// Select the clinicdentalsystem database
$conn->select_db("clinicdentalsystem");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
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

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'users' created successfully</p>";
} else {
    echo "<p>❌ Error creating table users: " . $conn->error . "</p>";
}

// Create appointments table
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    appointment_date DATETIME NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'appointments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table appointments: " . $conn->error . "</p>";
}

// Create medical_history table
$sql = "CREATE TABLE IF NOT EXISTS medical_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    allergies TEXT NULL,
    medications TEXT NULL,
    medical_conditions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'medical_history' created successfully</p>";
} else {
    echo "<p>❌ Error creating table medical_history: " . $conn->error . "</p>";
}

// Create treatments table
$sql = "CREATE TABLE IF NOT EXISTS treatments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    treatment_plan TEXT NOT NULL,
    notes TEXT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (doctor_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'treatments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table treatments: " . $conn->error . "</p>";
}

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    treatment_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'gcash') NOT NULL,
    reference_number VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    payment_date DATETIME NOT NULL,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'payments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table payments: " . $conn->error . "</p>";
}

// Create admin user if it doesn't exist
$admin_name = "Admin User";
$admin_email = "admin@gmail.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT); // Use PASSWORD_DEFAULT consistently
$admin_role = "admin";

$sql = "SELECT * FROM users WHERE email = '$admin_email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$admin_name', '$admin_email', '$admin_password', '$admin_role')";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Admin user created successfully</p>";
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Admin Login Details:</strong><br>";
        echo "Email: admin@gmail.com<br>";
        echo "Password: admin123<br>"; 
        echo "</div>";
    } else {
        echo "<p>❌ Error creating admin user: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ Admin user already exists</p>";
    // Update admin password to ensure it's correct
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = '$admin_password' WHERE email = '$admin_email'";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Admin password updated to ensure consistency</p>";
    }
}

// Verify role column has correct ENUM values
$verify_sql = "SHOW COLUMNS FROM users LIKE 'role'";
$role_info = $conn->query($verify_sql)->fetch_assoc();
$role_type = $role_info['Type'];

if (stripos($role_type, "enum('patient','doctor','admin')") === false) {
    // Fix the role column if it doesn't have the correct ENUM values
    $fix_sql = "ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient'";
    if ($conn->query($fix_sql)) {
        echo "<p>✅ Fixed role column ENUM values</p>";
    } else {
        echo "<p>❌ Error fixing role column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ Role column has correct ENUM values</p>";
}

$conn->close();

echo "<h2>Setup Complete!</h2>";
echo "<p>Follow these steps to access the system:</p>";
echo "<ol>";
echo "<li>Make sure all PHP files are properly placed in <code>c:\\xampp\\htdocs\\clinicdentalsystem\\</code></li>";
echo "<li>Access the system at: <a href='index.php'>http://localhost/clinicdentalsystem/</a></li>";
echo "<li>Use the login credentials provided above</li>";
echo "</ol>";

echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 20px 0;'>";
echo "<strong>Important Note:</strong><br>";
echo "If you're trying to access <code>http://localhost/auth/register.php</code> and getting a 404 error, you need to use the full path:<br>";
echo "<code>http://localhost/clinicdentalsystem/auth/register.php</code>";
echo "</div>";

echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
echo "</div>";
?>
