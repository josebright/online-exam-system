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
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
        } else {
            $result = loginUser($username, $password);
            
            if ($result['success']) {
                if ($result['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: student/dashboard.php');
                }
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
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo (defined('BASE_URL') ? BASE_URL : '.'); ?>/assets/css/main.css">
    
    <style>
        .demo-credentials {
            background: var(--light-bg);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        
        .demo-credentials h6 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            font-size: var(--font-size-base);
        }
        
        .demo-credentials code {
            background: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: var(--radius-sm);
            color: var(--primary-teal);
            font-weight: 600;
            font-size: var(--font-size-sm);
        }
        
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
        
        .login-features {
            list-style: none;
            padding: 0;
            margin: var(--spacing-lg) 0 0 0;
        }
        
        .login-features li {
            padding: var(--spacing-xs) 0;
            opacity: 0.9;
            font-size: var(--font-size-base);
        }
        
        .login-features li i {
            margin-right: var(--spacing-xs);
            color: var(--accent-gold);
        }
    </style>
</head>
<body>
    <div class="login-page-wrapper">
        <div class="login-container">
            <div class="card login-card">
                <div class="card-header">
                    <div class="logo-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h3 class="mb-0">Online Exam System</h3>
                    <p class="mb-0 mt-2 opacity-75">Examination Portal</p>
                    <ul class="login-features">
                        <li><i class="bi bi-check-circle-fill"></i> Secure Authentication</li>
                        <li><i class="bi bi-check-circle-fill"></i> Real-time Exam System</li>
                        <li><i class="bi bi-check-circle-fill"></i> Performance Analytics</li>
                        <li><i class="bi bi-check-circle-fill"></i> Professional Interface</li>
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
                    
                    <?php
                    $success = getFlashMessage('success');
                    if ($success):
                    ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person-fill"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Enter your username" required autofocus>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                            </button>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-decoration-none fw-bold">Register here</a>
                            </p>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="demo-credentials">
                        <h6><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1 small"><strong>Admin:</strong></p>
                                <small>Username: <code>admin</code></small><br>
                                <small>Password: <code>Admin@123</code></small>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 small"><strong>Student:</strong></p>
                                <small>Username: <code>john_doe</code></small><br>
                                <small>Password: <code>Student@123</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
            
            $('#loginForm').submit(function(e) {
                const username = $('#username').val().trim();
                const password = $('#password').val();
                
                if (username === '' || password === '') {
                    e.preventDefault();
                    showAlert('Please fill in all fields', 'warning');
                    return false;
                }
                
                const $submitBtn = $(this).find('button[type="submit"]');
                setButtonLoading($submitBtn);
            });
            
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

