<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success_message = '';
$error_message = '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';

// Handle user creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    // Validate role is one of the allowed roles
    if (!in_array($role, ['patient', 'doctor', 'admin'])) {
        $role = 'patient'; // Default fallback for security
    }
    
    $specialization = isset($_POST['specialization']) ? sanitize_input($_POST['specialization']) : '';
    
    // Check if email already exists
    $check_sql = "SELECT * FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Email already exists.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $insert_sql = "INSERT INTO users (name, email, password, role, specialization) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $specialization);
        
        if ($insert_stmt->execute()) {
            $success_message = "User created successfully.";
        } else {
            $error_message = "User creation failed: " . $conn->error;
        }
    }
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Don't allow deleting the current admin
    if ($user_id == $_SESSION['user_id']) {
        $error_message = "You cannot delete your own account.";
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "User deleted successfully.";
        } else {
            $error_message = "User deletion failed: " . $conn->error;
        }
    }
}

// Get users with optional role filter
$users_sql = "SELECT * FROM users";
if ($role_filter) {
    $users_sql .= " WHERE role = ?";
}
$users_sql .= " ORDER BY name"; // Changed from full_name to name

if ($role_filter) {
    $users_stmt = $conn->prepare($users_sql);
    $users_stmt->bind_param("s", $role_filter);
    $users_stmt->execute();
    $users = $users_stmt->get_result();
} else {
    $users = $conn->query($users_sql);
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>User Management</h2>
        
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Users <?php echo $role_filter ? '(' . ucfirst($role_filter) . 's)' : ''; ?></h5>
                <div>
                    <a href="user_management.php" class="btn btn-sm btn-outline-primary <?php echo !$role_filter ? 'active' : ''; ?>">All</a>
                    <a href="user_management.php?role=patient" class="btn btn-sm btn-outline-primary <?php echo $role_filter == 'patient' ? 'active' : ''; ?>">Patients</a>
                    <a href="user_management.php?role=doctor" class="btn btn-sm btn-outline-primary <?php echo $role_filter == 'doctor' ? 'active' : ''; ?>">Doctors</a>
                    <a href="user_management.php?role=admin" class="btn btn-sm btn-outline-primary <?php echo $role_filter == 'admin' ? 'active' : ''; ?>">Admins</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Info</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users->num_rows > 0): ?>
                                <?php while($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                        <td>
                                            <?php if($user['role'] == 'doctor' && !empty($user['specialization'])): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($user['specialization']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="user_management.php?delete=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Add New User</h5>
            </div>
            <div class="card-body">
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
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required onchange="toggleSpecialization()">
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3" id="specializationField" style="display: none;">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization">
                    </div>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </form>
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
