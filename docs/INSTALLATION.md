# Quick Installation Guide

## Prerequisites
- XAMPP, WAMP, MAMP, or LAMP stack installed
- PHP 7.4 or higher
- MySQL 5.7 or higher

## Installation Steps

### 1. Extract Files

**Option A - Web Server Directory (Recommended for Beginners):**
Extract the project folder to your web server directory:
- **XAMPP**: `C:\xampp\htdocs\online-exam-system`
- **WAMP**: `C:\wamp64\www\online-exam-system`
- **MAMP**: `/Applications/MAMP/htdocs/online-exam-system`
- **Linux**: `/var/www/html/online-exam-system`

**Option B - Any Directory (Using PHP Built-in Server):**
Extract to any location on your computer:
```bash
# Extract to any folder, e.g.:
# Windows: C:\Projects\online-exam-system
# Mac/Linux: ~/Projects/online-exam-system
```
You'll use PHP's built-in server (see Step 5B below)

### 2. Create Database
Open phpMyAdmin or MySQL command line and run:

```sql
CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Import Database
**Option A - Using phpMyAdmin:**
1. Open phpMyAdmin
2. Select `exam_system` database
3. Click "Import" tab
4. Choose file: `database_schema.sql`
5. Click "Go"

**Option B - Using Command Line:**
```bash
mysql -u root -p exam_system < database_schema.sql
```

### 4. Configure Application

**For Option A (Web Server Directory):**
Edit `config/config.php` and update:
```php
define('BASE_URL', 'http://localhost/online-exam-system');
```

**For Option B (Any Directory with PHP Server):**
Edit `config/config.php` and update:
```php
define('BASE_URL', 'http://localhost:8000');
```

**Database Password (Both Options):**
If your database password is not empty, edit `config/database.php`:
```php
define('DB_PASS', 'your_password_here');
```

### 5. Start Server

**Option A - Using XAMPP/WAMP/MAMP:**
- Start Apache and MySQL from control panel
- Ensure both services are running (green indicators)

**Option B - Using PHP Built-in Server:**
```bash
# Start MySQL (required for both options)
# Then in terminal/command prompt:
cd /path/to/online-exam-system
php -S localhost:8000
```
Keep this terminal window open while using the application.

### 6. Access Application

**Option A:**
```
http://localhost/online-exam-system
```

**Option B:**
```
http://localhost:8000
```

### 7. Login
**Admin:**
- Username: `admin`
- Password: `Admin@123`

**Student:**
- Username: `john_doe`
- Password: `Student@123`

Or register a new student account!

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database `exam_system` exists

### 404 Not Found
- **Option A**: Check if files are in correct directory (htdocs/www)
- **Option A**: Verify Apache is running
- **Option B**: Ensure PHP built-in server is running (`php -S localhost:8000`)
- Check `BASE_URL` in `config/config.php` matches your setup

### Session Errors
- Ensure PHP session extension is enabled
- Check if `logs` folder is writable

### Permission Issues (Linux/Mac)
```bash
chmod -R 755 /path/to/online-exam-system
chmod -R 777 /path/to/online-exam-system/logs
```

## What's Included

✅ Complete database with sample data  
✅ 1 Admin account  
✅ 3 Student accounts  
✅ 1 Sample exam with 7 questions  
✅ All 5 question types demonstrated  
✅ Security features enabled  
✅ Anti-cheating system active  

## Next Steps

1. **As Admin:**
   - Create new exams
   - Add questions
   - Publish exams
   - Monitor results

2. **As Student:**
   - Take available exams
   - View results
   - Track performance

## Which Option Should You Choose?

### Use Option A (Web Server Directory) if:
- ✅ You're new to PHP development
- ✅ You want the simplest setup
- ✅ You're already using XAMPP/WAMP/MAMP
- ✅ You need Apache-specific features (.htaccess)

### Use Option B (PHP Built-in Server) if:
- ✅ You want to keep projects organized in your own folders
- ✅ You're comfortable with command line
- ✅ You want quick testing without Apache
- ✅ You're developing on the go

**Note**: PHP built-in server is great for development but not recommended for production. For production, use Apache/Nginx.

## Support

Refer to `README.md` for comprehensive documentation.

---

**Installation complete! 🎉**

