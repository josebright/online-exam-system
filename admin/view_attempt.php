<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireAdmin();

$attemptId = isset($_GET['attempt_id']) ? sanitizeInput($_GET['attempt_id']) : null;

if (!$attemptId) {
    $_SESSION['error'] = 'Invalid attempt ID';
    header('Location: results.php');
    exit();
}

$attempt = getExamAttemptById($attemptId);

if (!$attempt) {
    $_SESSION['error'] = 'Exam attempt not found';
    header('Location: results.php');
    exit();
}

$exam = getExamById($attempt['exam_id']);
$questions = getExamQuestions($attempt['exam_id']);
$studentAnswers = getStudentAnswers($attemptId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isset($_POST['action']) && $_POST['action'] === 'regrade') {
            gradeExamAttempt($attemptId);
            calculateExamScore($attemptId);
            
            $attempt = getExamAttemptById($attemptId);
            $studentAnswers = getStudentAnswers($attemptId);
            $_SESSION['success'] = 'Exam re-graded successfully';
            header('Location: view_attempt.php?attempt_id=' . $attemptId);
            exit();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'unflag') {
            $result = unflagExamAttempt($attemptId, getCurrentUserId());
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            header('Location: view_attempt.php?attempt_id=' . $attemptId);
            exit();
        }
    }
}

$answerMap = [];
$totalMarksObtained = 0;
$totalMarksPossible = 0;

foreach ($studentAnswers as $answer) {
    $answerMap[$answer['question_id']] = $answer;
    $totalMarksObtained += (float)$answer['marks_obtained'];
}

foreach ($questions as $question) {
    $totalMarksPossible += (int)$question['marks'];
}

if ($attempt['duration_seconds'] <= 0 && $attempt['start_time'] && $attempt['submit_time']) {
    $attempt['duration_seconds'] = strtotime($attempt['submit_time']) - strtotime($attempt['start_time']);
} elseif ($attempt['duration_seconds'] <= 0 && $attempt['start_time']) {
    $attempt['duration_seconds'] = time() - strtotime($attempt['start_time']);
}

$actualScore = $totalMarksObtained;
$actualTotalMarks = $totalMarksPossible > 0 ? $totalMarksPossible : $attempt['total_marks'];

