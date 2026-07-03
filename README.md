<div align="center">

<h1>💊 MediSyncc</h1>
<p><strong>Smart Medication Management & Caregiver Alert System</strong></p>

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/HTML)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org/en-US/docs/Web/CSS)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License: MIT](https://img.shields.io/badge/License-MIT-10b981?style=for-the-badge)](./LICENSE)

<br>

*Empowering patients and caregivers to stay in sync — never miss a dose again.*

</div>

---

## 📖 Table of Contents

- [About the Project](#-about-the-project)
- [Key Features](#-key-features)
- [Project Statistics](#-project-statistics)
- [Tech Stack](#-tech-stack)
- [Quick Start](#-quick-start)
- [Installation Guide](#-installation-guide)
- [Database Setup](#-database-setup)
- [Environment Configuration](#-environment-configuration)
- [API Overview](#-api-overview)
- [Project Structure](#-project-structure)
- [Future Improvements](#-future-improvements)
- [Release Notes](#-release-notes)
- [Contributors](#-contributors)
- [License](#-license)
- [Acknowledgements](#-acknowledgements)

---

## 🏥 About the Project

**MediSyncc** is a full-stack web application designed to help patients manage their daily medication schedules with precision and confidence. It provides smart reminders, real-time caregiver alerts, and detailed adherence analytics — all from a beautiful, mobile-responsive interface.

Whether you are managing a chronic condition or simply tracking daily vitamins, MediSyncc adapts to your routine and keeps your trusted circle informed when it matters most.

> Built as a collaborative academic project, MediSyncc demonstrates real-world application development skills including secure authentication, relational database design, REST-style API endpoints, subscription management, and caregiver role management.

---

## ✨ Key Features

| Feature | Description |
|---|---|
| 🔔 **Smart Reminders** | Schedule per-medicine reminder times with snooze and repeat support |
| 👨‍⚕️ **Doctor Integration** | Link prescriptions directly to saved doctor profiles |
| 🛡️ **Caregiver Alerts** | Nominees receive notifications when critical doses are missed |
| 📊 **Adherence Dashboard** | Visual daily adherence tracking with percentage scores |
| 🔐 **Secure Auth** | Session-based login with bcrypt password hashing |
| 🧾 **Prescription Gate** | Restricted medicines require doctor details before saving |
| 💳 **Subscription Tiers** | Free, Basic, and Premium plans with feature gating |
| ⏱️ **4-Day Free Trial** | Auto-activates on registration; trial expiry enforced server-side |
| 🌙 **Dark Mode** | Full light/dark theme toggle, persisted per session |
| 📱 **Mobile Responsive** | Works seamlessly on phones, tablets, and desktops |

---

## 📊 Project Statistics

| Metric | Value |
|---|---|
| **PHP Version** | 8.0+ |
| **MySQL Version** | 8.0+ |
| **User-Facing Pages** | 9 |
| **REST API Endpoints** | 5 |
| **Database Tables** | 8 |
| **Subscription Plans** | 3 (Free, Basic, Premium) |
| **Contributor Roles** | Patient, Nominee (Caregiver) |
| **Auth Method** | PHP Sessions + bcrypt |

---

## 🛠 Tech Stack

- **Backend:** PHP 8.0 (procedural, MySQLi)
- **Database:** MySQL 8.0
- **Frontend:** Vanilla HTML5, CSS3 (custom properties / theming), JavaScript (ES6+)
- **Icons:** Font Awesome 6.4
- **Fonts:** Google Fonts — Poppins
- **Charts:** Chart.js
- **Server:** Apache (XAMPP recommended for local development)

---

## ⚡ Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/hack24aryan/medisyncc.git
cd medisyncc

# 2. Copy the environment template
cp .env.example .env

# 3. Edit .env and set your database credentials
#    Open .env in any text editor and fill in DB_PASS

# 4. Import the database schema
#    Open phpMyAdmin → Import → select database.sql
#    OR run via CLI:
mysql -u root -p < database.sql

# 5. Start Apache and MySQL via XAMPP Control Panel

# 6. Open in your browser
#    http://localhost/medisyncc/
```

---

## 📦 Installation Guide

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (PHP 8.0+, Apache, MySQL 8.0+)
- A modern web browser (Chrome, Firefox, Edge)
- Git (optional, for cloning)

### Steps

1. **Clone or download** this repository into your XAMPP `htdocs` folder:
   ```
   C:\xampp\htdocs\medisyncc\
   ```

2. **Create your environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Edit `.env`** and configure your local database credentials:
   ```ini
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=your_password_here
   DB_NAME=medisyncc_db
   ```

4. **Import the database** (see [Database Setup](#-database-setup) below).

5. **Start Apache and MySQL** using the XAMPP Control Panel.

6. **Open the project** in your browser:
   ```
   http://localhost/medisyncc/
   ```

---

## 🗄 Database Setup

MediSyncc requires a MySQL database. A full schema file is provided.

### Option A — phpMyAdmin (Recommended for XAMPP)

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** in the top navigation
3. Choose `database.sql` from the project root
4. Click **Go**

### Option B — MySQL CLI

```bash
mysql -u root -p < database.sql
```

### Schema Overview

| Table | Purpose |
|---|---|
| `users` | Patient accounts, subscription, and profile data |
| `user_medicines` | Medicines added by each patient |
| `medicine_times` | Individual reminder times per medicine |
| `medicine_logs` | Records of doses marked as taken |
| `user_doctors` | Saved doctor profiles linked to medicines |
| `nominees` | Caregiver accounts linked to a patient |
| `notifications` | Alerts sent to caregivers on missed doses |
| `activity_logs` | Audit trail of user actions |
| `site_stats` | Public counters shown on the landing page |

> All tables use `InnoDB` with foreign key constraints and proper indexes for referential integrity and query performance.

---

## ⚙ Environment Configuration

MediSyncc uses a `.env` file for all local configuration.

**Never commit `.env` to version control.** It is already listed in `.gitignore`.

### Template (`.env.example`)

```ini
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=medisyncc_db
```

### Your local file (`.env`)

```ini
DB_HOST=localhost
DB_USER=root
DB_PASS=your_actual_password
DB_NAME=medisyncc_db
```

The file is parsed at runtime by `load_env.php`, which is included by both `db.php` and `config.php`. If `.env` is missing, the application will halt and display a clear setup error.

---

## 🔌 API Overview

MediSyncc exposes lightweight internal JSON API endpoints:

| Endpoint | Method | Description |
|---|---|---|
| `api_login.php` | `POST` | Authenticate user, start session |
| `api_register.php` | `POST` | Create new user account |
| `api_mark_taken.php` | `POST` | Log a dose as taken for today |
| `api_dismiss_reminder.php` | `POST` | Dismiss/snooze a reminder |
| `api_delete_medicine.php` | `POST` | Soft-delete or remove a medicine |
| `api_add_doctor.php` | `POST` | Save a new doctor profile |

All endpoints return JSON with a `status` field (`"success"` or `"error"`).

---

## 📁 Project Structure

```
medisyncc/
│
├── .env                    # Local environment config (NOT committed)
├── .env.example            # Template for developers
├── .gitignore              # Git ignore rules
│
├── load_env.php            # Environment parser — loaded by db.php & config.php
├── db.php                  # Simple DB connection (used by auth & index)
├── config.php              # DB + session + subscription engine
├── footer.php              # Shared footer component
├── navbar.php              # Shared navigation bar component
├── logout.php              # Session destroy & redirect
│
├── index.php               # Public landing page
├── auth.php                # Login & registration page
├── dashboard.php           # Patient medication dashboard
├── add_medicine.php        # Add / manage medicines
├── profile.php             # User account & settings
├── nominee.php             # Manage caregiver accounts
├── nominee_login.php       # Caregiver login portal
├── nominee_dashboard.php   # Live caregiver view of patient
├── subscription.php        # Plan selection & upgrade
│
├── api_login.php           # POST: authenticate user
├── api_register.php        # POST: register new user
├── api_mark_taken.php      # POST: log dose as taken
├── api_dismiss_reminder.php# POST: dismiss a reminder
├── api_delete_medicine.php # POST: remove a medicine
├── api_add_doctor.php      # POST: save a doctor profile
│
├── database.sql            # Full DB schema + seed data
├── README.md               # Project documentation
├── AUTHORS.md              # Contributor details
├── CONTRIBUTING.md         # Development workflow
└── LICENSE                 # MIT License
```

---

## 🚀 Future Improvements

- [ ] **Email/SMS Notifications** — Integrate Twilio or Mailgun for real push alerts
- [ ] **Progressive Web App (PWA)** — Service worker for offline support and home-screen install
- [ ] **Mobile App** — React Native or Flutter companion app
- [ ] **AI Interaction Checker** — Flag dangerous drug combinations automatically
- [ ] **PDF Export** — Download adherence reports as PDFs for doctor visits
- [ ] **Multi-language Support** — i18n for regional languages
- [ ] **Two-Factor Authentication** — TOTP/OTP support for enhanced security
- [ ] **Admin Panel** — Site-wide user and subscription management dashboard
- [ ] **Appointment Reminders** — Calendar integration for doctor appointments
- [ ] **Inventory Tracker** — Track remaining pill count and alert when low

---

## 📋 Release Notes

### v1.0.0 — Initial Release
**Release Date:** July 2026

#### What's Included
- Complete patient dashboard with daily medication schedule
- Add medicines with multiple daily reminders
- Doctor profile management with prescription gating
- Caregiver (nominee) account system with real-time adherence view
- 3-tier subscription system (Free / Basic / Premium) with 4-day free trial
- Secure login/registration with bcrypt password hashing
- Dark mode support across all pages
- Shared footer component with contributor credits
- Environment-based configuration via `.env`
- Full database schema with foreign keys and indexes

#### Known Limitations
- Email/SMS notifications are simulated (no live delivery)
- Subscription payment is a mock gateway (no real transactions)
- `settings.php` page linked in navbar is not yet implemented

---

## 👥 Contributors

This project was **collaboratively designed and developed** by:

<table>
  <tr>
    <td align="center">
      <strong>Tapash Kumar Das</strong><br>
      Full-Stack Developer & Project Lead<br>
      <a href="https://github.com/hack24aryan">github.com/hack24aryan</a>
    </td>
    <td align="center">
      <strong>Trisha Tarwey</strong><br>
      Full-Stack Developer & UI/UX Designer<br>
      <a href="https://github.com/TrishaTarwey">github.com/TrishaTarwey</a>
    </td>
  </tr>
</table>

> Both contributors share equal ownership and responsibility for this project.

---

## 📄 License

This project is licensed under the **MIT License**.
See the [LICENSE](./LICENSE) file for full details.

---

## 🙏 Acknowledgements

- [Font Awesome](https://fontawesome.com/) — Icon library
- [Google Fonts — Poppins](https://fonts.google.com/specimen/Poppins) — Typography
- [Chart.js](https://www.chartjs.org/) — Adherence charts on the dashboard
- [UI Avatars](https://ui-avatars.com/) — Dynamic profile pictures
- [XAMPP](https://www.apachefriends.org/) — Local development environment

---

##  🏷 Suggested GitHub Topics

```
php  mysql  healthcare  medicine-reminder  patient-management
caregiver  web-application  xampp  college-project  medication-tracker
```

---

<div align="center">
  <sub>Collaboratively Developed by <a href="https://github.com/hack24aryan">Tapash Kumar Das</a> & <a href="https://github.com/TrishaTarwey">Trisha Tarwey</a></sub><br>
  <sub>&copy; 2026 MediSync. All Rights Reserved.</sub>
</div>
