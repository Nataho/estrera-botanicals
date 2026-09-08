<?php
require_once '../../config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/Image.php';
require_once ROOT_DIR . 'classes/UploadManager.php';

// admin only
require_role('admin');

$user = current_user();
$img = new Image($pdo);
$uploader = new UploadManager($pdo);

$message = '';
$error = '';

// handle upload form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['images'])) {
    // check if it's multiple files or single
    if (is_array($_FILES['images']['name'])) {
        $results = $uploader->upload_multiple($_FILES['images'], $user->id);

        $success_count = 0;
        $errors = [];
        foreach ($results as $result) {
            if (is_array($result)) {
                $success_count++;
            } else {
                $errors[] = $result;
            }
        }

        if ($success_count > 0) {
            $message = "$success_count image(s) uploaded";
        }
        if (!empty($errors)) {
            $error = implode(', ', $errors);
        }
    } else {
        $result = $uploader->upload($_FILES['images'], $user->id);
        if (is_array($result)) {
            $message = 'image uploaded: ' . $result['original_name'];
        } else {
            $error = $result;
        }
    }
}

// handle create product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $p_name  = trim($_POST['product_name'] ?? '');
    $p_desc  = trim($_POST['product_description'] ?? '');
    $p_price = filter_var($_POST['product_price'] ?? '', FILTER_VALIDATE_FLOAT);
    $p_stock = filter_var($_POST['product_stock'] ?? '0', FILTER_VALIDATE_INT);
    $p_img   = trim($_POST['product_img'] ?? '');

    // check if a new image file was also uploaded along with the product
    if (!empty($_FILES['product_image_file']['name'])) {
        $uploaded = $uploader->upload($_FILES['product_image_file'], $user->id);
        if (is_array($uploaded)) {
            $p_img = $uploaded['filename'];
        } else {
            $error = 'Product image upload failed: ' . $uploaded;
        }
    }

    if (empty($error)) {
        if ($p_name === '' || $p_price === false) {
            $error = 'Product name and a valid price are required.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO products (product_name, product_description, product_price, product_stock_quantity, product_img) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $p_name,
                    $p_desc,
                    $p_price,
                    $p_stock !== false ? $p_stock : 0,
                    $p_img
                ]);
                $message = "Product '{$p_name}' successfully added!";
            } catch (PDOException $e) {
                $error = 'Failed to add product: ' . $e->getMessage();
            }
        }
    }
}

// handle delete image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $deleted = $img->delete((int) $_POST['delete_id']);
    $message = $deleted ? 'image deleted' : 'could not delete image';
}

