<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Results & Analytics';

$query = "SELECT * FROM exam_statistics ORDER BY exam_id DESC";
$examStats = fetchAll($query);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

$query = "SELECT COUNT(*) as total FROM exam_attempts ea
          WHERE ea.status IN ('submitted', 'auto_submitted')";
$totalResult = fetchOne($query);
$totalAttempts = $totalResult['total'] ?? 0;
$totalPages = ceil($totalAttempts / ITEMS_PER_PAGE);

$query = "SELECT ea.*, e.title as exam_title, u.full_name as student_name, u.email as student_email,
          COALESCE(score_data.actual_score, 0) as actual_score,
          COALESCE(exam_marks.total_marks, e.total_marks, 0) as actual_total_marks
          FROM exam_attempts ea
          JOIN exams e ON ea.exam_id = e.id
          JOIN users u ON ea.student_id = u.id
          LEFT JOIN (
              SELECT 
                  sa.attempt_id,
                  SUM(COALESCE(sa.marks_obtained, 0)) as actual_score
              FROM student_answers sa
              GROUP BY sa.attempt_id
          ) score_data ON score_data.attempt_id = ea.id
          LEFT JOIN (
              SELECT 
                  exam_id,
                  SUM(marks) as total_marks
              FROM questions
              GROUP BY exam_id
          ) exam_marks ON exam_marks.exam_id = ea.exam_id
          WHERE ea.status IN ('submitted', 'auto_submitted')
          ORDER BY ea.flagged_for_cheating DESC, ea.submit_time DESC
          LIMIT ? OFFSET ?";
$recentAttempts = fetchAll($query, "ii", [ITEMS_PER_PAGE, $offset]);

$query = "SELECT 
          COUNT(DISTINCT ea.student_id) as total_students,
          COUNT(ea.id) as total_attempts,
          AVG(ea.percentage) as avg_percentage,
          SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) as total_passed,
          SUM(CASE WHEN ea.passed = 0 THEN 1 ELSE 0 END) as total_failed
          FROM exam_attempts ea
          WHERE ea.status IN ('submitted', 'auto_submitted')";
$overallStats = fetchOne($query);

include 'includes/header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Students</p>
                        <h3 class="mb-0"><?php echo $overallStats['total_students'] ?? 0; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Attempts</p>
                        <h3 class="mb-0"><?php echo $overallStats['total_attempts'] ?? 0; ?></h3>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Average Score</p>
                        <h3 class="mb-0"><?php echo number_format($overallStats['avg_percentage'] ?? 0, 1); ?>%</h3>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pass Rate</p>
                        <?php
                        $totalAttempts = $overallStats['total_attempts'] ?? 0;
                        $totalPassed = $overallStats['total_passed'] ?? 0;
                        $passRate = $totalAttempts > 0 ? ($totalPassed / $totalAttempts) * 100 : 0;
                        ?>
                        <h3 class="mb-0"><?php echo number_format($passRate, 1); ?>%</h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Exam-wise Statistics</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Students</th>
                        <th>Attempts</th>
                        <th>Avg Score</th>
                        <th>Highest</th>
                        <th>Lowest</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Pass Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($examStats)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($examStats as $stat): ?>
                            <?php
                            $totalAttempts = $stat['total_attempts'] ?? 0;
                            $passRate = $totalAttempts > 0 ? (($stat['passed_count'] ?? 0) / $totalAttempts) * 100 : 0;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($stat['exam_title']); ?></strong></td>
                                <td><?php echo $stat['total_students'] ?? 0; ?></td>
                                <td><?php echo $totalAttempts; ?></td>
                                <td><?php echo number_format($stat['average_score'] ?? 0, 2); ?>%</td>
                                <td><span class="badge bg-success"><?php echo number_format($stat['highest_score'] ?? 0, 2); ?>%</span></td>
                                <td><span class="badge bg-danger"><?php echo number_format($stat['lowest_score'] ?? 0, 2); ?>%</span></td>
                                <td><?php echo $stat['passed_count'] ?? 0; ?></td>
                                <td><?php echo $stat['failed_count'] ?? 0; ?></td>
                                <td>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar <?php echo $passRate >= 70 ? 'bg-success' : ($passRate >= 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                                             role="progressbar" style="width: <?php echo $passRate; ?>%">
                                            <?php echo number_format($passRate, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Exam Attempts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Status</th>
                        <th>Tab Switches</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAttempts)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No attempts yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentAttempts as $attempt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['student_email']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                <td><?php echo number_format((float)($attempt['actual_score'] ?? $attempt['score'] ?? 0), 2); ?> / <?php echo $attempt['actual_total_marks'] ?? $attempt['total_marks'] ?? 0; ?></td>
                                <td>
                                    <span class="badge <?php echo $attempt['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo number_format($attempt['percentage'], 2); ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = $attempt['status'] === 'auto_submitted' ? 'warning' : 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $statusBadge; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $attempt['status'])); ?>
                                    </span>
                                    <?php if ($attempt['flagged_for_cheating'] && !$attempt['unflagged_at']): ?>
                                        <span class="badge bg-danger ms-1" title="<?php echo htmlspecialchars($attempt['cheating_reason'] ?? 'Flagged for cheating'); ?>">
                                            <i class="bi bi-flag-fill"></i> Flagged
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempt['tab_switches'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $attempt['tab_switches']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($attempt['submit_time'])); ?></td>
                                <td>
                                    <a href="view_attempt.php?attempt_id=<?php echo $attempt['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Exam attempts pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link"><i class="bi bi-chevron-left"></i> Previous</span>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled">
                            <span class="page-link">Next <i class="bi bi-chevron-right"></i></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            
            <div class="text-center text-muted mt-2">
                <small>Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $totalAttempts); ?> of <?php echo $totalAttempts; ?> attempts</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


