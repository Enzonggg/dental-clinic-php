<?php
// This file provides a PHP fallback for analytics when Python is not available

function get_appointment_analytics($conn) {
    // Get appointment counts by status
    $status_sql = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
    $status_result = $conn->query($status_sql);
    $status_counts = [];
    
    while ($row = $status_result->fetch_assoc()) {
        $status_counts[] = $row;
    }
    
    // Get appointment counts by day of week
    $day_sql = "SELECT DAYNAME(appointment_date) as day_name, COUNT(*) as count 
               FROM appointments 
               GROUP BY day_name 
               ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $day_result = $conn->query($day_sql);
    $day_counts = [];
    
    while ($row = $day_result->fetch_assoc()) {
        $day_counts[] = $row;
    }
    
    // Get top doctors by appointment count
    $doctor_sql = "SELECT d.name as doctor_name, COUNT(a.id) as appointment_count
                  FROM appointments a
                  JOIN users d ON a.doctor_id = d.id
                  GROUP BY a.doctor_id
                  ORDER BY appointment_count DESC
                  LIMIT 5";
    $doctor_result = $conn->query($doctor_sql);
    $top_doctors = [];
    
    while ($row = $doctor_result->fetch_assoc()) {
        $top_doctors[] = $row;
    }
    
    // Generate summary data
    $total_appointments = 0;
    $pending_count = 0;
    $confirmed_count = 0;
    $cancelled_count = 0;
    
    foreach ($status_counts as $status) {
        $total_appointments += $status['count'];
        
        if ($status['status'] == 'pending') {
            $pending_count = $status['count'];
        } else if ($status['status'] == 'confirmed') {
            $confirmed_count = $status['count'];
        } else if ($status['status'] == 'cancelled') {
            $cancelled_count = $status['count'];
        }
    }
    
    return [
        "total_appointments" => $total_appointments,
        "status_counts" => $status_counts,
        "day_counts" => $day_counts,
        "top_doctors" => $top_doctors,
        "summary" => [
            "pending" => $pending_count,
            "confirmed" => $confirmed_count,
            "cancelled" => $cancelled_count
        ]
    ];
}

function get_payment_analytics($conn) {
    // Get payment counts by method
    $method_sql = "SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                  FROM payments
                  GROUP BY payment_method";
    $method_result = $conn->query($method_sql);
    $method_stats = [];
    
    while ($row = $method_result->fetch_assoc()) {
        $method_stats[] = $row;
    }
    
    // Get payment counts by status
    $status_sql = "SELECT status, COUNT(*) as count, SUM(amount) as total
                  FROM payments
                  GROUP BY status";
    $status_result = $conn->query($status_sql);
    $status_stats = [];
    
    while ($row = $status_result->fetch_assoc()) {
        $status_stats[] = $row;
    }
    
    // Get monthly revenue
    $monthly_sql = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month,
                   SUM(amount) as revenue
                   FROM payments
                   WHERE status = 'completed'
                   GROUP BY month
                   ORDER BY month DESC
                   LIMIT 6";
    $monthly_result = $conn->query($monthly_sql);
    $monthly_revenue = [];
    
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_revenue[] = $row;
    }
    
    // Calculate total revenue
    $total_sql = "SELECT SUM(amount) as total_revenue
                 FROM payments
                 WHERE status = 'completed'";
    $total_result = $conn->query($total_sql);
    $total_row = $total_result->fetch_assoc();
    $total_revenue = floatval($total_row['total_revenue'] ?? 0);
    
    // Calculate current month revenue
    $current_month = date('Y-m');
    $month_sql = "SELECT SUM(amount) as month_revenue
                 FROM payments
                 WHERE status = 'completed'
                 AND DATE_FORMAT(payment_date, '%Y-%m') = '$current_month'";
    $month_result = $conn->query($month_sql);
    $month_row = $month_result->fetch_assoc();
    $month_revenue = floatval($month_row['month_revenue'] ?? 0);
    
    return [
        "total_revenue" => $total_revenue,
        "month_revenue" => $month_revenue,
        "method_stats" => $method_stats,
        "status_stats" => $status_stats,
        "monthly_revenue" => $monthly_revenue
    ];
}
?>
