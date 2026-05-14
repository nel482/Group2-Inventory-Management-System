/**
 * ASAJ Cashier POS System - Main JavaScript
 * Handles shopping cart, product management, and transactions
 */

let cart = [];
let allProducts = [];
let selectedPaymentMethod = 'cash';

// Initialize shift start time from localStorage or create new one
let shiftStartTime;
const storedShiftTime = localStorage.getItem('shiftStartTime');
if (storedShiftTime) {
    shiftStartTime = new Date(storedShiftTime);
} else {
    shiftStartTime = new Date();
    localStorage.setItem('shiftStartTime', shiftStartTime.toISOString());
}

const API_ENDPOINT = 'cashier-api.php';

// Store interval IDs so we can clear them later
let shiftTimerInterval;
let refreshStatsInterval;

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    updateShiftTime();
    shiftTimerInterval = setInterval(updateShiftTime, 1000);
    updateSessionStats();
    // Update stats every 1.5 seconds (calculated from local transaction history)
    refreshStatsInterval = setInterval(updateSessionStats, 1500);

    const searchInput = document.getElementById('product-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const filtered = allProducts.filter(p =>
                p.name.toLowerCase().includes(query) ||
                (p.category && p.category.toLowerCase().includes(query))
            );
            renderProducts(filtered);
        });
    }
});

async function loadProducts() {
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_products'
        });
        allProducts = await response.json();
        renderProducts(allProducts);
    } catch (error) {
        SweetAlertTheme.fire({ title: 'Error', text: 'Failed to load products', icon: 'error' });
    }
}

function renderProducts(products) {
    const grid = document.getElementById('products-grid');
    if (products.length === 0) {
        grid.innerHTML = '<div class="empty-cart"><div class="empty-cart-icon">📭</div>No products</div>';
        return;
    }
    grid.innerHTML = products.map(p => `
        <div class="product-card slide-in" onclick='promptQuantityAndAddToCart(${p.id}, ${JSON.stringify(p.name)}, ${p.price}, ${p.stock})'>
            <div class="product-icon">📦</div>
            <div class="product-name">${p.name}</div>
            <div class="product-price">₱${parseFloat(p.price).toFixed(2)}</div>
        </div>
    `).join('');
}

async function promptQuantityAndAddToCart(id, name, price, stock) {
    const existing = cart.find(item => item.id === id);
    const availableStock = Math.max(0, parseInt(stock, 10) - (existing ? existing.qty : 0));

    if (availableStock <= 0) {
        SweetAlertTheme.fire({ title: 'Out of Stock', text: `No more ${name} units are available`, icon: 'warning' });
        return;
    }

    const config = SweetAlertTheme.getConfig();
    const result = await SweetAlertTheme.fire({
        title: `Add ${name}`,
        html: `<div style="text-align:left; color: ${config.labelColor};">Available stock: <strong style="color:#10b981;">${availableStock}</strong></div>`,
        input: 'number',
        inputValue: 1,
        inputAttributes: {
            min: 1,
            max: availableStock,
            step: 1
        },
        showCancelButton: true,
        confirmButtonText: 'Add to Cart',
        confirmButtonColor: '#00d9ff',
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        },
        preConfirm: (value) => {
            const qty = parseInt(value, 10);
            if (!Number.isInteger(qty) || qty < 1) {
                Swal.showValidationMessage('Please enter a valid quantity');
                return false;
            }
            if (qty > availableStock) {
                Swal.showValidationMessage(`Only ${availableStock} units available`);
                return false;
            }
            return qty;
        }
    });

    if (result.isConfirmed) {
        addToCart(id, name, price, stock, result.value);
    }
}

