<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Creates a new exam in the system
 *
 * Creates an exam with the specified parameters and sets it to 'draft' status.
 * The exam is associated with the currently logged-in admin user.
 *
 * @param string $title The title of the exam (required)
 * @param string $description The description/instructions for the exam
 * @param int $durationMinutes The duration of the exam in minutes (required)
 * @param int $passingMarks The minimum marks required to pass the exam
 * @param bool $allowRetake Whether students can retake this exam (default: false)
 * @param int $maxAttempts Maximum number of attempts allowed if retake is enabled (default: 1)
 * @param bool $showResults Whether to show results to students after submission (default: true)
 * @param bool $shuffleQuestions Whether to shuffle question order for each student (default: false)
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys.
 *               On success, also includes 'exam_id' (int) key.
 * @since 1.0.0
 */
function createExam($title, $description, $durationMinutes, $passingMarks, $allowRetake = false, $maxAttempts = 1, $showResults = true, $shuffleQuestions = false) {
    $createdBy = getCurrentUserId();
    
    if (empty($title) || empty($durationMinutes)) {
        return ['success' => false, 'message' => 'Title and duration are required'];
    }
    
    $examId = generateUUID();
    $query = "INSERT INTO exams (id, title, description, duration_minutes, passing_marks, allow_retake, max_attempts, show_results, shuffle_questions, created_by, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
    
    $result = executeInsert($query, "sssiiiiiss", [
        $examId,
        $title,
        $description,
        $durationMinutes,
        $passingMarks,
        $allowRetake ? 1 : 0,
        $maxAttempts,
        $showResults ? 1 : 0,
        $shuffleQuestions ? 1 : 0,
        $createdBy
    ]);
    
    if ($examId) {
        logActivity('exam_created', 'exam', $examId, "Created exam: $title");
        return ['success' => true, 'message' => 'Exam created successfully', 'exam_id' => $examId];
    }
    
    return ['success' => false, 'message' => 'Failed to create exam'];
}

/**
 * Updates an existing exam's details
 *
 * Updates all exam properties including title, description, duration, passing marks,
 * status, and configuration options. Logs the update activity.
 *
 * @param int $examId The ID of the exam to update
 * @param string $title The updated title of the exam
 * @param string $description The updated description of the exam
 * @param int $durationMinutes The updated duration in minutes
 * @param int $passingMarks The updated passing marks threshold
 * @param string $status The exam status ('draft', 'published', 'in_progress', 'closed')
 * @param bool $allowRetake Whether retakes are allowed
 * @param int $maxAttempts Maximum attempts if retake is enabled
 * @param bool $showResults Whether to show results to students
 * @param bool $shuffleQuestions Whether to shuffle questions
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function updateExam($examId, $title, $description, $durationMinutes, $passingMarks, $status, $allowRetake, $maxAttempts, $showResults, $shuffleQuestions) {
    $query = "UPDATE exams SET title = ?, description = ?, duration_minutes = ?, passing_marks = ?, 
              status = ?, allow_retake = ?, max_attempts = ?, show_results = ?, shuffle_questions = ?
              WHERE id = ?";
    
    $result = executeUpdate($query, "ssiisiiiis", [
        $title,
        $description,
        $durationMinutes,
        $passingMarks,
        $status,
        $allowRetake ? 1 : 0,
        $maxAttempts,
        $showResults ? 1 : 0,
        $shuffleQuestions ? 1 : 0,
        $examId
    ]);
    
    if ($result !== false) {
        logActivity('exam_updated', 'exam', $examId, "Updated exam: $title");
        return ['success' => true, 'message' => 'Exam updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update exam'];
}

/**
 * Deletes an exam from the system
 *
 * Permanently deletes an exam and all associated data (questions, attempts, etc.)
 * due to CASCADE constraints in the database. Logs the deletion activity.
 *
 * @param int $examId The ID of the exam to delete
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function deleteExam($examId) {
    $query = "DELETE FROM exams WHERE id = ?";
    $result = executeUpdate($query, "s", [$examId]);
    
    if ($result) {
        logActivity('exam_deleted', 'exam', $examId, "Deleted exam");
        return ['success' => true, 'message' => 'Exam deleted successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to delete exam'];
}

/**
 * Retrieves a single exam by its ID
 *
 * Fetches exam details including creator information and question count.
 *
 * @param int $examId The ID of the exam to retrieve
 * @return array|null Returns an associative array with exam data, or null if not found.
 *                    Includes: id, title, description, duration_minutes, total_marks,
 *                    passing_marks, status, creator_name, question_count, etc.
 * @since 1.0.0
 */
