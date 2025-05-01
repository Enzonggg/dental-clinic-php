<?php include_once('includes/header.php'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Welcome to Dental Clinic System</h1>
            <p class="lead">A comprehensive solution for managing dental appointments, treatments, and patient records.</p>
            <hr class="my-4">
            <p>This system helps dentists, patients, and administrators manage dental clinic operations efficiently.</p>
            
            <?php if(!is_logged_in()): ?>
                <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                    <a class="btn btn-primary btn-lg" href="auth/login.php" role="button">Login</a>
                    <a class="btn btn-success btn-lg" href="auth/register.php" role="button">Register</a>
                </div>
            <?php else: ?>
                <p>You are logged in as: <strong><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'User'; ?></strong> (<?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Unknown'; ?>)</p>
                <?php if(isset($_SESSION['role'])): ?>
                    <a class="btn btn-primary btn-lg" href="<?php echo $_SESSION['role']; ?>/dashboard.php" role="button">Go to Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-primary btn-lg" href="index.php" role="button">Home</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-header bg-primary text-white">
                <h5>For Patients</h5>
            </div>
            <div class="card-body">
                <i class="fas fa-user-injured fa-4x mb-3 text-primary"></i>
                <h5 class="card-title">Patient Features</h5>
                <p class="card-text">
                    Book appointments, view treatment history, make secure payments via GCash and more.
                </p>
                <a href="auth/register.php" class="btn btn-outline-primary">Register as Patient</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-header bg-success text-white">
                <h5>For Doctors</h5>
            </div>
            <div class="card-body">
                <i class="fas fa-user-md fa-4x mb-3 text-success"></i>
                <h5 class="card-title">Doctor Features</h5>
                <p class="card-text">
                    Manage appointments, maintain patient records, create treatment plans and more.
                </p>
                <a href="auth/login.php" class="btn btn-outline-success">Doctor Login</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-header bg-info text-white">
                <h5>For Admins</h5>
            </div>
            <div class="card-body">
                <i class="fas fa-user-cog fa-4x mb-3 text-info"></i>
                <h5 class="card-title">Admin Features</h5>
                <p class="card-text">
                    Oversee clinic operations, manage users, verify payments and generate reports.
                </p>
                <a href="auth/login.php" class="btn btn-outline-info">Admin Login</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Our Services</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="fas fa-tooth fa-3x mb-2 text-primary"></i>
                            <h6>General Dentistry</h6>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="fas fa-smile fa-3x mb-2 text-primary"></i>
                            <h6>Cosmetic Dentistry</h6>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="fas fa-teeth fa-3x mb-2 text-primary"></i>
                            <h6>Orthodontics</h6>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="text-center">
                            <i class="fas fa-x-ray fa-3x mb-2 text-primary"></i>
                            <h6>Dental Implants</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5 mb-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Clinic Hours</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tbody>
                        <tr>
                            <td>Monday - Friday</td>
                            <td>8:00 AM - 5:00 PM</td>
                        </tr>
                        <tr>
                            <td>Saturday</td>
                            <td>9:00 AM - 2:00 PM</td>
                        </tr>
                        <tr>
                            <td>Sunday</td>
                            <td>Closed</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Contact Information</h5>
            </div>
            <div class="card-body">
                <p><i class="fas fa-map-marker-alt"></i> 123 Dental Avenue, City, State</p>
                <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                <p><i class="fas fa-envelope"></i> info@dentalclinic.com</p>
                <p><i class="fas fa-globe"></i> www.dentalclinic.com</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3 mb-5">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5>System Administration</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <p>If you're experiencing database issues, please verify your setup:</p>
                    <a href="setup_verification.php" class="btn btn-info">Verify Database Setup</a>
                    <a href="setup_database.php" class="btn btn-primary">Run Database Setup</a>
                    <a href="fix_db.php" class="btn btn-warning">Repair Database</a>
                    <a href="create_admin.php" class="btn btn-danger">Create/Reset Admin</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('includes/footer.php'); ?>
