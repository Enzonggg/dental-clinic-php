<?php
include_once('../includes/header.php');

// Check if user is logged in and is a doctor
if (!is_logged_in() || $_SESSION['role'] !== 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle appointment confirmation
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $appointment_id = $_GET['confirm'];
    
    // Check if the appointment belongs to the doctor
    $check_sql = "SELECT * FROM appointments WHERE id = ? AND doctor_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 1) {
        $update_sql = "UPDATE appointments SET status = 'confirmed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $appointment_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Appointment confirmed successfully.";
        } else {
            $error_message = "Failed to confirm appointment: " . $conn->error;
        }
    } else {
        $error_message = "Invalid appointment or you don't have permission to confirm it.";
    }
}

// Handle appointment cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $appointment_id = $_GET['cancel'];
    
    // Check if the appointment belongs to the doctor
    $check_sql = "SELECT * FROM appointments WHERE id = ? AND doctor_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $doctor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 1) {
        $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $appointment_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Appointment cancelled successfully.";
        } else {
            $error_message = "Failed to cancel appointment: " . $conn->error;
        }
    } else {
        $error_message = "Invalid appointment or you don't have permission to cancel it.";
    }
}

// Get filter values
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';

// Base SQL query
$appointments_sql = "SELECT a.*, p.name as patient_name, p.phone as patient_phone 
                    FROM appointments a 
                    JOIN users p ON a.patient_id = p.id 
                    WHERE a.doctor_id = ? ";

// Add filters if set
$params = array($doctor_id);
$types = "i";

if ($status_filter) {
    $appointments_sql .= "AND a.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $appointments_sql .= "AND DATE(a.appointment_date) = ? ";
    $params[] = $date_filter;
    $types .= "s";
}

// Order by date
$appointments_sql .= "ORDER BY a.appointment_date ASC";

// Prepare and execute query
$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param($types, ...$params);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result();

// Get today's appointments
$today = date('Y-m-d');
$today_sql = "SELECT COUNT(*) as count FROM appointments 
             WHERE doctor_id = ? AND DATE(appointment_date) = ? AND status != 'cancelled'";
$today_stmt = $conn->prepare($today_sql);
$today_stmt->bind_param("is", $doctor_id, $today);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
$today_count = $today_result->fetch_assoc()['count'];

// Get upcoming appointments
$upcoming_sql = "SELECT COUNT(*) as count FROM appointments 
               WHERE doctor_id = ? AND DATE(appointment_date) > ? AND status != 'cancelled'";
$upcoming_stmt = $conn->prepare($upcoming_sql);
$upcoming_stmt->bind_param("is", $doctor_id, $today);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();
$upcoming_count = $upcoming_result->fetch_assoc()['count'];

// Get pending appointments
$pending_sql = "SELECT COUNT(*) as count FROM appointments 
              WHERE doctor_id = ? AND status = 'pending'";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("i", $doctor_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_count = $pending_result->fetch_assoc()['count'];
?>

<div class="row">
    <div class="col-md-12">
        <h2>Manage Appointments</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Today's Appointments</h5>
                <h2 class="display-4"><?php echo $today_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Upcoming Appointments</h5>
                <h2 class="display-4"><?php echo $upcoming_count; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Pending Confirmation</h5>
                <h2 class="display-4"><?php echo $pending_count; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Appointments</h5>
                <div>
                    <form class="d-flex" method="get" action="">
                        <select name="status" class="form-select me-2" style="width: auto;">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="date" name="date" class="form-control me-2" value="<?php echo $date_filter; ?>">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <?php if($status_filter || $date_filter): ?>
                            <a href="appointments.php" class="btn btn-secondary ms-2">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if($appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Contact</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($app = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($app['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['patient_phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($app['reason']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['status'] == 'confirmed' ? 'success' : 
                                                    ($app['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="patient_records.php?id=<?php echo $app['patient_id']; ?>" class="btn btn-sm btn-info">
                                                View Patient
                                            </a>
                                            
                                            <?php if($app['status'] == 'pending'): ?>
                                                <a href="appointments.php?confirm=<?php echo $app['id']; ?>" 
                                                   class="btn btn-sm btn-success"
                                                   onclick="return confirm('Are you sure you want to confirm this appointment?')">
                                                    Confirm
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($app['status'] != 'cancelled' && strtotime($app['appointment_date']) > time()): ?>
                                                <a href="appointments.php?cancel=<?php echo $app['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    Cancel
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No appointments found matching your criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
