🚌 Smart Bus Management System

A web-based system built with PHP, MySQL, HTML, CSS, and JavaScript for managing buses, routes, schedules, and users. It provides features for both administrators and passengers to improve efficiency and transparency in bus operations.

🚀 Features
👨‍💼 Admin Panel

User Management – Add, update, delete users and administrators.

Fleet Management – View all buses, add new buses, schedule maintenance.

Route Planning – Manage routes, bus stops, and optimize schedules.

Analytics Dashboard – View system stats (users, buses, routes, cities, alerts).

Before vs. After Optimization Charts – Compare current vs. optimized schedules.

System Alerts – Track and resolve alerts in real-time.

🧑‍💻 User Side

Registration & Login (with secure password hashing).

City & Route Selection.

View Available Buses.

Real-Time Simulation (in progress).

🗂️ Project Structure
Team Techys/
│── admin_dashboard.php    # Admin panel home
│── manage_users.php       # User management
│── fleet_mang.php         # Fleet management
│── route_plan.php         # Route planning
│── register.php           # User registration
│── login.php              # User login
│── dashboard.php          # User dashboard
│── under_construction.php # Placeholder page
│── config.php             # Database config
│── auth.php               # Authentication
│── smart_bus_system.sql   # Database schema
│── assets/                # CSS, JS, Images

🛠️ Tech Stack

Frontend: HTML5, CSS3, JavaScript, Chart.js

Backend: PHP (PDO, OOP approach)

Database: MySQL (XAMPP / phpMyAdmin)

Server: Apache (XAMPP / WAMP)

⚙️ Installation

Clone or download this repository into your server root directory (e.g., htdocs in XAMPP).

Import the provided smart_bus_system.sql file into phpMyAdmin.

Update config.php with your database credentials:

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_bus_system');
define('DB_USER', 'root');
define('DB_PASS', '');


Start Apache & MySQL in XAMPP.

Open the app in your browser:

http://localhost/Team Techys/

📊 Database Overview

Main tables include:

users – Stores registered users and admins.

buses – Fleet details.

routes – Bus routes.

cities – Active cities.

system_alerts – Alerts & logs.

user_sessions – Active user sessions.

🧩 Future Enhancements

Live chat support

Payment gateway integration

Mobile responsive app

👨‍👩‍👦 Team Techys

Developed as part of Hackovate Hackathon 🚀
