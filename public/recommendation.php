<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view recommendations.']);
}

// Process filter form if submitted
$filtered_recommendations = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    $budget = floatval($_POST['budget'] ?? 5.00);
    $meal_preference = $_POST['meal_preference'] ?? '';
    $pax = intval($_POST['pax'] ?? 20);
    $serving_method = $_POST['serving_method'] ?? '';
    $event_type = $_POST['event_type'] ?? '';

    // Debug logging
    error_log("Budget: $budget");
    error_log("Meal Preference: $meal_preference");
    error_log("Pax: $pax");
    error_log("Serving Method: $serving_method");
    error_log("Event Type: $event_type");

    // Build base SQL query
    $sql = "
        SELECT mi.*, mi.price_per_pax as price,
               COUNT(DISTINCT oi.order_id) as popularity
        FROM menu_items mi
        LEFT JOIN order_items oi ON mi.item_id = oi.item_id
        WHERE mi.is_available = 1
        AND mi.price_per_pax <= :budget
        AND mi.serving_methods LIKE :serving_method
        AND mi.event_types LIKE :event_type
        AND mi.min_pax <= :pax
        AND mi.max_pax >= :pax
    ";
    
    // Initialize parameters array
    $params = [
        ':budget' => $budget,
        ':serving_method' => "%$serving_method%",
        ':event_type' => "%$event_type%",
        ':pax' => $pax
    ];
    
    // Add meal preference if specified
    if (!empty($meal_preference)) {
        $sql .= " AND mi.category LIKE :meal_preference";
        $params[':meal_preference'] = "%$meal_preference%";
    }
    
    // Add final parts of query
    $sql .= " GROUP BY mi.item_id ORDER BY popularity DESC LIMIT 8";
    
    // Debug logging
    error_log("SQL Query: $sql");
    error_log("Parameters: " . print_r($params, true));
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $filtered_recommendations = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        error_log("SQL Query: $sql");
        error_log("Parameters: " . print_r($params, true));
        throw $e;
    }
}

// Fetch user's order history
$stmt = $pdo->prepare("
    SELECT oi.item_id, mi.name, mi.category, mi.image_url, mi.price_per_pax as price,
           COUNT(*) as order_count, SUM(oi.quantity) as total_quantity
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE o.customer_id = ? AND o.status != 'Cancelled'
    GROUP BY oi.item_id
    ORDER BY order_count DESC, total_quantity DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$frequently_ordered = $stmt->fetchAll();

// Get categories from frequently ordered items
$categories = array_unique(array_column($frequently_ordered, 'category'));

// Fetch recommended items from same categories
$recommended_items = [];
if (!empty($categories)) {
    // Create placeholders for each category
    $placeholders = array_map(function($index) {
        return ":category$index";
    }, array_keys($categories));
    
    $sql = "
        SELECT mi.*, mi.price_per_pax as price,
               COUNT(DISTINCT oi.order_id) as popularity
        FROM menu_items mi
        LEFT JOIN order_items oi ON mi.item_id = oi.item_id
        WHERE mi.category IN (" . implode(',', $placeholders) . ")
        AND mi.is_available = 1
        GROUP BY mi.item_id
        ORDER BY popularity DESC
        LIMIT 6
    ";
    
    // Create parameters array with named parameters
    $params = [];
    foreach ($categories as $index => $category) {
        $params[":category$index"] = $category;
    }
    
    // Debug logging
    error_log("Recommended Items SQL: $sql");
    error_log("Recommended Items Parameters: " . print_r($params, true));
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recommended_items = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("PDO Error in Recommended Items: " . $e->getMessage());
        error_log("SQL Query: $sql");
        error_log("Parameters: " . print_r($params, true));
        throw $e;
    }
}

