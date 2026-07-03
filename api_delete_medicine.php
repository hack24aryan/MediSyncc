<?php
session_start();
header('Content-Type: application/json');
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? 1;
    $time_id = (int)$_POST['time_id'];

    // 1. First, find the main medicine_id associated with this specific reminder time
    $stmt = $conn->prepare("SELECT medicine_id FROM medicine_times WHERE id = ?");
    $stmt->bind_param("i", $time_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $med_id = $row['medicine_id'];

        // 2. Delete the medicine (Because of our ON DELETE CASCADE database setup, 
        // this will automatically delete all related times and logs instantly!)
        $del_stmt = $conn->prepare("DELETE FROM user_medicines WHERE id = ? AND user_id = ?");
        $del_stmt->bind_param("ii", $med_id, $user_id);
        
        if ($del_stmt->execute()) {
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Could not delete medicine.']);
}
?>