function addToCart(id, name, price, stock, quantity = 1) {
    const qtyToAdd = Math.max(1, parseInt(quantity, 10) || 1);
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.qty + qtyToAdd <= stock) {
            existing.qty += qtyToAdd;
            updateCart();
            SweetAlertTheme.fire({
                title: 'Added to Cart',
                text: `${qtyToAdd} x ${name} has been added`,
                icon: 'success',
                toast: true,
                position: 'top-right',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            SweetAlertTheme.fire({ title: 'Out of Stock', text: `Only ${stock} units available`, icon: 'warning' });
        }
    } else {
        if (qtyToAdd > stock) {
            SweetAlertTheme.fire({ title: 'Out of Stock', text: `Only ${stock} units available`, icon: 'warning' });
            return;
        }

        cart.push({ id, name, price, qty: qtyToAdd, stock });
        updateCart();
        SweetAlertTheme.fire({
            title: 'Added to Cart',
            text: `${qtyToAdd} x ${name} has been added`,
            icon: 'success',
            toast: true,
            position: 'top-right',
            timer: 1500,
            showConfirmButton: false
        });
    }
}

function updateCart() {
    const cartItems = document.getElementById('cart-items');
    if (cart.length === 0) {
        cartItems.innerHTML = `<div class="empty-cart"><div class="empty-cart-icon">🛍️</div><div>Cart is empty</div></div>`;
        document.getElementById('cart-count').textContent = '0 items';
        updateSummary();
        return;
    }
    cartItems.innerHTML = cart.map(item => `
        <div class="cart-item slide-in">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
            </div>
            <div class="cart-item-qty">
                <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
                <span>${item.qty}</span>
                <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
            </div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.id})">✕</button>
        </div>
    `).join('');
    document.getElementById('cart-count').textContent = `${cart.length} items`;
    updateSummary();
}

function updateQty(id, change) {
    const item = cart.find(i => i.id === id);
    if (item) {
        const newQty = item.qty + change;
        if (newQty > 0 && newQty <= item.stock) {
            item.qty = newQty;
            updateCart();
        }
    }
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCart();
}

function clearCart() {
    if (cart.length === 0) return;
    SweetAlertTheme.fire({
        title: 'Clear Cart?',
        text: 'Remove all items?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Clear',
        confirmButtonColor: '#ef4444'
    }).then(result => {
        if (result.isConfirmed) {
            cart = [];
            updateCart();
        }
    });
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const tax = subtotal * 0.12;
    const total = subtotal + tax;
    document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
    document.getElementById('checkout-btn').disabled = cart.length === 0;
}

function setPaymentMethod(method) {
    selectedPaymentMethod = method;
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.method === method);
    });
}

