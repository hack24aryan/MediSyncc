<?php
header("Content-Type: application/json");
// 1. Connection settings
$host = 'localhost';
$user = 'root';
$pass = '1234'; 
$dbname = 'medisyncc_db';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// 2. Get Input
$data = json_decode(file_get_contents("php://input"));

// 3. Validation Check
if (isset($data->name, $data->email, $data->password, $data->phone, $data->gender)) {
    $name = trim($data->name);
    $email = trim($data->email);
    $phone = trim($data->phone);
    $gender = trim($data->gender);
    $pass = password_hash($data->password, PASSWORD_BCRYPT);

    // 4. Check if user exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered!"]);
        exit;
    }

    // 5. Insert including Phone and Gender
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone_number, gender, password, trial_start_date, subscription_status) VALUES (?, ?, ?, ?, ?, NOW(), 'trial')");
    $stmt->bind_param("sssss", $name, $email, $phone, $gender, $pass);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Account Created!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Execution failed: " . $stmt->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data fields."]);
}
$conn->close();
?>