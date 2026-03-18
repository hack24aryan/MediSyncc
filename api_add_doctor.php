<?php
session_start();
header('Content-Type: application/json');

// 1. Connect using your universal config file!
require 'config.php';

// 2. Check if connection was successful
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// 3. Get the data
$user_id = $_SESSION['user_id'] ?? 1;
$name = $_POST['doc_name'] ?? '';
$specialty = $_POST['doc_specialty'] ?? '';
$phone = $_POST['doc_phone'] ?? '';
$clinic = $_POST['doc_clinic'] ?? '';

// 4. Save to database
$stmt = $conn->prepare("INSERT INTO user_doctors (user_id, name, specialty, phone, clinic_name) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $user_id, $name, $specialty, $phone, $clinic);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'name' => $name, 'specialty' => $specialty]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}
?>