async function processCheckout() {
    if (cart.length === 0) {
        SweetAlertTheme.fire({ title: 'Empty Cart', text: 'Please add items', icon: 'warning' });
        return;
    }
    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0) * 1.12;
    const config = SweetAlertTheme.getConfig();
    const { value: proceed } = await SweetAlertTheme.fire({
        title: 'Confirm Transaction',
        html: `<div style="text-align: left;"><p><strong>Items:</strong> ${cart.length}</p><p><strong>Total:</strong> ₱${total.toFixed(2)}</p></div>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Process',
        confirmButtonColor: '#00d9ff'
    });
    if (!proceed) return;
    try {
        const formData = new FormData();
        formData.append('action', 'process_transaction');
        formData.append('payment_method', selectedPaymentMethod);
        formData.append('total', total);
        cart.forEach((item, idx) => {
            formData.append(`items[${idx}][id]`, item.id);
            formData.append(`items[${idx}][qty]`, item.qty);
            formData.append(`items[${idx}][price]`, item.price);
        });
        const response = await fetch(API_ENDPOINT, { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            const tax = subtotal * 0.12;
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-PH', { year: 'numeric', month: '2-digit', day: '2-digit' });
            const timeStr = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

            const itemsHtml = cart.map(item => {
                const itemTotal = (item.price * item.qty).toFixed(2);
                return `<div style="display: flex; justify-content: space-between; margin: 4px 0; font-size: 13px;"><span>${item.name} x${item.qty}</span><span>₱${itemTotal}</span></div>`;
            }).join('');

            const receiptHtml = `
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1a1f3a 100%); color: #fff; padding: 25px; border-radius: 12px; max-width: 400px; margin: 0 auto; border: 1px solid rgba(0, 217, 255, 0.3);">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="font-size: 28px; font-weight: bold; color: #00d9ff; letter-spacing: 2px;">ASAJ</div>
                        <div style="font-size: 12px; color: rgba(255, 255, 255, 0.6);">Receipt</div>
                    </div>
                    
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); border-bottom: 1px solid rgba(255, 255, 255, 0.2); padding: 12px 0; margin-bottom: 15px; font-size: 12px; text-align: center; color: rgba(255, 255, 255, 0.7);">
                        <div>${dateStr}</div>
                        <div>${timeStr}</div>
                        <div style="color: #00d9ff; font-weight: bold; margin-top: 6px;">Txn #${result.transaction_id}</div>
                    </div>
                    
                    <div style="margin-bottom: 15px; font-size: 12px;">
                        ${itemsHtml}
                    </div>
                    
                    <div style="border-top: 1px solid rgba(255, 255, 255, 0.2); padding-top: 12px; margin-bottom: 15px; font-size: 12px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: rgba(255, 255, 255, 0.8);">
                            <span>Subtotal</span>
                            <span>₱${subtotal.toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: rgba(255, 255, 255, 0.8);">
                            <span>Tax (12%)</span>
                            <span>₱${tax.toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; color: #00d9ff;">
                            <span>TOTAL</span>
                            <span>₱${total.toFixed(2)}</span>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.3); border-radius: 6px; padding: 10px; margin-bottom: 12px; font-size: 12px; text-align: center;">
                        <div style="color: rgba(255, 255, 255, 0.7); margin-bottom: 4px;">Payment Method</div>
                        <div style="color: #00d9ff; font-weight: bold;">${selectedPaymentMethod === 'cash' ? '💵 CASH' : '💳 CARD'}</div>
                    </div>
                    
                    <div style="text-align: center;">
                        <div class="receipt-success-label" style="color: #10b981; font-weight: bold; font-size: 16px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.7px; opacity: 0;">✓ PAYMENT SUCCESSFUL</div>
                        <div style="font-size: 12px; color: rgba(255, 255, 255, 0.7);">Thank you for your purchase!</div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: '📄 Receipt',
                html: receiptHtml,
                icon: 'success',
                confirmButtonColor: '#00d9ff',
                confirmButtonText: 'Done',
                customClass: {
                    popup: 'swal2-receipt-success'
                },
                showClass: {
                    popup: 'swal2-receipt-show'
                },
                hideClass: {
                    popup: 'swal2-receipt-hide'
                },
                didOpen: () => {
                    const swalPopup = document.querySelector('.swal2-popup');
                    const config = SweetAlertTheme.getConfig();
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalPopup) {
                        swalPopup.style.background = config.background;
                        swalPopup.style.boxShadow = '0 8px 32px rgba(0, 217, 255, 0.2)';
                        swalPopup.style.color = config.color;
                    }
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10000';
                    }
                }
            }).then(() => {
                saveTransaction(result.transaction_id, cart, total, selectedPaymentMethod);
                cart = [];
                updateCart();
                loadProducts();
                updateSessionStats();
            });
        } else {
            SweetAlertTheme.fire({ title: 'Error', text: result.error || 'Failed', icon: 'error' });
        }
    } catch (error) {
        SweetAlertTheme.fire({ title: 'Error', text: 'Transaction failed', icon: 'error' });
    }
}

