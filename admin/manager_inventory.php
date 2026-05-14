<?php
session_start();
require_once '../auth/auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once '../config/database.php';
$db = (new Database())->conn;

$msg = '';
$msgType = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'add') {
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price    = floatval($_POST['price'] ?? 0);
        $stock    = intval($_POST['stock'] ?? 0);

        if ($name && $price >= 0 && $stock >= 0) {
            try {
                $db->prepare("INSERT INTO products (name, category, price, stock, created_at) VALUES (?, ?, ?, ?, NOW())")
                    ->execute([$name, $category, $price, $stock]);
                echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error adding product.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields correctly.']);
        }
        exit;
    }

    if ($_POST['action'] === 'delete') {
        try {
            $db->prepare("DELETE FROM products WHERE id = ?")->execute([intval($_POST['product_id'])]);
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting product.']);
        }
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price    = floatval($_POST['price'] ?? 0);
        $stock    = intval($_POST['stock'] ?? 0);
        $id       = intval($_POST['product_id'] ?? 0);

        if ($name && $price > 0 && $stock >= 0 && $id > 0) {
            try {
                $stmt = $db->prepare("UPDATE products SET name=?, category=?, price=?, stock=? WHERE id=?");
                $stmt->execute([$name, $category, $price, $stock, $id]);
                echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields correctly.']);
        }
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build query with filters
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (name LIKE ? OR category LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_products = count($products);
$total_stock = array_sum(array_column($products, 'stock'));
$low_stock = count(array_filter($products, fn($p) => $p['stock'] < 10 && $p['stock'] > 0));
$out_of_stock = count(array_filter($products, fn($p) => $p['stock'] == 0));

// Get all categories for filter dropdown
$categories = $db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$total_products = count($products);
$total_stock = array_sum(array_column($products, 'stock'));
$low_stock = count(array_filter($products, fn($p) => $p['stock'] < 10 && $p['stock'] > 0));
$out_of_stock = count(array_filter($products, fn($p) => $p['stock'] == 0));

$baseCSS      = file_get_contents(__DIR__ . '/../assets/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/../assets/css/dashboard.css');
$inventoryCSS = file_get_contents(__DIR__ . '/../assets/css/inventory.css');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ASAJ System</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        <?= $baseCSS ?>
    </style>
    <style>
        <?= $dashboardCSS ?>
    </style>
    <style>
        <?= $inventoryCSS ?>
    </style>
    <style>
        .empty-link {
            color: #0066cc;
        }

        html[data-theme="dark"] .empty-link {
            color: #64b5f6;
        }

        .separator-dash {
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-brand">ASAJ System <span>Manager</span></div>
            <nav class="sidebar-nav">
                <div class="nav-label">Main</div>
                <a class="nav-item" href="manager_dashboard.php">Dashboard</a>
                <div class="nav-label">Management</div>
                <a class="nav-item" href="manager_sales.php">Sales Reports</a>
                <a class="nav-item active" href="manager_inventory.php">Inventory</a>
                <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
                <a class="nav-item" href="manager_audit.php">Audit Logs</a>
                <div class="nav-label">System</div>
                <a class="nav-item" href="manager_backups.php">Database Backups</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role">Manager</div>
                <a href="../auth/logout.php" class="btn-logout">Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <!-- Header -->
            <div class="inventory-header">
                <div class="header-left">
                    <h1>Inventory</h1>
                    <p>Manage products and stock levels</p>
                </div>
                <div class="header-right">
                    <button class="btn-primary" onclick="openAddSweetAlert()">+ Add Product</button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card healthy">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $total_products ?></div>
                    <div class="stat-change">Active inventory items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Stock</div>
                    <div class="stat-value"><?= $total_stock ?></div>
                    <div class="stat-change">Units in storage</div>
                </div>
                <div class="stat-card low-stock">
                    <div class="stat-label">Low Stock</div>
                    <div class="stat-value"><?= $low_stock ?></div>
                    <div class="stat-change negative">Items below threshold</div>
                </div>
                <div class="stat-card critical">
                    <div class="stat-label">Out of Stock</div>
                    <div class="stat-value"><?= $out_of_stock ?></div>
                    <div class="stat-change negative">Needs restock</div>
                </div>
            </div>

            <!-- Controls Section -->
            <div class="controls-section">
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 8px; width: 100%;">
                        <input type="text" name="search" placeholder="Search by product name or category..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">Search</button>
                        <?php if ($search || $category_filter): ?>
                            <a href="manager_inventory.php" class="btn-search-clear">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="filter-group">
                        <label for="category-filter">Category:</label>
                        <form method="GET" id="categoryForm">
                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <?php endif; ?>
                            <select name="category" id="category-filter" onchange="document.getElementById('categoryForm').submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category']) ?>"
                                        <?= $category_filter === $cat['category'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Products Table -->
            <div class="products-table-container">
                <?php if ($products): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock Status</th>
                                <th>Quantity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p):
                                $stock_status = $p['stock'] == 0 ? 'out-of-stock' : ($p['stock'] < 10 ? 'low-stock' : 'in-stock');
                                $status_text = $p['stock'] == 0 ? 'Out of Stock' : ($p['stock'] < 10 ? 'Low Stock' : 'In Stock');
                            ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div>
                                                <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                                                <div class="product-id">ID: #<?= $p['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($p['category']): ?>
                                            <span class="category-badge"><?= htmlspecialchars($p['category']) ?></span>
                                        <?php else: ?>
                                            <span class="separator-dash">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="price">₱<?= number_format($p['price'], 2) ?></div>
                                    </td>
                                    <td>
                                        <div class="stock-badge <?= $stock_status ?>">
                                            <?= $status_text ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600;"><?= $p['stock'] ?></span> units
                                        <?php if ($p['stock'] < 10 && $p['stock'] > 0): ?>
                                            <div class="stock-warning">⚠ Reorder soon</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick='openEditSweetAlert(<?= json_encode($p) ?>)'>✏️ Edit</button>
                                            <button class="btn-icon danger" onclick='deleteProductSweetAlert(<?= $p['id'] ?>, "<?= htmlspecialchars($p['name']) ?>")'>🗑️ Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>No Products Found</h3>
                        <p>
                            <?php if ($search || $category_filter): ?>
                                No products match your search criteria. <a href="manager_inventory.php" class="empty-link">View all products</a>
                            <?php else: ?>
                                Start by adding your first product
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Add Product with SweetAlert
        function openAddSweetAlert() {
            const html = SweetAlertTheme.getInputHTML([
                { id: 'add_name', label: 'Product Name *', placeholder: 'Enter product name' },
                { id: 'add_category', label: 'Category', placeholder: 'e.g., Electronics, Clothing' },
                { id: 'add_price', label: 'Price (₱) *', placeholder: '0.00', type: 'number', attributes: 'step="0.01" min="0"' },
                { id: 'add_stock', label: 'Stock *', placeholder: '0', type: 'number', attributes: 'min="0"' }
            ]);

            SweetAlertTheme.fire({
                title: 'Add New Product',
                html: html,
                confirmButtonText: 'Add Product',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#7c3aed',
                showCancelButton: true,
                didOpen: () => {
                    document.getElementById('add_name').focus();
                }
            }).then(result => {
                if (result.isConfirmed) {
                    submitAddProduct();
                }
            });
        }

        function submitAddProduct() {
            const name = document.getElementById('add_name').value.trim();
            const category = document.getElementById('add_category').value.trim();
            const price = parseFloat(document.getElementById('add_price').value);
            const stock = parseInt(document.getElementById('add_stock').value);

            if (!name || isNaN(price) || isNaN(stock)) {
                SweetAlertTheme.fire({ title: 'Error', text: 'Please fill in all required fields correctly.', icon: 'error' });
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('name', name);
            formData.append('category', category);
            formData.append('price', price);
            formData.append('stock', stock);

            fetch('manager_inventory.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        SweetAlertTheme.fire({ title: 'Success', text: data.message, icon: 'success' }).then(() => {
                            location.reload();
                        });
                    } else {
                        SweetAlertTheme.fire({ title: 'Error', text: data.message, icon: 'error' });
                    }
                })
                .catch(() => {
                    SweetAlertTheme.fire({ title: 'Error', text: 'An error occurred.', icon: 'error' });
                });
        }

        // Edit Product with SweetAlert
        function openEditSweetAlert(product) {
            const config = SweetAlertTheme.getConfig();
            const html = SweetAlertTheme.getInputHTML([
                { id: 'edit_name', label: 'Product Name *', placeholder: 'Enter product name', value: product.name || '' },
                { id: 'edit_category', label: 'Category', placeholder: 'e.g., Electronics, Clothing', value: product.category || '' },
                { id: 'edit_price', label: 'Price (₱) *', placeholder: '0.00', type: 'number', value: product.price, attributes: 'step="0.01" min="0"' },
                { id: 'edit_stock', label: 'Stock *', placeholder: '0', type: 'number', value: product.stock, attributes: 'min="0"' }
            ]);

            SweetAlertTheme.fire({
                title: 'Edit Product',
                html: html,
                confirmButtonText: 'Save Changes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#7c3aed',
                showCancelButton: true,
                didOpen: () => {
                    document.getElementById('edit_name').focus();
                }
            }).then(result => {
                if (result.isConfirmed) {
                    submitEditProduct(product.id);
                }
            });
        }

        function submitEditProduct(productId) {
            const name = document.getElementById('edit_name').value.trim();
            const category = document.getElementById('edit_category').value.trim();
            const price = parseFloat(document.getElementById('edit_price').value);
            const stock = parseInt(document.getElementById('edit_stock').value);

            if (!name || isNaN(price) || price <= 0 || isNaN(stock) || stock < 0) {
                SweetAlertTheme.fire({ title: 'Error', text: 'Please fill in all required fields correctly (price must be greater than 0).', icon: 'error' });
                return;
            }

            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('product_id', productId);
            formData.append('name', name);
            formData.append('category', category);
            formData.append('price', price);
            formData.append('stock', stock);

            fetch('manager_inventory.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        SweetAlertTheme.fire({ title: 'Success', text: data.message, icon: 'success' }).then(() => {
                            location.reload();
                        });
                    } else {
                        SweetAlertTheme.fire({ title: 'Error', text: data.message, icon: 'error' });
                    }
                })
                .catch(() => {
                    SweetAlertTheme.fire({ title: 'Error', text: 'An error occurred.', icon: 'error' });
                });
        }

        // Delete Product with SweetAlert
        function deleteProductSweetAlert(productId, productName) {
            SweetAlertTheme.fire({
                title: 'Delete Product?',
                text: 'Are you sure you want to delete "' + productName + '"? This action cannot be undone.',
                icon: 'warning',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#ef4444',
                showCancelButton: true,
            }).then(result => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('product_id', productId);

                    fetch('manager_inventory.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                SweetAlertTheme.fire({ title: 'Deleted!', text: data.message, icon: 'success' }).then(() => {
                                    location.reload();
                                });
                            } else {
                                SweetAlertTheme.fire({ title: 'Error', text: data.message, icon: 'error' });
                            }
                        })
                        .catch(() => {
                            SweetAlertTheme.fire({ title: 'Error', text: 'An error occurred.', icon: 'error' });
                        });
                }
            });
        }
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>