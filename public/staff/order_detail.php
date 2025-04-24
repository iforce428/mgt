<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Get order ID from URL
$order_id = $_GET['id'] ?? 0;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    $valid_statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Redirect to refresh the page
        header("Location: order_detail.php?id=" . $order_id);
        exit;
    }
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone_number as phone
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, mi.name, mi.image_url, oi.price_at_order as price_per_unit
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Butiran Pesanan</h1>
            <p class="text-muted mb-0">
                No. Pesanan: #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>
            </p>
        </div>
        <a href="index.php" class="btn btn-outline-warning">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        <!-- Order Information -->
        <div class="col-lg-8">
            <!-- Order Status -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Status Pesanan</h5>
                            <p class="text-muted mb-0">
                                Tarikh Pesanan: <?php echo date('d/m/Y h:i A', strtotime($order['placed_at'])); ?>
                            </p>
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <select name="status" class="form-select">
                                <?php
                                $statuses = [
                                    'Pending' => 'Menunggu',
                                    'Confirmed' => 'Disahkan',
                                    'Preparing' => 'Disediakan',
                                    'Ready' => 'Sedia',
                                    'Delivered' => 'Dihantar',
                                    'Cancelled' => 'Dibatalkan'
                                ];
                                foreach ($statuses as $value => $label):
                                    $selected = $order['status'] === $value ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $value; ?>" <?php echo $selected; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-warning">
                                Kemaskini
                            </button>
                        </form>
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
                            
                            <p class="mb-1"><strong>Kaedah Penghantaran:</strong></p>
                            <p class="mb-0"><?php echo $order['delivery_option']; ?></p>
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

                            <p class="mb-1"><strong>Jumlah Pax:</strong></p>
                            <p class="mb-3"><?php echo escape($order['total_pax']); ?></p>

                            <?php if (!empty($order['staff_notes'])): ?>
                                <p class="mb-1"><strong>Arahan Khas:</strong></p>
                                <p class="mb-0"><?php echo nl2br(escape($order['staff_notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow-sm">
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
                                                        RM <?php echo number_format($item['price_per_unit'], 2); ?> seunit
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price_per_unit'], 2); ?>
                                        </td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price_per_unit'] * $item['quantity'], 2); ?>
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

        <!-- Order Actions -->
        <div class="col-lg-4">
            <!-- Print Order -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Cetak Pesanan</h5>
                    <div class="d-grid gap-2">
                        <a href="print_order.php?id=<?php echo $order_id; ?>" 
                           class="btn btn-outline-warning"
                           target="_blank">
                            <i class="bi bi-printer"></i> Cetak Pesanan
                        </a>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Timeline Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Pesanan Dibuat</h6>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y h:i A', strtotime($order['placed_at'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] !== 'Pending'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Pesanan Disahkan</h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y h:i A', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['Preparing', 'Ready', 'Delivered'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Pesanan Disediakan</h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y h:i A', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['Ready', 'Delivered'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Pesanan Sedia</h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y h:i A', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'Delivered'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Pesanan Dihantar</h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y h:i A', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'Cancelled'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-danger"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-0">Pesanan Dibatalkan</h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y h:i A', strtotime($order['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 1rem;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}
</style>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 