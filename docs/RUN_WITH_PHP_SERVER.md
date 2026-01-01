# Running with PHP Built-in Server

## Quick Start (No XAMPP/WAMP Required!)

You can run this project from **any directory** using PHP's built-in development server.

### Prerequisites
- PHP 7.4+ installed
- MySQL installed and running

### Step-by-Step Guide

#### 1. Check PHP Installation
```bash
php -v
```
Should show PHP 7.4 or higher.

#### 2. Start MySQL
Make sure MySQL is running:
- **Windows**: Start MySQL service from Services
- **Mac**: `brew services start mysql` or MySQL Workbench
- **Linux**: `sudo systemctl start mysql`

#### 3. Create Database
```bash
mysql -u root -p
```
Then:
```sql
CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

#### 4. Import Database
```bash
cd /path/to/online-exam-system
mysql -u root -p exam_system < database_schema.sql
```

#### 5. Configure Application
Edit `config/config.php`:
```php
define('BASE_URL', 'http://localhost:8000');
```

Edit `config/database.php` (if you have a MySQL password):
```php
define('DB_PASS', 'your_mysql_password');
```

#### 6. Start PHP Server
```bash
cd /path/to/online-exam-system
php -S localhost:8000
```

You should see:
```
PHP 7.4.x Development Server (http://localhost:8000) started
```

#### 7. Access Application
Open your browser:
```
http://localhost:8000
```

**Login Credentials:**
- Admin: `admin` / `Admin@123`
- Student: `john_doe` / `Student@123`

### Common Commands

**Start Server:**
```bash
cd /path/to/online-exam-system
php -S localhost:8000
```

**Different Port:**
```bash
php -S localhost:3000
# Remember to update BASE_URL in config/config.php
```

**Stop Server:**
Press `Ctrl+C` in the terminal

**Check if Server is Running:**
```bash
# Open http://localhost:8000 in browser
# Or use curl:
curl http://localhost:8000
```

### Advantages of PHP Built-in Server

✅ **No Apache/XAMPP needed** - Just PHP and MySQL  
✅ **Run from any directory** - Keep projects organized  
✅ **Quick setup** - One command to start  
✅ **Multiple projects** - Use different ports (8000, 8001, etc.)  
✅ **Lightweight** - Minimal resource usage  

### Limitations

⚠️ **Development only** - Not for production use  
⚠️ **Single-threaded** - Handles one request at a time  
⚠️ **No .htaccess** - Apache-specific features won't work  
⚠️ **Basic features** - No advanced server configurations  

### Troubleshooting

**Port Already in Use:**
```bash
# Use a different port
php -S localhost:8001
# Update BASE_URL to http://localhost:8001
```

**MySQL Connection Error:**
```bash
# Check MySQL is running
mysql -u root -p -e "SELECT 1"

# Check credentials in config/database.php
```

**Can't Access from Browser:**
- Make sure server is running (check terminal)
- Verify URL matches: `http://localhost:8000`
- Check firewall settings

**Session Errors:**
```bash
# Make sure logs directory exists and is writable
mkdir -p logs
chmod 755 logs
```

### Example: Complete Setup

```bash
# 1. Navigate to project
cd ~/Downloads/online-exam-system

# 2. Create database
mysql -u root -p -e "CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 3. Import schema
mysql -u root -p exam_system < database_schema.sql

# 4. Start server
php -S localhost:8000

# 5. Open browser to http://localhost:8000
```

### Running Multiple Projects

You can run multiple PHP projects simultaneously on different ports:

```bash
# Terminal 1: Project A
cd ~/project-a
php -S localhost:8000

# Terminal 2: Project B
cd ~/project-b
php -S localhost:8001

# Terminal 3: This project
cd ~/online-exam-system
php -S localhost:8002
```

### Production Deployment

For production, use:
- Apache with mod_php
- Nginx with PHP-FPM
- Cloud platforms (AWS, DigitalOcean, etc.)

**Never use PHP built-in server in production!**

---

## Summary

The PHP built-in server is perfect for:
- ✅ Development and testing
- ✅ Quick demos
- ✅ Learning PHP
- ✅ Running projects without Apache

Just remember to:
1. Keep the terminal open while using the app
2. Update `BASE_URL` in config
3. Use proper server for production

**Happy coding! 🚀**

