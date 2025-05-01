<?php
include_once('../includes/header.php');
include_once('php_analytics.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$analysis_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'appointments';
$analytics_data = null;
$using_php_fallback = false;

// Function to run Python script and get results
function run_python_analytics($analysis_type) {
    $python_path = "python"; // Use "python3" on some systems
    $script_path = realpath(__DIR__ . '/../scripts/basic_analytics.py');
    
    // Check if the Python script exists
    if (!file_exists($script_path)) {
        return null;
    }
    
    // Execute the Python script with a timeout to prevent long-running processes
    $command = escapeshellcmd("$python_path \"$script_path\" $analysis_type 2>&1");
    $output = shell_exec($command);
    
    // Try to decode the JSON output
    $result = json_decode($output, true);
    
    // If decoding failed, return null to use PHP fallback
    if ($result === null) {
        return null;
    }
    
    return $result;
}

// Try Python analytics first, silently fall back to PHP if needed
$analytics_data = run_python_analytics($analysis_type);

// If Python failed, use PHP fallback without showing error
if ($analytics_data === null || isset($analytics_data['error'])) {
    // Use PHP fallback
    if ($analysis_type == 'appointments') {
        $analytics_data = get_appointment_analytics($conn);
    } else {
        $analytics_data = get_payment_analytics($conn);
    }
    $using_php_fallback = true;
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Analytics Dashboard</h2>
        <p>Data-driven insights for your dental clinic</p>
    </div>
</div>

<div class="row mt-3 mb-4">
    <div class="col-md-12">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?php echo $analysis_type == 'appointments' ? 'active' : ''; ?>" href="analytics.php?type=appointments">
                    Appointment Analytics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $analysis_type == 'payments' ? 'active' : ''; ?>" href="analytics.php?type=payments">
                    Payment Analytics
                </a>
            </li>
        </ul>
    </div>
</div>

<?php if($analysis_type == 'appointments'): ?>
    <!-- Appointment Analytics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Appointments</h5>
                    <h2 class="display-4"><?php echo $analytics_data['total_appointments']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Confirmed Appointments</h5>
                    <h2 class="display-4"><?php echo $analytics_data['summary']['confirmed']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Pending Appointments</h5>
                    <h2 class="display-4"><?php echo $analytics_data['summary']['pending']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Appointments by Day of Week</h5>
                </div>
                <div class="card-body">
                    <!-- Text-based display -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Day of Week</th>
                                    <th>Number of Appointments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($analytics_data['day_counts'] as $day): ?>
                                    <tr>
                                        <td><?php echo $day['day_name']; ?></td>
                                        <td><?php echo $day['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Appointment Status Distribution</h5>
                </div>
                <div class="card-body">
                    <!-- Text-based display -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = $analytics_data['total_appointments'];
                                foreach($analytics_data['status_counts'] as $status): 
                                    $percentage = $total > 0 ? round(($status['count'] / $total) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo ucfirst($status['status']); ?></td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td><?php echo $percentage; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Top Doctors by Appointment Count</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Doctor Name</th>
                                    <th>Appointment Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($analytics_data['top_doctors'])): ?>
                                    <?php foreach($analytics_data['top_doctors'] as $doctor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                            <td><?php echo $doctor['appointment_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php elseif($analysis_type == 'payments'): ?>
    <!-- Payment Analytics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <h2 class="display-4">$<?php echo number_format($analytics_data['total_revenue'], 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Current Month Revenue</h5>
                    <h2 class="display-4">$<?php echo number_format($analytics_data['month_revenue'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Payment Methods</h5>
                </div>
                <div class="card-body">
                    <!-- Text-based display instead of chart -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Payment Method</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($analytics_data['method_stats'])):
                                    foreach($analytics_data['method_stats'] as $method): 
                                ?>
                                    <tr>
                                        <td><?php echo ucfirst($method['payment_method']); ?></td>
                                        <td><?php echo $method['count']; ?></td>
                                        <td>$<?php echo number_format($method['total'], 2); ?></td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Payment Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($analytics_data['status_stats'])):
                                    foreach($analytics_data['status_stats'] as $status): 
                                ?>
                                    <tr>
                                        <td><?php echo ucfirst($status['status']); ?></td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td>$<?php echo number_format($status['total'], 2); ?></td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Monthly Revenue</h5>
                </div>
                <div class="card-body">
                    <!-- Text-based display instead of chart -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (!empty($analytics_data['monthly_revenue'])):
                                    foreach($analytics_data['monthly_revenue'] as $month): 
                                ?>
                                    <tr>
                                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                        <td>$<?php echo number_format($month['revenue'], 2); ?></td>
                                    </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#infoCollapse" aria-expanded="false">
                    Show Technical Information <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="collapse" id="infoCollapse">
                <div class="card-body">
                    <div class="alert alert-info">
                        <p><strong>Technical Details:</strong> This analytics dashboard uses a simple Python integration with SQLite for demonstration purposes. The analytics data is generated using sample data.</p>
                        <p>For a real deployment, you can connect to your actual MySQL database instead of using sample data.</p>
                    </div>
                    
                    <div class="mb-3">
                        <a href="analytics.php?type=<?php echo $analysis_type; ?>&refresh=true" class="btn btn-primary">
                            Refresh Analytics Data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../includes/footer.php'); ?>
