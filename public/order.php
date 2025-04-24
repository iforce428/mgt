<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to place an order.']);
}

// Initialize variables
$error_message = '';
$success_message = '';
$cart_items = [];
$total_amount = 0;

// Get cart data from POST or session storage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cart'])) {
        // Initial cart submission from menu page
        $cart_json = $_POST['cart'];
        $_SESSION['cart'] = $cart_json; // Store in session for later use
    } elseif (isset($_POST['submit_order'])) {
        // Form submission for order processing
        $cart_json = $_SESSION['cart'] ?? '{}';
    }
} else {
    // Regular page load - check session
    $cart_json = $_SESSION['cart'] ?? '{}';
}

$cart_items = json_decode($cart_json, true);

if (empty($cart_items)) {
    header('Location: menu.php');
    exit;
}

// Calculate total amount
foreach ($cart_items as $item_id => $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
    try {
        $delivery_date = $_POST['delivery_date'] ?? '';
        $delivery_time = $_POST['delivery_time'] ?? '';
        $delivery_location = $_POST['delivery_location'] ?? '';
        $event_type = $_POST['event_type'] ?? '';
        $serving_method = $_POST['serving_method'] ?? [];
        $staff_notes = $_POST['staff_notes'] ?? '';
        $delivery_option = $_POST['delivery_option'] ?? '';

        // Validate required fields
        $errors = [];
        if (empty($delivery_date)) $errors[] = 'Tarikh penghantaran diperlukan';
        if (empty($delivery_time)) $errors[] = 'Masa penghantaran diperlukan';
        if (empty($delivery_location)) $errors[] = 'Alamat penghantaran diperlukan';
        if (empty($event_type)) $errors[] = 'Jenis acara diperlukan';
        if (empty($serving_method)) $errors[] = 'Kaedah penyajian diperlukan';
        if (empty($delivery_option)) $errors[] = 'Kaedah pembayaran diperlukan';
        if (empty($cart_items)) $errors[] = 'Troli anda kosong';

        if (empty($errors)) {
            $pdo->beginTransaction();

            // Calculate total_pax from cart items
            $total_pax = array_sum(array_column($cart_items, 'quantity'));

            // Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    customer_id, 
                    delivery_date, 
                    delivery_time, 
                    total_pax,
                    delivery_option,
                    delivery_location,
                    serving_method,
                    event_type,
                    budget_per_pax,
                    total_amount,
                    staff_notes,
                    placed_at
                ) VALUES (
                    :customer_id,
                    :delivery_date,
                    :delivery_time,
                    :total_pax,
                    :delivery_option,
                    :delivery_location,
                    :serving_method,
                    :event_type,
                    :budget_per_pax,
                    :total_amount,
                    :staff_notes,
                    NOW()
                )
            ");

            $stmt->execute([
                'customer_id' => $_SESSION['user_id'],
                'delivery_date' => $delivery_date,
                'delivery_time' => $delivery_time,
                'total_pax' => $total_pax,
                'delivery_option' => $delivery_option,
                'delivery_location' => $delivery_location,
                'serving_method' => implode(',', $serving_method),
                'event_type' => $event_type,
                'budget_per_pax' => 0.00, // Default value
                'total_amount' => $total_amount,
                'staff_notes' => $staff_notes
            ]);

            $order_id = $pdo->lastInsertId();

            // Insert order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id,
                    item_id,
                    quantity,
                    price_at_order
                ) VALUES (
                    :order_id,
                    :item_id,
                    :quantity,
                    :price_at_order
                )
            ");

            foreach ($cart_items as $item_id => $item) {
                $stmt->execute([
                    'order_id' => $order_id,
                    'item_id' => $item_id,
                    'quantity' => $item['quantity'],
                    'price_at_order' => $item['price']
                ]);
            }

            $pdo->commit();
            
            // Clear cart data
            unset($_SESSION['cart_items']);
            echo "<script>sessionStorage.removeItem('cart');</script>";
            
            // Redirect to success page
            header("Location: order_success.php?order_id=" . $order_id);
            exit;

        } else {
            $error_message = implode('<br>', $errors);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Error placing order: " . $e->getMessage();
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold text-center mb-5">Buat Pesanan</h1>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo escape($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Order Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item_id => $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0"><?php echo escape($item['name']); ?></h6>
                                <small class="text-muted">
                                    RM <?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                </small>
                            </div>
                            <span class="fw-bold">
                                RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Jumlah:</h5>
                        <h5 class="mb-0 text-warning">
                            RM <?php echo number_format($total_amount, 2); ?>
                        </h5>
                    </div>
                </div>
            </div>

            <!-- Order Form -->
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="card shadow-sm">
                <input type="hidden" name="submit_order" value="1">
                <div class="card-body">
                    <!-- Delivery Date -->
                    <div class="mb-3">
                        <label for="delivery_date" class="form-label">Tarikh Penghantaran</label>
                        <input type="date" 
                               class="form-control" 
                               id="delivery_date" 
                               name="delivery_date"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               required>
                    </div>

                    <!-- Delivery Time -->
                    <div class="mb-3">
                        <label for="delivery_time" class="form-label">Masa Penghantaran</label>
                        <input type="time" 
                               class="form-control" 
                               id="delivery_time" 
                               name="delivery_time"
                               min="08:00"
                               max="20:00"
                               required>
                    </div>

                    <!-- Delivery Location -->
                    <div class="mb-3">
                        <label for="delivery_address" class="form-label">Alamat Penghantaran</label>
                        <textarea class="form-control" 
                                  id="delivery_address" 
                                  name="delivery_location" 
                                  rows="3" 
                                  required></textarea>
                    </div>

                    <!-- Event Type -->
                    <div class="mb-3">
                        <label class="form-label">Jenis Acara</label>
                        <select class="form-select" name="event_type" required>
                            <option value="All">Semua</option>
                            <option value="Corporate">Korporat</option>
                            <option value="Social">Sosial</option>
                            <option value="Wedding">Perkahwinan</option>
                        </select>
                    </div>

                    <!-- Serving Method -->
                    <div class="mb-3">
                        <label class="form-label">Kaedah Penyajian</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="serving_method[]" value="Buffet" id="serving_buffet" checked>
                            <label class="form-check-label" for="serving_buffet">
                                Buffet
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="serving_method[]" value="Packed" id="serving_packed" checked>
                            <label class="form-check-label" for="serving_packed">
                                Packed
                            </label>
                        </div>
                    </div>

                    <!-- Special Instructions -->
                    <div class="mb-3">
                        <label for="special_instructions" class="form-label">Arahan Khas (Pilihan)</label>
                        <textarea class="form-control" 
                                  id="special_instructions" 
                                  name="staff_notes" 
                                  rows="2"></textarea>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="form-label">Kaedah Pembayaran</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="delivery_option" 
                                   id="payment_cash" 
                                   value="Cash"
                                   required>
                            <label class="form-check-label" for="payment_cash">
                                Tunai (Bayar semasa penghantaran)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="radio" 
                                   name="delivery_option" 
                                   id="payment_online" 
                                   value="Online Banking"
                                   required>
                            <label class="form-check-label" for="payment_online">
                                Pembayaran Online
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg">
                            Buat Pesanan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('delivery_date').min = tomorrow.toISOString().split('T')[0];
});
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 