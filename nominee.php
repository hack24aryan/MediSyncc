<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- HANDLE AJAX REQUESTS (Add & Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['nominee_name']);
        $rel = $_POST['relationship'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $conf = $_POST['confirm_password'];
        
        if ($pass !== $conf) { echo json_encode(['status'=>'error', 'message'=>'Passwords do not match.']); exit; }
        
        // Duplicate Check
        $chk = $conn->prepare("SELECT id FROM nominees WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) { echo json_encode(['status'=>'error', 'message'=>'Email already registered.']); exit; }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pref = $_POST['pref'];
        $sens = $_POST['sens'];
        $r1 = isset($_POST['r_once']) ? 1 : 0;
        $r2 = isset($_POST['r_twice']) ? 1 : 0;
        $r3 = isset($_POST['r_high']) ? 1 : 0;
        $r4 = isset($_POST['r_adh']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO nominees (user_id, nominee_name, relationship, email, phone, password, notification_preference, alert_sensitivity, alert_rule_once, alert_rule_twice, alert_rule_high_risk, alert_rule_adherence, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssiiiii", $user_id, $name, $rel, $email, $phone, $hash, $pref, $sens, $r1, $r2, $r3, $r4, $active);
        
        if ($stmt->execute()) echo json_encode(['status'=>'success', 'message'=>'Nominee account created successfully!']);
        else echo json_encode(['status'=>'error', 'message'=>'Database error.']);
        exit;
    }
    
    if ($_POST['action'] === 'delete') {
        $nid = (int)$_POST['nominee_id'];
        $stmt = $conn->prepare("DELETE FROM nominees WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $nid, $user_id);
        if($stmt->execute()) echo json_encode(['status'=>'success']);
        else echo json_encode(['status'=>'error']);
        exit;
    }
}

// Fetch Active Nominees
$nom_query = $conn->query("SELECT * FROM nominees WHERE user_id = $user_id ORDER BY created_at DESC");
$nominees = $nom_query->fetch_all(MYSQLI_ASSOC);

// Fetch User Name for Navbar
$usr = $conn->query("SELECT full_name FROM users WHERE id = $user_id")->fetch_assoc();
$user_name = explode(' ', $usr['full_name'])[0] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Nominees | MediSyncc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #f4f7fb; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --border: #e2e8f0; --accent: #0ea5e9; --danger: #ef4444; --success: #10b981; --shadow: 0 10px 30px -10px rgba(0,0,0,0.05); }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text-main); }
        .container { max-width: 1400px; margin: 40px auto; padding: 0 5%; display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        .card { background: var(--card-bg); padding: 30px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--border); animation: slideUp 0.5s ease; }
        .card-header { font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; color: var(--accent); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full { grid-column: span 2; }
        label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 5px; color: var(--text-muted); }
        input, select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; outline: none; transition: 0.3s; }
        input:focus, select:focus { border-color: var(--accent); }
        .btn { padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; color: white; background: var(--accent); width: 100%; transition: 0.3s; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .nominee-card { border: 1px solid var(--border); padding: 20px; border-radius: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
        .nominee-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); border-color: var(--accent); }
        .n-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 20px; background: rgba(16,185,129,0.1); color: var(--success); font-weight: bold; }
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); border-radius: 34px; transition: .4s; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: .4s; }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(20px); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 900px) { .container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-shield"></i> Add Caregiver / Nominee</div>
            <form id="nomineeForm">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="form-group"><label>Nominee Name</label><input type="text" name="nominee_name" required></div>
                    <div class="form-group"><label>Relationship</label>
                        <select name="relationship">
                            <option>Spouse</option><option>Parent</option><option>Child</option><option>Friend</option><option>Nurse</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Email Address (Login ID)</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="tel" name="phone" required></div>
                    <div class="form-group"><label>Set Password</label><input type="password" name="password" required></div>
                    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
                </div>

                <div class="card-header" style="margin-top: 20px; font-size: 1rem;"><i class="fas fa-bell"></i> Alert Rules & Permissions</div>
                <div class="form-grid">
                    <div class="form-group"><label>Notification Method</label>
                        <select name="pref"><option value="In-app">In-app Dashboard</option><option value="Email">Email + In-app</option></select>
                    </div>
                    <div class="form-group"><label>Sensitivity</label>
                        <select name="sens"><option value="All">All Missed Meds</option><option value="High-Risk">High-Risk Only</option></select>
                    </div>
                </div>

                <div style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border);">
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:8px;"><input type="checkbox" name="r_once" style="width:auto;"> Notify if missed once</label>
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:8px;"><input type="checkbox" name="r_twice" checked style="width:auto;"> Notify if missed twice consecutively</label>
                    <label style="display:flex; align-items:center; gap:10px; margin-bottom:8px;"><input type="checkbox" name="r_high" checked style="width:auto;"> Notify immediately on High-Risk meds</label>
                    <label style="display:flex; align-items:center; gap:10px;"><input type="checkbox" name="r_adh" checked style="width:auto;"> Notify if adherence drops below 70%</label>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <span style="font-weight: 500;">Activate Account Immediately</span>
                    <label class="switch"><input type="checkbox" name="is_active" checked><span class="slider"></span></label>
                </div>

                <button type="submit" class="btn" id="saveBtn">Create Nominee Account</button>
            </form>
        </div>

        <div>
            <div class="card">
                <div class="card-header" style="display:flex; justify-content:space-between;">
                    <span><i class="fas fa-users"></i> Active Nominees (<?php echo count($nominees); ?>)</span>
                    <a href="nominee_login.php" target="_blank" style="font-size:0.8rem; color:var(--accent); text-decoration:none;"><i class="fas fa-external-link-alt"></i> Portal Login</a>
                </div>
                
                <?php if(empty($nominees)): ?>
                    <div style="text-align:center; padding: 40px 20px; color:var(--text-muted);">
                        <i class="fas fa-user-slash" style="font-size: 3rem; opacity: 0.2; margin-bottom:10px;"></i>
                        <p>No caregivers added yet.</p>
                    </div>
                <?php else: foreach($nominees as $n): ?>
                    <div class="nominee-card">
                        <div>
                            <div style="font-weight: 600; font-size: 1.05rem;">
                                <?php echo htmlspecialchars($n['nominee_name']); ?> 
                                <?php if($n['is_active']) echo '<span class="n-badge">Active</span>'; else echo '<span class="n-badge" style="background:#fee2e2; color:#ef4444;">Paused</span>'; ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-user-tag"></i> <?php echo $n['relationship']; ?> | <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($n['email']); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
                                Monitoring since: <?php echo date('d M Y', strtotime($n['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <button onclick="deleteNominee(<?php echo $n['id']; ?>)" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:1.2rem; padding:10px;" title="Remove Nominee"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('nomineeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            btn.innerHTML = 'Saving...'; btn.disabled = true;

            try {
                const res = await fetch('nominee.php', { method: 'POST', body: new FormData(e.target) });
                const data = await res.json();
                alert(data.message);
                if(data.status === 'success') location.reload();
            } catch(err) {
                alert('Network Error.');
            }
            btn.innerHTML = 'Create Nominee Account'; btn.disabled = false;
        });

        async function deleteNominee(id) {
            if(!confirm("Revoke access for this nominee? They will no longer be able to log in.")) return;
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('nominee_id', id);
            await fetch('nominee.php', { method: 'POST', body: fd });
            location.reload();
        }
    </script>
</body>
</html>