<?php
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sub_info = validateSubscription($conn, $user_id);

// --- HANDLE UPGRADE AJAX REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upgrade') {
    ob_clean();
    header('Content-Type: application/json');
    
    $selected_plan = $_POST['plan_type'];
    if (!in_array($selected_plan, ['Free', 'Basic', 'Premium'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid plan selected.']); exit;
    }

    // Process "Payment" & Update DB (Adds 1 month subscription)
    $stmt = $conn->prepare("UPDATE users SET plan_type = ?, subscription_start = NOW(), subscription_end = DATE_ADD(NOW(), INTERVAL 1 MONTH), subscription_status = 'active' WHERE id = ?");
    $stmt->bind_param("si", $selected_plan, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "Successfully upgraded to $selected_plan!"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans | MediSyncc</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #f8fafc; --card: #ffffff; --text: #1e293b; --muted: #64748b; --border: #e2e8f0; --accent: #0ea5e9; --premium: #8b5cf6; --success: #10b981; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg); color: var(--text); }
        
        .container { max-width: 1200px; margin: 50px auto; padding: 0 5%; text-align: center; }
        .header { margin-bottom: 50px; }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        
        /* Status Banners */
        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 20px; border-radius: 30px; font-weight: 600; font-size: 0.9rem; margin-bottom: 20px; }
        .status-trial { background: rgba(14, 165, 233, 0.1); color: var(--accent); border: 1px solid var(--accent); }
        .status-expired { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid var(--danger); animation: pulse 2s infinite; }

        /* Pricing Grid */
        .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 60px; }
        .plan-card { background: var(--card); border-radius: 24px; padding: 40px; border: 1px solid var(--border); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-align: left; position: relative; }
        .plan-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05); }
        
        .plan-card.active-plan { border: 2px solid var(--success); }
        .current-label { position: absolute; top: 15px; right: 20px; background: var(--success); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }

        .plan-premium { border: 2px solid var(--premium); background: linear-gradient(to bottom, #ffffff, #fdfaff); }
        .premium-tag { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: linear-gradient(90deg, var(--premium), var(--accent)); color: white; padding: 6px 20px; border-radius: 30px; font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; }

        .price { font-size: 3rem; font-weight: 700; margin: 20px 0; }
        .price span { font-size: 1rem; color: var(--muted); font-weight: 400; }

        .features { list-style: none; margin-bottom: 30px; }
        .features li { margin-bottom: 12px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
        .features i { color: var(--success); font-size: 1rem; }
        .features .no { color: var(--muted); opacity: 0.5; text-decoration: line-through; }
        .features .no i { color: var(--muted); }

        .btn { width: 100%; padding: 16px; border-radius: 14px; border: none; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.3s; }
        .btn-free { background: var(--bg); color: var(--text); border: 2px solid var(--border); }
        .btn-basic { background: var(--accent); color: white; }
        .btn-premium { background: var(--premium); color: white; box-shadow: 0 10px 20px -5px rgba(139, 92, 246, 0.4); }
        .btn:hover { opacity: 0.9; transform: scale(1.02); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* Modal Styles */
        .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 40px; border-radius: 24px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid var(--accent); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }

        @media (max-width: 900px) { .pricing-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="header">
            <?php if($sub_info['status'] === 'trial'): ?>
                <div class="status-badge status-trial"><i class="fas fa-bolt"></i> Free Premium Trial: <?php echo $sub_info['trial_days_left']; ?> Days Remaining</div>
            <?php elseif($sub_info['status'] === 'expired'): ?>
                <div class="status-badge status-expired"><i class="fas fa-exclamation-triangle"></i> Trial Expired - Select a plan to continue</div>
            <?php endif; ?>
            
            <h1>Choose Your Health Plan</h1>
            <p style="color: var(--muted);">Join 10,000+ users staying healthy with MediSyncc Smart Reminders.</p>
        </div>

        <div class="pricing-grid">
            <div class="plan-card <?php echo $sub_info['plan'] == 'Free' ? 'active-plan' : ''; ?>">
                <?php if($sub_info['plan'] == 'Free') echo '<span class="current-label">Current</span>'; ?>
                <h3>Free</h3>
                <div class="price">₹0<span>/mo</span></div>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Up to 3 Medicines</li>
                    <li><i class="fas fa-check-circle"></i> 1 Nominee / Caregiver</li>
                    <li class="no"><i class="fas fa-times-circle"></i> AI Adherence Insights</li>
                    <li class="no"><i class="fas fa-times-circle"></i> Emergency Escalation</li>
                    <li class="no"><i class="fas fa-times-circle"></i> Monthly Analytics PDF</li>
                </ul>
                <button class="btn btn-free" disabled>Default Plan</button>
            </div>

            <div class="plan-card <?php echo $sub_info['plan'] == 'Basic' ? 'active-plan' : ''; ?>">
                <?php if($sub_info['plan'] == 'Basic') echo '<span class="current-label">Current</span>'; ?>
                <h3>Basic</h3>
                <div class="price">₹99<span>/mo</span></div>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Unlimited Medicines</li>
                    <li><i class="fas fa-check-circle"></i> 1 Nominee / Caregiver</li>
                    <li><i class="fas fa-check-circle"></i> Basic AI Insights</li>
                    <li class="no"><i class="fas fa-times-circle"></i> Emergency Escalation</li>
                    <li class="no"><i class="fas fa-times-circle"></i> Monthly Analytics PDF</li>
                </ul>
                <button class="btn btn-basic" onclick="openPayment('Basic', 99)" <?php echo $sub_info['plan'] == 'Basic' ? 'disabled' : ''; ?>>
                    <?php echo $sub_info['plan'] == 'Basic' ? 'Plan Active' : 'Upgrade to Basic'; ?>
                </button>
            </div>

            <div class="plan-card plan-premium <?php echo $sub_info['plan'] == 'Premium' && $sub_info['status'] == 'active' ? 'active-plan' : ''; ?>">
                <div class="premium-tag">RECOMMENDED</div>
                <?php if($sub_info['plan'] == 'Premium' && $sub_info['status'] == 'active') echo '<span class="current-label">Current</span>'; ?>
                <h3>Premium</h3>
                <div class="price">₹199<span>/mo</span></div>
                <ul class="features">
                    <li><i class="fas fa-check-circle"></i> Unlimited Medicines</li>
                    <li><i class="fas fa-check-circle"></i> Unlimited Nominees</li>
                    <li><i class="fas fa-check-circle"></i> AI Caregiver Engine</li>
                    <li><i class="fas fa-check-circle"></i> Emergency Escalation</li>
                    <li><i class="fas fa-check-circle"></i> Monthly Analytics PDF</li>
                </ul>
                <button class="btn btn-premium" onclick="openPayment('Premium', 199)" <?php echo ($sub_info['plan'] == 'Premium' && $sub_info['status'] == 'active') ? 'disabled' : ''; ?>>
                    <?php echo ($sub_info['plan'] == 'Premium' && $sub_info['status'] == 'active') ? 'Plan Active' : 'Go Premium'; ?>
                </button>
            </div>
        </div>

        <p style="color: var(--muted); font-size: 0.85rem;">Secure checkout powered by simulated MediPay Gateway. No real card info required.</p>
    </div>

    <div id="paymentModal" class="modal">
        <div class="modal-content" id="modalContent">
            <div class="loader"></div>
            <h2 id="processText">Processing Transaction...</h2>
            <p style="color: var(--muted); margin-top: 10px;">Verifying your secure payment details.</p>
        </div>
    </div>

    <script>
        function openPayment(plan, price) {
            const modal = document.getElementById('paymentModal');
            const content = document.getElementById('modalContent');
            modal.style.display = 'flex';

            // Simulate Network Delay
            setTimeout(async () => {
                const fd = new FormData();
                fd.append('action', 'upgrade');
                fd.append('plan_type', plan);

                try {
                    const response = await fetch('subscription.php', { method: 'POST', body: fd });
                    const result = await response.json();

                    if(result.status === 'success') {
                        content.innerHTML = `
                            <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 20px;"></i>
                            <h2>Payment Successful!</h2>
                            <p style="margin: 15px 0 25px; color: var(--muted);">Your account has been upgraded to <b>${plan}</b> for 30 days.</p>
                            <button class="btn btn-basic" onclick="window.location.href='dashboard.php'">Finish & Back to Dashboard</button>
                        `;
                    } else {
                        content.innerHTML = `<h2>Error</h2><p>${result.message}</p><button onclick="location.reload()">Try Again</button>`;
                    }
                } catch (e) {
                    content.innerHTML = `<h2>Network Error</h2><button onclick="location.reload()">Retry</button>`;
                }
            }, 2000);
        }
    </script>
</body>
</html>