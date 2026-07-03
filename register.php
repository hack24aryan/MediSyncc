<?php
include 'db.php';

// Get the data from the JavaScript Fetch request
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $full_name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT); // Securely encrypt password

    // Check if email already exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already exists!"]);
    } else {
        $sql = "INSERT INTO users (full_name, email, password) VALUES ('$full_name', '$email', '$password')";
        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Account created successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed."]);
        }
    }
}
?>