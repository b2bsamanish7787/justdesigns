<?php
/**
 * User Login Page
 * Just Designs - login.php
 */
$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';

// Already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/');
}

$error = '';
$redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['user_id']             = $user['id'];
                $_SESSION['user_name']           = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email']          = $user['email'];
                $_SESSION['is_subscriber']       = $user['is_subscriber'];
                $_SESSION['must_change_password'] = $user['must_change_password'];

                setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
                redirect($redirect ?: SITE_URL . '/');
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
?>

<div class="auth-wrapper py-5">
    <div class="container">
        <div class="auth-card card mx-auto">
            <div class="auth-header">
                <i class="fas fa-sign-in-alt fa-2x mb-2"></i>
                <h3>Welcome Back</h3>
                <p class="mb-0 opacity-75">Login to your <?= SITE_NAME ?> account</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com"
                                   value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2 mt-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>

                <hr class="my-4">
                <p class="text-center mb-0">
                    Don't have an account?
                    <a href="<?= SITE_URL ?>/register.php" class="fw-bold text-decoration-none">Subscribe for Free</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function(){
    const pwd = document.querySelector('input[name=password]');
    const icon = this.querySelector('i');
    if(pwd.type === 'password') { pwd.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'fas fa-eye'; }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
