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

// Create doctor_schedule table if it doesn't exist yet
$table_check = $conn->query("SHOW TABLES LIKE 'doctor_schedule'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE doctor_schedule (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT(11) NOT NULL,
        day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id),
        UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
    )";
    
    if (!$conn->query($create_table_sql)) {
        $error_message = "Failed to create schedule table: " . $conn->error;
    }
}

// Create time_off table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'time_off'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE time_off (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT(11) NOT NULL,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        reason VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id)
    )";
    
    if (!$conn->query($create_table_sql)) {
        $error_message = "Failed to create time off table: " . $conn->error;
    }
}

// Handle saving regular schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_schedule'])) {
    // First delete existing schedule for this doctor
    $delete_sql = "DELETE FROM doctor_schedule WHERE doctor_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $doctor_id);
    $delete_stmt->execute();
    
    // Days of the week
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    
    foreach ($days as $day) {
        if (isset($_POST[$day . '_active']) && $_POST[$day . '_active'] == '1') {
            $start_time = sanitize_input($_POST[$day . '_start']);
            $end_time = sanitize_input($_POST[$day . '_end']);
            
            // Validate time inputs
            if (empty($start_time) || empty($end_time)) {
                $error_message = "Please provide both start and end times for active days.";
                break;
            }
            
            // Validate end time is after start time
            if ($end_time <= $start_time) {
                $error_message = "End time must be after start time for " . ucfirst($day) . ".";
                break;
            }
            
            // Insert schedule record
            $insert_sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, is_active) 
                          VALUES (?, ?, ?, ?, 1)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("isss", $doctor_id, $day, $start_time, $end_time);
            
            if (!$insert_stmt->execute()) {
                $error_message = "Failed to save schedule for " . ucfirst($day) . ": " . $conn->error;
                break;
            }
        }
    }
    
    if (empty($error_message)) {
        $success_message = "Your schedule has been updated successfully.";
    }
}

// Handle adding time off
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_time_off'])) {
    $start_date = sanitize_input($_POST['time_off_start_date']);
    $start_time = sanitize_input($_POST['time_off_start_time']);
    $end_date = sanitize_input($_POST['time_off_end_date']);
    $end_time = sanitize_input($_POST['time_off_end_time']);
    $reason = sanitize_input($_POST['time_off_reason']);
    
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    
    // Validate dates
    if (strtotime($end_datetime) <= strtotime($start_datetime)) {
        $error_message = "End time must be after start time for time off.";
    } else {
        // Insert time off record
        $insert_sql = "INSERT INTO time_off (doctor_id, start_datetime, end_datetime, reason) 
                      VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isss", $doctor_id, $start_datetime, $end_datetime, $reason);
        
        if ($insert_stmt->execute()) {
            $success_message = "Time off has been scheduled successfully.";
        } else {
            $error_message = "Failed to schedule time off: " . $conn->error;
        }
    }
}

// Handle deleting time off
if (isset($_GET['delete_time_off']) && is_numeric($_GET['delete_time_off'])) {
    $time_off_id = $_GET['delete_time_off'];
    
    // Verify the time off belongs to the doctor
    $verify_sql = "SELECT * FROM time_off WHERE id = ? AND doctor_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $time_off_id, $doctor_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows == 1) {
        $delete_sql = "DELETE FROM time_off WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $time_off_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Time off has been deleted successfully.";
        } else {
            $error_message = "Failed to delete time off: " . $conn->error;
        }
    } else {
        $error_message = "Invalid time off record or you don't have permission to delete it.";
    }
}

// Get current schedule for this doctor
$schedule_sql = "SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $doctor_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();

$schedule = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedule[$row['day_of_week']] = $row;
}

// Get upcoming time off
$time_off_sql = "SELECT * FROM time_off WHERE doctor_id = ? AND end_datetime >= NOW() ORDER BY start_datetime ASC";
$time_off_stmt = $conn->prepare($time_off_sql);
$time_off_stmt->bind_param("i", $doctor_id);
$time_off_stmt->execute();
$upcoming_time_off = $time_off_stmt->get_result();

