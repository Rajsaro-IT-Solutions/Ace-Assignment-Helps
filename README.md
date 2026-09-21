# 🎓 Ace Assignment Helps (AAH)

A modern, full-featured web portal and management system for academic assignment assistance. Built with native PHP, MySQL (with an automatic JSON fallback database), modern responsive CSS, and dynamic role-based dashboards for **Students**, **Allocators**, and **Admins**.

---

## 📑 Table of Contents
1. [System Requirements](#system-requirements)
2. [Fixing "php: command not found"](#fixing-php-command-not-found)
3. [Running Locally](#running-locally)
   - [Method 1: Built-in PHP Server (Recommended)](#method-1-built-in-php-server-recommended)
   - [Method 2: Using XAMPP / WAMP / MAMP](#method-2-using-xampp--wamp--mamp)
   - [Method 3: Using Docker](#method-3-using-docker)
4. [Database Configuration & Migration](#database-configuration--migration)
5. [Default Test Login Credentials](#default-test-login-credentials)
6. [How to Deploy on Vercel](#how-to-deploy-on-vercel)
7. [How to Deploy on Hostinger](#how-to-deploy-on-hostinger)
8. [Project Structure](#project-structure)
9. [Troubleshooting & FAQs](#troubleshooting--faqs)

---

## 💻 System Requirements

- **PHP**: Version 8.0 or higher (PHP 8.2+ recommended)
- **PHP Extensions**: `php-pdo`, `php-mysql`, `php-mbstring`, `php-curl`, `php-json`
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB (optional for local testing; an automatic offline JSON fallback is included)
- **Web Server** (Optional for production): Apache (with `mod_rewrite`) or Nginx

---

## ⚠️ Fixing "php: command not found"

If you see `php: command not found` when trying to run a command:

### 1. If PHP is not yet installed:
- **Ubuntu / Debian / Linux Mint:**
  ```bash
  sudo apt update
  sudo apt install -y php php-cli php-mysql php-mbstring php-curl php-json
  ```
- **macOS (using Homebrew):**
  ```bash
  brew install php
  ```
- **Windows:**
  - **Option A (Easiest):** Download and install [XAMPP](https://www.apachefriends.org/download.html). It includes PHP, Apache, and MySQL.
  - **Option B (Manual):** Download PHP from [windows.php.net](https://windows.php.net/download/) and add your PHP installation directory (e.g., `C:\xampp\php` or `C:\php`) to your system's **Environment Variables (`PATH`)**.

### 2. If you see `^[[200~` or weird symbols in your terminal:
This is caused by **bracketed paste mode** in some terminal emulators when pasting text.
- Type the command manually or use `Ctrl + Shift + V` instead of right-clicking.
- Verify PHP is working by typing:
  ```bash
  php -v
  ```

---

## 🚀 Running Locally

### Method 1: Built-in PHP Server (Recommended)

This is the fastest method to run the website locally without setting up Apache or Nginx.

1. **Open your terminal** and navigate to the project directory:
   ```bash
   cd /path/to/Ace-Assignment-Helps
   ```

2. **Start the PHP development server**:
   ```bash
   php -S localhost:8000 api/index.php
   ```
   > **Note:** We specify `api/index.php` because it serves as the built-in front controller and router for clean URLs and static files.

3. **Open your browser** and visit:
   ```
   http://localhost:8000
   ```

4. **To stop the server**, press `Ctrl + C` in your terminal.

---

### Method 2: Using XAMPP / WAMP / MAMP

If you prefer using a local Apache stack:

1. **Move or copy the project folder** into your server's web root directory:
   - **XAMPP (Windows):** `C:\xampp\htdocs\AAH`
   - **XAMPP (Linux):** `/opt/lampp/htdocs/AAH`
   - **MAMP (macOS):** `/Applications/MAMP/htdocs/AAH`

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Access the application** in your browser:
   ```
   http://localhost/AAH/
   ```
   *(The included `.htaccess` file handles clean URL routing automatically under Apache).*

---

### Method 3: Using Docker

If you have Docker installed and don't want to install PHP directly:

```bash
docker run -it --rm -p 8000:8000 -v "$(pwd)":/app -w /app php:8.3-cli php -S 0.0.0.0:8000 api/index.php
```
Then visit `http://localhost:8000`.

---

## 🗄️ Database Configuration & Migration

The website includes **dual database support**:
- **AWS RDS MySQL / Custom MySQL**: Used whenever reachable.
- **Local JSON Database Fallback** (`data/database.json`): Automatically engages if MySQL is unreachable or credentials are not configured, ensuring the website never crashes with a 500 error during local development.

### Configuring Database Credentials:
Open `includes/db.php` to configure your database connection:

```php
private static $dbHost = 'localhost'; // Or AWS RDS / Hostinger DB host
private static $dbPort = 3306;
private static $dbUser = 'your_db_user';
private static $dbPass = 'your_db_password';
private static $dbName = 'aceassignmenthelp_db';
```

### Running Schema & Seed Migration:
If you want to set up the tables and initial seed data in MySQL, run:
```bash
php setup_mysql.php
```

---

## 🔑 Default Test Login Credentials

You can test the system using the pre-seeded accounts:

| Role | Email Address | Default Password | Portal URL |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@aceassign.com` | `password` | `/login.php` &rarr; `/admin/index.php` |
| **Allocator** | `allocator@aceassign.com` | `password` | `/login.php` &rarr; `/allocator/index.php` |
| **Student** | `sarah.jenkins@stanford.edu` | `password` | `/login.php` &rarr; `/student/index.php` |

*(You can also register a new student account at `/register.php`)*.

---

## ⚡ How to Deploy on Vercel

The project is already configured with `vercel.json` for deployment with the Serverless PHP runtime (`vercel-php`).

### Option A: Via GitHub (Recommended)

1. **Push your repository to GitHub**:
   ```bash
   git add .
   git commit -m "Prepare for deployment"
   git push origin main
   ```

2. **Go to [Vercel.com](https://vercel.com/)** and sign in.
3. Click **"Add New..."** &rarr; **"Project"**.
4. Select and import your GitHub repository (`Ace-Assignment-Helps`).
5. In the project configuration:
   - **Framework Preset**: Leave as **Other**.
   - **Root Directory**: Leave as `./`.
   - **Build & Output Settings**: Leave empty (defaults).
6. Click **Deploy**.

> **Note on Vercel Serverless Architecture:**
> Vercel functions have a read-only filesystem (except `/tmp`). The site connects directly to the remote AWS RDS MySQL database specified in `includes/db.php`.

### Option B: Via Vercel CLI

1. Install Vercel CLI:
   ```bash
   npm install -g vercel
   ```
2. In the project folder, run:
   ```bash
   vercel
   ```
3. Follow the CLI prompts to link and deploy your project.
4. For production deployment:
   ```bash
   vercel --prod
   ```

---

## 🌐 How to Deploy on Hostinger

Hostinger provides standard Apache-based shared/cloud web hosting with full MySQL and PHP support.

### Step 1: Prepare Your Files
Create a `.zip` file of the project root directory (ensure you include all files, including hidden files like `.htaccess`).

### Step 2: Upload Files via Hostinger File Manager
1. Log in to your **Hostinger hPanel**.
2. Navigate to **Websites** &rarr; click **Manage** next to your domain.
3. In the sidebar, search for **File Manager** and open it.
4. Open the `public_html` directory.
5. Upload your `.zip` archive and **Extract** it directly into `public_html`.
   *(Make sure files like `index.php` and `.htaccess` are directly inside `public_html`, not inside a nested subfolder).*

### Step 3: Create MySQL Database in Hostinger
1. In hPanel, navigate to **Databases** &rarr; **MySQL Databases**.
2. Create a new database:
   - **MySQL Database Name**: e.g., `u123456789_ace`
   - **MySQL Username**: e.g., `u123456789_admin`
   - **Password**: Choose a strong password and save it.
3. Click **Create**.

### Step 4: Import Database Tables & Data
1. In the same **MySQL Databases** page, click **Enter phpMyAdmin** next to your newly created database.
2. Either:
   - Click **Import** and upload your exported `.sql` file, OR
   - Open the **SQL** tab and run the table creation queries from `setup_mysql.php`.

### Step 5: Update Database Credentials in `includes/db.php`
1. In Hostinger **File Manager**, open `public_html/includes/db.php`.
2. Update the credentials with your Hostinger database details:
   ```php
   private static $dbHost = 'localhost'; // 'localhost' is standard on Hostinger
   private static $dbPort = 3306;
   private static $dbUser = 'u123456789_admin';
   private static $dbPass = 'your_hostinger_db_password';
   private static $dbName = 'u123456789_ace';
   ```
3. Save the file.

### Step 6: Verify PHP Version & Extensions
1. In hPanel, go to **Advanced** &rarr; **PHP Configuration**.
2. Under **PHP Version**, select **PHP 8.2** or **PHP 8.3** and click **Update**.
3. Under **PHP Extensions**, ensure `pdo_mysql`, `mbstring`, `curl`, and `json` are enabled.

### Step 7: Verify `.htaccess`
Hostinger uses Apache. The included `.htaccess` file in the root directory:
- Protects sensitive files (`setup_mysql.php`, `data/database.json`, `.git`, `.env`).
- Automatically routes clean URLs (e.g., `domain.com/about` &rarr; `about.php`).
- Ensures proper MIME types for web fonts and assets.

### Step 8: Permissions
Ensure standard Linux permissions are applied:
- Folders: `755`
- Files: `644`
- If using the JSON fallback without MySQL, ensure the `data/` folder and `data/database.json` have write permissions (`775` or `664`).

---

## 📁 Project Structure

```text
├── .htaccess             # Apache rewrite and security configurations
├── about.php             # About Us page
├── admin/                # Admin Portal (orders, allocations, users, audit logs)
├── allocator/            # Allocator Portal (expert matching, assignment management)
├── student/              # Student Portal (order tracking, ticket submission, profiles)
├── api/
│   └── index.php         # Front router for Vercel & PHP built-in server
├── assets/
│   ├── css/              # Application stylesheets
│   ├── js/               # Frontend scripts
│   └── image/            # Brand logos and banners
├── data/
│   └── database.json     # Offline JSON database fallback
├── includes/
│   ├── auth.php          # Session & authentication handler
│   ├── db.php            # PDO MySQL connection & JSON fallback engine
│   ├── header.php        # Global header & navigation
│   ├── footer.php        # Global footer
│   └── helpers.php       # Utility functions & business logic
├── login.php             # Unified portal login page
├── register.php          # Student registration page
├── setup_mysql.php       # MySQL database migration & seed script
└── vercel.json           # Vercel serverless deployment config
```

---

## ❓ Troubleshooting & FAQs

#### Q: The page displays a 404 when navigating to `/about` or `/services` on local server.
**A:** Make sure you start the PHP server specifying `api/index.php` as the router:
```bash
php -S localhost:8000 api/index.php
```

#### Q: I get `php: command not found`.
**A:** Follow the instructions in the [Fixing "php: command not found"](#fixing-php-command-not-found) section to install PHP on your operating system or add it to your PATH.

#### Q: How can I change the admin or student credentials?
**A:** In the portal, log in as Admin (`admin@aceassign.com` / `password`), go to **Users**, and edit or add users. Alternatively, update `data/database.json` or your MySQL database.

---

&copy; 2026 Ace Assignment Helps. All Rights Reserved.
