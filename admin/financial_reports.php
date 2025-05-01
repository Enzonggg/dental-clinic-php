<?php
include_once('../includes/header.php');

// Check if user is logged in and is an admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Default date range is current month
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-t');

// Summary statistics
$summary_sql = "SELECT 
                SUM(t.cost) as total_revenue,
                COUNT(t.id) as treatment_count,
                COUNT(DISTINCT t.patient_id) as patient_count,
                AVG(t.cost) as average_treatment_cost,
                MAX(t.cost) as highest_treatment_cost
                FROM treatments t
                WHERE t.created_at BETWEEN ? AND ?";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("ss", $start_date, $end_date);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Revenue by doctor
$doctor_revenue_sql = "SELECT 
                      u.id, 
                      u.name as doctor_name, 
                      u.specialization,
                      COUNT(t.id) as treatment_count,
                      SUM(t.cost) as revenue
                      FROM treatments t
                      JOIN users u ON t.doctor_id = u.id
                      WHERE t.created_at BETWEEN ? AND ?
                      GROUP BY t.doctor_id
                      ORDER BY revenue DESC";
$doctor_revenue_stmt = $conn->prepare($doctor_revenue_sql);
$doctor_revenue_stmt->bind_param("ss", $start_date, $end_date);
$doctor_revenue_stmt->execute();
$doctor_revenue = $doctor_revenue_stmt->get_result();

// Revenue by treatment type (using diagnosis as proxy for treatment type)
$treatment_revenue_sql = "SELECT 
                         diagnosis as treatment_type,
                         COUNT(id) as count,
                         SUM(cost) as revenue,
                         AVG(cost) as average_cost
                         FROM treatments
                         WHERE created_at BETWEEN ? AND ?
                         GROUP BY diagnosis
                         ORDER BY revenue DESC
                         LIMIT 10";
$treatment_revenue_stmt = $conn->prepare($treatment_revenue_sql);
$treatment_revenue_stmt->bind_param("ss", $start_date, $end_date);
$treatment_revenue_stmt->execute();
$treatment_revenue = $treatment_revenue_stmt->get_result();

// Monthly revenue for the past 12 months
$monthly_revenue_sql = "SELECT 
                       DATE_FORMAT(created_at, '%Y-%m') as month,
                       DATE_FORMAT(created_at, '%b %Y') as month_name,
                       SUM(cost) as revenue,
                       COUNT(id) as treatment_count
                       FROM treatments
                       WHERE created_at >= DATE_SUB(?, INTERVAL 12 MONTH)
                       GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                       ORDER BY month";
$monthly_revenue_stmt = $conn->prepare($monthly_revenue_sql);
$monthly_revenue_stmt->bind_param("s", $end_date);
$monthly_revenue_stmt->execute();
$monthly_revenue = $monthly_revenue_stmt->get_result();

// Payment method statistics
$payment_methods_sql = "SELECT 
                       p.payment_method,
                       COUNT(p.id) as count,
                       SUM(p.amount) as amount
                       FROM payments p
                       WHERE p.payment_date BETWEEN ? AND ?
                       AND p.status = 'completed'
                       GROUP BY p.payment_method";
$payment_methods_stmt = $conn->prepare($payment_methods_sql);
$payment_methods_stmt->bind_param("ss", $start_date, $end_date);
$payment_methods_stmt->execute();
$payment_methods = $payment_methods_stmt->get_result();

// Recent payments
$recent_payments_sql = "SELECT 
                       p.id, 
                       p.amount, 
                       p.payment_method, 
                       p.payment_date, 
                       p.status,
                       t.diagnosis,
                       patient.name as patient_name,
                       doctor.name as doctor_name
                       FROM payments p
                       JOIN treatments t ON p.treatment_id = t.id
                       JOIN users patient ON t.patient_id = patient.id
                       JOIN users doctor ON t.doctor_id = doctor.id
                       ORDER BY p.payment_date DESC
                       LIMIT 10";
$recent_payments = $conn->query($recent_payments_sql);

// Prepare data for charts
$months = [];
$revenue_data = [];
$treatment_count_data = [];

while ($row = $monthly_revenue->fetch_assoc()) {
    $months[] = $row['month_name'];
    $revenue_data[] = $row['revenue'];
    $treatment_count_data[] = $row['treatment_count'];
}

// Reset monthly revenue result for template use
$monthly_revenue_stmt->execute();
$monthly_revenue = $monthly_revenue_stmt->get_result();

