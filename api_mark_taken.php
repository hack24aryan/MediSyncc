<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

// Catch any stray output that might break JSON
ob_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $time_id = isset($_POST['time_id']) ? (int)$_POST['time_id'] : 0;
    $user_id = $_SESSION['user_id'] ?? 1;

    if ($time_id > 0) {
        // 1. Check for duplicates
        $check = $conn->prepare("SELECT id FROM medicine_logs WHERE medicine_time_id = ? AND log_date = CURDATE()");
        if (!$check) {
            echo json_encode(['status' => 'error', 'message' => 'SQL Error: ' . $conn->error]);
            exit;
        }
        $check->bind_param("i", $time_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            // 2. Insert the log
            $stmt = $conn->prepare("INSERT INTO medicine_logs (user_id, medicine_time_id, log_date, taken_time) VALUES (?, ?, CURDATE(), NOW())");
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Insert Error: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("ii", $user_id, $time_id);
            
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save to database.']);
            }
        } else {
            echo json_encode(['status' => 'success']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No ID received.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

// Ensure nothing else is printed
ob_end_flush();
exit;
?>