// Get past time off
$past_time_off_sql = "SELECT * FROM time_off WHERE doctor_id = ? AND end_datetime < NOW() ORDER BY start_datetime DESC LIMIT 10";
$past_time_off_stmt = $conn->prepare($past_time_off_sql);
$past_time_off_stmt->bind_param("i", $doctor_id);
$past_time_off_stmt->execute();
$past_time_off = $past_time_off_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Schedule Management</h2>
        <p>Set your regular working hours and schedule time off.</p>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Regular Working Hours</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php
                    $days = [
                        'monday' => 'Monday',
                        'tuesday' => 'Tuesday',
                        'wednesday' => 'Wednesday',
                        'thursday' => 'Thursday',
                        'friday' => 'Friday',
                        'saturday' => 'Saturday',
                        'sunday' => 'Sunday'
                    ];
                    
                    foreach ($days as $day_key => $day_name):
                        $is_active = isset($schedule[$day_key]) ? 1 : 0;
                        $start_time = isset($schedule[$day_key]) ? $schedule[$day_key]['start_time'] : '09:00';
                        $end_time = isset($schedule[$day_key]) ? $schedule[$day_key]['end_time'] : '17:00';
                    ?>
                        <div class="row mb-3 align-items-center">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" 
                                           id="<?php echo $day_key; ?>_active" name="<?php echo $day_key; ?>_active" 
                                           <?php echo $is_active ? 'checked' : ''; ?> 
                                           onchange="toggleTimeFields('<?php echo $day_key; ?>')">
                                    <label class="form-check-label" for="<?php echo $day_key; ?>_active">
                                        <?php echo $day_name; ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="time" class="form-control" id="<?php echo $day_key; ?>_start" 
                                       name="<?php echo $day_key; ?>_start" value="<?php echo $start_time; ?>" 
                                       <?php echo !$is_active ? 'disabled' : ''; ?>>
                            </div>
                            <div class="col-md-1 text-center">to</div>
                            <div class="col-md-4">
                                <input type="time" class="form-control" id="<?php echo $day_key; ?>_end" 
                                       name="<?php echo $day_key; ?>_end" value="<?php echo $end_time; ?>" 
                                       <?php echo !$is_active ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="save_schedule" class="btn btn-primary">Save Schedule</button>
                </form>
                
                <div class="alert alert-info mt-3">
                    <strong>Note:</strong> Setting your working hours helps patients book appointments during times you're available. 
                    You can always adjust specific days as needed using the Time Off feature.
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Schedule Time Off</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="time_off_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="time_off_start_date" name="time_off_start_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="time_off_start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="time_off_start_time" name="time_off_start_time" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="time_off_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="time_off_end_date" name="time_off_end_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="time_off_end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="time_off_end_time" name="time_off_end_time" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="time_off_reason" class="form-label">Reason (Optional)</label>
                        <input type="text" class="form-control" id="time_off_reason" name="time_off_reason" placeholder="Vacation, Conference, Personal, etc.">
                    </div>
                    
                    <button type="submit" name="add_time_off" class="btn btn-primary">Schedule Time Off</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Upcoming Time Off</h5>
            </div>
            <div class="card-body">
                <?php if($upcoming_time_off->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($time_off = $upcoming_time_off->fetch_assoc()): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php
                                        $start = new DateTime($time_off['start_datetime']);
                                        $end = new DateTime($time_off['end_datetime']);
                                        
                                        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                                            // Same day
                                            echo $start->format('F j, Y');
                                        } else {
                                            // Different days
                                            echo $start->format('M j') . ' - ' . $end->format('M j, Y');
                                        }
                                        ?>
                                    </h6>
                                    <small>
                                        <?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A'); ?>
                                    </small>
                                </div>
                                <?php if (!empty($time_off['reason'])): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($time_off['reason']); ?></p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <a href="schedule.php?delete_time_off=<?php echo $time_off['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this time off?')">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No upcoming time off scheduled.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Previous Time Off</h5>
            </div>
            <div class="card-body">
                <?php if($past_time_off->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Dates</th>
                                    <th>Times</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($time_off = $past_time_off->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $start = new DateTime($time_off['start_datetime']);
                                            $end = new DateTime($time_off['end_datetime']);
                                            
                                            if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                                                // Same day
                                                echo $start->format('M j, Y');
                                            } else {
                                                // Different days
                                                echo $start->format('M j') . ' - ' . $end->format('M j, Y');
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $start->format('g:i A') . ' - ' . $end->format('g:i A'); ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($time_off['reason']) ? htmlspecialchars($time_off['reason']) : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No previous time off history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTimeFields(day) {
    const isChecked = document.getElementById(day + '_active').checked;
    document.getElementById(day + '_start').disabled = !isChecked;
    document.getElementById(day + '_end').disabled = !isChecked;
}

// Set min date of end date based on start date
document.getElementById('time_off_start_date').addEventListener('change', function() {
    document.getElementById('time_off_end_date').min = this.value;
    // If end date is before start date, update it
    if (document.getElementById('time_off_end_date').value < this.value) {
        document.getElementById('time_off_end_date').value = this.value;
    }
});
</script>

<?php include_once('../includes/footer.php'); ?>
