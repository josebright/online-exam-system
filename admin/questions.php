<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/exam_functions.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Manage Questions';

$examId = isset($_GET['exam_id']) ? sanitizeInput($_GET['exam_id']) : null;

if (!$examId) {
    $exams = getAllExams();
    $csrfToken = generateCSRFToken();
    include 'includes/header.php';
    ?>
    
    <div class="mb-4">
        <h4><i class="bi bi-question-circle me-2"></i>Select Exam to Manage Questions</h4>
    </div>
    
    <?php if (empty($exams)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <h5 class="mt-3">No Exams Available</h5>
                <p class="text-muted">Create an exam first before adding questions</p>
                <a href="exams.php?action=create" class="btn btn-gradient mt-3">
                    <i class="bi bi-plus-circle me-2"></i>Create New Exam
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($exams as $exam): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($exam['title']); ?></h5>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)); ?><?php echo strlen($exam['description']) > 100 ? '...' : ''; ?></p>
                            
                            <div class="mb-3 d-flex flex-wrap gap-2">
                                <span class="badge bg-primary">
                                    <i class="bi bi-question-circle me-1"></i><?php echo $exam['question_count']; ?> Questions
                                </span>
                                <span class="badge bg-info">
                                    <i class="bi bi-clock me-1"></i><?php echo $exam['duration_minutes']; ?> Min
                                </span>
                                <span class="badge bg-<?php echo $exam['status'] === 'published' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($exam['status']); ?>
                                </span>
                            </div>
                            
                            <a href="questions.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-gradient w-100">
                                <i class="bi bi-gear me-2"></i>Manage Questions
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php include 'includes/footer.php'; ?>
    <?php
    exit();
}

$exam = getExamById($examId);
if (!$exam) {
    $_SESSION['error'] = 'Exam not found';
    header('Location: questions.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            $questionType = sanitizeInput($_POST['question_type']);
            $questionText = sanitizeInput($_POST['question_text']);
            $marks = (int)$_POST['marks'];
            $orderNumber = (int)$_POST['order_number'];
            
            $options = [];
            
            if (in_array($questionType, ['multiple_choice', 'multiple_select', 'true_false'])) {
                if (isset($_POST['options'])) {
                    foreach ($_POST['options'] as $index => $optionText) {
                        if (!empty($optionText)) {
                            $isCorrect = false;
                            
                            if ($questionType === 'multiple_choice' || $questionType === 'true_false') {
                                $isCorrect = isset($_POST['correct_option']) && $_POST['correct_option'] == $index;
                            } else {
                                $isCorrect = isset($_POST['correct_options']) && in_array($index, $_POST['correct_options']);
                            }
                            
                            $options[] = [
                                'text' => sanitizeInput($optionText),
                                'is_correct' => $isCorrect
                            ];
                        }
                    }
                }
            }
            
            $correctAnswer = null;
            if (in_array($questionType, ['short_answer', 'fill_blank'])) {
                $correctAnswer = sanitizeInput($_POST['correct_answer'] ?? '');
            }
            
            $result = createQuestion($examId, $questionType, $questionText, $marks, $orderNumber, $options, $correctAnswer);
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
            
        } elseif ($action === 'update') {
            $questionId = sanitizeInput($_POST['question_id']);
            $questionText = sanitizeInput($_POST['question_text']);
            $marks = (int)$_POST['marks'];
            $orderNumber = (int)$_POST['order_number'];
            
            $correctAnswer = null;
            $question = getQuestionById($questionId);
            if ($question && in_array($question['question_type'], ['short_answer', 'fill_blank'])) {
                $correctAnswer = sanitizeInput($_POST['correct_answer'] ?? '');
            }
            
            $result = updateQuestion($questionId, $questionText, $marks, $orderNumber, $correctAnswer);
            
            if (isset($_POST['option_ids']) && isset($_POST['option_texts'])) {
                foreach ($_POST['option_ids'] as $index => $optionId) {
                    $optionText = sanitizeInput($_POST['option_texts'][$index]);
                    $isCorrect = isset($_POST['option_correct']) && in_array($optionId, $_POST['option_correct']);
                    updateQuestionOption($optionId, $optionText, $isCorrect);
                }
            }
            
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
            
        } elseif ($action === 'delete') {
            $questionId = sanitizeInput($_POST['question_id']);
            $result = deleteQuestion($questionId);
            $_SESSION[$result['success'] ? 'success' : 'error'] = $result['message'];
        }
    }
    
    header('Location: questions.php?exam_id=' . $examId);
    exit();
}

