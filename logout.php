<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

startSecureSession();

logoutUser();

header('Location: login.php');
exit();
?>


