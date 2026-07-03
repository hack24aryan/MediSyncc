<?php
session_start();
require 'config.php'; 
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reminder_id = isset($_POST['reminder_id']) ? (int)$_POST['reminder_id'] : 0;
    $user_id = $_SESSION['user_id'] ?? 1;

    if ($reminder_id > 0) {
        // 1. Find the medicine_id linked to this specific reminder
        $stmt_find = $conn->prepare("SELECT medicine_id FROM medicine_times WHERE id = ?");
        $stmt_find->bind_param("i", $reminder_id);
        $stmt_find->execute();
        $result = $stmt_find->get_result()->fetch_assoc();

        if ($result) {
            $medicine_id = $result['medicine_id'];

            // 2. Delete everything! 
            // We delete the parent (user_medicines). 
            // This will remove all times and logs associated with it.
            
            // First, manually clear logs and times to avoid foreign key conflicts
            $conn->query("DELETE FROM medicine_logs WHERE medicine_time_id IN (SELECT id FROM medicine_times WHERE medicine_id = $medicine_id)");
            $conn->query("DELETE FROM medicine_times WHERE medicine_id = $medicine_id");

            // Now delete the main medicine entry
            $stmt_del = $conn->prepare("DELETE FROM user_medicines WHERE id = ? AND user_id = ?");
            $stmt_del->bind_param("ii", $medicine_id, $user_id);

            if ($stmt_del->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete medicine record']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Medicine not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
    exit;
}
?>