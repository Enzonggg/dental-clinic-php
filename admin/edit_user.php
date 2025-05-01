<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success_message = '';
$error_message = '';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$user_id) {
    header("Location: user_management.php");
    exit;
}

// Get user data
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows != 1) {
    header("Location: user_management.php");
    exit;
}

$user = $user_result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $specialization = isset($_POST['specialization']) ? sanitize_input($_POST['specialization']) : '';
    
    // Check if email exists but skip current user
    $check_sql = "SELECT * FROM users WHERE email = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Email already exists for another user.";
    } else {
        // Update user
        $update_sql = "UPDATE users SET name = ?, email = ?, role = ?, specialization = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssi", $name, $email, $role, $specialization, $user_id);
        
        if ($update_stmt->execute()) {
            // Handle password update if provided
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $pwd_sql = "UPDATE users SET password = ? WHERE id = ?";
                $pwd_stmt = $conn->prepare($pwd_sql);
                $pwd_stmt->bind_param("si", $hashed_password, $user_id);
                $pwd_stmt->execute();
            }
            
            $success_message = "User updated successfully.";
            
            // Refresh user data
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
        } else {
            $error_message = "User update failed: " . $conn->error;
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Edit User</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Edit User: <?php echo htmlspecialchars($user['name']); ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                        <small class="text-muted">Only fill this if you want to change the password</small>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required onchange="toggleSpecialization()">
                            <option value="patient" <?php echo $user['role'] == 'patient' ? 'selected' : ''; ?>>Patient</option>
                            <option value="doctor" <?php echo $user['role'] == 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="specializationField" style="display: <?php echo $user['role'] == 'doctor' ? 'block' : 'none'; ?>;">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlspecialchars($user['specialization'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    <a href="user_management.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>User Information</h5>
            </div>
            <div class="card-body">
                <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Created:</strong> <?php echo isset($user['created_at']) ? format_date($user['created_at']) : 'N/A'; ?></p>
                <p><strong>Last Updated:</strong> <?php echo isset($user['updated_at']) ? format_date($user['updated_at']) : 'N/A'; ?></p>
                
                <?php if($user['role'] == 'patient'): ?>
                    <div class="mt-4">
                        <h6>Related Data</h6>
                        <?php
                        // Get appointment count
                        $app_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?";
                        $app_stmt = $conn->prepare($app_sql);
                        $app_stmt->bind_param("i", $user_id);
                        $app_stmt->execute();
                        $app_result = $app_stmt->get_result();
                        $app_count = $app_result->fetch_assoc()['count'];
                        ?>
                        <p><strong>Appointments:</strong> <?php echo $app_count; ?></p>
                    </div>
                <?php elseif($user['role'] == 'doctor'): ?>
                    <div class="mt-4">
                        <h6>Related Data</h6>
                        <?php
                        // Get appointment count
                        $app_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ?";
                        $app_stmt = $conn->prepare($app_sql);
                        $app_stmt->bind_param("i", $user_id);
                        $app_stmt->execute();
                        $app_result = $app_stmt->get_result();
                        $app_count = $app_result->fetch_assoc()['count'];
                        
                        // Get patient count
                        $patient_sql = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?";
                        $patient_stmt = $conn->prepare($patient_sql);
                        $patient_stmt->bind_param("i", $user_id);
                        $patient_stmt->execute();
                        $patient_result = $patient_stmt->get_result();
                        $patient_count = $patient_result->fetch_assoc()['count'];
                        ?>
                        <p><strong>Appointments:</strong> <?php echo $app_count; ?></p>
                        <p><strong>Patients:</strong> <?php echo $patient_count; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSpecialization() {
    var role = document.getElementById('role').value;
    var specializationField = document.getElementById('specializationField');
    
    if (role === 'doctor') {
        specializationField.style.display = 'block';
    } else {
        specializationField.style.display = 'none';
    }
}
</script>

<?php include_once('../includes/footer.php'); ?>
