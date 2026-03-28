<?php
/**
 * Admin Manage Images
 * Just Designs - admin/manage-images.php
 */
$adminPageTitle = 'Manage Images';
require_once __DIR__ . '/includes/admin_header.php';

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request.');
    } else {
        $deleteId = (int)$_GET['delete'];
        // Get files to delete from disk
        $filesStmt = $pdo->prepare('SELECT filename FROM image_files WHERE image_id = ?');
        $filesStmt->execute([$deleteId]);
        $filesToDelete = $filesStmt->fetchAll();

        $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$deleteId]);

        foreach ($filesToDelete as $file) {
            // Prevent path traversal: ensure filename is a plain basename
            $safeFilename = basename($file['filename']);
            if ($safeFilename && strpos($safeFilename, '/') === false && strpos($safeFilename, '..') === false) {
                $filePath = UPLOAD_DIR . $safeFilename;
                if (file_exists($filePath)) unlink($filePath);
            }
        }
        setFlash('success', 'Image deleted successfully.');
    }
    header('Location: ' . SITE_URL . '/admin/manage-images.php');
    exit;
}

// Filter
$filterType = $_GET['type'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($filterType === 'free') {
    $where[] = "i.image_type = 'free'";
} elseif ($filterType === 'premium') {
    $where[] = "i.image_type = 'premium'";
}

if (!empty($search)) {
    $where[] = "(i.name LIKE ? OR i.image_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM images i $whereClause");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$params[] = $perPage;
$params[] = $offset;
$imagesStmt = $pdo->prepare(
    "SELECT i.*,
            (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file,
            (SELECT COUNT(*) FROM likes WHERE image_id = i.id) AS like_count
     FROM images i $whereClause ORDER BY i.created_at DESC LIMIT ? OFFSET ?"
);
$imagesStmt->execute($params);
$images = $imagesStmt->fetchAll();

$csrfToken = getCsrfToken();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-images me-2"></i>Manage Images</h4>
    <a href="<?= SITE_URL ?>/admin/upload.php" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Upload New
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>All Types</option>
                    <option value="free" <?= $filterType === 'free' ? 'selected' : '' ?>>Free</option>
                    <option value="premium" <?= $filterType === 'premium' ? 'selected' : '' ?>>Premium</option>
                </select>
            </div>
            <div class="col">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or code..."
                           value="<?= e($search) ?>">
                    <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <?php if (!empty($search)): ?>
            <div class="col-auto">
                <a href="?type=<?= e($filterType) ?>" class="btn btn-sm btn-outline-danger">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <small class="text-muted">Showing <?= count($images) ?> of <?= $totalRecords ?> images</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:70px">Thumb</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Dimensions</th>
                    <th>Likes</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($images as $img):
                    $thumb = getThumbUrl($img);
                ?>
                <tr>
                    <td>
                        <img src="<?= e($thumb) ?>" alt="" style="width:60px;height:45px;object-fit:cover;border-radius:6px">
                    </td>
                    <td><?= e($img['name']) ?></td>
                    <td><code><?= e($img['image_code']) ?></code></td>
                    <td>
                        <span class="badge <?= $img['image_type'] === 'premium' ? 'bg-warning text-dark' : 'bg-success' ?>">
                            <?= $img['image_type'] ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= e($img['dimensions'] ?: '—') ?></td>
                    <td><i class="fas fa-heart text-danger me-1"></i><?= (int)$img['like_count'] ?></td>
                    <td class="text-muted small"><?= date('M j, Y', strtotime($img['created_at'])) ?></td>
                    <td>
                        <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $img['id'] ?>" target="_blank"
                           class="btn btn-sm btn-outline-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= SITE_URL ?>/admin/edit-image.php?id=<?= $img['id'] ?>"
                           class="btn btn-sm btn-outline-secondary ms-1" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= SITE_URL ?>/admin/manage-images.php?delete=<?= $img['id'] ?>&csrf_token=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger btn-delete-confirm ms-1" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($images)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No images found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&type=<?= e($filterType) ?>&search=<?= urlencode($search) ?>">
                        <?= $p ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
