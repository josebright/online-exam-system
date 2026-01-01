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

incrementTabSwitches($attemptId);

$attempt = getExamAttemptById($attemptId);
$tabSwitches = $attempt['tab_switches'] ?? 0;

$warningThreshold = TAB_SWITCH_WARNING_THRESHOLD;
$terminationThreshold = $warningThreshold;

if ($tabSwitches >= $terminationThreshold) {
    submitExamAttempt($attemptId, true);
    logActivity('exam_terminated', 'exam_attempt', $attemptId, 'Exam terminated due to excessive tab switching');
    sendJSONResponse([
        'success' => true,
        'message' => 'Tab switch recorded',
        'tab_switches' => $tabSwitches,
        'terminated' => true
    ]);
    exit();
}

logActivity('tab_switch_detected', 'exam_attempt', $attemptId, 'Student switched tabs during exam');

sendJSONResponse([
    'success' => true,
    'message' => 'Tab switch recorded',
    'tab_switches' => $tabSwitches,
    'terminated' => false
]);
?>


