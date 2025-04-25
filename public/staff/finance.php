<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$selected_date = $_GET['profit_date'] ?? date('Y-m-d');

// Initialize session storage for profit calculations if not exists
if (!isset($_SESSION['profit_calculations'])) {
    $_SESSION['profit_calculations'] = [
        'tng_amount' => 0,
        'cash_amount' => 0,
        'employee_salary' => 0,
        'overhead_cost' => 0,
        'raw_materials_cost' => 0,
        'total_costs' => 0,
        'gross_profit' => 0,
        'net_profit' => 0
    ];
}

// Handle profit calculation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_profit'])) {
    // Get the selected date from the form
    $selected_date = $_POST['profit_date'] ?? date('Y-m-d');
    
    $_SESSION['profit_calculations'] = [
        'tng_amount' => floatval($_POST['tng_amount'] ?? 0),
        'cash_amount' => floatval($_POST['cash_amount'] ?? 0),
        'employee_salary' => floatval($_POST['employee_salary'] ?? 0),
        'overhead_cost' => floatval($_POST['overhead_cost'] ?? 0),
        'raw_materials_cost' => floatval($_POST['raw_materials_cost'] ?? 0)
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

    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Delete existing records for this date
        $stmt = $pdo->prepare("DELETE FROM financial_records WHERE record_date = ?");
        $stmt->execute([$selected_date]);
        
        // Insert income records
        if ($_SESSION['profit_calculations']['tng_amount'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO financial_records 
                (record_date, description, type, category, amount, recorded_by_staff_id)
                VALUES (?, 'Touch N Go Income', 'Income', 'Touch N Go', ?, ?)
            ");
            $stmt->execute([
                $selected_date,
                $_SESSION['profit_calculations']['tng_amount'],
                $_SESSION['user_id']
            ]);
        }
        
        if ($_SESSION['profit_calculations']['cash_amount'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO financial_records 
                (record_date, description, type, category, amount, recorded_by_staff_id)
                VALUES (?, 'Cash Income', 'Income', 'Cash', ?, ?)
            ");
            $stmt->execute([
                $selected_date,
                $_SESSION['profit_calculations']['cash_amount'],
                $_SESSION['user_id']
            ]);
        }
        
        // Insert expense records
        if ($_SESSION['profit_calculations']['employee_salary'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO financial_records 
                (record_date, description, type, category, amount, recorded_by_staff_id)
                VALUES (?, 'Employee Salary', 'Expense', 'Gaji Pekerja', ?, ?)
            ");
            $stmt->execute([
                $selected_date,
                $_SESSION['profit_calculations']['employee_salary'],
                $_SESSION['user_id']
            ]);
        }
        
        if ($_SESSION['profit_calculations']['overhead_cost'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO financial_records 
                (record_date, description, type, category, amount, recorded_by_staff_id)
                VALUES (?, 'Overhead Costs', 'Expense', 'Kos Overhead', ?, ?)
            ");
            $stmt->execute([
                $selected_date,
                $_SESSION['profit_calculations']['overhead_cost'],
                $_SESSION['user_id']
            ]);
        }
        
        if ($_SESSION['profit_calculations']['raw_materials_cost'] > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO financial_records 
                (record_date, description, type, category, amount, recorded_by_staff_id)
                VALUES (?, 'Raw Materials Cost', 'Expense', 'Kos Bahan Mentah', ?, ?)
            ");
            $stmt->execute([
                $selected_date,
                $_SESSION['profit_calculations']['raw_materials_cost'],
                $_SESSION['user_id']
            ]);
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error saving financial records: " . $e->getMessage());
    }
}

// Fetch financial statistics for the date range
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        COUNT(CASE WHEN delivery_option = 'Cash' THEN 1 END) as cash_orders,
        COUNT(CASE WHEN delivery_option = 'Online Banking' THEN 1 END) as online_orders,
        SUM(CASE WHEN delivery_option = 'Cash' THEN total_amount ELSE 0 END) as cash_revenue,
        SUM(CASE WHEN delivery_option = 'Online Banking' THEN total_amount ELSE 0 END) as online_revenue
    FROM orders
    WHERE DATE(placed_at) BETWEEN ? AND ?
    AND status != 'Cancelled'
");
$stmt->execute([$start_date, $end_date]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch daily revenue
$stmt = $pdo->prepare("
    SELECT 
        DATE(placed_at) as date,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders
    WHERE DATE(placed_at) BETWEEN ? AND ?
    AND status != 'Cancelled'
    GROUP BY DATE(placed_at)
    ORDER BY date
");
$stmt->execute([$start_date, $end_date]);
$daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch category revenue
$stmt = $pdo->prepare("
    SELECT 
        mi.category,
        COUNT(DISTINCT o.order_id) as order_count,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price_at_order) as revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE DATE(o.placed_at) BETWEEN ? AND ?
    AND o.status != 'Cancelled'
    GROUP BY mi.category
    ORDER BY revenue DESC
");
$stmt->execute([$start_date, $end_date]);
$category_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch profit data for selected date
$stmt = $pdo->prepare("
    SELECT 
        DATE(o.placed_at) as date,
        SUM(CASE WHEN delivery_option = 'Online Banking' THEN total_amount ELSE 0 END) as tng_amount,
        SUM(CASE WHEN delivery_option = 'Cash' THEN total_amount ELSE 0 END) as cash_amount,
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
if (!isset($_POST['calculate_profit']) && $profit_data) {
    $_SESSION['profit_calculations']['tng_amount'] = floatval($profit_data['tng_amount'] ?? 0);
    $_SESSION['profit_calculations']['cash_amount'] = floatval($profit_data['cash_amount'] ?? 0);
    $_SESSION['profit_calculations']['gross_profit'] = 
        $_SESSION['profit_calculations']['tng_amount'] + 
        $_SESSION['profit_calculations']['cash_amount'];
    $_SESSION['profit_calculations']['net_profit'] = 
        $_SESSION['profit_calculations']['gross_profit'] - 
        $_SESSION['profit_calculations']['total_costs'];
}

// Get menu items sold on selected date
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

// Get selected date from form or use today's date
$selected_date = $_POST['profit_date'] ?? date('Y-m-d');

// Fetch financial records for the selected date
$stmt = $pdo->prepare("
    SELECT * FROM financial_records 
    WHERE record_date = ?
    ORDER BY type DESC, category ASC
");
$stmt->execute([$selected_date]);
$financial_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals from records
$total_income = 0;
$total_expenses = 0;
foreach ($financial_records as $record) {
    if ($record['type'] === 'Income') {
        $total_income += $record['amount'];
    } else {
        $total_expenses += $record['amount'];
    }
}
$net_profit = $total_income - $total_expenses;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Kewangan</h1>
    
    <!-- Date Selection Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="post" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="profit_date" class="form-label">Pilih Tarikh:</label>
                    <input type="date" class="form-control" id="profit_date" name="profit_date" 
                           value="<?= htmlspecialchars($selected_date) ?>" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary mt-4">Papar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    Jumlah Pendapatan
                    <h4>RM <?= number_format($total_income, 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    Jumlah Perbelanjaan
                    <h4>RM <?= number_format($total_expenses, 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card <?= $net_profit >= 0 ? 'bg-primary' : 'bg-danger' ?> text-white mb-4">
                <div class="card-body">
                    Untung Bersih
                    <h4>RM <?= number_format($net_profit, 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit Calculation Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calculator me-1"></i>
            Pengiraan Keuntungan
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="profit_date" value="<?= htmlspecialchars($selected_date) ?>">
                
                <div class="col-md-6">
                    <h5>Pendapatan</h5>
                    <div class="mb-3">
                        <label for="tng_amount" class="form-label">Touch N Go</label>
                        <input type="number" step="0.01" class="form-control" id="tng_amount" name="tng_amount" 
                               value="<?= htmlspecialchars($_SESSION['profit_calculations']['tng_amount'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="cash_amount" class="form-label">Tunai</label>
                        <input type="number" step="0.01" class="form-control" id="cash_amount" name="cash_amount"
                               value="<?= htmlspecialchars($_SESSION['profit_calculations']['cash_amount'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5>Perbelanjaan</h5>
                    <div class="mb-3">
                        <label for="employee_salary" class="form-label">Gaji Pekerja</label>
                        <input type="number" step="0.01" class="form-control" id="employee_salary" name="employee_salary"
                               value="<?= htmlspecialchars($_SESSION['profit_calculations']['employee_salary'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="overhead_cost" class="form-label">Kos Overhead</label>
                        <input type="number" step="0.01" class="form-control" id="overhead_cost" name="overhead_cost"
                               value="<?= htmlspecialchars($_SESSION['profit_calculations']['overhead_cost'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="raw_materials_cost" class="form-label">Kos Bahan Mentah</label>
                        <input type="number" step="0.01" class="form-control" id="raw_materials_cost" name="raw_materials_cost"
                               value="<?= htmlspecialchars($_SESSION['profit_calculations']['raw_materials_cost'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" name="calculate_profit" class="btn btn-primary">Kira & Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Records Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Rekod Kewangan (<?= date('d/m/Y', strtotime($selected_date)) ?>)
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th>Jumlah (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($financial_records as $record): ?>
                    <tr class="<?= $record['type'] === 'Income' ? 'table-success' : 'table-danger' ?>">
                        <td><?= $record['type'] === 'Income' ? 'Pendapatan' : 'Perbelanjaan' ?></td>
                        <td><?= htmlspecialchars($record['category']) ?></td>
                        <td><?= htmlspecialchars($record['description']) ?></td>
                        <td class="text-end"><?= number_format($record['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($financial_records)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Tiada rekod untuk tarikh ini</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary">
                        <th colspan="3" class="text-end">Untung Bersih:</th>
                        <th class="text-end"><?= number_format($net_profit, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($item) {
            return date('d/m/Y', strtotime($item['date']));
        }, $daily_revenue)); ?>,
        datasets: [{
            label: 'Pendapatan Harian',
            data: <?php echo json_encode(array_map(function($item) {
                return $item['revenue'];
            }, $daily_revenue)); ?>,
            borderColor: '#ffc107',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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
                        return 'RM ' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 