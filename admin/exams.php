<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Manage Exams';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $result = createExam(
                sanitizeInput($_POST['title']),
                sanitizeInput($_POST['description']),
                (int)$_POST['duration_minutes'],
                (int)$_POST['passing_marks'],
                isset($_POST['allow_retake']),
                (int)($_POST['max_attempts'] ?? 1),
                isset($_POST['show_results']),
                isset($_POST['shuffle_questions'])
            );
            
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                header('Location: questions.php?exam_id=' . $result['exam_id']);
                exit();
            } else {
                $_SESSION['error'] = $result['message'];
            }
        } elseif ($action === 'update') {
            $result = updateExam(
                sanitizeInput($_POST['exam_id']),
                sanitizeInput($_POST['title']),
                sanitizeInput($_POST['description']),
                (int)$_POST['duration_minutes'],
                (int)$_POST['passing_marks'],
                sanitizeInput($_POST['status']),
                isset($_POST['allow_retake']),
                (int)($_POST['max_attempts'] ?? 1),
                isset($_POST['show_results']),
                isset($_POST['shuffle_questions'])
            );
            
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
        } elseif ($action === 'delete') {
            $result = deleteExam(sanitizeInput($_POST['exam_id']));
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
        }
    }
    
    header('Location: exams.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$examId = isset($_GET['exam_id']) ? sanitizeInput($_GET['exam_id']) : null;

$exam = null;
if ($action === 'edit' && $examId) {
    $exam = getExamById($examId);
    if (!$exam) {
        $_SESSION['error'] = 'Exam not found';
        header('Location: exams.php');
        exit();
    }
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$exams = getAllExams(null, $page);
$totalExams = countExams();
$totalPages = ceil($totalExams / ITEMS_PER_PAGE);

$csrfToken = generateCSRFToken();

include 'includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-file-earmark-text me-2"></i>All Exams</h4>
        <a href="?action=create" class="btn btn-gradient">
            <i class="bi bi-plus-circle me-2"></i>Create New Exam
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Questions</th>
                            <th>Duration</th>
                            <th>Total Marks</th>
                            <th>Status</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No exams created yet. Click "Create New Exam" to get started.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $e): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($e['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($e['description'], 0, 50)); ?>...</small>
                                    </td>
                                    <td><?php echo $e['question_count']; ?></td>
                                    <td><?php echo $e['duration_minutes']; ?> min</td>
                                    <td><?php echo $e['total_marks']; ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($e['status']) {
                                            'draft' => 'secondary',
                                            'published' => 'success',
                                            'in_progress' => 'warning',
                                            'closed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucfirst($e['status']); ?></span>
                                    </td>
                                    <td><?php echo $e['student_count']; ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="questions.php?exam_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-primary" title="Manage Questions">
                                                <i class="bi bi-question-circle"></i>
                                            </a>
                                            <a href="?action=edit&exam_id=<?php echo $e['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" class="d-inline delete-exam-form" data-exam-id="<?php echo $e['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="exam_id" value="<?php echo $e['id']; ?>">
                                                <button type="button" class="btn btn-sm btn-danger delete-exam-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteExamModal">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-<?php echo $action === 'create' ? 'plus-circle' : 'pencil'; ?> me-2"></i><?php echo $action === 'create' ? 'Create New' : 'Edit'; ?> Exam</h4>
        <a href="exams.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="exams.php" id="examForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($exam['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="duration_minutes" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" 
                                   min="<?php echo MIN_EXAM_DURATION; ?>" max="<?php echo MAX_EXAM_DURATION; ?>" required
                                   value="<?php echo $exam['duration_minutes'] ?? 30; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="passing_marks" class="form-label">Passing Marks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="passing_marks" name="passing_marks" 
                                   min="0" required value="<?php echo $exam['passing_marks'] ?? 0; ?>">
                        </div>
                        
                        <?php if ($action === 'edit'): ?>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo ($exam['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($exam['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="closed" <?php echo ($exam['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Exam Settings</h6>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="allow_retake" name="allow_retake"
                                           <?php echo ($exam['allow_retake'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_retake">
                                        Allow Retake
                                    </label>
                                </div>
                                
                                <div class="mb-3" id="max_attempts_group">
                                    <label for="max_attempts" class="form-label">Max Attempts</label>
                                    <input type="number" class="form-control" id="max_attempts" name="max_attempts" 
                                           min="1" max="10" value="<?php echo $exam['max_attempts'] ?? 1; ?>">
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="show_results" name="show_results"
                                           <?php echo ($exam['show_results'] ?? true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="show_results">
                                        Show Detailed Results to Students
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="shuffle_questions" name="shuffle_questions"
                                           <?php echo ($exam['shuffle_questions'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="shuffle_questions">
                                        Shuffle Questions
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-gradient btn-lg">
                        <i class="bi bi-check-circle me-2"></i><?php echo $action === 'create' ? 'Create Exam' : 'Update Exam'; ?>
                    </button>
                    <a href="exams.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$additionalJS = <<<'JS'
<script>
$(document).ready(function() {
    function showAlert(message, type = 'warning') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('body').append(alertHtml);
        setTimeout(function() {
            $('.alert:last').fadeOut(function() {
                $(this).remove();
            });
        }, 10000);
    }
    
    let deleteExamForm = null;
    $('.delete-exam-btn').on('click', function() {
        deleteExamForm = $(this).closest('form');
    });
    
    $('#confirmDeleteExam').on('click', function(e) {
        e.preventDefault();
        if (deleteExamForm && deleteExamForm.length > 0) {
            const $btn = $(this);
            setButtonLoading($btn);
            deleteExamForm.submit();
        } else {
            showAlert('Unable to find form. Please try again.', 'danger');
        }
    });
    
    $('#allow_retake').change(function() {
        if ($(this).is(':checked')) {
            $('#max_attempts_group').show();
        } else {
            $('#max_attempts_group').hide();
            $('#max_attempts').val(1);
        }
    }).trigger('change');
});
</script>
JS;

include 'includes/footer.php';
?>

<div class="modal fade" id="deleteExamModal" tabindex="-1" aria-labelledby="deleteExamModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteExamModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Exam
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete this exam?</p>
                <div class="alert alert-warning mb-0">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        This action cannot be undone. All questions, attempts, and results associated with this exam will also be deleted.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteExam">
                    <i class="bi bi-trash me-2"></i>Yes, Delete Exam
                </button>
            </div>
        </div>
    </div>
</div>


