<?php
include_once('../includes/header.php');

// Check if user is logged in and is a doctor
if (!is_logged_in() || get_user_role() != 'doctor') {
    header("Location: ../auth/login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_message = '';
$error_message = '';

// Check if a specific patient is selected or get all patients
if ($patient_id) {
    // Verify that the doctor has access to this patient
    $access_sql = "SELECT COUNT(*) as count FROM appointments 
                  WHERE doctor_id = ? AND patient_id = ?";
    $access_stmt = $conn->prepare($access_sql);
    $access_stmt->bind_param("ii", $doctor_id, $patient_id);
    $access_stmt->execute();
    $access_result = $access_stmt->get_result();
    $has_access = ($access_result->fetch_assoc()['count'] > 0);
    
    if (!$has_access) {
        $error_message = "You don't have access to this patient's records.";
        $patient_id = 0;
    } else {
        // Get patient details
        $patient_sql = "SELECT * FROM users WHERE id = ? AND role = 'patient'";
        $patient_stmt = $conn->prepare($patient_sql);
        $patient_stmt->bind_param("i", $patient_id);
        $patient_stmt->execute();
        $patient_result = $patient_stmt->get_result();
        $patient = $patient_result->fetch_assoc();
        
        // Get patient medical history
        $history_sql = "SELECT * FROM medical_history WHERE patient_id = ?";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("i", $patient_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $medical_history = $history_result->fetch_assoc();
        
        // Get patient treatments
        $treatments_sql = "SELECT * FROM treatments WHERE patient_id = ? ORDER BY created_at DESC";
        $treatments_stmt = $conn->prepare($treatments_sql);
        $treatments_stmt->bind_param("i", $patient_id);
        $treatments_stmt->execute();
        $treatments = $treatments_stmt->get_result();
        
        // Handle new treatment form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_treatment'])) {
            $diagnosis = sanitize_input($_POST['diagnosis']);
            $treatment_plan = sanitize_input($_POST['treatment_plan']);
            $notes = sanitize_input($_POST['notes']);
            $cost = floatval($_POST['cost']);
            
            $insert_sql = "INSERT INTO treatments (patient_id, doctor_id, diagnosis, treatment_plan, notes, cost) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iisssd", $patient_id, $doctor_id, $diagnosis, $treatment_plan, $notes, $cost);
            
            if ($insert_stmt->execute()) {
                $success_message = "Treatment record added successfully.";
                // Refresh the treatments list
                $treatments_stmt->execute();
                $treatments = $treatments_stmt->get_result();
            } else {
                $error_message = "Failed to add treatment record: " . $conn->error;
            }
        }
    }
}

// Get list of all patients for the doctor
$patients_sql = "SELECT DISTINCT u.id, u.name 
                FROM users u 
                JOIN appointments a ON u.id = a.patient_id 
                WHERE a.doctor_id = ? AND u.role = 'patient'
                ORDER BY u.name";
$patients_stmt = $conn->prepare($patients_sql);
$patients_stmt->bind_param("i", $doctor_id);
$patients_stmt->execute();
$patients = $patients_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Patient Records</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Patient List</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php if($patients->num_rows > 0): ?>
                        <?php while($p = $patients->fetch_assoc()): ?>
                            <a href="patient_records.php?id=<?php echo $p['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo ($patient_id == $p['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No patients found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <?php if($patient_id && isset($patient)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Patient Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Name:</h6>
                            <p><?php echo htmlspecialchars($patient['name']); ?></p>
                            
                            <h6>Email:</h6>
                            <p><?php echo htmlspecialchars($patient['email']); ?></p>
                            
                            <h6>Phone:</h6>
                            <p><?php echo isset($patient['phone']) ? htmlspecialchars($patient['phone']) : 'Not provided'; ?></p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Medical History:</h6>
                            <?php if(isset($medical_history)): ?>
                                <p><strong>Allergies:</strong> <?php echo htmlspecialchars($medical_history['allergies']); ?></p>
                                <p><strong>Current Medications:</strong> <?php echo htmlspecialchars($medical_history['medications']); ?></p>
                                <p><strong>Medical Conditions:</strong> <?php echo htmlspecialchars($medical_history['medical_conditions']); ?></p>
                            <?php else: ?>
                                <p>No medical history records available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Treatment History</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTreatmentModal">Add New Treatment</button>
                </div>
                <div class="card-body">
                    <?php if($treatments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Diagnosis</th>
                                        <th>Treatment Plan</th>
                                        <th>Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($treatment = $treatments->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo format_date($treatment['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($treatment['diagnosis']); ?></td>
                                            <td><?php echo htmlspecialchars($treatment['treatment_plan']); ?></td>
                                            <td>$<?php echo number_format($treatment['cost'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No treatment records found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add Treatment Modal -->
            <div class="modal fade" id="addTreatmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Treatment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="diagnosis" class="form-label">Diagnosis</label>
                                    <input type="text" class="form-control" id="diagnosis" name="diagnosis" required>
                                </div>
                                <div class="mb-3">
                                    <label for="treatment_plan" class="form-label">Treatment Plan</label>
                                    <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="cost" class="form-label">Cost ($)</label>
                                    <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                                </div>
                                <button type="submit" name="add_treatment" class="btn btn-primary">Save Treatment</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif($patient_id == 0 && !$error_message): ?>
            <div class="alert alert-info">Please select a patient from the list.</div>
        <?php endif; ?>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
