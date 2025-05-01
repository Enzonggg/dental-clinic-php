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
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

// Check if prescriptions table exists, if not create it - MOVED UP TO BEGINNING OF SCRIPT
$table_check = $conn->query("SHOW TABLES LIKE 'prescriptions'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE prescriptions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        patient_id INT(11) NOT NULL,
        doctor_id INT(11) NOT NULL,
        medication VARCHAR(255) NOT NULL,
        dosage VARCHAR(100) NOT NULL,
        frequency VARCHAR(100) NOT NULL,
        duration VARCHAR(100) NOT NULL,
        instructions TEXT NOT NULL,
        notes TEXT NULL,
        status ENUM('active', 'completed') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id),
        FOREIGN KEY (doctor_id) REFERENCES users(id)
    )";
    
    if (!$conn->query($create_table_sql)) {
        $error_message = "Failed to create prescriptions table: " . $conn->error;
    } else {
        $success_message = "Prescriptions system initialized successfully.";
    }
}

// Handle prescription creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_prescription'])) {
    $patient_id = intval($_POST['patient_id']);
    $medication = sanitize_input($_POST['medication']);
    $dosage = sanitize_input($_POST['dosage']);
    $frequency = sanitize_input($_POST['frequency']);
    $duration = sanitize_input($_POST['duration']);
    $instructions = sanitize_input($_POST['instructions']);
    $notes = sanitize_input($_POST['notes']);
    
    // Verify patient exists and doctor has access to this patient
    $access_sql = "SELECT COUNT(*) as count FROM appointments 
                  WHERE doctor_id = ? AND patient_id = ?";
    $access_stmt = $conn->prepare($access_sql);
    $access_stmt->bind_param("ii", $doctor_id, $patient_id);
    $access_stmt->execute();
    $access_result = $access_stmt->get_result();
    $has_access = ($access_result->fetch_assoc()['count'] > 0);
    
    if (!$has_access) {
        $error_message = "You don't have access to prescribe medication for this patient.";
    } else {
        // Insert new prescription
        $insert_sql = "INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, frequency, duration, instructions, notes) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iissssss", $patient_id, $doctor_id, $medication, $dosage, $frequency, $duration, $instructions, $notes);
        
        if ($insert_stmt->execute()) {
            $success_message = "Prescription added successfully.";
        } else {
            $error_message = "Failed to add prescription: " . $conn->error;
        }
    }
}

// Handle completing prescription
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $prescription_id = $_GET['complete'];
    
    // Verify the prescription belongs to the doctor
    $verify_sql = "SELECT * FROM prescriptions WHERE id = ? AND doctor_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $prescription_id, $doctor_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows != 1) {
        $error_message = "Invalid prescription or you don't have permission to modify it.";
    } else {
        $update_sql = "UPDATE prescriptions SET status = 'completed' WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $prescription_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Prescription marked as completed.";
        } else {
            $error_message = "Failed to update prescription: " . $conn->error;
        }
    }
}

// Get patient list for doctor
$patients_sql = "SELECT DISTINCT u.id, u.name 
                FROM users u 
                JOIN appointments a ON u.id = a.patient_id 
                WHERE a.doctor_id = ? AND u.role = 'patient'
                ORDER BY u.name";
$patients_stmt = $conn->prepare($patients_sql);
$patients_stmt->bind_param("i", $doctor_id);
$patients_stmt->execute();
$patients = $patients_stmt->get_result();

// Initialize variables
$prescriptions = null;
$prescriptions_exist = false;

