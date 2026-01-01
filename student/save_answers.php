<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

header('Content-Type: application/json');

startSecureSession();

if (!isLoggedIn() || !isStudent()) {
    sendJSONResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    sendJSONResponse(['success' => false, 'message' => 'Invalid security token'], 403);
}

$attemptId = isset($_POST['attempt_id']) ? sanitizeInput($_POST['attempt_id']) : null;

if (!$attemptId) {
    sendJSONResponse(['success' => false, 'message' => 'Attempt ID required'], 400);
}

$attempt = getExamAttemptById($attemptId);

if (!$attempt || $attempt['student_id'] != getCurrentUserId()) {
    sendJSONResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
}

if ($attempt['status'] !== 'in_progress') {
    sendJSONResponse(['success' => false, 'message' => 'Exam already submitted'], 400);
}

$savedCount = 0;

foreach ($_POST as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        $questionId = str_replace('answer_', '', $key);
        if (strlen($questionId) !== 36 || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $questionId)) {
            continue;
        }
        
        $answerText = null;
        $selectedOptionIds = null;
        
        if (is_array($value)) {
            $validUuids = array_filter($value, function($id) {
                return strlen($id) === 36 && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id);
            });
            $selectedOptionIds = implode(',', $validUuids);
        } elseif (strlen($value) === 36 && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $selectedOptionIds = $value;
        } else {
            $answerText = sanitizeInput($value);
        }
        
        if (saveStudentAnswer($attemptId, $questionId, $answerText, $selectedOptionIds)) {
            $savedCount++;
        }
    }
}

sendJSONResponse([
    'success' => true,
    'message' => 'Answers saved',
    'saved_count' => $savedCount
]);
?>


