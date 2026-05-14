<?php
session_start();
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireRole('manager');

$db = (new Database())->conn;
$msg = '';
$msgType = '';

// Handle reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_action'])) {
    try {
        if ($_POST['reset_action'] === 'clear_transactions') {
            // Delete all transaction items first (foreign key constraint)
            $db->query("DELETE FROM transaction_items");
            // Delete all transactions
            $db->query("DELETE FROM transactions");
            $msg = 'All cashier transactions have been cleared successfully!';
            $msgType = 'success';
        } elseif ($_POST['reset_action'] === 'reset_stock') {
            // Reset all product stock to 100
            $stock_value = intval($_POST['stock_value'] ?? 100);
            $db->prepare("UPDATE products SET stock = ?")->execute([$stock_value]);
            $msg = "All product stock has been reset to $stock_value units!";
            $msgType = 'success';
        } elseif ($_POST['reset_action'] === 'full_reset') {
            // Full reset: clear transactions and reset stock
            $stock_value = intval($_POST['stock_value'] ?? 100);

            $db->query("DELETE FROM transaction_items");
            $db->query("DELETE FROM transactions");
            $db->prepare("UPDATE products SET stock = ?")->execute([$stock_value]);

            $msg = "Full reset complete! All transactions cleared and stock reset to $stock_value units.";
            $msgType = 'success';
        }
    } catch (Exception $e) {
        $msg = 'Error: ' . $e->getMessage();
        $msgType = 'error';
    }
}

// Get current stats
$trans_count = $db->query("SELECT COUNT(*) as count FROM transactions")->fetch(PDO::FETCH_ASSOC)['count'];
$products = $db->query("SELECT COUNT(*) as count, AVG(stock) as avg_stock FROM products")->fetch(PDO::FETCH_ASSOC);
$product_count = $products['count'];
$avg_stock = round($products['avg_stock'], 2);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Cashier Data - Manager</title>
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 10px 40px var(--shadow-medium);
            padding: 30px;
        }

        h1 {
            color: var(--text-primary);
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 14px;
        }

        .stats {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--success-border);
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid var(--danger-border);
        }

        .reset-options {
            display: grid;
            gap: 15px;
            margin-bottom: 30px;
        }

        .reset-option {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reset-option:hover {
            border-color: #0066cc;
            background: var(--bg-tertiary);
        }

        .reset-option input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }

        .reset-option label {
            cursor: pointer;
            display: flex;
            align-items: center;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .reset-option-desc {
            color: var(--text-secondary);
            font-size: 13px;
            margin-left: 25px;
            line-height: 1.4;
        }

        .stock-input-group {
            margin-left: 25px;
            margin-top: 10px;
            display: none;
        }

        .stock-input-group.show {
            display: block;
        }

        .stock-input-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-secondary);
        }

        .stock-input-group input {
            width: 100px;
            padding: 8px;
            border: 1px solid var(--input-border);
            background-color: var(--input-bg);
            color: var(--input-text);
            border-radius: 4px;
            font-size: 14px;
        }

        .form-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 30px;
        }

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset {
            background: var(--danger-text);
            color: white;
        }

        .btn-reset:hover {
            background: var(--danger-border);
            box-shadow: 0 4px 12px var(--shadow-medium);
        }

        .btn-cancel {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-cancel:hover {
            background: var(--bg-tertiary);
        }

        .warning-box {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: var(--warning-text);
        }

        .warning-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔄 Reset Cashier Data</h1>
        <p class="subtitle">Manage cashier system data and transactions</p>

        <?php if ($msg): ?>
            <div class="alert <?php echo $msgType; ?> show">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value"><?php echo $trans_count; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Avg Stock / Product</div>
                <div class="stat-value"><?php echo $avg_stock; ?></div>
            </div>
        </div>

        <div class="warning-box">
            <div class="warning-title">⚠️ Warning</div>
            <div>These actions cannot be undone. Please ensure you have a backup before proceeding.</div>
        </div>

        <form method="POST">
            <div class="reset-options">
                <div class="reset-option">
                    <label>
                        <input type="radio" name="reset_action" value="clear_transactions" checked>
                        Clear Transactions Only
                    </label>
                    <div class="reset-option-desc">
                        Delete all transaction records and items. Product stock levels will remain unchanged.
                    </div>
                </div>

                <div class="reset-option">
                    <label>
                        <input type="radio" name="reset_action" value="reset_stock">
                        Reset Stock Only
                    </label>
                    <div class="reset-option-desc">
                        Reset all product stock to a specified quantity. Transaction records will remain unchanged.
                    </div>
                    <div class="stock-input-group" id="stock-group-2">
                        <label for="stock_value_2">Set all products to:</label>
                        <input type="number" id="stock_value_2" name="stock_value" value="100" min="0">
                    </div>
                </div>

                <div class="reset-option">
                    <label>
                        <input type="radio" name="reset_action" value="full_reset">
                        Full Reset
                    </label>
                    <div class="reset-option-desc">
                        Delete all transactions AND reset product stock. Complete system reset.
                    </div>
                    <div class="stock-input-group" id="stock-group-3">
                        <label for="stock_value_3">Set all products to:</label>
                        <input type="number" id="stock_value_3" name="stock_value" value="100" min="0">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-reset">Confirm Reset</button>
                <button type="button" class="btn-cancel" onclick="window.history.back()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        const radioButtons = document.querySelectorAll('input[name="reset_action"]');
        const stockGroup2 = document.getElementById('stock-group-2');
        const stockGroup3 = document.getElementById('stock-group-3');

        radioButtons.forEach(radio => {
            radio.addEventListener('change', () => {
                stockGroup2.classList.remove('show');
                stockGroup3.classList.remove('show');

                if (radio.value === 'reset_stock') {
                    stockGroup2.classList.add('show');
                } else if (radio.value === 'full_reset') {
                    stockGroup3.classList.add('show');
                }
            });
        });

        // Confirmation before submitting
        document.querySelector('form').addEventListener('submit', (e) => {
            const action = document.querySelector('input[name="reset_action"]:checked').value;
            let confirmMsg = '';

            if (action === 'clear_transactions') {
                confirmMsg = 'Are you sure you want to delete all transaction records? This cannot be undone.';
            } else if (action === 'reset_stock') {
                confirmMsg = 'Are you sure you want to reset all product stock levels? Transaction records will be preserved.';
            } else if (action === 'full_reset') {
                confirmMsg = 'Are you sure you want to perform a FULL RESET? This will delete all transactions and reset stock. This cannot be undone!';
            }

            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    </script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>