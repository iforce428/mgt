<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get report type
$report_type = $_GET['type'] ?? 'sales';

// Get specific date for profit report
$selected_date = $_GET['profit_date'] ?? date('Y-m-d');

// Initialize session storage for profit calculations if not exists
if (!isset($_SESSION['profit_calculations'])) {
    $_SESSION['profit_calculations'] = [
        'tng_amount' => 0,
        'cash_amount' => 0,
        'employee_salary' => 1000.00, // Default values as shown in the UI
        'overhead_cost' => 1100.00,
        'raw_materials_cost' => 3000.00,
        'total_costs' => 5100.00,
        'gross_profit' => 0,
        'net_profit' => 0
    ];
}

// Handle profit calculation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_profit'])) {
    $_SESSION['profit_calculations'] = [
        'tng_amount' => floatval($_POST['tng_amount'] ?? 0),
        'cash_amount' => floatval($_POST['cash_amount'] ?? 0),
        'employee_salary' => floatval($_POST['employee_salary'] ?? 1000.00),
        'overhead_cost' => floatval($_POST['overhead_cost'] ?? 1100.00),
        'raw_materials_cost' => floatval($_POST['raw_materials_cost'] ?? 3000.00)
    ];
    
    // Calculate totals
    $_SESSION['profit_calculations']['total_costs'] = 
        $_SESSION['profit_calculations']['employee_salary'] +
        $_SESSION['profit_calculations']['overhead_cost'] +
        $_SESSION['profit_calculations']['raw_materials_cost'];
        
    $_SESSION['profit_calculations']['gross_profit'] = 
        $_SESSION['profit_calculations']['tng_amount'] +
        $_SESSION['profit_calculations']['cash_amount'];
        
    $_SESSION['profit_calculations']['net_profit'] = 
        $_SESSION['profit_calculations']['gross_profit'] -
        $_SESSION['profit_calculations']['total_costs'];
}

