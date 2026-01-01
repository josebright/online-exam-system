<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } else {
            $result = registerUser($username, $email, $password, $fullName, 'student');
            
            if ($result['success']) {
                $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
                header('Location: login.php');
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo (defined('BASE_URL') ? BASE_URL : '.'); ?>/assets/css/main.css">
    
    <style>
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: var(--radius-sm);
            transition: all var(--transition-base);
        }
        
        .strength-weak { background-color: var(--danger); width: 33%; }
        .strength-medium { background-color: var(--warning); width: 66%; }
        .strength-strong { background-color: var(--success); width: 100%; }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
        }
        
        .input-group-text {
            border-right: none;
            border-radius: var(--radius-md) 0 0 var(--radius-md);
        }
        
        .form-label {
            font-size: var(--font-size-base);
            margin-bottom: var(--spacing-xs);
        }
        
        .register-features {
            list-style: none;
            padding: 0;
            margin: var(--spacing-lg) 0 0 0;
        }
        
        .register-features li {
            padding: var(--spacing-xs) 0;
            opacity: 0.9;
            font-size: var(--font-size-base);
        }
        
        .register-features li i {
            margin-right: var(--spacing-xs);
            color: var(--accent-gold);
        }
    </style>
</head>
<body>
    <div class="register-page-wrapper">
        <div class="register-container">
            <div class="card register-card">
                <div class="card-header">
                    <div class="logo-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h3 class="mb-0">Online Exam System</h3>
                    <p class="mb-0 mt-2 opacity-75">Create Student Account</p>
                    <ul class="register-features">
                        <li><i class="bi bi-check-circle-fill"></i> Free Registration</li>
                        <li><i class="bi bi-check-circle-fill"></i> Instant Access</li>
                        <li><i class="bi bi-check-circle-fill"></i> Track Your Progress</li>
                        <li><i class="bi bi-check-circle-fill"></i> Secure & Private</li>
                    </ul>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-badge"></i>
                                </span>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       placeholder="Enter your full name" required 
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Choose a username" required pattern="[a-zA-Z0-9_]{3,20}"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                            <small class="text-muted">3-20 characters, letters, numbers, and underscores only</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope-fill"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Create a strong password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <small class="text-muted">Min 8 characters with uppercase, lowercase, number & special character</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required>
                            </div>
                            <small id="passwordMatch" class="text-muted"></small>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="bi bi-person-check me-2"></i>Create Account
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none fw-bold">Login here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const toggleIcon = $('#toggleIcon');
                
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    toggleIcon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    toggleIcon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });
            
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrength');
                
                if (password.length === 0) {
                    strengthBar.removeClass().addClass('password-strength');
                    return;
                }
                
                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[@$!%*?&]/.test(password)) strength++;
                
                strengthBar.removeClass();
                strengthBar.addClass('password-strength');
                
                if (strength <= 2) {
                    strengthBar.addClass('strength-weak');
                } else if (strength === 3) {
                    strengthBar.addClass('strength-medium');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });
            
            $('#confirm_password').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $(this).val();
                const matchText = $('#passwordMatch');
                
                if (confirmPassword === '') {
                    matchText.text('').removeClass();
                    return;
                }
                
                if (password === confirmPassword) {
                    matchText.text('✓ Passwords match').removeClass().addClass('text-success');
                } else {
                    matchText.text('✗ Passwords do not match').removeClass().addClass('text-danger');
                }
            });
            
            function showAlert(message, type = 'danger') {
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
            
            $('#registerForm').submit(function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    showAlert('Passwords do not match!', 'danger');
                    return false;
                }
                
                const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!pattern.test(password)) {
                    e.preventDefault();
                    showAlert('Password must be at least 8 characters with uppercase, lowercase, number, and special character', 'danger');
                    return false;
                }
                
                const $submitBtn = $(this).find('button[type="submit"]');
                setButtonLoading($submitBtn);
            });
            
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
            
            function setupAlertAutoDismiss($alert) {
                if ($alert.data('auto-dismiss-setup')) {
                    return;
                }
                
                const isError = $alert.hasClass('alert-danger');
                const isWarning = $alert.hasClass('alert-warning');
                const timer = isError ? 7000 : (isWarning ? 6000 : 10000);
                
                $alert.css('position', 'relative');
                
                const $progressBar = $('<div class="alert-progress"></div>');
                $alert.append($progressBar);
                
                $progressBar.css({
                    'width': '100%',
                    'transition': 'width ' + (timer / 1000) + 's linear',
                    'height': '3px',
                    'background': 'rgba(0,0,0,0.2)',
                    'position': 'absolute',
                    'bottom': '0',
                    'left': '0',
                    'border-radius': '0 0 var(--radius-md) var(--radius-md)',
                    'z-index': '1'
                });
                
                setTimeout(function() {
                    $progressBar.css('width', '0%');
                }, 10);
                
                setTimeout(function() {
                    if ($alert.length && $alert.is(':visible')) {
                        const bsAlert = new bootstrap.Alert($alert[0]);
                        bsAlert.close();
                    }
                }, timer);
                
                $alert.data('auto-dismiss-setup', true);
            }
            
            $('.alert').each(function() {
                setupAlertAutoDismiss($(this));
            });
        });
    </script>
</body>
</html>

