<?php
header("Content-Type: application/json");
session_start();
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (isset($data->email) && isset($data->password)) {
    $email = $conn->real_escape_string($data->email);
    
    $sql = "SELECT id, full_name, password FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify the hashed password
        if (password_verify($data->password, $user['password'])) {
            // Set session variables for logged-in state
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            
            echo json_encode(["status" => "success", "message" => "Login Successful!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect password."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Email not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing credentials."]);
}
$conn->close();
?>