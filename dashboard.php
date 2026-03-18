<?php
require_once 'config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- MOCK USER SESSION FOR XAMPP TESTING ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Assuming your test user is ID 1
}
$user_id = $_SESSION['user_id'];

// --- DATABASE CONNECTION ---
$host = 'localhost';
$user = 'root';
$pass = '1234';
$dbname = 'medisyncc_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch User Info
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
$user_name = $user_result ? explode(' ', $user_result['full_name'])[0] : "Patient";
$subscription = "Premium";

// Fetch Today's Medicines & Check if they are taken
$sql = "
    SELECT 
        mt.id as time_id, um.medicine_name as name, um.dosage, um.medicine_image, 
        TIME_FORMAT(mt.reminder_time, '%h:%i %p') as time_formatted,
TIME_FORMAT(mt.reminder_time, '%H:%i') as time24,
        IF(ml.id IS NOT NULL, 'taken', 'pending') as status,
        d.name as doc_name, d.specialty as doc_specialty, d.phone as doc_phone, d.clinic_name as doc_clinic
    FROM user_medicines um
    JOIN medicine_times mt ON um.id = mt.medicine_id
    LEFT JOIN medicine_logs ml ON mt.id = ml.medicine_time_id AND ml.log_date = CURDATE()
    LEFT JOIN user_doctors d ON um.doctor_id = d.id
    WHERE um.user_id = ? AND um.status = 'ACTIVE'
    ORDER BY mt.reminder_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$medicines_array = [];
