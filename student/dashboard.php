<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'Dashboard';
$studentId = getCurrentUserId();

$publishedExams = getPublishedExams();
$myAttempts = getStudentExamAttempts($studentId);
$performance = getStudentPerformance($studentId);

include 'includes/header.php';
?>

<!-- Welcome Banner -->
<div class="card welcome-banner mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="mb-3 fw-bold">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="mb-0 fs-5"><i class="bi bi-quote"></i> Prepare with confidence. Excel with knowledge. <i class="bi bi-quote"></i></p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <i class="bi bi-mortarboard" style="font-size: 4rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<!-- Performance Stats -->
<?php if ($performance): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-check" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_taken'] ?? 0; ?></h3>
                    <p class="mb-0">Exams Taken</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-2"><?php echo number_format($performance['average_score'] ?? 0, 1); ?>%</h3>
                    <p class="mb-0">Average Score</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_passed'] ?? 0; ?></h3>
                    <p class="mb-0">Passed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_failed'] ?? 0; ?></h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Available Exams -->
<h4 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Available Exams</h4>

<div class="row g-4 mb-5">
    <?php if (empty($publishedExams)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3">No Exams Available</h5>
                    <p class="text-muted">Check back later for new exams</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($publishedExams as $exam): ?>
            <?php
            $attempts = getStudentExamAttempts($studentId, $exam['id']);
            $submittedAttempts = array_filter($attempts, function($a) {
                return in_array($a['status'], ['submitted', 'auto_submitted']);
            });
            $submittedCount = count($submittedAttempts);
            $hasAttempted = $submittedCount > 0;
            $canRetake = $exam['allow_retake'] && $submittedCount < $exam['max_attempts'];
            $canTakeExam = !$hasAttempted || $canRetake;
            
            $inProgressAttempt = null;
            foreach ($attempts as $attempt) {
                if ($attempt['status'] === 'in_progress') {
                    $inProgressAttempt = $attempt;
                    break;
                }
            }
            ?>
            <div class="col-md-6">
                <div class="card exam-card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)); ?>...</p>
                        
                        <div class="mb-3">
                            <span class="badge bg-primary me-2">
                                <i class="bi bi-question-circle me-1"></i><?php echo $exam['question_count']; ?> Questions
                            </span>
                            <span class="badge bg-info me-2">
                                <i class="bi bi-clock me-1"></i><?php echo $exam['duration_minutes']; ?> Minutes
                            </span>
                            <span class="badge bg-success">
                                <i class="bi bi-trophy me-1"></i><?php echo $exam['total_marks']; ?> Marks
                            </span>
                        </div>
                        
                        <?php if ($hasAttempted): ?>
                            <div class="alert alert-info mb-3">
                                <small>
                                    <i class="bi bi-info-circle me-1"></i>
                                    You have attempted this exam <?php echo count($attempts); ?> time(s)
                                </small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($inProgressAttempt): ?>
                            <a href="take_exam.php?attempt_id=<?php echo $inProgressAttempt['id']; ?>" class="btn btn-warning w-100">
                                <i class="bi bi-play-circle me-2"></i>Resume Exam
                            </a>
                        <?php elseif ($canTakeExam): ?>
                            <a href="exam_instructions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-gradient w-100">
                                <i class="bi bi-play-circle me-2"></i><?php echo $hasAttempted ? 'Retake Exam' : 'Start Exam'; ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="bi bi-lock me-2"></i>Already Completed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Recent Attempts -->
<?php if (!empty($myAttempts)): ?>
    <h4 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Attempts</h4>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($myAttempts, 0, 5) as $attempt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                <td><?php echo number_format((float)($attempt['actual_score'] ?? $attempt['score'] ?? 0), 2); ?> / <?php echo $attempt['actual_total_marks'] ?? $attempt['total_marks'] ?? 0; ?></td>
                                <td>
                                    <span class="badge <?php echo $attempt['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo number_format($attempt['percentage'], 2); ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = match($attempt['status']) {
                                        'in_progress' => 'warning',
                                        'submitted' => 'success',
                                        'auto_submitted' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusBadge; ?>">
                                        <?php 
                                        $statusText = ucwords(str_replace('_', ' ', $attempt['status']));
                                        if ($attempt['status'] === 'auto_submitted') {
                                            $statusText = 'Auto Submitted';
                                        }
                                        echo $statusText;
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($attempt['created_at'])); ?></td>
                                <td>
                                    <?php if ($attempt['status'] === 'in_progress'): ?>
                                        <a href="take_exam.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="bi bi-play-circle me-1"></i>Resume
                                        </a>
                                    <?php elseif (in_array($attempt['status'], ['submitted', 'auto_submitted'])): ?>
                                        <a href="view_result.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="my_results.php" class="btn btn-outline-primary">View All Results</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

