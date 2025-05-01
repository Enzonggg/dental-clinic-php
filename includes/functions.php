<?php
// Security function to sanitize inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function is_logged_in() {
    if(isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

// Function to check user role
function get_user_role() {
    if(isset($_SESSION['role'])) {
        return $_SESSION['role'];
    }
    return null;
}

// Validate user role against allowed values
function validate_role($role) {
    $valid_roles = ['patient', 'doctor', 'admin'];
    return in_array($role, $valid_roles) ? $role : 'patient';
}

// Function to redirect based on user role
function redirect_by_role($role) {
    // Validate role before redirecting
    if (!in_array($role, ['patient', 'doctor', 'admin'])) {
        // Invalid role, redirect to homepage
        header("Location: ../index.php");
        exit;
    }
    
    switch($role) {
        case 'patient':
            header("Location: ../patient/dashboard.php");
            break;
        case 'doctor':
            header("Location: ../doctor/dashboard.php");
            break;
        case 'admin':
            header("Location: ../admin/dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit;
}

// Format date for display
function format_date($date) {
    return date("F j, Y, g:i a", strtotime($date));
}
?>
