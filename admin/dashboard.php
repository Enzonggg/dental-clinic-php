<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Get counts for dashboard
$patients_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient'")->fetch_assoc()['count'];
$doctors_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'doctor'")->fetch_assoc()['count'];
$appointments_count = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_count = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];

// Get recent appointments
$recent_appointments_sql = "SELECT a.*, p.name as patient_name, d.name as doctor_name 
                           FROM appointments a 
                           JOIN users p ON a.patient_id = p.id 
                           JOIN users d ON a.doctor_id = d.id 
                           ORDER BY a.created_at DESC LIMIT 10";
$recent_appointments = $conn->query($recent_appointments_sql);

// Get revenue summary
$revenue_sql = "SELECT SUM(cost) as total_revenue FROM treatments";
$revenue_result = $conn->query($revenue_sql);
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'];

// Get monthly revenue for chart
$monthly_revenue_sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(cost) as revenue 
                       FROM treatments 
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                       GROUP BY month 
                       ORDER BY month";
$monthly_revenue = $conn->query($monthly_revenue_sql);
?>

<div class="row">
    <div class="col-md-12">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Total Patients</h5>
                <h2 class="display-4"><?php echo $patients_count; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="user_management.php?role=patient">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Total Doctors</h5>
                <h2 class="display-4"><?php echo $doctors_count; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="user_management.php?role=doctor">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Total Appointments</h5>
                <h2 class="display-4"><?php echo $appointments_count; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="appointment_management.php">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger text-white mb-4">
            <div class="card-body">
                <h5>Pending Appointments</h5>
                <h2 class="display-4"><?php echo $pending_count; ?></h2>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="appointment_management.php?status=pending">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-1"></i> Monthly Revenue</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie me-1"></i> Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body text-center">
                                <h5>Total Revenue</h5>
                                <h2 class="display-4">$<?php echo number_format($total_revenue, 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-secondary text-white mb-4">
                            <div class="card-body text-center">
                                <h5>Avg. Treatment Cost</h5>
                                <?php
                                $avg_cost_sql = "SELECT AVG(cost) as avg_cost FROM treatments";
                                $avg_cost_result = $conn->query($avg_cost_sql);
                                $avg_cost = $avg_cost_result->fetch_assoc()['avg_cost'];
                                ?>
                                <h2 class="display-4">$<?php echo number_format($avg_cost, 2); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="financial_reports.php" class="btn btn-primary">View Detailed Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-table me-1"></i> Recent Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_appointments->num_rows > 0): ?>
                                <?php while($app = $recent_appointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_date($app['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($app['doctor_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['status'] == 'confirmed' ? 'success' : 
                                                    ($app['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="appointment_management.php?view=<?php echo $app['id']; ?>" 
                                               class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No appointments found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-cogs me-1"></i> Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="user_management.php" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-users"></i><br>User Management
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="payment_verification.php" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-money-bill"></i><br>Payment Verification
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="appointment_management.php" class="btn btn-warning btn-lg w-100">
                            <i class="fas fa-calendar-check"></i><br>Appointments
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="analytics.php" class="btn btn-info btn-lg w-100">
                            <i class="fas fa-chart-bar"></i><br>Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for data visualization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    var ctx = document.getElementById('revenueChart').getContext('2d');
    var months = [];
    var revenues = [];
    
    <?php while($month_data = $monthly_revenue->fetch_assoc()): ?>
        months.push('<?php echo date("M Y", strtotime($month_data['month'] . "-01")); ?>');
        revenues.push(<?php echo $month_data['revenue']; ?>);
    <?php endwhile; ?>
    
    var revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Monthly Revenue ($)',
                data: revenues,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include_once('../includes/footer.php'); ?>