function updateShiftTime() {
    const now = new Date();
    const elapsedMs = now - shiftStartTime;
    const totalSeconds = Math.floor(elapsedMs / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const el = document.getElementById('shift-time');
    if (el) {
        el.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
}

function voidTransaction() {
    if (transactionHistory.length === 0) {
        SweetAlertTheme.fire({
            title: 'No Transactions',
            text: 'No transactions available to void',
            icon: 'info',
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10000';
                }
            }
        });
        return;
    }

    // Create HTML for transaction list
    let transactionsList = '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';
    transactionHistory.forEach((trans, index) => {
        const recordDate = trans.date ? new Date(trans.date) : new Date(trans.timestamp || Date.now());
        const date = isNaN(recordDate.getTime()) ? (trans.timestamp || 'Unknown date') : recordDate.toLocaleDateString();
        const time = isNaN(recordDate.getTime()) ? '' : recordDate.toLocaleTimeString();
        transactionsList += `
            <div style="padding: 14px; margin-bottom: 10px; background: rgba(15, 23, 42, 0.85); border-radius: 10px; cursor: pointer; border: 1px solid rgba(0, 217, 255, 0.18);" onclick="selectTransactionToVoid(${index})">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                    <div style="font-weight: 700; color: #ffffff; font-size: 14px;">Transaction #${trans.id}</div>
                    <div style="font-size: 11px; color: rgba(255, 255, 255, 0.75);">${date} ${time}</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 13px; color: rgba(255, 255, 255, 0.9);">
                    <span>${trans.items.length} items</span>
                    <span style="font-weight: 700; color: #00d9ff;">₱${trans.total.toFixed(2)}</span>
                </div>
            </div>
        `;
    });
    transactionsList += '</div>';

    const config = SweetAlertTheme.getConfig();
    SweetAlertTheme.fire({
        title: 'Void/Return Transaction',
        html: '<div style="text-align: left;"><p style="margin-bottom: 12px; color: ' + config.labelColor + ';">Select a transaction to void:</p>' + transactionsList + '</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Close',
        confirmButtonColor: '#ef4444',
        allowOutsideClick: false,
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        }
    });
}

