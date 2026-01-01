<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireStudent();

$attemptId = isset($_GET['attempt_id']) ? sanitizeInput($_GET['attempt_id']) : null;

if (!$attemptId) {
    $_SESSION['error'] = 'Invalid exam attempt';
    header('Location: dashboard.php');
    exit();
}

$attempt = getExamAttemptById($attemptId);

if (!$attempt || $attempt['student_id'] != getCurrentUserId()) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: dashboard.php');
    exit();
}

if ($attempt['status'] !== 'in_progress') {
    $_SESSION['error'] = 'This exam has already been submitted';
    header('Location: view_result.php?attempt_id=' . $attemptId);
    exit();
}

$warningThreshold = TAB_SWITCH_WARNING_THRESHOLD;
$terminationThreshold = $warningThreshold;

if ($attempt['tab_switches'] >= $terminationThreshold) {
    submitExamAttempt($attemptId, true);
    $_SESSION['error'] = 'Your exam has been terminated due to excessive tab switching.';
    header('Location: view_result.php?attempt_id=' . $attemptId);
    exit();
}

$exam = getExamById($attempt['exam_id']);
$questions = getExamQuestions($attempt['exam_id'], $exam['shuffle_questions']);
$studentAnswers = getStudentAnswers($attemptId);

$answerMap = [];
foreach ($studentAnswers as $answer) {
    $answerMap[$answer['question_id']] = $answer;
}

if (empty($attempt['start_time'])) {
    $_SESSION['error'] = 'Exam start time not set. Please contact administrator.';
    header('Location: dashboard.php');
    exit();
}

$startTime = strtotime($attempt['start_time']);
$currentTime = time();
$elapsedSeconds = max(0, $currentTime - $startTime);
$totalSeconds = (int)$exam['duration_minutes'] * 60;
$remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);


if ($remainingSeconds <= 0) {
    submitExamAttempt($attemptId, true);
    $_SESSION['success'] = 'Exam auto-submitted due to time expiration';
    header('Location: view_result.php?attempt_id=' . $attemptId);
    exit();
}

