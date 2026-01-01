<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Dashboard';

$conn = getDBConnection();

$totalExams = countExams();
$totalStudents = countUsers('student');

$query = "SELECT COUNT(*) as total FROM exam_attempts WHERE status IN ('submitted', 'auto_submitted')";
$result = fetchOne($query);
$totalAttempts = $result['total'] ?? 0;
$query = "SELECT AVG(percentage) as avg_score FROM exam_attempts WHERE status IN ('submitted', 'auto_submitted')";
$result = fetchOne($query);
$avgScore = round($result['avg_score'] ?? 0, 2);

$query = "SELECT ea.*, e.title as exam_title, u.full_name as student_name,
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
          ORDER BY ea.submit_time DESC
          LIMIT 10";
$recentAttempts = fetchAll($query);

$query = "SELECT status, COUNT(*) as count FROM exams GROUP BY status";
$examStatusData = fetchAll($query);

include 'includes/header.php';
?>

<div class="card welcome-banner mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-3 fw-bold">Admin Dashboard</h3>
                <p class="mb-0 fs-5">Manage exams, monitor performance, and track student progress</p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <i class="bi bi-speedometer2" style="font-size: 3.5rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Exams</p>
                        <h3 class="mb-0"><?php echo $totalExams; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-file-earmark-text"></i>
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
                        <p class="text-muted mb-1">Total Students</p>
                        <h3 class="mb-0"><?php echo $totalStudents; ?></h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
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
                        <h3 class="mb-0"><?php echo $totalAttempts; ?></h3>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
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
                        <h3 class="mb-0"><?php echo $avgScore; ?>%</h3>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Exam Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examStatusData as $data): ?>
                                <?php
                                $percentage = $totalExams > 0 ? round(($data['count'] / $totalExams) * 100, 1) : 0;
                                $badgeClass = match($data['status']) {
                                    'draft' => 'secondary',
                                    'published' => 'success',
                                    'in_progress' => 'warning',
                                    'closed' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <tr>
                                    <td><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucfirst($data['status']); ?></span></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $badgeClass; ?>" role="progressbar" 
                                                 style="width: <?php echo $percentage; ?>%">
                                                <?php echo $percentage; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="exams.php?action=create" class="btn btn-gradient btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>Create New Exam
                    </a>
                    <a href="questions.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-question-circle me-2"></i>Manage Questions
                    </a>
                    <a href="students.php" class="btn btn-outline-success btn-lg">
                        <i class="bi bi-people me-2"></i>View Students
                    </a>
                    <a href="results.php" class="btn btn-outline-info btn-lg">
                        <i class="bi bi-bar-chart me-2"></i>View Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Exam Attempts</h5>
                <a href="results.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAttempts)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No exam attempts yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAttempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
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
                                            $statusText = $attempt['status'] === 'auto_submitted' ? 'Auto Submitted' : 'Submitted';
                                            ?>
                                            <span class="badge bg-<?php echo $statusBadge; ?>"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($attempt['submit_time'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

