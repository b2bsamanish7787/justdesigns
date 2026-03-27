<?php
/**
 * Registration / Subscription Page
 * Just Designs - register.php
 */
$pageTitle = 'Subscribe';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $phone     = trim($_POST['phone'] ?? '');

        // Validation
        if (empty($firstName))               $errors[] = 'First name is required.';
        if (empty($lastName))                $errors[] = 'Last name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                                             $errors[] = 'A valid email address is required.';
        if (!empty($phone) && !preg_match('/^[\d\+\-\(\)\s]{7,20}$/', $phone))
                                             $errors[] = 'Invalid phone number format.';

        if (empty($errors)) {
            // Check duplicate email
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'This email is already registered. Please <a href="' . SITE_URL . '/login.php">login</a>.';
            } else {
                // Generate temp password
                $tempPassword = generateTempPassword();
                $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (first_name, last_name, email, phone, password, is_subscriber, must_change_password)
                     VALUES (?, ?, ?, ?, ?, 1, 1)'
                );
                $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword]);

                // Send welcome email
                sendWelcomeEmail($email, $firstName, $tempPassword);

                $success = true;
                // Store temp password in session for display (demo only - not for production)
                $_SESSION['reg_temp_pwd'] = $tempPassword;
                $_SESSION['reg_email']    = $email;
            }
        }
    }
}
?>

<div class="auth-wrapper py-5">
    <div class="container">
        <?php if ($success): ?>
        <div class="auth-card card mx-auto">
            <div class="auth-header" style="background: linear-gradient(135deg,#27ae60,#2ecc71)">
                <i class="fas fa-check-circle fa-3x mb-2"></i>
                <h3>Registration Successful!</h3>
            </div>
            <div class="card-body p-4 text-center">
                <p class="lead">Welcome to <strong><?= SITE_NAME ?></strong>!</p>
                <p>Your account has been created. Check your email for your temporary password.</p>

                <!-- Demo: Show temp password since email may not work in local env -->
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-key me-2"></i>
                    <strong>Your temporary password:</strong>
                    <code class="ms-2 fs-5"><?= e($_SESSION['reg_temp_pwd'] ?? '') ?></code>
                    <br><small class="text-muted">Please change it after logging in.</small>
                </div>

                <a href="<?= SITE_URL ?>/login.php" class="btn btn-dark btn-lg fw-bold mt-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="auth-card card mx-auto" style="max-width:520px">
            <div class="auth-header">
                <i class="fas fa-crown fa-2x mb-2 text-warning"></i>
                <h3>Subscribe to <?= SITE_NAME ?></h3>
                <p class="mb-0 opacity-75">Get full access to all premium images — free!</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                        <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">First Name *</label>
                            <input type="text" name="first_name" class="form-control"
                                   value="<?= e($_POST['first_name'] ?? '') ?>"
                                   placeholder="John" required>
                            <div class="invalid-feedback">First name required.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Last Name *</label>
                            <input type="text" name="last_name" class="form-control"
                                   value="<?= e($_POST['last_name'] ?? '') ?>"
                                   placeholder="Doe" required>
                            <div class="invalid-feedback">Last name required.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control"
                                       value="<?= e($_POST['email'] ?? '') ?>"
                                       placeholder="you@example.com" required>
                            </div>
                            <div class="invalid-feedback">Valid email required.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Phone Number <span class="text-muted">(optional)</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?= e($_POST['phone'] ?? '') ?>"
                                       placeholder="+1 555 000 0000">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 mt-4">
                        <i class="fas fa-crown me-2"></i>Create My Account
                    </button>
                </form>

                <hr class="my-3">
                <p class="text-center mb-0 small">
                    Already have an account? <a href="<?= SITE_URL ?>/login.php" class="fw-bold">Login</a>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
