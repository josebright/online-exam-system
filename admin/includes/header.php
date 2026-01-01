<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - <?php echo APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo (defined('BASE_URL') ? BASE_URL : '../..'); ?>/assets/css/main.css">
    
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-heart-pulse-fill" style="font-size: 2.5rem; color: var(--accent-gold);"></i>
            <h4 class="mt-2">Online Exam System</h4>
            <small>Admin Panel</small>
            <div class="user-info">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="exams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span>Manage Exams</span>
            </a>
            <a href="questions.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['questions.php', 'add_question.php', 'edit_question.php']) ? 'active' : ''; ?>">
                <i class="bi bi-question-circle"></i>
                <span>Manage Questions</span>
            </a>
            <a href="students.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                <i class="bi bi-people"></i>
                <span>Students</span>
            </a>
            <a href="results.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart"></i>
                <span>Results & Analytics</span>
            </a>
            <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
            <a href="../logout.php" class="text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-link d-md-none p-0" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h5>
            </div>
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle" style="font-size: 1.75rem; color: var(--text-dark);"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        
        <div class="content-area">
            <?php
            $success = getFlashMessage('success');
            $error = getFlashMessage('error');
            
            if ($success):
            ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

