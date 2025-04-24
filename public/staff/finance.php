<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Get date range
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch financial statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        COUNT(*) as cash_orders,
        0 as online_orders,
        SUM(total_amount) as cash_revenue,
        0 as online_revenue
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
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Laporan Kewangan</h1>
        
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
                    <h6 class="card-subtitle text-muted mb-2">Pendapatan Tunai</h6>
                    <h2 class="card-title mb-0">
                        RM <?php echo number_format($stats['cash_revenue'], 2); ?>
                    </h2>
                    <small class="text-muted">
                        <?php echo number_format($stats['cash_orders']); ?> pesanan
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-subtitle text-muted mb-2">Pendapatan Online</h6>
                    <h2 class="card-title mb-0">
                        RM <?php echo number_format($stats['online_revenue'], 2); ?>
                    </h2>
                    <small class="text-muted">
                        <?php echo number_format($stats['online_orders']); ?> pesanan
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Daily Revenue Chart -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Pendapatan Harian</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Revenue -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Pendapatan Mengikut Kategori</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0">Kategori</th>
                                    <th class="border-0 text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_revenue as $category): ?>
                                    <tr>
                                        <td>
                                            <?php echo escape($category['category']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo $category['order_count']; ?> pesanan
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-warning">
                                                RM <?php echo number_format($category['revenue'], 2); ?>
                                            </strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profit Calculator -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Kiraan Keuntungan</h5>
    </div>
    <div class="card-body">
        <form id="profitCalculatorForm" class="row">
            <div class="col-md-6">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label" style="width: 200px;">Jumlah Touch N Go:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="touchNGoAmount" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label" style="width: 200px;">Jumlah Wang Tunai:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="cashAmount" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label" style="width: 200px;">Gaji Pekerja:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="salaryExpense" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label" style="width: 200px;">Kos Overhead:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="overheadCost" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label" style="width: 200px;">Kos Bahan Mentah:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="number" class="form-control" id="rawMaterialCost" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-warning" onclick="calculateProfit()">
                        Kira Keuntungan
                    </button>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <label class="form-label fw-bold" style="width: 200px;">Jumlah Keuntungan:</label>
                    <div class="input-group" style="width: 200px;">
                        <span class="input-group-text">RM</span>
                        <input type="text" class="form-control fw-bold" id="totalProfit" readonly>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for revenue chart
const dates = <?php echo json_encode(array_column($daily_revenue, 'date')); ?>;
const revenues = <?php echo json_encode(array_column($daily_revenue, 'revenue')); ?>;
const orderCounts = <?php echo json_encode(array_column($daily_revenue, 'order_count')); ?>;

// Create revenue chart
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Pendapatan (RM)',
            data: revenues,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
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
        }
    }
});

function calculateProfit() {
    const touchNGo = parseFloat(document.getElementById('touchNGoAmount').value) || 0;
    const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
    const salary = parseFloat(document.getElementById('salaryExpense').value) || 0;
    const overhead = parseFloat(document.getElementById('overheadCost').value) || 0;
    const rawMaterial = parseFloat(document.getElementById('rawMaterialCost').value) || 0;

    // Calculate total revenue
    const totalRevenue = touchNGo + cash;

    // Calculate total expenses
    const totalExpenses = salary + overhead + rawMaterial;

    // Calculate profit
    const profit = totalRevenue - totalExpenses;

    // Display the result
    document.getElementById('totalProfit').value = profit.toFixed(2);
}
</script>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 