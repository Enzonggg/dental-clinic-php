<?php
include_once('../includes/header.php');

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize_input($_POST['role']);
    
    // Explicitly sanitize and validate role (only allow patient and doctor)
    $role = isset($_POST['role']) ? sanitize_input($_POST['role']) : 'patient';
    if (!in_array($role, ['patient', 'doctor'])) {
        $role = 'patient'; // Default fallback for security
    }
    
    // Add specialization field for doctors
    $specialization = '';
    if ($role == 'doctor' && isset($_POST['specialization'])) {
        $specialization = sanitize_input($_POST['specialization']);
    }
    
    // Check if email already exists
    $check_sql = "SELECT * FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = "Email already exists";
    } elseif ($password != $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare the SQL based on role
        if ($role == 'doctor') {
            $insert_sql = "INSERT INTO users (name, email, password, role, specialization) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $specialization);
        } else {
            $insert_sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        }
        
        if ($insert_stmt->execute()) {
            $success = "Registration successful! You can now login.";
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Register</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <!-- Make the role selection more prominent -->
                    <div class="mb-3">
                        <label for="role" class="form-label fw-bold">Register as:</label>
                        <select class="form-select form-select-lg" id="role" name="role" required onchange="toggleSpecialization()">
                            <option value="" disabled selected>Select your role</option>
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                        </select>
                        <div class="form-text">Choose whether you're registering as a patient or doctor</div>
                    </div>
                    <div class="mb-3" id="specializationField" style="display: none;">
                        <label for="specialization" class="form-label">Specialization (for doctors)</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" placeholder="e.g., General Dentistry, Orthodontics">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>
                </form>
                <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<script>
// Ensure this runs on page load and when selection changes
function toggleSpecialization() {
    var role = document.getElementById('role').value;
    var specializationField = document.getElementById('specializationField');
    
    console.log("Role selected:", role); // Debug output
    
    if (role === 'doctor') {
        specializationField.style.display = 'block';
    } else {
        specializationField.style.display = 'none';
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSpecialization();
});
</script>

<?php include_once('../includes/footer.php'); ?>
