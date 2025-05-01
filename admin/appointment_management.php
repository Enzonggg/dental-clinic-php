<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle appointment status changes
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $appointment_id = intval($_GET['confirm']);
    
    $update_sql = "UPDATE appointments SET status = 'confirmed' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $appointment_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Appointment confirmed successfully.";
    } else {
        $error_message = "Failed to confirm appointment: " . $conn->error;
    }
}

if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $appointment_id = intval($_GET['cancel']);
    
    $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $appointment_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Appointment cancelled successfully.";
    } else {
        $error_message = "Failed to cancel appointment: " . $conn->error;
    }
}

// Handle filters
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$doctor_filter = isset($_GET['doctor']) ? intval($_GET['doctor']) : 0;
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$search_term = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query based on filters
$query = "SELECT a.*, 
          p.name as patient_name, 
          d.name as doctor_name, 
          d.specialization
          FROM appointments a
          JOIN users p ON a.patient_id = p.id
          JOIN users d ON a.doctor_id = d.id
          WHERE 1=1";

$params = [];
$param_types = "";

if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($doctor_filter > 0) {
    $query .= " AND a.doctor_id = ?";
    $params[] = $doctor_filter;
    $param_types .= "i";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(a.appointment_date) = ?";
    $params[] = $date_filter;
    $param_types .= "s";
}

if (!empty($search_term)) {
    $query .= " AND (p.name LIKE ? OR d.name LIKE ?)";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$query .= " ORDER BY a.appointment_date DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$appointments = $stmt->get_result();

// Get all doctors for filter dropdown
$doctors_sql = "SELECT id, name, specialization FROM users WHERE role = 'doctor' ORDER BY name";
$doctors = $conn->query($doctors_sql);

// Get appointment statistics
$stats_sql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
              SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
              COUNT(DISTINCT patient_id) as unique_patients,
              COUNT(DISTINCT doctor_id) as unique_doctors
              FROM appointments";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Appointment Management</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Total Appointments</h5>
                <h2 class="display-4"><?php echo $stats['total']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Pending</h5>
                <h2 class="display-4"><?php echo $stats['pending']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Confirmed</h5>
                <h2 class="display-4"><?php echo $stats['confirmed']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
            <div class="card-body">
                <h5>Cancelled</h5>
                <h2 class="display-4"><?php echo $stats['cancelled']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Filter Appointments</h5>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="doctor" class="form-label">Doctor</label>
                        <select class="form-select" id="doctor" name="doctor">
                            <option value="0">All Doctors</option>
                            <?php while($doctor = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['id']; ?>" <?php echo $doctor_filter == $doctor['id'] ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($doctor['name']); ?> 
                                    (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Patient or doctor name" value="<?php echo $search_term; ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="appointment_management.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>
                    Appointments
                    <?php 
                    if (!empty($status_filter)) echo " - " . ucfirst($status_filter);
                    if (!empty($date_filter)) echo " on " . date('F j, Y', strtotime($date_filter));
                    if (!empty($search_term)) echo " matching '" . $search_term . "'";
                    ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if($appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($appointment = $appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($appointment['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td>
                                            Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?><br>
                                            <small><?php echo htmlspecialchars($appointment['specialization']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $appointment['status'] == 'confirmed' ? 'success' : 
                                                    ($appointment['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo format_date($appointment['created_at']); ?></td>
                                        <td>
                                            <?php if($appointment['status'] == 'pending'): ?>
                                                <a href="appointment_management.php?confirm=<?php echo $appointment['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   onclick="return confirm('Are you sure you want to confirm this appointment?')">
                                                    Confirm
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($appointment['status'] != 'cancelled'): ?>
                                                <a href="appointment_management.php?cancel=<?php echo $appointment['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    Cancel
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $appointment['id']; ?>">
                                                Details
                                            </button>
                                            
                                            <!-- Details Modal -->
                                            <div class="modal fade" id="detailsModal<?php echo $appointment['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Appointment Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <h6>Appointment ID:</h6>
                                                                    <p><?php echo $appointment['id']; ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Status:</h6>
                                                                    <p>
                                                                        <span class="badge bg-<?php 
                                                                            echo $appointment['status'] == 'confirmed' ? 'success' : 
                                                                                ($appointment['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                                                        ?>">
                                                                            <?php echo ucfirst($appointment['status']); ?>
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <h6>Date:</h6>
                                                                    <p><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Time:</h6>
                                                                    <p><?php echo date('g:i A', strtotime($appointment['appointment_date'])); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <h6>Patient:</h6>
                                                                    <p><?php echo htmlspecialchars($appointment['patient_name']); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Doctor:</h6>
                                                                    <p>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <h6>Reason for Visit:</h6>
                                                                <p><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <h6>Created:</h6>
                                                                    <p><?php echo format_date($appointment['created_at']); ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date picker defaults
    const dateInput = document.getElementById('date');
    if (dateInput && !dateInput.value) {
        // Don't set a default date if none is specified
    }
});
</script>

<?php include_once('../includes/footer.php'); ?>
