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
        // SEO fields
        $seoTitle       = mb_substr(trim($_POST['seo_title']       ?? ''), 0, 120);
        $seoDescription = mb_substr(trim($_POST['seo_description'] ?? ''), 0, 200);
        $altText        = mb_substr(trim($_POST['alt_text']        ?? ''), 0, 255);
        $tags           = mb_substr(trim($_POST['tags']            ?? ''), 0, 500);
        $slugInput      = trim($_POST['slug'] ?? '');
        $slugBase       = $slugInput !== '' ? generateSlug($slugInput) : generateSlug($name);

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

        // Resolve unique slug
        $finalSlug = empty($errors) ? uniqueImageSlug($pdo, $slugBase) : '';

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
                'INSERT INTO images
                    (image_code, name, description, dimensions, image_type, primary_image,
                     slug, seo_title, seo_description, alt_text, tags)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $primaryUrl = '';
            foreach ($uploadedFiles as $f) {
                if ($f['is_primary']) $primaryUrl = UPLOAD_URL . $f['filename'];
            }
            $insertStmt->execute([
                $imageCode, $name, $description, $dimensions, $imageType, $primaryUrl,
                $finalSlug, $seoTitle ?: null, $seoDescription ?: null, $altText ?: null, $tags ?: null,
            ]);
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

                <!-- ===== SEO Settings ===== -->
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="fw-bold mb-3 text-primary">
                        <i class="fas fa-search me-2"></i>SEO Settings
                        <small class="text-muted fw-normal fs-6 ms-1">(for organic search &amp; social sharing)</small>
                    </h6>
                    <div class="row g-3">

                        <!-- URL Slug -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                URL Slug
                                <small class="text-muted fw-normal">– leave blank to auto-generate from name</small>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text text-muted small"><?= e(SITE_URL) ?>/design/</span>
                                <input type="text" name="slug" id="seo-slug" class="form-control"
                                       value="<?= e($_POST['slug'] ?? '') ?>"
                                       placeholder="e.g. abstract-blue-free-design"
                                       pattern="[a-z0-9\-]+"
                                       title="Lowercase letters, numbers, and hyphens only">
                            </div>
                        </div>

                        <!-- SEO Title -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                SEO Title
                                <small class="text-muted fw-normal">– Google result headline (recommended: 50–60 chars, max 120)</small>
                            </label>
                            <input type="text" name="seo_title" id="seo-title" class="form-control"
                                   value="<?= e($_POST['seo_title'] ?? '') ?>"
                                   placeholder="e.g. Abstract Blue 1920×1080 Free Design | Just Designs"
                                   maxlength="120">
                            <div class="d-flex justify-content-between mt-1">
                                <div class="form-text">Leave blank to use the image name. Google typically shows 50–60 characters.</div>
                                <small id="seo-title-count" class="text-muted">0 / 60</small>
                            </div>
                        </div>

                        <!-- Meta Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                Meta Description
                                <small class="text-muted fw-normal">– Google snippet (max 160 chars)</small>
                            </label>
                            <textarea name="seo_description" id="seo-description" class="form-control" rows="2"
                                      placeholder="e.g. Download this vibrant abstract blue design for free. Perfect for wallpapers and creative projects."
                                      maxlength="200"><?= e($_POST['seo_description'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <div class="form-text">Leave blank to use the description. Aim for 120–160 characters.</div>
                                <small id="seo-desc-count" class="text-muted">0 / 160</small>
                            </div>
                        </div>

                        <!-- Alt Text -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Image Alt Text
                                <small class="text-muted fw-normal">– for Google Images &amp; screen readers</small>
                            </label>
                            <input type="text" name="alt_text" class="form-control"
                                   value="<?= e($_POST['alt_text'] ?? '') ?>"
                                   placeholder="e.g. Abstract blue digital art with swirling gradients"
                                   maxlength="255">
                        </div>

                        <!-- Tags -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Tags / Keywords
                                <small class="text-muted fw-normal">– comma-separated</small>
                            </label>
                            <input type="text" name="tags" class="form-control"
                                   value="<?= e($_POST['tags'] ?? '') ?>"
                                   placeholder="e.g. abstract, blue, gradient, digital art, wallpaper"
                                   maxlength="500">
                            <div class="form-text">Add 5–10 relevant terms for meta keywords and schema.</div>
                        </div>

                        <!-- Live SERP Preview -->
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <p class="small fw-semibold text-muted mb-2"><i class="fab fa-google me-1"></i>Search result preview</p>
                                <div id="serp-title" style="color:#1a0dab;font-size:1.1rem;line-height:1.3;font-family:arial,sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">&nbsp;</div>
                                <div id="serp-url"   style="color:#006621;font-size:.8rem;font-family:arial,sans-serif;"><?= e(SITE_URL) ?>/design/<span id="serp-slug-part"></span></div>
                                <div id="serp-desc"  style="color:#545454;font-size:.87rem;font-family:arial,sans-serif;margin-top:2px;">&nbsp;</div>
                            </div>
                        </div>

                    </div><!-- /.row -->
                </div>
                <!-- ===== /SEO Settings ===== -->

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

<script>
// SEO SERP live preview + character counters
(function () {
    const siteName    = <?= json_encode(SITE_NAME) ?>;
    const imageNameEl = document.querySelector('input[name="image_name"]');
    const slugEl      = document.getElementById('seo-slug');
    const titleEl     = document.getElementById('seo-title');
    const descEl      = document.getElementById('seo-description');
    const titleCount  = document.getElementById('seo-title-count');
    const descCount   = document.getElementById('seo-desc-count');
    const serpTitle   = document.getElementById('serp-title');
    const serpSlug    = document.getElementById('serp-slug-part');
    const serpDesc    = document.getElementById('serp-desc');

    function toSlug(str) {
        return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }

    function updatePreview() {
        const rawName = (imageNameEl ? imageNameEl.value.trim() : '') || 'Image Name';
        const title   = titleEl.value.trim() || rawName + ' | ' + siteName;
        const slug    = slugEl.value.trim() || toSlug(rawName);
        const desc    = descEl.value.trim() || '';

        serpTitle.textContent = title.length > 70 ? title.substring(0, 70) + '…' : title;
        serpSlug.textContent  = slug;
        serpDesc.textContent  = desc.length > 160 ? desc.substring(0, 160) + '…' : (desc || '(no meta description set)');

        const tLen = titleEl.value.length;
        titleCount.textContent = tLen + ' / 60';
        titleCount.style.color = tLen > 70 ? '#dc3545' : tLen > 60 ? '#fd7e14' : '#6c757d';

        const dLen = descEl.value.length;
        descCount.textContent = dLen + ' / 160';
        descCount.style.color = dLen > 200 ? '#dc3545' : dLen > 160 ? '#fd7e14' : '#6c757d';
    }

    if (imageNameEl) {
        imageNameEl.addEventListener('input', function () {
            if (!slugEl.value.trim()) {
                slugEl.placeholder = 'e.g. ' + toSlug(this.value.trim());
            }
            updatePreview();
        });
    }

    [slugEl, titleEl, descEl].forEach(el => el && el.addEventListener('input', updatePreview));

    updatePreview();
})();
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

