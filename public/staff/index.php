<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch order statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'Confirmed' THEN 1 END) as confirmed_orders,
        COUNT(CASE WHEN status = 'Preparing' THEN 1 END) as preparing_orders,
        COUNT(CASE WHEN status = 'Ready' THEN 1 END) as ready_orders,
        COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as delivered_orders,
        COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled_orders
    FROM orders
    WHERE placed_at BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone_number
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.placed_at BETWEEN ? AND ?
    ORDER BY o.placed_at DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch popular items
$stmt = $pdo->prepare("
    SELECT mi.name, mi.category, mi.price_per_pax as price,
           COUNT(DISTINCT oi.order_id) as order_count,
           SUM(oi.quantity) as total_quantity
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.placed_at BETWEEN ? AND ?
    GROUP BY mi.item_id
    ORDER BY total_quantity DESC
    LIMIT 5
");
$stmt->execute([$start_date, $end_date]);
$popular_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Dashboard</h1>
        
        <!-- Date Range Filter -->
        <form class="d-flex gap-2">
            <input type="date" 
                   class="form-control" 
                   name="start_date" 
                   value="<?php echo $start_date; ?>">
            <input type="date" 
                   class="form-control" 
                   name="end_date" 
                   value="<?php echo $end_date; ?>">
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-filter"></i> Tapis
            </button>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Jumlah Pesanan</h6>
                    <h2 class="card-title mb-0"><?php echo number_format($stats['total_orders']); ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Jumlah Pendapatan</h6>
                    <h2 class="card-title mb-0">
                        RM <?php echo number_format($stats['total_revenue'], 2); ?>
                    </h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Pesanan Belum Selesai</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($stats['pending_orders'] + $stats['confirmed_orders'] + $stats['preparing_orders']); ?>
                    </h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Pesanan Selesai</h6>
                    <h2 class="card-title mb-0">
                        <?php echo number_format($stats['delivered_orders']); ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Pesanan Terkini</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">No. Pesanan</th>
                                    <th class="border-0">Pelanggan</th>
                                    <th class="border-0">Jumlah</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y h:i A', strtotime($order['placed_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo escape($order['full_name']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo escape($order['phone_number']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong class="text-warning">
                                                RM <?php echo number_format($order['total_amount'], 2); ?>
                                            </strong>
                                        </td>
                                        <td>
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
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
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
                <div class="card-footer bg-light">
                    <a href="orders.php" class="btn btn-link text-warning p-0">
                        Lihat Semua Pesanan
                    </a>
                </div>
            </div>
        </div>

        <!-- Popular Items -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Menu Popular</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular_items as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo escape($item['name']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo escape($item['category']); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-warning">
                                            RM <?php echo number_format($item['price'], 2); ?>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $item['total_quantity']; ?> unit
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="menu_management.php" class="btn btn-link text-warning p-0">
                        Urus Menu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 