// Fetch report data based on type
switch ($report_type) {
    case 'profit':
        // Profit report data
        $stmt = $pdo->prepare("
            SELECT 
                DATE(o.placed_at) as date,
                SUM(CASE WHEN delivery_option = 'Penghantaran' THEN total_amount ELSE 0 END) as tng_amount,
                SUM(CASE WHEN delivery_option = 'Ambil Sendiri' THEN total_amount ELSE 0 END) as cash_amount,
                COUNT(DISTINCT o.order_id) as total_orders,
                SUM(oi.quantity) as total_items,
                SUM(oi.quantity * oi.price_at_order) as total_revenue
            FROM orders o
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            WHERE DATE(o.placed_at) = ?
            AND o.status != 'Cancelled'
            GROUP BY DATE(o.placed_at)
        ");
        $stmt->execute([$selected_date]);
        $profit_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Update session with fetched amounts if not manually entered
        if (!isset($_POST['calculate_profit'])) {
            $_SESSION['profit_calculations']['tng_amount'] = floatval($profit_data['tng_amount'] ?? 0);
            $_SESSION['profit_calculations']['cash_amount'] = floatval($profit_data['cash_amount'] ?? 0);
            $_SESSION['profit_calculations']['gross_profit'] = 
                $_SESSION['profit_calculations']['tng_amount'] + 
                $_SESSION['profit_calculations']['cash_amount'];
            $_SESSION['profit_calculations']['net_profit'] = 
                $_SESSION['profit_calculations']['gross_profit'] - 
                $_SESSION['profit_calculations']['total_costs'];
        }

        // Get menu items sold on that date
        $stmt = $pdo->prepare("
            SELECT 
                mi.name as menu_name,
                SUM(oi.quantity) as quantity,
                SUM(oi.quantity * oi.price_at_order) as total_amount
            FROM order_items oi
            JOIN menu_items mi ON oi.item_id = mi.item_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.placed_at) = ?
            AND o.status != 'Cancelled'
            GROUP BY mi.item_id, mi.name
            ORDER BY mi.name
        ");
        $stmt->execute([$selected_date]);
        $menu_items_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'sales':
        // Sales report
        $stmt = $pdo->prepare("
            SELECT 
                DATE(placed_at) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue,
                COUNT(*) as cash_orders,
                0 as online_orders,
                SUM(total_amount) as cash_revenue,
                0 as online_revenue
            FROM orders
            WHERE DATE(placed_at) BETWEEN ? AND ?
            AND status != 'Cancelled'
            GROUP BY DATE(placed_at)
            ORDER BY date
        ");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'items':
        // Popular items report
        $stmt = $pdo->prepare("
            SELECT 
                mi.name,
                mi.category,
                mi.price_per_pax as price,
                COUNT(DISTINCT o.order_id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.quantity * oi.price_at_order) as revenue
            FROM order_items oi
            JOIN menu_items mi ON oi.item_id = mi.item_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.placed_at) BETWEEN ? AND ?
            AND o.status != 'Cancelled'
            GROUP BY mi.item_id
            ORDER BY total_quantity DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'customers':
        // Customer report
        $stmt = $pdo->prepare("
            SELECT 
                o.order_id,
                o.customer_id,
                u.full_name,
                o.total_amount,
                o.delivery_option,
                o.event_type,
                o.serving_method,
                o.total_pax,
                o.placed_at,
                o.status
            FROM orders o
            JOIN users u ON o.customer_id = u.user_id
            WHERE DATE(o.placed_at) BETWEEN ? AND ?
            ORDER BY o.placed_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Laporan</h1>
        
        <!-- Report Controls -->
        <div class="d-flex gap-3">
            <?php if ($report_type === 'profit'): ?>
                <!-- Date Selector for Profit Report -->
                <form class="d-flex gap-2">
                    <input type="hidden" name="type" value="profit">
                    <div class="d-flex align-items-center gap-2">
                        <label>Laporan Keuntungan Pada:</label>
                        <input type="date" 
                               class="form-control" 
                               name="profit_date" 
                               value="<?php echo $selected_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-filter"></i> Tapis
                    </button>
                </form>
            <?php else: ?>
                <!-- Existing Date Range Filter -->
                <form class="d-flex gap-2">
                    <input type="hidden" name="type" value="<?php echo $report_type; ?>">
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
            <?php endif; ?>

            <!-- Report Type Selector -->
            <div class="btn-group">
                <a href="?type=profit" 
                   class="btn btn-outline-warning <?php echo $report_type === 'profit' ? 'active' : ''; ?>">
                    Laporan Keuntungan
                </a>
                <a href="?type=sales&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-warning <?php echo $report_type === 'sales' ? 'active' : ''; ?>">
                    Laporan Jualan
                </a>
                <a href="?type=items&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-warning <?php echo $report_type === 'items' ? 'active' : ''; ?>">
                    Laporan Menu
                </a>
                <a href="?type=customers&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-outline-warning <?php echo $report_type === 'customers' ? 'active' : ''; ?>">
                    Laporan Pelanggan
                </a>
            </div>

            <!-- Export Button -->
            <button type="button" class="btn btn-warning" onclick="exportReport()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Report Content -->
    <?php if ($report_type === 'profit'): ?>
        <!-- Profit Report Layout -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="<?php echo public_url('assets/images/logo.png'); ?>" alt="Armaya Enterprise" style="height: 50px;">
                    <h2 class="mb-0">Armaya Enterprise</h2>
                </div>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="calculate_profit" value="1">
                    <div class="row g-4">
                        <!-- Keuntungan Kasar Section -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Keuntungan Kasar</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Touch N Go</label>
                                        <div class="input-group">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="tng_amount" 
                                                   step="0.01" 
                                                   value="<?php echo number_format($_SESSION['profit_calculations']['tng_amount'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Wang Tunai</label>
                                        <div class="input-group">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="cash_amount" 
                                                   step="0.01" 
                                                   value="<?php echo number_format($_SESSION['profit_calculations']['cash_amount'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="fw-bold mt-3">
                                        Jumlah Keuntungan Kasar: 
                                        <span class="float-end">
                                            RM <?php echo number_format($_SESSION['profit_calculations']['gross_profit'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jumlah Kos Section -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Jumlah Kos</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Gaji Pekerja</label>
                                        <div class="input-group">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="employee_salary" 
                                                   step="0.01" 
                                                   value="<?php echo number_format($_SESSION['profit_calculations']['employee_salary'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kos Overhead</label>
                                        <div class="input-group">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="overhead_cost" 
                                                   step="0.01" 
                                                   value="<?php echo number_format($_SESSION['profit_calculations']['overhead_cost'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kos Bahan Mentah</label>
                                        <div class="input-group">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="raw_materials_cost" 
                                                   step="0.01" 
                                                   value="<?php echo number_format($_SESSION['profit_calculations']['raw_materials_cost'], 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                    <div class="fw-bold mt-3">
                                        Jumlah Kos: 
                                        <span class="float-end">
                                            RM <?php echo number_format($_SESSION['profit_calculations']['total_costs'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Jumlah Jualan Catering Section -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Jumlah Jualan Catering</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Menu</th>
                                                    <th class="text-center">Jumlah Pesanan</th>
                                                    <th class="text-end">Jumlah Harga (RM)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($menu_items_data as $item): ?>
                                                <tr>
                                                    <td><?php echo escape($item['menu_name']); ?></td>
                                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                                    <td class="text-end"><?php echo number_format($item['total_amount'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr class="fw-bold">
                                                    <td>Jumlah</td>
                                                    <td class="text-center"><?php echo array_sum(array_column($menu_items_data, 'quantity')); ?></td>
                                                    <td class="text-end">RM <?php echo number_format(array_sum(array_column($menu_items_data, 'total_amount')), 2); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keuntungan Bersih -->
                    <div class="row mt-4">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-warning mb-3">
                                <i class="bi bi-calculator"></i> Kira Keuntungan
                            </button>
                            <h4>
                                Keuntungan Bersih: 
                                <span class="<?php echo $_SESSION['profit_calculations']['net_profit'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    RM <?php echo number_format($_SESSION['profit_calculations']['net_profit'], 2); ?>
                                </span>
                            </h4>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="button" class="btn btn-warning me-2" onclick="downloadReport()">
                                <i class="bi bi-download"></i> Muat Turun
                            </button>
                            <button type="button" class="btn btn-warning" onclick="printReport()">
                                <i class="bi bi-printer"></i> Cetak
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Existing Report Tables -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="reportTable">
                        <thead class="bg-light">
                            <?php if ($report_type === 'sales'): ?>
                                <tr>
                                    <th class="border-0">Tarikh</th>
                                    <th class="border-0 text-center">Jumlah Pesanan</th>
                                    <th class="border-0 text-end">Jumlah Pendapatan</th>
                                    <th class="border-0 text-center">Tunai</th>
                                    <th class="border-0 text-center">Online</th>
                                </tr>
                            <?php elseif ($report_type === 'items'): ?>
                                <tr>
                                    <th class="border-0">Menu</th>
                                    <th class="border-0">Kategori</th>
                                    <th class="border-0 text-center">Jumlah Pesanan</th>
                                    <th class="border-0 text-center">Jumlah Unit</th>
                                    <th class="border-0 text-end">Jumlah Pendapatan</th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <th class="border-0">Tarikh</th>
                                    <th>ID Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Jenis Acara</th>
                                    <th>Kaedah Penyajian</th>
                                    <th>Jumlah Pax</th>
                                    <th>Jumlah (RM)</th>
                                    <th>Status</th>
                                </tr>
                            <?php endif; ?>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $row): ?>
                                <?php if ($report_type === 'sales'): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                                        <td class="text-center"><?php echo number_format($row['order_count']); ?></td>
                                        <td class="text-end">
                                            <strong class="text-warning">
                                                RM <?php echo number_format($row['revenue'], 2); ?>
                                            </strong>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($row['cash_orders']); ?> pesanan
                                            <br>
                                            <small class="text-muted">
                                                RM <?php echo number_format($row['cash_revenue'], 2); ?>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($row['online_orders']); ?> pesanan
                                            <br>
                                            <small class="text-muted">
                                                RM <?php echo number_format($row['online_revenue'], 2); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php elseif ($report_type === 'items'): ?>
                                    <tr>
                                        <td><?php echo escape($row['name']); ?></td>
                                        <td><?php echo escape($row['category']); ?></td>
                                        <td class="text-center"><?php echo number_format($row['order_count']); ?></td>
                                        <td class="text-center"><?php echo number_format($row['total_quantity']); ?></td>
                                        <td class="text-end">
                                            <strong class="text-warning">
                                                RM <?php echo number_format($row['revenue'], 2); ?>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y h:i A', strtotime($row['placed_at'])); ?></td>
                                        <td><?php echo escape($row['order_id']); ?></td>
                                        <td><?php echo escape($row['full_name']); ?></td>
                                        <td><?php echo escape($row['event_type']); ?></td>
                                        <td><?php echo escape($row['serving_method']); ?></td>
                                        <td><?php echo escape($row['total_pax']); ?></td>
                                        <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                        <td><?php echo escape($row['status']); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add print-specific styles -->
<style media="print">
    .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
function exportReport() {
    // Get table data
    const table = document.getElementById('reportTable');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    // Convert to worksheet
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Create workbook
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Report');
    
    // Generate filename
    const filename = '<?php echo $report_type; ?>_report_<?php echo date('Y-m-d'); ?>.xlsx';
    
    // Save file
    XLSX.writeFile(wb, filename);
}

function downloadReport() {
    // Implementation for downloading the profit report
    exportReport();
}

function printReport() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 