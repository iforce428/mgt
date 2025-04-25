<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view order details.']);
}

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone_number as phone
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<script>window.location.href = "order_history.php";</script>';
    exit;
}

// Check if order can be modified
$delivery_date = new DateTime($order['delivery_date']);
$today = new DateTime();
$days_until_delivery = $today->diff($delivery_date)->days;
$can_modify = $days_until_delivery > 1 && $order['status'] === 'Pending'; // Can modify if more than 1 day before delivery AND status is Pending

// Handle order cancellation
if (isset($_POST['cancel_order']) && $can_modify) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        echo '<script>window.location.href = "order_history.php?message=Order cancelled successfully";</script>';
        exit;
    } catch (PDOException $e) {
        $error_message = "Error cancelling order: " . $e->getMessage();
    }
}

// Handle order update
if (isset($_POST['update_order']) && $can_modify) {
    try {
        $pdo->beginTransaction();
        
        // Update order details
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET delivery_date = ?,
                delivery_time = ?,
                delivery_location = ?,
                event_type = ?,
                total_pax = ?,
                total_amount = ?,
                staff_notes = ?
            WHERE order_id = ?
        ");
        
        $stmt->execute([
            $_POST['delivery_date'],
            $_POST['delivery_time'],
            $_POST['delivery_location'],
            $_POST['event_type'],
            $_POST['total_pax'],
            $_POST['total_amount'],
            $_POST['staff_notes'],
            $order_id
        ]);
        
        // Delete existing order items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // Insert new order items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, item_id, quantity, price_at_order)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($_POST['items'] as $item) {
                $stmt->execute([
                    $order_id,
                    $item['item_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
        }
        
        $pdo->commit();
        echo '<script>window.location.href = "order_detail.php?id=' . $order_id . '&message=Order updated successfully";</script>';
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error updating order: " . $e->getMessage();
    }
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, mi.name, mi.image_url, oi.price_at_order as price
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

// Fetch available menu items for editing
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE is_available = 1");
$stmt->execute();
$menu_items = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-5 fw-bold mb-0">Butiran Pesanan</h1>
                <a href="order_history.php" class="btn btn-outline-warning">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Order Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Status Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?php 
                            echo match($order['status']) {
                                'Pending' => 'warning',
                                'Confirmed' => 'info',
                                'Preparing' => 'primary',
                                'Ready' => 'success',
                                'Delivered' => 'success',
                                'Completed' => 'success',
                                'Cancelled' => 'danger',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                        <?php if ($can_modify): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <button type="submit" name="cancel_order" class="btn btn-danger">
                                    <i class="bi bi-x-circle"></i> Batalkan Pesanan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Butiran Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php if ($can_modify): ?>
                        <form method="POST" id="editOrderForm">
                            <input type="hidden" name="update_order" value="1">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="delivery_date" class="form-label">Tarikh Penghantaran</label>
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                           value="<?php echo htmlspecialchars($order['delivery_date']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="delivery_time" class="form-label">Masa Penghantaran</label>
                                    <input type="time" class="form-control" id="delivery_time" name="delivery_time" 
                                           value="<?php echo htmlspecialchars($order['delivery_time']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="delivery_location" class="form-label">Alamat Penghantaran</label>
                                <textarea class="form-control" id="delivery_location" name="delivery_location" 
                                          rows="2" required><?php echo htmlspecialchars($order['delivery_location']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="event_type" class="form-label">Jenis Acara</label>
                                <select class="form-select" id="event_type" name="event_type" required>
                                    <option value="Corporate" <?php echo $order['event_type'] === 'Corporate' ? 'selected' : ''; ?>>Corporate</option>
                                    <option value="Social" <?php echo $order['event_type'] === 'Social' ? 'selected' : ''; ?>>Social</option>
                                    <option value="Wedding" <?php echo $order['event_type'] === 'Wedding' ? 'selected' : ''; ?>>Wedding</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="total_pax" class="form-label">Jumlah Pax</label>
                                <input type="number" class="form-control" id="total_pax" name="total_pax" 
                                       value="<?php echo htmlspecialchars($order['total_pax']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="total_amount" class="form-label">Jumlah Harga</label>
                                <div class="input-group">
                                    <span class="input-group-text">RM</span>
                                    <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" 
                                           value="<?php echo htmlspecialchars($order['total_amount']); ?>" required readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="staff_notes" class="form-label">Nota</label>
                                <textarea class="form-control" id="staff_notes" name="staff_notes" 
                                          rows="2"><?php echo htmlspecialchars($order['staff_notes']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Item Pesanan</label>
                                <div id="orderItems">
                                    <?php foreach ($order_items as $item): ?>
                                        <div class="card mb-2">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <select class="form-select" name="items[<?php echo $item['order_item_id']; ?>][item_id]" required>
                                                            <?php foreach ($menu_items as $menu_item): ?>
                                                                <option value="<?php echo $menu_item['item_id']; ?>" 
                                                                        <?php echo $item['item_id'] === $menu_item['item_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($menu_item['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="number" class="form-control" name="items[<?php echo $item['order_item_id']; ?>][quantity]" 
                                                               value="<?php echo $item['quantity']; ?>" placeholder="Quantity" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="text" class="form-control price-input" name="items[<?php echo $item['order_item_id']; ?>][price]" 
                                                               value="<?php echo $item['price']; ?>" placeholder="Price" required readonly style="background-color: #f8f9fa;">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-danger remove-item">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-success mt-2" id="addItem">
                                    <i class="bi bi-plus-circle"></i> Tambah Item
                                </button>
                            </div>

                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Jumlah Semua:</h5>
                                        <h4 class="mb-0 text-warning" id="totalDisplay">RM 0.00</h4>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tarikh Penghantaran:</strong> <?php echo htmlspecialchars($order['delivery_date']); ?></p>
                                <p><strong>Masa Penghantaran:</strong> <?php echo htmlspecialchars($order['delivery_time']); ?></p>
                                <p><strong>Alamat Penghantaran:</strong> <?php echo htmlspecialchars($order['delivery_location']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Jenis Acara:</strong> <?php echo htmlspecialchars($order['event_type']); ?></p>
                                <p><strong>Jumlah Pax:</strong> <?php echo htmlspecialchars($order['total_pax']); ?></p>
                                <p><strong>Jumlah Harga:</strong> RM <?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        <?php if ($order['staff_notes']): ?>
                            <div class="mt-3">
                                <p><strong>Nota:</strong> <?php echo htmlspecialchars($order['staff_notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Item Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kuantiti</th>
                                    <th>Harga Seunit</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                        <td>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Jumlah Semua:</strong></td>
                                    <td><strong class="text-warning">RM <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    const addItemBtn = document.getElementById('addItem');
    const orderItems = document.getElementById('orderItems');
    const menuItems = <?php echo json_encode($menu_items); ?>.map(item => ({
        ...item,
        price_per_pax: parseFloat(item.price_per_pax)
    }));
    const totalAmountInput = document.getElementById('total_amount');
    const totalDisplay = document.getElementById('totalDisplay');
    
    console.log('Menu items:', menuItems);
    
    // Function to update total amount
    function updateTotalAmount() {
        let total = 0;
        document.querySelectorAll('#orderItems .card').forEach(card => {
            const quantity = parseFloat(card.querySelector('input[name$="[quantity]"]').value) || 0;
            const price = parseFloat(card.querySelector('input[name$="[price]"]').value) || 0;
            total += quantity * price;
        });
        if (totalAmountInput) {
            totalAmountInput.value = total.toFixed(2);
        }
        if (totalDisplay) {
            totalDisplay.textContent = `RM ${total.toFixed(2)}`;
        }
    }
    
    // Function to update price when item is selected
    function updateItemPrice(selectElement) {
        const selectedItemId = selectElement.value;
        const menuItem = menuItems.find(item => item.item_id == selectedItemId);
        if (menuItem) {
            const priceInput = selectElement.closest('.row').querySelector('input[name$="[price]"]');
            if (priceInput) {
                priceInput.value = menuItem.price_per_pax.toFixed(2);
                updateTotalAmount();
            }
        }
    }
    
    // Function to create new item HTML
    function createNewItemHtml(itemId) {
        return `
            <div class="card mb-2">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-select item-select" name="items[${itemId}][item_id]" required>
                                <option value="">Pilih Item</option>
                                ${menuItems.map(item => `
                                    <option value="${item.item_id}" data-price="${item.price_per_pax}">
                                        ${item.name} (RM ${item.price_per_pax.toFixed(2)})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control quantity-input" name="items[${itemId}][quantity]" 
                                   placeholder="Kuantiti" required min="1" value="1">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control price-input" name="items[${itemId}][price]" 
                                   placeholder="Harga" required readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Add new item
    if (addItemBtn) {
        console.log('Add item button found');
        addItemBtn.addEventListener('click', function(e) {
            console.log('Add item button clicked');
            e.preventDefault();
            const itemId = Date.now();
            const newItemHtml = createNewItemHtml(itemId);
            
            if (orderItems) {
                orderItems.insertAdjacentHTML('beforeend', newItemHtml);
                
                // Add event listeners to the new item
                const newCard = orderItems.lastElementChild;
                if (newCard) {
                    const select = newCard.querySelector('.item-select');
                    const quantityInput = newCard.querySelector('.quantity-input');
                    
                    if (select) {
                        select.addEventListener('change', function() {
                            updateItemPrice(this);
                        });
                    }
                    
                    if (quantityInput) {
                        quantityInput.addEventListener('input', function() {
                            updateTotalAmount();
                        });
                    }
                }
            }
        });
    } else {
        console.log('Add item button not found');
    }
    
    // Remove item
    if (orderItems) {
        orderItems.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                const card = e.target.closest('.card');
                if (card) {
                    card.remove();
                    updateTotalAmount();
                }
            }
        });
    }
    
    // Add event listeners to existing items
    document.querySelectorAll('.item-select').forEach(select => {
        select.addEventListener('change', function() {
            updateItemPrice(this);
        });
    });
    
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', function() {
            updateTotalAmount();
        });
    });
    
    // Set minimum date for delivery date
    const deliveryDate = document.getElementById('delivery_date');
    if (deliveryDate) {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 2); // Minimum 2 days from today
        deliveryDate.min = tomorrow.toISOString().split('T')[0];
    }
    
    // Initialize total amount
    updateTotalAmount();
});
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 