function selectTransactionToVoid(index) {
    const trans = transactionHistory[index];

    SweetAlertTheme.fire({
        title: 'Confirm Void',
        html: `<div style="text-align: left; font-size: 13px; background: rgba(127, 29, 29, 0.42); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 12px; box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);">
            <p><strong>Transaction #${trans.id}</strong></p>
            <p>Total: <strong>₱${trans.total.toFixed(2)}</strong></p>
            <p>Items: <strong>${trans.items.length}</strong></p>
            <p style="color: #ff6b6b; margin-top: 12px;">⚠️ This action will void the transaction and restore stock.</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Void Transaction',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        }
    }).then(result => {
        if (result.isConfirmed) {
            // Send void request to server
            fetch('cashier-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=void_transaction&transaction_id=${trans.id}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mark transaction as voided instead of removing
                        transactionHistory[index].status = 'voided';
                        transactionHistory[index].voidedAt = new Date().toLocaleString();
                        localStorage.setItem('cashierHistory', JSON.stringify(transactionHistory));

                        SweetAlertTheme.fire({
                            title: 'Voided',
                            text: 'Transaction has been voided successfully',
                            icon: 'success',
                            customClass: {
                                popup: 'swal2-receipt-success'
                            },
                            showClass: {
                                popup: 'swal2-receipt-show'
                            },
                            hideClass: {
                                popup: 'swal2-receipt-hide'
                            },
                            didOpen: () => {
                                const swalContainer = document.querySelector('.swal2-container');
                                if (swalContainer) {
                                    swalContainer.style.zIndex = '10000';
                                }
                            }
                        });

                        refreshStats();
                        updateSessionStats();
                    } else {
                        SweetAlertTheme.fire({
                            title: 'Error',
                            text: data.error || 'Failed to void transaction',
                            icon: 'error',
                            didOpen: () => {
                                const swalContainer = document.querySelector('.swal2-container');
                                if (swalContainer) {
                                    swalContainer.style.zIndex = '10000';
                                }
                            }
                        });
                    }
                })
                .catch(err => {
                    SweetAlertTheme.fire({
                        title: 'Error',
                        text: 'Network error: ' + err.message,
                        icon: 'error',
                        didOpen: () => {
                            const swalContainer = document.querySelector('.swal2-container');
                            if (swalContainer) {
                                swalContainer.style.zIndex = '10000';
                            }
                        }
                    });
                });
        } else {
            voidTransaction();
        }
    });
}

function viewSalesLog() {
    const totalSalesEl = document.getElementById('total-sales-value');
    const transactionCountEl = document.getElementById('transaction-count-value');
    const totalSales = totalSalesEl ? totalSalesEl.textContent : '₱0.00';
    const transactionCount = transactionCountEl ? transactionCountEl.textContent : '0';

    // Calculate cashier's stats
    const totalRevenue = transactionHistory.reduce((sum, tx) => sum + (tx.status !== 'voided' ? tx.total : 0), 0);
    const totalItems = transactionHistory.reduce((sum, tx) => sum + (tx.status !== 'voided' ? tx.items.length : 0), 0);
    const completedTransactions = transactionHistory.filter(tx => tx.status !== 'voided').length;
    const voidedTransactions = transactionHistory.filter(tx => tx.status === 'voided').length;

    // Create transaction list HTML
    let transactionsList = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
    transactionHistory.forEach((trans) => {
        const recordDate = trans.date ? new Date(trans.date) : new Date(trans.timestamp || Date.now());
        const date = isNaN(recordDate.getTime()) ? (trans.timestamp || 'Unknown date') : recordDate.toLocaleDateString();
        const time = isNaN(recordDate.getTime()) ? '' : recordDate.toLocaleTimeString();
        const statusColor = trans.status === 'voided' ? '#ef4444' : '#10b981';
        const statusText = trans.status === 'voided' ? 'VOIDED' : 'COMPLETED';
        transactionsList += `
            <div style="padding: 10px; margin-bottom: 8px; background: ${trans.status === 'voided' ? 'rgba(127, 29, 29, 0.55)' : 'rgba(6, 95, 70, 0.55)'}; border-left: 3px solid ${statusColor}; border-radius: 6px; border: 1px solid ${trans.status === 'voided' ? 'rgba(239, 68, 68, 0.45)' : 'rgba(16, 185, 129, 0.45)'}; box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: bold; color: #00d9ff;">Txn #${trans.id}</span>
                    <span style="font-size: 11px; background: ${statusColor}; color: white; padding: 2px 6px; border-radius: 3px;">${statusText}</span>
                </div>
                <div style="font-size: 12px; color: #ccc; margin-bottom: 4px;">${date} ${time}</div>
                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                    <span>${trans.items.length} items</span>
                    <span style="color: ${trans.status === 'voided' ? '#ef4444' : '#10b981'}; font-weight: bold;">₱${trans.total.toFixed(2)}</span>
                </div>
            </div>
        `;
    });
    transactionsList += '</div>';

    const htmlContent = `
        <div style="text-align: left; padding: 10px; background: rgba(15, 23, 42, 0.6); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.18);">
            <div style="background: linear-gradient(135deg, rgba(6, 95, 70, 0.5), rgba(16, 185, 129, 0.22)); border-left: 3px solid #10b981; padding: 12px; border-radius: 6px; margin-bottom: 16px; box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Total Sales</div>
                        <div style="font-size: 18px; font-weight: bold; color: #10b981;">₱${totalRevenue.toFixed(2)}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Transactions</div>
                        <div style="font-size: 18px; font-weight: bold; color: #00d9ff;">${completedTransactions}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Items Sold</div>
                        <div style="font-size: 16px; font-weight: bold; color: #7c3aed;">${totalItems}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #888; text-transform: uppercase;">Voided</div>
                        <div style="font-size: 16px; font-weight: bold; color: #ef4444;">${voidedTransactions}</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: bold; color: #00d9ff; margin-bottom: 8px;">📋 Today's Activity:</div>
                ${transactionsList}
            </div>
        </div>
    `;

    Swal.fire({
        title: '📊 Cashier Sales Summary',
        html: htmlContent,
        icon: 'info',
        width: '600px',
        confirmButtonColor: '#00d9ff',
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        }
    });
}

function updateAvgTransaction() {
    const totalSalesEl = document.getElementById('total-sales-value');
    const transactionCountEl = document.getElementById('transaction-count-value');
    if (totalSalesEl && transactionCountEl) {
        const totalSales = parseFloat(totalSalesEl.textContent.replace('₱', '').replace(/,/g, '')) || 0;
        const transactionCount = parseInt(transactionCountEl.textContent) || 0;
        if (transactionCount > 0) {
            const avg = totalSales / transactionCount;
            const avgEl = document.getElementById('avg-trans');
            if (avgEl) avgEl.textContent = `₱${avg.toFixed(2)}`;
        }
    }
}

function refreshStats() {
    updateSessionStats();
}

// ============================================
// END SHIFT FUNCTIONALITY
// ============================================
async function endShift() {
    // Get current shift data
    const totalSalesEl = document.getElementById('total-sales-value');
    const transactionCountEl = document.getElementById('transaction-count-value');
    const shiftTimeEl = document.getElementById('shift-time');

    const totalSales = parseFloat(totalSalesEl.textContent.replace('₱', '').replace(/,/g, '')) || 0;
    const transactionCount = parseInt(transactionCountEl.textContent) || 0;
    const shiftDuration = shiftTimeEl.textContent || '00:00:00';

    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });

    // Show confirmation dialog
    const config = SweetAlertTheme.getConfig();
    const result = await SweetAlertTheme.fire({
        title: '🛑 End Shift Confirmation',
        html: `
            <div style="text-align: left; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(0, 217, 255, 0.2); border-radius: 12px; padding: 16px; margin: 12px 0;">
                <div style="margin-bottom: 12px;">
                    <div style="font-size: 12px; color: rgba(255, 255, 255, 0.6); text-transform: uppercase; margin-bottom: 4px;">Shift Summary</div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 8px; border-radius: 6px;">
                            <div style="font-size: 11px; color: rgba(255, 255, 255, 0.6);">Total Sales</div>
                            <div style="font-size: 16px; font-weight: bold; color: #10b981;">₱${totalSales.toFixed(2)}</div>
                        </div>
                        <div style="background: rgba(0, 217, 255, 0.1); border: 1px solid rgba(0, 217, 255, 0.3); padding: 8px; border-radius: 6px;">
                            <div style="font-size: 11px; color: rgba(255, 255, 255, 0.6);">Transactions</div>
                            <div style="font-size: 16px; font-weight: bold; color: #00d9ff;">${transactionCount}</div>
                        </div>
                        <div style="background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.3); padding: 8px; border-radius: 6px;">
                            <div style="font-size: 11px; color: rgba(255, 255, 255, 0.6);">Shift Duration</div>
                            <div style="font-size: 16px; font-weight: bold; color: #7c3aed;">${shiftDuration}</div>
                        </div>
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); padding: 8px; border-radius: 6px;">
                            <div style="font-size: 11px; color: rgba(255, 255, 255, 0.6);">End Time</div>
                            <div style="font-size: 16px; font-weight: bold; color: #f59e0b;">${timeStr}</div>
                        </div>
                    </div>
                </div>
                <div style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; border-radius: 6px; padding: 10px; margin-top: 12px;">
                    <div style="font-size: 12px; color: #ff6b6b; font-weight: 600;">⚠️ Warning</div>
                    <div style="font-size: 12px; color: rgba(255, 255, 255, 0.8); margin-top: 6px;">This will reset all session statistics and clear your cart. Completed transactions will be saved.</div>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '✓ End Shift',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        allowOutsideClick: false,
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        }
    });

    if (!result.isConfirmed) {
        return; // User cancelled
    }

    try {
        // Show loading state
        SweetAlertTheme.fire({
            title: 'Processing Shift End...',
            html: '<div style="color: rgba(255, 255, 255, 0.8);">Saving shift data and resetting session...</div>',
            icon: 'info',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10000';
                }
            }
        });

        // Send shift completion data to API
        const formData = new FormData();
        formData.append('action', 'end_shift');
        // Convert to MySQL datetime format (YYYY-MM-DD HH:MM:SS)
        const shiftStartDate = new Date(shiftStartTime);
        const year = shiftStartDate.getFullYear();
        const month = String(shiftStartDate.getMonth() + 1).padStart(2, '0');
        const day = String(shiftStartDate.getDate()).padStart(2, '0');
        const hours = String(shiftStartDate.getHours()).padStart(2, '0');
        const minutes = String(shiftStartDate.getMinutes()).padStart(2, '0');
        const seconds = String(shiftStartDate.getSeconds()).padStart(2, '0');
        const mysqlDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        formData.append('shift_start', mysqlDateTime);
        formData.append('total_sales', totalSales);
        formData.append('transaction_count', transactionCount);
        formData.append('shift_duration', shiftDuration);

        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Reset all session-based statistics
            resetShiftData();

            // Show success summary
            SweetAlertTheme.fire({
                title: '✓ Shift Ended Successfully',
                html: `
                    <div style="text-align: left; background: rgba(6, 95, 70, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 16px; margin: 12px 0;">
                        <div style="margin-bottom: 12px;">
                            <div style="font-size: 12px; color: rgba(255, 255, 255, 0.6); text-transform: uppercase; margin-bottom: 8px;">Final Shift Report</div>
                            <div style="display: grid; gap: 8px;">
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 6px;">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Total Sales:</span>
                                    <span style="color: #10b981; font-weight: bold;">₱${totalSales.toFixed(2)}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 6px;">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Transactions:</span>
                                    <span style="color: #00d9ff; font-weight: bold;">${transactionCount}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 6px;">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Shift Duration:</span>
                                    <span style="color: #7c3aed; font-weight: bold;">${shiftDuration}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: rgba(255, 255, 255, 0.8);">Average Transaction:</span>
                                    <span style="color: #00d9ff; font-weight: bold;">₱${(transactionCount > 0 ? totalSales / transactionCount : 0).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; border-radius: 6px; padding: 10px; margin-top: 12px;">
                            <div style="font-size: 12px; color: #10b981; font-weight: 600;">✓ Shift data saved to database</div>
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'Return to Terminal',
                confirmButtonColor: '#10b981',
                allowOutsideClick: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10000';
                    }
                }
            });
        } else {
            throw new Error(result.error || 'Failed to end shift');
        }
    } catch (error) {
        SweetAlertTheme.fire({
            title: 'Error',
            text: 'Failed to end shift: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#ef4444',
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10000';
                }
            }
        });
    }
}

// Reset all shift-based data (session statistics, cart, timer)
function resetShiftData() {
    // Stop the auto-refresh intervals to prevent stats from being overwritten
    if (shiftTimerInterval) clearInterval(shiftTimerInterval);
    if (refreshStatsInterval) clearInterval(refreshStatsInterval);

    // Reset stats to default values
    const totalSalesEl = document.getElementById('total-sales-value');
    const transactionCountEl = document.getElementById('transaction-count-value');
    const shiftTimeEl = document.getElementById('shift-time');
    const avgTransEl = document.getElementById('avg-trans');

    if (totalSalesEl) totalSalesEl.textContent = '₱0.00';
    if (transactionCountEl) transactionCountEl.textContent = '0';
    if (shiftTimeEl) shiftTimeEl.textContent = '00:00:00';
    if (avgTransEl) avgTransEl.textContent = '₱0.00';

    // Clear the shopping cart
    cart = [];
    updateCart();

    // Reset payment method
    selectedPaymentMethod = 'cash';
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.method === 'cash');
    });

    // Reset timer to new shift start time
    shiftStartTime = new Date();
    localStorage.setItem('shiftStartTime', shiftStartTime.toISOString());

    // Clear transaction history for UI display
    transactionHistory = [];
    localStorage.setItem('cashierHistory', JSON.stringify(transactionHistory));

    // Reload products
    loadProducts();
}

// Session-based Stats Calculation (from local transaction history, not database)
function updateSessionStats() {
    // Calculate stats from transactionHistory (stored in localStorage)
    const completedTransactions = transactionHistory.filter(tx => tx.status !== 'voided');
    const totalSales = completedTransactions.reduce((sum, tx) => sum + tx.total, 0);
    const transactionCount = completedTransactions.length;

    // Update UI with calculated stats
    const totalSalesEl = document.getElementById('total-sales-value');
    const transactionCountEl = document.getElementById('transaction-count-value');
    const avgTransEl = document.getElementById('avg-trans');

    if (totalSalesEl) {
        totalSalesEl.textContent = `₱${totalSales.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    if (transactionCountEl) {
        transactionCountEl.textContent = transactionCount;
    }

    if (avgTransEl) {
        const avgTransaction = transactionCount > 0 ? totalSales / transactionCount : 0;
        avgTransEl.textContent = `₱${avgTransaction.toFixed(2)}`;
    }
}

// Transaction History Functions
let transactionHistory = JSON.parse(localStorage.getItem('cashierHistory')) || [];

function saveTransaction(transactionId, items, total, paymentMethod) {
    const now = new Date();
    const transaction = {
        id: transactionId,
        items: items,
        total: total,
        paymentMethod: paymentMethod,
        date: now.toISOString(),
        timestamp: now.toLocaleString('en-PH'),
        itemCount: items.length
    };
    transactionHistory.unshift(transaction);
    localStorage.setItem('cashierHistory', JSON.stringify(transactionHistory));
}

function viewTransactionHistory() {
    console.log('Opening history modal, transactions:', transactionHistory.length);

    // Remove existing modal if any
    const existingModal = document.getElementById('historyModal');
    if (existingModal) existingModal.remove();

    const itemsHTML = transactionHistory.length === 0 ?
        '<div class="history-empty"><div class="history-empty-icon">📭</div><div>No transactions yet</div></div>' :
        transactionHistory.map(tx => `
            <div class="history-item" style="${tx.status === 'voided' ? 'opacity: 0.6; background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;' : ''}">
                <div class="history-item-info">
                    <div class="history-item-id" style="${tx.status === 'voided' ? 'text-decoration: line-through;' : ''}">
                        Transaction #${tx.id}
                        ${tx.status === 'voided' ? '<span style="margin-left: 8px; color: #ef4444; font-weight: bold; text-decoration: none;">VOIDED</span>' : ''}
                    </div>
                    <div class="history-item-time">${tx.timestamp}${tx.voidedAt ? `<div style="color: #ef4444; font-size: 11px; margin-top: 4px;">Voided: ${tx.voidedAt}</div>` : ''}</div>
                </div>
                <div class="history-item-amount">
                    <div class="history-item-total" style="${tx.status === 'voided' ? 'color: #ef4444;' : ''}">₱${parseFloat(tx.total).toFixed(2)}</div>
                    <div class="history-item-method">${tx.paymentMethod === 'cash' ? '💵 Cash' : '💳 Card'}</div>
                </div>
            </div>
        `).join('');

    const modalHTML = `
        <div class="history-modal show" id="historyModal" style="display: flex;" onclick="if(event.target.id === 'historyModal') closeHistoryModal()">
            <div class="history-content">
                <div class="history-header">
                    <h2>📜 Transaction History</h2>
                    <button class="history-close" onclick="closeHistoryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #fff;">✕</button>
                </div>
                <div class="history-body" id="historyBody" style="flex: 1; overflow-y: auto; padding: 15px;">
                    ${itemsHTML}
                </div>
                <div class="history-footer">
                    <button class="history-btn" onclick="clearHistoryData()">Clear History</button>
                    <button class="history-btn history-btn-primary" onclick="closeHistoryModal()">Done</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    console.log('Modal added to DOM');
}

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.remove();
}

function clearHistoryData() {
    if (transactionHistory.length === 0) {
        Swal.fire({
            title: 'Empty',
            text: 'No history to clear',
            icon: 'info',
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '10000';
                }
            }
        });
        return;
    }

    Swal.fire({
        title: 'Clear History?',
        text: 'Are you sure you want to clear all transaction history? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Clear History',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444',
        didOpen: () => {
            const swalContainer = document.querySelector('.swal2-container');
            if (swalContainer) {
                swalContainer.style.zIndex = '10000';
            }
        }
    }).then(result => {
        if (result.isConfirmed) {
            transactionHistory = [];
            localStorage.setItem('cashierHistory', JSON.stringify(transactionHistory));
            const historyBody = document.getElementById('historyBody');
            if (historyBody) {
                historyBody.innerHTML = '<div class="history-empty"><div class="history-empty-icon">📭</div><div>No transactions yet</div></div>';
            }
            Swal.fire({
                title: 'Cleared',
                text: 'History cleared successfully',
                icon: 'success',
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '10000';
                    }
                }
            });
        }
    });
}