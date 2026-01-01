-- ============================================
-- ONLINE EXAMINATION SYSTEM - DATABASE SCHEMA
-- ============================================
-- Created for: Online Exam System
-- Database: MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE exam_system;

-- ============================================
-- USERS TABLE (Admin & Students)
-- ============================================
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'student') DEFAULT 'student',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EXAMS TABLE
-- ============================================
CREATE TABLE exams (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL COMMENT 'Total exam duration in minutes',
    total_marks INT NOT NULL DEFAULT 0,
    passing_marks INT NOT NULL DEFAULT 0,
    status ENUM('draft', 'published', 'in_progress', 'closed') DEFAULT 'draft',
    allow_retake BOOLEAN DEFAULT FALSE,
    max_attempts INT DEFAULT 1,
    show_results BOOLEAN DEFAULT TRUE,
    shuffle_questions BOOLEAN DEFAULT FALSE,
    created_by CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- QUESTIONS TABLE
-- ============================================
CREATE TABLE questions (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    question_type ENUM('multiple_choice', 'multiple_select', 'true_false', 'short_answer', 'fill_blank') NOT NULL,
    question_text TEXT NOT NULL,
    correct_answer TEXT NULL COMMENT 'Correct answer for short_answer and fill_blank question types',
    marks INT NOT NULL DEFAULT 1,
    order_number INT NOT NULL DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    INDEX idx_exam_id (exam_id),
    INDEX idx_question_type (question_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- QUESTION OPTIONS TABLE
-- ============================================
CREATE TABLE question_options (
    id CHAR(36) PRIMARY KEY,
    question_id CHAR(36) NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    order_number INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_question_id (question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EXAM ATTEMPTS TABLE
-- ============================================
CREATE TABLE exam_attempts (
    id CHAR(36) PRIMARY KEY,
    exam_id CHAR(36) NOT NULL,
    student_id CHAR(36) NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    submit_time TIMESTAMP NULL,
    duration_seconds INT DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'submitted', 'auto_submitted', 'abandoned') DEFAULT 'not_started',
    score DECIMAL(5,2) DEFAULT 0.00,
    total_marks INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    passed BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    tab_switches INT DEFAULT 0 COMMENT 'Anti-cheating: count tab switches',
    flagged_for_cheating BOOLEAN DEFAULT FALSE COMMENT 'Flagged if submitted after expected end_time',
    cheating_reason TEXT NULL COMMENT 'Reason for flagging (e.g., "Submitted 120 seconds after exam duration expired")',
    unflagged_by CHAR(36) NULL COMMENT 'Admin user ID who unflagged this attempt',
    unflagged_at TIMESTAMP NULL COMMENT 'Timestamp when attempt was unflagged',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unflagged_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attempt (exam_id, student_id, attempt_number),
    INDEX idx_student_exam (student_id, exam_id),
    INDEX idx_status (status),
    INDEX idx_flagged (flagged_for_cheating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- STUDENT ANSWERS TABLE
-- ============================================
CREATE TABLE student_answers (
    id CHAR(36) PRIMARY KEY,
    attempt_id CHAR(36) NOT NULL,
    question_id CHAR(36) NOT NULL,
    answer_text TEXT,
    selected_option_ids TEXT COMMENT 'Comma-separated option IDs for multiple choice/select',
    is_correct BOOLEAN DEFAULT FALSE,
    marks_obtained DECIMAL(5,2) DEFAULT 0.00,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_answer (attempt_id, question_id),
    INDEX idx_attempt_id (attempt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SESSIONS TABLE (For security)
-- ============================================
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    session_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT LOG TABLE (Security & tracking)
-- ============================================
CREATE TABLE audit_logs (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id CHAR(36),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- ============================================
-- Password: Admin@123 (hashed with PASSWORD_BCRYPT)
-- Note: Generate UUIDs using UUID() function or application code
INSERT INTO users (id, username, email, password_hash, full_name, role, status) VALUES
(UUID(), 'admin', 'admin@examportal.com', '$2y$12$ZTSeo0O2fEnDNMbi6BXbfOtFA.sch6rka/KrFOWFFiV8BDK7NZVGC', 'System Administrator', 'admin', 'active');

-- ============================================
-- INSERT SAMPLE STUDENTS
-- ============================================
-- Password for all: Student@123
-- Note: Generate UUIDs using UUID() function or application code
INSERT INTO users (id, username, email, password_hash, full_name, role, status) VALUES
(UUID(), 'john_doe', 'john.doe@student.com', '$2y$12$mQDW99wcKV/T8bxCVm95le9DsuBSm2xi2c8w0DB8wG5ofJUgc3O.e', 'John Doe', 'student', 'active'),
(UUID(), 'jane_smith', 'jane.smith@student.com', '$2y$12$mQDW99wcKV/T8bxCVm95le9DsuBSm2xi2c8w0DB8wG5ofJUgc3O.e', 'Jane Smith', 'student', 'active'),
(UUID(), 'mike_wilson', 'mike.wilson@student.com', '$2y$12$mQDW99wcKV/T8bxCVm95le9DsuBSm2xi2c8w0DB8wG5ofJUgc3O.e', 'Mike Wilson', 'student', 'active');

-- ============================================
-- INSERT SAMPLE EXAM
-- ============================================
-- Note: Replace 'ADMIN_USER_ID' with actual admin UUID from users table
-- For initial setup, use: (SELECT id FROM users WHERE username = 'admin' LIMIT 1)
INSERT INTO exams (id, title, description, duration_minutes, total_marks, passing_marks, status, allow_retake, show_results, created_by) VALUES
(UUID(), 'General Knowledge Assessment', 'A comprehensive test covering various topics including science, history, and current affairs.', 5, 50, 25, 'published', FALSE, TRUE, (SELECT id FROM users WHERE username = 'admin' LIMIT 1));

-- ============================================
-- INSERT SAMPLE QUESTIONS
-- ============================================

-- Question 1: Multiple Choice
-- Note: Replace 'EXAM_ID' with actual exam UUID from exams table
SET @exam_id = (SELECT id FROM exams WHERE title = 'General Knowledge Assessment' LIMIT 1);
SET @q1_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, marks, order_number) VALUES
(@q1_id, @exam_id, 'multiple_choice', 'What is the capital of France?', 5, 1);

INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) VALUES
(UUID(), @q1_id, 'London', FALSE, 1),
(UUID(), @q1_id, 'Paris', TRUE, 2),
(UUID(), @q1_id, 'Berlin', FALSE, 3),
(UUID(), @q1_id, 'Madrid', FALSE, 4);

-- Question 2: Multiple Select
SET @q2_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, marks, order_number) VALUES
(@q2_id, @exam_id, 'multiple_select', 'Which of the following are programming languages? (Select all that apply)', 10, 2);

INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) VALUES
(UUID(), @q2_id, 'Python', TRUE, 1),
(UUID(), @q2_id, 'HTML', FALSE, 2),
(UUID(), @q2_id, 'JavaScript', TRUE, 3),
(UUID(), @q2_id, 'CSS', FALSE, 4),
(UUID(), @q2_id, 'Java', TRUE, 5);

-- Question 3: True/False
SET @q3_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, marks, order_number) VALUES
(@q3_id, @exam_id, 'true_false', 'The Earth is flat.', 5, 3);

INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) VALUES
(UUID(), @q3_id, 'True', FALSE, 1),
(UUID(), @q3_id, 'False', TRUE, 2);

-- Question 4: Short Answer
SET @q4_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, correct_answer, marks, order_number) VALUES
(@q4_id, @exam_id, 'short_answer', 'What does HTML stand for?', 'HyperText Markup Language', 10, 4);

-- Question 5: Fill in the Blank
SET @q5_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, correct_answer, marks, order_number) VALUES
(@q5_id, @exam_id, 'fill_blank', 'The process of converting source code into machine code is called __________.', 'compilation', 10, 5);

-- Question 6: Multiple Choice
SET @q6_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, marks, order_number) VALUES
(@q6_id, @exam_id, 'multiple_choice', 'Which planet is known as the Red Planet?', 5, 6);

INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) VALUES
(UUID(), @q6_id, 'Venus', FALSE, 1),
(UUID(), @q6_id, 'Mars', TRUE, 2),
(UUID(), @q6_id, 'Jupiter', FALSE, 3),
(UUID(), @q6_id, 'Saturn', FALSE, 4);

-- Question 7: True/False
SET @q7_id = UUID();
INSERT INTO questions (id, exam_id, question_type, question_text, marks, order_number) VALUES
(@q7_id, @exam_id, 'true_false', 'Water boils at 100 degrees Celsius at sea level.', 5, 7);

INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) VALUES
(UUID(), @q7_id, 'True', TRUE, 1),
(UUID(), @q7_id, 'False', FALSE, 2);

-- ============================================
-- VIEWS FOR ANALYTICS
-- ============================================

-- View: Exam Statistics
CREATE VIEW exam_statistics AS
SELECT 
    e.id AS exam_id,
    e.title AS exam_title,
    COUNT(DISTINCT ea.student_id) AS total_students,
    COUNT(ea.id) AS total_attempts,
    AVG(ea.percentage) AS average_score,
    MAX(ea.percentage) AS highest_score,
    MIN(ea.percentage) AS lowest_score,
    SUM(CASE WHEN ea.passed = TRUE THEN 1 ELSE 0 END) AS passed_count,
    SUM(CASE WHEN ea.passed = FALSE THEN 1 ELSE 0 END) AS failed_count
