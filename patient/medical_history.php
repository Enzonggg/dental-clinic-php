<?php
include_once('../includes/header.php');

// Check if user is logged in and is a patient
if (!is_logged_in() || $_SESSION['role'] !== 'patient') {
    header("Location: ../auth/login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Create medical_history table if it doesn't exist
$table_check = $conn->query("SHOW TABLES LIKE 'medical_history'");
if ($table_check->num_rows == 0) {
    $create_table_sql = "CREATE TABLE medical_history (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        patient_id INT(11) NOT NULL,
        allergies TEXT NULL,
        medications TEXT NULL,
        medical_conditions TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id)
    )";
    
    if (!$conn->query($create_table_sql)) {
        $error_message = "Failed to create medical history table: " . $conn->error;
    }
}

// Get existing medical history
$medical_sql = "SELECT * FROM medical_history WHERE patient_id = ?";
$medical_stmt = $conn->prepare($medical_sql);
$medical_stmt->bind_param("i", $patient_id);
$medical_stmt->execute();
$medical_result = $medical_stmt->get_result();
$medical_history = $medical_result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $allergies = sanitize_input($_POST['allergies']);
    $medications = sanitize_input($_POST['medications']);
    $medical_conditions = sanitize_input($_POST['medical_conditions']);
    
    if ($medical_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE medical_history SET allergies = ?, medications = ?, medical_conditions = ? WHERE patient_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $allergies, $medications, $medical_conditions, $patient_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Medical history updated successfully.";
            
            // Refresh data
            $medical_stmt->execute();
            $medical_result = $medical_stmt->get_result();
            $medical_history = $medical_result->fetch_assoc();
        } else {
            $error_message = "Failed to update medical history: " . $conn->error;
        }
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO medical_history (patient_id, allergies, medications, medical_conditions) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isss", $patient_id, $allergies, $medications, $medical_conditions);
        
        if ($insert_stmt->execute()) {
            $success_message = "Medical history saved successfully.";
            
            // Refresh data
            $medical_stmt->execute();
            $medical_result = $medical_stmt->get_result();
            $medical_history = $medical_result->fetch_assoc();
        } else {
            $error_message = "Failed to save medical history: " . $conn->error;
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Medical History</h2>
        <p>Please provide accurate information about your medical history to help us provide better care.</p>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Your Medical Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="allergies" class="form-label">Allergies</label>
                        <textarea class="form-control" id="allergies" name="allergies" rows="3" 
                                  placeholder="List any allergies you have (medications, foods, materials, etc.)"><?php echo htmlspecialchars($medical_history['allergies'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medications" class="form-label">Current Medications</label>
                        <textarea class="form-control" id="medications" name="medications" rows="3" 
                                  placeholder="List all medications you are currently taking"><?php echo htmlspecialchars($medical_history['medications'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="medical_conditions" class="form-label">Medical Conditions</label>
                        <textarea class="form-control" id="medical_conditions" name="medical_conditions" rows="4" 
                                  placeholder="List any medical conditions you have (e.g., diabetes, heart disease, etc.)"><?php echo htmlspecialchars($medical_history['medical_conditions'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Medical History</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Why This Matters</h5>
            </div>
            <div class="card-body">
                <p>Your medical history is important for several reasons:</p>
                <ul>
                    <li><strong>Safety:</strong> Helps prevent adverse reactions to dental treatments or medications</li>
                    <li><strong>Better Treatment:</strong> Allows us to adapt treatments to your specific needs</li>
                    <li><strong>Comprehensive Care:</strong> Helps us understand how your overall health may affect your dental health</li>
                </ul>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Your medical information is kept strictly confidential and is only accessible to your healthcare providers.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
