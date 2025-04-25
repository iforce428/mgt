<?php
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_staff_login();

// Handle all report downloads before any output
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

if (isset($_GET['download_report'])) {
    $report_type = $_GET['download_report'];
    $filename = '';
    $headers = [];
    $data = [];
    
    header('Content-Type: text/csv');
    $output = fopen('php://output', 'w');
    
    switch ($report_type) {
        case 'profit':
            $filename = "profit_report_" . date('Y-m-d') . ".csv";
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Write headers
            fputcsv($output, [
                'Tarikh', 'Keterangan', 'Kategori', 'Jenis', 'Jumlah (RM)'
            ]);
            
            // Fetch transactions
            $stmt = $pdo->prepare("
                SELECT *
                FROM financial_records
                WHERE record_date BETWEEN ? AND ?
                ORDER BY record_date DESC, type DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            $total_income = 0;
            $total_expenses = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['type'] === 'Income') {
                    $total_income += $row['amount'];
                } else {
                    $total_expenses += $row['amount'];
                }
                
                fputcsv($output, [
                    date('d/m/Y', strtotime($row['record_date'])),
                    $row['description'],
                    $row['category'],
                    $row['type'] === 'Income' ? 'Pendapatan' : 'Perbelanjaan',
                    number_format($row['amount'], 2)
                ]);
            }
            
            // Write summary
            fputcsv($output, ['', '', '', '', '']);
            fputcsv($output, ['', '', 'Jumlah Pendapatan:', '', number_format($total_income, 2)]);
            fputcsv($output, ['', '', 'Jumlah Perbelanjaan:', '', number_format($total_expenses, 2)]);
            fputcsv($output, ['', '', 'Untung Bersih:', '', number_format($total_income - $total_expenses, 2)]);
            break;
            
        case 'sales':
            $filename = "sales_report_" . date('Y-m-d') . ".csv";
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Write headers
            fputcsv($output, [
                'Tarikh', 'Jumlah Pesanan', 'Pesanan Tunai', 'Jumlah Tunai (RM)', 
                'Pesanan Online', 'Jumlah Online (RM)', 'Jumlah Keseluruhan (RM)'
            ]);
            
            // Fetch sales data
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(o.placed_at) as date,
                    COUNT(*) as total_orders,
                    COUNT(CASE WHEN o.delivery_option = 'Cash' THEN 1 END) as cash_orders,
                    COUNT(CASE WHEN o.delivery_option = 'Online Banking' THEN 1 END) as online_orders,
                    SUM(CASE WHEN o.delivery_option = 'Cash' THEN o.total_amount ELSE 0 END) as cash_revenue,
                    SUM(CASE WHEN o.delivery_option = 'Online Banking' THEN o.total_amount ELSE 0 END) as online_revenue,
                    SUM(o.total_amount) as total_revenue
                FROM orders o
                WHERE DATE(o.placed_at) BETWEEN ? AND ?
                AND o.status != 'Cancelled'
                GROUP BY DATE(o.placed_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            $total_orders = 0;
            $total_cash = 0;
            $total_online = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $total_orders += $row['total_orders'];
                $total_cash += $row['cash_revenue'];
                $total_online += $row['online_revenue'];
                
                fputcsv($output, [
                    date('d/m/Y', strtotime($row['date'])),
                    $row['total_orders'],
                    $row['cash_orders'],
                    number_format($row['cash_revenue'], 2),
                    $row['online_orders'],
                    number_format($row['online_revenue'], 2),
                    number_format($row['total_revenue'], 2)
                ]);
            }
            
            // Write summary
            fputcsv($output, ['', '', '', '', '', '', '']);
            fputcsv($output, ['Jumlah', $total_orders, '', number_format($total_cash, 2), '', 
                number_format($total_online, 2), number_format($total_cash + $total_online, 2)]);
            break;
            
        case 'menu':
            $filename = "menu_report_" . date('Y-m-d') . ".csv";
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Write headers
            fputcsv($output, [
                'Menu', 'Kategori', 'Jumlah Pesanan', 'Jumlah Unit', 'Jumlah (RM)'
            ]);
            
            // Fetch menu data
            $stmt = $pdo->prepare("
                SELECT 
                    mi.name,
                    mi.category,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price_at_order) as total_revenue
                FROM menu_items mi
                LEFT JOIN order_items oi ON mi.item_id = oi.item_id
                LEFT JOIN orders o ON oi.order_id = o.order_id
                    AND DATE(o.placed_at) BETWEEN ? AND ?
                    AND o.status != 'Cancelled'
                GROUP BY mi.item_id, mi.name, mi.category
                ORDER BY total_quantity DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            $total_orders = 0;
            $total_quantity = 0;
            $total_revenue = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $total_orders += $row['order_count'];
                $total_quantity += $row['total_quantity'];
                $total_revenue += $row['total_revenue'];
                
                fputcsv($output, [
                    $row['name'],
                    $row['category'],
                    $row['order_count'],
                    $row['total_quantity'] ?? 0,
                    number_format($row['total_revenue'] ?? 0, 2)
                ]);
            }
            
            // Write summary
            fputcsv($output, ['', '', '', '', '']);
            fputcsv($output, ['Jumlah', '', $total_orders, $total_quantity, number_format($total_revenue, 2)]);
            break;
            
        case 'customer':
            $filename = "customer_report_" . date('Y-m-d') . ".csv";
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Write headers
            fputcsv($output, [
                'Nama Pelanggan', 'Jumlah Pesanan', 'Jumlah Perbelanjaan (RM)', 'Tarikh Pesanan Terakhir'
            ]);
            
            // Fetch customer data
            $stmt = $pdo->prepare("
                SELECT 
                    u.full_name,
                    COUNT(o.order_id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.placed_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON u.user_id = o.customer_id
                    AND DATE(o.placed_at) BETWEEN ? AND ?
                    AND o.status != 'Cancelled'
                WHERE u.role = 'Customer'
                GROUP BY u.user_id, u.full_name
                ORDER BY total_spent DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            
            $total_orders = 0;
            $total_spent = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $total_orders += $row['total_orders'];
                $total_spent += $row['total_spent'];
                
                fputcsv($output, [
                    $row['full_name'],
                    $row['total_orders'],
                    number_format($row['total_spent'] ?? 0, 2),
                    $row['last_order_date'] ? date('d/m/Y', strtotime($row['last_order_date'])) : '-'
                ]);
            }
            
            // Write summary
            fputcsv($output, ['', '', '', '']);
            fputcsv($output, ['Jumlah', $total_orders, number_format($total_spent, 2), '']);
            break;
    }
    
    fclose($output);
    exit();
}