FROM exams e
LEFT JOIN exam_attempts ea ON e.id = ea.exam_id AND ea.status IN ('submitted', 'auto_submitted')
GROUP BY e.id, e.title;

-- View: Student Performance
CREATE VIEW student_performance AS
SELECT 
    u.id AS student_id,
    u.full_name,
    u.email,
    COUNT(ea.id) AS exams_taken,
    AVG(ea.percentage) AS average_score,
    SUM(CASE WHEN ea.passed = TRUE THEN 1 ELSE 0 END) AS exams_passed,
    SUM(CASE WHEN ea.passed = FALSE THEN 1 ELSE 0 END) AS exams_failed
FROM users u
LEFT JOIN exam_attempts ea ON u.id = ea.student_id AND ea.status IN ('submitted', 'auto_submitted')
WHERE u.role = 'student'
GROUP BY u.id, u.full_name, u.email;

-- ============================================
-- STORED PROCEDURES
-- ============================================

DELIMITER //

-- Procedure: Calculate Exam Score
CREATE PROCEDURE calculate_exam_score(IN p_attempt_id CHAR(36))
BEGIN
    DECLARE v_total_marks INT DEFAULT 0;
    DECLARE v_obtained_marks DECIMAL(10,2) DEFAULT 0;
    DECLARE v_percentage DECIMAL(5,2) DEFAULT 0;
    DECLARE v_passing_marks INT DEFAULT 0;
    DECLARE v_exam_id CHAR(36) DEFAULT '';
    DECLARE v_passed BOOLEAN DEFAULT FALSE;
    
    -- Get exam_id and passing marks first
    SELECT ea.exam_id, e.passing_marks INTO v_exam_id, v_passing_marks
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.id = p_attempt_id;
    
    -- Get obtained marks from student answers
    SELECT COALESCE(SUM(sa.marks_obtained), 0) INTO v_obtained_marks
    FROM student_answers sa
    WHERE sa.attempt_id = p_attempt_id;
    
    -- Get total marks from ALL questions in the exam (not just answered ones)
    SELECT COALESCE(SUM(marks), 0) INTO v_total_marks
    FROM questions
    WHERE exam_id = v_exam_id;
    
    -- If no questions found, use exam's total_marks as fallback
    IF v_total_marks = 0 THEN
        SELECT total_marks INTO v_total_marks
        FROM exams
        WHERE id = v_exam_id;
    END IF;
    
    -- Calculate percentage based on total exam marks
    IF v_total_marks > 0 THEN
        SET v_percentage = (v_obtained_marks / v_total_marks) * 100;
    END IF;
    
    -- Determine if passed (compare obtained marks to passing marks)
    IF v_obtained_marks >= v_passing_marks THEN
        SET v_passed = TRUE;
    END IF;
    
    -- Update exam attempt
    UPDATE exam_attempts
    SET 
        score = v_obtained_marks,
        total_marks = v_total_marks,
        percentage = v_percentage,
        passed = v_passed
    WHERE id = p_attempt_id;
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

DELIMITER //

-- Trigger: Update exam total marks when questions are inserted
CREATE TRIGGER update_exam_total_marks_insert AFTER INSERT ON questions
FOR EACH ROW
BEGIN
    UPDATE exams 
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0) 
        FROM questions 
        WHERE exam_id = NEW.exam_id
    )
    WHERE id = NEW.exam_id;
END //

-- Trigger: Update exam total marks when questions are updated
CREATE TRIGGER update_exam_total_marks_update AFTER UPDATE ON questions
FOR EACH ROW
BEGIN
    -- Update the old exam if exam_id changed
    IF OLD.exam_id != NEW.exam_id THEN
        UPDATE exams 
        SET total_marks = (
            SELECT COALESCE(SUM(marks), 0) 
            FROM questions 
            WHERE exam_id = OLD.exam_id
        )
        WHERE id = OLD.exam_id;
    END IF;
    
    -- Update the new/current exam
    UPDATE exams 
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0) 
        FROM questions 
        WHERE exam_id = NEW.exam_id
    )
    WHERE id = NEW.exam_id;
END //

-- Trigger: Update exam total marks when questions are deleted
CREATE TRIGGER update_exam_total_marks_delete AFTER DELETE ON questions
FOR EACH ROW
BEGIN
    UPDATE exams 
    SET total_marks = (
        SELECT COALESCE(SUM(marks), 0) 
        FROM questions 
        WHERE exam_id = OLD.exam_id
    )
    WHERE id = OLD.exam_id;
END //

DELIMITER ;

-- ============================================
-- END OF SCHEMA
-- ============================================


