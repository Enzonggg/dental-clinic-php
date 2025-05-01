<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle payment verification
if (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $payment_id = $_GET['verify'];
    
    $update_sql = "UPDATE payments SET status = 'completed' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $payment_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Payment verified successfully.";
    } else {
        $error_message = "Payment verification failed: " . $conn->error;
    }
}

// Handle payment rejection
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $payment_id = $_GET['reject'];
    
    $update_sql = "UPDATE payments SET status = 'rejected' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $payment_id);
    
    if ($update_stmt->execute()) {
        $success_message = "Payment rejected successfully.";
    } else {
        $error_message = "Payment rejection failed: " . $conn->error;
    }
}

// Get pending payments
$pending_sql = "SELECT p.*, t.diagnosis, t.treatment_plan, t.patient_id, 
                t.doctor_id, t.cost, 
                patient.name as patient_name, 
                doctor.name as doctor_name 
                FROM payments p 
                JOIN treatments t ON p.treatment_id = t.id 
                JOIN users patient ON t.patient_id = patient.id 
                JOIN users doctor ON t.doctor_id = doctor.id 
                WHERE p.status = 'pending' 
                ORDER BY p.payment_date DESC";
$pending_payments = $conn->query($pending_sql);

// Get all payments for history
$all_sql = "SELECT p.*, t.diagnosis, t.treatment_plan, t.patient_id, 
           t.doctor_id, t.cost, 
           patient.name as patient_name, 
           doctor.name as doctor_name 
           FROM payments p 
           JOIN treatments t ON p.treatment_id = t.id 
           JOIN users patient ON t.patient_id = patient.id 
           JOIN users doctor ON t.doctor_id = doctor.id 
           ORDER BY p.payment_date DESC 
           LIMIT 50";
$all_payments = $conn->query($all_sql);

// Get payment statistics
$stats_sql = "SELECT 
              COUNT(*) as total_payments,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
              SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
              SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_payments,
              SUM(CASE WHEN payment_method = 'gcash' THEN 1 ELSE 0 END) as gcash_payments,
              SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) as cash_payments,
              SUM(CASE WHEN payment_method = 'card' THEN 1 ELSE 0 END) as card_payments,
              SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue
              FROM payments";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Payment Verification</h2>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Total Revenue</h5>
                <h2 class="display-4">$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Completed Payments</h5>
                <h2 class="display-4"><?php echo $stats['completed_payments']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Pending Payments</h5>
                <h2 class="display-4"><?php echo $stats['pending_payments']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <h5>GCash Payments</h5>
                <h2 class="display-4"><?php echo $stats['gcash_payments']; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Pending GCash Payments</h5>
            </div>
            <div class="card-body">
                <?php if($pending_payments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Treatment</th>
                                    <th>Doctor</th>
                                    <th>Amount</th>
                                    <th>Reference #</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($payment = $pending_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($payment['payment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['diagnosis']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php if($payment['payment_method'] == 'gcash'): ?>
                                                <strong><?php echo htmlspecialchars($payment['reference_number']); ?></strong>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="payment_verification.php?verify=<?php echo $payment['id']; ?>" 
                                               class="btn btn-sm btn-success"
                                               onclick="return confirm('Are you sure you want to verify this payment?')">
                                                Verify
                                            </a>
                                            <a href="payment_verification.php?reject=<?php echo $payment['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to reject this payment?')">
                                                Reject
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No pending payments to verify.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Payment History</h5>
            </div>
            <div class="card-body">
                <?php if($all_payments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Treatment</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference #</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($payment = $all_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($payment['payment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['diagnosis']); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php if($payment['payment_method'] == 'gcash'): ?>
                                                <span class="badge bg-primary">GCash</span>
                                            <?php elseif($payment['payment_method'] == 'cash'): ?>
                                                <span class="badge bg-secondary">Cash</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Card</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($payment['payment_method'] == 'gcash' && !empty($payment['reference_number'])): ?>
                                                <?php echo htmlspecialchars($payment['reference_number']); ?>
                                            <?php else: ?>
                                                -
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

<?php include_once('../includes/footer.php'); ?>
