# Online Examination System - Technical Overview

> A comprehensive technical reference for developers, maintainers, and reviewers.

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Directory Structure](#directory-structure)
5. [Database Schema](#database-schema)
6. [Core Modules](#core-modules)
7. [Security Implementation](#security-implementation)
8. [Feature Details](#feature-details)
9. [Data Flow Diagrams](#data-flow-diagrams)
10. [API Endpoints](#api-endpoints)
11. [Configuration](#configuration)
12. [Deployment](#deployment)

---

## Executive Summary

The Online Examination System is a secure, full-featured web application for conducting online assessments. Built with PHP and MySQL, it provides role-based access for administrators and students with comprehensive exam management, real-time exam taking, automatic grading, and analytics capabilities.

### Key Highlights

- **5 Question Types**: Multiple Choice, Multiple Select, True/False, Short Answer, Fill in the Blank
- **Real-time Features**: Server-synced timer, auto-save, auto-submit
- **Security**: CSRF protection, XSS prevention, SQL injection protection, rate limiting
- **Anti-Cheating**: Tab switch detection, server-enforced time limits
- **Analytics**: Exam statistics, student performance tracking, pass rates

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Browser    │  │  Bootstrap   │  │  jQuery/JavaScript   │  │
│  │   (HTML5)    │  │   5.3.2      │  │      (ES6+)          │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVER LAYER                              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    PHP 7.4+ (Application)                 │  │
│  │  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────────┐ │  │
│  │  │ Security│  │  Auth   │  │  Exam   │  │   Config    │ │  │
│  │  │ Module  │  │ Module  │  │ Module  │  │   Module    │ │  │
│  │  └─────────┘  └─────────┘  └─────────┘  └─────────────┘ │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       DATABASE LAYER                             │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              MySQL 5.7+ (InnoDB Engine)                   │  │
│  │   8 Tables │ 2 Views │ 1 Stored Procedure │ 3 Triggers   │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Request Flow

```
User Request → Apache/PHP Server → Session Validation → CSRF Check
    → Input Sanitization → Business Logic → Database (Prepared Statements)
    → Response Generation → Output Encoding → Client Display
```

---

## Technology Stack

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Markup | HTML5 | - | Page structure |
| Styling | CSS3 | - | Custom styles |
| UI Framework | Bootstrap | 5.3.2 | Responsive design, components |
| Icons | Bootstrap Icons | 1.11.2 | UI iconography |
| Client Logic | JavaScript | ES6+ | Interactivity, timer, AJAX |
| DOM/AJAX | jQuery | 3.7.1 | Simplified DOM manipulation |
| Server | PHP | 7.4+ | Application logic |
| Database | MySQL | 5.7+/8.0+ | Data persistence |
| Web Server | Apache/Nginx | - | HTTP server |

**Design Decision**: No additional frameworks or libraries beyond requirements. Pure PHP without Composer dependencies for simplicity and portability.

---

## Directory Structure

```
online-exam-system/
│
├── admin/                          # Admin Panel
│   ├── includes/
│   │   ├── header.php             # Admin layout header
│   │   └── footer.php             # Admin layout footer
│   ├── dashboard.php              # Statistics overview
│   ├── exams.php                  # Exam CRUD operations
│   ├── questions.php              # Question management
│   ├── students.php               # Student management
│   ├── results.php                # Analytics dashboard
│   ├── view_attempt.php           # Detailed attempt view
│   └── settings.php               # System settings
│
├── student/                        # Student Portal
│   ├── includes/
│   │   ├── header.php             # Student layout header
│   │   └── footer.php             # Student layout footer
│   ├── dashboard.php              # Available exams
│   ├── exam_instructions.php      # Pre-exam instructions
│   ├── take_exam.php              # Exam interface
│   ├── view_result.php            # Result display
│   ├── my_results.php             # Results history
│   ├── profile.php                # User profile
│   ├── save_answers.php           # AJAX: Auto-save
│   ├── track_tab_switch.php       # AJAX: Anti-cheating
│   ├── submit_exam.php            # AJAX: Submission
│   └── get_exam_time.php          # AJAX: Timer sync
│
├── config/                         # Configuration
│   ├── config.php                 # Application settings
│   ├── database.php               # Database connection
│   ├── env.php                    # Environment loader
│   └── env_check.php              # Environment validator
│
├── includes/                       # Shared Modules
│   ├── security.php               # Security functions
│   ├── auth.php                   # User management
│   └── exam_functions.php         # Exam business logic
│
├── assets/                         # Static Assets
│   └── css/
│       └── main.css               # Custom styles
│
├── docs/                           # Documentation
│   ├── PROJECT_OVERVIEW.md        # This file
│   ├── INSTALLATION.md            # Setup guide
│   ├── RUN_WITH_PHP_SERVER.md     # Alternative setup
│   ├── FEATURES.md                # Feature checklist
│   ├── ERD.md                     # Database documentation
│   ├── UI_DESIGN_NOTES.md         # UI guidelines
│   ├── CREDENTIALS.txt            # Test accounts
│   └── PROJECT_STRUCTURE.txt      # File organization
│
├── index.php                       # Entry point
├── login.php                       # Authentication
├── register.php                   # Student registration
├── logout.php                      # Session termination
├── database_schema.sql            # Database setup
├── .env.example                   # Environment template
├── .htaccess                      # Apache config
└── README.md                      # Quick start guide
```

---

## Database Schema

### Entity Relationship Overview

```
USERS (1) ──────< (N) EXAMS (created_by)
  │                    │
  │                    │
  │ (1)           (1)  │
  │                    │
  ▼                    ▼
EXAM_ATTEMPTS (N) ──── (N) QUESTIONS
  │                         │
  │                         │
  │ (1)                (1)  │
  │                         │
  ▼                         ▼
STUDENT_ANSWERS (N) ─────  QUESTION_OPTIONS (N)
```

### Tables Summary

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | User accounts | id (UUID), username, email, password_hash, role |
| `exams` | Exam definitions | id, title, duration_minutes, passing_marks, status |
| `questions` | Exam questions | id, exam_id, question_type, marks |
| `question_options` | MCQ options | id, question_id, option_text, is_correct |
| `exam_attempts` | Student sessions | id, exam_id, student_id, score, tab_switches |
| `student_answers` | Responses | id, attempt_id, question_id, answer_text |
| `sessions` | Auth sessions | id, user_id, expires_at |
| `audit_logs` | Activity log | id, user_id, action, entity_type |

### Question Types

| Type | Storage | Grading |
|------|---------|---------|
| `multiple_choice` | Selected option_id | Automatic |
| `multiple_select` | Comma-separated option_ids | Automatic |
| `true_false` | Selected option_id (True/False) | Automatic |
| `short_answer` | Text in answer_text | Automatic (exact match) |
| `fill_blank` | Text in answer_text | Automatic (case-insensitive) |

---

## Core Modules

### 1. Security Module (`includes/security.php`)

**Functions:**
- `startSecureSession()` - Session initialization with timeout
- `generateCSRFToken()` / `verifyCSRFToken()` - CSRF protection
- `sanitizeInput()` - XSS prevention
- `hashPassword()` / `verifyPassword()` - Bcrypt hashing
- `checkLoginAttempts()` - Rate limiting
- `logActivity()` - Audit logging

### 2. Authentication Module (`includes/auth.php`)

**Functions:**
- `loginUser()` - Credential verification
- `createUser()` - Registration
- `getUserById()` / `getUserByUsername()` - User retrieval
- `saveSessionToDatabase()` - Session persistence
- `deleteSessionFromDatabase()` - Session cleanup

### 3. Exam Module (`includes/exam_functions.php`)

**Functions:**
- `createExam()` / `updateExam()` / `deleteExam()` - CRUD
- `getExamQuestions()` - Question retrieval (with shuffle support)
- `startExamAttempt()` - Attempt initialization
- `saveStudentAnswer()` - Answer persistence
- `submitExamAttempt()` - Grading and finalization
- `getRemainingTime()` - Server-side timer

---

## Security Implementation

### Authentication Flow

```
Login Request
    │
    ▼
Check Rate Limit (5 attempts/15 min)
    │
    ▼ (Allowed)
Verify Credentials (bcrypt)
    │
    ▼ (Valid)
Generate CSRF Token
    │
    ▼
Create Secure Session
    │
    ▼
Set HTTP-only Cookie
    │
    ▼
Redirect by Role
```

### Security Measures

| Threat | Protection |
|--------|------------|
| SQL Injection | Prepared statements with parameterized queries |
| XSS | `htmlspecialchars()` on all output |
| CSRF | Token verification on all state-changing requests |
| Session Hijacking | HTTP-only cookies, session regeneration |
| Brute Force | Rate limiting (5 attempts/15 min timeout) |
| Password Exposure | Bcrypt hashing (cost factor: 12) |

---

## Feature Details

### Exam Taking Flow

```
Student Dashboard
    │
    ▼
Click "Start Exam"
    │
    ▼
Verify Retake Policy
    │
    ▼
Show Instructions
    │
    ▼
Create Attempt (start_time recorded)
    │
    ▼
┌─────────────────────────────────┐
│         EXAM INTERFACE          │
│  ┌───────────────────────────┐  │
│  │    Timer (server-synced)  │  │
│  │    Auto-save (30s)        │  │
│  │    Tab detection active   │  │
│  │    Question navigation    │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
    │
    ▼
Submit (Manual or Auto)
    │
    ▼
Grade Answers (stored procedure)
    │
    ▼
Display Results
```

### Anti-Cheating System

1. **Tab Switch Detection**: JavaScript `visibilitychange` event
2. **Logging**: AJAX POST to `track_tab_switch.php`
3. **Warning**: Visual alert after threshold (5 switches)
4. **Termination**: Exam auto-submitted at threshold
5. **Admin Visibility**: Tab switch count in results view

### Timer Synchronization

- Client-side countdown for smooth display
- Server poll every 30 seconds for accuracy
- Server calculates: `start_time + duration - current_time`
- Auto-submit triggered when `remaining <= 0`

---

## Data Flow Diagrams

### Admin Exam Creation Flow

```
Login → Dashboard → Manage Exams → Create New Exam
  ↓
Fill Exam Details (title, duration, passing marks, retake, shuffle)
  ↓
Save Exam (Status: Draft)
  ↓
Add Questions (5 types supported)
  ↓
Publish Exam (Status: Published)
  ↓
Exam available to students
```

### Student Exam Flow

```
Login → Dashboard → Start Exam → Instructions
  ↓
Take Exam (timer, auto-save, tab detection)
  ↓
Submit (Manual or Auto)
  ↓
Grading → Results
```

---

## API Endpoints (AJAX)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `student/save_answers.php` | POST | Auto-save answers |
| `student/submit_exam.php` | POST | Submit and grade |
| `student/track_tab_switch.php` | POST | Log tab switches |
| `student/get_exam_time.php` | GET | Timer sync |

**Request Format**: Form data with CSRF token  
**Response Format**: JSON `{success: bool, message: string, data?: object}`

---

## Configuration

### Environment Variables (`.env`)

Copy `.env.example` to `.env` and configure:

```ini
# Application
APP_NAME="Online Examination System"
BASE_URL="http://localhost/online-exam-system"

# Database
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="exam_system"
DB_USER="root"
DB_PASS=""
DB_CHARSET="utf8mb4"

# Security
SESSION_LIFETIME=3600        # 1 hour
MAX_LOGIN_ATTEMPTS=5
LOGIN_TIMEOUT=900            # 15 minutes

# Exam Settings
MIN_EXAM_DURATION=5          # minutes
MAX_EXAM_DURATION=180        # minutes
AUTO_SAVE_INTERVAL=30        # seconds
TAB_SWITCH_WARNING_THRESHOLD=5
```

---

## Deployment

### Requirements

- PHP 7.4+ with mysqli extension
- MySQL 5.7+ or 8.0+
- Apache (with mod_rewrite) or Nginx
- HTTPS recommended for production

### Quick Start

1. Clone/extract to web server directory
2. Create database: `CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
3. Import schema: `mysql -u root -p exam_system < database_schema.sql`
4. Copy `.env.example` to `.env` and configure
5. Access via browser

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Disable `DISPLAY_ERRORS`
- [ ] Enable `SESSION_COOKIE_SECURE`
- [ ] Configure HTTPS
- [ ] Set strong database password
- [ ] Review file permissions

---

## Additional Resources

- [Installation Guide](./INSTALLATION.md)
- [Feature Checklist](./FEATURES.md)
- [Database ERD](./ERD.md)
- [Test Credentials](./CREDENTIALS.txt)

---

**Version**: 1.0.0  
**Last Updated**: March 2025
