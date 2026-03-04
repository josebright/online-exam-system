# Online Examination System

A secure, full-featured online examination platform built with PHP and MySQL.

## 📖 Documentation

**→ [Technical Overview](./docs/PROJECT_OVERVIEW.md)** — Comprehensive documentation for developers and maintainers

| Document | Description |
|----------|-------------|
| [PROJECT_OVERVIEW.md](./docs/PROJECT_OVERVIEW.md) | Complete technical reference, architecture, and implementation details |
| [INSTALLATION.md](./docs/INSTALLATION.md) | Detailed setup instructions |
| [FEATURES.md](./docs/FEATURES.md) | Feature checklist |
| [ERD.md](./docs/ERD.md) | Database schema and entity relationships |
| [CREDENTIALS.txt](./docs/CREDENTIALS.txt) | All test accounts |

---

## ✨ Features

- **5 Question Types**: Multiple Choice, Multiple Select, True/False, Short Answer, Fill in the Blank
- **Real-time Exam**: Server-synced timer, auto-save every 30 seconds, auto-submit on expiry
- **Anti-Cheating**: Tab switch detection and logging
- **Security**: CSRF protection, XSS prevention, SQL injection protection, rate limiting
- **Analytics**: Performance dashboards for admins with exam and student statistics

---

## 🛠 Tech Stack

| Component | Version |
|-----------|---------|
| PHP | 7.4+ |
| MySQL | 5.7+ |
| Bootstrap | 5.3.2 |
| jQuery | 3.7.1 |

---

## 🚀 Quick Start

### 1. Database Setup

```sql
CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
mysql -u root -p exam_system < database_schema.sql
```

### 2. Configuration

Copy `.env.example` to `.env` and update:

```ini
BASE_URL="http://localhost/online-exam-system"
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="exam_system"
DB_USER="root"
DB_PASS="your_password"
DB_CHARSET="utf8mb4"
```

### 3. Run

**Option A - Web Server (XAMPP/WAMP/MAMP):**
```
http://localhost/online-exam-system
```

**Option B - PHP Built-in Server:**
```bash
php -S localhost:8000
# Visit http://localhost:8000
```

See [docs/RUN_WITH_PHP_SERVER.md](./docs/RUN_WITH_PHP_SERVER.md) for details.

---

## 🔑 Test Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | Admin@123 |
| Student | john_doe | Student@123 |
| Student | jane_smith | Student@123 |
| Student | mike_wilson | Student@123 |

Full list: [docs/CREDENTIALS.txt](./docs/CREDENTIALS.txt)

---

## 📁 Project Structure

```
online-exam-system/
├── admin/          # Admin panel (exams, questions, students, analytics)
├── student/        # Student portal (dashboard, exams, results)
├── config/         # Configuration (env, database)
├── includes/       # Shared PHP modules (security, auth, exam logic)
├── assets/         # Static files (CSS)
├── docs/           # Documentation
├── login.php       # Authentication
├── register.php    # Student registration
└── database_schema.sql
```

---

## 📄 License

Developed for assessment purposes.

---

For comprehensive documentation, see **[docs/PROJECT_OVERVIEW.md](./docs/PROJECT_OVERVIEW.md)**.