$pageTitle = 'View Exam Attempt';

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4><i class="bi bi-eye me-2"></i>Exam Attempt Details</h4>
            <div>
                <?php if ($attempt['flagged_for_cheating'] && !$attempt['unflagged_at']): ?>
                    <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#unflagExamModal">
                        <i class="bi bi-flag-fill me-2"></i>Unflag Attempt
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#regradeExamModal">
                        <i class="bi bi-arrow-clockwise me-2"></i>Re-grade Exam
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($attempt['flagged_for_cheating']): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Flagged for Cheating</h5>
                <p class="mb-2"><strong>Reason:</strong> <?php echo htmlspecialchars($attempt['cheating_reason'] ?? 'No reason provided'); ?></p>
                <?php if ($attempt['unflagged_at']): ?>
                    <p class="mb-0"><strong>Unflagged:</strong> <?php echo date('M d, Y H:i:s', strtotime($attempt['unflagged_at'])); ?> 
                    <?php if ($attempt['unflagged_by']): 
                        $unflagger = getUserById($attempt['unflagged_by']);
                        if ($unflagger): ?>
                            by <?php echo htmlspecialchars($unflagger['full_name']); ?>
                        <?php endif; 
                    endif; ?>
                    </p>
                <?php else: ?>
                    <p class="mb-0">This attempt was flagged and has not been scored. Review the details and unflag if this was a system error.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Student Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong>Student Name:</strong> <?php echo htmlspecialchars($attempt['student_name']); ?>
                        </p>
                        <p class="mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($attempt['student_email']); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Exam:</strong> <?php echo htmlspecialchars($exam['title']); ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong>Attempt Number:</strong> <?php echo $attempt['attempt_number']; ?>
                        </p>
                        <p class="mb-2">
                            <strong>Start Time:</strong> <?php echo date('M d, Y H:i:s', strtotime($attempt['start_time'])); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Submit Time:</strong> <?php echo $attempt['submit_time'] ? date('M d, Y H:i:s', strtotime($attempt['submit_time'])) : 'N/A'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <h1 class="display-4 <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($attempt['percentage'], 2); ?>%
                        </h1>
                        <p class="text-muted">Score</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?php echo number_format($actualScore, 2); ?> / <?php echo $actualTotalMarks; ?></h3>
                        <p class="text-muted">Marks Obtained</p>
                    </div>
                    <div class="col-md-3">
                        <h3>
                            <?php 
                            $duration = max(0, (int)$attempt['duration_seconds']);
                            $hours = floor($duration / 3600);
                            $minutes = floor(($duration % 3600) / 60);
                            $seconds = $duration % 60;
                            echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                            ?>
                        </h3>
                        <p class="text-muted">Time Taken</p>
                    </div>
                    <div class="col-md-3">
                        <h3>
                            <?php if ($attempt['passed']): ?>
                                <span class="badge bg-success" style="font-size: 1.5rem;">PASSED</span>
                            <?php else: ?>
                                <span class="badge bg-danger" style="font-size: 1.5rem;">FAILED</span>
                            <?php endif; ?>
                        </h3>
                        <p class="text-muted">Status</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-4">
                        <p class="mb-1">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php echo $attempt['status'] === 'auto_submitted' ? 'warning' : 'success'; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $attempt['status'])); ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">
                            <strong>Tab Switches:</strong> 
                            <?php if ($attempt['tab_switches'] > 0): ?>
                                <span class="badge bg-warning"><?php echo $attempt['tab_switches']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1">
                            <strong>IP Address:</strong> 
                            <code class="d-inline-block ms-2 font-monospace" style="word-break: break-all; max-width: 100%; white-space: normal;"><?php echo htmlspecialchars($attempt['ip_address'] ?? 'N/A'); ?></code>
                        </p>
                    </div>
                </div>
                
                <?php if ($attempt['tab_switches'] > 0): ?>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This student switched tabs <?php echo $attempt['tab_switches']; ?> time(s) during the exam.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Detailed Answers</h5>
            </div>
            <div class="card-body">
                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $answer = $answerMap[$question['id']] ?? null;
                    $isCorrect = $answer && ($answer['is_correct'] == 1 || $answer['is_correct'] === true);
                    
                    $hasValidAnswer = false;
                    if ($answer) {
                        if (in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
                            $hasValidAnswer = !empty($answer['selected_option_ids']);
                        } elseif ($question['question_type'] === 'multiple_select') {
                            $hasValidAnswer = !empty($answer['selected_option_ids']);
                        } else {
                            $hasValidAnswer = !empty(trim($answer['answer_text'] ?? ''));
                        }
                    }
                    ?>
                    
                    <div class="card mb-3 border-<?php echo $isCorrect ? 'success' : ($hasValidAnswer ? 'danger' : 'warning'); ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                                    <span class="badge bg-info me-2"><?php echo ucwords(str_replace('_', ' ', $question['question_type'])); ?></span>
                                    <span class="badge bg-secondary"><?php echo $question['marks']; ?> marks</span>
                                </div>
                                <div>
                                    <?php if ($isCorrect): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Correct</span>
                                    <?php elseif ($hasValidAnswer): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Incorrect</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><i class="bi bi-dash-circle me-1"></i>Not Answered</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h6><?php echo htmlspecialchars($question['question_text']); ?></h6>
                            
                            <?php if (in_array($question['question_type'], ['multiple_choice', 'multiple_select', 'true_false'])): ?>
                                <?php
                                $options = getQuestionOptions($question['id']);
                                $selectedIds = [];
                                if ($answer && !empty($answer['selected_option_ids'])) {
                                    $selectedIds = array_filter(array_map('trim', explode(',', trim($answer['selected_option_ids']))));
                                    $selectedIds = array_values($selectedIds);
                                }
                                ?>
                                
                                <ul class="list-unstyled mt-3">
                                    <?php foreach ($options as $option): ?>
                                        <?php
                                        $isSelected = in_array($option['id'], $selectedIds);
                                        $isCorrectOption = $option['is_correct'];
                                        
                                        $class = '';
                                        $icon = 'bi-circle';
                                        
                                        if ($isCorrectOption) {
                                            $class = 'text-success fw-bold';
                                            $icon = 'bi-check-circle-fill';
                                        } elseif ($isSelected && !$isCorrectOption) {
                                            $class = 'text-danger';
                                            $icon = 'bi-x-circle-fill';
                                        }
                                        ?>
                                        <li class="mb-2 <?php echo $class; ?>">
                                            <i class="bi <?php echo $icon; ?> me-2"></i>
                                            <?php echo htmlspecialchars($option['option_text']); ?>
                                            <?php if ($isSelected): ?>
                                                <span class="badge bg-secondary ms-2">Student's Answer</span>
                                            <?php endif; ?>
                                            <?php if ($isCorrectOption): ?>
                                                <span class="badge bg-success ms-2">Correct Answer</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                            <?php elseif ($question['question_type'] === 'short_answer' || $question['question_type'] === 'fill_blank'): ?>
                                <div class="mt-3">
                                    <p class="mb-2">
                                        <strong>Student's Answer:</strong> 
                                        <?php 
                                        $studentAnswerText = $answer['answer_text'] ?? null;
                                        if ($answer && $studentAnswerText !== null && trim($studentAnswerText) !== '') {
                                            echo '<span class="ms-2">' . nl2br(htmlspecialchars($studentAnswerText)) . '</span>';
                                        } else {
                                            echo '<span class="text-muted ms-2"><em>Not answered</em></span>';
                                        }
                                        ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Correct Answer:</strong> 
                                        <?php 
                                        $correctAnswerText = $question['correct_answer'] ?? null;
                                        if ($correctAnswerText !== null && trim($correctAnswerText) !== '') {
                                            echo '<span class="ms-2 text-success">' . nl2br(htmlspecialchars($correctAnswerText)) . '</span>';
                                        } else {
                                            echo '<span class="text-muted ms-2"><em>Not set</em></span>';
                                        }
                                        ?>
                                    </p>
                                    <?php if ($answer && $answer['marks_obtained'] == 0 && $studentAnswerText !== null && trim($studentAnswerText) !== ''): ?>
                                        <div class="alert alert-info mt-2">
                                            <small><i class="bi bi-info-circle me-1"></i>This answer was marked incorrect or requires manual grading.</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 pt-3 border-top">
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <strong>Marks Obtained:</strong> <?php echo $answer ? number_format((float)$answer['marks_obtained'], 2) : '0.00'; ?> / <?php echo $question['marks']; ?>
                                        </small>
                                    </div>
                                    <?php if ($answer): ?>
                                        <div class="col-md-6 text-end">
                                            <small class="text-muted">
                                                <strong>Answered At:</strong> <?php echo $answer['answered_at'] ? date('M d, Y H:i:s', strtotime($answer['answered_at'])) : 'N/A'; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($attempt['flagged_for_cheating'] && !$attempt['unflagged_at']): ?>
