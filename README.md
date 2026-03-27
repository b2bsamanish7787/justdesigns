# Just Designs — Full-Stack Portfolio Web Portal

A complete portfolio-style website with client and admin views, featuring image management, user authentication, and a subscription system.

---

## Features

### Frontend (Client View)
- **Home page** with responsive 3-column image grid (Bootstrap)
- **Load More** button — AJAX-powered (loads 50 images per click, no page reload)
- **Free & Premium** image types — premium images blurred for guests
- **Premium Page** with subscription prompt for non-subscribers
- **User Registration/Subscription** — generates a temporary password and sends email
- **Secure Login/Logout** — PHP sessions + `password_hash` (bcrypt)
- **Change Password** page (prompted on first login)
- **Image Detail Page** — large image view, gallery, metadata, Like button
- **Wishlist/Favorites** — logged-in users can like/save images (AJAX)
- Image **lazy loading** for performance

### Admin Panel (`/admin`)
- Secure admin login (separate session)
- **Dashboard** with stats (total images, users, likes)
- **Upload Images** — multiple files per entry, 5 MB limit, jpg/png/webp only, primary image selection
- **Manage Images** — list, filter, search, delete
- **Users** — list subscribers, toggle active status, delete

---

## Tech Stack

| Layer        | Technology                        |
|--------------|-----------------------------------|
| Backend      | PHP 8+ with PDO (prepared statements) |
| Database     | MySQL 5.7+ / MariaDB              |
| Frontend     | Bootstrap 5, Font Awesome 6       |
| Interactivity| jQuery 3 + AJAX                   |
| Uploads      | PHP `move_uploaded_file` + finfo  |

---

## Folder Structure

```
justdesigns/
├── index.php                 # Home page (gallery + load more)
├── premium.php               # Premium images page
├── image-detail.php          # Image detail + related gallery + likes
├── login.php                 # User login
├── register.php              # Subscription / registration
├── logout.php                # User logout
├── change-password.php       # Force-change temp password
├── wishlist.php              # Liked / saved images
├── database.sql              # MySQL schema + sample data
│
├── includes/
│   ├── db.php                # PDO database connection + constants
│   ├── functions.php         # Helper functions (auth, images, email…)
│   ├── header.php            # Shared HTML header + navbar
│   └── footer.php            # Shared HTML footer + scripts
│
├── admin/
│   ├── login.php             # Admin login
│   ├── logout.php            # Admin logout
│   ├── index.php             # Dashboard
│   ├── upload.php            # Upload images form
│   ├── manage-images.php     # List/delete images
│   ├── users.php             # Manage subscribers
│   └── includes/
│       ├── admin_header.php
│       └── admin_footer.php
│
├── ajax/
│   ├── load_more.php         # AJAX: load more images
│   └── like.php              # AJAX: like/unlike image
│
├── assets/
│   ├── css/style.css         # Custom styles + Bootstrap overrides
│   └── js/main.js            # jQuery scripts (lazy load, AJAX, etc.)
│
└── uploads/                  # Uploaded image files (auto-created)
```

---

## Installation & Setup

### 1. Requirements
- PHP 8.0+ with extensions: `pdo_mysql`, `fileinfo`, `gd`
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with `mod_rewrite` (for `.htaccess`)

### 2. Clone / Copy Files
```bash
git clone https://github.com/b2bsamanish7787/justdesigns.git
# Place in your web server root, e.g. /var/www/html/justdesigns
```

### 3. Create the Database
```bash
mysql -u root -p < database.sql
```
This creates the `justdesigns` database with all tables and sample data.

### 4. Configure Database Connection
Edit `includes/db.php` and update the credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'justdesigns');
define('DB_USER', 'root');       // ← your MySQL user
define('DB_PASS', '');           // ← your MySQL password
define('SITE_URL', 'http://localhost/justdesigns'); // ← your base URL
```

### 5. Permissions
```bash
chmod 755 uploads/
```

### 6. Access the Application

| URL                              | Description              |
|----------------------------------|--------------------------|
| `http://localhost/justdesigns/`  | Frontend home page       |
| `http://localhost/justdesigns/admin/` | Admin panel         |

**Default admin credentials:**
- Username: `admin`
- Password: `admin123`

> ⚠️ **Change the admin password immediately after setup!**

---

## Security Notes

- All database queries use **PDO prepared statements** (no SQL injection)
- Passwords hashed with **bcrypt** (`password_hash`)
- File uploads validated by **MIME type** (finfo) and extension whitelist
- **PHP execution blocked** in `uploads/` and `includes/` via `.htaccess`
- **CSRF tokens** on all state-changing forms
- Session IDs regenerated on login

---

## Optional: Enable SMTP Email

To send real emails (temporary passwords), install [PHPMailer](https://github.com/PHPMailer/PHPMailer) and update the `sendWelcomeEmail()` function in `includes/functions.php`.

```bash
composer require phpmailer/phpmailer
```
