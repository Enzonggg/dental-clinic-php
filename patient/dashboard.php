<?php
include_once('../includes/header.php');

// Strict role check - redirect if not a patient
if (!is_logged_in() || $_SESSION['role'] !== 'patient') {
    header("Location: ../auth/login.php");
    exit;
}

// Get patient information
$patient_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Get upcoming appointments
$app_sql = "SELECT a.*, d.name as doctor_name 
           FROM appointments a 
           JOIN users d ON a.doctor_id = d.id 
           WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() 
           ORDER BY a.appointment_date ASC LIMIT 5";
$app_stmt = $conn->prepare($app_sql);
$app_stmt->bind_param("i", $patient_id);
$app_stmt->execute();
$appointments = $app_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Patient Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if($appointments->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($app = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo format_date($app['appointment_date']); ?></td>
                                    <td><?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $app['status'] == 'confirmed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No upcoming appointments.</p>
                <?php endif; ?>
                <div class="text-center">
                    <a href="appointments.php" class="btn btn-primary">Manage Appointments</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="appointments.php?action=new" class="btn btn-success">Book New Appointment</a>
                    <a href="medical_history.php" class="btn btn-info">Update Medical History</a>
                    <a href="treatments.php" class="btn btn-secondary">View Treatment Plans</a>
                    <a href="payments.php" class="btn btn-warning">View Payments</a>
                    <a href="profile.php" class="btn btn-primary">Manage Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
