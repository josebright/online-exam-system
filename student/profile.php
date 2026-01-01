<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';

startSecureSession();
requireStudent();

$pageTitle = 'Profile';

$currentUser = getCurrentUser();

if (!$currentUser) {
    $_SESSION['error'] = 'Unable to load user profile.';
    header('Location: dashboard.php');
    exit();
}

include 'includes/header.php';
?>

<h2 class="mb-4">My Profile</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Profile Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Full Name:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['email'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Username:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentUser['username'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <?php 
                        $status = $currentUser['status'] ?? 'active';
                        $statusClass = $status === 'active' ? 'bg-success' : ($status === 'inactive' ? 'bg-secondary' : 'bg-danger');
                        ?>
                        <span class="badge <?php echo $statusClass; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Member Since:</dt>
                    <dd class="col-sm-8">
                        <?php 
                        if (!empty($currentUser['created_at'])) {
                            echo date('F d, Y', strtotime($currentUser['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </dd>
                    
                    <?php if (!empty($currentUser['last_login'])): ?>
                    <dt class="col-sm-4">Last Login:</dt>
                    <dd class="col-sm-8">
                        <?php echo date('F d, Y g:i A', strtotime($currentUser['last_login'])); ?>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


