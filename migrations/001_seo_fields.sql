-- Migration 001: Add per-image SEO fields
-- Run once against the justdesigns database.

ALTER TABLE images
    ADD COLUMN IF NOT EXISTS slug            VARCHAR(300) NULL UNIQUE AFTER image_code,
    ADD COLUMN IF NOT EXISTS seo_title       VARCHAR(120) NULL        AFTER description,
    ADD COLUMN IF NOT EXISTS seo_description VARCHAR(200) NULL        AFTER seo_title,
    ADD COLUMN IF NOT EXISTS alt_text        VARCHAR(255) NULL        AFTER seo_description,
    ADD COLUMN IF NOT EXISTS tags            TEXT         NULL        AFTER alt_text;

-- Back-fill slugs for existing rows
UPDATE images
SET    slug = CONCAT(
           LOWER(REGEXP_REPLACE(REPLACE(name, ' ', '-'), '[^a-z0-9\\-]', '')),
           '-',
           id
       )
WHERE  slug IS NULL OR slug = '';

-- Fast lookup index (skip if already exists — re-run safe via procedure)
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE()
      AND table_name   = 'images'
      AND index_name   = 'idx_images_slug'
);
SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE images ADD INDEX idx_images_slug (slug)',
    'SELECT "Index idx_images_slug already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