function getExamById($examId) {
    $query = "SELECT e.*, u.full_name as creator_name,
              (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
              (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = e.id) as total_marks
              FROM exams e
              LEFT JOIN users u ON e.created_by = u.id
              WHERE e.id = ?";
    return fetchOne($query, "s", [$examId]);
}

/**
 * Retrieves all exams with optional filtering and pagination
 *
 * Fetches a paginated list of exams, optionally filtered by status.
 * Includes creator information, question count, and student count.
 *
 * @param string|null $status Optional status filter ('draft', 'published', 'in_progress', 'closed')
 * @param int $page The page number for pagination (default: 1)
 * @param int $limit The number of items per page (default: ITEMS_PER_PAGE)
 * @return array Returns an array of exam records, each containing exam data with
 *               creator_name, question_count, and student_count
 * @since 1.0.0
 */
function getAllExams($status = null, $page = 1, $limit = ITEMS_PER_PAGE) {
    $offset = ($page - 1) * $limit;
    
    if ($status) {
        $query = "SELECT e.*, u.full_name as creator_name,
                  (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
                  (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = e.id) as total_marks,
                  (SELECT COUNT(DISTINCT student_id) FROM exam_attempts WHERE exam_id = e.id) as student_count
                  FROM exams e
                  LEFT JOIN users u ON e.created_by = u.id
                  WHERE e.status = ?
                  ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
        return fetchAll($query, "sii", [$status, $limit, $offset]);
    } else {
        $query = "SELECT e.*, u.full_name as creator_name,
                  (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
                  (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = e.id) as total_marks,
                  (SELECT COUNT(DISTINCT student_id) FROM exam_attempts WHERE exam_id = e.id) as student_count
                  FROM exams e
                  LEFT JOIN users u ON e.created_by = u.id
                  ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
        return fetchAll($query, "ii", [$limit, $offset]);
    }
}

/**
 * Counts the total number of exams, optionally filtered by status
 *
 * @param string|null $status Optional status filter to count only exams with that status
 * @return int The total count of exams matching the criteria
 * @since 1.0.0
 */
function countExams($status = null) {
    if ($status) {
        $query = "SELECT COUNT(*) as total FROM exams WHERE status = ?";
        $result = fetchOne($query, "s", [$status]);
    } else {
        $query = "SELECT COUNT(*) as total FROM exams";
        $result = fetchOne($query);
    }
    
    return $result['total'] ?? 0;
}

/**
 * Retrieves only published exams for student viewing
 *
 * Fetches a paginated list of exams with 'published' status that students can take.
 * Includes question count for each exam.
 *
 * @param int $page The page number for pagination (default: 1)
 * @param int $limit The number of items per page (default: ITEMS_PER_PAGE)
 * @return array Returns an array of published exam records with question_count
 * @since 1.0.0
 */
function getPublishedExams($page = 1, $limit = ITEMS_PER_PAGE) {
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT e.*,
              (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count
              FROM exams e
              WHERE e.status = 'published'
              ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
    
    return fetchAll($query, "ii", [$limit, $offset]);
}

/**
 * Updates the total_marks field in the exams table
 *
 * Recalculates and updates the total marks for an exam based on the sum of all
 * question marks. This should be called whenever questions are added, updated, or deleted.
 *
 * @param int $examId The ID of the exam to update
 * @return void
 * @since 1.0.0
 */
function updateExamTotalMarks($examId) {
    $query = "UPDATE exams 
              SET total_marks = (
                  SELECT COALESCE(SUM(marks), 0) 
                  FROM questions 
                  WHERE exam_id = ?
              )
              WHERE id = ?";
    executeUpdate($query, "ss", [$examId, $examId]);
}

/**
 * Creates a new question for an exam
 *
 * Creates a question and optionally adds answer options for multiple choice,
 * multiple select, or true/false question types. Automatically creates options
 * if provided in the $options array. Updates exam total marks after creation.
 *
 * @param int $examId The ID of the exam this question belongs to
 * @param string $questionType The type of question: 'multiple_choice', 'multiple_select',
 *                             'true_false', 'short_answer', or 'fill_blank'
 * @param string $questionText The question text/content (required)
 * @param int $marks The marks allocated for this question
 * @param int $orderNumber The display order of this question in the exam
 * @param array $options Optional array of options for MCQ/select questions.
 *                      Each option should have 'text' and 'is_correct' keys.
 * @return array Returns an array with 'success' (bool), 'message' (string),
 *               and on success, 'question_id' (int)
 * @since 1.0.0
 */
function createQuestion($examId, $questionType, $questionText, $marks, $orderNumber, $options = [], $correctAnswer = null) {
    if (empty($questionText) || empty($questionType)) {
        return ['success' => false, 'message' => 'Question text and type are required'];
    }
    
    if (in_array($questionType, ['short_answer', 'fill_blank']) && empty($correctAnswer)) {
        return ['success' => false, 'message' => 'Correct answer is required for ' . str_replace('_', ' ', $questionType) . ' questions'];
    }
    
    $questionId = generateUUID();
    $query = "INSERT INTO questions (id, exam_id, question_type, question_text, correct_answer, marks, order_number) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeInsert($query, "sssssii", [
        $questionId,
        $examId,
        $questionType,
        $questionText,
        $correctAnswer,
        $marks,
        $orderNumber
    ]);
    
    if ($questionId) {
        if (!empty($options) && in_array($questionType, ['multiple_choice', 'multiple_select', 'true_false'])) {
            foreach ($options as $index => $option) {
                addQuestionOption($questionId, $option['text'], $option['is_correct'], $index + 1);
            }
        }
        
        updateExamTotalMarks($examId);
        logActivity('question_created', 'question', $questionId, "Created question for exam: $examId");
        return ['success' => true, 'message' => 'Question created successfully', 'question_id' => $questionId];
    }
    
    return ['success' => false, 'message' => 'Failed to create question'];
}

/**
 * Updates an existing question's details
 *
 * Updates the question text, marks, and order number. Does not modify question type
 * or options (those require separate operations). Updates exam total marks after update.
 *
 * @param int $questionId The ID of the question to update
 * @param string $questionText The updated question text
 * @param int $marks The updated marks allocated for this question
 * @param int $orderNumber The updated display order
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function updateQuestion($questionId, $questionText, $marks, $orderNumber, $correctAnswer = null) {
    $question = getQuestionById($questionId);
    if (!$question) {
        return ['success' => false, 'message' => 'Question not found'];
    }
    
    if (in_array($question['question_type'], ['short_answer', 'fill_blank']) && empty($correctAnswer)) {
        return ['success' => false, 'message' => 'Correct answer is required for ' . str_replace('_', ' ', $question['question_type']) . ' questions'];
    }
    
    $query = "UPDATE questions SET question_text = ?, correct_answer = ?, marks = ?, order_number = ? WHERE id = ?";
    
    $result = executeUpdate($query, "ssiis", [$questionText, $correctAnswer, $marks, $orderNumber, $questionId]);
    
    if ($result !== false) {
        updateExamTotalMarks($question['exam_id']);
        logActivity('question_updated', 'question', $questionId, "Updated question");
        return ['success' => true, 'message' => 'Question updated successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to update question'];
}

/**
 * Deletes a question and all its associated options
 *
 * Permanently deletes a question. Associated options are deleted via CASCADE constraint.
 *
 * @param int $questionId The ID of the question to delete
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function deleteQuestion($questionId) {
    $query = "DELETE FROM questions WHERE id = ?";
    $result = executeUpdate($query, "s", [$questionId]);
    
    if ($result) {
        logActivity('question_deleted', 'question', $questionId, "Deleted question");
        return ['success' => true, 'message' => 'Question deleted successfully'];
    }
    
    return ['success' => false, 'message' => 'Failed to delete question'];
}

/**
 * Retrieves a single question by its ID
 *
 * @param int $questionId The ID of the question to retrieve
 * @return array|null Returns an associative array with question data, or null if not found
 * @since 1.0.0
 */
function getQuestionById($questionId) {
    $query = "SELECT * FROM questions WHERE id = ?";
    return fetchOne($query, "s", [$questionId]);
}

/**
 * Retrieves all questions for an exam, optionally shuffled
 *
 * Fetches questions ordered by order_number. If shuffle is enabled,
 * uses PHP's shuffle() function for consistent randomization.
 *
 * @param int $examId The ID of the exam
 * @param bool $shuffle Whether to shuffle the questions (default: false)
 * @return array Returns an array of question records
 * @since 1.0.0
 */
function getExamQuestions($examId, $shuffle = false) {
    $query = "SELECT * FROM questions WHERE exam_id = ? ORDER BY order_number ASC";
    $questions = fetchAll($query, "s", [$examId]);
    
    if ($shuffle && !empty($questions)) {
        shuffle($questions);
    }
    
    return $questions;
}

/**
 * Adds an answer option to a question
 *
 * Creates a new option for multiple choice, multiple select, or true/false questions.
 *
 * @param int $questionId The ID of the question
 * @param string $optionText The text content of the option
 * @param bool $isCorrect Whether this option is the correct answer
 * @param int $orderNumber The display order of this option
 * @return bool|int Returns the option ID on success, false on failure
 * @since 1.0.0
 */
function addQuestionOption($questionId, $optionText, $isCorrect, $orderNumber) {
    $optionId = generateUUID();
    $query = "INSERT INTO question_options (id, question_id, option_text, is_correct, order_number) 
              VALUES (?, ?, ?, ?, ?)";
    
    return executeInsert($query, "sssii", [
        $optionId,
        $questionId,
        $optionText,
        $isCorrect ? 1 : 0,
        $orderNumber
    ]);
}

/**
 * Updates an existing question option
 *
 * @param int $optionId The ID of the option to update
 * @param string $optionText The updated option text
 * @param bool $isCorrect Whether this option is correct
 * @return bool|int Returns the result of the update operation
 * @since 1.0.0
 */
function updateQuestionOption($optionId, $optionText, $isCorrect) {
    $query = "UPDATE question_options SET option_text = ?, is_correct = ? WHERE id = ?";
    
    return executeUpdate($query, "sis", [$optionText, $isCorrect ? 1 : 0, $optionId]);
}

/**
 * Deletes a question option
 *
 * @param int $optionId The ID of the option to delete
 * @return bool|int Returns the result of the delete operation
 * @since 1.0.0
 */
function deleteQuestionOption($optionId) {
    $query = "DELETE FROM question_options WHERE id = ?";
    return executeUpdate($query, "s", [$optionId]);
}

/**
 * Retrieves all options for a question
 *
 * Fetches all answer options for a question, ordered by order_number.
 *
 * @param int $questionId The ID of the question
 * @return array Returns an array of option records, each containing:
 *               id, question_id, option_text, is_correct, order_number
 * @since 1.0.0
 */
function getQuestionOptions($questionId) {
    $query = "SELECT * FROM question_options WHERE question_id = ? ORDER BY order_number ASC";
    return fetchAll($query, "s", [$questionId]);
}

/**
 * Starts a new exam attempt for a student
 *
 * Validates exam availability, checks retake permissions, and creates a new exam attempt.
 * If an in-progress attempt exists, returns that instead of creating a new one.
 * Tracks IP address and user agent for security purposes.
 *
 * @param int $examId The ID of the exam to start
 * @param int $studentId The ID of the student starting the exam
 * @return array Returns an array with:
 *               - 'success' (bool): Whether the operation succeeded
 *               - 'message' (string): Status message
 *               - 'attempt_id' (int): The ID of the created/resumed attempt (on success)
 * @since 1.0.0
 */
function startExamAttempt($examId, $studentId) {
    $exam = getExamById($examId);
    if (!$exam || $exam['status'] !== 'published') {
        return ['success' => false, 'message' => 'Exam not available'];
    }
    
    $query = "SELECT id FROM exam_attempts 
              WHERE exam_id = ? AND student_id = ? AND status = 'in_progress'";
    $existing = fetchOne($query, "ss", [$examId, $studentId]);
    
    if ($existing) {
        return ['success' => true, 'message' => 'Resuming exam', 'attempt_id' => $existing['id']];
    }
    
    $query = "SELECT COUNT(*) as attempt_count FROM exam_attempts 
              WHERE exam_id = ? AND student_id = ? 
              AND status IN ('submitted', 'auto_submitted')
              AND (flagged_for_cheating = 0 OR unflagged_at IS NOT NULL)";
    $result = fetchOne($query, "ss", [$examId, $studentId]);
    $attemptCount = $result['attempt_count'] ?? 0;
    
    $query = "SELECT flagged_for_cheating, unflagged_at FROM exam_attempts 
              WHERE exam_id = ? AND student_id = ? 
              AND status IN ('submitted', 'auto_submitted')
              ORDER BY submit_time DESC LIMIT 1";
    $lastAttempt = fetchOne($query, "ss", [$examId, $studentId]);
    
    if ($lastAttempt && $lastAttempt['flagged_for_cheating'] && !$lastAttempt['unflagged_at']) {
        return ['success' => false, 'message' => 'Your last attempt was flagged for review. Please wait for admin review or contact administrator.'];
    }
    
    if (!$exam['allow_retake'] && $attemptCount > 0) {
        return ['success' => false, 'message' => 'You have already taken this exam. Retakes are not allowed.'];
    }
    
    if ($exam['allow_retake'] && $attemptCount >= $exam['max_attempts']) {
        return ['success' => false, 'message' => "Maximum attempts ({$exam['max_attempts']}) reached for this exam."];
    }
    
    $attemptNumber = $attemptCount + 1;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $totalMarksQuery = "SELECT COALESCE(SUM(marks), 0) as total FROM questions WHERE exam_id = ?";
    $totalMarksResult = fetchOne($totalMarksQuery, "s", [$examId]);
    $actualTotalMarks = (int)$totalMarksResult['total'];
    
    $durationMinutes = (int)$exam['duration_minutes'];
    $expectedEndTime = date('Y-m-d H:i:s', strtotime("+{$durationMinutes} minutes"));
    
    $attemptId = generateUUID();
    $query = "INSERT INTO exam_attempts (id, exam_id, student_id, attempt_number, start_time, end_time, status, ip_address, user_agent, total_marks) 
              VALUES (?, ?, ?, ?, NOW(), ?, 'in_progress', ?, ?, ?)";
    
    $result = executeInsert($query, "sssisssi", [
        $attemptId,
        $examId,
        $studentId,
        $attemptNumber,
        $expectedEndTime,
        $ipAddress,
        $userAgent,
        $actualTotalMarks
    ]);
    
    if ($attemptId) {
        logActivity('exam_started', 'exam_attempt', $attemptId, "Started exam: {$exam['title']}");
        
        return ['success' => true, 'message' => 'Exam started', 'attempt_id' => $attemptId];
    }
    
    return ['success' => false, 'message' => 'Failed to start exam'];
}

/**
 * Grades all answers in an exam attempt
 *
 * Automatically grades all student answers based on question type:
 * - Multiple Choice/True-False: Checks if selected option is correct
 * - Multiple Select: Verifies all correct options are selected (exact match)
 * - Short Answer/Fill Blank: Requires manual grading (marks_obtained remains 0)
 *
 * Updates the 'is_correct' and 'marks_obtained' fields for each answer.
 *
 * @param int $attemptId The ID of the exam attempt to grade
 * @return void
 * @since 1.0.0
 */
function gradeExamAttempt($attemptId) {
    $answers = getStudentAnswers($attemptId);
    
    foreach ($answers as $answer) {
        $question = getQuestionById($answer['question_id']);
        if (!$question) {
            continue;
        }
        
        $isCorrect = false;
        $marksObtained = 0;
        
        if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
            if (!empty($answer['selected_option_ids'])) {
                $selectedOptionId = trim($answer['selected_option_ids']);
                if (!empty($selectedOptionId) && strlen($selectedOptionId) === 36) {
                    $query = "SELECT is_correct FROM question_options WHERE id = ?";
                    $option = fetchOne($query, "s", [$selectedOptionId]);
                    
                    if ($option && ($option['is_correct'] == 1 || $option['is_correct'] === true)) {
                        $isCorrect = true;
                        $marksObtained = $question['marks'];
                    }
                }
            }
        } elseif ($question['question_type'] === 'multiple_select') {
            if (!empty($answer['selected_option_ids'])) {
                $selectedIdsStr = trim($answer['selected_option_ids']);
                $selectedIds = array_filter(array_map('trim', explode(',', $selectedIdsStr)));
                $selectedIds = array_values(array_filter($selectedIds, function($id) {
                    return strlen($id) === 36;
                }));
                
                if (!empty($selectedIds)) {
                    $query = "SELECT id, is_correct FROM question_options WHERE question_id = ?";
                    $allOptions = fetchAll($query, "s", [$question['id']]);
                    
                    $correctIds = [];
                    foreach ($allOptions as $opt) {
                        if ($opt['is_correct'] == 1 || $opt['is_correct'] === true) {
                            $correctIds[] = $opt['id'];
                        }
                    }
                    
                    sort($selectedIds);
                    sort($correctIds);
                    
                    if (count($selectedIds) === count($correctIds)) {
                        $allMatch = true;
                        for ($i = 0; $i < count($selectedIds); $i++) {
                            if ($selectedIds[$i] !== $correctIds[$i]) {
                                $allMatch = false;
                                break;
                            }
                        }
                        if ($allMatch) {
                            $isCorrect = true;
                            $marksObtained = $question['marks'];
                        }
                    }
                }
            }
        } elseif ($question['question_type'] === 'short_answer' || $question['question_type'] === 'fill_blank') {
            if (!empty($question['correct_answer']) && !empty($answer['answer_text'])) {
                $normalizeAnswer = function($text) {
                    $text = trim($text);
                    $text = mb_strtolower($text, 'UTF-8');
                    $text = preg_replace('/\s+/', ' ', $text);
                    return $text;
                };
                
                $studentAnswer = $normalizeAnswer($answer['answer_text']);
                $correctAnswer = $normalizeAnswer($question['correct_answer']);
                
                if ($studentAnswer === $correctAnswer) {
                    $isCorrect = true;
                    $marksObtained = $question['marks'];
                }
            }
        }
        
        $query = "UPDATE student_answers SET is_correct = ?, marks_obtained = ? WHERE id = ?";
        executeUpdate($query, "ids", [$isCorrect ? 1 : 0, $marksObtained, $answer['id']]);
    }
}

/**
 * Submits an exam attempt and calculates final score
 *
 * Marks the exam attempt as submitted (or auto-submitted), calculates duration,
 * grades all answers, and calculates the final exam score using stored procedure.
 *
 * @param int $attemptId The ID of the exam attempt to submit
 * @param bool $autoSubmit Whether this is an automatic submission (timer expiry, etc.)
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function submitExamAttempt($attemptId, $autoSubmit = false) {
    $status = $autoSubmit ? 'auto_submitted' : 'submitted';
    
    $attempt = getExamAttemptById($attemptId);
    if (!$attempt) {
        return ['success' => false, 'message' => 'Exam attempt not found'];
    }
    
    $exam = getExamById($attempt['exam_id']);
    if (!$exam) {
        return ['success' => false, 'message' => 'Exam not found'];
    }
    
    $startTime = strtotime($attempt['start_time']);
    $currentTime = time();
    $elapsedSeconds = $currentTime - $startTime;
    $maxDurationSeconds = (int)$exam['duration_minutes'] * 60;
    
    $expectedEndTime = $startTime + $maxDurationSeconds;
    $expectedEndTimeFormatted = date('Y-m-d H:i:s', $expectedEndTime);
    
    $endTime = $expectedEndTimeFormatted;
    
    $durationSeconds = min($elapsedSeconds, $maxDurationSeconds);
    
    $flaggedForCheating = false;
    $cheatingReason = null;
    
    if (!$autoSubmit && $currentTime > $expectedEndTime) {
        $secondsOver = $currentTime - $expectedEndTime;
        $flaggedForCheating = true;
        $cheatingReason = "Submitted " . $secondsOver . " second(s) after exam duration expired. Expected end time: " . $expectedEndTimeFormatted . ", Actual submit time: " . date('Y-m-d H:i:s', $currentTime);
    }
    
    if ($autoSubmit) {
        $flaggedForCheating = false;
        $cheatingReason = null;
    }
    
    if ($flaggedForCheating) {
        $query = "UPDATE exam_attempts 
                  SET end_time = ?, submit_time = NOW(), status = ?, duration_seconds = ?,
                      flagged_for_cheating = 1, cheating_reason = ?
                  WHERE id = ?";
        $result = executeUpdate($query, "ssiss", [$endTime, $status, $durationSeconds, $cheatingReason, $attemptId]);
    } else {
        $query = "UPDATE exam_attempts 
                  SET end_time = ?, submit_time = NOW(), status = ?, duration_seconds = ?
                  WHERE id = ?";
        $result = executeUpdate($query, "ssis", [$endTime, $status, $durationSeconds, $attemptId]);
    }
    
    if ($result !== false) {
        if (!$flaggedForCheating) {
            gradeExamAttempt($attemptId);
            calculateExamScore($attemptId);
        } else {
            executeUpdate("UPDATE exam_attempts SET score = 0, percentage = 0, passed = 0 WHERE id = ?", "s", [$attemptId]);
            logActivity('exam_flagged_cheating', 'exam_attempt', $attemptId, "Exam flagged for cheating: " . $cheatingReason);
        }
        
        logActivity('exam_submitted', 'exam_attempt', $attemptId, $flaggedForCheating ? "Submitted exam (FLAGGED FOR CHEATING)" : "Submitted exam");
        
        return [
            'success' => true, 
            'message' => $flaggedForCheating ? 'Exam submitted but flagged for review due to late submission' : 'Exam submitted successfully',
            'flagged' => $flaggedForCheating
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to submit exam'];
}

/**
 * Saves or updates a student's answer to a question
 *
 * If an answer already exists for this question in the attempt, it updates it.
 * Otherwise, creates a new answer record. Supports both text-based and option-based answers.
 *
 * @param int $attemptId The ID of the exam attempt
 * @param int $questionId The ID of the question being answered
 * @param string|null $answerText The text answer for short_answer/fill_blank questions
 * @param string|null $selectedOptionIds Comma-separated option IDs for MCQ/select questions
 * @return bool|int Returns the answer ID on success, false on failure
 * @since 1.0.0
 */
function saveStudentAnswer($attemptId, $questionId, $answerText = null, $selectedOptionIds = null) {
    $query = "SELECT id FROM student_answers WHERE attempt_id = ? AND question_id = ?";
    $existing = fetchOne($query, "ss", [$attemptId, $questionId]);
    
    if ($existing) {
        $query = "UPDATE student_answers SET answer_text = ?, selected_option_ids = ?, answered_at = NOW() 
                  WHERE id = ?";
        return executeUpdate($query, "sss", [$answerText, $selectedOptionIds, $existing['id']]);
    } else {
        $answerId = generateUUID();
        $query = "INSERT INTO student_answers (id, attempt_id, question_id, answer_text, selected_option_ids) 
                  VALUES (?, ?, ?, ?, ?)";
        return executeInsert($query, "sssss", [$answerId, $attemptId, $questionId, $answerText, $selectedOptionIds]);
    }
}

/**
 * Retrieves all answers for a specific exam attempt
 *
 * Fetches all student answers for an attempt, including question details
 * (type, text, marks) for display purposes.
 *
 * @param int $attemptId The ID of the exam attempt
 * @return array Returns an array of answer records, each containing:
 *               answer data plus question_type, question_text, and marks
 * @since 1.0.0
 */
function getStudentAnswers($attemptId) {
    $query = "SELECT sa.*, q.question_type, q.question_text, q.marks, q.correct_answer
              FROM student_answers sa
              JOIN questions q ON sa.question_id = q.id
              WHERE sa.attempt_id = ?";
    return fetchAll($query, "s", [$attemptId]);
}

/**
 * Calculates and updates the final exam score using stored procedure
 *
 * Calls the database stored procedure 'calculate_exam_score' which:
 * - Sums all marks_obtained from student_answers
 * - Calculates percentage based on total marks
 * - Determines pass/fail status
 * - Updates exam_attempts table with score, percentage, and passed flag
 *
 * @param int $attemptId The ID of the exam attempt to calculate score for
 * @return void
 * @since 1.0.0
 */
function calculateExamScore($attemptId) {
    $query = "SELECT COALESCE(SUM(marks_obtained), 0) as total_obtained
              FROM student_answers
              WHERE attempt_id = ?";
    $result = fetchOne($query, "s", [$attemptId]);
    $totalObtained = (float)$result['total_obtained'];
    
    $attempt = getExamAttemptById($attemptId);
    if (!$attempt) {
        return;
    }
    
    $exam = getExamById($attempt['exam_id']);
    if (!$exam) {
        return;
    }
    
    $query = "SELECT COALESCE(SUM(marks), 0) as total_possible
              FROM questions
              WHERE exam_id = ?";
    $result = fetchOne($query, "s", [$exam['id']]);
    $totalPossible = (float)$result['total_possible'];
    
    if ($totalPossible == 0) {
        $totalPossible = (float)$exam['total_marks'];
    }
    
    $percentage = $totalPossible > 0 ? ($totalObtained / $totalPossible) * 100 : 0;
    
    $passed = $totalObtained >= $exam['passing_marks'];
    
    $query = "UPDATE exam_attempts 
              SET score = ?, total_marks = ?, percentage = ?, passed = ?
              WHERE id = ?";
    executeUpdate($query, "diids", [$totalObtained, (int)$totalPossible, $percentage, $passed ? 1 : 0, $attemptId]);
}

/**
 * Retrieves a single exam attempt by its ID
 *
 * Fetches attempt details including exam information and student information.
 *
 * @param int $attemptId The ID of the exam attempt to retrieve
 * @return array|null Returns an associative array with attempt data including:
 *                    exam_title, duration_minutes, show_results, student_name, student_email,
 *                    or null if not found
 * @since 1.0.0
 */
function getExamAttemptById($attemptId) {
    $query = "SELECT ea.*, e.title as exam_title, e.duration_minutes, e.show_results,
              u.full_name as student_name, u.email as student_email
              FROM exam_attempts ea
              JOIN exams e ON ea.exam_id = e.id
              JOIN users u ON ea.student_id = u.id
              WHERE ea.id = ?";
    return fetchOne($query, "s", [$attemptId]);
}

/**
 * Retrieves all exam attempts for a student, optionally filtered by exam
 *
 * Fetches attempts with calculated actual scores and total marks from student answers
 * and questions, ensuring accuracy even if exam configuration changes.
 *
 * @param int $studentId The ID of the student
 * @param int|null $examId Optional exam ID to filter attempts for a specific exam
 * @return array Returns an array of attempt records, each containing:
 *               attempt data plus actual_score and actual_total_marks (calculated values)
 * @since 1.0.0
 */
function getStudentExamAttempts($studentId, $examId = null) {
    if ($examId) {
        $query = "SELECT ea.*, e.title as exam_title,
                  COALESCE(score_data.actual_score, 0) as actual_score,
                  COALESCE(exam_marks.total_marks, e.total_marks, 0) as actual_total_marks
                  FROM exam_attempts ea
                  JOIN exams e ON ea.exam_id = e.id
                  LEFT JOIN (
                      SELECT 
                          sa.attempt_id,
                          SUM(COALESCE(sa.marks_obtained, 0)) as actual_score
                      FROM student_answers sa
                      GROUP BY sa.attempt_id
                  ) score_data ON score_data.attempt_id = ea.id
                  LEFT JOIN (
                      SELECT 
                          exam_id,
                          SUM(marks) as total_marks
                      FROM questions
                      GROUP BY exam_id
                  ) exam_marks ON exam_marks.exam_id = ea.exam_id
                  WHERE ea.student_id = ? AND ea.exam_id = ?
                  ORDER BY ea.created_at DESC";
        return fetchAll($query, "ss", [$studentId, $examId]);
    } else {
        $query = "SELECT ea.*, e.title as exam_title,
                  COALESCE(score_data.actual_score, 0) as actual_score,
                  COALESCE(exam_marks.total_marks, e.total_marks, 0) as actual_total_marks
                  FROM exam_attempts ea
                  JOIN exams e ON ea.exam_id = e.id
                  LEFT JOIN (
                      SELECT 
                          sa.attempt_id,
                          SUM(COALESCE(sa.marks_obtained, 0)) as actual_score
                      FROM student_answers sa
                      GROUP BY sa.attempt_id
                  ) score_data ON score_data.attempt_id = ea.id
                  LEFT JOIN (
                      SELECT 
                          exam_id,
                          SUM(marks) as total_marks
                      FROM questions
                      GROUP BY exam_id
                  ) exam_marks ON exam_marks.exam_id = ea.exam_id
                  WHERE ea.student_id = ?
                  ORDER BY ea.created_at DESC";
        return fetchAll($query, "s", [$studentId]);
    }
}

/**
 * Unflags an exam attempt that was previously flagged for cheating
 *
 * Allows an admin to review and unflag an attempt if it was a system error.
 * When unflagged, the attempt can be graded and the student can retake the exam.
 *
 * @param int $attemptId The ID of the exam attempt to unflag
 * @param int $adminId The ID of the admin user unflagging the attempt
 * @return array Returns an array with 'success' (bool) and 'message' (string) keys
 * @since 1.0.0
 */
function unflagExamAttempt($attemptId, $adminId) {
    $attempt = getExamAttemptById($attemptId);
    if (!$attempt) {
        return ['success' => false, 'message' => 'Exam attempt not found'];
    }
    
    if (!$attempt['flagged_for_cheating']) {
        return ['success' => false, 'message' => 'This attempt is not flagged for cheating'];
    }
    
    $query = "UPDATE exam_attempts 
              SET flagged_for_cheating = 0, 
                  unflagged_by = ?, 
                  unflagged_at = NOW()
              WHERE id = ?";
    
    $result = executeUpdate($query, "ss", [$adminId, $attemptId]);
    
    if ($result !== false) {
        gradeExamAttempt($attemptId);
        calculateExamScore($attemptId);
        
        logActivity('exam_unflagged', 'exam_attempt', $attemptId, "Exam attempt unflagged by admin (ID: $adminId)");
        
        return ['success' => true, 'message' => 'Exam attempt unflagged successfully. Score has been calculated.'];
    }
    
    return ['success' => false, 'message' => 'Failed to unflag exam attempt'];
}

/**
 * Increments the tab switch counter for an exam attempt
 *
 * Used for anti-cheating detection. Increments the tab_switches field
 * in the exam_attempts table each time a student switches tabs/windows.
 *
 * @param int $attemptId The ID of the exam attempt
 * @return bool|int Returns the result of the update operation
 * @since 1.0.0
 */
function incrementTabSwitches($attemptId) {
    $query = "UPDATE exam_attempts SET tab_switches = tab_switches + 1 WHERE id = ?";
    return executeUpdate($query, "s", [$attemptId]);
}

/**
 * Retrieves statistics for a specific exam
 *
 * Fetches pre-calculated statistics from the exam_statistics view/table.
 *
 * @param int $examId The ID of the exam
 * @return array|null Returns exam statistics or null if not found
 * @since 1.0.0
 */
function getExamStatistics($examId) {
    $query = "SELECT * FROM exam_statistics WHERE exam_id = ?";
    return fetchOne($query, "s", [$examId]);
}

/**
 * Retrieves performance statistics for a specific student
 *
 * Fetches pre-calculated performance metrics from the student_performance view/table.
 *
 * @param int $studentId The ID of the student
 * @return array|null Returns student performance data or null if not found
 * @since 1.0.0
 */
function getStudentPerformance($studentId) {
    $query = "SELECT * FROM student_performance WHERE student_id = ?";
    return fetchOne($query, "s", [$studentId]);
}
?>


