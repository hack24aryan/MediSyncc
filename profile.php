<?php
require 'config.php';
// checkLogin(); // Uncomment after implementing login.php


// Mock data if session isn't set for testing
if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }
$user_id = $_SESSION['user_id'];

// --- HANDLE PASSWORD UPDATE ---
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Fetch current hashed password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    // 2. Verify current password
    if (password_verify($current_pass, $res['password'])) {
        if ($new_pass === $confirm_pass) {
            // 3. Hash and Save new password
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $hashed_pass, $user_id);
            
            if ($update->execute()) {
                $message = "Password changed successfully!";
                $conn->query("INSERT INTO activity_logs (user_id, activity, status) VALUES ($user_id, 'Password Changed', 'Success')");
            }
        } else {
            $message = "New passwords do not match!";
        }
    } else {
        $message = "Current password is incorrect!";
    }
}

// --- HANDLE POST UPDATES ---
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = $_POST['full_name'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        
        $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, gender=? WHERE id=?");
        $stmt->bind_param("sssi", $fullname, $phone, $gender, $user_id);
        if ($stmt->execute()) {
            $message = "Profile updated successfully!";
            $conn->query("INSERT INTO activity_logs (user_id, activity, status) VALUES ($user_id, 'Profile Updated', 'Success')");
        }
    }
}

// Fetch User Data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile | MediSyncc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a; --accent: #0ea5e9; --success: #10b981; --danger: #ef4444;
            --bg: #f8fafc; --card: #ffffff; --border: #e2e8f0; --text: #1e293b;
        }
        * { margin:0; padding:0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text); }

        /* Sidebar & Layout */
        .container { display: grid; grid-template-columns: 350px 1fr; gap: 30px; padding: 40px 5%; max-width: 1400px; margin: 0 auto; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Left Card */
        .profile-card { background: var(--card); border-radius: 20px; padding: 30px; border: 1px solid var(--border); text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 20px; }
        .avatar-wrapper img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent); }
        .upload-btn { position: absolute; bottom: 0; right: 0; background: var(--accent); color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid var(--card); }
        
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-top: 10px; }
        .badge-premium { background: rgba(14, 165, 233, 0.1); color: var(--accent); }

        /* Right Tabs */
        .settings-panel { background: var(--card); border-radius: 20px; border: 1px solid var(--border); overflow: hidden; }
        .tabs-header { display: flex; background: #f1f5f9; border-bottom: 1px solid var(--border); }
        .tab-btn { padding: 20px 25px; cursor: pointer; font-weight: 500; color: var(--text); border: none; background: none; transition: 0.3s; font-size: 0.9rem; }
        .tab-btn.active { background: var(--card); color: var(--accent); border-bottom: 3px solid var(--accent); }
        
        .tab-content { padding: 40px; display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text); }
        input, select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); outline: none; transition: 0.3s; }
        input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }

        .btn-save { background: var(--accent); color: white; padding: 12px 30px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: #0284c7; transform: translateY(-2px); }

        /* Toggle Switches */
        .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid var(--border); }
        .switch { position: relative; display: inline-block; width: 45px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(21px); }

        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<nav style="background: var(--card); border-bottom: 1px solid var(--border); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;">
    <a href="dashboard.php" style="text-decoration: none; font-size: 1.5rem; font-weight: 700; color: var(--accent);">
        <i class="fas fa-pills"></i> MediSyncc
    </a>
    <a href="dashboard.php" style="text-decoration: none; color: var(--text); font-weight: 500; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; transition: 0.3s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text)'">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</nav>
<div class="container">
    <div style="grid-column: 1 / -1; margin-bottom: -10px; animation: fadeIn 0.5s ease;">
    <a href="dashboard.php" style="text-decoration: none; color: var(--accent); font-size: 0.85rem; font-weight: 500;">
        Dashboard
    </a> 
    <span style="color: var(--border); margin: 0 10px;">/</span> 
    <span style="color: var(--text-muted); font-size: 0.85rem;">Account Settings</span>
</div>
    <div class="profile-card">
    <div class="avatar-wrapper">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name'] ?? 'User'); ?>&background=0ea5e9&color=fff">
    </div>
    <h2><?php echo htmlspecialchars($user['full_name'] ?? 'New User'); ?></h2>
    <p style="color: gray;"><?php echo htmlspecialchars($user['email'] ?? 'No Email'); ?></p>
    <span class="badge badge-premium"><?php echo htmlspecialchars($user['plan_type'] ?? 'Free'); ?> Member</span>
    
    <div style="margin-top: 30px; text-align: left; font-size: 0.85rem;">
        <div class="toggle-row"><span>Phone</span><b><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></b></div>
        <div class="toggle-row"><span>Joined</span><b><?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'Recent'; ?></b></div>
    </div>