// get all images for the gallery & product dropdown
$all_images = $img->all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media & Product Manager | Admin</title>
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page">
    <?php require ROOT_DIR . 'components/header.php'; ?>

    <main class="admin-wrapper">
        <div class="admin-top-nav">
            <a class="admin-breadcrumb" href="<?= BASE_URL ?>admin/dashboard">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
            <span class="admin-badge">Admin Workspace</span>
        </div>

        <div class="admin-page-header">
            <h1>Media & Product Manager</h1>
            <p>Upload new assets to the shared library and manage store products.</p>
        </div>

        <?php if ($message): ?>
            <div class="admin-alert ok">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-alert err">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- 1. Media Upload Card -->
            <section class="admin-card">
                <div class="admin-card-header">
                    <h2>Upload Media Images</h2>
                    <p>Direct upload to <code>eb-uploads</code> directory</p>
                </div>

                <form class="admin-form" method="POST" enctype="multipart/form-data">
                    <div class="upload-dropzone" data-dropzone="multi">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <p>Drag &amp; drop images here, or click to browse</p>
                        <span class="browse-btn">Choose Files</span>
                        <input type="file" name="images[]" multiple accept="image/*">
                        <div class="dropzone-file-indicator"></div>
                    </div>
                    <button type="submit" class="btn-admin">
                        <i class="fa-solid fa-upload"></i> Upload Media
                    </button>
                </form>
            </section>

            <!-- 2. Add New Product Card -->
            <section class="admin-card">
                <div class="admin-card-header">
                    <h2>Add New Product</h2>
                    <p>Register a botanicals product to the database</p>
                </div>

                <form class="admin-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">

                    <div class="form-group">
                        <label for="product_name">Product Name *</label>
                        <input type="text" id="product_name" name="product_name" required placeholder="e.g. Organic Lavender Soap">
                    </div>

                    <div class="form-group">
                        <label for="product_description">Description</label>
                        <textarea id="product_description" name="product_description" rows="3" placeholder="Handcrafted herbal soap with soothing lavender..."></textarea>
                    </div>

                    <div class="form-row-pair">
                        <div class="form-group">
                            <label for="product_price">Price (₱) *</label>
                            <input type="number" id="product_price" name="product_price" step="0.01" min="0" required placeholder="150.00">
                        </div>
                        <div class="form-group">
                            <label for="product_stock">Stock Quantity</label>
                            <input type="number" id="product_stock" name="product_stock" min="0" value="10">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="product_img">Select Existing Uploaded Image</label>
                        <select id="product_img" name="product_img">
                            <option value="">-- None or choose from gallery --</option>
                            <?php foreach ($all_images as $img_item): ?>
                                <option value="<?= htmlspecialchars($img_item['filename']) ?>">
                                    <?= htmlspecialchars($img_item['original_name']) ?> (<?= htmlspecialchars($img_item['filename']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Or Upload Product Image Directly (Drag &amp; Drop)</label>
                        <div class="upload-dropzone compact" data-dropzone="single">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Drag &amp; drop product photo here, or click to browse</p>
                            <span class="browse-btn">Choose Photo</span>
                            <input type="file" id="product_image_file" name="product_image_file" accept="image/*">
                            <img class="dropzone-preview-thumb" alt="Product Preview">
                            <div class="dropzone-file-indicator"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn-admin">
                        <i class="fa-solid fa-plus"></i> Save Product
                    </button>
                </form>
            </section>
        </div>

        <!-- 3. Uploaded Images Gallery -->
        <section class="admin-gallery-section">
            <div class="gallery-section-header">
                <h2>Media Library</h2>
                <span><?= count($all_images) ?> uploaded image(s)</span>
            </div>

            <?php if (empty($all_images)): ?>
                <p style="color: var(--palette-gray); font-family: var(--document-font);">No uploaded images found in library.</p>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($all_images as $image): ?>
                        <div class="gallery-card">
                            <div class="gallery-preview-wrapper">
                                <?php if ($image['exists']): ?>
                                    <img src="<?= htmlspecialchars($image['url']) ?>" alt="<?= htmlspecialchars($image['original_name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="height:100%; display:flex; align-items:center; justify-content:center; color: var(--palette-gray); font-size: 0.85rem;">
                                        <i class="fa-solid fa-image-slash" style="margin-right: 6px;"></i> Missing
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="gallery-card-content">
                                <div>
                                    <div class="gallery-card-title"><?= htmlspecialchars($image['original_name']) ?></div>
                                    <div class="gallery-card-meta">
                                        ID: <code><?= $image['image_id'] ?></code><br>
                                        <code><?= htmlspecialchars($image['filename']) ?></code>
                                    </div>
                                </div>
                                <div class="gallery-card-actions">
                                    <form method="POST" onsubmit="return confirm('Delete this image permanently?')">
                                        <input type="hidden" name="delete_id" value="<?= $image['image_id'] ?>">
                                        <button type="submit" class="btn-admin danger" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php require ROOT_DIR . 'components/footer.php'; ?>

    <script>
        document.querySelectorAll('.upload-dropzone').forEach(dropzone => {
            const input = dropzone.querySelector('input[type="file"]');
            const indicator = dropzone.querySelector('.dropzone-file-indicator');
            const previewThumb = dropzone.querySelector('.dropzone-preview-thumb');

            // Drag and drop event handlers
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            // Handle dropped files
            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    input.files = dt.files;
                    updateIndicator(input.files);
                }
            });

            // Handle file input change (clicks)
            input.addEventListener('change', () => {
                updateIndicator(input.files);
            });

            function updateIndicator(files) {
                if (!files || files.length === 0) {
                    if (indicator) indicator.style.display = 'none';
                    if (previewThumb) previewThumb.style.display = 'none';
                    return;
                }

                if (indicator) {
                    indicator.style.display = 'inline-flex';
                    if (files.length === 1) {
                        indicator.innerHTML = `<i class="fa-solid fa-check"></i> ${files[0].name} (${(files[0].size / 1024).toFixed(1)} KB)`;
                    } else {
                        indicator.innerHTML = `<i class="fa-solid fa-check"></i> ${files.length} files selected`;
                    }
                }

                // Show preview thumbnail if it's a single image
                if (previewThumb && files[0] && files[0].type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewThumb.src = e.target.result;
                        previewThumb.style.display = 'block';
                    };
                    reader.readAsDataURL(files[0]);
                }
            }
        });
    </script>
</body>
</html>
