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
    header('Location: order_history.php');
    exit;
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

            <!-- Order Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Status Pesanan</h5>
                            <p class="text-muted mb-0">
                                No. Pesanan: #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
                            </p>
                        </div>
                        <?php
                        $status_class = [
                            'Pending' => 'warning',
                            'Confirmed' => 'info',
                            'Preparing' => 'primary',
                            'Ready' => 'success',
                            'Delivered' => 'success',
                            'Cancelled' => 'danger'
                        ][$order['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?> fs-6">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Maklumat Pelanggan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nama:</strong></p>
                            <p class="mb-3"><?php echo escape($order['full_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Telefon:</strong></p>
                            <p class="mb-3"><?php echo escape($order['phone']); ?></p>
                            
                            <p class="mb-1"><strong>Tarikh Pesanan:</strong></p>
                            <p class="mb-0">
                                <?php echo date('d/m/Y h:i A', strtotime($order['placed_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Maklumat Penghantaran</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tarikh Penghantaran:</strong></p>
                            <p class="mb-3">
                                <?php echo date('d/m/Y', strtotime($order['delivery_date'])); ?>
                            </p>
                            
                            <p class="mb-1"><strong>Masa Penghantaran:</strong></p>
                            <p class="mb-3">
                                <?php echo date('h:i A', strtotime($order['delivery_time'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Alamat Penghantaran:</strong></p>
                            <p class="mb-3"><?php echo nl2br(escape($order['delivery_location'])); ?></p>
                            
                            <p class="mb-1"><strong>Jenis Acara:</strong></p>
                            <p class="mb-3"><?php echo escape($order['event_type']); ?></p>
                            
                            <p class="mb-1"><strong>Kaedah Penyajian:</strong></p>
                            <p class="mb-3"><?php echo escape($order['serving_method']); ?></p>
                            
                            <?php if (!empty($order['staff_notes'])): ?>
                                <p class="mb-1"><strong>Arahan Khas:</strong></p>
                                <p class="mb-0"><?php echo nl2br(escape($order['staff_notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Item Pesanan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Item</th>
                                    <th class="border-0 text-center">Kuantiti</th>
                                    <th class="border-0 text-end">Harga</th>
                                    <th class="border-0 text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image_url'])): ?>
                                                    <img src="<?php echo escape($item['image_url']); ?>" 
                                                         alt="<?php echo escape($item['name']); ?>"
                                                         class="rounded me-3"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo escape($item['name']); ?></h6>
                                                    <small class="text-muted">
                                                        RM <?php echo number_format($item['price'], 2); ?> seunit
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price'], 2); ?>
                                        </td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Jumlah:</strong></td>
                                    <td class="text-end">
                                        <strong class="text-warning">
                                            RM <?php echo number_format($order['total_amount'], 2); ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 