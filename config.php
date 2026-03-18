<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================= DATABASE CONNECTION =================
$host = 'localhost';
$user = 'root';
$pass = '1234'; // Update with your XAMPP password
$dbname = 'medisyncc_db';

// Enable error reporting for MySQLi (Best practice for debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Security: Check if user is logged in
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// ================= SUBSCRIPTION & TRIAL ENGINE =================
function validateSubscription($conn, $user_id) {
    $stmt = $conn->prepare("SELECT plan_type, trial_start_date, subscription_end, subscription_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Safety check if user doesn't have the subscription columns yet
    if (!$user || !isset($user['subscription_status'])) {
        return ['status' => 'active', 'plan' => 'Free', 'trial_days_left' => 0]; 
    }

    $now = new DateTime();
    $status = $user['subscription_status'];
    $plan = $user['plan_type'];
    $days_left = 0;

    // 1. Handle 4-Day Free Trial
    if ($status === 'trial') {
        $trial_start = new DateTime($user['trial_start_date']);
        $trial_end = clone $trial_start;
        $trial_end->modify('+4 days');
        
        if ($now > $trial_end) {
            // Trial Expired! Lock them to Free plan
            $conn->query("UPDATE users SET subscription_status = 'expired', plan_type = 'Free' WHERE id = $user_id");
            $status = 'expired';
            $plan = 'Free';
        } else {
            $days_left = $now->diff($trial_end)->days;
            $plan = 'Premium'; // Give all features during trial
        }
    } 
    // 2. Handle Paid Subscription Expiry
    else if ($status === 'active' && $user['subscription_end']) {
        $sub_end = new DateTime($user['subscription_end']);
        if ($now > $sub_end) {
            $conn->query("UPDATE users SET subscription_status = 'expired', plan_type = 'Free' WHERE id = $user_id");
            $status = 'expired';
            $plan = 'Free';
        }
    }

    return ['status' => $status, 'plan' => $plan, 'trial_days_left' => $days_left];
}

// --- GLOBAL FEATURE LIMITS ---
// Default limits (Free plan) in case user is not logged in yet
$current_limits = [
    'max_meds' => 3, 
    'max_nominees' => 1, 
    'ai' => false, 
    'escalation' => false
]; 

if (isset($_SESSION['user_id'])) {
    $sub_info = validateSubscription($conn, $_SESSION['user_id']);
    
    // Feature Limits Dictionary
    $feature_limits = [
        'Free' => ['max_meds' => 3, 'max_nominees' => 1, 'ai' => false, 'escalation' => false],
        'Basic' => ['max_meds' => 999, 'max_nominees' => 1, 'ai' => true, 'escalation' => false],
        'Premium' => ['max_meds' => 999, 'max_nominees' => 999, 'ai' => true, 'escalation' => true]
    ];
    
    $current_limits = $feature_limits[$sub_info['plan']];
    $current_page = basename($_SERVER['PHP_SELF']);

    // Enforce Redirect if trial is expired (Only if they aren't already on the subscription page)
    if ($sub_info['status'] === 'expired' && $current_page !== 'subscription.php' && $current_page !== 'logout.php') {
        header("Location: subscription.php?expired=true");
        exit();
    }
}
?>