// Get prescriptions - but only if the table exists
if ($table_check->num_rows > 0) {
    try {
        // Get prescriptions - either for specific patient or all patients
        if ($patient_id > 0) {
            $prescriptions_sql = "SELECT p.*, u.name as patient_name 
                                FROM prescriptions p 
                                JOIN users u ON p.patient_id = u.id 
                                WHERE p.doctor_id = ? AND p.patient_id = ? 
                                ORDER BY p.created_at DESC";
            $prescriptions_stmt = $conn->prepare($prescriptions_sql);
            $prescriptions_stmt->bind_param("ii", $doctor_id, $patient_id);
        } else {
            $prescriptions_sql = "SELECT p.*, u.name as patient_name 
                                FROM prescriptions p 
                                JOIN users u ON p.patient_id = u.id 
                                WHERE p.doctor_id = ? 
                                ORDER BY p.created_at DESC";
            $prescriptions_stmt = $conn->prepare($prescriptions_sql);
            $prescriptions_stmt->bind_param("i", $doctor_id);
        }
        
        $prescriptions_stmt->execute();
        $prescriptions = $prescriptions_stmt->get_result();
        $prescriptions_exist = true;
    } catch (Exception $e) {
        $error_message = "Error retrieving prescriptions: " . $e->getMessage();
    }
}

