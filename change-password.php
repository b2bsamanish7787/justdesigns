<?php
/**
 * Change Password Page (required after first login)
 * Just Designs - change-password.php
 */
$pageTitle = 'Change Password';
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if (empty($currentPwd)) $errors[] = 'Current password is required.';
        if (strlen($newPwd) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($newPwd !== $confirmPwd) $errors[] = 'New passwords do not match.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($currentPwd, $user['password'])) {
                $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?');
                $updateStmt->execute([$newHash, $_SESSION['user_id']]);
                $_SESSION['must_change_password'] = 0;
                $success = true;
            } else {
                $errors[] = 'Current password is incorrect.';
            }
        }
    }
}
?>

<div class="auth-wrapper py-5">
    <div class="container">
        <div class="auth-card card mx-auto">
            <div class="auth-header" style="background: linear-gradient(135deg,#e67e22,#e94560)">
                <i class="fas fa-key fa-2x mb-2"></i>
                <h3>Change Password</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-2x d-block mb-2"></i>
                    <strong>Password changed successfully!</strong>
                    <br>Your account is now secure.
                </div>
                <a href="<?= SITE_URL ?>/" class="btn btn-dark w-100 mt-2">
                    <i class="fas fa-home me-2"></i>Go to Home
                </a>
                <?php else: ?>
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <small class="text-muted">(min. 8 chars)</small></label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2">
                        <i class="fas fa-save me-2"></i>Update Password
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
