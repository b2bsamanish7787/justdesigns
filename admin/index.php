<?php
/**
 * Admin Dashboard
 * Just Designs - admin/index.php
 */
$adminPageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

// Stats
$totalImages   = $pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
$freeImages    = $pdo->query("SELECT COUNT(*) FROM images WHERE image_type='free'")->fetchColumn();
$premiumImages = $pdo->query("SELECT COUNT(*) FROM images WHERE image_type='premium'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalLikes    = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();

// Recent images
$recentImages = $pdo->query(
    "SELECT i.*, (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file
     FROM images i ORDER BY i.created_at DESC LIMIT 6"
)->fetchAll();

// Recent users
$recentUsers = $pdo->query(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>

<h4 class="fw-bold mb-4"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h4>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#4361ee,#3a0ca3)">
            <i class="fas fa-images fa-2x mb-2"></i>
            <h2><?= $totalImages ?></h2>
            <p class="mb-0">Total Images</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#27ae60,#2ecc71)">
            <i class="fas fa-image fa-2x mb-2"></i>
            <h2><?= $freeImages ?></h2>
            <p class="mb-0">Free Images</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#f5c518,#e67e22)">
            <i class="fas fa-crown fa-2x mb-2"></i>
            <h2><?= $premiumImages ?></h2>
            <p class="mb-0">Premium Images</p>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#e94560,#c0392b)">
            <i class="fas fa-users fa-2x mb-2"></i>
            <h2><?= $totalUsers ?></h2>
            <p class="mb-0">Subscribers</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#7209b7,#480ca8)">
            <i class="fas fa-heart fa-2x mb-2"></i>
            <h2><?= $totalLikes ?></h2>
            <p class="mb-0">Total Likes</p>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100 p-3">
            <h6 class="fw-bold mb-3">Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= SITE_URL ?>/admin/upload.php" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>Upload Images
                </a>
                <a href="<?= SITE_URL ?>/admin/manage-images.php" class="btn btn-secondary">
                    <i class="fas fa-images me-2"></i>Manage Images
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-info text-white">
                    <i class="fas fa-users me-2"></i>View Users
                </a>
                <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-outline-dark">
                    <i class="fas fa-eye me-2"></i>View Site
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Images -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-images me-2"></i>Recent Images</h6>
        <a href="<?= SITE_URL ?>/admin/manage-images.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($recentImages as $img):
                $thumb = getThumbUrl($img);
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="text-center">
                    <img src="<?= e($thumb) ?>" alt="<?= e($img['name']) ?>"
                         style="width:100%;height:80px;object-fit:cover;border-radius:8px">
                    <p class="small mt-1 mb-0 text-truncate"><?= e($img['name']) ?></p>
                    <span class="badge <?= $img['image_type'] === 'premium' ? 'bg-warning text-dark' : 'bg-success' ?> small">
                        <?= $img['image_type'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Users -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-users me-2"></i>Recent Subscribers</h6>
        <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $user): ?>
                <tr>
                    <td><?= e($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['phone'] ?: '—') ?></td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentUsers)): ?>
                <tr><td colspan="5" class="text-center text-muted">No users yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