while ($row = $result->fetch_assoc()) {
    $medicines_array[] = [
        'id' => $row['time_id'],
        'name' => $row['name'], 
        'dosage' => $row['dosage'],
        'image' => $row['medicine_image'], 
        'time' => $row['time_formatted'],
        'time24' => $row['time24'], 
        'status' => $row['status'],
        'doc_name' => $row['doc_name'] ?? null,
        'doc_phone' => $row['doc_phone'] ?? null,
        'doc_clinic' => $row['doc_clinic'] ?? null,
        'doc_specialty' => $row['doc_specialty'] ?? null
    ];
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSyncc | Smart Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* (KEEP ALL YOUR EXACT CSS HERE) */
        :root { --primary: #0f172a; --accent: #0ea5e9; --accent-hover: #0284c7; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --purple: #8b5cf6; --bg: #f4f7fb; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; --border: #e2e8f0; --shadow: 0 10px 30px -10px rgba(0,0,0,0.05); --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        [data-theme="dark"] { --primary: #f8fafc; --bg: #020617; --card-bg: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --shadow: 0 10px 30px -10px rgba(0,0,0,0.5); }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); transition: var(--transition); overflow-x: hidden; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pulse-border { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        @keyframes shake { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-15deg); } 75% { transform: rotate(15deg); } }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .reveal { opacity: 0; animation: slideUp 0.6s ease forwards; } .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
        .navbar { position: sticky; top: 0; z-index: 100; background: rgba(var(--card-bg), 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; transition: var(--transition); }
        .navbar.scrolled { box-shadow: var(--shadow); }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--accent); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; align-items: center; } .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 500; transition: var(--transition); } .nav-links a:hover { color: var(--accent); }
        .nav-actions { display: flex; align-items: center; gap: 15px; position: relative; }
        .icon-btn { background: none; border: none; font-size: 1.2rem; color: var(--text-main); cursor: pointer; transition: var(--transition); position: relative; } .icon-btn:hover { color: var(--accent); transform: scale(1.1); }
        .badge { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%; font-weight: bold; }
        .shaking { animation: shake 0.5s ease infinite; color: var(--accent); }
        .dropdown { position: absolute; top: 40px; right: 40px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; width: 300px; box-shadow: var(--shadow); display: none; flex-direction: column; overflow: hidden; animation: fadeIn 0.3s ease; } .dropdown.active { display: flex; } .dropdown-item { padding: 15px; border-bottom: 1px solid var(--border); font-size: 0.9rem; cursor: pointer; transition: var(--transition); } .dropdown-item:hover { background: rgba(14, 165, 233, 0.05); }
        .dashboard-container { padding: 30px 5%; max-width: 1400px; margin: 0 auto; }
        .dash-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; } .dash-header h1 { font-size: 2rem; color: var(--text-main); } .dash-header p { color: var(--text-muted); font-size: 1.1rem; }
        .date-badge { background: var(--accent); color: white; padding: 8px 15px; border-radius: 20px; font-weight: 500; font-size: 0.9rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--border); display: flex; align-items: center; gap: 20px; transition: var(--transition); } .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px -5px rgba(14, 165, 233, 0.2); }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; } .stat-info h3 { font-size: 2rem; margin: 0; line-height: 1; } .stat-info p { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }
        .bg-blue { background: rgba(14, 165, 233, 0.1); color: var(--accent); } .bg-green { background: rgba(16, 185, 129, 0.1); color: var(--success); } .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); } .bg-purple { background: rgba(139, 92, 246, 0.1); color: var(--purple); }
        .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card { background: var(--card-bg); border-radius: 20px; padding: 25px; box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 30px; } .card-title { font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .table-responsive { overflow-x: auto; } table { width: 100%; border-collapse: collapse; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); } th { color: var(--text-muted); font-weight: 500; font-size: 0.9rem; } tr { transition: var(--transition); } tr:hover { background: rgba(14, 165, 233, 0.02); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; } .status-pending { background: rgba(245, 158, 11, 0.1); color: var(--warning); } .status-taken { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .row-current { border-left: 4px solid var(--accent); background: rgba(14, 165, 233, 0.03); } .row-overdue { border: 2px solid var(--danger); animation: pulse-border 2s infinite; border-radius: 10px; }
        .btn-action { padding: 8px 15px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: var(--transition); display: flex; align-items: center; gap: 5px; } .btn-take { background: var(--accent); color: white; } .btn-take:hover { background: var(--accent-hover); transform: translateY(-2px); } .btn-taken { background: var(--success); color: white; cursor: default; }
        .ai-panel { background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(139, 92, 246, 0.1)); border: 1px solid rgba(14, 165, 233, 0.2); } .progress-bar-container { width: 100%; height: 8px; background: var(--border); border-radius: 4px; margin: 15px 0; overflow: hidden; } .progress-bar { height: 100%; background: linear-gradient(90deg, var(--accent), var(--purple)); width: 0%; transition: width 1s ease-out; } .ai-message { font-size: 0.95rem; display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .reminder-widget { text-align: center; padding: 30px; background: linear-gradient(135deg, var(--primary), #1e293b); color: white; border-radius: 20px; position: relative; overflow: hidden;} .timer-display { font-size: 2.5rem; font-weight: 700; color: var(--accent); margin: 15px 0; font-variant-numeric: tabular-nums; } .reminder-med { font-size: 1.2rem; font-weight: 600; }
        .chart-container { position: relative; height: 250px; width: 100%; display: flex; justify-content: center; }
        .toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; } .toast { background: var(--card-bg); border-left: 4px solid var(--accent); padding: 15px 20px; border-radius: 8px; box-shadow: var(--shadow); animation: slideInRight 0.3s ease forwards; display: flex; align-items: center; gap: 10px; }
        @media (max-width: 1024px) { .main-grid { grid-template-columns: 1fr; } } @media (max-width: 768px) { .nav-links { display: none; } .dash-header { flex-direction: column; align-items: flex-start; gap: 15px; } }
        /* Add this to your existing CSS */
.dropdown {
    position: absolute;
    top: 50px;
    right: 0;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 280px;
    box-shadow: var(--shadow);
    display: none; /* Hidden by default */
    flex-direction: column;
    z-index: 1000;
    animation: fadeIn 0.2s ease-out;
}

.dropdown.active {
    display: flex;
}

.dropdown-item {
    padding: 12px 15px;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.2s;
}

.dropdown-item:hover {
    background: rgba(14, 165, 233, 0.05);
    color: var(--accent);
}

#notif-content {
    max-height: 300px;
    overflow-y: auto;
}

.notif-empty {
    padding: 20px;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.85rem;
}
    </style>
</head>
<body data-theme="light">

    <nav class="navbar" id="navbar">
        <a href="#" class="logo"><i class="fas fa-pills"></i> MediSyncc</a>
        <div class="nav-links">
            <a href="#" style="color: var(--accent);">Dashboard</a>
            <a href="add_medicine.php">Add Medicine</a>
            <a href="nominee.php">Nominee</a>
            <a href="subscription.php">Subscription</a>
        </div>
        <div class="nav-actions">
    <button class="icon-btn" id="sound-toggle" title="Toggle Sound"><i class="fas fa-volume-up"></i></button>
    <button class="icon-btn" id="theme-toggle" title="Dark Mode"><i class="fas fa-moon"></i></button>
    
    <div style="position: relative;">
        <button class="icon-btn" id="bell-icon">
            <i class="fas fa-bell"></i>
            <span class="badge" id="notif-badge">0</span>
        </button>
        <div class="dropdown" id="notif-dropdown">
            <div class="dropdown-header" style="padding:15px; font-weight:600; border-bottom:1px solid var(--border);">Notifications</div>
            <div id="notif-content">
                </div>
        </div>
    </div>

    <div style="position: relative;">
        <button class="icon-btn" id="profile-btn" style="padding:0;">
            <img src="https://ui-avatars.com/api/?name=<?php echo $user_name; ?>&background=0ea5e9&color=fff" alt="Profile" style="width: 35px; border-radius: 50%; border: 2px solid transparent; transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
        </button>
        <div class="dropdown" id="profile-dropdown" style="right: 0; width: 200px;">
            <div class="dropdown-item" onclick="location.href='profile.php'"><i class="fas fa-user-circle"></i> My Profile</div>
            <div class="dropdown-item" onclick="location.href='settings.php'"><i class="fas fa-cog"></i> Settings</div>
            <hr style="border: 0; border-top: 1px solid var(--border);">
            <div class="dropdown-item" style="color: var(--danger);" onclick="location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</div>
        </div>
    </div>
</div>
    </nav>

    <div class="dashboard-container">
        <div class="dash-header reveal">
            <div>
                <h1>Welcome, <?php echo $user_name; ?> 👋</h1>
                <p>Stay consistent. Your health matters.</p>
            </div>
            <div class="date-badge" id="current-date">Loading date...</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card reveal delay-1">
                <div class="stat-icon bg-blue"><i class="fas fa-prescription-bottle-alt"></i></div>
                <div class="stat-info"><h3 id="stat-total">0</h3><p>Total Scheduled</p></div>
            </div>
            <div class="stat-card reveal delay-2">
                <div class="stat-icon bg-green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><h3 id="stat-taken">0</h3><p>Taken Today</p></div>
            </div>
            <div class="stat-card reveal delay-3">
                <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3 id="stat-pending">0</h3><p>Pending</p></div>
            </div>
            <div class="stat-card reveal delay-3">
                <div class="stat-icon bg-purple"><i class="fas fa-user-shield"></i></div>
                <div class="stat-info"><h3>Active</h3><p>Nominee Alert</p></div>
            </div>
        </div>

        <div class="main-grid">
            <div>
               <div class="card ai-panel reveal" style="position: relative; overflow: hidden;">
    
    <?php if (!$current_limits['ai']): ?>
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); backdrop-filter: blur(4px); z-index: 5; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
            <i class="fas fa-lock" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 10px;"></i>
            <h4 style="margin: 0; color: var(--text-main);">AI Insight Locked</h4>
            <p style="font-size: 0.8rem; margin: 5px 0 15px; color: var(--text-muted);">Upgrade to Basic or Premium to unlock AI tracking</p>
            <a href="subscription.php" style="padding: 8px 20px; background: var(--purple); color: white; text-decoration: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">Unlock Now</a>
        </div>
    <?php endif; ?>

    <div class="card-title"><span style="color: var(--purple);"><i class="fas fa-robot"></i> AI Health Insight</span></div>
    <div style="display: flex; justify-content: space-between;">
        <span>Daily Adherence</span>
        <span id="adherence-text" style="font-weight: bold;">0%</span>
    </div>
    <div class="progress-bar-container">
        <div class="progress-bar" id="adherence-bar"></div>
    </div>
    <div class="ai-message" id="ai-message">
        <i class="fas fa-spinner fa-spin" style="color: var(--accent);"></i> Analyzing adherence...
    </div>
