<?php
/**
 * Admin Edit Image
 * Just Designs - admin/edit-image.php
 *
 * Allows the admin to:
 *  - Edit image metadata (name, code, type, dimensions, description)
 *  - Delete individual files attached to the image set
 *  - Change which file is the primary / thumbnail
 *  - Upload additional files to the existing image set
 */
$adminPageTitle = 'Edit Image';
require_once __DIR__ . '/includes/admin_header.php';

// Require a valid numeric ID
$imageId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$imageId) {
    setFlash('danger', 'Invalid image ID.');
    header('Location: ' . SITE_URL . '/admin/manage-images.php');
    exit;
}

// Load the image record
$image = getImageById($pdo, $imageId);
if (!$image) {
    setFlash('danger', 'Image not found.');
    header('Location: ' . SITE_URL . '/admin/manage-images.php');
    exit;
}

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? 'update';

        // ----------------------------------------------------------------
        // Action: delete a single file from the set
        // ----------------------------------------------------------------
        if ($action === 'delete_file') {
            $fileId = (int)($_POST['file_id'] ?? 0);
            if ($fileId) {
                $countStmt = $pdo->prepare('SELECT COUNT(*) FROM image_files WHERE image_id = ?');
                $countStmt->execute([$imageId]);
                $remaining = (int)$countStmt->fetchColumn();

                if ($remaining <= 1) {
                    setFlash('danger', 'Cannot delete the only file. Upload a replacement first.');
                } else {
                    $fileStmt = $pdo->prepare(
                        'SELECT filename, is_primary FROM image_files WHERE id = ? AND image_id = ?'
                    );
                    $fileStmt->execute([$fileId, $imageId]);
                    $fileRow = $fileStmt->fetch();

                    if ($fileRow) {
                        $pdo->prepare('DELETE FROM image_files WHERE id = ?')->execute([$fileId]);

                        $safeFilename = basename($fileRow['filename']);
                        if ($safeFilename && strpos($safeFilename, '/') === false && strpos($safeFilename, '..') === false) {
                            $filePath = UPLOAD_DIR . $safeFilename;
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }

                        // If we deleted the primary, auto-promote the next file
                        if ($fileRow['is_primary']) {
                            $firstStmt = $pdo->prepare(
                                'SELECT id, filename FROM image_files WHERE image_id = ? ORDER BY id ASC LIMIT 1'
                            );
                            $firstStmt->execute([$imageId]);
                            $newPrimary = $firstStmt->fetch();
                            if ($newPrimary) {
                                $pdo->prepare('UPDATE image_files SET is_primary = 1 WHERE id = ?')
                                    ->execute([$newPrimary['id']]);
                                $pdo->prepare('UPDATE images SET primary_image = ? WHERE id = ?')
                                    ->execute([UPLOAD_URL . $newPrimary['filename'], $imageId]);
                            }
                        }

                        setFlash('success', 'File removed successfully.');
                    }
                }
            }
            header('Location: ' . SITE_URL . '/admin/edit-image.php?id=' . $imageId);
            exit;
        }

        // ----------------------------------------------------------------
        // Action: set a file as primary thumbnail
        // ----------------------------------------------------------------
        if ($action === 'set_primary') {
            $fileId = (int)($_POST['file_id'] ?? 0);
            if ($fileId) {
                $fileStmt = $pdo->prepare(
                    'SELECT filename FROM image_files WHERE id = ? AND image_id = ?'
                );
                $fileStmt->execute([$fileId, $imageId]);
                $fileRow = $fileStmt->fetch();

                if ($fileRow) {
                    $pdo->prepare('UPDATE image_files SET is_primary = 0 WHERE image_id = ?')
                        ->execute([$imageId]);
                    $pdo->prepare('UPDATE image_files SET is_primary = 1 WHERE id = ?')
                        ->execute([$fileId]);
                    $pdo->prepare('UPDATE images SET primary_image = ? WHERE id = ?')
                        ->execute([UPLOAD_URL . $fileRow['filename'], $imageId]);
                    setFlash('success', 'Primary image updated.');
                }
            }
            header('Location: ' . SITE_URL . '/admin/edit-image.php?id=' . $imageId);
            exit;
        }

        // ----------------------------------------------------------------
        // Action: update metadata + optionally add more files
        // ----------------------------------------------------------------
        $name        = trim($_POST['image_name'] ?? '');
        $imageCode   = trim($_POST['image_code'] ?? '');
        $imageType   = ($_POST['image_type'] ?? 'free') === 'premium' ? 'premium' : 'free';
        $dimensions  = trim($_POST['dimensions'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name))      $errors[] = 'Image name is required.';
        if (empty($imageCode)) $errors[] = 'Image code is required.';

        // Uniqueness check — exclude the current record
        if (empty($errors)) {
            $codeCheck = $pdo->prepare('SELECT id FROM images WHERE image_code = ? AND id != ?');
            $codeCheck->execute([$imageCode, $imageId]);
            if ($codeCheck->fetch()) {
                $errors[] = 'Image code already exists. Please use a unique code.';
            }
        }

        // Validate & stage any newly uploaded files
        $newUploadedFiles = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $files      = $_FILES['images'];
            $totalFiles = count($files['name']);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Upload error for file ' . ($i + 1) . '.';
                    continue;
                }
                if ($files['size'][$i] > MAX_FILE_SIZE) {
                    $maxMb = MAX_FILE_SIZE / (1024 * 1024);
                    $errors[] = htmlspecialchars($files['name'][$i]) . ' exceeds ' . $maxMb . 'MB limit.';
                    continue;
                }
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($files['tmp_name'][$i]);
                if (!in_array($mimeType, ALLOWED_TYPES, true)) {
                    $errors[] = htmlspecialchars($files['name'][$i]) . ' is not a valid image type (jpg, png, webp only).';
                    continue;
                }
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXT, true)) {
                    $ext = 'jpg';
                }
                $newUploadedFiles[] = [
                    'tmp'      => $files['tmp_name'][$i],
                    'filename' => uniqid('img_', true) . '.' . $ext,
                ];
            }
        }

        if (empty($errors)) {
            // Persist metadata changes
            $pdo->prepare(
                'UPDATE images SET image_code = ?, name = ?, description = ?, dimensions = ?, image_type = ? WHERE id = ?'
            )->execute([$imageCode, $name, $description, $dimensions, $imageType, $imageId]);

            // Move and register new files (not primary — admin can set primary separately)
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            foreach ($newUploadedFiles as $f) {
                if (move_uploaded_file($f['tmp'], UPLOAD_DIR . $f['filename'])) {
                    $pdo->prepare(
                        'INSERT INTO image_files (image_id, filename, is_primary) VALUES (?, ?, 0)'
                    )->execute([$imageId, $f['filename']]);
                } else {
                    $errors[] = 'Failed to save uploaded file: ' . $f['filename'];
                }
            }

            if (empty($errors)) {
                setFlash('success', 'Image "' . $name . '" updated successfully!');
                header('Location: ' . SITE_URL . '/admin/edit-image.php?id=' . $imageId);
                exit;
            }
        }
    }
}

