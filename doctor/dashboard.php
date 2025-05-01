<?php
include_once('../includes/header.php');

// Strict role check - redirect if not a doctor
if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];

// Get today's appointments
$today = date('Y-m-d');
$today_sql = "SELECT a.*, u.name as patient_name 
             FROM appointments a 
             JOIN users u ON a.patient_id = u.id 
             WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ? AND a.status != 'cancelled'
             ORDER BY a.appointment_date ASC";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("is", $doctor_id, $today);
$today_stmt->execute();
$today_appointments = $today_stmt->get_result();

// Get upcoming appointments
$upcoming_sql = "SELECT a.*, u.name as patient_name 
                FROM appointments a 
                JOIN users u ON a.patient_id = u.id 
                WHERE a.doctor_id = ? AND DATE(a.appointment_date) > ? AND a.status != 'cancelled'
                ORDER BY a.appointment_date ASC LIMIT 5";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->bind_param("is", $doctor_id, $today);
$upcoming_stmt->execute();
$upcoming_appointments = $upcoming_stmt->get_result();

// Get patient count
$patient_sql = "SELECT COUNT(DISTINCT patient_id) as patient_count 
               FROM appointments 
               WHERE doctor_id = ?";
$patient_stmt = $conn->prepare($patient_sql);
$patient_stmt->bind_param("i", $doctor_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient_count = $patient_result->fetch_assoc()['patient_count'];

// Get appointment count
$appointment_sql = "SELECT COUNT(*) as appointment_count 
                   FROM appointments 
                   WHERE doctor_id = ? AND status != 'cancelled'";
$appointment_stmt = $conn->prepare($appointment_sql);
$appointment_stmt->bind_param("i", $doctor_id);
$appointment_stmt->execute();
$appointment_result = $appointment_stmt->get_result();
$appointment_count = $appointment_result->fetch_assoc()['appointment_count'];
?>

<div class="row">
    <div class="col-md-12">
        <h2>Doctor Dashboard</h2>
        <p>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Today's Appointments</h5>
                <h2 class="display-4"><?php echo $today_appointments->num_rows; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Total Patients</h5>
                <h2 class="display-4"><?php echo $patient_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Total Appointments</h5>
                <h2 class="display-4"><?php echo $appointment_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <h5>Upcoming Appointments</h5>
                <h2 class="display-4"><?php echo $upcoming_appointments->num_rows; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Today's Appointments</h5>
            </div>
            <div class="card-body">
                <?php if($today_appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($app = $today_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('h:i A', strtotime($app['appointment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['reason']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['status'] == 'confirmed' ? 'success' : 'warning'; 
                                            ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="patient_records.php?id=<?php echo $app['patient_id']; ?>" class="btn btn-sm btn-info">View Records</a>
                                            <?php if($app['status'] == 'pending'): ?>
                                                <a href="appointments.php?confirm=<?php echo $app['id']; ?>" class="btn btn-sm btn-success">Confirm</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No appointments scheduled for today.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="appointments.php" class="btn btn-primary">Manage Appointments</a>
                    <a href="patient_records.php" class="btn btn-info">Patient Records</a>
                    <a href="treatment_plans.php" class="btn btn-warning">Treatment Plans</a>
                    <a href="prescriptions.php" class="btn btn-success">Prescriptions</a>
                    <a href="schedule.php" class="btn btn-secondary">My Schedule</a>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if($upcoming_appointments->num_rows > 0): ?>
                    <ul class="list-group">
                        <?php while($app = $upcoming_appointments->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($app['patient_name']); ?></h6>
                                    <small><?php echo format_date($app['appointment_date']); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($app['reason']); ?></p>
                                <small class="text-muted">
                                    Status: 
                                    <span class="badge bg-<?php 
                                        echo $app['status'] == 'confirmed' ? 'success' : 'warning'; 
                                    ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No upcoming appointments.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
