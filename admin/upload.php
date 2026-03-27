<?php
/**
 * Admin Upload Images
 * Just Designs - admin/upload.php
 */
$adminPageTitle = 'Upload Images';
require_once __DIR__ . '/includes/admin_header.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name        = trim($_POST['image_name'] ?? '');
        $imageCode   = trim($_POST['image_code'] ?? '');
        $imageType   = $_POST['image_type'] === 'premium' ? 'premium' : 'free';
        $dimensions  = trim($_POST['dimensions'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $primaryIdx  = (int)($_POST['primary_image_index'] ?? 0);

        // Validation
        if (empty($name))       $errors[] = 'Image name is required.';
        if (empty($imageCode))  $errors[] = 'Image code is required.';

        // Check unique code
        if (empty($errors)) {
            $codeCheck = $pdo->prepare('SELECT id FROM images WHERE image_code = ?');
            $codeCheck->execute([$imageCode]);
            if ($codeCheck->fetch()) {
                $errors[] = 'Image code already exists. Please use a unique code.';
            }
        }

        // Process uploaded files
        $uploadedFiles = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $totalFiles = count($files['name']);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Upload error for file ' . ($i + 1) . ': ' . $files['error'][$i];
                    continue;
                }

                // Size check
                if ($files['size'][$i] > MAX_FILE_SIZE) {
                    $maxMb = MAX_FILE_SIZE / (1024 * 1024);
                    $errors[] = htmlspecialchars($files['name'][$i]) . ' exceeds ' . $maxMb . 'MB limit.';
                    continue;
                }

                // Type check using finfo (more secure than MIME type header)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($files['tmp_name'][$i]);
                if (!in_array($mimeType, ALLOWED_TYPES, true)) {
                    $errors[] = htmlspecialchars($files['name'][$i]) . ' is not a valid image type (jpg, png, webp only).';
                    continue;
                }

                // Generate unique filename
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXT, true)) $ext = 'jpg';
                $newFilename = uniqid('img_', true) . '.' . $ext;

                $uploadedFiles[] = [
                    'tmp'        => $files['tmp_name'][$i],
                    'filename'   => $newFilename,
                    'is_primary' => ($i === $primaryIdx) ? 1 : 0,
                ];
            }
        }

        if (empty($errors)) {
            // Insert main image record
            $insertStmt = $pdo->prepare(
                'INSERT INTO images (image_code, name, description, dimensions, image_type, primary_image)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $primaryUrl = '';
            foreach ($uploadedFiles as $f) {
                if ($f['is_primary']) $primaryUrl = UPLOAD_URL . $f['filename'];
            }
            $insertStmt->execute([$imageCode, $name, $description, $dimensions, $imageType, $primaryUrl]);
            $imageId = (int)$pdo->lastInsertId();

            // Move files and insert file records
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

            foreach ($uploadedFiles as $f) {
                if (move_uploaded_file($f['tmp'], UPLOAD_DIR . $f['filename'])) {
                    $fileStmt = $pdo->prepare(
                        'INSERT INTO image_files (image_id, filename, is_primary) VALUES (?, ?, ?)'
                    );
                    $fileStmt->execute([$imageId, $f['filename'], $f['is_primary']]);
                } else {
                    $errors[] = 'Failed to move uploaded file: ' . $f['filename'];
                }
            }

            if (empty($errors)) {
                $success = true;
                setFlash('success', 'Image "' . $name . '" uploaded successfully!');
            }
        }
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fas fa-upload me-2"></i>Upload Images</h4>
    <a href="<?= SITE_URL ?>/admin/manage-images.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-list me-1"></i>Manage Images
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?><li><?= $err ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="row g-3">
                <!-- Image Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image Type *</label>
                    <select name="image_type" class="form-select" required>
                        <option value="free" <?= ($_POST['image_type'] ?? '') === 'free' ? 'selected' : '' ?>>Free</option>
                        <option value="premium" <?= ($_POST['image_type'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium</option>
                    </select>
                </div>

                <!-- Image Code -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Unique Image Code *</label>
                    <input type="text" name="image_code" class="form-control"
                           value="<?= e($_POST['image_code'] ?? '') ?>"
                           placeholder="e.g. DESIGN001" required>
                    <div class="invalid-feedback">Code is required and must be unique.</div>
                </div>

                <!-- Image Name -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Image Name *</label>
                    <input type="text" name="image_name" class="form-control"
                           value="<?= e($_POST['image_name'] ?? '') ?>"
                           placeholder="e.g. Abstract Blue" required>
                </div>

                <!-- Dimensions -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Dimensions <span class="text-muted">(optional)</span></label>
                    <input type="text" name="dimensions" class="form-control"
                           value="<?= e($_POST['dimensions'] ?? '') ?>"
                           placeholder="e.g. 1920x1080 or 4K">
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Description <span class="text-muted">(optional)</span></label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Brief description of the image..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- File Upload -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Upload Images <span class="text-muted">(jpg, png, webp | max 5MB each)</span></label>
                    <input type="file" name="images[]" id="images" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp">
                    <div class="form-text">Select multiple images. Use the radio button below to set the primary (thumbnail) image.</div>
                </div>

                <!-- Upload Preview -->
                <div class="col-12">
                    <div id="upload-preview"></div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-upload me-2"></i>Upload Image
                </button>
                <a href="<?= SITE_URL ?>/admin/" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
