<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

header('Content-Type: application/json');

startSecureSession();

if (!isLoggedIn() || !isStudent()) {
    sendJSONResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    exit();
}

$attemptId = isset($_GET['attempt_id']) ? sanitizeInput($_GET['attempt_id']) : null;

if (!$attemptId) {
    sendJSONResponse(['success' => false, 'message' => 'Attempt ID required'], 400);
    exit();
}

$attempt = getExamAttemptById($attemptId);

if (!$attempt || $attempt['student_id'] != getCurrentUserId()) {
    sendJSONResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
    exit();
}

if ($attempt['status'] !== 'in_progress') {
    sendJSONResponse(['success' => false, 'message' => 'Exam not in progress'], 400);
    exit();
}

$exam = getExamById($attempt['exam_id']);

if (!$exam) {
    sendJSONResponse(['success' => false, 'message' => 'Exam not found'], 404);
    exit();
}

if (empty($attempt['start_time'])) {
    sendJSONResponse(['success' => false, 'message' => 'Exam start time not set'], 500);
    exit();
}

$startTime = strtotime($attempt['start_time']);
$currentTime = time();
$elapsedSeconds = max(0, $currentTime - $startTime);
$totalSeconds = (int)$exam['duration_minutes'] * 60;
$remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);

if ($remainingSeconds <= 0) {
    submitExamAttempt($attemptId, true);
    sendJSONResponse([
        'success' => true,
        'remaining_seconds' => 0,
        'time_expired' => true,
        'message' => 'Time expired'
    ]);
    exit();
}

sendJSONResponse([
    'success' => true,
    'remaining_seconds' => $remainingSeconds,
    'time_expired' => false,
    'start_time' => $startTime,
    'current_time' => $currentTime,
    'total_seconds' => $totalSeconds
]);
?>