$pageTitle = 'Taking Exam';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .exam-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .timer {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .timer.warning {
            color: #ffc107;
            animation: pulse 1s infinite;
        }
        
        .timer.danger {
            color: #dc3545;
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .question-nav {
            position: sticky;
            top: 80px;
        }
        
        .question-nav-item {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
            border: 2px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            background-color: transparent;
        }
        
        .question-nav-item:hover {
            background-color: #e9ecef;
        }
        
        .question-nav-item.answered {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .question-nav-item.current {
            background-color: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }
        
        .question-nav-item.answered.current {
            background-color: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
        }
        
        .question-nav-item.d-inline-flex {
            min-width: 40px;
            min-height: 40px;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
        }
        
        .question-nav-item.current.d-inline-flex {
            background-color: #667eea !important;
            color: white !important;
            border-color: #667eea !important;
        }
        
        .question-card {
            display: none;
        }
        
        .question-card.active {
            display: block;
        }
        
        .option-label {
            cursor: pointer;
            padding: 1rem;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s;
            margin-bottom: 0.5rem;
        }
        
        .option-label:hover {
            background-color: #f8f9fa;
            border-color: #667eea;
        }
        
        .option-label input:checked ~ .option-text {
            font-weight: bold;
        }
        
        .option-label input:checked {
            accent-color: #667eea;
        }
        
        #tabSwitchWarning {
            display: none;
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1050;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .exam-header {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .question-card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: box-shadow 0.3s;
        }
        
        .question-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .option-label {
            transition: all 0.2s;
        }
        
        .option-label:hover {
            transform: translateX(5px);
        }
        
        .question-nav-item {
            transition: all 0.2s;
        }
        
        .question-nav-item:hover {
            transform: scale(1.1);
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .review-question-item {
            transition: all 0.3s;
        }
        
        .review-question-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
                </div>
                <div class="col-md-4 text-center">
                    <div class="timer" id="timer" data-remaining="<?php echo $remainingSeconds; ?>">
                        <i class="bi bi-clock me-2"></i><span id="timerDisplay">00:00:00</span>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-light" id="submitExamBtn">
                        <i class="bi bi-check-circle me-2"></i>Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="tabSwitchWarning" class="alert alert-warning alert-dismissible fade" style="display: none;">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
            <div class="flex-grow-1">
                <strong>Tab Switch Detected!</strong>
                <p class="mb-0">You have switched tabs <strong id="tabSwitchCount">0</strong> time(s).</p>
                <small>Excessive tab switching may result in exam termination.</small>
            </div>
            <button type="button" class="btn-close" aria-label="Close"></button>
        </div>
    </div>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-9">
                <form id="examForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="attempt_id" value="<?php echo $attemptId; ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="card question-card mb-4 <?php echo $index === 0 ? 'active' : ''; ?>" id="question-<?php echo $question['id']; ?>" data-question-index="<?php echo $index; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5>Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></h5>
                                    <span class="badge bg-primary"><?php echo $question['marks']; ?> mark(s)</span>
                                </div>
                                
                                <p class="lead"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                
                                <?php
                                $savedAnswer = $answerMap[$question['id']] ?? null;
                                $options = getQuestionOptions($question['id']);
                                
                                $hasValidAnswer = false;
                                if ($savedAnswer) {
                                    if (in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
                                        $hasValidAnswer = !empty($savedAnswer['selected_option_ids']);
                                    } elseif ($question['question_type'] === 'multiple_select') {
                                        $hasValidAnswer = !empty($savedAnswer['selected_option_ids']);
                                    } else {
                                        $hasValidAnswer = !empty(trim($savedAnswer['answer_text'] ?? ''));
                                    }
                                }
                                ?>
                                
                                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                    <?php foreach ($options as $option): ?>
                                        <label class="option-label d-block">
                                            <input type="radio" name="answer_<?php echo $question['id']; ?>" 
                                                   value="<?php echo $option['id']; ?>" class="me-2"
                                                   <?php echo ($savedAnswer && $savedAnswer['selected_option_ids'] == $option['id']) ? 'checked' : ''; ?>>
                                            <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($question['question_type'] === 'multiple_select'): ?>
                                    <?php
                                    $selectedOptions = $savedAnswer ? explode(',', $savedAnswer['selected_option_ids']) : [];
                                    ?>
                                    <?php foreach ($options as $option): ?>
                                        <label class="option-label d-block">
                                            <input type="checkbox" name="answer_<?php echo $question['id']; ?>[]" 
                                                   value="<?php echo $option['id']; ?>" class="me-2"
                                                   <?php echo in_array($option['id'], $selectedOptions) ? 'checked' : ''; ?>>
                                            <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <?php foreach ($options as $option): ?>
                                        <label class="option-label d-block">
                                            <input type="radio" name="answer_<?php echo $question['id']; ?>" 
                                                   value="<?php echo $option['id']; ?>" class="me-2"
                                                   <?php echo ($savedAnswer && $savedAnswer['selected_option_ids'] == $option['id']) ? 'checked' : ''; ?>>
                                            <span class="option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <textarea class="form-control" name="answer_<?php echo $question['id']; ?>" 
                                              rows="4" placeholder="Type your answer here..."><?php echo htmlspecialchars($savedAnswer['answer_text'] ?? ''); ?></textarea>
                                    
                                <?php elseif ($question['question_type'] === 'fill_blank'): ?>
                                    <input type="text" class="form-control" name="answer_<?php echo $question['id']; ?>" 
                                           placeholder="Type your answer here..." 
                                           value="<?php echo htmlspecialchars($savedAnswer['answer_text'] ?? ''); ?>">
                                <?php endif; ?>
                                
                                <hr class="my-4">
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary prev-btn" <?php echo $index === 0 ? 'disabled' : ''; ?>>
                                        <i class="bi bi-arrow-left me-2"></i>Previous
                                    </button>
                                    <button type="button" class="btn btn-gradient next-btn">
                                        <?php echo $index === count($questions) - 1 ? 'Review' : 'Next'; ?>
                                        <i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
                
                <div id="reviewView" style="display: none;">
                    <div class="card question-card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Review All Questions</h5>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToExamBtn">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Exam
                                </button>
            </div>
            
                            <?php foreach ($questions as $index => $question): ?>
                                <?php
                                $savedAnswer = $answerMap[$question['id']] ?? null;
                                $options = getQuestionOptions($question['id']);
                                
                                $hasValidAnswer = false;
                                if ($savedAnswer) {
                                    if (in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
                                        $hasValidAnswer = !empty($savedAnswer['selected_option_ids']);
                                    } elseif ($question['question_type'] === 'multiple_select') {
                                        $hasValidAnswer = !empty($savedAnswer['selected_option_ids']);
                                    } else {
                                        $hasValidAnswer = !empty(trim($savedAnswer['answer_text'] ?? ''));
                                    }
                                }
                                ?>
                                
                                <div class="card question-card mb-3 review-question-item" style="cursor: pointer;" data-question-index="<?php echo $index; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></h6>
                                            <div>
                                                <span class="badge bg-primary me-2"><?php echo $question['marks']; ?> mark(s)</span>
                                                <?php if ($hasValidAnswer): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Answered</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><i class="bi bi-exclamation-circle me-1"></i>Not Answered</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="lead mb-3"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                        
                                        <div class="mb-2">
                                            <strong class="text-muted">Your Answer:</strong>
                                            <?php if ($hasValidAnswer): ?>
                                                <?php if (in_array($question['question_type'], ['multiple_choice', 'true_false'])): ?>
                                                    <?php
                                                    $selectedOptionId = $savedAnswer['selected_option_ids'];
                                                    foreach ($options as $opt) {
                                                        if ($opt['id'] === $selectedOptionId) {
                                                            echo '<div class="mt-2"><span class="badge bg-light text-dark border">' . htmlspecialchars($opt['option_text']) . '</span></div>';
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                <?php elseif ($question['question_type'] === 'multiple_select'): ?>
                                                    <?php
                                                    $selectedIds = explode(',', $savedAnswer['selected_option_ids']);
                                                    $selectedOptions = [];
                                                    foreach ($options as $opt) {
                                                        if (in_array($opt['id'], $selectedIds)) {
                                                            $selectedOptions[] = $opt['option_text'];
                                                        }
                                                    }
                                                    if (!empty($selectedOptions)) {
                                                        echo '<div class="mt-2">';
                                                        foreach ($selectedOptions as $optText) {
                                                            echo '<span class="badge bg-light text-dark border me-1">' . htmlspecialchars($optText) . '</span>';
                                                        }
                                                        echo '</div>';
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <div class="mt-2 p-2 bg-light rounded border">
                                                        <?php echo nl2br(htmlspecialchars($savedAnswer['answer_text'] ?? '')); ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="mt-2 text-muted"><em>No answer provided</em></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary review-question-btn" data-question-index="<?php echo $index; ?>">
                                                <i class="bi bi-arrow-right-circle me-1"></i>Go to Question
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="backToExamFromReviewBtn">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Exam
                                </button>
                                <button type="button" class="btn btn-gradient" id="submitFromReviewBtn">
                                    <i class="bi bi-check-circle me-2"></i>Submit Exam
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="question-nav">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Question Navigator</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap" id="questionNavigation">
                                <?php foreach ($questions as $index => $question): ?>
                                    <?php
                                    $savedAnswer = $answerMap[$question['id']] ?? null;
                                    $isAnswered = false;
                                    if ($savedAnswer) {
                                        if (in_array($question['question_type'], ['multiple_choice', 'true_false'])) {
                                            $isAnswered = !empty($savedAnswer['selected_option_ids']);
                                        } elseif ($question['question_type'] === 'multiple_select') {
                                            $isAnswered = !empty($savedAnswer['selected_option_ids']);
                                        } else {
                                            $isAnswered = !empty(trim($savedAnswer['answer_text'] ?? ''));
                                        }
                                    }
                                    $classes = 'question-nav-item';
                                    if ($index === 0) $classes .= ' current';
                                    if ($isAnswered) $classes .= ' answered';
                                    ?>
                                    <div class="<?php echo $classes; ?>" data-question-index="<?php echo $index; ?>">
                                        <?php echo $index + 1; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="small">
                                <div class="mb-2">
                                    <span class="question-nav-item answered d-inline-flex me-2">&nbsp;</span>
                                    <span>Answered</span>
                                </div>
                                <div class="mb-2">
                                    <span class="question-nav-item current d-inline-flex me-2" style="background-color: #667eea !important; border-color: #667eea !important; color: white !important;">&nbsp;</span>
                                    <span>Current</span>
                                </div>
                                <div>
                                    <span class="question-nav-item d-inline-flex me-2">&nbsp;</span>
                                    <span>Not Answered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-body text-center">
                            <p class="mb-2">Auto-save active</p>
                            <small class="text-muted">Last saved: <span id="lastSaved">Never</span></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-gradient text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-check-circle me-2"></i>Submit Exam
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Are you sure you want to submit your exam?</p>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        You have answered <strong id="answeredCount">0</strong> out of <strong><?php echo count($questions); ?></strong> questions.
                    </div>
                    <p class="text-danger mb-0"><strong><i class="bi bi-exclamation-triangle me-1"></i>This action cannot be undone!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmSubmitBtn">
                        <i class="bi bi-check-circle me-2"></i>Yes, Submit Exam
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="tabSwitchWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Warning: Tab Switching Detected
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">You have switched tabs <strong id="warningTabCount">0</strong> time(s).</p>
                    <div class="alert alert-warning mb-0">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Warning:</strong> Next tab switching will result in automatic exam termination. Please stay on this page.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle me-2"></i>I Understand
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="tabSwitchTerminationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle-fill me-2"></i>Exam Terminated
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Your exam has been terminated due to excessive tab switching.</p>
                    <div class="alert alert-danger mb-0">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            You switched tabs too many times. The exam will be submitted automatically.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmTermination">
                        <i class="bi bi-check-circle me-2"></i>Understood
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="timeUpModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-bs-dismiss="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-clock-fill me-2"></i>Time's Up!
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Your exam time has expired.</p>
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Your exam will be submitted automatically in a few seconds.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="awayAutoSubmitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Exam Auto-Submitted
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Your exam has been automatically submitted.</p>
                    <div class="alert alert-warning mb-3">
                        <strong>Reason:</strong> You were away from the exam page for more than <?php echo AUTO_SAVE_INTERVAL; ?> seconds after switching tabs.
                    </div>
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            This action was taken to maintain exam integrity. You will be redirected to view your results.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="awayAutoSubmitOkBtn">
                        <i class="bi bi-check-circle me-2"></i>OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Error
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Shows loading state on a button without changing its design
         * @param {jQuery|string} button - Button element or selector
         * @param {string} loadingText - Optional text to show while loading
         */
        function setButtonLoading(button, loadingText = '') {
            const $btn = $(button);
            if ($btn.length === 0 || $btn.data('loading-active')) return;
            
            if (!$btn.data('original-html')) {
                $btn.data('original-html', $btn.html());
            }
            if (!$btn.data('original-disabled')) {
                $btn.data('original-disabled', $btn.prop('disabled'));
            }
            
            $btn.data('loading-active', true);
            $btn.prop('disabled', true);
            
            const $spinner = $('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
            const originalContent = $btn.contents();
            
            if (originalContent.length > 0) {
                $btn.prepend($spinner);
            } else {
                $btn.html($spinner[0].outerHTML + (loadingText || 'Loading...'));
            }
        }
        
        /**
         * Removes loading state from a button and restores original design
         * @param {jQuery|string} button - Button element or selector
         */
        function removeButtonLoading(button) {
            const $btn = $(button);
            if ($btn.length === 0 || !$btn.data('loading-active')) return;
            
            const originalHtml = $btn.data('original-html');
            const originalDisabled = $btn.data('original-disabled');
            
            if (originalHtml) {
                $btn.html(originalHtml);
                $btn.removeData('original-html');
            }
            
            if (originalDisabled !== undefined) {
                $btn.prop('disabled', originalDisabled);
                $btn.removeData('original-disabled');
            } else {
                $btn.prop('disabled', false);
            }
            
            $btn.removeData('loading-active');
        }
        
        let currentQuestionIndex = 0;
        let remainingSeconds = <?php echo $remainingSeconds; ?>;
        let timerInterval;
        let autoSaveInterval;
        let tabSwitchCount = <?php echo $attempt['tab_switches'] ?? 0; ?>;
        const attemptId = '<?php echo htmlspecialchars($attemptId, ENT_QUOTES, 'UTF-8'); ?>';
        const totalQuestions = <?php echo count($questions); ?>;
        let examInProgress = true;
        let tabSwitchHandler = null;
        let pageInitialized = false;
        let lastVisibilityChange = 0;
        let awayTimer = null;
        const AWAY_TIMEOUT = <?php echo AUTO_SAVE_INTERVAL * 1000; ?>;
        
        const SYNC_INTERVAL = 10000;
        const SYNC_THRESHOLD = 3;
        
        $(document).ready(function() {
            $('#tabSwitchCount').text(tabSwitchCount);
            
            if (remainingSeconds > 0) {
                updateTimerDisplay();
                
                fetchRemainingTime().then(function() {
            startTimer();
                }).catch(function() {
                    startTimer();
                });
            } else {
                autoSubmitExam();
            }
            
            const lastSavedKey = 'exam_last_saved_' + attemptId;
            const lastSavedTime = localStorage.getItem(lastSavedKey);
            if (lastSavedTime) {
                const savedDate = new Date(parseInt(lastSavedTime));
                $('#lastSaved').text(savedDate.toLocaleTimeString());
            }
            
            startAutoSave();
            setupTabSwitchDetection();
            setupNavigation();
            
            setTimeout(function() {
                updateQuestionNavigation();
                updateAnsweredCount();
            }, 100);
            
            setTimeout(function() {
                updateQuestionNavigation();
                updateAnsweredCount();
            }, 500);
            
            let updateTimeout;
            $('input, textarea').on('change input', function() {
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(function() {
                    updateQuestionNavigation();
                    updateAnsweredCount();
                }, 100);
            });
            
            $('#submitModal').on('shown.bs.modal', function() {
                updateAnsweredCount();
            });
            
            window.addEventListener('beforeunload', function(e) {
                if ($('#submitModal').hasClass('show') || $('#tabSwitchTerminationModal').hasClass('show')) {
                    return;
                }
                e.preventDefault();
                e.returnValue = '';
            });
        });
        
        let syncInterval = null;
        
        function fetchRemainingTime() {
            return $.ajax({
                url: 'get_exam_time.php',
                method: 'GET',
                data: { attempt_id: attemptId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.time_expired || response.remaining_seconds <= 0) {
                            if (timerInterval) {
                                clearInterval(timerInterval);
                                timerInterval = null;
                            }
                            if (syncInterval) {
                                clearInterval(syncInterval);
                                syncInterval = null;
                            }
                            autoSubmitExam();
                            return;
                        }
                        
                        const newRemaining = response.remaining_seconds;
                        const diff = Math.abs(remainingSeconds - newRemaining);
                        
                        if (remainingSeconds === 0 || diff >= SYNC_THRESHOLD) {
                            remainingSeconds = newRemaining;
                            updateTimerDisplay();
                            
                            if (!timerInterval && remainingSeconds > 0) {
                                startTimer();
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                }
            });
        }
        
        function startTimer() {
            if (remainingSeconds <= 0) {
                return;
            }
            
            if (timerInterval) {
                return;
            }
            
            updateTimerDisplay();
            
            timerInterval = setInterval(function() {
                if (remainingSeconds > 0) {
                remainingSeconds--;
                updateTimerDisplay();
                
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                        timerInterval = null;
                        if (syncInterval) {
                            clearInterval(syncInterval);
                            syncInterval = null;
                        }
                    autoSubmitExam();
                    }
                }
            }, 1000);
            
            if (!syncInterval) {
                syncInterval = setInterval(function() {
                    fetchRemainingTime();
                }, SYNC_INTERVAL);
            }
        }
        
        function updateTimerDisplay() {
            const hours = Math.floor(remainingSeconds / 3600);
            const minutes = Math.floor((remainingSeconds % 3600) / 60);
            const seconds = remainingSeconds % 60;
            
            const display = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
            $('#timerDisplay').text(display);
            
            const $timer = $('#timer');
            $timer.removeClass('warning danger');
            
            if (remainingSeconds <= 60) {
                $timer.addClass('danger');
            } else if (remainingSeconds <= 300) {
                $timer.addClass('warning');
            }
            
        }
        
        function pad(num) {
            return num.toString().padStart(2, '0');
        }
        
        function startAutoSave() {
            autoSaveInterval = setInterval(function() {
                saveAnswers(false);
            }, <?php echo AUTO_SAVE_INTERVAL * 1000; ?>);
        }
        
        function saveAnswers(showMessage = true) {
            if ($('.modal.show').length > 0) {
                return Promise.resolve();
            }
            
            const $form = $('#examForm');
            if ($form.length === 0) {
                return Promise.resolve();
            }
            
            const formData = $form.serialize();
            const csrfToken = $('input[name="csrf_token"]').val();
            const attemptIdValue = $('input[name="attempt_id"]').val();
            
            let dataToSend = formData;
            if (!formData.includes('csrf_token=') && csrfToken) {
                dataToSend += (dataToSend ? '&' : '') + 'csrf_token=' + encodeURIComponent(csrfToken);
            }
            if (!formData.includes('attempt_id=') && attemptIdValue) {
                dataToSend += (dataToSend ? '&' : '') + 'attempt_id=' + encodeURIComponent(attemptIdValue);
            }
            
            if (!csrfToken || !attemptIdValue) {
                return Promise.resolve();
            }
            
            return $.ajax({
                url: 'save_answers.php',
                method: 'POST',
                data: dataToSend,
                dataType: 'json'
            }).then(function(response) {
                    if (response.success) {
                    const now = new Date();
                    const lastSavedKey = 'exam_last_saved_' + attemptId;
                    localStorage.setItem(lastSavedKey, now.getTime().toString());
                    $('#lastSaved').text(now.toLocaleTimeString());
                        updateQuestionNavigation();
                        if (showMessage) {
                            showNotification('Answers saved successfully', 'success');
                        }
                } else {
                    if (showMessage) {
                        showNotification('Failed to save answers: ' + (response.message || 'Unknown error'), 'danger');
                    }
                    return Promise.reject(new Error(response.message || 'Failed to save answers'));
                }
            }).catch(function(xhr, status, error) {
                let errorMessage = 'Failed to save answers';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                }
                
                if (showMessage) {
                    showNotification(errorMessage, 'danger');
                }
                return Promise.reject(new Error(errorMessage));
            });
        }
        
        function setupTabSwitchDetection() {
            setTimeout(function() {
                pageInitialized = true;
            }, 2000);
            
            tabSwitchHandler = function() {
                if (!examInProgress) {
                    return;
                }
                
                if (!pageInitialized) {
                    return;
                }
                
                const now = Date.now();
                if (now - lastVisibilityChange < 500) {
                    return;
                }
                lastVisibilityChange = now;
                
                if (document.hidden) {
                    tabSwitchCount++;
                    $('#tabSwitchCount').text(tabSwitchCount);
                    
                    const warningThreshold = <?php echo TAB_SWITCH_WARNING_THRESHOLD; ?>;
                    if (tabSwitchCount > 0 && tabSwitchCount < warningThreshold - 1) {
                        if (window.tabWarningTimeout) {
                            clearTimeout(window.tabWarningTimeout);
                            window.tabWarningTimeout = null;
                        }
                        const $alert = $('#tabSwitchWarning');
                        
                        if ($alert.length > 0) {
                            $alert.stop(true, true).removeAttr('style').css({
                                'display': 'block',
                                'visibility': 'visible',
                                'opacity': '0'
                            }).animate({
                                'opacity': '1'
                            }, 300);
                            
                            window.tabWarningTimeout = setTimeout(function() {
                                $alert.fadeOut(300);
                                window.tabWarningTimeout = null;
                            }, 10000);
                        }
                    }
                    
                    startAwayTimer();
                    
                    $.post('track_tab_switch.php', {
                        attempt_id: attemptId,
                        csrf_token: $('input[name="csrf_token"]').val()
                    }).done(function(response) {
                        if (!examInProgress) {
                            return;
                        }
                        
                        if (response.success) {
                            if (response.tab_switches !== undefined) {
                                tabSwitchCount = response.tab_switches;
                                $('#tabSwitchCount').text(tabSwitchCount);
                            }
                            
                            if (response.terminated) {
                                stopTabSwitchDetection();
                                clearAwayTimer();
                                $('#tabSwitchWarning').fadeOut();
                                $('#tabSwitchWarningModal').modal('hide');
                                $('#tabSwitchTerminationModal').modal('show');
                                return;
                            }
                            
                            const warningThreshold = <?php echo TAB_SWITCH_WARNING_THRESHOLD; ?>;
                            const terminationThreshold = <?php echo $terminationThreshold; ?>;
                            
                            if (tabSwitchCount >= terminationThreshold) {
                                stopTabSwitchDetection();
                                clearAwayTimer();
                                $('#tabSwitchWarning').fadeOut();
                                $('#tabSwitchWarningModal').modal('hide');
                                $('#tabSwitchTerminationModal').modal('show');
                            } 
                            else if (tabSwitchCount === warningThreshold - 1) {
                                $('#tabSwitchWarning').fadeOut();
                                $('#tabSwitchWarningModal').modal('show');
                            } 
                            if (tabSwitchCount > 0 && tabSwitchCount < warningThreshold - 1) {
                                if (window.tabWarningTimeout) {
                                    clearTimeout(window.tabWarningTimeout);
                                    window.tabWarningTimeout = null;
                                }
                                const $alert = $('#tabSwitchWarning');
                                if ($alert.length > 0) {
                                    $alert.stop(true, true).removeAttr('style').css({
                                        'display': 'block',
                                        'visibility': 'visible',
                                        'opacity': '0'
                                    }).animate({
                                        'opacity': '1'
                                    }, 300);
                                    
                                    window.tabWarningTimeout = setTimeout(function() {
                                        $alert.fadeOut(300);
                                        window.tabWarningTimeout = null;
                                    }, 10000);
                                }
                            }
                        }
                    });
                } else {
                    clearAwayTimer();
                }
            };
            
            document.addEventListener('visibilitychange', tabSwitchHandler);
        }
        
        function stopTabSwitchDetection() {
            examInProgress = false;
            clearAwayTimer();
            if (tabSwitchHandler) {
                document.removeEventListener('visibilitychange', tabSwitchHandler);
                tabSwitchHandler = null;
            }
        }
        
        function startAwayTimer() {
            clearAwayTimer();
            
            if (!examInProgress) {
                return;
            }
            
            awayTimer = setTimeout(function() {
                if (!examInProgress || !document.hidden) {
                    return;
                }
                
                stopTabSwitchDetection();
                
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                if (syncInterval) {
                    clearInterval(syncInterval);
                    syncInterval = null;
                }
                if (autoSaveInterval) {
                    clearInterval(autoSaveInterval);
                    autoSaveInterval = null;
                }
                
                const csrfToken = $('input[name="csrf_token"]').val();
                
                if (!csrfToken || !attemptId) {
                    window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    return;
                }
                
                $.ajax({
                    url: 'submit_exam.php',
                    method: 'POST',
                    data: {
                        attempt_id: attemptId,
                        csrf_token: csrfToken,
                        auto_submit: true,
                        reason: 'away_timeout'
                    },
                    dataType: 'json',
                    success: function(response) {
                        showAwayAutoSubmitModal();
                    },
                    error: function(xhr, status, error) {
                        showAwayAutoSubmitModal();
                    }
                });
            }, AWAY_TIMEOUT);
        }
        
        function clearAwayTimer() {
            if (awayTimer) {
                clearTimeout(awayTimer);
                awayTimer = null;
            }
        }
        
        function showAwayAutoSubmitModal() {
            $('.modal').modal('hide');
            $('#awayAutoSubmitModal').modal('show');
        }
        
        function terminateExam() {
            stopTabSwitchDetection();
            
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
                autoSaveInterval = null;
            }
            
            const $btn = $('#confirmTermination');
            const csrfToken = $('input[name="csrf_token"]').val();
            
            if (!csrfToken) {
                if ($btn.length > 0) {
                    removeButtonLoading($btn);
                }
                alert('Error: Security token not found. Please refresh the page.');
                return;
            }
            
            if (!attemptId) {
                if ($btn.length > 0) {
                    removeButtonLoading($btn);
                }
                alert('Error: Exam attempt ID not found. Please refresh the page.');
                return;
            }
            
            if ($btn.length > 0 && !$btn.data('loading-active')) {
                setButtonLoading($btn);
            }
            
            $.ajax({
                url: 'submit_exam.php',
                method: 'POST',
                data: {
                    attempt_id: attemptId,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    } else {
                        $('.modal').modal('hide');
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    }
                },
                error: function(xhr, status, error) {
                    let isAlreadySubmitted = false;
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message && (response.message.includes('already submitted') || response.message.includes('Exam already submitted'))) {
                            isAlreadySubmitted = true;
                        }
                    } catch (e) {
                        if (xhr.status === 400) {
                            isAlreadySubmitted = true;
                        }
                    }
                    
                    $('.modal').modal('hide');
                    $(window).off('beforeunload');
                    window.location.href = 'view_result.php?attempt_id=' + attemptId;
                }
            });
        }
        
        function setupNavigation() {
            $(document).off('click', '.next-btn').on('click', '.next-btn', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const btnText = $btn.text().trim();
                
                if (btnText.toLowerCase().includes('review') || currentQuestionIndex >= totalQuestions - 1) {
                    showReviewView();
                } else {
                saveAnswers(false);
                    showQuestion(currentQuestionIndex + 1);
                }
            });
            
            $('#backToExamBtn, #backToExamFromReviewBtn').click(function() {
                hideReviewView();
                const lastIndex = currentQuestionIndex >= 0 ? currentQuestionIndex : (totalQuestions - 1);
                showQuestion(lastIndex);
            });
            
            $('.review-question-btn').click(function(e) {
                e.stopPropagation();
                const index = parseInt($(this).data('question-index'));
                hideReviewView();
                showQuestion(index);
            });
            
            $('.review-question-item').click(function(e) {
                if (!$(e.target).closest('.review-question-btn').length) {
                    const index = parseInt($(this).data('question-index'));
                    hideReviewView();
                    showQuestion(index);
                }
            });
            
            $('#submitFromReviewBtn').click(function() {
                saveAnswers(false);
                updateAnsweredCount();
                $('#submitModal').modal('show');
            });
            
            $('.prev-btn').click(function() {
                saveAnswers(false);
                if (currentQuestionIndex > 0) {
                    showQuestion(currentQuestionIndex - 1);
                }
            });
            
            $('.question-nav-item').click(function() {
                const index = parseInt($(this).data('question-index'));
                saveAnswers(false);
                showQuestion(index);
            });
            
            $('#submitExamBtn').click(function() {
                saveAnswers(false);
                updateAnsweredCount();
                $('#submitModal').modal('show');
            });
            
            $('#confirmSubmitBtn').click(function(e) {
                e.preventDefault();
                const $btn = $(this);
                if ($btn.prop('disabled')) {
                    return false;
                }
                setButtonLoading($btn);
                submitExam();
            });
            
            $('#confirmTermination').click(function() {
                const $btn = $(this);
                if ($btn.prop('disabled') || $btn.data('loading-active')) {
                    return false;
                }
                setButtonLoading($btn);
                terminateExam();
            });
            
            $('#submitFromReviewBtn').click(function() {
                const $btn = $(this);
                setButtonLoading($btn);
            });
            
            $('#tabSwitchWarningModal').on('show.bs.modal', function() {
                $('#warningTabCount').text(tabSwitchCount);
                $('#tabSwitchWarning').fadeOut();
            });
            
            $('#timeUpModal').on('hide.bs.modal', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            $('#timeUpModal').on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
            
            $(document).on('click', '#tabSwitchWarning .btn-close', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.tabWarningTimeout) {
                    clearTimeout(window.tabWarningTimeout);
                    window.tabWarningTimeout = null;
                }
                $('#tabSwitchWarning').fadeOut();
                return false;
            });
            
            $(document).on('close.bs.alert', '#tabSwitchWarning', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.tabWarningTimeout) {
                    clearTimeout(window.tabWarningTimeout);
                    window.tabWarningTimeout = null;
                }
                $(this).fadeOut();
                return false;
            });
            
            $('#awayAutoSubmitOkBtn').click(function() {
                $(window).off('beforeunload');
                window.location.href = 'view_result.php?attempt_id=' + attemptId;
            });
        }
        
        function showQuestion(index) {
            $('#reviewView').hide();
            $('#examForm').show();
            $('.col-lg-3').show();
            
            const questionIndex = parseInt(index);
            
            $('.question-card').removeClass('active');
            $('.question-card[data-question-index="' + questionIndex + '"]').addClass('active');
            
            $('.question-nav-item').removeClass('current');
            $('.question-nav-item[data-question-index="' + questionIndex + '"]').addClass('current');
            
            currentQuestionIndex = questionIndex;
            
            $('.prev-btn').prop('disabled', questionIndex === 0);
            $('.next-btn').text(questionIndex === totalQuestions - 1 ? 'Review' : 'Next');
            
            updateQuestionNavigation();
            
            $('.question-nav-item').removeClass('current');
            $('.question-nav-item[data-question-index="' + questionIndex + '"]').addClass('current');
            
            window.scrollTo(0, 0);
        }
        
        function showReviewView() {
            saveAnswers(false);
            
            try {
                const success = buildReviewContent();
                
                if (!success) {
                    alert('Error loading review. Please try again.');
                    return;
                }
                
                setTimeout(function() {
                    $('#examForm').hide();
                    $('.col-lg-3').hide();
                    
                    const $reviewView = $('#reviewView');
                    
                    if ($reviewView.length === 0) {
                        alert('Review view not found. Please refresh the page.');
                        return;
                    }
                    
                    $reviewView.removeAttr('style');
                    $reviewView.css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                    
                    const $card = $reviewView.find('.card').first();
                    const $cardBody = $reviewView.find('.card-body').first();
                    
                    if ($card.length > 0) {
                        $card.css({
                            'display': 'block',
                            'visibility': 'visible'
                        });
                    }
                    
                    if ($cardBody.length > 0) {
                        $cardBody.css({
                            'display': 'block',
                            'visibility': 'visible',
                            'min-height': '100px'
                        });
                    }
                    
                    $reviewView.show();
                    $reviewView[0].offsetHeight;
                    
                    window.scrollTo(0, 0);
                    $('.question-nav-item').removeClass('current');
                }, 100);
            } catch (error) {
                alert('Error loading review: ' + error.message);
            }
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
        function buildReviewContent() {
            let reviewHTML = '';
            let processedCount = 0;
            
            const $questionCards = $('#examForm .question-card[data-question-index]');
            
            if ($questionCards.length === 0) {
                return false;
            }
            
            $questionCards.each(function() {
                processedCount++;
                const $card = $(this);
                const index = parseInt($card.data('question-index'));
                const questionId = $card.attr('id')?.replace('question-', '') || '';
                
                let questionText = $card.find('p.lead').first().text() || 
                                 $card.find('.lead').first().text() ||
                                 'Question ' + (index + 1);
                
                let marks = $card.find('.badge.bg-primary').first().text() || 
                           $card.find('.badge').first().text() ||
                           '1 mark(s)';
                
                let answerHTML = '';
                let isAnswered = false;
                
                if (questionId) {
                    const $checkedRadio = $('input[type="radio"][name="answer_' + questionId + '"]:checked');
                    if ($checkedRadio.length > 0) {
                        isAnswered = true;
                        const answerText = $checkedRadio.closest('label').find('.option-text').text() || 
                                        $checkedRadio.next('.option-text').text() ||
                                        $checkedRadio.val() ||
                                        'Selected';
                        answerHTML = `<div class="alert alert-light border mt-2 mb-0">${escapeHtml(answerText)}</div>`;
                    }
                    
                    if (!isAnswered) {
                        const $checkedBoxes = $('input[type="checkbox"][name="answer_' + questionId + '[]"]:checked');
                        if ($checkedBoxes.length > 0) {
                            isAnswered = true;
                            let answers = [];
                            $checkedBoxes.each(function() {
                                const answerText = $(this).closest('label').find('.option-text').text() || 
                                                 $(this).next('.option-text').text() ||
                                                 $(this).val() ||
                                                 'Selected';
                                answers.push(answerText);
                            });
                            answerHTML = `<div class="alert alert-light border mt-2 mb-0"><ul class="mb-0">${answers.map(a => '<li>' + escapeHtml(a) + '</li>').join('')}</ul></div>`;
                        }
                    }
                    
                    if (!isAnswered) {
                        const $textarea = $('textarea[name="answer_' + questionId + '"]');
                        const $textInput = $('input[type="text"][name="answer_' + questionId + '"]');
                        
                        if ($textarea.length > 0) {
                            const val = $textarea.val();
                            if (val && val.trim() !== '') {
                                isAnswered = true;
                                answerHTML = `<div class="alert alert-light border mt-2 mb-0">${escapeHtml(val)}</div>`;
                            }
                        } else if ($textInput.length > 0) {
                            const val = $textInput.val();
                            if (val && val.trim() !== '') {
                                isAnswered = true;
                                answerHTML = `<div class="alert alert-light border mt-2 mb-0">${escapeHtml(val)}</div>`;
                            }
                        }
                    }
                }
                
                if (!isAnswered) {
                    const $checkedRadio = $card.find('input[type="radio"]:checked');
                    if ($checkedRadio.length > 0) {
                        isAnswered = true;
                        const answerText = $checkedRadio.closest('label').find('.option-text').text() || $checkedRadio.val();
                        answerHTML = `<div class="alert alert-light border mt-2 mb-0">${escapeHtml(answerText)}</div>`;
                    }
                }
                
                if (!isAnswered) {
                    answerHTML = '<div class="alert alert-warning mt-2 mb-0"><em class="text-muted">No answer provided</em></div>';
                }
                
                const statusBadge = isAnswered 
                    ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Answered</span>'
                    : '<span class="badge bg-warning"><i class="bi bi-exclamation-circle me-1"></i>Not Answered</span>';
                
                reviewHTML += `
                    <div class="card mb-3 review-question-item" style="cursor: pointer;" data-question-index="${index}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">Question ${index + 1} of ${totalQuestions}</h6>
                                <div>
                                    <span class="badge bg-primary me-2">${marks}</span>
                                    ${statusBadge}
                                </div>
                            </div>
                            <p class="lead mb-3">${escapeHtml(questionText)}</p>
                            <div class="mb-2">
                                <strong class="text-muted">Your Answer:</strong>
                                ${answerHTML}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary review-question-btn" data-question-index="${index}">
                                <i class="bi bi-arrow-right-circle me-1"></i>Go to Question
                            </button>
                        </div>
                    </div>
                `;
            });
            
            if (reviewHTML === '') {
                reviewHTML = '<div class="alert alert-warning">Unable to load review content. Please try again.</div>';
            }
            
            const $reviewView = $('#reviewView');
            const $reviewCardBody = $reviewView.find('.card-body').first();
            
            if ($reviewView.length === 0) {
                        return false;
                    }
            
            if ($reviewCardBody.length === 0) {
                return false;
            }
            
            $reviewCardBody.html(`
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Review All Questions</h5>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="backToExamBtn">
                        <i class="bi bi-arrow-left me-2"></i>Back to Exam
                    </button>
                </div>
                ${reviewHTML}
                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" id="backToExamFromReviewBtn">
                        <i class="bi bi-arrow-left me-2"></i>Back to Exam
                    </button>
                    <button type="button" class="btn btn-gradient" id="submitFromReviewBtn">
                        <i class="bi bi-check-circle me-2"></i>Submit Exam
                    </button>
                </div>
            `);
            
            $('.review-question-btn, .review-question-item').off('click').on('click', function(e) {
                if (!$(e.target).closest('.review-question-btn').length && $(e.target).hasClass('review-question-item')) {
                    return;
                }
                const index = parseInt($(this).data('question-index'));
                hideReviewView();
                showQuestion(index);
            });
            
            $('#backToExamBtn, #backToExamFromReviewBtn').off('click').on('click', function() {
                hideReviewView();
                const lastIndex = currentQuestionIndex >= 0 ? currentQuestionIndex : (totalQuestions - 1);
                showQuestion(lastIndex);
            });
            
            $('#submitFromReviewBtn').off('click').on('click', function() {
                saveAnswers(false);
                updateAnsweredCount();
                $('#submitModal').modal('show');
            });
            
            return true;
        }
        
        function hideReviewView() {
            $('#reviewView').hide();
            $('#examForm').show();
            $('.col-lg-3').show();
        }
        
        function updateQuestionNavigation() {
            $('.question-nav-item[data-question-index]').each(function() {
                const $navItem = $(this);
                const index = parseInt($navItem.attr('data-question-index'));
                
                if (isNaN(index)) {
                    return;
                }
                
                const $card = $('.question-card[data-question-index="' + index + '"]');
                
                if ($card.length === 0) {
                    return;
                }
                
                const questionId = $card.attr('id')?.replace('question-', '') || '';
                let isAnswered = false;
                
                if (questionId) {
                    $('input[type="radio"][name="answer_' + questionId + '"]').each(function() {
                        if (this.checked === true) {
                        isAnswered = true;
                        return false;
                    }
                });
                
                    if (!isAnswered) {
                        $('input[type="checkbox"][name="answer_' + questionId + '[]"]').each(function() {
                            if (this.checked === true) {
                                isAnswered = true;
                                return false;
                            }
                        });
                    }
                    
                    if (!isAnswered) {
                        const $textInput = $('textarea[name="answer_' + questionId + '"], input[type="text"][name="answer_' + questionId + '"]');
                        if ($textInput.length > 0) {
                            const val = $textInput.val();
                            if (val && val.trim() !== '') {
                                isAnswered = true;
                            }
                        }
                    }
                }
                
                if (!isAnswered && $card.length > 0) {
                    $card.find('input[type="radio"]').each(function() {
                        if (this.checked === true) {
                            isAnswered = true;
                            return false;
                        }
                    });
                    
                    if (!isAnswered) {
                        $card.find('input[type="checkbox"]').each(function() {
                            if (this.checked === true) {
                                isAnswered = true;
                                return false;
                            }
                        });
                    }
                    
                    if (!isAnswered) {
                        $card.find('textarea[name^="answer_"], input[type="text"][name^="answer_"]').each(function() {
                            const val = $(this).val();
                            if (val && val.trim() !== '') {
                                isAnswered = true;
                                return false;
                            }
                        });
                    }
                }
                
                if (isAnswered) {
                    if (!$navItem.hasClass('answered')) {
                    $navItem.addClass('answered');
                    }
                } else {
                    if ($navItem.hasClass('answered')) {
                    $navItem.removeClass('answered');
                    }
                }
            });
            
            if (typeof currentQuestionIndex !== 'undefined' && currentQuestionIndex !== null && !isNaN(currentQuestionIndex)) {
                const currentIndex = parseInt(currentQuestionIndex);
                $('.question-nav-item').removeClass('current');
                $('.question-nav-item[data-question-index="' + currentIndex + '"]').addClass('current');
            }
        }
        
        function updateAnsweredCount() {
            let count = $('.question-nav-item.answered[data-question-index]').length;
            count = Math.min(count, totalQuestions);
            $('#answeredCount').text(count);
        }
        
        function submitExam() {
            const $openModals = $('.modal.show').not('#submitModal');
            if ($openModals.length > 0) {
                return;
            }
            
            stopTabSwitchDetection();
            
            const $btn = $('#confirmSubmitBtn');
            
            if (timerInterval) {
            clearInterval(timerInterval);
                timerInterval = null;
            }
            if (syncInterval) {
                clearInterval(syncInterval);
                syncInterval = null;
            }
            if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
                autoSaveInterval = null;
            }
            
            saveAnswers(false).then(function() {
            $.ajax({
                url: 'submit_exam.php',
                method: 'POST',
                data: {
                    attempt_id: attemptId,
                    csrf_token: $('input[name="csrf_token"]').val()
                },
                    dataType: 'json',
                success: function(response) {
                    if (response.success) {
                            $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    } else {
                            $('.modal').modal('hide');
                            $(window).off('beforeunload');
                            window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    }
                },
                    error: function(xhr, status, error) {
                        let isAlreadySubmitted = false;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message && (response.message.includes('already submitted') || response.message.includes('Exam already submitted'))) {
                                isAlreadySubmitted = true;
                            }
                        } catch (e) {
                            if (xhr.status === 400) {
                                isAlreadySubmitted = true;
                            }
                        }
                        
                        $('.modal').modal('hide');
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                }
                });
            }).catch(function() {
                $.ajax({
                    url: 'submit_exam.php',
                    method: 'POST',
                    data: {
                        attempt_id: attemptId,
                        csrf_token: $('input[name="csrf_token"]').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $(window).off('beforeunload');
                            window.location.href = 'view_result.php?attempt_id=' + attemptId;
                        } else {
                            $('.modal').modal('hide');
                            $(window).off('beforeunload');
                            window.location.href = 'view_result.php?attempt_id=' + attemptId;
                        }
                    },
                    error: function(xhr, status, error) {
                        let isAlreadySubmitted = false;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message && (response.message.includes('already submitted') || response.message.includes('Exam already submitted'))) {
                                isAlreadySubmitted = true;
                            }
                        } catch (e) {
                            if (xhr.status === 400) {
                                isAlreadySubmitted = true;
                            }
                        }
                        
                        $('.modal').modal('hide');
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    }
                });
            });
        }
        
        function autoSubmitExam() {
            stopTabSwitchDetection();
            
            const timeUpModal = new bootstrap.Modal(document.getElementById('timeUpModal'), {
                backdrop: 'static',
                keyboard: false
            });
            timeUpModal.show();
            
            setTimeout(function() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                if (syncInterval) {
                    clearInterval(syncInterval);
                    syncInterval = null;
                }
                if (autoSaveInterval) {
                    clearInterval(autoSaveInterval);
                    autoSaveInterval = null;
                }
                
                const csrfToken = $('input[name="csrf_token"]').val();
                
                if (!csrfToken || !attemptId) {
                    $('.modal').modal('hide');
                    $(window).off('beforeunload');
                    window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    return;
                }
                
                $.ajax({
                    url: 'submit_exam.php',
                    method: 'POST',
                    data: {
                        attempt_id: attemptId,
                        csrf_token: csrfToken,
                        auto_submit: true,
                        reason: 'time_expired'
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('.modal').modal('hide');
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    },
                    error: function(xhr, status, error) {
                        $('.modal').modal('hide');
                        $(window).off('beforeunload');
                        window.location.href = 'view_result.php?attempt_id=' + attemptId;
                    }
                });
            }, 3000);
        }
        
        function showNotification(message, type) {
        }
    </script>
</body>
</html>


