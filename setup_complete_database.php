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

echo "<h1>Dental Clinic System - Complete Database Setup</h1>";
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

// 1. Create users table with comprehensive fields
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    specialization VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    profile_picture VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'users' created successfully</p>";
} else {
    echo "<p>❌ Error creating table users: " . $conn->error . "</p>";
}

// 2. Create appointments table with better status options
$sql = "CREATE TABLE IF NOT EXISTS appointments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    appointment_date DATETIME NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'appointments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table appointments: " . $conn->error . "</p>";
}

// 3. Create medical_history table with more detailed medical information
$sql = "CREATE TABLE IF NOT EXISTS medical_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    allergies TEXT NULL,
    medications TEXT NULL,
    medical_conditions TEXT NULL,
    previous_surgeries TEXT NULL,
    blood_pressure VARCHAR(20) NULL,
    blood_type VARCHAR(10) NULL,
    smoking_status ENUM('non_smoker', 'former_smoker', 'current_smoker') NULL,
    pregnancy_status BOOLEAN NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'medical_history' created successfully</p>";
} else {
    echo "<p>❌ Error creating table medical_history: " . $conn->error . "</p>";
}

// 4. Create treatments table with more comprehensive treatment tracking
$sql = "CREATE TABLE IF NOT EXISTS treatments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    diagnosis VARCHAR(255) NOT NULL,
    treatment_plan TEXT NOT NULL,
    treatment_type ENUM('diagnostic', 'preventive', 'restorative', 'endodontic', 'periodontal', 'prosthodontic', 'oral_surgery', 'orthodontic', 'cosmetic') NULL,
    teeth_involved VARCHAR(100) NULL,
    notes TEXT NULL,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_treatment_type (treatment_type)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'treatments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table treatments: " . $conn->error . "</p>";
}

// 5. Create payments table with more detailed payment information
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    treatment_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'gcash', 'bank_transfer', 'check', 'insurance') NOT NULL,
    insurance_provider VARCHAR(100) NULL,
    insurance_policy_number VARCHAR(50) NULL,
    reference_number VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
    payment_date DATETIME NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE CASCADE,
    INDEX idx_payment_method (payment_method),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'payments' created successfully</p>";
} else {
    echo "<p>❌ Error creating table payments: " . $conn->error . "</p>";
}

// 6. Create prescriptions table for medication tracking
$sql = "CREATE TABLE IF NOT EXISTS prescriptions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    patient_id INT(11) NOT NULL,
    doctor_id INT(11) NOT NULL,
    treatment_id INT(11) NULL,
    medication VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) NOT NULL,
    frequency VARCHAR(100) NOT NULL,
    duration VARCHAR(100) NOT NULL,
    instructions TEXT NOT NULL,
    notes TEXT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_id) REFERENCES treatments(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_doctor_id (doctor_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'prescriptions' created successfully</p>";
} else {
    echo "<p>❌ Error creating table prescriptions: " . $conn->error . "</p>";
}

// 7. Create treatment_templates table for reusable treatment plans
$sql = "CREATE TABLE IF NOT EXISTS treatment_templates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    procedure_steps TEXT NOT NULL,
    estimated_cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_category (category)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'treatment_templates' created successfully</p>";
} else {
    echo "<p>❌ Error creating table treatment_templates: " . $conn->error . "</p>";
}

// 8. Create doctor_schedule table for availability tracking
$sql = "CREATE TABLE IF NOT EXISTS doctor_schedule (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_day_of_week (day_of_week)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'doctor_schedule' created successfully</p>";
} else {
    echo "<p>❌ Error creating table doctor_schedule: " . $conn->error . "</p>";
}

// 9. Create time_off table for doctor unavailability
$sql = "CREATE TABLE IF NOT EXISTS time_off (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT(11) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_date_range (start_date, end_date)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'time_off' created successfully</p>";
} else {
    echo "<p>❌ Error creating table time_off: " . $conn->error . "</p>";
}

// 10. Create analytics_data table for storing generated analytics
$sql = "CREATE TABLE IF NOT EXISTS analytics_data (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    report_period VARCHAR(20) NOT NULL,
    report_date DATE NOT NULL,
    data_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'analytics_data' created successfully</p>";
} else {
    echo "<p>❌ Error creating table analytics_data: " . $conn->error . "</p>";
}

// 11. Create system_logs table for tracking system activities
$sql = "CREATE TABLE IF NOT EXISTS system_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p>✅ Table 'system_logs' created successfully</p>";
} else {
    echo "<p>❌ Error creating table system_logs: " . $conn->error . "</p>";
}

