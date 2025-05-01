<?php
include_once('../includes/header.php');

// Check if user is logged in and is a patient
if (!is_logged_in() || $_SESSION['role'] !== 'patient') {
    header("Location: ../auth/login.php");
    exit;
}

$patient_id = $_SESSION['user_id'];

// Get treatments for the patient
$treatments_sql = "SELECT t.*, u.name as doctor_name, u.specialization as doctor_specialization
                  FROM treatments t
                  JOIN users u ON t.doctor_id = u.id
                  WHERE t.patient_id = ?
                  ORDER BY t.created_at DESC";
$treatments_stmt = $conn->prepare($treatments_sql);
$treatments_stmt->bind_param("i", $patient_id);
$treatments_stmt->execute();
$treatments = $treatments_stmt->get_result();

// Get payment status for each treatment
$treatments_data = [];
while ($treatment = $treatments->fetch_assoc()) {
    $payment_sql = "SELECT status FROM payments WHERE treatment_id = ? ORDER BY payment_date DESC LIMIT 1";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("i", $treatment['id']);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();
    
    if ($payment_result->num_rows > 0) {
        $payment = $payment_result->fetch_assoc();
        $treatment['payment_status'] = $payment['status'];
    } else {
        $treatment['payment_status'] = 'unpaid';
    }
    
    $treatments_data[] = $treatment;
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>My Treatment Plans</h2>
        <p>View your treatment plans and payment status</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Your Treatment History</h5>
            </div>
            <div class="card-body">
                <?php if(count($treatments_data) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Treatment Plan</th>
                                    <th>Cost</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($treatments_data as $treatment): ?>
                                    <tr>
                                        <td><?php echo format_date($treatment['created_at']); ?></td>
                                        <td>
                                            Dr. <?php echo htmlspecialchars($treatment['doctor_name']); ?><br>
                                            <small><?php echo htmlspecialchars($treatment['doctor_specialization']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($treatment['diagnosis']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#planModal<?php echo $treatment['id']; ?>">
                                                View Plan
                                            </button>
                                            
                                            <!-- Treatment Plan Modal -->
                                            <div class="modal fade" id="planModal<?php echo $treatment['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Treatment Plan Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h6>Diagnosis:</h6>
                                                            <p><?php echo htmlspecialchars($treatment['diagnosis']); ?></p>
                                                            
                                                            <h6>Treatment Plan:</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($treatment['treatment_plan'])); ?></p>
                                                            
                                                            <?php if(!empty($treatment['notes'])): ?>
                                                                <h6>Notes:</h6>
                                                                <p><?php echo nl2br(htmlspecialchars($treatment['notes'])); ?></p>
                                                            <?php endif; ?>
                                                            
                                                            <div class="alert alert-info">
                                                                <strong>Cost: $<?php echo number_format($treatment['cost'], 2); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($treatment['cost'], 2); ?></td>
                                        <td>
                                            <?php if($treatment['payment_status'] == 'completed'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif($treatment['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($treatment['payment_status'] == 'unpaid'): ?>
                                                <a href="payments.php" class="btn btn-sm btn-primary">Pay Now</a>
                                            <?php elseif($treatment['payment_status'] == 'pending'): ?>
                                                <a href="payments.php" class="btn btn-sm btn-secondary">View Payment</a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled>Paid</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No treatment plans found.</p>
                    <div class="alert alert-info">
                        <p>Your dentist will create treatment plans during your appointments. After your next visit, you'll be able to see your treatment details here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
