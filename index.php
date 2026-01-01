<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/security.php';

startSecureSession();

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit();
?>