require_once __DIR__ . '/../../src/includes/header.php';

// Get active tab
$active_tab = $_GET['tab'] ?? 'profit';

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch daily financial records
$stmt = $pdo->prepare("
    SELECT 
        record_date,
        type,
        SUM(amount) as total_amount
    FROM financial_records
    WHERE record_date BETWEEN ? AND ?
    GROUP BY record_date, type
    ORDER BY record_date ASC
");
$stmt->execute([$start_date, $end_date]);
$daily_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process data for charts
$dates = [];
$income_data = [];
$expense_data = [];
$profit_data = [];
$current_date = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current_date <= $end) {
    $date_str = $current_date->format('Y-m-d');
    $dates[] = $date_str;
    $income_data[$date_str] = 0;
    $expense_data[$date_str] = 0;
    $current_date->modify('+1 day');
}

foreach ($daily_records as $record) {
    if ($record['type'] === 'Income') {
        $income_data[$record['record_date']] = floatval($record['total_amount']);
    } else {
        $expense_data[$record['record_date']] = floatval($record['total_amount']);
    }
}

foreach ($dates as $date) {
    $profit_data[$date] = $income_data[$date] - $expense_data[$date];
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Laporan</h1>
    
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'profit' ? 'active' : '' ?>" href="?tab=profit">
                Laporan Keuntungan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'sales' ? 'active' : '' ?>" href="?tab=sales">
                Laporan Jualan
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'menu' ? 'active' : '' ?>" href="?tab=menu">
                Laporan Menu
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab === 'customer' ? 'active' : '' ?>" href="?tab=customer">
                Laporan Pelanggan
            </a>
        </li>
    </ul>

    <?php if ($active_tab === 'profit'): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chart-line me-1"></i>
                Laporan Keuntungan
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-warning">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <a href="?tab=profit&download_report=profit&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Muat Turun CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Date Range Selection -->
            <form method="get" class="row g-3 mb-4 no-print">
                <input type="hidden" name="tab" value="profit">
                <div class="col-md-4">
                    <label class="form-label">Dari Tarikh:</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?= htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hingga Tarikh:</label>
                    <input type="date" class="form-control" name="end_date" 
                           value="<?= htmlspecialchars($_GET['end_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Tapis</button>
                </div>
            </form>

            <!-- Charts -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Aliran Tunai Harian</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="cashFlowChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Keterangan</th>
                            <th>Kategori</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT *
                            FROM financial_records
                            WHERE record_date BETWEEN ? AND ?
                            ORDER BY record_date DESC, type DESC
                        ");
                        $stmt->execute([$start_date, $end_date]);
                        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $total_income = 0;
                        $total_expenses = 0;

                        foreach ($transactions as $transaction):
                            if ($transaction['type'] === 'Income') {
                                $total_income += $transaction['amount'];
                            } else {
                                $total_expenses += $transaction['amount'];
                            }
                        ?>
                            <tr class="<?= $transaction['type'] === 'Income' ? 'table-success' : 'table-danger' ?>">
                                <td><?= date('d/m/Y', strtotime($transaction['record_date'])) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td><?= htmlspecialchars($transaction['category']) ?></td>
                                <td><?= $transaction['type'] === 'Income' ? 'Pendapatan' : 'Perbelanjaan' ?></td>
                                <td class="text-end"><?= number_format($transaction['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="4" class="text-end">Jumlah Pendapatan:</th>
                            <th class="text-end">RM <?= number_format($total_income, 2) ?></th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="4" class="text-end">Jumlah Perbelanjaan:</th>
                            <th class="text-end">RM <?= number_format($total_expenses, 2) ?></th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="4" class="text-end">Untung Bersih:</th>
                            <th class="text-end">RM <?= number_format($total_income - $total_expenses, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Cash Flow Chart
    new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($date) {
                return date('d/m/Y', strtotime($date));
            }, $dates)) ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?= json_encode(array_values($income_data)) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true
            }, {
                label: 'Perbelanjaan',
                data: <?= json_encode(array_values($expense_data)) ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true
            }, {
                label: 'Untung Bersih',
                data: <?= json_encode(array_values($profit_data)) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'RM ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': RM ' + 
                                   context.raw.toLocaleString(undefined, {
                                       minimumFractionDigits: 2,
                                       maximumFractionDigits: 2
                                   });
                        }
                    }
                }
            }
        }
    });

    // Print styles
    if (window.matchMedia) {
        const mediaQueryList = window.matchMedia('print');
        mediaQueryList.addListener(function(mql) {
            if (mql.matches) {
                document.querySelectorAll('canvas').forEach(canvas => {
                    canvas.style.maxHeight = '500px';
                });
            }
        });
    }
    </script>

    <style media="print">
    @page {
        size: landscape;
    }
    .btn, form, .no-print {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-header {
        background: none !important;
        border: none !important;
    }
    .table {
        font-size: 12px;
    }
    canvas {
        max-height: 250px !important;
        width: auto !important;
        page-break-inside: avoid;
    }
    </style>
    <?php endif; ?>

    <?php if ($active_tab === 'sales'): ?>
    <!-- Sales Report Content -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-chart-bar me-1"></i>
                Laporan Jualan
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-warning">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <a href="?tab=sales&download_report=sales&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Muat Turun CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Date Range Selection -->
            <form method="get" class="row g-3 mb-4">
                <input type="hidden" name="tab" value="sales">
                <div class="col-md-4">
                    <label class="form-label">Dari Tarikh:</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?= htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hingga Tarikh:</label>
                    <input type="date" class="form-control" name="end_date" 
                           value="<?= htmlspecialchars($_GET['end_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Tapis</button>
                </div>
            </form>

            <?php
            // Fetch sales data
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(o.placed_at) as date,
                    COUNT(*) as total_orders,
                    SUM(o.total_amount) as total_revenue,
                    COUNT(CASE WHEN o.delivery_option = 'Cash' THEN 1 END) as cash_orders,
                    COUNT(CASE WHEN o.delivery_option = 'Online Banking' THEN 1 END) as online_orders,
                    SUM(CASE WHEN o.delivery_option = 'Cash' THEN o.total_amount ELSE 0 END) as cash_revenue,
                    SUM(CASE WHEN o.delivery_option = 'Online Banking' THEN o.total_amount ELSE 0 END) as online_revenue
                FROM orders o
                WHERE DATE(o.placed_at) BETWEEN ? AND ?
                AND o.status != 'Cancelled'
                GROUP BY DATE(o.placed_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th class="text-center">Jumlah Pesanan</th>
                            <th class="text-center">Pesanan Tunai</th>
                            <th class="text-center">Pesanan Online</th>
                            <th class="text-end">Jumlah (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_orders = 0;
                        $total_cash_orders = 0;
                        $total_online_orders = 0;
                        $total_revenue = 0;

                        foreach ($sales_data as $sale): 
                            $total_orders += $sale['total_orders'];
                            $total_cash_orders += $sale['cash_orders'];
                            $total_online_orders += $sale['online_orders'];
                            $total_revenue += $sale['total_revenue'];
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($sale['date'])) ?></td>
                                <td class="text-center"><?= $sale['total_orders'] ?></td>
                                <td class="text-center">
                                    <?= $sale['cash_orders'] ?>
                                    <br>
                                    <small class="text-muted">
                                        RM <?= number_format($sale['cash_revenue'], 2) ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <?= $sale['online_orders'] ?>
                                    <br>
                                    <small class="text-muted">
                                        RM <?= number_format($sale['online_revenue'], 2) ?>
                                    </small>
                                </td>
                                <td class="text-end">RM <?= number_format($sale['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Jumlah</th>
                            <th class="text-center"><?= $total_orders ?></th>
                            <th class="text-center"><?= $total_cash_orders ?></th>
                            <th class="text-center"><?= $total_online_orders ?></th>
                            <th class="text-end">RM <?= number_format($total_revenue, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($active_tab === 'menu'): ?>
    <!-- Menu Report Content -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-utensils me-1"></i>
                Laporan Menu
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-warning">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <a href="?tab=menu&download_report=menu&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Muat Turun CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Date Range Selection -->
            <form method="get" class="row g-3 mb-4">
                <input type="hidden" name="tab" value="menu">
                <div class="col-md-4">
                    <label class="form-label">Dari Tarikh:</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?= htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hingga Tarikh:</label>
                    <input type="date" class="form-control" name="end_date" 
                           value="<?= htmlspecialchars($_GET['end_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Tapis</button>
                </div>
            </form>

            <?php
            // Fetch menu items data
            $stmt = $pdo->prepare("
                SELECT 
                    mi.name,
                    mi.category,
                    COUNT(DISTINCT o.order_id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.quantity * oi.price_at_order) as total_revenue
                FROM menu_items mi
                LEFT JOIN order_items oi ON mi.item_id = oi.item_id
                LEFT JOIN orders o ON oi.order_id = o.order_id
                    AND DATE(o.placed_at) BETWEEN ? AND ?
                    AND o.status != 'Cancelled'
                GROUP BY mi.item_id, mi.name, mi.category
                ORDER BY total_quantity DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Kategori</th>
                            <th class="text-center">Jumlah Pesanan</th>
                            <th class="text-center">Jumlah Unit</th>
                            <th class="text-end">Jumlah (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_orders = 0;
                        $total_quantity = 0;
                        $total_revenue = 0;

                        foreach ($menu_items as $item): 
                            $total_orders += $item['order_count'];
                            $total_quantity += $item['total_quantity'];
                            $total_revenue += $item['total_revenue'];
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= htmlspecialchars($item['category']) ?></td>
                                <td class="text-center"><?= $item['order_count'] ?></td>
                                <td class="text-center"><?= $item['total_quantity'] ?? 0 ?></td>
                                <td class="text-end">RM <?= number_format($item['total_revenue'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="2">Jumlah</th>
                            <th class="text-center"><?= $total_orders ?></th>
                            <th class="text-center"><?= $total_quantity ?></th>
                            <th class="text-end">RM <?= number_format($total_revenue, 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($active_tab === 'customer'): ?>
    <!-- Customer Report Content -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Laporan Pelanggan
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-warning">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <a href="?tab=customer&download_report=customer&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Muat Turun CSV
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Date Range Selection -->
            <form method="get" class="row g-3 mb-4">
                <input type="hidden" name="tab" value="customer">
                <div class="col-md-4">
                    <label class="form-label">Dari Tarikh:</label>
                    <input type="date" class="form-control" name="start_date" 
                           value="<?= htmlspecialchars($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hingga Tarikh:</label>
                    <input type="date" class="form-control" name="end_date" 
                           value="<?= htmlspecialchars($_GET['end_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Tapis</button>
                </div>
            </form>

            <?php
            // Fetch customer orders data
            $stmt = $pdo->prepare("
                SELECT 
                    u.full_name,
                    COUNT(o.order_id) as total_orders,
                    SUM(o.total_amount) as total_spent,
                    MAX(o.placed_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON u.user_id = o.customer_id
                    AND DATE(o.placed_at) BETWEEN ? AND ?
                    AND o.status != 'Cancelled'
                WHERE u.role = 'Customer'
                GROUP BY u.user_id, u.full_name
                ORDER BY total_spent DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Nama Pelanggan</th>
                            <th class="text-center">Jumlah Pesanan</th>
                            <th class="text-end">Jumlah Perbelanjaan (RM)</th>
                            <th>Tarikh Pesanan Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_orders = 0;
                        $total_spent = 0;

                        foreach ($customers as $customer): 
                            $total_orders += $customer['total_orders'];
                            $total_spent += $customer['total_spent'];
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['full_name']) ?></td>
                                <td class="text-center"><?= $customer['total_orders'] ?></td>
                                <td class="text-end">RM <?= number_format($customer['total_spent'] ?? 0, 2) ?></td>
                                <td><?= $customer['last_order_date'] ? date('d/m/Y', strtotime($customer['last_order_date'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Jumlah</th>
                            <th class="text-center"><?= $total_orders ?></th>
                            <th class="text-end">RM <?= number_format($total_spent, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 