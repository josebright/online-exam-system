<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireStudent();

$pageTitle = 'My Results';
$studentId = getCurrentUserId();

$attempts = getStudentExamAttempts($studentId);
$performance = getStudentPerformance($studentId);

include 'includes/header.php';
?>

<h2 class="mb-4">My Results</h2>

<?php if ($performance): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-clipboard-check" style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_taken'] ?? 0; ?></h3>
                    <p class="mb-0">Total Exams</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart" style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo number_format($performance['average_score'] ?? 0, 1); ?>%</h3>
                    <p class="mb-0">Average Score</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_passed'] ?? 0; ?></h3>
                    <p class="mb-0">Passed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                    <h3 class="mt-2"><?php echo $performance['exams_failed'] ?? 0; ?></h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Exam History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Attempt</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2">No exam attempts yet</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($attempt['exam_title']); ?></strong></td>
                                <td>#<?php echo $attempt['attempt_number']; ?></td>
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


