/**
 * Just Designs - Main JavaScript
 * jQuery + AJAX functionality
 */
$(function () {

    /* ============================================
       Lazy Loading with IntersectionObserver
    ============================================ */
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        lazyImages.forEach((img) => observer.observe(img));
    } else {
        // Fallback: load all images immediately
        $('img.lazy').each(function () {
            $(this).attr('src', $(this).data('src')).addClass('loaded');
        });
    }

    /* ============================================
       Load More Images (Home Page)
    ============================================ */
    let offset = parseInt($('#image-grid').data('initial-count') || 30);
    const loadMoreBtn = $('#load-more-btn');
    const imageGrid = $('#image-grid');

    loadMoreBtn.on('click', function () {
        const type = loadMoreBtn.data('type') || 'all';
        loadMoreBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Loading...'
        );

        $.ajax({
            url: (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/load_more.php',
            type: 'GET',
            data: { offset: offset, type: type },
            success: function (response) {
                if (response.html) {
                    imageGrid.append(response.html);
                    offset += response.count;
                    initLazyImages();

                    if (!response.has_more) {
                        loadMoreBtn.closest('#load-more-wrapper').html(
                            '<p class="text-muted">No more images to load.</p>'
                        );
                    } else {
                        loadMoreBtn.prop('disabled', false).html(
                            '<i class="fas fa-plus me-2"></i>Load More'
                        );
                    }
                } else {
                    loadMoreBtn.closest('#load-more-wrapper').html(
                        '<p class="text-muted">No more images to load.</p>'
                    );
                }
            },
            error: function () {
                loadMoreBtn.prop('disabled', false).html(
                    '<i class="fas fa-plus me-2"></i>Load More'
                );
                showToast('Error loading images. Please try again.', 'danger');
            }
        });
    });

    /* ============================================
       Like / Unlike Button
    ============================================ */
    $(document).on('click', '.like-btn', function () {
        const btn = $(this);
        const imageId = btn.data('image-id');

        if (!imageId) return;

        // Check auth
        if (btn.data('auth') === 'no') {
            showToast('Please login to like images.', 'warning');
            return;
        }

        btn.prop('disabled', true);

        $.ajax({
            url: (typeof SITE_URL !== 'undefined' ? SITE_URL : '') + '/ajax/like.php',
            type: 'POST',
            data: {
                image_id: imageId,
                csrf_token: $('meta[name="csrf-token"]').attr('content') || ''
            },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    if (res.action === 'liked') {
                        btn.addClass('liked').find('i').removeClass('far').addClass('fas');
                    } else {
                        btn.removeClass('liked').find('i').removeClass('fas').addClass('far');
                    }
                    // Update count
                    const countSpan = btn.find('.like-count');
                    if (countSpan.length) {
                        countSpan.text(res.count);
                    }
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message || 'Error. Please login first.', 'warning');
                }
            },
            error: function () {
                showToast('Could not process your request.', 'danger');
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    /* ============================================
       Image Detail Gallery Switcher
    ============================================ */
    $(document).on('click', '.detail-gallery-thumb', function () {
        const src = $(this).attr('src');
        $('#primary-detail-img').attr('src', src);
        $('.detail-gallery-thumb').removeClass('active');
        $(this).addClass('active');
    });

    /* ============================================
       Admin: Upload Preview with Primary Selection
    ============================================ */
    $('#images').on('change', function () {
        const files = this.files;
        const preview = $('#upload-preview');
        preview.empty();
        $('#primary-radio-group').empty();

        if (files.length === 0) return;

        Array.from(files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const item = $(`
                    <div class="preview-item" id="preview-item-${index}">
                        <img src="${e.target.result}" alt="Preview">
                        <label>
                            <input type="radio" name="primary_image_index" value="${index}"
                                ${index === 0 ? 'checked' : ''}>
                            Primary
                        </label>
                    </div>
                `);
                preview.append(item);

                // Highlight selected primary
                preview.find('input[type=radio]').on('change', function () {
                    preview.find('.preview-item').removeClass('primary-selected');
                    $(`#preview-item-${$(this).val()}`).addClass('primary-selected');
                });

                if (index === 0) item.addClass('primary-selected');
            };
            reader.readAsDataURL(file);
        });
    });

    /* ============================================
       Toast Notification Helper
    ============================================ */
    window.showToast = function (message, type = 'info') {
        const id = 'toast-' + Date.now();
        const bg = type === 'success' ? 'bg-success'
                 : type === 'danger' ? 'bg-danger'
                 : type === 'warning' ? 'bg-warning text-dark'
                 : 'bg-info';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bg} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;

        if (!$('#toast-container').length) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>');
        }

        $('#toast-container').append(html);
        const toastEl = document.getElementById(id);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 3500 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    /* ============================================
       Re-initialize lazy loading for dynamic content
    ============================================ */
    window.initLazyImages = function () {
        if ('IntersectionObserver' in window) {
            const newImages = document.querySelectorAll('img.lazy:not(.loaded)');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });
            newImages.forEach((img) => observer.observe(img));
        } else {
            $('img.lazy:not(.loaded)').each(function () {
                $(this).attr('src', $(this).data('src')).addClass('loaded');
            });
        }
    };

    /* ============================================
       Admin: Delete Confirm
    ============================================ */
    $(document).on('click', '.btn-delete-confirm', function (e) {
        if (!confirm('Are you sure you want to delete this item? This cannot be undone.')) {
            e.preventDefault();
        }
    });

    /* ============================================
       Form Validation Feedback
    ============================================ */
    $('form.needs-validation').on('submit', function (e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

});
