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

$attempt = getExamAttemptById($attemptId);
$autoSubmit = false;

if (isset($_POST['auto_submit']) && $_POST['auto_submit'] === 'true') {
    $autoSubmit = true;
}

if (!$autoSubmit && $attempt) {
    $exam = getExamById($attempt['exam_id']);
    if ($exam && $attempt['start_time']) {
        $startTime = strtotime($attempt['start_time']);
        $expectedEndTime = $startTime + ((int)$exam['duration_minutes'] * 60);
        $currentTime = time();
        
        if (abs($currentTime - $expectedEndTime) <= 5) {
            $autoSubmit = true;
        }
        
        if ($attempt['status'] === 'auto_submitted') {
            $autoSubmit = true;
        }
    }
}

$result = submitExamAttempt($attemptId, $autoSubmit);

if ($result['success']) {
    sendJSONResponse([
        'success' => true,
        'message' => 'Exam submitted successfully',
        'attempt_id' => $attemptId
    ]);
} else {
    sendJSONResponse([
        'success' => false,
        'message' => $result['message']
    ], 500);
}
?>


