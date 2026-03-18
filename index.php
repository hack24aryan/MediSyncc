<?php
include 'db.php';
// Fetch stats from database
$query = "SELECT * FROM site_stats WHERE id = 1";
$result = $conn->query($query);
$stats = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediSyncc | Smart Medication Reminders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0f172a;
            --accent: #0ea5e9;
            --bg: #f4f7fb;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --nav-bg: rgba(244, 247, 251, 0.8);
            --transition: all 0.3s ease;
            --shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
        }

        [data-theme="dark"] {
            --primary: #0ea5e9;
            --accent: #38bdf8;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --nav-bg: rgba(15, 23, 42, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html { scroll-behavior: smooth; }
        body { background-color: var(--bg); color: var(--text-main); line-height: 1.6; overflow-x: hidden; transition: background 0.5s ease; }

        /* --- Global Animations --- */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }

        /* --- Announcement Bar --- */
        .announcement {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.9rem;
            animation: slideDown 0.6s ease-out;
            position: relative;
            z-index: 1001;
        }
        @keyframes slideDown { from { transform: translateY(-100%); } to { transform: translateY(0); } }

        /* --- Navbar --- */
        nav {
            position: sticky;
            top: 0;
            width: 100%;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: var(--transition);
            background: transparent;
        }
        nav.scrolled {
            background: var(--nav-bg);
            backdrop-filter: blur(10px);
            padding: 15px 8%;
            box-shadow: var(--shadow);
        }
        .logo { font-size: 1.5rem; font-weight: 700; color: var(--accent); text-decoration: none; }
        .nav-links { display: flex; gap: 30px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 500; transition: var(--accent); }
        .nav-links a:hover { color: var(--accent); }
        
        .nav-btns { display: flex; gap: 15px; align-items: center; }
        .btn { padding: 10px 24px; border-radius: 50px; cursor: pointer; font-weight: 600; transition: var(--transition); border: none; text-decoration: none; display: inline-block; position: relative; overflow: hidden; }
        .btn-outline { border: 2px solid var(--accent); color: var(--accent); background: transparent; }
        .btn-fill { background: var(--accent); color: white; }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3); }

        /* Dark Mode Toggle */
        .theme-toggle { cursor: pointer; font-size: 1.2rem; color: var(--text-main); margin-left: 15px; }

        /* --- Hero Section --- */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 100px 8% 150px;
            align-items: center;
            gap: 50px;
            position: relative;
        }
        .hero-content h1 { font-size: 3.5rem; line-height: 1.2; margin-bottom: 20px; color: var(--primary); }
        .hero-content p { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; }
        
        .hero-visual { position: relative; }
        .dashboard-mock {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        
        .shape { position: absolute; z-index: -1; border-radius: 50%; background: var(--accent); filter: blur(80px); opacity: 0.1; }

        /* --- Stats Section --- */
        .stats {
            display: flex;
            justify-content: space-around;
            padding: 80px 8%;
            background: var(--card-bg);
            margin: -80px 8% 50px;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }
        .stat-item { text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: var(--accent); display: block; }

        /* --- Features Grid --- */
        .section-header { text-align: center; margin-bottom: 60px; padding: 0 20px; }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 8% 100px;
        }
        .feature-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .feature-card:hover { transform: translateY(-10px); box-shadow: var(--shadow); }
        .feature-card i { font-size: 2.5rem; color: var(--accent); margin-bottom: 20px; }

        /* --- How It Works --- */
        .how-it-works { padding: 100px 8%; background: #0f172a; color: white; }
        .steps { display: flex; justify-content: space-between; position: relative; margin-top: 50px; }
        .step { flex: 1; text-align: center; position: relative; z-index: 2; }
        .step-num { 
            width: 60px; height: 60px; background: var(--accent); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;
            font-weight: 700; font-size: 1.5rem; position: relative;
        }
        .step-num::after {
            content: ''; position: absolute; width: 100%; height: 100%; 
            border: 2px solid var(--accent); border-radius: 50%; animation: pulse 2s infinite;
        }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

        /* --- Pricing --- */
        .pricing-grid {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 0 8% 100px;
            flex-wrap: wrap;
        }
        .price-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 20px;
            width: 350px;
            text-align: center;
            transition: var(--transition);
        }
        .price-card.featured {
            transform: scale(1.05);
            border: 2px solid var(--accent);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.2);
        }
        .price-card:hover { transform: scale(1.08); }
        .price-amount { font-size: 3rem; font-weight: 700; margin: 20px 0; }

        /* --- FAQ --- */
        .faq-container { max-width: 800px; margin: 0 auto 100px; padding: 0 20px; }
        .faq-item { background: var(--card-bg); margin-bottom: 15px; border-radius: 10px; overflow: hidden; }
        .faq-question { padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
        .faq-answer { padding: 0 20px; max-height: 0; overflow: hidden; transition: 0.3s ease; color: var(--text-muted); }
        .faq-item.active .faq-answer { padding-bottom: 20px; max-height: 200px; }
        .faq-item i { transition: 0.3s; }
        .faq-item.active i { transform: rotate(45deg); }

        /* --- Footer --- */
        footer { background: #0f172a; color: white; padding: 80px 8% 20px; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 50px; margin-bottom: 50px; }
        .footer-logo { font-size: 1.5rem; font-weight: 700; margin-bottom: 20px; display: block; }
        .social-links { display: flex; gap: 15px; margin-top: 20px; }
        .social-links a { color: white; font-size: 1.2rem; transition: var(--transition); }
        .social-links a:hover { color: var(--accent); transform: translateY(-3px); }

        /* Responsive */
        @media (max-width: 992px) {
            .hero { grid-template-columns: 1fr; text-align: center; }
            .nav-links { display: none; }
            .stats { flex-wrap: wrap; gap: 30px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        /* Mobile Menu */
        .menu-btn { display: none; font-size: 1.5rem; cursor: pointer; }
        @media (max-width: 992px) { .menu-btn { display: block; } }

    </style>
</head>
<body>

    

    <nav id="navbar">
         <a href="#" class="logo"><i class="fas fa-pills"></i> MediSyncc</a>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#how">How It Works</a>
            <a href="#pricing">Pricing</a>
            <div class="theme-toggle" id="theme-btn"><i class="fas fa-moon"></i></div>
        </div>
        <div class="nav-btns">
           <a href="auth.php" class="btn btn-outline">Login</a>
<a href="auth.php" class="btn btn-fill">Get Started</a>


         <div class="menu-btn"><i class="fas fa-bars"></i></div>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="shape" style="width: 400px; height: 400px; top: -100px; left: -100px;"></div>
        <div class="hero-content reveal">
            <h1>Never Miss a <br><span style="color: var(--accent)">Dose Again.</span></h1>
            <p>The ultra-modern medicine companion that syncs with your life. Smart reminders, caregiver alerts, and detailed health analytics in one place.</p>
            <div style="display: flex; gap: 20px;">
                <button class="btn btn-fill">Download App</button>
                <button class="btn btn-outline">Watch Demo</button>
            </div>
        </div>
        <div class="hero-visual reveal">
            <div class="dashboard-mock">
                <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                    <b>My Medications</b>
                    <span style="color: var(--accent)">Today</span>
                </div>
                <div style="background: var(--bg); padding:15px; border-radius:12px; margin-bottom:10px; display:flex; align-items:center; gap:15px;">
                    <i class="fas fa-pills" style="color: #0ea5e9;"></i>
                    <div>
                        <div style="font-weight:600">Amoxicillin 500mg</div>
                        <div style="font-size:0.8rem; color:var(--text-muted)">Next dose: 08:00 PM</div>
                    </div>
                </div>
                <div style="background: var(--bg); padding:15px; border-radius:12px; opacity: 0.7; display:flex; align-items:center; gap:15px;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <div>
                        <div style="font-weight:600">Vitamin D3</div>
                        <div style="font-size:0.8rem; color:var(--text-muted)">Taken at 09:00 AM</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="stats reveal">
        <div class="stat-item">
            <span class="stat-number" data-target="<?php echo $stats['total_users']; ?>">0</span>
            <p>Users</p>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-target="<?php echo $stats['reminders_sent']; ?>">0</span>
            <p>Reminders Sent</p>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-target="<?php echo $stats['on_time_percent']; ?>">0</span>
            <p>On-Time %</p>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-target="<?php echo $stats['rating']; ?>">0</span>
            <p>Rating</p>
        </div>
    </div>

    <section id="features" style="padding: 100px 0;">
        <div class="section-header reveal">
            <h2>Everything you need to <span style="color: var(--accent)">stay healthy</span></h2>
            <p>Designed for patients, trusted by doctors.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card reveal">
                <i class="fas fa-clock"></i>
                <h3>Smart Scheduling</h3>
                <p>AI-driven scheduling that adapts to your timezone and daily routine automatically.</p>
            </div>
            <div class="feature-card reveal">
                <i class="fas fa-bell"></i>
                <h3>Real-Time Alerts</h3>
                <p>Persistent notifications that won't stop until you acknowledge your medication.</p>
            </div>
            <div class="feature-card reveal">
                <i class="fas fa-users"></i>
                <h3>Caregiver Alerts</h3>
                <p>Instantly notify family or carers if a critical dose is missed.</p>
            </div>
            <div class="feature-card reveal">
                <i class="fas fa-user-md"></i>
                <h3>Doctor Panel</h3>
                <p>Directly share your adherence reports with your healthcare provider.</p>
            </div>
            <div class="feature-card reveal">
                <i class="fas fa-chart-line"></i>
                <h3>Health Analytics</h3>
                <p>Visualize your progress with beautiful, easy-to-read health trends.</p>
            </div>
            <div class="feature-card reveal">
                <i class="fas fa-mobile-alt"></i>
                <h3>Mobile Friendly</h3>
                <p>Access your schedule from any device, anywhere in the world.</p>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how">
        <div class="section-header reveal">
            <h2 style="color: white;">How It <span style="color: var(--accent)">Works</span></h2>
        </div>
        <div class="steps">
            <div class="step reveal">
                <div class="step-num">1</div>
                <h3>Add Meds</h3>
                <p>Scan your prescription or enter details manually.</p>
            </div>
            <div class="step reveal">
                <div class="step-num">2</div>
                <h3>Set Alerts</h3>
                <p>Choose your times and notification preferences.</p>
            </div>
            <div class="step reveal">
                <div class="step-num">3</div>
                <h3>Stay On Track</h3>
                <p>Get notified and keep your health in perfect sync.</p>
            </div>
        </div>
    </section>

    <section id="pricing" style="padding: 100px 0;">
        <div class="section-header reveal">
            <h2>Simple <span style="color: var(--accent)">Pricing</span></h2>
            <p>Choose the plan that fits your health journey.</p>
        </div>
        <div class="pricing-grid">
            <div class="price-card reveal">
                <h3>Free</h3>
                <div class="price-amount">₹0</div>
                <p>Up to 3 medications<br>Basic notifications<br>1 Device sync</p>
                <button class="btn btn-outline" style="margin-top: 20px; width: 100%;">Current Plan</button>
            </div>
            <div class="price-card featured reveal">
                <h3>Basic</h3>
                <div class="price-amount">₹99<span>/mo</span></div>
                <p>Unlimited medications<br>Caregiver alerts<br>Cloud Backup</p>
                <button class="btn btn-fill" style="margin-top: 20px; width: 100%;">Get Started</button>
            </div>
            <div class="price-card reveal">
                <h3>Premium</h3>
                <div class="price-amount">₹199<span>/mo</span></div>
                <p>Doctor integration<br>Advanced Analytics<br>Priority Support</p>
                <button class="btn btn-outline" style="margin-top: 20px; width: 100%;">Go Pro</button>
            </div>
        </div>
    </section>

    <div class="faq-container reveal">
        <h2 style="text-align:center; margin-bottom:40px;">Frequently Asked Questions</h2>
        <div class="faq-item">
            <div class="faq-question">Is my data secure? <i class="fas fa-plus"></i></div>
            <div class="faq-answer">Yes, we use bank-level AES-256 encryption to ensure your medical data remains private and secure.</div>
        </div>
        <div class="faq-item">
            <div class="faq-question">Can I add multiple caregivers? <i class="fas fa-plus"></i></div>
            <div class="faq-answer">On the Premium plan, you can add up to 5 family members or caregivers to receive notifications.</div>
        </div>
    </div>

    <section style="padding: 100px 8%; text-align: center;">
        <div class="reveal" style="background: linear-gradient(135deg, #0f172a, #0ea5e9); padding: 80px 40px; border-radius: 30px; color: white;">
            <h2>Ready to Take Control of Your Health?</h2>
            <p style="margin: 20px 0 40px; opacity: 0.9;">Join 500,000+ users staying healthy with MediSyncc.</p>
            <button class="btn btn-fill" style="background: white; color: var(--accent); font-size: 1.2rem; padding: 15px 40px;">Get Started for Free</button>
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div>
               <a href="#" class="logo"><i class="fas fa-pills"></i> MediSyncc</a>
                <p style="opacity: 0.7;">Empowering individuals to manage their health journey with confidence and precision.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div>
                <h4>Company</h4>
                <p>About Us</p><p>Careers</p><p>Privacy</p>
            </div>
            <div>
                <h4>Support</h4>
                <p>Help Center</p><p>Contact Us</p><p>Security</p>
            </div>
            <div>
                <h4>Contact</h4>
                <p>support@medisyncc.com</p>
                <p>+91 98765 43210</p>
            </div>
        </div>
        <div style="text-align: center; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.1); opacity: 0.5;">
            &copy; 2026 MediSyncc Inc. All rights reserved.
        </div>
    </footer>

    <script>
        // --- Navbar Scroll Effect ---
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 50) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        });

        // --- Dark Mode Toggle ---
        const themeBtn = document.getElementById('theme-btn');
        const body = document.body;
        themeBtn.addEventListener('click', () => {
            const isDark = body.getAttribute('data-theme') === 'dark';
            body.setAttribute('data-theme', isDark ? 'light' : 'dark');
            themeBtn.innerHTML = isDark ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';
        });

        // --- Intersection Observer for Animations ---
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    if (entry.target.classList.contains('stats')) startCounters();
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // --- Stats Counter Animation ---
        function startCounters() {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const target = +stat.getAttribute('data-target');
                const count = +stat.innerText;
                const speed = 200;
                const inc = target / speed;

                const updateCount = () => {
                    const current = +stat.innerText;
                    if (current < target) {
                        stat.innerText = Math.ceil(current + inc);
                        setTimeout(updateCount, 1);
                    } else {
                        stat.innerText = target + (target === 5 ? '★' : (target === 99 ? '%' : '+'));
                    }
                };
                updateCount();
            });
        }

        // --- FAQ Accordion ---
        document.querySelectorAll('.faq-question').forEach(item => {
            item.addEventListener('click', () => {
                const parent = item.parentElement;
                parent.classList.toggle('active');
            });
        });

        // --- Ripple Effect for Buttons ---
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                let x = e.clientX - e.target.offsetLeft;
                let y = e.clientY - e.target.offsetTop;
                let ripple = document.createElement('span');
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    </script>
</body>
</html>