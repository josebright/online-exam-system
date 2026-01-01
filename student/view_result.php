<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireStudent();

$attemptId = isset($_GET['attempt_id']) ? sanitizeInput($_GET['attempt_id']) : null;

if (!$attemptId) {
    $_SESSION['error'] = 'Invalid attempt';
    header('Location: dashboard.php');
    exit();
}

$attempt = getExamAttemptById($attemptId);

if (!$attempt || $attempt['student_id'] != getCurrentUserId()) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: dashboard.php');
    exit();
}

$exam = getExamById($attempt['exam_id']);
$questions = getExamQuestions($attempt['exam_id']);
$studentAnswers = getStudentAnswers($attemptId);

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

$pageTitle = 'Exam Result';

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <?php if ($attempt['flagged_for_cheating'] && !$attempt['unflagged_at']): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Exam Attempt Under Review</h5>
                <p class="mb-0">Your exam submission has been flagged for review. Your score will not be recorded until an administrator reviews your attempt. Please contact the administrator if you believe this is an error.</p>
            </div>
        <?php elseif ($attempt['flagged_for_cheating'] && $attempt['unflagged_at']): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-info-circle-fill me-2"></i>Review Completed</h5>
                <p class="mb-0">Your exam attempt was reviewed and unflagged. Your score has been calculated and recorded.</p>
            </div>
        <?php endif; ?>
        
        <!-- Result Summary -->
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
                
                <div class="row mt-4">
                    <div class="col-md-3">
                        <h1 class="display-4 <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($attempt['percentage'], 2); ?>%
                        </h1>
                        <p class="text-muted">Your Score</p>
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
                
                <?php if ($attempt['tab_switches'] > 0): ?>
                    <div class="alert alert-warning mt-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Tab switches detected: <?php echo $attempt['tab_switches']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($exam['show_results']): ?>
            <!-- Detailed Results -->
            <h4 class="mb-3">Detailed Results</h4>
            
            <?php foreach ($questions as $index => $question): ?>
                <?php
                $answer = $answerMap[$question['id']] ?? null;
                $isCorrect = $answer && $answer['is_correct'];
                
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
                
                <div class="card mb-3">
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
                            $selectedIds = $answer ? explode(',', $answer['selected_option_ids']) : [];
                            ?>
                            
                            <ul class="list-unstyled mt-3">
                                <?php foreach ($options as $option): ?>
                                    <?php
                                    $isSelected = in_array($option['id'], $selectedIds);
                                    $isCorrectOption = $option['is_correct'];
                                    
                                    $class = '';
                                    $icon = 'bi-circle';
                                    
                                    if ($isCorrectOption) {
                                        $class = 'text-success';
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
                                            <span class="badge bg-secondary ms-2">Your Answer</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                        <?php elseif ($question['question_type'] === 'short_answer' || $question['question_type'] === 'fill_blank'): ?>
                            <div class="mt-3">
                                <strong>Your Answer:</strong>
                                <p class="ms-3"><?php echo $answer ? htmlspecialchars($answer['answer_text']) : '<em>Not answered</em>'; ?></p>
                                <?php if ($answer && $answer['marks_obtained'] == 0): ?>
                                    <div class="alert alert-info">
                                        <small><i class="bi bi-info-circle me-1"></i>This answer requires manual grading by the instructor.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                Marks: <?php echo $answer ? number_format((float)$answer['marks_obtained'], 2) : '0.00'; ?> / <?php echo $question['marks']; ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Detailed results are not available for this exam.
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4 mb-5">
            <a href="dashboard.php" class="btn btn-gradient btn-lg">
                <i class="bi bi-house me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