</div>

                <div class="card reveal delay-1">
                    <div class="card-title">
                        Today's Schedule
                        <button class="btn-action btn-take" style="font-size: 0.8rem;" onclick="location.href='add_medicine.php'"><i class="fas fa-plus"></i> Add</button>
                    </div>
                    <div class="table-responsive">
                        <table id="med-table">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="med-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <div class="card reminder-widget reveal">
                    <div style="font-size: 0.9rem; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px;">Next Reminder</div>
                    <div class="reminder-med" id="next-med-name">--</div>
                    <div class="timer-display" id="countdown-timer">--:--:--</div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">Starts in</div>
                </div>

                <div class="card reveal delay-1">
                    <div class="card-title">Progress Overview</div>
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <div class="card reveal delay-2" style="background: linear-gradient(45deg, var(--card-bg), rgba(16, 185, 129, 0.05));">
    <div class="card-title">Subscription</div>
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <span style="font-weight: 600;">
            <?php echo $sub_info['plan']; ?> Plan
            <?php if($sub_info['status'] === 'trial'): ?>
                <br><small style="color:var(--accent); font-weight:400;">(<?php echo $sub_info['trial_days_left']; ?> days left)</small>
            <?php endif; ?>
        </span>
        
        <?php if($sub_info['status'] === 'expired'): ?>
            <span class="status-badge" style="background:rgba(239,68,68,0.1); color:var(--danger); border-radius: 20px; padding: 5px 12px; font-size: 0.8rem; font-weight: 600;"><i class="fas fa-times"></i> Expired</span>
        <?php else: ?>
            <span class="status-badge status-taken"><i class="fas fa-check"></i> Active</span>
        <?php endif; ?>
    </div>
    
    <?php if($sub_info['plan'] !== 'Premium' || $sub_info['status'] === 'expired'): ?>
        <a href="subscription.php" style="display:block; margin-top:15px; font-size:0.8rem; color:var(--accent); text-decoration:none; font-weight:600;">Upgrade to Premium <i class="fas fa-arrow-right"></i></a>
    <?php endif; ?>
