# Database Entity Relationship Diagram (ERD)

## Overview
This document describes the database schema for the Online Examination System.

## Entities and Relationships

### 1. USERS
**Purpose**: Stores both admin and student accounts
- **Primary Key**: id
- **Relationships**:
  - One user can create many exams (1:N with EXAMS)
  - One user can have many exam attempts (1:N with EXAM_ATTEMPTS)
  - One user can have many sessions (1:N with SESSIONS)
  - One user can have many audit logs (1:N with AUDIT_LOGS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- username (UNIQUE)
- email (UNIQUE)
- password_hash
- full_name
- role (admin/student)
- status (active/inactive/suspended)
- timestamps

### 2. EXAMS
**Purpose**: Stores exam configurations
- **Primary Key**: id
- **Foreign Keys**: created_by → users(id)
- **Relationships**:
  - One exam has many questions (1:N with QUESTIONS)
  - One exam has many attempts (1:N with EXAM_ATTEMPTS)
  - One exam belongs to one creator (N:1 with USERS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- title
- description
- duration_minutes
- total_marks
- passing_marks
- status (draft/published/in_progress/closed)
- allow_retake
- max_attempts
- show_results
- shuffle_questions
- created_by (FK, CHAR(36)) - References users(id)
- timestamps

### 3. QUESTIONS
**Purpose**: Stores exam questions
- **Primary Key**: id
- **Foreign Keys**: exam_id → exams(id)
- **Relationships**:
  - One question belongs to one exam (N:1 with EXAMS)
  - One question has many options (1:N with QUESTION_OPTIONS)
  - One question has many student answers (1:N with STUDENT_ANSWERS)

**Question Types**:
1. multiple_choice - Single correct answer from multiple options
2. multiple_select - Multiple correct answers from options
3. true_false - Binary choice question
4. short_answer - Text-based answer
5. fill_blank - Fill in the missing word/phrase

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- exam_id (FK, CHAR(36)) - References exams(id)
- question_type
- question_text
- marks
- order_number
- is_required
- timestamps

### 4. QUESTION_OPTIONS
**Purpose**: Stores answer options for MCQ/True-False questions
- **Primary Key**: id
- **Foreign Keys**: question_id → questions(id)
- **Relationships**:
  - One option belongs to one question (N:1 with QUESTIONS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- question_id (FK, CHAR(36)) - References questions(id)
- option_text
- is_correct
- order_number
- created_at

### 5. EXAM_ATTEMPTS
**Purpose**: Tracks student exam sessions
- **Primary Key**: id
- **Foreign Keys**: 
  - exam_id → exams(id)
  - student_id → users(id)
- **Relationships**:
  - One attempt belongs to one exam (N:1 with EXAMS)
  - One attempt belongs to one student (N:1 with USERS)
  - One attempt has many student answers (1:N with STUDENT_ANSWERS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- exam_id (FK, CHAR(36)) - References exams(id)
- student_id (FK, CHAR(36)) - References users(id)
- attempt_number
- start_time
- end_time
- submit_time
- duration_seconds
- status (not_started/in_progress/submitted/auto_submitted/abandoned)
- score
- total_marks
- percentage
- passed
- ip_address
- user_agent
- tab_switches (anti-cheating)
- timestamps

### 6. STUDENT_ANSWERS
**Purpose**: Stores student responses to questions
- **Primary Key**: id
- **Foreign Keys**: 
  - attempt_id → exam_attempts(id)
  - question_id → questions(id)
- **Relationships**:
  - One answer belongs to one attempt (N:1 with EXAM_ATTEMPTS)
  - One answer belongs to one question (N:1 with QUESTIONS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- attempt_id (FK, CHAR(36)) - References exam_attempts(id)
- question_id (FK, CHAR(36)) - References questions(id)
- answer_text (for text-based answers)
- selected_option_ids (TEXT, comma-separated UUIDs for MCQ)
- is_correct
- marks_obtained
- answered_at

### 7. SESSIONS
**Purpose**: Manages user authentication sessions
- **Primary Key**: id (session token)
- **Foreign Keys**: user_id → users(id)
- **Relationships**:
  - One session belongs to one user (N:1 with USERS)

**Fields**:
- id (PK, VARCHAR(128)) - Session token
- user_id (FK, CHAR(36)) - References users(id)
- session_data
- ip_address
- user_agent
- created_at
- expires_at

### 8. AUDIT_LOGS
**Purpose**: Security and activity tracking
- **Primary Key**: id
- **Foreign Keys**: user_id → users(id)
- **Relationships**:
  - One log belongs to one user (N:1 with USERS)

**Fields**:
- id (PK, CHAR(36)) - UUID v4
- user_id (FK, CHAR(36)) - References users(id)
- action
- entity_type
- entity_id (CHAR(36)) - UUID of the entity being logged
- details
- ip_address
- created_at

## Database Views

### exam_statistics
Provides aggregated statistics for each exam:
- Total students
- Total attempts
- Average/highest/lowest scores
- Pass/fail counts

### student_performance
Provides performance metrics for each student:
- Total exams taken
- Average score
- Exams passed/failed

## Stored Procedures

### calculate_exam_score(attempt_id CHAR(36))
Calculates and updates the final score for an exam attempt:
- Accepts UUID of exam attempt
- Sums marks from all answers
- Calculates percentage
- Determines pass/fail status
- Updates exam_attempts record

## Security Features

1. **UUID Primary Keys**: All tables use UUID v4 (CHAR(36)) for enhanced security
   - Prevents ID enumeration attacks
   - Non-sequential IDs protect privacy
   - Globally unique identifiers
2. **Password Hashing**: Using bcrypt (PASSWORD_BCRYPT in PHP)
3. **Session Management**: Secure session tokens with expiration
4. **Audit Logging**: All critical actions are logged
5. **Foreign Key Constraints**: Ensures data integrity
6. **Indexes**: Optimized for common queries
7. **Anti-Cheating**: Tab switch tracking in exam_attempts

## Cascade Rules

- Deleting a user cascades to their sessions, exams, and attempts
- Deleting an exam cascades to its questions and attempts
- Deleting a question cascades to its options and student answers
- Deleting an attempt cascades to student answers

## Character Set
- UTF-8 (utf8mb4) for international character support
- Collation: utf8mb4_unicode_ci

