<?php
// 1. MUST BE FIRST: Load the system brain (Calculates trial/plan limits)
require_once 'config.php'; 

// 2. Session & User Security
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 3. Database Connection
$host = 'localhost';
$user = 'root';
$pass = '1234'; 
$dbname = 'medisyncc_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

// 4. Fetch User Info for Navbar
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
$user_name = $user_result ? explode(' ', $user_result['full_name'])[0] : "Patient";

// 5. Fetch User's Saved Doctors
$doc_query = $conn->query("SELECT * FROM user_doctors WHERE user_id = $user_id ORDER BY name ASC");
$my_doctors = $doc_query->fetch_all(MYSQLI_ASSOC);


// ==========================================
// 🚨 SUBSCRIPTION LIMIT CHECK
// ==========================================

// Ensure variables from config.php are available
if (isset($current_limits) && isset($sub_info)) {
    
    $med_count_result = $conn->query("SELECT COUNT(*) as total FROM user_medicines WHERE user_id = $user_id AND status = 'ACTIVE'");
    $current_med_count = $med_count_result->fetch_assoc()['total'];

    if ($current_med_count >= $current_limits['max_meds']) {
        
        // AJAX Response
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error', 
                'message' => "Limit Reached: Your {$sub_info['plan']} plan only allows {$current_limits['max_meds']} medicines. Please upgrade!"
            ]);
            exit;
        }
        
        // Page View Response
        include 'navbar.php'; 
        ?>
        <div style="text-align:center; padding: 100px 5%; font-family: 'Poppins', sans-serif; background: #f8fafc; min-height: 100vh;">
            <i class="fas fa-lock" style="font-size: 5rem; color: #ef4444; margin-bottom: 20px;"></i>
            <h2 style="font-size: 2rem; color: #1e293b;">Medicine Limit Reached</h2>
            <p style="color: #64748b; margin-bottom: 30px;">
                You are currently on the <b><?php echo $sub_info['plan']; ?> Plan</b>, which allows only <?php echo $current_limits['max_meds']; ?> medicines.
            </p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <a href="subscription.php" style="text-decoration:none; padding: 12px 25px; border-radius: 10px; background: #0ea5e9; color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">Upgrade Now</a>
                <a href="dashboard.php" style="text-decoration:none; padding: 12px 25px; color: #64748b; font-weight: 500;">Back to Dashboard</a>
            </div>
        </div>
        <?php
        exit; 
    }
}
// ==========================================