</div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toast-container"></div>
        <audio id="notify-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        let isAlarmRinging = false;
let currentRingingMedId = null; // Tracks which medicine is causing the noise
const alarmAudio = document.getElementById('notify-sound');
        // ================= DYNAMIC PHP DATA INJECTION =================
        // Here we take the array from the database and feed it directly into JavaScript!
        let medicines = <?php echo json_encode($medicines_array); ?>;

        let soundEnabled = true;
        let chartInstance = null;

        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            updateDate();
            renderTable();
            updateStats();
            initChart();
            startSmartEngine();
         
            
           
           setInterval(checkNextReminder, 1000);

            window.addEventListener('scroll', () => { document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10); });
        });

        const themeBtn = document.getElementById('theme-toggle');
        const soundBtn = document.getElementById('sound-toggle');
        
        function initTheme() {
            if(localStorage.getItem('medisyncc-theme') === 'dark') {
                document.body.setAttribute('data-theme', 'dark');
                themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
            }
        }
        themeBtn.addEventListener('click', () => {
            const isDark = document.body.getAttribute('data-theme') === 'dark';
            document.body.setAttribute('data-theme', isDark ? 'light' : 'dark');
            themeBtn.innerHTML = isDark ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';
            localStorage.setItem('medisyncc-theme', isDark ? 'light' : 'dark');
            if(chartInstance) chartInstance.update();
        });

        soundBtn.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            soundBtn.innerHTML = soundEnabled ? '<i class="fas fa-volume-up"></i>' : '<i class="fas fa-volume-mute"></i>';
            soundBtn.style.color = soundEnabled ? 'var(--text-main)' : 'var(--danger)';
        });

        function updateDate() {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', options);
        }

        function renderTable() {
            const tbody = document.getElementById('med-tbody');
            tbody.innerHTML = '';
            
            if(medicines.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">No medicines scheduled for today. Add some!</td></tr>`;
                return;
            }

            const now = new Date();
            const currentHour = now.getHours();
            const currentMin = now.getMinutes();
            const currentTimeStr = `${currentHour.toString().padStart(2, '0')}:${currentMin.toString().padStart(2, '0')}`;

            medicines.forEach(med => {
                let rowClass = "";
               

                const tr = document.createElement('tr');
                if(rowClass) tr.className = rowClass;
                
                let btnHTML = med.status === 'taken' 
                    ? `<button class="btn-action btn-taken" disabled><i class="fas fa-check"></i> Taken</button>`
                    : `<button class="btn-action btn-take" onclick="markTaken(${med.id}, this)"><i class="fas fa-check-circle"></i> Mark Taken</button>`;
                    btnHTML += `<button onclick="dismissReminder(${med.id}, this)" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); border: none; padding: 10px; border-radius: 8px; cursor: pointer; margin-left: 8px;"><i class="fas fa-times"></i></button>`;
                let statusBadge = med.status === 'taken'
                    ? `<span class="status-badge status-taken">Taken</span>`
                    : `<span class="status-badge status-pending">Pending</span>`;

                // Check if an image exists. If not, show a nice default icon.
                let imageHTML = med.image 
                    ? `<img src="${med.image}" alt="${med.name}" style="width: 45px; height: 45px; border-radius: 10px; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">` 
                    : `<div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(14, 165, 233, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;"><i class="fas fa-pills"></i></div>`;

                // Build the Doctor Badge UI
                let doctorHTML = "";
                if (med.doc_name) {
                    doctorHTML = `
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; background: rgba(14, 165, 233, 0.05); padding: 4px 8px; border-radius: 6px; display: inline-block; border: 1px solid rgba(14, 165, 233, 0.1);">
                            <i class="fas fa-user-md" style="color: var(--accent);"></i> <b>${med.doc_name}</b> (${med.doc_specialty})<br>
                            <span style="opacity: 0.8;"><i class="fas fa-phone-alt"></i> ${med.doc_phone} &nbsp; <i class="fas fa-map-marker-alt"></i> ${med.doc_clinic}</span>
                        </div>
                    `;
                }

                tr.innerHTML = `
                    <td style="font-weight: 500; display: flex; align-items: center; gap: 15px;">
                        ${imageHTML}
                        <div>
                            <span style="font-size: 1.05rem;">${med.name}</span>
                            ${doctorHTML}
                        </div>
                    </td>
                    <td style="vertical-align: middle;">${med.dosage}</td>
                    <td style="font-weight: 600; vertical-align: middle;">${med.time}</td>
                    <td style="vertical-align: middle;">${statusBadge}</td>
                    
<td style="vertical-align: middle; white-space: nowrap; display: flex; align-items: center; gap: 8px;">
    ${btnHTML}
</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function addHours(timeStr, hours) {
            let [h, m] = timeStr.split(':');
            h = (parseInt(h) + hours).toString().padStart(2, '0');
            return `${h}:${m}`;
        }

        // ================= ACTION: MARK TAKEN (REAL DATABASE UPDATE) =================
 
        function markTaken(id, btnElement) {

    // --- ADD THIS STOP LOGIC ---
    if (isAlarmRinging && currentRingingMedId === id) {
        alarmAudio.pause();
        alarmAudio.currentTime = 0; // Reset sound to beginning
        isAlarmRinging = false;
        currentRingingMedId = null;
        document.getElementById('bell-icon').classList.remove('shaking');
    }
            // 1. Instantly update the UI (Optimistic Update)
            const medIndex = medicines.findIndex(m => m.id === id);
            if(medIndex > -1) {
                medicines[medIndex].status = 'taken';
                
                if(soundEnabled) {
                    const audio = document.getElementById('notify-sound');
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log("Audio blocked."));
                }

                btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Saving...`;
                btnElement.disabled = true;

                // 2. Send the request to the database using FormData so PHP can read it
                const formData = new FormData();
                formData.append('time_id', id);

                fetch('api_mark_taken.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Change button to green taken state
                        btnElement.innerHTML = `<i class="fas fa-check"></i> Taken`;
                        btnElement.className = "btn-action btn-taken";
                        
                        const tr = btnElement.closest('tr');
                        tr.className = "";
                        tr.querySelector('.status-badge').className = "status-badge status-taken";
                        tr.querySelector('.status-badge').innerText = "Taken";

                        showToast(`✅ ${medicines[medIndex].name} marked as taken!`);
                        
                        updateStats();
                        updateChartData();
                    } else {
                        alert("Database error: " + data.message);
                        btnElement.disabled = false;
                        btnElement.innerHTML = `<i class="fas fa-check-circle"></i> Mark Taken`;
                    }
                })
                .catch(err => {
                    console.error("Sync failed", err);
                    alert("Network error updating database.");
                    btnElement.disabled = false;
                    btnElement.innerHTML = `<i class="fas fa-check-circle"></i> Mark Taken`;
                });
            }
        }
        function updateStats() {
            const total = medicines.length;
            const taken = medicines.filter(m => m.status === 'taken').length;
            const pending = total - taken;

            document.getElementById('stat-total').innerText = total;
            animateValue("stat-taken", parseInt(document.getElementById('stat-taken').innerText), taken, 500);
            animateValue("stat-pending", parseInt(document.getElementById('stat-pending').innerText), pending, 500);

            // Avoid division by zero if user has no medicines
            const adherence = total > 0 ? Math.round((taken / total) * 100) : 0;
            document.getElementById('adherence-text').innerText = `${adherence}%`;
            document.getElementById('adherence-bar').style.width = `${adherence}%`;

            const aiMsg = document.getElementById('ai-message');
            if(total === 0) {
                aiMsg.innerHTML = `<i class="fas fa-info-circle" style="color: var(--accent);"></i> Add a medicine to start tracking!`;
            } else if (adherence === 100) {
                aiMsg.innerHTML = `<i class="fas fa-star" style="color: var(--success);"></i> Perfect! You've taken all medicines today.`;
            } else if (adherence >= 50) {
                aiMsg.innerHTML = `<i class="fas fa-thumbs-up" style="color: var(--accent);"></i> Doing great! Keep it up.`;
            } else {
                aiMsg.innerHTML = `<i class="fas fa-exclamation-circle" style="color: var(--warning);"></i> You have pending medications. Stay on track!`;
            }
        }

        function animateValue(id, start, end, duration) {
            if (start === end) return;
            const obj = document.getElementById(id);
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        }

        function initChart() {
            const ctx = document.getElementById('progressChart').getContext('2d');
            const taken = medicines.filter(m => m.status === 'taken').length;
            const pending = medicines.length - taken;

            // Give it empty state colors if total is 0
            const dataColors = medicines.length > 0 ? ['#10b981', '#f59e0b'] : ['#e2e8f0'];
            const dataValues = medicines.length > 0 ? [taken, pending] : [1];

            chartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: medicines.length > 0 ? ['Taken', 'Pending'] : ['No Medicines'],
                    datasets: [{
                        data: dataValues,
                        backgroundColor: dataColors,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { color: 'var(--text-main)', font: { family: 'Poppins' } } } } }
            });
        }

        function updateChartData() {
            const taken = medicines.filter(m => m.status === 'taken').length;
            const pending = medicines.length - taken;
            chartInstance.data.datasets[0].data = [taken, pending];
            chartInstance.update();
        }

        function startSmartEngine() {
            setInterval(checkNextReminder, 1000); 
        }

        function checkNextReminder() {
    try {
        const timerDisplay = document.getElementById('countdown-timer');
        const medNameDisplay = document.getElementById('next-med-name');

        if (!medicines || medicines.length === 0) {
            medNameDisplay.innerText = "No medicines scheduled";
            timerDisplay.style.color = "var(--text-muted)";
            timerDisplay.innerText = "--:--:--";
            return;
        }

        const now = new Date();
        let nextMed = null;
        let minDiff = Infinity;
        let overdueMed = null;

        medicines.forEach(med => {
            if (med.status === 'pending' && med.time24) {
                let timeParts = med.time24.split(':');
                let h = parseInt(timeParts[0]);
                let m = parseInt(timeParts[1]);
                
                let medTime = new Date();
                medTime.setHours(h, m, 0, 0);
                
                let diff = medTime - now;

                // --- NEW TRIGGER LOGIC START ---
                // If current time matches medicine time (within 60 seconds)
                // and the alarm isn't already ringing for this medicine.
                if (diff <= 0 && diff > -60000 && !isAlarmRinging) {
                    triggerAlarm(med);
                }
                // --- NEW TRIGGER LOGIC END ---

                if (diff > 0 && diff < minDiff) {
                    minDiff = diff;
                    nextMed = med;
                } else if (diff <= 0) {
                    if (!overdueMed) overdueMed = med;
                }
            }
        });

        // (Rest of your UI update code remains the same...)
        if (nextMed) {
            medNameDisplay.innerText = `${nextMed.name} at ${nextMed.time}`;
            let s = Math.floor((minDiff / 1000) % 60);
            let m = Math.floor((minDiff / 1000 / 60) % 60);
            let h = Math.floor((minDiff / (1000 * 60 * 60)) % 24);
            timerDisplay.style.color = "var(--accent)";
            timerDisplay.innerText = `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        } else if (overdueMed) {
            medNameDisplay.innerText = `${overdueMed.name} was at ${overdueMed.time}`;
            timerDisplay.style.color = "var(--danger)";
            timerDisplay.innerText = "OVERDUE!";
        } else {
            medNameDisplay.innerText = "All done for today!";
            timerDisplay.style.color = "var(--success)";
            timerDisplay.innerText = "--:--:--";
        }
    } catch (error) {
        console.error("Timer crashed! Reason:", error);
    }
}
        function triggerAlarm(med) {
    if (!isAlarmRinging && soundEnabled) {
        isAlarmRinging = true;
        currentRingingMedId = med.id;
        
        // Start the loud looping sound
        alarmAudio.play().catch(e => {
            console.log("Browser blocked autoplay. Click anywhere on the page to enable sound.");
            showToast("⚠️ Click page to enable Alarm Sound!");
        });

        // Visual "Alert Mode"
        document.getElementById('bell-icon').classList.add('shaking');
        showToast(`🚨 URGENT: Time for ${med.name}!`, "danger");
        
        // Optional: Flash the tab title to grab attention
        let originalTitle = document.title;
        let flashInterval = setInterval(() => {
            document.title = document.title === "!!! MEDICINE !!!" ? originalTitle : "!!! MEDICINE !!!";
            if (!isAlarmRinging) {
                clearInterval(flashInterval);
                document.title = originalTitle;
            }
        }, 1000);
    }
}

        function showToast(message) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'fadeIn 0.3s ease reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
        

        async function dismissReminder(reminderId, buttonElement) {
            if (!confirm("Remove this reminder from today's list?")) return;

            const formData = new FormData();
            formData.append('reminder_id', reminderId);

            try {
                const response = await fetch('api_dismiss_reminder.php', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();

                if (result.status === 'success') {
                    // This command forces the entire browser to reload the page
                    window.location.reload(); 
                } else {
                    alert("Database Error: Could not delete.");
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Check if api_dismiss_reminder.php exists!");
            }
        }


        // Add this inside your DOMContentLoaded or at the bottom of your script
const bellBtn = document.getElementById('bell-icon');
const profileBtn = document.getElementById('profile-btn');
const notifDropdown = document.getElementById('notif-dropdown');
const profileDropdown = document.getElementById('profile-dropdown');

// Toggle Notifications
bellBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.remove('active'); // Close other dropdown
    notifDropdown.classList.toggle('active');
    updateNotifications();
});

// Toggle Profile
profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown.classList.remove('active'); // Close other dropdown
    profileDropdown.classList.toggle('active');
});

// Close when clicking anywhere else
window.addEventListener('click', () => {
    notifDropdown.classList.remove('active');
    profileDropdown.classList.remove('active');
});

function updateNotifications() {
    const content = document.getElementById('notif-content');
    const badge = document.getElementById('notif-badge');
    const pendingMeds = medicines.filter(m => m.status === 'pending');
    
    badge.innerText = pendingMeds.length;
    badge.style.display = pendingMeds.length > 0 ? 'block' : 'none';

    if (pendingMeds.length === 0) {
        content.innerHTML = '<div class="notif-empty">No new notifications</div>';
    } else {
        content.innerHTML = pendingMeds.map(m => `
            <div class="dropdown-item" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                <div style="font-weight: 600;">Reminder: ${m.name}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Scheduled for ${m.time}</div>
            </div>
        `).join('');
    }
}

// Run once on load to set the badge count
updateNotifications();
    </script>
</body>
</html>