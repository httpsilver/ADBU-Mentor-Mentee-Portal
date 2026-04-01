# 🎓 ADBU MentorConnect : Mentor-Mentee Management Portal

A full-featured, responsive web application designed to manage mentor–mentee relationships in academic institutions. The system provides secure authentication, role-based dashboards, real-time communication, and administrative controls.

---

## 🚀 Tech Stack

| Layer    | Technology                             |
| -------- | -------------------------------------- |
| Frontend | HTML5, CSS3, Bootstrap 5.3, JavaScript |
| Backend  | PHP 8.x                                |
| Database | MySQL 8                                |
| Icons    | Bootstrap Icons 1.11                   |
| Fonts    | Google Fonts – Inter                   |

---

## 📁 Project Structure

```
mentor-portal/
├── index.html
├── database.sql
├── css/
│   └── styles.css
├── js/
│   └── app.js
├── php/
│   ├── config.php
│   ├── auth.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── messages.php
│   ├── admin.php
│   └── profile.php
└── pages/
    ├── register.html
    ├── forgot-password.html
    ├── sidebar_nav.php
    ├── admin-dashboard.php
    ├── manage-users.php
    ├── assign-mentors.php
    ├── view-assignments.php
    ├── settings.php
    ├── mentor-dashboard.php
    ├── my-mentees.php
    ├── mentee-dashboard.php
    ├── my-mentor.php
    ├── chat.php
    └── profile.php
```

---

## ⚙️ Installation Guide

### 1️⃣ Prerequisites

* PHP 8.0+
* MySQL 8.0+
* Apache/Nginx (XAMPP/WAMP/Laragon recommended)
* Web browser (Chrome recommended)
* Git (optional)

---

### 2️⃣ Clone Repository

```bash
git clone https://github.com/yourusername/mentor-portal.git
```

---

### 3️⃣ Database Setup

Option 1 (CLI):

```bash
mysql -u root -p < database.sql
```

Option 2:

* Open phpMyAdmin
* Create database: `mentor_portal`
* Import `database.sql`

---

### 4️⃣ Configure Database , hcaptcha and smtp server

Edit `php/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'mentor_portal');
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_ENCRYPTION', 'tls');

define('SMTP_USER',       'youremailserver@gmail.com');
define('SMTP_PASS',       'app_password_here'); 
define('MAIL_FROM',       'youremailserver@gmail.com');
define('MAIL_FROM_NAME',  'ADBU MentorConnect');
define('SMTP_CONFIGURED', SMTP_USER !== 'ENTER_YOUR_GMAIL@gmail.com');

define('HCAPTCHA_SECRET',   'secret_key_here');
define('HCAPTCHA_SITE_KEY', 'site_key_here');
define('CAPTCHA_ENABLED',   true);
```

---

### 5️⃣ Run the Application

* Move project folder to:

  * `htdocs/` (XAMPP)
  * `www/` (WAMP)

* Start Apache & MySQL

* Open in browser:

```
http://localhost/mentor-portal/
```

---

## 🎥 Video Demonstration

👉 **Watch the Project Demo:**
https://youtu.be/nrt7kcxxgdk?si=Y3yXIXFaCPGh7SV1

---

## 🔐 Demo Credentials

> ⚠️ For testing purposes only — change before deployment.

| Role   | Email                                                           | Password |
| ------ | --------------------------------------------------------------- | -------- |
| Admin  | [admin@mentorportal.com](mailto:admin@mentorportal.com)         | password |
| Mentor | [sarah.j@mentorportal.com](mailto:sarah.j@mentorportal.com)     | password |
| Mentor | [michael.c@mentorportal.com](mailto:michael.c@mentorportal.com) | password |
| Mentee | [alex.t@mentorportal.com](mailto:alex.t@mentorportal.com)       | password |
| Mentee | [jamie.w@mentorportal.com](mailto:jamie.w@mentorportal.com)     | password |

---

## ✨ Key Features

### 🔐 Authentication & Security

* Secure login with password hashing (bcrypt)
* Role-based access control
* Session timeout handling

### 👥 Admin Dashboard

* User management (CRUD operations)
* Mentor–mentee assignment system
* Real-time statistics dashboard

### 💬 Messaging System

* Slack-style chat interface
* Real-time polling (3 seconds)
* Private conversations
* Unread message tracking

### 📱 Responsive Design

* Fully responsive (Desktop, Tablet, Mobile)
* Collapsible sidebar navigation
* Mobile-optimized chat interface

### 🎨 UI/UX Design

* Light/Dark theme toggle
* Modern design system using CSS variables
* Consistent spacing and typography

---

## 🔒 Security Features

* PDO prepared statements (prevents SQL injection)
* XSS protection (`htmlspecialchars()`)
* Secure session handling
* Role-based API protection
* File upload validation

---

## 🛠️ Customization

### Change Theme Color

```css
:root { --accent: #4F46E5; }
[data-theme="dark"] { --accent: #6366F1; }
```

### Modify Navigation

Edit:

```
pages/sidebar_nav.php
```

### Extend Backend APIs

Modify:

```
php/messages.php
```

---

## 📌 Future Improvements

* WebSocket-based real-time chat
* Email notifications
* Advanced analytics dashboard
* Mobile app integration

---

## 👨‍💻 Author

Silverina Nongbri

Student Project Submission

---
