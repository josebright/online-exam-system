<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Students';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$students = getAllUsers('student', $page);
$totalStudents = countUsers('student');
$totalPages = ceil($totalStudents / ITEMS_PER_PAGE);

$query = "SELECT * FROM student_performance ORDER BY average_score DESC";
$performances = fetchAll($query);

$performanceMap = [];
foreach ($performances as $perf) {
    $performanceMap[$perf['student_id']] = $perf;
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-people me-2"></i>All Students</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Exams Taken</th>
                        <th>Avg Score</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No students registered yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php $perf = $performanceMap[$student['id']] ?? null; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo $perf['exams_taken'] ?? 0; ?></td>
                                <td>
                                    <?php if ($perf && $perf['exams_taken'] > 0): ?>
                                        <span class="badge bg-primary"><?php echo number_format($perf['average_score'], 1); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-success"><?php echo $perf['exams_passed'] ?? 0; ?></span></td>
                                <td><span class="badge bg-danger"><?php echo $perf['exams_failed'] ?? 0; ?></span></td>
                                <td>
                                    <?php
                                    $statusBadge = match($student['status']) {
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        'suspended' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusBadge; ?>"><?php echo ucfirst($student['status']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Students pagination" class="mt-4">
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
                <small>Showing <?php echo (($page - 1) * ITEMS_PER_PAGE) + 1; ?> - <?php echo min($page * ITEMS_PER_PAGE, $totalStudents); ?> of <?php echo $totalStudents; ?> students</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


