<?php
/**
 * Site-wide HTML footer
 * Just Designs - includes/footer.php
 */
?>

<footer class="bg-dark text-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="fw-bold"><i class="fas fa-palette me-2"></i><?= SITE_NAME ?></h5>
                <p class="text-muted small">A curated collection of stunning free and premium design images for creatives everywhere.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= SITE_URL ?>/" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/premium.php" class="text-muted text-decoration-none">Premium Images</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php" class="text-muted text-decoration-none">Subscribe</a></li>
                    <li><a href="<?= SITE_URL ?>/login.php" class="text-muted text-decoration-none">Login</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Account</h6>
                <ul class="list-unstyled small">
                    <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/wishlist.php" class="text-muted text-decoration-none">My Wishlist</a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php" class="text-muted text-decoration-none">Logout</a></li>
                    <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php" class="text-muted text-decoration-none">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php" class="text-muted text-decoration-none">Register / Subscribe</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center text-muted small">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
