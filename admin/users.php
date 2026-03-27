<?php
/**
 * Admin Users Management
 * Just Designs - admin/users.php
 */
$adminPageTitle = 'Users';
require_once __DIR__ . '/includes/admin_header.php';

// Toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    if (verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $userId = (int)$_GET['toggle'];
        $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$userId]);
        setFlash('success', 'User status updated.');
    }
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([(int)$_GET['delete']]);
        setFlash('success', 'User deleted.');
    }
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$params = [];
$whereClause = '';
if (!empty($search)) {
    $whereClause = 'WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%", "%$search%"];
}

$total = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
$total->execute($params);
$totalRecords = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$params[] = $perPage;
$params[] = $offset;
$stmt = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM likes WHERE user_id = u.id) AS like_count
                       FROM users u $whereClause ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params);
$users = $stmt->fetchAll();

$csrfToken = getCsrfToken();
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Subscribers</h4>
    <span class="badge bg-dark fs-6"><?= $totalRecords ?> total</span>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2">
            <div class="col">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                           value="<?= e($search) ?>">
                    <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <?php if (!empty($search)): ?>
            <div class="col-auto">
                <a href="?" class="btn btn-sm btn-outline-danger">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Likes</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $user): ?>
                <tr>
                    <td class="text-muted small"><?= ($offset + $i + 1) ?></td>
                    <td><?= e($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e($user['phone'] ?: '—') ?></td>
                    <td><i class="fas fa-heart text-danger me-1"></i><?= (int)$user['like_count'] ?></td>
                    <td class="text-muted small"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <a href="?toggle=<?= $user['id'] ?>&csrf_token=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-secondary" title="Toggle status">
                            <i class="fas fa-toggle-<?= $user['is_active'] ? 'on' : 'off' ?>"></i>
                        </a>
                        <a href="?delete=<?= $user['id'] ?>&csrf_token=<?= $csrfToken ?>"
                           class="btn btn-sm btn-outline-danger btn-delete-confirm ms-1" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
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
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
