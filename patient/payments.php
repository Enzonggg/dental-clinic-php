<?php
include_once('../includes/header.php');

// Check if user is logged in and is a patient
if (!is_logged_in() || get_user_role() != 'patient') {
    header("Location: ../auth/login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get unpaid treatments
$unpaid_sql = "SELECT t.*, u.name as doctor_name 
              FROM treatments t 
              JOIN users u ON t.doctor_id = u.id 
              LEFT JOIN payments p ON t.id = p.treatment_id 
              WHERE t.patient_id = ? AND (p.id IS NULL OR p.status != 'completed') 
              ORDER BY t.created_at DESC";
$unpaid_stmt = $conn->prepare($unpaid_sql);
$unpaid_stmt->bind_param("i", $patient_id);
$unpaid_stmt->execute();
$unpaid_treatments = $unpaid_stmt->get_result();

// Get payment history
$history_sql = "SELECT p.*, t.diagnosis, t.treatment_plan, t.cost, u.name as doctor_name 
               FROM payments p 
               JOIN treatments t ON p.treatment_id = t.id 
               JOIN users u ON t.doctor_id = u.id 
               WHERE t.patient_id = ? 
               ORDER BY p.payment_date DESC";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $patient_id);
$history_stmt->execute();
$payment_history = $history_stmt->get_result();

// Handle payment submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $treatment_id = intval($_POST['treatment_id']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $amount = floatval($_POST['amount']);
    $reference_number = '';
    
    // Generate reference number for GCash payments
    if ($payment_method == 'gcash') {
        $reference_number = sanitize_input($_POST['reference_number']);
        if (empty($reference_number)) {
            $error_message = "GCash Reference Number is required.";
        }
    }
    
    if (empty($error_message)) {
        // Verify the treatment belongs to patient and get cost
        $verify_sql = "SELECT cost FROM treatments WHERE id = ? AND patient_id = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $treatment_id, $patient_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows != 1) {
            $error_message = "Invalid treatment selected.";
        } else {
            $treatment_cost = $verify_result->fetch_assoc()['cost'];
            
            // Verify the payment amount
            if ($amount != $treatment_cost) {
                $error_message = "Payment amount must match the treatment cost.";
            } else {
                // Create payment record
                $status = ($payment_method == 'gcash') ? 'pending' : 'completed';
                $payment_date = date('Y-m-d H:i:s');
                
                $insert_sql = "INSERT INTO payments (treatment_id, amount, payment_method, reference_number, status, payment_date) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("idssss", $treatment_id, $amount, $payment_method, $reference_number, $status, $payment_date);
                
                if ($insert_stmt->execute()) {
                    if ($payment_method == 'gcash') {
                        $success_message = "GCash payment submitted successfully. Your payment is pending verification.";
                    } else {
                        $success_message = "Payment recorded successfully.";
                    }
                    
                    // Refresh the unpaid treatments list
                    $unpaid_stmt->execute();
                    $unpaid_treatments = $unpaid_stmt->get_result();
                    
                    // Refresh the payment history
                    $history_stmt->execute();
                    $payment_history = $history_stmt->get_result();
                } else {
                    $error_message = "Payment failed: " . $conn->error;
                }
            }
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Payments</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5>Pending Payments</h5>
            </div>
            <div class="card-body">
                <?php if($unpaid_treatments->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($treatment = $unpaid_treatments->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($treatment['diagnosis']); ?></h6>
                                    <strong>$<?php echo number_format($treatment['cost'], 2); ?></strong>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($treatment['treatment_plan']); ?></p>
                                <small>Dr. <?php echo htmlspecialchars($treatment['doctor_name']); ?> - <?php echo format_date($treatment['created_at']); ?></small>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $treatment['id']; ?>">
                                        Make Payment
                                    </button>
                                </div>
                                
                                <!-- Payment Modal -->
                                <div class="modal fade" id="paymentModal<?php echo $treatment['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Make Payment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" action="" id="paymentForm<?php echo $treatment['id']; ?>">
                                                    <input type="hidden" name="treatment_id" value="<?php echo $treatment['id']; ?>">
                                                    <input type="hidden" name="amount" value="<?php echo $treatment['cost']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Treatment</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($treatment['diagnosis']); ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Amount</label>
                                                        <input type="text" class="form-control" value="$<?php echo number_format($treatment['cost'], 2); ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="payment_method" class="form-label">Payment Method</label>
                                                        <select class="form-select" id="payment_method<?php echo $treatment['id']; ?>" name="payment_method" onchange="toggleReferenceField(<?php echo $treatment['id']; ?>)">
                                                            <option value="cash">Cash (Pay at Clinic)</option>
                                                            <option value="gcash">GCash</option>
                                                            <option value="card">Credit/Debit Card</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3" id="gcashInfo<?php echo $treatment['id']; ?>" style="display: none;">
                                                        <div class="alert alert-info">
                                                            <h6>GCash Payment Instructions:</h6>
                                                            <p>1. Open your GCash app and log in.</p>
                                                            <p>2. Send payment to: <strong>09123456789</strong> (Dental Clinic)</p>
                                                            <p>3. Use your Patient ID as reference: <strong><?php echo $patient_id; ?></strong></p>
                                                            <p>4. Enter the GCash Reference Number below.</p>
                                                        </div>
                                                        
                                                        <label for="reference_number" class="form-label">GCash Reference Number</label>
                                                        <input type="text" class="form-control" id="reference_number<?php echo $treatment['id']; ?>" name="reference_number" placeholder="e.g. 1234567890">
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" form="paymentForm<?php echo $treatment['id']; ?>" name="make_payment" class="btn btn-primary">Submit Payment</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No pending payments.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5>Payment History</h5>
            </div>
            <div class="card-body">
                <?php if($payment_history->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Treatment</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($payment = $payment_history->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($payment['payment_date']); ?></td>
                                        <td>
                                            <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($payment['treatment_plan']); ?>">
                                                <?php echo htmlspecialchars($payment['diagnosis']); ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php if($payment['payment_method'] == 'gcash'): ?>
                                                <span class="badge bg-primary">GCash</span>
                                                <?php if(!empty($payment['reference_number'])): ?>
                                                    <br><small><?php echo $payment['reference_number']; ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo ucfirst($payment['payment_method']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $payment['status'] == 'completed' ? 'success' : 
                                                    ($payment['status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No payment history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleReferenceField(treatmentId) {
    var paymentMethod = document.getElementById('payment_method' + treatmentId).value;
    var gcashInfo = document.getElementById('gcashInfo' + treatmentId);
    
    if (paymentMethod === 'gcash') {
        gcashInfo.style.display = 'block';
    } else {
        gcashInfo.style.display = 'none';
    }
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
