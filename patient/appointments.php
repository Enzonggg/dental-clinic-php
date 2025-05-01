<?php
include_once('../includes/header.php');

// Check if user is logged in and is a patient
if (!is_logged_in() || get_user_role() != 'patient') {
    header("Location: ../auth/login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';
$success_message = '';
$error_message = '';

// Handle booking new appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
    $doctor_id = sanitize_input($_POST['doctor_id']);
    $appointment_date = sanitize_input($_POST['appointment_date']);
    $appointment_time = sanitize_input($_POST['appointment_time']);
    $reason = sanitize_input($_POST['reason']);
    
    $datetime = $appointment_date . ' ' . $appointment_time;
    
    // Check if the selected time is available
    $check_sql = "SELECT * FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $doctor_id, $datetime);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "The selected time is not available. Please choose another time.";
    } else {
        // Insert new appointment
        $status = 'pending';
        $insert_sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iisss", $patient_id, $doctor_id, $datetime, $reason, $status);
        
        if ($insert_stmt->execute()) {
            $success_message = "Appointment booked successfully! Waiting for confirmation.";
            $action = ''; // Reset action to show all appointments
        } else {
            $error_message = "Failed to book appointment: " . $conn->error;
        }
    }
}

// Handle cancelling appointment
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $appointment_id = $_GET['cancel'];
    
    // Check if the appointment belongs to the patient
    $check_sql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $appointment_id, $patient_id);
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

// Get available doctors for booking form
$doctors_sql = "SELECT id, name, specialization FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($doctors_sql);

// Get patient appointments
$appointments_sql = "SELECT a.*, d.name as doctor_name, d.specialization 
                    FROM appointments a 
                    JOIN users d ON a.doctor_id = d.id 
                    WHERE a.patient_id = ? 
                    ORDER BY a.appointment_date DESC";
$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $patient_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result();
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
        
        <?php if($action == 'new'): ?>
            <!-- Booking Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Book New Appointment</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="doctor_id" class="form-label">Select Doctor</label>
                            <select class="form-select" id="doctor_id" name="doctor_id" required>
                                <option value="">-- Select Doctor --</option>
                                <?php while($doctor = $doctors_result->fetch_assoc()): ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        Dr. <?php echo htmlspecialchars($doctor['name']); ?> 
                                        (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="appointment_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="appointment_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason for Visit</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
                        <a href="appointments.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Appointments List -->
            <div class="mb-3">
                <a href="appointments.php?action=new" class="btn btn-success">Book New Appointment</a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Your Appointments</h5>
                </div>
                <div class="card-body">
                    <?php if($appointments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Doctor</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($app = $appointments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo format_date($app['appointment_date']); ?></td>
                                            <td>Dr. <?php echo htmlspecialchars($app['doctor_name']); ?><br>
                                                <small><?php echo htmlspecialchars($app['specialization']); ?></small>
                                            </td>
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
                                                <?php if($app['status'] != 'cancelled' && strtotime($app['appointment_date']) > time()): ?>
                                                    <a href="appointments.php?cancel=<?php echo $app['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                        Cancel
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>You don't have any appointments yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
