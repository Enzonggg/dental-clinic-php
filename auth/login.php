<?php
include_once('../includes/header.php');

$error = '';
$debug_info = ''; // For debugging purposes

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // For admin login specifically, let's add detailed debugging
    if ($email === 'admin@gmail.com') {
        // Check if admin exists
        $admin_check_sql = "SELECT * FROM users WHERE email = ?";
        $admin_check_stmt = $conn->prepare($admin_check_sql);
        $admin_check_stmt->bind_param("s", $email);
        $admin_check_stmt->execute();
        $admin_check_result = $admin_check_stmt->get_result();
        
        if ($admin_check_result->num_rows > 0) {
            $debug_info .= "✅ Admin user found in database.<br>";
            $admin_user = $admin_check_result->fetch_assoc();
            
            // Test password verification
            if (password_verify($password, $admin_user['password'])) {
                $debug_info .= "✅ Password verification successful.<br>";
            } else {
                $debug_info .= "❌ Password verification failed. Please try running create_admin.php to reset the admin account.<br>";
            }
            
            $debug_info .= "Role: " . $admin_user['role'] . "<br>";
            $debug_info .= "Name: " . $admin_user['name'] . "<br>";
        } else {
            $debug_info .= "❌ Admin user NOT found in database. Please run create_admin.php to create the admin account.<br>";
        }
    }
    
    // Regular login process
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            redirect_by_role($user['role']);
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Login</h4>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($debug_info): ?>
                    <div class="alert alert-info">
                        <h5>Login Troubleshooting</h5>
                        <?php echo $debug_info; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <p class="mt-3 text-center">Don't have an account? <a href="register.php">Register here</a></p>
                
                <div class="alert alert-info mt-3">
                    <p><strong>Admin Login:</strong> admin@gmail.com / admin123</p>
                    <p><strong>Having trouble?</strong> <a href="../create_admin.php">Reset Admin Account</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
