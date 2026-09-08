<?php 
require_once '../../config.php';
require_once ROOT_DIR . 'functions/auth.php';

// only admins allowed here, kick anyone else to 403
require_role('admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Estrera Botanicals</title>
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
    <style>
        .admin-dashboard-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .admin-nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        .admin-card {
            display: block;
            padding: 24px;
            background: #f9fbf9;
            border: 1.5px solid #dbe6dc;
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
        }
        .admin-card:hover {
            border-color: var(--palette-dark-green, #2b502e);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(43, 80, 46, 0.1);
        }
        .admin-card h3 {
            margin: 0 0 8px 0;
            color: var(--palette-dark-green, #2b502e);
        }
        .admin-card p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars(current_user()->name ?? 'Admin') ?>!</p>
        
        <div class="admin-nav-cards">
            <a href="<?= BASE_URL ?>admin/upload" class="admin-card">
                <h3>🖼️ Media & Upload Manager</h3>
                <p>Upload images to eb-uploads, copy IDs, manage gallery, and delete media files.</p>
            </a>
            <a href="<?= BASE_URL ?>shop" class="admin-card">
                <h3>🛍️ View Shop</h3>
                <p>Browse products and view store front.</p>
            </a>
        </div>
    </div>
</body>
</html>