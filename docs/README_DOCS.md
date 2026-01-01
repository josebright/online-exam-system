# Documentation Files

This folder contains the documentation required by the Practical Test Instruction Guide.

## Required Documentation

### ✅ ERD.md
Database schema and entity relationship diagram. Describes all tables, relationships, and data structure.

### ✅ PROJECT_STRUCTURE.txt
Clear project structure showing file organization, directory layout, and complete project flow diagrams for both admin and student workflows.

### ✅ INSTALLATION.md
Setup instructions for installing and configuring the system. Includes step-by-step guide for both web server and PHP built-in server setups.

### ✅ FEATURES.md
Complete list of features implemented, matching the requirements checklist. Includes all functional requirements, question types, security features, and optional bonuses.

### ✅ CREDENTIALS.txt
Login credentials for testing the system (admin and student accounts). Includes quick start guide and testing workflow.

## Additional Documentation

### RUN_WITH_PHP_SERVER.md
Alternative setup guide for running the project using PHP's built-in development server without XAMPP/WAMP. Perfect for quick development and testing.

### UI_DESIGN_NOTES.md
UI design documentation including color palette, design inspiration, styling guidelines, and component specifications.

## Additional Files in Root

- **README.md** - Main project documentation (includes technology stack, features, setup, and comprehensive project flow)
- **database_schema.sql** - Database migration script with all table definitions, views, stored procedures, and sample data

## Project Flow Overview

The Online Examination System follows a structured workflow:

### Admin Flow
1. **Login** → Access admin dashboard
2. **Create Exam** → Set exam details (title, duration, passing marks)
3. **Add Questions** → Add questions using 5 supported question types
4. **Publish Exam** → Make exam available to students
5. **Monitor** → View attempts, analytics, and student performance

### Student Flow
1. **Register/Login** → Create account or login
2. **View Exams** → See available published exams
3. **Start Exam** → Read instructions and begin
4. **Take Exam** → Answer questions with timer, auto-save, and navigation
5. **Submit** → Manual or automatic submission when time expires
6. **View Results** → See score, correct answers, and detailed breakdown

### Security Flow
- All requests go through validation, CSRF protection, XSS prevention, and SQL injection prevention
- Sessions are secured with HTTP-only cookies and expiration
- Exam attempts are tracked with IP addresses and tab switch monitoring

For detailed flow diagrams, see `PROJECT_STRUCTURE.txt` and `README.md`.

