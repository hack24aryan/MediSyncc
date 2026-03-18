<?php
session_start();
require 'config.php';

if (!isset($_SESSION['nominee_id'])) {
    header("Location: nominee_login.php");
    exit();
}

$nominee_id = $_SESSION['nominee_id'];
$patient_id = $_SESSION['monitored_user_id'];

// --- HANDLE AJAX ACKNOWLEDGE ALERT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ack_id'])) {
    $aid = (int)$_POST['ack_id'];
    $conn->query("UPDATE notifications SET status = 'ACKNOWLEDGED' WHERE id = $aid AND nominee_id = $nominee_id");
    echo json_encode(['status'=>'success']);
    exit;
}

// Fetch Patient Info
$p_query = $conn->query("SELECT full_name, phone FROM users WHERE id = $patient_id");
$patient = $p_query->fetch_assoc();

// Calculate Live Adherence for Today
$total_q = $conn->query("SELECT COUNT(mt.id) as total FROM medicine_times mt JOIN user_medicines um ON mt.medicine_id = um.id WHERE um.user_id = $patient_id AND um.status = 'ACTIVE'");
$total_doses = $total_q->fetch_assoc()['total'];

$taken_q = $conn->query("SELECT COUNT(id) as taken FROM medicine_logs WHERE user_id = $patient_id AND log_date = CURDATE()");
$taken_doses = $taken_q->fetch_assoc()['taken'];

$adherence = ($total_doses > 0) ? round(($taken_doses / $total_doses) * 100) : 100;

// Fetch Today's Live Schedule
$schedule = $conn->query("
    SELECT um.medicine_name, um.dosage, TIME_FORMAT(mt.reminder_time, '%h:%i %p') as time, 
    IF(ml.id IS NOT NULL, 'Taken', 'Pending') as status
    FROM user_medicines um
    JOIN medicine_times mt ON um.id = mt.medicine_id
    LEFT JOIN medicine_logs ml ON mt.id = ml.medicine_time_id AND ml.log_date = CURDATE()
    WHERE um.user_id = $patient_id AND um.status = 'ACTIVE' ORDER BY mt.reminder_time ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch Active Alerts
$alerts = $conn->query("SELECT * FROM notifications WHERE nominee_id = $nominee_id ORDER BY created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Caregiver Dashboard | MediSyncc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --text: #1e293b; --accent: #0ea5e9; --danger: #ef4444; --success: #10b981; --warning: #f59e0b; }
        body { margin:0; font-family:'Poppins', sans-serif; background:var(--bg); color:var(--text); }
        .nav { background: #1e293b; color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 5%; display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card { background: var(--card); padding: 25px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-box { background: rgba(14,165,233,0.1); padding: 20px; border-radius: 15px; text-align: center; border: 1px solid rgba(14,165,233,0.2); }
        .stat-box.danger { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.2); color: var(--danger); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
        .alert-item { padding: 15px; border-left: 4px solid var(--danger); background: rgba(239,68,68,0.05); margin-bottom: 10px; border-radius: 0 10px 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .alert-item.ack { border-color: var(--success); background: rgba(16,185,129,0.05); opacity: 0.7; }
        .btn-ack { background: var(--success); color: white; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 0.8rem; }
    </style>
</head>
<body>

    <div class="nav">
        <div style="font-size: 1.2rem; font-weight: 700;"><i class="fas fa-shield-alt" style="color:var(--accent);"></i> Caregiver Portal</div>
        <div style="font-size: 0.9rem;">
            Welcome, <?php echo htmlspecialchars($_SESSION['nominee_name']); ?> | 
            <a href="logout.php" style="color:#fca5a5; text-decoration:none; margin-left:15px;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div>
            <div class="card">
                <h3 style="margin-top:0; color:var(--accent);">Patient Overview: <?php echo htmlspecialchars($patient['full_name']); ?></h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <div class="stat-box <?php echo $adherence < 70 ? 'danger' : ''; ?>">
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo $adherence; ?>%</div>
                        <div style="font-size: 0.9rem; font-weight: 600;">Today's Adherence</div>
                    </div>
                    <div class="stat-box" style="background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.2); color: var(--success);">
                        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo $taken_doses; ?> / <?php echo $total_doses; ?></div>
                        <div style="font-size: 0.9rem; font-weight: 600;">Doses Taken</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0;"><i class="fas fa-list"></i> Today's Schedule</h3>
                <table>
                    <thead><tr><th>Medicine</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($schedule as $s): ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($s['medicine_name']); ?></b> <br><small><?php echo $s['dosage']; ?></small></td>
                            <td><?php echo $s['time']; ?></td>
                            <td>
                                <?php if($s['status'] == 'Taken'): ?>
                                    <span class="badge" style="background:#d1fae5; color:#059669;">Taken</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#fef3c7; color:#d97706;">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <div class="card">
                <h3 style="margin-top:0; color:var(--danger);"><i class="fas fa-exclamation-circle"></i> Live Alerts</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">Auto-updates every 30s</p>
                
                <div id="alerts-container">
                    <?php if(empty($alerts)): ?>
                        <div style="text-align:center; padding: 20px; color:var(--success);"><i class="fas fa-check-circle" style="font-size: 2rem;"></i><br>All good! No recent alerts.</div>
                    <?php else: foreach($alerts as $a): ?>
                        <div class="alert-item <?php echo $a['status'] == 'ACKNOWLEDGED' ? 'ack' : ''; ?>" id="alert-<?php echo $a['id']; ?>">
                            <div>
                                <div style="font-size:0.85rem; font-weight:600;"><?php echo htmlspecialchars($a['message']); ?></div>
                                <div style="font-size:0.75rem; opacity:0.8; margin-top:4px;"><?php echo date('h:i A', strtotime($a['created_at'])); ?></div>
                            </div>
                            <?php if($a['status'] == 'SENT'): ?>
                                <button class="btn-ack" onclick="ackAlert(<?php echo $a['id']; ?>)">Ack</button>
                            <?php else: ?>
                                <i class="fas fa-check" style="color:var(--success);"></i>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function ackAlert(id) {
            const fd = new FormData();
            fd.append('ack_id', id);
            await fetch('nominee_dashboard.php', { method: 'POST', body: fd });
            
            const item = document.getElementById('alert-' + id);
            item.classList.add('ack');
            item.querySelector('.btn-ack').outerHTML = '<i class="fas fa-check" style="color:var(--success);"></i>';
        }

        // Auto-refresh the page gently every 30 seconds to catch new alerts without manual reload
        setInterval(() => {
            location.reload(); 
            // In a larger app, you would use fetch() here to only reload the #alerts-container HTML.
        }, 30000); 
    </script>
</body>
</html>