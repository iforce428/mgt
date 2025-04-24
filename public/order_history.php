<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view your orders.']);
}

// Pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total orders count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Fetch orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.item_id) as total_items,
           SUM(oi.quantity) as total_quantity
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.customer_id = ?
    GROUP BY o.order_id
    ORDER BY o.placed_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="display-5 fw-bold text-center mb-5">Sejarah Pesanan</h1>

            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                    <p class="lead text-muted mt-3">Anda belum membuat sebarang pesanan.</p>
                    <a href="menu.php" class="btn btn-warning mt-3">
                        Buat Pesanan
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0">No. Pesanan</th>
                                        <th class="border-0">Tarikh</th>
                                        <th class="border-0">Jumlah</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0">Jenis</th>
                                        <th class="border-0">Item</th>
                                        <th class="border-0">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($order['placed_at'])); ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('h:i A', strtotime($order['placed_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong class="text-warning">
                                                    RM <?php echo number_format($order['total_amount'], 2); ?>
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $order['total_pax']; ?> pax
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'Pending' => 'warning',
                                                    'Confirmed' => 'info',
                                                    'Preparing' => 'primary',
                                                    'Ready' => 'success',
                                                    'Completed' => 'success',
                                                    'Cancelled' => 'danger'
                                                ][$order['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $order['delivery_option']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo $order['event_type']; ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $order['serving_method']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo $order['total_items']; ?> item
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $order['total_quantity']; ?> unit
                                                </small>
                                            </td>
                                            <td>
                                                <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning">
                                                    Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 