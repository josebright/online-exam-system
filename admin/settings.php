<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
requireAdmin();

$pageTitle = 'Settings';

$currentUser = getCurrentUser();

include 'includes/header.php';
?>

<h4 class="mb-4"><i class="bi bi-gear me-2"></i>Settings</h4>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Full Name:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['full_name']); ?></dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['email']); ?></dd>
                    
                    <dt class="col-sm-4">Username:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['username']); ?></dd>
                    
                    <dt class="col-sm-4">Role:</dt>
                    <dd class="col-sm-8"><span class="badge bg-primary"><?php echo ucfirst($currentUser['role']); ?></span></dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">System Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-6">Application:</dt>
                    <dd class="col-sm-6"><?php echo APP_NAME; ?></dd>
                    
                    <dt class="col-sm-6">Version:</dt>
                    <dd class="col-sm-6"><?php echo APP_VERSION; ?></dd>
                    
                    <dt class="col-sm-6">PHP Version:</dt>
                    <dd class="col-sm-6"><?php echo phpversion(); ?></dd>
                    
                    <dt class="col-sm-6">Server:</dt>
                    <dd class="col-sm-6"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


