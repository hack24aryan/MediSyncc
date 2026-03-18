<?php
session_start();
require 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, user_id, nominee_name, password, is_active FROM nominees WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $nominee = $result->fetch_assoc();
        if ($nominee['is_active'] == 1) {
            if (password_verify($password, $nominee['password'])) {
                // Set Secure Caregiver Session
                $_SESSION['nominee_id'] = $nominee['id'];
                $_SESSION['monitored_user_id'] = $nominee['user_id'];
                $_SESSION['nominee_name'] = $nominee['nominee_name'];
                header("Location: nominee_dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Your account access has been paused by the patient.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Caregiver Portal Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #1e293b; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); border: 1px solid #334155; }
        .login-box h2 { color: #0ea5e9; text-align: center; margin-bottom: 5px; }
        .login-box p { text-align: center; color: #94a3b8; font-size: 0.9rem; margin-bottom: 30px; }
        input { width: 100%; padding: 15px; margin-bottom: 20px; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; box-sizing: border-box; outline: none; }
        input:focus { border-color: #0ea5e9; }
        .btn { width: 100%; padding: 15px; background: #0ea5e9; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: 0.3s; }
        .btn:hover { background: #0284c7; }
        .error { color: #ef4444; background: rgba(239,68,68,0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>MediSyncc Caregiver</h2>
        <p>Log in to monitor your loved one's health.</p>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn">Access Portal</button>
        </form>
    </div>
</body>
</html>