// Create admin user with consistent credentials
$admin_name = "Admin User";
$admin_email = "admin@gmail.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
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
        echo "Password: admin123";
        echo "</div>";
    } else {
        echo "<p>❌ Error creating admin user: " . $conn->error . "</p>";
    }
} else {
    // Update admin credentials to ensure consistency
    $sql = "UPDATE users SET password = '$admin_password', name = '$admin_name', role = '$admin_role' WHERE email = '$admin_email'";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Admin user credentials updated</p>";
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Admin Login Details:</strong><br>";
        echo "Email: admin@gmail.com<br>";
        echo "Password: admin123";
        echo "</div>";
    } else {
        echo "<p>❌ Error updating admin user: " . $conn->error . "</p>";
    }
}

// Create test doctor
$doctor_name = "Dr. John Smith";
$doctor_email = "doctor@example.com";
$doctor_password = password_hash("doctor123", PASSWORD_DEFAULT);
$doctor_role = "doctor";
$doctor_specialization = "General Dentistry";

$sql = "SELECT * FROM users WHERE email = '$doctor_email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO users (name, email, password, role, specialization) VALUES ('$doctor_name', '$doctor_email', '$doctor_password', '$doctor_role', '$doctor_specialization')";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Test doctor account created successfully</p>";
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Doctor Login Details:</strong><br>";
        echo "Email: doctor@example.com<br>";
        echo "Password: doctor123";
        echo "</div>";
    } else {
        echo "<p>❌ Error creating test doctor: " . $conn->error . "</p>";
    }
}

// Create test patient
$patient_name = "Jane Doe";
$patient_email = "patient@example.com";
$patient_password = password_hash("patient123", PASSWORD_DEFAULT);
$patient_role = "patient";

$sql = "SELECT * FROM users WHERE email = '$patient_email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$patient_name', '$patient_email', '$patient_password', '$patient_role')";
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Test patient account created successfully</p>";
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Patient Login Details:</strong><br>";
        echo "Email: patient@example.com<br>";
        echo "Password: patient123";
        echo "</div>";
    } else {
        echo "<p>❌ Error creating test patient: " . $conn->error . "</p>";
    }
}

// Add sample doctor schedule
$doctor_sql = "SELECT id FROM users WHERE role = 'doctor' LIMIT 1";
$doctor_result = $conn->query($doctor_sql);
if ($doctor_result->num_rows > 0) {
    $doctor_id = $doctor_result->fetch_assoc()['id'];
    
    // Check if schedule already exists
    $schedule_check = $conn->query("SELECT * FROM doctor_schedule WHERE doctor_id = $doctor_id");
    if ($schedule_check->num_rows == 0) {
        // Add weekday schedule
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        foreach ($weekdays as $day) {
            $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) 
                    VALUES ($doctor_id, '$day', '09:00:00', '17:00:00')";
            $conn->query($sql);
        }
        
        // Add Saturday schedule (shorter hours)
        $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) 
                VALUES ($doctor_id, 'saturday', '09:00:00', '13:00:00')";
        $conn->query($sql);
        
        echo "<p>✅ Sample doctor schedule created</p>";
    }
}

$conn->close();

echo "<h2>Enhanced Database Setup Complete!</h2>";
echo "<p>Your database has been set up with a comprehensive structure that includes:</p>";
echo "<ul>";
echo "<li>User management with role-based access control</li>";
echo "<li>Appointment scheduling and management</li>";
echo "<li>Medical history tracking</li>";
echo "<li>Treatment planning and history</li>";
echo "<li>Payment processing with multiple methods</li>";
echo "<li>Prescription management</li>";
echo "<li>Doctor scheduling and availability tracking</li>";
echo "<li>Analytics data storage</li>";
echo "<li>System activity logging</li>";
echo "</ul>";

echo "<div style='background-color: #f0f8ff; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; margin: 20px 0;'>";
echo "<strong>Next Steps:</strong><br>";
echo "<ol>";
echo "<li>If you're using the Python analytics features, make sure to install the required dependencies:</li>";
echo "<pre>pip install mysql-connector-python pandas matplotlib</pre>";
echo "<li>Make sure all PHP files are properly placed in <code>c:\\xampp\\htdocs\\clinicdentalsystem\\</code></li>";
echo "<li>Access the system at: <a href='index.php'>http://localhost/clinicdentalsystem/</a></li>";
echo "<li>Use the login credentials provided above</li>";
echo "</ol>";
echo "</div>";

echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
echo "</div>";
?>