// If a specific patient is selected, get their details
if ($patient_id > 0) {
    $patient_sql = "SELECT * FROM users WHERE id = ? AND role = 'patient'";
    $patient_stmt = $conn->prepare($patient_sql);
    $patient_stmt->bind_param("i", $patient_id);
    $patient_stmt->execute();
    $patient_result = $patient_stmt->get_result();
    $patient = $patient_result->fetch_assoc();
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Prescriptions Management</h2>
        
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
                <h5>Select Patient</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="prescriptions.php" class="list-group-item list-group-item-action <?php echo $patient_id == 0 ? 'active' : ''; ?>">
                        All Patients
                    </a>
                    <?php if($patients->num_rows > 0): ?>
                        <?php while($p = $patients->fetch_assoc()): ?>
                            <a href="prescriptions.php?patient_id=<?php echo $p['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo ($patient_id == $p['id']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted p-2">No patients found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <?php if($patient_id > 0 && isset($patient)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Prescribe Medication for <?php echo htmlspecialchars($patient['name']); ?></h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
                        Add New Prescription
                    </button>
                </div>
                <div class="card-body">
                    <h6>Patient Information:</h6>
                    <p>Name: <?php echo htmlspecialchars($patient['name']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($patient['email']); ?></p>
                    <p>Phone: <?php echo isset($patient['phone']) ? htmlspecialchars($patient['phone']) : 'Not provided'; ?></p>
                    
                    <?php
                    // Get patient medical history if available
                    $history_sql = "SELECT * FROM medical_history WHERE patient_id = ?";
                    $history_stmt = $conn->prepare($history_sql);
                    $history_stmt->bind_param("i", $patient_id);
                    $history_stmt->execute();
                    $history_result = $history_stmt->get_result();
                    $medical_history = $history_result->fetch_assoc();
                    ?>
                    
                    <?php if(isset($medical_history)): ?>
                        <div class="alert alert-info mt-3">
                            <h6>Medical Information (Important for prescribing):</h6>
                            <p><strong>Allergies:</strong> <?php echo htmlspecialchars($medical_history['allergies']); ?></p>
                            <p><strong>Current Medications:</strong> <?php echo htmlspecialchars($medical_history['medications']); ?></p>
                            <p><strong>Medical Conditions:</strong> <?php echo htmlspecialchars($medical_history['medical_conditions']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <p>No medical history available for this patient. Please ask the patient to update their medical information.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add Prescription Modal -->
            <div class="modal fade" id="addPrescriptionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">New Prescription for <?php echo htmlspecialchars($patient['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="">
                                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                                
                                <div class="mb-3">
                                    <label for="medication" class="form-label">Medication</label>
                                    <input type="text" class="form-control" id="medication" name="medication" required placeholder="Medication name">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="dosage" class="form-label">Dosage</label>
                                            <input type="text" class="form-control" id="dosage" name="dosage" required placeholder="e.g., 500mg">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="frequency" class="form-label">Frequency</label>
                                            <input type="text" class="form-control" id="frequency" name="frequency" required placeholder="e.g., 3 times daily">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="duration" class="form-label">Duration</label>
                                            <input type="text" class="form-control" id="duration" name="duration" required placeholder="e.g., 7 days">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3" required placeholder="How to take the medication"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional instructions or warnings"></textarea>
                                </div>
                                
                                <button type="submit" name="add_prescription" class="btn btn-primary">Save Prescription</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif($patient_id > 0 && !isset($patient)): ?>
            <div class="alert alert-danger">Patient not found or you don't have access to this patient.</div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Prescriptions Management</h5>
                </div>
                <div class="card-body">
                    <p>Please select a patient from the list to prescribe medication or view their prescriptions.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Prescriptions Table -->
        <div class="card">
            <div class="card-header">
                <h5><?php echo $patient_id > 0 ? "Prescriptions for " . htmlspecialchars($patient['name'] ?? '') : "All Prescriptions"; ?></h5>
            </div>
            <div class="card-body">
                <?php if($prescriptions_exist && $prescriptions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Instructions</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($prescription = $prescriptions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($prescription['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['medication']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                                        <td>
                                            <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($prescription['instructions']); ?>">
                                                <?php echo substr(htmlspecialchars($prescription['instructions']), 0, 30) . 
                                                      (strlen($prescription['instructions']) > 30 ? '...' : ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $prescription['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($prescription['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $prescription['id']; ?>">
                                                View
                                            </button>
                                            
                                            <?php if($prescription['status'] == 'active'): ?>
                                                <a href="prescriptions.php?complete=<?php echo $prescription['id']; ?>&patient_id=<?php echo $prescription['patient_id']; ?>" 
                                                   class="btn btn-sm btn-warning"
                                                   onclick="return confirm('Mark this prescription as completed?')">
                                                    Complete
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="javascript:void(0);" class="btn btn-sm btn-secondary" onclick="printPrescription(<?php echo $prescription['id']; ?>)">
                                                Print
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Prescription Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div id="prescription-<?php echo $prescription['id']; ?>">
                                                        <div class="text-center mb-4">
                                                            <h4>Dental Clinic System</h4>
                                                            <p>123 Dental Avenue, City, State</p>
                                                            <p>Phone: (123) 456-7890</p>
                                                            <hr>
                                                            <h5>Prescription</h5>
                                                        </div>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <p><strong>Patient:</strong> <?php echo htmlspecialchars($prescription['patient_name']); ?></p>
                                                            </div>
                                                            <div class="col-md-6 text-end">
                                                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($prescription['created_at'])); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <h6>Medication</h6>
                                                            <p><?php echo htmlspecialchars($prescription['medication']); ?></p>
                                                        </div>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-md-4">
                                                                <h6>Dosage</h6>
                                                                <p><?php echo htmlspecialchars($prescription['dosage']); ?></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6>Frequency</h6>
                                                                <p><?php echo htmlspecialchars($prescription['frequency']); ?></p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <h6>Duration</h6>
                                                                <p><?php echo htmlspecialchars($prescription['duration']); ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <h6>Instructions</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($prescription['instructions'])); ?></p>
                                                        </div>
                                                        
                                                        <?php if(!empty($prescription['notes'])): ?>
                                                            <div class="mb-3">
                                                                <h6>Additional Notes</h6>
                                                                <p><?php echo nl2br(htmlspecialchars($prescription['notes'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mt-4 text-end">
                                                            <p>Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                                                            <p>Signature: _____________________</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-primary" onclick="printPrescription(<?php echo $prescription['id']; ?>)">Print</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No prescriptions found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function printPrescription(id) {
    const content = document.getElementById('prescription-' + id).innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
        <head>
            <title>Prescription</title>
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    font-size: 14px;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ccc;
                }
                h4, h5, h6 { margin-top: 10px; margin-bottom: 5px; }
                p { margin: 5px 0; }
                hr { border: 1px solid #eee; }
                .text-end { text-align: right; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                ${content}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                }
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include_once('../includes/footer.php'); ?>
