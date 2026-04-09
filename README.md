# Design Portal – Project Management System

A complete, secure web portal for a design team's project management, built with **PHP (core)**, **MySQL**, **Bootstrap 5**, **jQuery**, and **AJAX**.

## Features

### Client View (Public)
- No login required – accessible at `/`
- Live project table with search, sort, and server-side pagination
- Color-coded status badges (Pending/Ongoing/Completed)
- Remaining days display; rows highlighted red when deadline < 3 days

### Admin Panel (`/admin`)
- Secure login with bcrypt password hashing and 30-minute session timeout
- Dashboard stats: Total / Ongoing / Completed / Pending
- Full CRUD project management via Bootstrap 5 modals + AJAX
- Assign multiple designers per project
- Live search with debounce, sortable columns, paginated table
- Toast notifications for all actions

### Security
- CSRF tokens on all forms
- PDO prepared statements (no raw queries)
- `htmlspecialchars` output escaping on all user data
- SQL injection prevention via ORDER BY column whitelist
- Session fixation prevention (`session_regenerate_id`)
- Session timeout after 30 minutes of inactivity

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Web server (Apache / Nginx) with URL rewriting enabled
- PHP extensions: `pdo`, `pdo_mysql`

## Installation

### 1. Clone / Download
Place the project files in your web server's document root (e.g. `/var/www/html/design-portal`).

### 2. Configure Database
Edit `config/database.php` and set your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'design_portal');
```

### 3. Import Database Schema
```bash
mysql -u root -p < database/schema.sql
```
This creates the database, tables, and inserts sample data plus a default admin account.

### 4. Access the Application
| URL | Description |
|-----|-------------|
| `http://yoursite.com/` | Public client dashboard |
| `http://yoursite.com/admin/` | Admin login |

### Default Admin Credentials
| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `Admin@1234` |

> **Change the password immediately after first login in production.**

## Directory Structure
```
design-portal/
├── config/
│   └── database.php          Database connection config
├── includes/
│   ├── auth.php               Login/logout/session auth helpers
│   ├── csrf.php               CSRF token generation & validation
│   ├── functions.php          Project CRUD & helper functions
│   └── session.php            Secure session management
├── admin/
│   ├── index.php              Admin dashboard
│   ├── login.php              Admin login page
│   ├── logout.php             Session destroy
│   └── ajax/                  Protected AJAX endpoints
│       ├── get_projects.php
│       ├── get_project.php
│       ├── add_project.php
│       ├── edit_project.php
│       └── delete_project.php
├── ajax/
│   └── get_projects.php       Public read-only AJAX endpoint
├── assets/
│   ├── css/style.css          Custom styles
│   └── js/
│       ├── admin.js           Admin panel JavaScript
│       └── client.js          Client view JavaScript
├── database/
│   └── schema.sql             Full database schema + sample data
└── index.php                  Public client view
```

## Tech Stack
- **Backend**: PHP 8 (core, no frameworks)
- **Database**: MySQL with PDO
- **Frontend**: Bootstrap 5.3, Bootstrap Icons, jQuery 3.7
- **UI Pattern**: Admin sidebar layout + public hero dashboard
