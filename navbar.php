<style>
    .navbar { position: sticky; top: 0; z-index: 100; background: rgba(var(--card-bg), 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; transition: var(--transition); }
    .navbar.scrolled { box-shadow: var(--shadow); }
    .logo { font-size: 1.5rem; font-weight: 700; color: var(--accent); text-decoration: none; }
    .nav-links { display: flex; gap: 20px; align-items: center; } 
    .nav-links a { text-decoration: none; color: var(--text-main); font-weight: 500; transition: var(--transition); } 
    .nav-links a:hover, .nav-links a.active { color: var(--accent); }
    .nav-actions { display: flex; align-items: center; gap: 15px; position: relative; }
    .icon-btn { background: none; border: none; font-size: 1.2rem; color: var(--text-main); cursor: pointer; transition: var(--transition); position: relative; } 
    .icon-btn:hover { color: var(--accent); transform: scale(1.1); }
    .badge { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%; font-weight: bold; }
    .shaking { animation: shake 0.5s ease infinite; color: var(--accent); }
    .dropdown { position: absolute; top: 50px; right: 0; background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; width: 280px; box-shadow: var(--shadow); display: none; flex-direction: column; z-index: 1000; animation: fadeIn 0.2s ease-out; }
    .dropdown.active { display: flex; }
    .dropdown-item { padding: 12px 15px; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.2s; color: var(--text-main); }
    .dropdown-item:hover { background: rgba(14, 165, 233, 0.05); color: var(--accent); }
    #notif-content { max-height: 300px; overflow-y: auto; }
    .notif-empty { padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; }
</style>

<nav class="navbar" id="navbar">
    <a href="dashboard.php" class="logo"><i class="fas fa-pills"></i> MediSyncc</a>
    <div class="nav-links">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="add_medicine.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'add_medicine.php' ? 'active' : ''; ?>">Add Medicine</a>
        <a href="nominee.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'nominee.php' ? 'active' : ''; ?>">Nominee</a>
        <a href="subscription.php">Subscription</a>
    </div>
    <div class="nav-actions">
        <button class="icon-btn" id="sound-toggle" title="Toggle Sound"><i class="fas fa-volume-up"></i></button>
        <button class="icon-btn" id="theme-toggle" title="Dark Mode"><i class="fas fa-moon"></i></button>
        
        <div style="position: relative;">
            <button class="icon-btn" id="bell-icon">
                <i class="fas fa-bell"></i>
                <span class="badge" id="notif-badge" style="display:none;">0</span>
            </button>
            <div class="dropdown" id="notif-dropdown">
                <div class="dropdown-header" style="padding:15px; font-weight:600; border-bottom:1px solid var(--border);">Notifications</div>
                <div id="notif-content"></div>
            </div>
        </div>

        <div style="position: relative;">
            <button class="icon-btn" id="profile-btn" style="padding:0;">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name ?? 'User'); ?>&background=0ea5e9&color=fff" alt="Profile" style="width: 35px; border-radius: 50%; border: 2px solid transparent; transition: 0.3s;" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
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

<script>
    const bellBtn = document.getElementById('bell-icon');
    const profileBtn = document.getElementById('profile-btn');
    const notifDropdown = document.getElementById('notif-dropdown');
    const profileDropdown = document.getElementById('profile-dropdown');

    if(bellBtn) {
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if(profileDropdown) profileDropdown.classList.remove('active');
            notifDropdown.classList.toggle('active');
            updateNotifications();
        });
    }

    if(profileBtn) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if(notifDropdown) notifDropdown.classList.remove('active');
            profileDropdown.classList.toggle('active');
        });
    }

    window.addEventListener('click', () => {
        if(notifDropdown) notifDropdown.classList.remove('active');
        if(profileDropdown) profileDropdown.classList.remove('active');
    });

    window.addEventListener('scroll', () => { 
        const nav = document.getElementById('navbar');
        if(nav) nav.classList.toggle('scrolled', window.scrollY > 10); 
    });

    function updateNotifications() {
        const content = document.getElementById('notif-content');
        const badge = document.getElementById('notif-badge');
        
        // Safety check to make sure medicines array exists on the current page
        if (typeof medicines !== 'undefined' && badge && content) {
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
        } else if (badge && content) {
            // If on a page without medicines (like profile.php), hide badge
            badge.style.display = 'none';
            content.innerHTML = '<div class="notif-empty">No new notifications</div>';
        }
    }

    // Run once on load to set the badge count
    updateNotifications();
</script>