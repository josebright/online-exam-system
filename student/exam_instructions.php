<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireStudent();

$examId = isset($_GET['exam_id']) ? sanitizeInput($_GET['exam_id']) : null;

if (!$examId) {
    $_SESSION['error'] = 'Invalid exam';
    header('Location: dashboard.php');
    exit();
}

$exam = getExamById($examId);
if (!$exam || $exam['status'] !== 'published') {
    $_SESSION['error'] = 'Exam not available';
    header('Location: dashboard.php');
    exit();
}

$questions = getExamQuestions($examId);
$studentId = getCurrentUserId();

$attempts = getStudentExamAttempts($studentId, $examId);
$submittedAttempts = array_filter($attempts, function($a) {
    return in_array($a['status'], ['submitted', 'auto_submitted']);
});
$submittedCount = count($submittedAttempts);
$hasAttempted = $submittedCount > 0;
$canRetake = $exam['allow_retake'] && $submittedCount < $exam['max_attempts'];

if ($hasAttempted && !$canRetake) {
    if (!$exam['allow_retake']) {
        $_SESSION['error'] = 'You have already completed this exam. Retakes are not allowed.';
    } else {
        $_SESSION['error'] = "You have reached the maximum number of attempts ({$exam['max_attempts']}) for this exam.";
    }
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $result = startExamAttempt($examId, $studentId);
        
        if ($result['success']) {
            header('Location: take_exam.php?attempt_id=' . $result['attempt_id']);
            exit();
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
}

$pageTitle = 'Exam Instructions';
$csrfToken = generateCSRFToken();

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3 class="mb-0"><i class="bi bi-info-circle me-2"></i>Exam Instructions</h3>
            </div>
            <div class="card-body p-4">
                <h4 class="mb-3"><?php echo htmlspecialchars($exam['title']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($exam['description']); ?></p>
                
                <hr>
                
                <h5 class="mb-3"><i class="bi bi-list-check me-2"></i>Exam Details</h5>
                <ul class="list-group mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-question-circle text-primary me-2"></i>Total Questions</span>
                        <strong><?php echo count($questions); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clock text-warning me-2"></i>Duration</span>
                        <strong><?php echo $exam['duration_minutes']; ?> Minutes</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-trophy text-success me-2"></i>Total Marks</span>
                        <strong><?php echo $exam['total_marks']; ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check-circle text-info me-2"></i>Passing Marks</span>
                        <strong><?php echo $exam['passing_marks']; ?></strong>
                    </li>
                </ul>
                
                <div class="alert alert-warning" data-no-auto-dismiss="true">
                    <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Important Instructions</h6>
                    <ul class="mb-0">
                        <li><strong>Read Carefully:</strong> Read each question thoroughly before selecting your answer</li>
                        <li><strong>Timer:</strong> The countdown timer will start immediately when you begin the exam and cannot be paused</li>
                        <li><strong>Auto-Submit:</strong> The exam will be automatically submitted when time expires or you are away for <?php echo AUTO_SAVE_INTERVAL; ?> seconds</li>
                        <li><strong>Auto-Save:</strong> Your answers are automatically saved every <?php echo AUTO_SAVE_INTERVAL; ?> seconds</li>
                        <li><strong>Connection:</strong> Ensure stable internet connection. If you refresh the page, you can continue where you left off</li>
                        <li><strong>Tab Switching:</strong> Excessive tab switching (more than <?php echo TAB_SWITCH_WARNING_THRESHOLD; ?> times) will result in automatic exam termination</li>
                        <li><strong>Browser:</strong> Do not close your browser or navigate away from the exam page until you submit</li>
                        <?php if (!$exam['allow_retake']): ?>
                            <li class="text-danger"><strong>Single Attempt:</strong> You can only attempt this exam once - make it count!</li>
                        <?php else: ?>
                            <li><strong>Retakes:</strong> You can attempt this exam up to <?php echo $exam['max_attempts']; ?> time(s)</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <?php if ($hasAttempted): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        You have already attempted this exam <?php echo $submittedCount; ?> time(s). 
                        This will be attempt #<?php echo $submittedCount + 1; ?>.
                        <?php if ($exam['allow_retake']): ?>
                            (Maximum: <?php echo $exam['max_attempts']; ?> attempts)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <form method="POST" id="startExamForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <button type="button" class="btn btn-gradient btn-lg w-100" data-bs-toggle="modal" data-bs-target="#startExamModal">
                            <i class="bi bi-play-circle me-2"></i>I Understand, Start Exam
                        </button>
                    </form>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="startExamModal" tabindex="-1" aria-labelledby="startExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white">
                <h5 class="modal-title" id="startExamModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Start Exam
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you ready to start the exam?</p>
                <div class="alert alert-info mb-0" data-no-auto-dismiss="true">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        Make sure you have read all the instructions carefully before proceeding.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-gradient" id="confirmStartExam">
                    <i class="bi bi-play-circle me-2"></i>Yes, Start Exam
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$additionalJS = <<<'JS'
<script>
$(document).ready(function() {
    $('#confirmStartExam').click(function() {
        $('#startExamForm').submit();
    });
});
</script>
JS;

include 'includes/footer.php';
?>