// Fetch popular items
$stmt = $pdo->prepare("
    SELECT mi.*, mi.price_per_pax as price,
           COUNT(DISTINCT oi.order_id) as popularity
    FROM menu_items mi
    LEFT JOIN order_items oi ON mi.item_id = oi.item_id
    WHERE mi.is_available = 1
    GROUP BY mi.item_id
    ORDER BY popularity DESC
    LIMIT 6
");
$stmt->execute();
$popular_items = $stmt->fetchAll();
?>

<div class="container py-5">
    <h1 class="display-5 fw-bold text-center mb-5">Cadangan Menu</h1>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <h5 class="card-title mb-4">Isi butiran kriteria bagi pesanan katering anda:</h5>
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Bajet:</label>
                        <select name="budget" class="form-select">
                            <option value="5.00">RM5.00</option>
                            <option value="7.00">RM7.00</option>
                            <option value="10.00">RM10.00</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Keutamaan hidangan:</label>
                        <input type="text" name="meal_preference" class="form-control" placeholder="cth: Ayam, Ikan, Sayur">
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Bilangan:</label>
                        <select name="pax" class="form-select">
                            <?php for($i = 10; $i <= 100; $i += 10): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Kaedah hidangan:</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="serving_method" value="Buffet" id="buffet">
                                <label class="form-check-label" for="buffet">Hidang (Buffet)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="serving_method" value="Packed" id="packed">
                                <label class="form-check-label" for="packed">Bungkus</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Kategori majlis:</label>
                        <input type="text" name="event_type" class="form-control" placeholder="cth: Mesyuarat, Kenduri">
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="submit" name="filter" class="btn btn-warning btn-lg px-5">
                            <i class="bi bi-funnel-fill me-2"></i>Rekomendasi Menu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtered Recommendations -->
    <?php if (!empty($filtered_recommendations)): ?>
        <div class="mb-5">
            <h2 class="h4 fw-bold mb-4">Rekomendasi menu berikut memenuhi kriteria yang dipinta:</h2>
            <div class="row g-4">
                <?php foreach ($filtered_recommendations as $item): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo escape($item['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo escape($item['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-warning">
                                    RM<?php echo number_format($item['price'], 2); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2"><?php echo escape($item['name']); ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo escape($item['category']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo $item['popularity'] ?? 0; ?> pesanan
                                    </small>
                                    <a href="menu.php" class="btn btn-warning btn-sm">
                                        Pesan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Frequently Ordered Items -->
    <?php if (!empty($frequently_ordered)): ?>
        <div class="mb-5">
            <h2 class="h4 fw-bold mb-4">Menu Kegemaran Anda</h2>
            <div class="row g-4">
                <?php foreach ($frequently_ordered as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo escape($item['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo escape($item['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2"><?php echo escape($item['name']); ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo escape($item['category']); ?>
                                </p>
                                <p class="card-text fw-bold text-warning mb-3">
                                    RM <?php echo number_format($item['price'], 2); ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Dipesan <?php echo $item['order_count']; ?> kali
                                    </small>
                                    <a href="menu.php" class="btn btn-warning btn-sm">
                                        Pesan Lagi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recommended Items -->
    <?php if (!empty($recommended_items)): ?>
        <div class="mb-5">
            <h2 class="h4 fw-bold mb-4">Cadangan Untuk Anda</h2>
            <div class="row g-4">
                <?php foreach ($recommended_items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo escape($item['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo escape($item['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2"><?php echo escape($item['name']); ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo escape($item['category']); ?>
                                </p>
                                <p class="card-text fw-bold text-warning mb-3">
                                    RM <?php echo number_format($item['price'], 2); ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo $item['popularity']; ?> pesanan
                                    </small>
                                    <a href="menu.php" class="btn btn-warning btn-sm">
                                        Pesan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Popular Items -->
    <div class="mb-5">
        <h2 class="h4 fw-bold mb-4">Menu Popular</h2>
        <div class="row g-4">
            <?php foreach ($popular_items as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($item['image_url'])): ?>
                            <img src="<?php echo escape($item['image_url']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo escape($item['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-2"><?php echo escape($item['name']); ?></h5>
                            <p class="card-text text-muted small mb-2">
                                <?php echo escape($item['category']); ?>
                            </p>
                            <p class="card-text fw-bold text-warning mb-3">
                                RM <?php echo number_format($item['price'], 2); ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?php echo $item['popularity']; ?> pesanan
                                </small>
                                <a href="menu.php" class="btn btn-warning btn-sm">
                                    Pesan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- View All Menu Button -->
    <div class="text-center">
        <a href="menu.php" class="btn btn-warning btn-lg">
            Lihat Semua Menu
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 
