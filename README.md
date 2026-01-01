# Online Examination System

A comprehensive, secure, and modern online examination platform built with PHP, MySQL, Bootstrap, jQuery, and vanilla JavaScript.

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation Guide](#installation-guide)
- [Database Setup](#database-setup)
- [Project Structure](#project-structure)
- [Features Implemented](#features-implemented)
- [Security Features](#security-features)
- [Login Credentials](#login-credentials)
- [Usage Guide](#usage-guide)
- [Database Schema](#database-schema)
- [Optional Bonuses Implemented](#optional-bonuses-implemented)

---

## ✨ Features

### Admin Features
- **Dashboard**: Overview of exams, students, and performance metrics
- **Exam Management**: Create, edit, delete, and publish exams
- **Question Management**: Support for 5 question types
- **Student Management**: View all students and their performance
- **Results & Analytics**: Comprehensive analytics dashboard with statistics
- **Security**: CSRF protection, XSS prevention, SQL injection protection

### Student Features
- **User Registration & Login**: Secure authentication system
- **Exam Instructions**: Clear instructions before starting exams
- **Exam Taking Interface**: 
  - Real-time countdown timer with backend synchronization (polling-based)
  - Auto-save functionality (every 30 seconds)
  - Question navigation
  - Progress tracking
  - Review all questions before submission
- **Auto-submit**: Automatic submission when time expires (server-enforced)
- **Results Viewing**: Detailed results with correct answers
- **Performance Dashboard**: Track personal performance metrics

### Security & Anti-Cheating
- **Tab Switch Detection**: Monitors and logs tab switches
- **Session Management**: Secure session handling with expiration
- **CSRF Protection**: Token-based CSRF protection on all forms
- **Input Validation**: Server-side and client-side validation
- **Password Security**: Bcrypt hashing with strength requirements
- **SQL Injection Prevention**: Prepared statements throughout

---

## 🛠 Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **HTML5** | - | Structure and markup |
| **CSS3** | - | Styling |
| **Bootstrap** | 5.3.2 (Latest) | UI framework and responsive design |
| **JavaScript** | ES6+ | Client-side interactivity |
| **jQuery** | 3.7.1 (Latest) | DOM manipulation and AJAX |
| **PHP** | 7.4+ | Server-side logic |
| **MySQL** | 5.7+ / 8.0+ | Database management |

**Note**: Only the technologies specified in the requirements are used. No additional frameworks or libraries.

---

## 💻 System Requirements

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: Version 7.4 or higher
- **MySQL**: Version 5.7 or higher
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)
- **Extensions**: PHP mysqli extension enabled

---

## 📦 Installation Guide

> **Quick Start**: See `docs/INSTALLATION.md` for detailed setup instructions  
> **PHP Server**: See `docs/RUN_WITH_PHP_SERVER.md` to run without XAMPP/WAMP  

### Step 1: Clone or Download

```bash
# Clone the repository or download and extract the ZIP file
cd /path/to/your/webserver/htdocs/
# Place the online-exam-system folder here
```

### Step 2: Configure Database Connection

Edit the database configuration file:

**File**: `config/database.php`

```php
define('DB_HOST', 'localhost');     // Your database host
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
define('DB_NAME', 'exam_system');   // Database name
```

### Step 3: Configure Application Settings

Edit the application configuration:

**File**: `config/config.php`

```php
define('BASE_URL', 'http://localhost/online-exam-system');
```

Update `BASE_URL` to match your server configuration.

---

## 🗄 Database Setup

### Option 1: Automatic Setup (Recommended)

1. Create a new database:

```sql
CREATE DATABASE exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:

```bash
mysql -u root -p exam_system < database_schema.sql
```

### Option 2: Manual Setup

1. Open phpMyAdmin or MySQL command line
2. Create a new database named `exam_system`
3. Copy and execute the SQL from `database_schema.sql`

### Database Features

The database includes:
- ✅ Complete schema with all tables
- ✅ Foreign key constraints
- ✅ Indexes for performance
- ✅ Views for analytics
- ✅ Stored procedures for calculations
- ✅ Triggers for automatic updates
- ✅ Sample data (1 admin, 3 students, 1 exam with questions)

---

## 📁 Project Structure

> **Detailed structure**: See `docs/PROJECT_STRUCTURE.txt`

```
online-exam-system/
│
├── admin/                      # Admin panel
│   ├── includes/              # Admin header/footer
│   ├── dashboard.php          # Admin dashboard
│   ├── exams.php              # Exam management
│   ├── questions.php          # Question management
│   ├── students.php           # Student management
│   ├── results.php            # Analytics dashboard
│   └── settings.php           # Settings
│
├── student/                    # Student portal
│   ├── includes/              # Student header/footer
│   ├── dashboard.php          # Student dashboard
│   ├── exam_instructions.php  # Exam instructions
│   ├── take_exam.php          # Exam interface
│   ├── view_result.php        # View results
│   ├── my_results.php         # Results history
│   ├── profile.php            # Student profile
│   ├── save_answers.php       # AJAX: Save answers
│   ├── track_tab_switch.php   # AJAX: Track tab switches
│   └── submit_exam.php        # AJAX: Submit exam
│
├── config/                     # Configuration files
│   ├── config.php             # Application config
│   └── database.php           # Database config
│
├── includes/                   # Shared includes
│   ├── security.php           # Security functions
│   ├── auth.php               # Authentication
│   └── exam_functions.php     # Exam-related functions
│
├── assets/                     # Static assets
│   ├── css/                   # Custom stylesheets
│   ├── js/                    # Custom JavaScript
│   └── images/                # Images
│
├── docs/                       # Documentation
│   ├── INSTALLATION.md        # Installation guide
│   ├── FEATURES.md            # Features checklist
│   ├── ERD.md                 # Database ERD
│   ├── CREDENTIALS.txt        # Login credentials
│   ├── SUMMARY.md             # Project summary
│   └── ... (more docs)
│
├── login.php                   # Login page
├── register.php                # Registration page
├── logout.php                  # Logout handler
├── index.php                   # Home page (redirects)
├── database_schema.sql         # Database schema
└── README.md                   # This file
```

---

## ✅ Features Implemented

### 1. Authentication & Authorization
- ✅ Secure login system with rate limiting
- ✅ Student registration with validation
- ✅ Role-based access control (Admin/Student)
- ✅ Session management with timeout
- ✅ Password hashing (bcrypt)

### 2. Exam Management (Admin)
- ✅ Create, edit, delete exams
- ✅ Set exam duration, passing marks
- ✅ Configure retake settings
- ✅ Publish/draft/close status
- ✅ Shuffle questions option

### 3. Question Management (Admin)
- ✅ **5 Question Types Supported**:
  1. **Multiple Choice** - Single correct answer
  2. **Multiple Select** - Multiple correct answers
  3. **True/False** - Binary choice
  4. **Short Answer** - Text-based answer
  5. **Fill in the Blank** - Single word/phrase answer
- ✅ Add, edit, delete questions
- ✅ Set marks per question
- ✅ Question ordering
- ✅ Option management

### 4. Exam Taking Flow (Student)
- ✅ Exam instructions page
- ✅ Timer starts on exam begin
- ✅ **Real-time timer with backend synchronization** (polling-based, server-enforced, manipulation-proof)
- ✅ Visual timer warnings (yellow at 5 min, red at 1 min)
- ✅ Auto-save every 30 seconds
- ✅ Question navigation with answered/unanswered indicators
- ✅ Progress tracking
- ✅ Review all questions before submission
- ✅ Auto-submit on time expiration (server-side enforcement)
- ✅ Manual submit option with confirmation
- ✅ Prevent accidental page leave

### 5. Results & Analytics
- ✅ Automatic grading for MCQ/True-False/Multiple Select
- ✅ Detailed result view with correct answers
- ✅ Score calculation and percentage
- ✅ Pass/fail status
- ✅ Admin analytics dashboard
- ✅ Exam-wise statistics
- ✅ Student performance tracking

### 6. Security Features
- ✅ CSRF token protection
- ✅ XSS prevention (input sanitization)
- ✅ SQL injection prevention (prepared statements)
- ✅ Password strength validation
- ✅ Session security
- ✅ Login rate limiting
- ✅ Audit logging
- ✅ Tab switch detection and termination
- ✅ IP address tracking per exam attempt

### 7. Real-time Features
- ✅ **Exam Timer Synchronization**: Polling-based sync with backend (server-enforced, manipulation-proof)
- ✅ **Timer Persistence**: Timer continues accurately even after page refresh
- ✅ **Auto-submit Enforcement**: Server automatically submits exams when time expires
- ✅ **Client-side Countdown**: Smooth local countdown with periodic backend validation

---

## 🔒 Security Features

### 1. Authentication Security
- Bcrypt password hashing (cost: 12)
- Password strength requirements (8+ chars, uppercase, lowercase, number, special char)
- Login rate limiting (5 attempts per 15 minutes)
- Session regeneration
- Session timeout (1 hour)

### 2. Input Validation & Sanitization
- Server-side validation on all inputs
- HTML special characters encoding
- SQL injection prevention via prepared statements
- Client-side validation for better UX

### 3. CSRF Protection
- Token generation and verification on all forms
- Token stored in session
- Hash comparison for security

### 4. Session Security
- HTTP-only cookies
- Secure session configuration
- Session expiration
- IP address tracking

### 5. Database Security
- Prepared statements throughout
- Foreign key constraints
- Proper indexing
- Audit logging

---

## 🔑 Login Credentials

> **Full credentials list**: See `docs/CREDENTIALS.txt`

### Admin Account
- **Username**: `admin`
- **Password**: `Admin@123`
- **Email**: admin@examportal.com

### Sample Student Accounts

| Username | Password | Email |
|----------|----------|-------|
| john_doe | Student@123 | john.doe@student.com |
| jane_smith | Student@123 | jane.smith@student.com |
| mike_wilson | Student@123 | mike.wilson@student.com |

**Note**: You can register new student accounts from the registration page.

---

## 📖 Usage Guide

### For Admin

1. **Login** with admin credentials
2. **Create an Exam**:
   - Go to "Manage Exams" → "Create New Exam"
   - Fill in exam details (title, description, duration, passing marks)
   - Configure settings (retake, shuffle, etc.)
   - Click "Create Exam"

3. **Add Questions**:
   - After creating exam, you'll be redirected to add questions
   - Select question type
   - Enter question text and marks
   - Add options (for MCQ/Multiple Select/True-False)
   - Mark correct answers
   - Click "Add Question"

4. **Publish Exam**:
   - Go to "Manage Exams"
   - Edit the exam
   - Change status to "Published"
   - Students can now see and take the exam

5. **View Results**:
   - Go to "Results & Analytics"
   - View overall statistics
   - Check exam-wise performance
   - Monitor individual attempts

### For Students

1. **Register** a new account or login
2. **View Available Exams** on dashboard
3. **Start Exam**:
   - Click "Start Exam"
   - Read instructions carefully
   - Click "I Understand, Start Exam"

4. **Take Exam**:
   - Answer questions
   - Use navigation to move between questions
   - Answers are auto-saved every 30 seconds
   - Watch the timer
   - Click "Submit Exam" when done

5. **View Results**:
   - After submission, view your score
   - See correct/incorrect answers
   - Check detailed breakdown

---

## 🔄 Project Flow

### System Overview

The Online Examination System follows a structured workflow for both administrators and students, ensuring secure and efficient exam management and delivery.

### Admin Workflow

#### 1. Authentication & Access
- Admin logs in with credentials
- System validates credentials and creates secure session
- Role-based access control ensures admin-only features are accessible

#### 2. Exam Creation Flow
```
Login → Dashboard → Manage Exams → Create New Exam
  ↓
Fill Exam Details:
  - Title & Description
  - Duration (minutes)
  - Passing Marks
  - Retake Settings
  - Shuffle Questions Option
  ↓
Save Exam (Status: Draft)
  ↓
Add Questions:
  - Select Question Type (5 types supported)
  - Enter Question Text
  - Set Marks
  - Add Options (for MCQ/True-False)
  - Mark Correct Answers
  - Save Question
  ↓
Repeat until all questions added
  ↓
Publish Exam (Status: Published)
  ↓
Exam becomes available to students
```

#### 3. Monitoring & Analytics
- View real-time exam attempts
- Monitor student performance
- Track tab switches (anti-cheating)
- Generate analytics reports
- View detailed attempt logs

### Student Workflow

#### 1. Registration & Authentication
```
Visit Registration Page
  ↓
Fill Registration Form:
  - Full Name
  - Username (unique)
  - Email (unique)
  - Password (strength validated)
  ↓
Account Created
  ↓
Login with Credentials
  ↓
Session Created → Redirected to Dashboard
```

#### 2. Exam Taking Flow
```
Student Dashboard
  ↓
View Available Exams (Published status)
  ↓
Click "Start Exam"
  ↓
Exam Instructions Page:
  - Read exam rules
  - Understand timer behavior
  - Review retake policy
  ↓
Click "I Understand, Start Exam"
  ↓
Exam Attempt Created:
  - Start time recorded
  - Timer initialized
  - Status: in_progress
  ↓
Take Exam Interface:
  - Timer countdown (server-synced)
  - Question navigation
  - Auto-save every 30 seconds
  - Tab switch detection active
  - Progress tracking
  ↓
Answer Questions:
  - Multiple Choice: Select one option
  - Multiple Select: Select multiple options
  - True/False: Select true or false
  - Short Answer: Type text response
  - Fill in Blank: Enter word/phrase
  ↓
Review All Questions (optional)
  ↓
Submit Exam (Manual or Auto):
  - Manual: Click "Submit Exam" button
  - Auto: Timer expires → Server auto-submits
  ↓
Grading Process:
  - Automatic grading for objective questions
  - Score calculation
  - Percentage calculation
  - Pass/Fail determination
  ↓
Results Page:
  - View score and percentage
  - See correct/incorrect answers
  - Review detailed breakdown
  - Check time taken
```

#### 3. Results & History
- View all exam attempts
- Check performance over time
- Review detailed results for each attempt
- Track progress and improvement

### Security Flow

#### Authentication Security
```
Login Attempt
  ↓
Validate Credentials
  ↓
Check Rate Limiting (5 attempts/15 min)
  ↓
Verify Password (bcrypt hash)
  ↓
Generate CSRF Token
  ↓
Create Secure Session:
  - HTTP-only cookie
  - Session expiration set
  - IP address tracked
  ↓
Redirect to Dashboard
```

#### Exam Security Flow
```
Exam Start
  ↓
Validate Session
  ↓
Check Exam Status (must be Published)
  ↓
Verify Retake Policy
  ↓
Create Secure Attempt:
  - IP address logged
  - User agent recorded
  - Start time set
  ↓
During Exam:
  - Tab switches tracked
  - Answers auto-saved
  - Timer synced with server
  ↓
Submit Validation:
  - Verify attempt ownership
  - Check time limits
  - Validate all answers
  ↓
Grading & Storage
```

### Data Flow Architecture

```
User Input → Validation Layer → Security Layer → Business Logic → Database
     ↑                                                                    ↓
     └────────────────── Response & Display ←────────────────────────────┘
```

1. **Input Layer**: User submits form/data
2. **Validation Layer**: Client-side and server-side validation
3. **Security Layer**: CSRF verification, XSS prevention, SQL injection protection
4. **Business Logic**: Process request, apply rules
5. **Database Layer**: Store/retrieve data with prepared statements
6. **Response Layer**: Format and return data
7. **Display Layer**: Render UI with sanitized output

### Real-time Features Flow

#### Timer Synchronization
```
Client Timer (JavaScript)
  ↓
Every 30 seconds: Poll server for actual time
  ↓
Server calculates: start_time + duration - current_time
  ↓
Return remaining time
  ↓
Client updates display
  ↓
If time <= 0: Trigger auto-submit
```

#### Auto-save Flow
```
User answers question
  ↓
Every 30 seconds (or on navigation):
  ↓
Collect all answers
  ↓
AJAX POST to save_answers.php
  ↓
Validate CSRF token
  ↓
Store in database (student_answers table)
  ↓
Return success/error
  ↓
Update UI indicator
```

#### Tab Switch Detection
```
User switches tab/window
  ↓
JavaScript visibilitychange event
  ↓
AJAX POST to track_tab_switch.php
  ↓
Increment tab_switches counter
  ↓
Store in database
  ↓
If count > threshold: Show warning
  ↓
Admin can view count in results
```

---

## 🗃 Database Schema

> **Detailed documentation**: See `docs/ERD.md`

### Main Tables

1. **users** - Stores admin and student accounts
2. **exams** - Exam configurations
3. **questions** - Exam questions
4. **question_options** - Answer options for MCQ/True-False
5. **exam_attempts** - Student exam sessions
6. **student_answers** - Student responses
7. **sessions** - Session management
8. **audit_logs** - Activity tracking

### Views

- **exam_statistics** - Aggregated exam stats
- **student_performance** - Student performance metrics

### Stored Procedures

- **calculate_exam_score** - Calculates final score and pass/fail status

---

## 🎁 Optional Bonuses Implemented

### ✅ 1. Basic Analytics Dashboard

**Location**: Admin → Results & Analytics

**Features**:
- Overall statistics (students, attempts, average score, pass rate)
- Exam-wise statistics with pass rates
- Recent exam attempts
- Visual progress bars
- Performance metrics
- Student-wise analytics

### ✅ 2. Anti-Cheating Logic

**Tab Switch Detection**:
- Monitors when student switches tabs/windows
- Counts and logs all tab switches
- Displays warning to student
- Stores count in database
- Admin can view tab switch count in results
- Alert after 3 tab switches

**Implementation**:
- JavaScript `visibilitychange` event listener
- AJAX call to server on each switch
- Database logging
- Visual warning to student

---

## 🚀 Additional Features

### Beyond Requirements

1. **Auto-save Functionality**: Answers saved every 30 seconds
2. **Question Navigator**: Visual navigation with answered/unanswered indicators
3. **Responsive Design**: Works on desktop, tablet, and mobile
4. **Modern UI**: Beautiful gradient design with smooth animations
5. **Progress Tracking**: Real-time progress indicators
6. **Audit Logging**: Complete activity tracking
7. **Session Security**: Advanced session management
8. **Password Strength Indicator**: Real-time password strength feedback
9. **Flash Messages**: User-friendly success/error notifications
10. **Pagination**: For large datasets

---

## 🔧 Configuration

### Exam Settings (config/config.php)

```php
define('SESSION_LIFETIME', 3600);              // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);               // 5 attempts
define('LOGIN_TIMEOUT', 900);                  // 15 minutes
define('MIN_EXAM_DURATION', 5);                // 5 minutes
define('MAX_EXAM_DURATION', 180);              // 3 hours
define('AUTO_SAVE_INTERVAL', 30);              // 30 seconds
define('TAB_SWITCH_WARNING_THRESHOLD', 5);     // 5 switches
```

---

## 🐛 Troubleshooting

### Common Issues

**1. Database Connection Error**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists

**2. Login Not Working**
- Clear browser cookies and cache
- Check if session directory is writable
- Verify PHP session extension is enabled

**3. Timer Not Working**
- Enable JavaScript in browser
- Check browser console for errors
- Ensure jQuery is loading properly

**4. Auto-save Not Working**
- Verify CSRF token is present
- Check server error logs

---

## 📝 Notes

- **Browser Compatibility**: Tested on Chrome, Firefox, Safari, Edge
- **Mobile Responsive**: Fully responsive design
- **Production Ready**: Includes error handling and logging
- **Scalable**: Can handle multiple concurrent users
- **Maintainable**: Clean code structure with comments

---

## 👨‍💻 Developer Information

**Developed for**: Online Exam System  
**Date**: December 2025  
**Version**: 1.0.0  
**Technologies**: HTML, CSS, Bootstrap 5.3.2, JavaScript, jQuery 3.7.1, PHP, MySQL

---

## 📄 License

This project is developed for assessment purposes.

---

## 🙏 Acknowledgments

- Bootstrap for the UI framework
- jQuery for DOM manipulation
- Bootstrap Icons for iconography
- PHP and MySQL communities

---

## 📚 Additional Documentation

All detailed documentation is available in the `docs/` folder:

- **docs/INSTALLATION.md** - Detailed installation guide
- **docs/RUN_WITH_PHP_SERVER.md** - Run without XAMPP/WAMP
- **docs/FEATURES.md** - Complete features checklist
- **docs/ERD.md** - Database schema and entity relationships
- **docs/CREDENTIALS.txt** - All login credentials for testing
- **docs/PROJECT_STRUCTURE.txt** - Detailed file structure and organization
- **docs/UI_DESIGN_NOTES.md** - UI design documentation

## 📞 Support

For any issues or questions, please refer to the documentation in the `docs/` folder or the code comments.

---

**End of Documentation**