$action = $_GET['action'] ?? 'list';
$questionId = isset($_GET['question_id']) ? sanitizeInput($_GET['question_id']) : null;

$question = null;
$questionOptions = [];
if ($action === 'edit' && $questionId) {
    $question = getQuestionById($questionId);
    if (!$question) {
        $_SESSION['error'] = 'Question not found';
        header('Location: questions.php?exam_id=' . $examId);
        exit();
    }
    $questionOptions = getQuestionOptions($questionId);
}

$questions = getExamQuestions($examId);

$csrfToken = generateCSRFToken();

include 'includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="exams.php">Exams</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($exam['title']); ?></li>
            </ol>
        </nav>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5><?php echo htmlspecialchars($exam['title']); ?></h5>
            <p class="text-muted mb-2"><?php echo htmlspecialchars($exam['description']); ?></p>
            <div class="d-flex gap-3">
                <span><i class="bi bi-clock me-1"></i><?php echo $exam['duration_minutes']; ?> minutes</span>
                <span><i class="bi bi-trophy me-1"></i><?php echo $exam['total_marks']; ?> marks</span>
                <span><i class="bi bi-question-circle me-1"></i><?php echo count($questions); ?> questions</span>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-question-circle me-2"></i>Questions</h4>
        <a href="?exam_id=<?php echo $examId; ?>&action=create" class="btn btn-gradient">
            <i class="bi bi-plus-circle me-2"></i>Add Question
        </a>
    </div>
    
    <div class="row">
        <?php if (empty($questions)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="mt-3">No Questions Added Yet</h5>
                        <p class="text-muted">Start building your exam by adding questions</p>
                        <a href="?exam_id=<?php echo $examId; ?>&action=create" class="btn btn-gradient">
                            <i class="bi bi-plus-circle me-2"></i>Add First Question
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $index => $q): ?>
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2">Q<?php echo $index + 1; ?></span>
                                        <span class="badge bg-info me-2"><?php echo ucwords(str_replace('_', ' ', $q['question_type'])); ?></span>
                                        <span class="badge bg-success"><?php echo $q['marks']; ?> marks</span>
                                    </div>
                                    <h6><?php echo htmlspecialchars($q['question_text']); ?></h6>
                                    
                                    <?php if (in_array($q['question_type'], ['multiple_choice', 'multiple_select', 'true_false'])): ?>
                                        <?php $options = getQuestionOptions($q['id']); ?>
                                        <ul class="list-unstyled mt-2 ms-3">
                                            <?php foreach ($options as $opt): ?>
                                                <li class="mb-1">
                                                    <?php if ($opt['is_correct']): ?>
                                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-circle me-2"></i>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($opt['option_text']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <div class="table-actions">
                                    <a href="?exam_id=<?php echo $examId; ?>&action=edit&question_id=<?php echo $q['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline delete-question-form" data-question-id="<?php echo $q['id']; ?>" id="deleteQuestionForm_<?php echo $q['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-danger delete-question-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteQuestionModal" data-form-id="deleteQuestionForm_<?php echo $q['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="exams.php">Exams</a></li>
                <li class="breadcrumb-item"><a href="questions.php?exam_id=<?php echo $examId; ?>"><?php echo htmlspecialchars($exam['title']); ?></a></li>
                <li class="breadcrumb-item active"><?php echo $action === 'create' ? 'Add Question' : 'Edit Question'; ?></li>
            </ol>
        </nav>
    </div>
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><?php echo $action === 'create' ? 'Add New Question' : 'Edit Question'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST" action="questions.php?exam_id=<?php echo $examId; ?>" id="questionForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create' : 'update'; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8">
                        <?php if ($action === 'create'): ?>
                            <div class="mb-3">
                                <label for="question_type" class="form-label">Question Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="question_type" name="question_type" required>
                                    <option value="">Select Type...</option>
                                    <option value="multiple_choice">Multiple Choice (Single Answer)</option>
                                    <option value="multiple_select">Multiple Select (Multiple Answers)</option>
                                    <option value="true_false">True/False</option>
                                    <option value="short_answer">Short Answer</option>
                                    <option value="fill_blank">Fill in the Blank</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Question Type</label>
                                <input type="text" class="form-control" value="<?php echo ucwords(str_replace('_', ' ', $question['question_type'])); ?>" readonly>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($question['question_text'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3" id="correctAnswerContainer" style="display: none;">
                            <label for="correct_answer" class="form-label">Correct Answer <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="correct_answer" name="correct_answer" 
                                   value="<?php echo htmlspecialchars($question['correct_answer'] ?? ''); ?>"
                                   placeholder="Enter the correct answer">
                            <small class="form-text text-muted">This will be compared case-insensitively and spacing-tolerant with student answers.</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="marks" class="form-label">Marks <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="marks" name="marks" min="1" required
                                   value="<?php echo $question['marks'] ?? 1; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order</label>
                            <input type="number" class="form-control" id="order_number" name="order_number" min="0"
                                   value="<?php echo $question['order_number'] ?? count($questions) + 1; ?>">
                        </div>
                    </div>
                </div>
                
                <div id="optionsContainer" style="display: none;">
                    <hr>
                    <h6>Answer Options</h6>
                    <div id="optionsList"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">
                        <i class="bi bi-plus-circle me-1"></i>Add Option
                    </button>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-gradient btn-lg">
                        <i class="bi bi-check-circle me-2"></i><?php echo $action === 'create' ? 'Add Question' : 'Update Question'; ?>
                    </button>
                    <a href="questions.php?exam_id=<?php echo $examId; ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$additionalJS = <<<'JS'
<script>
$(document).ready(function() {
    let optionCounter = 0;
    
    function showOptionsForType(type) {
        const container = $('#optionsContainer');
        const optionsList = $('#optionsList');
        const addBtn = $('#addOptionBtn');
        const correctAnswerContainer = $('#correctAnswerContainer');
        const correctAnswerInput = $('#correct_answer');
        
        optionsList.empty();
        
        if (type === 'multiple_choice' || type === 'multiple_select') {
            container.show();
            addBtn.show();
            correctAnswerContainer.hide();
            correctAnswerInput.removeAttr('required');
            
            for (let i = 0; i < 4; i++) {
                addOption(type);
            }
        } else if (type === 'true_false') {
            container.show();
            addBtn.hide();
            correctAnswerContainer.hide();
            correctAnswerInput.removeAttr('required');
            
            addTrueFalseOptions();
        } else if (type === 'short_answer' || type === 'fill_blank') {
            container.hide();
            correctAnswerContainer.show();
            correctAnswerInput.attr('required', 'required');
        } else {
            container.hide();
            correctAnswerContainer.hide();
            correctAnswerInput.removeAttr('required');
        }
    }
    
    function addOption(type) {
        const inputType = type === 'multiple_choice' || type === 'true_false' ? 'radio' : 'checkbox';
        const inputName = type === 'multiple_choice' || type === 'true_false' ? 'correct_option' : 'correct_options[]';
        
        const optionHtml = `
            <div class="input-group mb-2 option-item">
                <div class="input-group-text">
                    <input class="form-check-input mt-0" type="${inputType}" name="${inputName}" value="${optionCounter}">
                </div>
                <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCounter + 1}" required>
                <button type="button" class="btn btn-outline-danger remove-option">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        
        $('#optionsList').append(optionHtml);
        optionCounter++;
    }
    
    function addTrueFalseOptions() {
        const trueFalseHtml = `
            <div class="input-group mb-2">
                <div class="input-group-text">
                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="0" required>
                </div>
                <input type="text" class="form-control" name="options[]" value="True" readonly>
            </div>
            <div class="input-group mb-2">
                <div class="input-group-text">
                    <input class="form-check-input mt-0" type="radio" name="correct_option" value="1" required>
                </div>
                <input type="text" class="form-control" name="options[]" value="False" readonly>
            </div>
        `;
        
        $('#optionsList').html(trueFalseHtml);
    }
    
    $('#question_type').change(function() {
        const type = $(this).val();
        optionCounter = 0;
        showOptionsForType(type);
    });
    
JS;
if ($action === 'edit' && $question) {
    $additionalJS .= "showOptionsForType('" . addslashes($question['question_type']) . "');\n";
}
$additionalJS .= <<<'JS'
    
    $('#addOptionBtn').click(function() {
        const type = $('#question_type').val();
        addOption(type);
    });
    
    function showAlert(message, type = 'warning') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;">
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('body').append(alertHtml);
        const $alert = $('.alert:last');
        if (typeof setupAlertAutoDismiss === 'function') {
            setupAlertAutoDismiss($alert);
        } else {
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $(this).remove();
                });
            }, 10000);
        }
    }
    
    $(document).on('click', '.remove-option', function() {
        if ($('.option-item').length > 2) {
            $(this).closest('.option-item').remove();
        } else {
            showAlert('You must have at least 2 options', 'warning');
        }
    });
    
    let deleteForm = null;
    
    $(document).on('click', '.delete-question-btn', function(e) {
        const formId = $(this).data('form-id');
        
        if (formId) {
            deleteForm = $('#' + formId);
        } else {
            deleteForm = $(this).closest('form');
        }
        
        $('#deleteQuestionModal').data('delete-form', deleteForm);
    });
    
    $('#deleteQuestionModal').on('shown.bs.modal', function() {
        const $confirmBtn = $(this).find('#confirmDeleteQuestion');
        
        $confirmBtn.off('click.deleteQuestion');
        
        $confirmBtn.on('click.deleteQuestion', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            if ($btn.prop('disabled') || $btn.data('loading-active')) {
                return false;
            }
            
            const $modal = $('#deleteQuestionModal');
            let form = $modal.data('delete-form');
            if (!form || form.length === 0) {
                form = deleteForm;
            }
            
            if (!form || form.length === 0) {
                alert('Unable to find form. Please try again.');
                $modal.modal('hide');
                return false;
            }
            
            setButtonLoading($btn);
            form[0].submit();
            return false;
        });
    });
    
    $(document).on('click', '#confirmDeleteQuestion', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        if ($btn.prop('disabled') || $btn.data('loading-active')) {
            return false;
        }
        
        const $modal = $('#deleteQuestionModal');
        let form = $modal.data('delete-form');
        if (!form || form.length === 0) {
            form = deleteForm;
        }
        
        if (!form || form.length === 0) {
            alert('Unable to find form. Please try again.');
            $modal.modal('hide');
            return false;
        }
        
        setButtonLoading($btn);
        form[0].submit();
        return false;
    });
});
</script>
JS;

include 'includes/footer.php';
?>

<div class="modal fade" id="deleteQuestionModal" tabindex="-1" aria-labelledby="deleteQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteQuestionModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Question
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to delete this question?</p>
                <div class="alert alert-warning mb-0">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        This action cannot be undone. All associated options will also be deleted.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteQuestion">
                    <i class="bi bi-trash me-2"></i>Yes, Delete Question
                </button>
            </div>
        </div>
    </div>
</div>


