# RTS Ticket System - Setup & Run Instructions

## Quick Start

### 1. Database Setup
```bash
# Create the database (you'll need MySQL root password)
mysql -u root -p < setup_database.sql
```

**Default Credentials:**
- Admin: `admin@rts.com` / `admin123`
- Student: `john@rts.com` / `admin123`
- Trainer: `sarah@rts.com` / `admin123`
- Intern: `mike@rts.com` / `admin123`

### 2. Configure Database (if needed)
Edit `config.php` and update these lines if your MySQL settings differ:
```php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = ""; // your MySQL password
$DB_NAME = "rts_ticket_system";
```

### 3. Start the Server
```bash
php -S localhost:8000
```

### 4. Access the Application
Open your browser and navigate to:
- **Main App:** http://localhost:8000
- **Login:** http://localhost:8000/login.php
- **Register:** http://localhost:8000/register.php
- **Admin Panel:** http://localhost:8000/admin_panel.php (admin only)

## Features

- ✅ User Registration with Admin Approval
- ✅ Role-Based Access Control (Admin, Student, Trainer, Intern, Management)
- ✅ Ticket Creation & Management
- ✅ Real-time Ticket Status Tracking
- ✅ Live Team Chat System
- ✅ Facial Recognition for Admin Login (optional)
- ✅ Admin User Management Panel
- ✅ Chat History Archival

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser
- (Optional) Webcam for facial recognition

## Notes

- The facial recognition feature requires an external API at `https://192.168.1.15:5173/recognize`
- Chat logs are stored in `/system/Ch@tr@@m/` directory
- Change default passwords in production!
