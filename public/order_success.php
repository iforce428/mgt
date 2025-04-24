<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Get order ID from URL
$order_id = $_GET['order_id'] ?? 0;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone_number
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: menu.php');
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, mi.name
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                <h1 class="display-5 fw-bold mt-3">Pesanan Berjaya!</h1>
                <p class="lead text-muted">Terima kasih atas pesanan anda.</p>
            </div>

            <!-- Order Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Butiran Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Maklumat Pelanggan</h6>
                            <p class="mb-1"><?php echo escape($order['full_name']); ?></p>
                            <p class="mb-0"><?php echo escape($order['phone_number']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Maklumat Penghantaran</h6>
                            <p class="mb-1">
                                <strong>Tarikh:</strong> 
                                <?php echo date('d/m/Y', strtotime($order['delivery_date'])); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Masa:</strong> 
                                <?php echo date('h:i A', strtotime($order['delivery_time'])); ?>
                            </p>
                            <p class="mb-0">
                                <strong>Alamat:</strong><br>
                                <?php echo nl2br(escape($order['delivery_location'])); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Kuantiti</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo escape($item['name']); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price_at_order'], 2); ?>
                                        </td>
                                        <td class="text-end">
                                            RM <?php echo number_format($item['price_at_order'] * $item['quantity'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Jumlah:</strong></td>
                                    <td class="text-end">
                                        <strong class="text-warning">
                                            RM <?php echo number_format($order['total_amount'], 2); ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="menu.php" class="btn btn-warning">
                    Buat Pesanan Lagi
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 