// --- HANDLE AJAX FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Sanitize Inputs
    $medicine_name = trim($_POST['medicine_name'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $type = $_POST['type'] ?? '';
    $purpose = trim($_POST['purpose'] ?? '');
    $frequency = $_POST['frequency'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    $repeat_hours = !empty($_POST['repeat_hours']) ? (int)$_POST['repeat_hours'] : NULL;
    $snooze_duration = (int)($_POST['snooze_duration'] ?? 5);
    $food_instruction = $_POST['food_instruction'] ?? '';
    $doctor_id = !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : NULL;
    $doctor_note = trim($_POST['doctor_note'] ?? '');
    $notify_nominee = isset($_POST['notify_nominee']) ? 'YES' : 'NO';
    $times = $_POST['times'] ?? [];
    

    // --- 1. STRICT DATE VALIDATION ---
    $today = date('Y-m-d');
    if ($start_date < $today) {
        echo json_encode(['status' => 'error', 'message' => 'Start date cannot be in the past.']);
        exit;
    }
    if ($end_date !== NULL && $end_date < $start_date) {
        echo json_encode(['status' => 'error', 'message' => 'End date cannot be earlier than the start date.']);
        exit;
    }

    // --- 2. DUPLICATE MEDICINE CHECK ---
    $stmt = $conn->prepare("SELECT id FROM user_medicines WHERE user_id = ? AND medicine_name = ? AND status = 'ACTIVE'");
    $stmt->bind_param("is", $user_id, $medicine_name);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You already have an active schedule for this medicine.']);
        exit;
    }

    // --- 3. SECURE FILE UPLOAD HANDLER ---
    function uploadImage($fileKey, $allowed_ext = ['jpg', 'jpeg', 'png'], $max_size = 5242880) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === 0) {
            if ($_FILES[$fileKey]['size'] > $max_size) return ['error' => 'File too large (Max 5MB)'];
            $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) return ['error' => 'Invalid file type. Only JPG/PNG allowed.'];
            
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $filename = $upload_dir . uniqid('img_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $filename)) return ['path' => $filename];
        }
        return ['path' => null];
    }

    $med_img = uploadImage('medicine_photo');
    if (isset($med_img['error'])) { echo json_encode(['status' => 'error', 'message' => $med_img['error']]); exit; }

    // --- 4. INSERT MEDICINE RECORD ---
    // Check if this medicine required a prescription
    $requires_rx = (!empty($_POST['rx_doctor_name']) && !empty($_POST['rx_date']));
    $status = $requires_rx ? 'INACTIVE' : 'ACTIVE';

    // The new Insert Statement (with doctor_id added)
    $stmt = $conn->prepare("INSERT INTO user_medicines (user_id, medicine_name, dosage, type, purpose, doctor_id, medicine_image, frequency, start_date, end_date, repeat_hours, snooze_duration, food_instruction, doctor_note, notify_nominee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssissssiissss", $user_id, $medicine_name, $dosage, $type, $purpose, $doctor_id, $med_img['path'], $frequency, $start_date, $end_date, $repeat_hours, $snooze_duration, $food_instruction, $doctor_note, $notify_nominee, $status);
    $stmt->execute();
    $medicine_id = $conn->insert_id;
    

    // --- 5. INSERT REMINDER TIMES ---
    $time_stmt = $conn->prepare("INSERT INTO medicine_times (medicine_id, reminder_time) VALUES (?, ?)");
    foreach ($times as $time) {
        if (!empty($time)) {
            $time_stmt->bind_param("is", $medicine_id, $time);
            $time_stmt->execute();
        }
    }

    // --- 6. PRESCRIPTION HANDLING ---
    if (!empty($_POST['rx_doctor_name']) && !empty($_POST['rx_date'])) {
        if (strtotime($_POST['rx_date']) < strtotime('-6 months')) {
            echo json_encode(['status' => 'error', 'message' => 'Prescription is older than 6 months. Rejected.']);
            exit;
        }

        $rx_img = uploadImage('prescription_image');
        if (isset($rx_img['error'])) { echo json_encode(['status' => 'error', 'message' => $rx_img['error']]); exit; }
        
        if ($rx_img['path']) {
            $rx_stmt = $conn->prepare("INSERT INTO prescriptions (user_id, medicine_name, doctor_name, prescription_date, prescription_image, verified_status) VALUES (?, ?, ?, ?, ?, 'PENDING')");
            $rx_stmt->bind_param("issss", $user_id, $medicine_name, $_POST['rx_doctor_name'], $_POST['rx_date'], $rx_img['path']);
            $rx_stmt->execute();
        }
    }

    // --- 7. AI ADHERENCE SIMULATION ---
    $simulated_taken = rand(7, 10); // Mock past 10 doses
    $adherence_rate = ($simulated_taken / 10) * 100;
    
    if ($adherence_rate < 80) {
        $ai_insight = "⚠ AI Insight: You are missing doses frequently. Let's build a better habit with $medicine_name.";
    } else {
        $ai_insight = "💡 AI Adherence Prediction: Excellent! You have a strong consistency score.";
    }

    // Simulate Nominee Setup Notification
    if ($notify_nominee === 'YES') {
        $conn->query("INSERT INTO notifications (user_id, message) VALUES ($user_id, 'Nominee monitoring activated for $medicine_name.')");
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Medicine added successfully!',
        'ai_insight' => $ai_insight
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medicine | MediSyncc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ================= CSS VARIABLES & THEME ================= */
        :root {
            --primary: #0f172a; --accent: #0ea5e9; --accent-hover: #0284c7;
            --bg: #f4f7fb; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b;
            --border: #e2e8f0; --shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            --primary: #f8fafc; --bg: #020617; --card-bg: #1e293b;
            --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text-main); transition: var(--transition); }

        /* --- Remove Time Button --- */
        .btn-remove-time {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: none;
            border-radius: 10px;
            width: 45px;
            height: 45px; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-remove-time:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        /* --- Layout --- */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 5%; display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card { background: var(--card-bg); border-radius: 20px; padding: 30px; box-shadow: var(--shadow); border: 1px solid var(--border); animation: slideUp 0.6s ease; }
        .card-header { font-size: 1.2rem; font-weight: 600; margin-bottom: 20px; border-bottom: 2px solid var(--border); padding-bottom: 10px; color: var(--accent); }

        /* --- Forms --- */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-size: 0.9rem; font-weight: 500; margin-bottom: 8px; color: var(--text-main); }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid var(--border); border-radius: 10px; background: var(--bg); color: var(--text-main); font-size: 0.95rem; transition: var(--transition); outline: none; }
        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }

        /* --- Buttons --- */
        .btn { padding: 12px 25px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: var(--transition); font-size: 1rem; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--accent); color: white; width: 100%; position: relative; overflow: hidden; }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3); }
        .btn-outline { border: 2px solid var(--accent); color: var(--accent); background: transparent; padding: 8px 15px; font-size: 0.85rem; }
        .btn-outline:hover { background: var(--accent); color: white; }

        /* --- Toggle Switch --- */
        .toggle-container { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: var(--bg); border-radius: 10px; border: 1px solid var(--border); }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent); }
        input:checked + .slider:before { transform: translateX(24px); }

        /* --- Preview Panel --- */
        .preview-card { background: linear-gradient(135deg, var(--primary), #1e293b); color: white; border-radius: 20px; padding: 30px; position: sticky; top: 100px; }
        .preview-img-box { width: 100%; height: 150px; background: rgba(255,255,255,0.1); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; overflow: hidden; border: 2px dashed rgba(255,255,255,0.3); }
        .preview-img-box img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .preview-detail { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 0.95rem; }
        .preview-detail i { color: var(--accent); width: 20px; }

        /* --- Smart Alerts --- */
        .smart-alert { display: none; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 500; animation: fade-in 0.4s ease; }
        .alert-info { background: rgba(14, 165, 233, 0.1); color: var(--accent); border: 1px solid rgba(14, 165, 233, 0.2); }
        .alert-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }

        /* --- Modals --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); animation: slideUp 0.4s ease; }

        /* --- Animations --- */
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .spinner { display: none; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 3px solid white; width: 20px; height: 20px; animation: spin 1s linear infinite; }

        /* --- Responsive --- */
        @media (max-width: 992px) {
            .container { grid-template-columns: 1fr; }
            .preview-card { position: relative; top: 0; order: -1; }
        }
    </style>
</head>
<body data-theme="light">

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="card">
            <h2 style="margin-bottom: 5px;">Configure Medicine Reminder</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 0.9rem;">Set up your smart schedules and AI adherence tracking.</p>

            <div id="ai-alert" class="smart-alert alert-info"><i class="fas fa-lightbulb"></i> <span id="ai-text"></span></div>
            <div id="dup-alert" class="smart-alert alert-warning"><i class="fas fa-exclamation-triangle"></i> You already have an active schedule for this medicine.</div>

            <form id="medicine-form" enctype="multipart/form-data">
                
                <div class="card-header"><i class="fas fa-info-circle"></i> Basic Information</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Medicine Name</label>
                        <input type="text" name="medicine_name" id="med-name" class="form-control" placeholder="e.g., Vitamin D, Amoxicillin" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Dosage</label>
                        <input type="text" name="dosage" id="med-dosage" class="form-control" placeholder="e.g., 500mg or 1 Pill" required>
                    </div>
                    <div class="form-group">
                        <label>Medicine Type</label>
                        <select name="type" id="med-type" class="form-control">
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Drops">Drops</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Purpose (Optional)</label>
                        <input type="text" name="purpose" class="form-control" placeholder="e.g., Fever, Vitamin Deficiency">
                    </div>
                    <div class="form-group full">
                        <label>Upload Medicine Photo</label>
                        <input type="file" name="medicine_photo" id="med-photo" class="form-control" accept="image/png, image/jpeg">
                    </div>
                </div>

                <div class="card-header" style="margin-top: 20px;"><i class="fas fa-clock"></i> Reminder Settings</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Frequency</label>
                        <select name="frequency" id="med-freq" class="form-control">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Once">Once</option>
                            <option value="Custom">Custom / As Needed</option>
                        </select>
                    </div>
                    
                    <div class="form-group full" style="border: 1px solid var(--border); padding: 15px; border-radius: 10px; background: rgba(0,0,0,0.02);">
                        <label style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                            <span style="font-weight: 600;">Daily Reminder Times</span>
                            <button type="button" class="btn-outline" id="add-time-btn"><i class="fas fa-plus"></i> Add Time</button>
                        </label>
                        <div id="times-container" style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <input type="time" name="times[]" class="form-control" style="width: auto; flex: 1 1 150px;" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required 
                               value="<?php echo date('Y-m-d'); ?>" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date (Optional)</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="card-header" style="margin-top: 20px;"><i class="fas fa-sliders-h"></i> Advanced Options</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Repeat Every X Hours (Optional)</label>
                        <input type="number" name="repeat_hours" class="form-control" placeholder="e.g., 8" min="1" max="72">
                    </div>
                    <div class="form-group">
                        <label>Snooze Duration</label>
                        <select name="snooze_duration" class="form-control">
                            <option value="5">5 Minutes</option>
                            <option value="10">10 Minutes</option>
                            <option value="15">15 Minutes</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Food Instruction</label>
                        <select name="food_instruction" id="med-food" class="form-control">
                            <option value="After Food">After Food</option>
                            <option value="Before Food">Before Food</option>
                            <option value="With Food">With Food</option>
                            <option value="No Restriction">No Restriction</option>
                        </select>
                    </div>
                    <div class="form-group full" style="background: rgba(14, 165, 233, 0.05); padding: 20px; border-radius: 15px; border: 1px solid rgba(14, 165, 233, 0.2);">
                        <label style="color: var(--accent);"><i class="fas fa-user-md"></i> Prescribing Doctor</label>
                        <div style="display: flex; gap: 10px;">
                            <select name="doctor_id" id="doctor-select" class="form-control" style="margin: 0; flex: 1;">
                                <option value="">-- Self Prescribed (No Doctor) --</option>
                                <?php foreach($my_doctors as $doc): ?>
                                    <option value="<?php echo $doc['id']; ?>">
                                        <?php echo htmlspecialchars($doc['name'] . ' (' . $doc['specialty'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline" onclick="document.getElementById('doctor-modal').style.display='flex'">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                        </div>
                        <textarea name="doctor_note" class="form-control" style="margin-top: 15px;" placeholder="Any specific instructions from this doctor?"></textarea>
                    </div>
                </div>
                
                <div class="form-group full toggle-container">
                    <div>
                        <div style="font-weight: 600; color: var(--text-main);">Notify Nominee via SMS/Email</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);">Alert family if 30 minutes pass and dose is pending.</div>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="notify_nominee" checked>
                        <span class="slider"></span>
                    </label>
                </div>

                <input type="hidden" name="rx_doctor_name" id="hidden_rx_doctor">
                <input type="hidden" name="rx_date" id="hidden_rx_date">

                <button type="submit" class="btn btn-primary" id="submit-btn" style="margin-top: 20px; font-size: 1.1rem; padding: 15px;">
                    <span id="btn-text">Save Medicine & Sync</span>
                    <div class="spinner" id="btn-spinner"></div>
                </button>
            </form>
        </div>

        <div>
            <div class="preview-card">
                <h3 style="margin-bottom: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 15px;">Smart Preview</h3>
                
                <div class="preview-img-box">
                    <i class="fas fa-camera" id="prev-placeholder" style="font-size: 3rem; opacity: 0.5;"></i>
                    <img id="prev-img" src="" alt="Medicine Preview">
                </div>

                <h2 id="prev-name" style="color: var(--accent); margin-bottom: 15px;">--</h2>
                
                <div class="preview-detail"><i class="fas fa-pills"></i> <span id="prev-dosage">Dosage: --</span> (<span id="prev-type">Tablet</span>)</div>
                <div class="preview-detail"><i class="fas fa-redo"></i> <span id="prev-freq">Daily</span></div>
                <div class="preview-detail"><i class="fas fa-utensils"></i> <span id="prev-food">After Food</span></div>
                
                <div id="rx-badge" style="display: none; margin-top: 20px; background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 0.85rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.4);">
                    <i class="fas fa-exclamation-triangle"></i> Prescription Required
                </div>
            </div>
        </div>
    </div>

    <div id="doctor-modal" class="modal">
        <div class="modal-content">
            <h3 style="color: var(--accent); margin-bottom: 20px;"><i class="fas fa-user-plus"></i> Add New Doctor</h3>
            <form id="new-doctor-form">
                <div class="form-group"><label>Doctor Name</label><input type="text" name="doc_name" class="form-control" required placeholder="Dr. Anjali Mehta"></div>
                <div class="form-group"><label>Specialty</label><input type="text" name="doc_specialty" class="form-control" required placeholder="Dentist"></div>
                <div class="form-group"><label>Phone Number</label><input type="tel" name="doc_phone" class="form-control" placeholder="📞 91234-56789"></div>
                <div class="form-group"><label>Clinic Name / Location</label><input type="text" name="doc_clinic" class="form-control" placeholder="📍 City Dental Clinic"></div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('doctor-modal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;" id="save-doc-btn">Save Doctor</button>
                </div>
            </form>
        </div>
    </div>

    <div id="rx-modal" class="modal">
        <div class="modal-content">
            <h3 style="color: var(--danger); margin-bottom: 15px;"><i class="fas fa-file-medical"></i> Prescription Required</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 20px;">This medicine is classified as high-risk or restricted. Please provide valid prescription details to continue.</p>
            
            <div class="form-group">
                <label>Doctor's Name</label>
                <input type="text" id="rx_doctor" class="form-control" placeholder="Dr. Smith">
            </div>
            <div class="form-group">
                <label>Prescription Date</label>
                <input type="date" id="rx_date" class="form-control" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Upload Prescription (JPG/PNG)</label>
                <input type="file" id="rx_image" class="form-control" accept="image/png, image/jpeg">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" style="flex: 1;" onclick="document.getElementById('rx-modal').style.display='none'">Cancel</button>
                <button type="button" class="btn btn-primary" style="flex: 1;" id="confirm-rx-btn">Confirm & Save</button>
            </div>
        </div>
    </div>

    <div id="success-modal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <div style="font-size: 4rem; color: var(--success); margin-bottom: 10px; animation: slideUp 0.5s ease;"><i class="fas fa-check-circle"></i></div>
            <h2 style="margin-bottom: 10px;">Medicine Synced!</h2>
            <p id="success-insight" style="background: rgba(14, 165, 233, 0.1); color: var(--accent); padding: 15px; border-radius: 10px; font-size: 0.95rem; margin-bottom: 20px;"></p>
            <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">Go to Dashboard</button>
        </div>
    </div>

    <script>
        // ================= DATE VALIDATION LOGIC =================
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        startDateInput.addEventListener('change', function() {
            // Force the End Date's minimum value to be the chosen Start Date
            endDateInput.min = this.value;
            // If user previously picked an End Date that is now invalid, snap it to the new Start Date
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });

        // ================= LIVE PREVIEW & AI SUGGESTIONS =================
        const medName = document.getElementById('med-name');
        const medDosage = document.getElementById('med-dosage');
        const medType = document.getElementById('med-type');
        const medFreq = document.getElementById('med-freq');
        const medFood = document.getElementById('med-food');
        const aiAlert = document.getElementById('ai-alert');
        const rxBadge = document.getElementById('rx-badge');

        // Simulated Restricted Database List
        const restrictedMeds = ['morphine', 'amoxicillin', 'adderall', 'oxycodone', 'warfarin', 'antibiotic'];
        let requiresRx = false;
        let rxValidated = false;

        medName.addEventListener('input', function() {
            const val = this.value;
            document.getElementById('prev-name').innerText = val || '--';
            
            // AI Suggestion Rule
            if(val.toLowerCase().includes('vitamin d')) {
                document.getElementById('ai-text').innerText = "AI Suggestion: Vitamin D is fat-soluble. Best taken in the morning after a meal.";
                aiAlert.style.display = 'block';
                medFood.value = "After Food"; // Auto-assign UI
                document.getElementById('prev-food').innerText = "After Food";
            } else {
                aiAlert.style.display = 'none';
            }

            // Prescription Checking Logic
            requiresRx = restrictedMeds.some(med => val.toLowerCase().includes(med));
            rxBadge.style.display = requiresRx ? 'block' : 'none';
            if(!requiresRx) rxValidated = false; 
        });

        medDosage.addEventListener('input', function() { document.getElementById('prev-dosage').innerText = 'Dosage: ' + (this.value || '--'); });
        medType.addEventListener('change', function() { document.getElementById('prev-type').innerText = this.value; });
        medFreq.addEventListener('change', function() { document.getElementById('prev-freq').innerText = this.value; });
        medFood.addEventListener('change', function() { document.getElementById('prev-food').innerText = this.value; });

        // ================= IMAGE UPLOAD PREVIEW =================
        document.getElementById('med-photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('prev-img').src = e.target.result;
                    document.getElementById('prev-img').style.display = 'block';
                    document.getElementById('prev-placeholder').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

       // ================= DYNAMIC TIME INPUTS =================
        document.getElementById('add-time-btn').addEventListener('click', () => {
            const container = document.getElementById('times-container');
            
            // Create a wrapper div to hold the input AND the remove button
            const wrapper = document.createElement('div');
            wrapper.style.display = 'flex';
            wrapper.style.alignItems = 'center';
            wrapper.style.gap = '8px';
            wrapper.style.flex = '1 1 150px';
            wrapper.style.animation = 'slideUp 0.3s ease';

            // Create the Time Input
            const input = document.createElement('input');
            input.type = 'time'; 
            input.name = 'times[]'; 
            input.className = 'form-control';
            input.style.margin = '0'; // Override default margins
            input.required = true;

            // Create the Remove Button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-time';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.title = 'Remove Time';
            
            // When clicked, delete this specific wrapper
            removeBtn.onclick = function() {
                wrapper.remove();
            };

            // Put the input and button inside the wrapper, then add to container
            wrapper.appendChild(input);
            wrapper.appendChild(removeBtn);
            container.appendChild(wrapper);
        });
        
        // ================= FORM SUBMISSION & RX MODAL LOGIC =================
        const form = document.getElementById('medicine-form');
        const rxModal = document.getElementById('rx-modal');
        const successModal = document.getElementById('success-modal');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Intercept flow if prescription is required but not provided
            if(requiresRx && !rxValidated) {
                rxModal.style.display = 'flex';
                return;
            }

            submitForm();
        });

        document.getElementById('confirm-rx-btn').addEventListener('click', () => {
            const rxDoc = document.getElementById('rx_doctor').value;
            const rxDate = document.getElementById('rx_date').value;
            const rxImg = document.getElementById('rx_image').files.length;

            if(!rxDoc || !rxDate || rxImg === 0) {
                alert("Please complete all prescription fields and upload the image.");
                return;
            }

            // Transfer data to hidden form inputs so they get submitted
            document.getElementById('hidden_rx_doctor').value = rxDoc;
            document.getElementById('hidden_rx_date').value = rxDate;
            
            rxValidated = true;
            rxModal.style.display = 'none';
            submitForm(); 
        });

        async function submitForm() {
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            
            btnText.style.display = 'none';
            btnSpinner.style.display = 'block';

            const formData = new FormData(form);
            
            if(requiresRx) {
                formData.append('prescription_image', document.getElementById('rx_image').files[0]);
            }

            try {
                const response = await fetch('add_medicine.php', {
                    method: 'POST',
                    body: formData
                });
                
                const rawText = await response.text();
                
                try {
                    const result = JSON.parse(rawText);

                    if(result.status === 'success') {
                        document.getElementById('success-insight').innerText = result.ai_insight;
                        successModal.style.display = 'flex';
                    } else {
                        alert("Application Notice:\n" + result.message);
                    }
                } catch (parseError) {
                    console.error("Raw PHP Error Output:", rawText);
                    alert("PHP Error Logged:\n\n" + rawText);
                }

            } catch (error) {
                console.error('Network Error:', error);
                alert('A network error occurred connecting to the server.');
            } finally {
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        }

        // Handle saving a new doctor
        document.getElementById('new-doctor-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('save-doc-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            const formData = new FormData(e.target);
            
            try {
                const res = await fetch('api_add_doctor.php', { method: 'POST', body: formData });
                const rawText = await res.text(); // Read exactly what PHP sends back
                
                try {
                    const data = JSON.parse(rawText);
                    
                    if(data.status === 'success') {
                        // Instantly add it to the dropdown and select it!
                        const select = document.getElementById('doctor-select');
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.text = `${data.name} (${data.specialty})`;
                        select.appendChild(option);
                        select.value = data.id;
                        
                        document.getElementById('doctor-modal').style.display = 'none';
                        e.target.reset();
                    } else {
                        alert("Database Error: " + (data.message || "Could not save doctor."));
                    }
                } catch (parseError) {
                    console.error("Raw Output:", rawText);
                    alert("PHP CRASHED! Here is the exact error:\n\n" + rawText);
                }
            } catch (networkError) {
                alert("Network error. Did you name the file exactly api_add_doctor.php?");
            } finally {
                btn.innerHTML = 'Save Doctor'; 
            }
        });
        
    </script>
</body>
</html>