</div>

    <div class="settings-panel">
        <div class="tabs-header">
            <button class="tab-btn active" onclick="openTab(event, 'personal')">Personal Info</button>
            <button class="tab-btn" onclick="openTab(event, 'security')">Security</button>
            <button class="tab-btn" onclick="openTab(event, 'notifs')">Notifications</button>
            <button class="tab-btn" onclick="openTab(event, 'activity')">Activity</button>
        </div>

        <div id="personal" class="tab-content active">
            <h3>Personal Information</h3>
            <p style="color: gray; font-size: 0.85rem; margin-bottom: 25px;">Update your basic details to keep your medical records accurate.</p>
            <form action="" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#f1f5f9">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>
                <div class="form-group">
    <label>Gender</label>
    <?php $current_gender = $user['gender'] ?? 'Male'; ?>
    <select name="gender">
        <option value="Male" <?php echo ($current_gender == 'Male') ? 'selected' : ''; ?>>Male</option>
        <option value="Female" <?php echo ($current_gender == 'Female') ? 'selected' : ''; ?>>Female</option>
        <option value="Other" <?php echo ($current_gender == 'Other') ? 'selected' : ''; ?>>Other</option>
    </select>
</div>
                <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
            </form>
        </div>

       <div id="security" class="tab-content">
    <h3>Security Settings</h3>
    
    <?php if($message): ?>
        <div style="padding:10px; background:rgba(16,185,129,0.1); color:var(--success); border-radius:8px; margin-bottom:15px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group" style="margin-top:20px;">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="••••••••" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" id="new-pass" placeholder="Enter new password" required>
            <div id="strength" style="height:4px; width:0%; background:var(--danger); margin-top:5px; transition:0.3s"></div>
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
        </div>
        
        <button type="submit" name="change_password" class="btn-save" style="margin-bottom: 20px;">Update Password</button>
    </form>

    <div class="toggle-row">
        <div>
            <b>Two-Factor Authentication</b>
            <p style="font-size:0.75rem; color:gray">Secure your account with 2FA.</p>
        </div>
        <label class="switch">
            <input type="checkbox" <?php echo ($user['two_factor_enabled'] ?? 0) ? 'checked' : ''; ?>>
            <span class="slider"></span>
        </label>
    </div>
</div>

        <div id="notifs" class="tab-content">
            <h3>Notification Preferences</h3>
            <div class="toggle-row"><span>Email Notifications</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
            <div class="toggle-row"><span>SMS Reminders</span><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
            <div class="toggle-row"><span>Sound Alerts</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
        </div>

        <div id="activity" class="tab-content">
            <h3>Recent Activity</h3>
            <table style="width:100%; border-collapse: collapse; margin-top:20px;">
                <thead><tr style="text-align:left; font-size:0.8rem; color:gray"><th style="padding:10px">Date</th><th>Activity</th><th>Status</th></tr></thead>
                <tbody>
                    <?php
                    $logs = $conn->query("SELECT * FROM activity_logs WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
                    while($row = $logs->fetch_assoc()):
                    ?>
                    <tr style="font-size:0.9rem; border-top: 1px solid var(--border)">
                        <td style="padding:15px"><?php echo date('d M', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['activity']; ?></td>
                        <td><span style="color:var(--success)">● <?php echo $row['status']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        let content = document.getElementsByClassName("tab-content");
        for (let i = 0; i < content.length; i++) content[i].style.display = "none";
        
        let btns = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < btns.length; i++) btns[i].className = btns[i].className.replace(" active", "");
        
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    // Password Strength Logic
    document.getElementById('new-pass').addEventListener('input', function() {
        let val = this.value;
        let bar = document.getElementById('strength');
        if(val.length > 8) { bar.style.width = '100%'; bar.style.background = 'var(--success)'; }
        else if(val.length > 4) { bar.style.width = '50%'; bar.style.background = 'var(--warning)'; }
        else { bar.style.width = '20%'; bar.style.background = 'var(--danger)'; }
    });
</script>

</body>
</html>