// Reload fresh data after any action
$image         = getImageById($pdo, $imageId);
$existingFiles = getImageFiles($pdo, $imageId);
$csrfToken     = getCsrfToken();
?>

<!-- Page header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">
        <i class="fas fa-edit me-2"></i>Edit Image
        <small class="text-muted fs-6 ms-2">#<?= $imageId ?></small>
    </h4>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $imageId ?>" target="_blank"
           class="btn btn-outline-info btn-sm">
            <i class="fas fa-eye me-1"></i>View Live
        </a>
        <a href="<?= SITE_URL ?>/admin/manage-images.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-list me-1"></i>Back to List
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- ================================================================
     SECTION 1: Metadata + Add More Files
================================================================ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2 text-primary"></i>Image Details</h6>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="?id=<?= $imageId ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action"     value="update">

            <div class="row g-3">
                <!-- Image Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image Type *</label>
                    <select name="image_type" class="form-select" required>
                        <option value="free"    <?= $image['image_type'] === 'free'    ? 'selected' : '' ?>>Free</option>
                        <option value="premium" <?= $image['image_type'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                    </select>
                </div>

                <!-- Image Code -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Unique Image Code *</label>
                    <input type="text" name="image_code" class="form-control"
                           value="<?= e($image['image_code']) ?>"
                           placeholder="e.g. DESIGN001" required>
                    <div class="invalid-feedback">Code is required and must be unique.</div>
                </div>

                <!-- Image Name -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image Name *</label>
                    <input type="text" name="image_name" class="form-control"
                           value="<?= e($image['name']) ?>"
                           placeholder="e.g. Abstract Blue" required>
                </div>

                <!-- Dimensions -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Dimensions <span class="text-muted">(optional)</span></label>
                    <input type="text" name="dimensions" class="form-control"
                           value="<?= e($image['dimensions'] ?? '') ?>"
                           placeholder="e.g. 1920x1080 or 4K">
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-muted">(optional)</span></label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Brief description..."><?= e($image['description'] ?? '') ?></textarea>
                </div>

                <!-- Add More Files -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Add More Images <span class="text-muted">(jpg, png, webp | max 5 MB each)</span>
                    </label>
                    <input type="file" name="images[]" id="more-images" class="form-control"
                           multiple accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">
                        Select one or more images to add to this set. Leave empty to keep current files unchanged.
                    </div>
                </div>

                <!-- New file preview -->
                <div class="col-12">
                    <div id="new-upload-preview" class="d-flex flex-wrap gap-2 mt-1"></div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="<?= SITE_URL ?>/admin/manage-images.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================
     SECTION 2: Existing Files
================================================================ -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-photo-video me-2 text-primary"></i>
            Current Files
            <span class="badge bg-secondary ms-2"><?= count($existingFiles) ?></span>
        </h6>
        <span class="text-muted small">Click <strong>Set Primary</strong> to change the thumbnail</span>
    </div>
    <div class="card-body p-4">
        <?php if (empty($existingFiles)): ?>
            <p class="text-muted text-center py-3">No files uploaded yet. Use the form above to add images.</p>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($existingFiles as $file): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border <?= $file['is_primary'] ? 'border-primary border-2' : '' ?> position-relative">
                    <?php if ($file['is_primary']): ?>
                    <span class="position-absolute top-0 start-0 badge bg-primary m-2" style="z-index:1">
                        <i class="fas fa-star me-1"></i>Primary
                    </span>
                    <?php endif; ?>

                    <img src="<?= e(UPLOAD_URL . $file['filename']) ?>"
                         alt="File <?= (int)$file['id'] ?>"
                         class="card-img-top"
                         style="height:150px;object-fit:cover;"
                         onerror="this.src='https://via.placeholder.com/300x200?text=No+Preview'">

                    <div class="card-body p-2 d-flex flex-column gap-2">
                        <p class="small text-muted mb-0 text-truncate" title="<?= e($file['filename']) ?>">
                            <?= e($file['filename']) ?>
                        </p>
                        <p class="small text-muted mb-0">
                            Added <?= date('M j, Y', strtotime($file['created_at'])) ?>
                        </p>

                        <!-- Set Primary -->
                        <?php if (!$file['is_primary']): ?>
                        <form method="POST" action="?id=<?= $imageId ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action"     value="set_primary">
                            <input type="hidden" name="file_id"    value="<?= (int)$file['id'] ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-star me-1"></i>Set Primary
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Delete File -->
                        <form method="POST" action="?id=<?= $imageId ?>"
                              onsubmit="return confirm('Remove this file? This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="action"     value="delete_file">
                            <input type="hidden" name="file_id"    value="<?= (int)$file['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                <?= count($existingFiles) <= 1 ? 'disabled title="Cannot remove the only file"' : '' ?>>
                                <i class="fas fa-trash me-1"></i>Remove
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Live preview of newly selected files before upload
document.getElementById('more-images').addEventListener('change', function () {
    const preview = document.getElementById('new-upload-preview');
    preview.innerHTML = '';
    Array.from(this.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = e => {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'position:relative;display:inline-block;';
            wrapper.innerHTML =
                '<img src="' + e.target.result + '" ' +
                'style="width:100px;height:80px;object-fit:cover;border-radius:6px;border:2px solid #dee2e6">' +
                '<small class="d-block text-muted text-truncate mt-1" style="max-width:100px">' +
                file.name + '</small>';
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