// Prepare payment method data for pie chart
$payment_method_labels = [];
$payment_method_data = [];
$payment_method_colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];

$color_index = 0;
$payment_methods_stmt->execute();
$payment_methods = $payment_methods_stmt->get_result();

while ($row = $payment_methods->fetch_assoc()) {
    $payment_method_labels[] = ucfirst($row['payment_method']);
    $payment_method_data[] = $row['amount'];
    $color_index++;
}

// Reset payment methods result for template use
$payment_methods_stmt->execute();
$payment_methods = $payment_methods_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12">
        <h2>Financial Reports</h2>
        <p>View and analyze clinic revenue and financial statistics</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Date Range Selection</h5>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="quickDatesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Quick Dates
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="quickDatesDropdown">
                                <li><a class="dropdown-item" href="?start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-t'); ?>">This Month</a></li>
                                <li><a class="dropdown-item" href="?start_date=<?php echo date('Y-m-01', strtotime('last month')); ?>&end_date=<?php echo date('Y-m-t', strtotime('last month')); ?>">Last Month</a></li>
                                <li><a class="dropdown-item" href="?start_date=<?php echo date('Y-01-01'); ?>&end_date=<?php echo date('Y-12-31'); ?>">This Year</a></li>
                                <li><a class="dropdown-item" href="?start_date=<?php echo date('Y-m-d', strtotime('-90 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>">Last 90 Days</a></li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <h4>Financial Summary (<?php echo date('M j, Y', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?>)</h4>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-primary text-white mb-4">
            <div class="card-body">
                <h5>Total Revenue</h5>
                <h2 class="display-4">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success text-white mb-4">
            <div class="card-body">
                <h5>Treatments</h5>
                <h2 class="display-4"><?php echo $summary['treatment_count'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-info text-white mb-4">
            <div class="card-body">
                <h5>Patients Treated</h5>
                <h2 class="display-4"><?php echo $summary['patient_count'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning text-white mb-4">
            <div class="card-body">
                <h5>Avg. Treatment Cost</h5>
                <h2 class="display-4">$<?php echo number_format($summary['average_treatment_cost'] ?? 0, 2); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Monthly Revenue (Last 12 Months)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Payment Methods</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="paymentMethodsChart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Count</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($payment_methods->num_rows > 0): ?>
                                <?php while($method = $payment_methods->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo ucfirst($method['payment_method']); ?></td>
                                        <td><?php echo $method['count']; ?></td>
                                        <td>$<?php echo number_format($method['amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No payment data available</td>
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
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Revenue by Doctor</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Treatments</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($doctor_revenue->num_rows > 0): ?>
                                <?php while($doctor = $doctor_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td>Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                        <td><?php echo $doctor['treatment_count']; ?></td>
                                        <td>$<?php echo number_format($doctor['revenue'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No revenue data available</td>
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
                <h5>Top Treatments by Revenue</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Treatment Type</th>
                                <th>Count</th>
                                <th>Avg. Cost</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($treatment_revenue->num_rows > 0): ?>
                                <?php while($treatment = $treatment_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($treatment['treatment_type']); ?></td>
                                        <td><?php echo $treatment['count']; ?></td>
                                        <td>$<?php echo number_format($treatment['average_cost'], 2); ?></td>
                                        <td>$<?php echo number_format($treatment['revenue'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No treatment data available</td>
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
                <h5>Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Treatment</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_payments->num_rows > 0): ?>
                                <?php while($payment = $recent_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                                        <td>Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['diagnosis']); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No payment records found</td>
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
                <h5>Monthly Revenue Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Treatment Count</th>
                                <th>Revenue</th>
                                <th>Avg. per Treatment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($monthly_revenue->num_rows > 0): ?>
                                <?php while($month = $monthly_revenue->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $month['month_name']; ?></td>
                                        <td><?php echo $month['treatment_count']; ?></td>
                                        <td>$<?php echo number_format($month['revenue'], 2); ?></td>
                                        <td>$<?php echo number_format($month['revenue'] / $month['treatment_count'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No monthly data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Revenue Chart
    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Treatment Count',
                    data: <?php echo json_encode($treatment_count_data); ?>,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue ($)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    title: {
                        display: true,
                        text: 'Treatment Count'
                    }
                }
            }
        }
    });
    
    // Payment Methods Chart
    var paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    var paymentChart = new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($payment_method_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($payment_method_data); ?>,
                backgroundColor: <?php echo json_encode($payment_method_colors); ?>,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include_once('../includes/footer.php'); ?>
