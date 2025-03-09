<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../models/Product.php';

// Získání seznamu produktů
$products = Product::getAllProducts();

// Current tab - default to products
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="/mprojekt/public/assets/css/styles.css">
    <style>
        /* Admin panel tabs styling */
        .admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .admin-tabs .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            background-color: #f0f0f0;
        }
        
        .admin-tabs .tab.active {
            background-color: #3498db;
            color: white;
            border-color: #ddd;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* User management specific styles */
        .user-actions {
            margin-bottom: 20px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .role-admin {
            background-color: #e74c3c;
            color: white;
        }
        
        .role-user {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/navbar.php'; ?>
<div class="adminpanel">
    <div class="container">
        <h1>Admin Panel</h1>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-info"><?= htmlspecialchars($_SESSION['message']); ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <div class="admin-tabs">
            <a href="/mprojekt/public/admin?tab=products" class="tab <?= $activeTab == 'products' ? 'active' : '' ?>">Správa produktů</a>
            <a href="/mprojekt/public/admin?tab=users" class="tab <?= $activeTab == 'users' ? 'active' : '' ?>">Správa uživatelů</a>
        </div>
        
        <!-- Products Tab Content -->
        <div class="tab-content <?= $activeTab == 'products' ? 'active' : '' ?>" id="products-tab">
            <h2>Přidat nový produkt</h2>
            <form action="/mprojekt/public/admin/create" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Název:</label>
                        <input type="text" id="name" name="name" required maxlength="100" placeholder="Název produktu">
                    </div>
                    
                    <div class="form-group">
                        <label for="sku">Kód produktu (SKU):</label>
                        <input type="text" id="sku" name="sku" maxlength="50" placeholder="Např. PROD-001">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Popis:</label>
                    <textarea id="description" name="description" required rows="4" placeholder="Detailní popis produktu"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Cena (Kč):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Množství skladem:</label>
                        <input type="number" id="stock" name="stock" min="0" value="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Kategorie:</label>
                        <select id="category" name="category" required>
                            <option value="">-- Vyberte kategorii --</option>
                            <option value="Doplňky stravy">Doplňky stravy</option>
                            <option value="Fitness vybavení">Fitness vybavení</option>
                            <option value="Oblečení">Oblečení</option>
                            <option value="Příslušenství">Příslušenství</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Stav:</label>
                        <select id="status" name="status">
                            <option value="active" selected>Aktivní</option>
                            <option value="inactive">Neaktivní</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Obrázek produktu:</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
                    <small>Povolené formáty: JPG, PNG, GIF. Maximální velikost: 2MB</small>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="featured" name="featured" value="1">
                    <label for="featured">Doporučený produkt</label>
                </div>

                <button type="submit" class="btn btn-primary">Vytvořit produkt</button>
            </form>

            <h2>Správa produktů</h2>
            <div class="table-responsive">
                <table class="admin-products">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Název</th>
                            <th>Popis</th>
                            <th>Cena</th>
                            <th>Skladem</th>
                            <th>Kategorie</th>
                            <th>Stav</th>
                            <th>Obrázek</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td class="description-cell"><?= htmlspecialchars(substr($product['description'], 0, 100)) ?><?= (strlen($product['description']) > 100) ? '...' : '' ?></td>
                                <td><?= number_format($product['price'], 2) ?> Kč</td>
                                <td><?= isset($product['stock']) ? $product['stock'] : 0 ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><span class="status-badge <?= isset($product['status']) && $product['status'] == 'active' ? 'active' : 'inactive' ?>"><?= isset($product['status']) ? ($product['status'] == 'active' ? 'Aktivní' : 'Neaktivní') : 'Aktivní' ?></span></td>
                                <td><img src="/mprojekt/public/assets/images/<?= htmlspecialchars($product['image'] ?? 'default.png') ?>" 
                                        width="50" 
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        onerror="this.onerror=null; this.src='/mprojekt/public/assets/images/default.png';">
                                </td>
                                <td class="actions">
                                    <a href="/mprojekt/public/admin/edit?id=<?= $product['id'] ?>" class="btn btn-warning">Upravit</a>
                                    <a href="/mprojekt/public/admin/delete?id=<?= $product['id'] ?>" class="btn btn-danger" onclick="return confirm('Opravdu chcete tento produkt smazat?');">Smazat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Users Tab Content -->
        <div class="tab-content <?= $activeTab == 'users' ? 'active' : '' ?>" id="users-tab">
            <?php 
            if ($activeTab == 'users') {
                include __DIR__ . '/users.php';
            }
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add tab functionality
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // This is now handled by the href links, but keeping for any direct clicks
            if (!this.classList.contains('active')) {
                // Update active tab
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show active content
                const tabId = this.getAttribute('href').split('=')[1];
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId + '-tab') {
                        content.classList.add('active');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