<div class="modal fade" id="unflagExamModal" tabindex="-1" aria-labelledby="unflagExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="unflagExamModalLabel">
                    <i class="bi bi-flag-fill me-2"></i>Unflag Exam Attempt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="unflag">
                    <p>Are you sure you want to unflag this exam attempt?</p>
                    <div class="alert alert-info">
                        <strong>Reason for flagging:</strong><br>
                        <?php echo htmlspecialchars($attempt['cheating_reason'] ?? 'No reason provided'); ?>
                    </div>
                    <p>Unflagging will:</p>
                    <ul>
                        <li>Calculate and record the exam score</li>
                        <li>Allow the student to retake the exam (if retakes are enabled)</li>
                        <li>Record that you reviewed and unflagged this attempt</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-flag me-2"></i>Unflag Attempt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="regradeExamModal" tabindex="-1" aria-labelledby="regradeExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="regradeExamModalLabel">
                    <i class="bi bi-arrow-clockwise me-2"></i>Confirm Re-grade Exam
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to re-grade this exam?</p>
                <div class="alert alert-info mb-0">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        This will recalculate all marks based on current answers. This action cannot be undone.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <form method="POST" class="d-inline" id="regradeExamForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="regrade">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise me-2"></i>Yes, Re-grade
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

