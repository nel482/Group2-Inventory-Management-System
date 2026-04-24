<?php
session_start();
require_once 'auth.php';
$auth = new Auth();
$auth->requireRole('manager');
$user = $auth->currentUser();

require_once 'database.php';
$db = (new Database())->conn;

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name     = trim($_POST['name']);
        $category = trim($_POST['category']);
        $price    = floatval($_POST['price']);
        $stock    = intval($_POST['stock']);
        if ($name) {
            $db->prepare("INSERT INTO products (name, category, price, stock) VALUES (?, ?, ?, ?)")
               ->execute([$name, $category, $price, $stock]);
            $msg = 'Product added.';
        }
    }
    if ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([intval($_POST['product_id'])]);
        $msg = 'Product deleted.';
    }
    if ($_POST['action'] === 'edit') {
        $stmt = $db->prepare("UPDATE products SET name=?, category=?, price=?, stock=? WHERE id=?");
        $stmt->execute([trim($_POST['name']), trim($_POST['category']), floatval($_POST['price']), intval($_POST['stock']), intval($_POST['product_id'])]);
        $msg = 'Product updated.';
    }
}

$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$baseCSS      = file_get_contents(__DIR__ . '/css/base.css');
$dashboardCSS = file_get_contents(__DIR__ . '/css/dashboard.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory</title>
    <style><?= $baseCSS ?></style>
    <style><?= $dashboardCSS ?></style>
    <style>
        .msg { padding:10px 14px; background:#eaf7ef; border:1px solid #b2dfcc; color:#27ae60; border-radius:4px; margin-bottom:16px; font-size:13px; }
        .add-form { background:#fff; border:1px solid #e0e0e0; border-radius:6px; padding:20px; margin-bottom:24px; }
        .add-form h3 { font-size:14px; font-weight:600; margin-bottom:14px; color:#333; }
        .form-row { display:flex; gap:10px; flex-wrap:wrap; }
        .form-row input { flex:1; min-width:120px; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:13px; }
        .form-row button { padding:8px 18px; background:#4a90e2; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px; }
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid #e0e0e0; border-radius:6px; font-size:13px; }
        th { background:#f5f7fa; padding:10px 14px; text-align:left; font-size:12px; color:#777; border-bottom:1px solid #e0e0e0; }
        td { padding:10px 14px; border-bottom:1px solid #f5f5f5; color:#444; }
        tr:last-child td { border-bottom:none; }
        .btn-del { background:none; border:none; color:#e74c3c; cursor:pointer; font-size:12px; }
        .btn-edit { background:none; border:none; color:#4a90e2; cursor:pointer; font-size:12px; margin-right:8px; }
        .low { color:#e74c3c; font-weight:600; }
        .modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:100; align-items:center; justify-content:center; }
        .modal-bg.open { display:flex; }
        .modal { background:#fff; border-radius:6px; padding:28px; width:100%; max-width:380px; }
        .modal h3 { font-size:14px; font-weight:600; margin-bottom:14px; }
        .modal input { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:13px; margin-bottom:10px; box-sizing:border-box; }
        .modal-btns { display:flex; gap:10px; justify-content:flex-end; margin-top:4px; }
        .modal-btns button { padding:8px 18px; border-radius:4px; border:none; cursor:pointer; font-size:13px; }
        .btn-save { background:#4a90e2; color:#fff; }
        .btn-cancel { background:#f0f0f0; color:#555; }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-brand">POS System <span>Manager</span></div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a class="nav-item" href="manager_dashboard.php">Dashboard</a>
            <div class="nav-label">Management</div>
            <a class="nav-item" href="manager_sales.php">Sales Reports</a>
            <a class="nav-item active" href="manager_inventory.php">Inventory</a>
            <a class="nav-item" href="manager_staff.php">Staff Accounts</a>
            <a class="nav-item" href="manager_audit.php">Audit Logs</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role">Manager</div>
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </aside>

    <main class="main">
        <div class="page-title">Inventory</div>
        <div class="page-subtitle">Manage your products and stock</div>

        <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div class="add-form">
            <h3>Add New Product</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Product name" required>
                    <input type="text" name="category" placeholder="Category">
                    <input type="number" name="price" placeholder="Price" step="0.01" min="0" required>
                    <input type="number" name="stock" placeholder="Stock" min="0" required>
                    <button type="submit">Add</button>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products): ?>
                <?php foreach ($products as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['category']) ?></td>
                    <td>₱<?= number_format($p['price'], 2) ?></td>
                    <td class="<?= $p['stock'] < 10 ? 'low' : '' ?>">
                        <?= $p['stock'] ?><?= $p['stock'] < 10 ? ' ⚠' : '' ?>
                    </td>
                    <td>
                        <button class="btn-edit" onclick='openEdit(<?= json_encode($p) ?>)'>Edit</button>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn-del">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px">No products yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<!-- Edit Modal -->
<div class="modal-bg" id="editModal">
    <div class="modal">
        <h3>Edit Product</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="product_id" id="edit_id">
            <input type="text" name="name" id="edit_name" placeholder="Product name" required>
            <input type="text" name="category" id="edit_category" placeholder="Category">
            <input type="number" name="price" id="edit_price" placeholder="Price" step="0.01" min="0" required>
            <input type="number" name="stock" id="edit_stock" placeholder="Stock" min="0" required>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeEdit()">Cancel</button>
                <button type="submit" class="btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(p) {
    document.getElementById('edit_id').value       = p.id;
    document.getElementById('edit_name').value     = p.name;
    document.getElementById('edit_category').value = p.category;
    document.getElementById('edit_price').value    = p.price;
    document.getElementById('edit_stock').value    = p.stock;
    document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('open');
}
</script>
</body>
</html>
