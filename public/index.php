<?php
// Include necessary files
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/functions.php';

// Set page title
$page_title = 'Armaya Enterprise - Catering Services';

// Include header
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container-fluid p-0">
    <!-- Hero Section -->
    <div class="position-relative">
        <img src="images/hero-background.jpg" alt="Catering Background" class="w-100" style="height: 60vh; object-fit: cover; background-color: #f8f9fa;">
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5);">
            <div class="text-center text-white">
                <h1 class="display-4 fw-bold mb-4">Armaya Catering</h1>
                <p class="lead mb-4">Serving delicious food for your special occasions</p>
                <a href="menu.php" class="btn btn-warning btn-lg">View Menu</a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-egg-fried display-4 text-warning mb-3"></i>
                        <h3 class="h5 fw-bold">Quality Food</h3>
                        <p class="text-muted">We use only the freshest ingredients to prepare our delicious meals.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-truck display-4 text-warning mb-3"></i>
                        <h3 class="h5 fw-bold">Delivery Service</h3>
                        <p class="text-muted">We deliver to your location with care and punctuality.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check display-4 text-warning mb-3"></i>
                        <h3 class="h5 fw-bold">Easy Booking</h3>
                        <p class="text-muted">Simple online booking system for your convenience.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Menu Items -->
    <div class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Popular Menu Items</h2>
            <div class="row g-4">
                <?php
                // Fetch popular menu items
                try {
                    $stmt = $conn->prepare("
                        SELECT * FROM menu_items 
                        WHERE is_available = 1
                        ORDER BY item_id 
                        LIMIT 3
                    ");
                    $stmt->execute();
                    $popular_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    if (!empty($popular_items)) {
                        foreach ($popular_items as $item):
                        ?>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                        class="card-img-top" 
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" 
                                        style="height: 200px;">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo !empty($item['description']) ? htmlspecialchars($item['description']) : 'No description available'; ?></p>
                                    <p class="card-text fw-bold text-warning">
                                        RM <?php echo number_format($item['price_per_pax'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php 
                        endforeach;
                    } else {
                        echo '<div class="col-12 text-center"><p>No menu items available at the moment.</p></div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="col-12 text-center"><p>Unable to load menu items. Please try again later.</p></div>';
                }
                ?>
            </div>
            <div class="text-center mt-4">
                <a href="menu.php" class="btn btn-outline-warning">View All Menu Items</a>
            </div>
        </div>
    </div>

    <!-- Testimonials -->
    <div class="container py-5">
        <h2 class="text-center mb-5">What Our Customers Say</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="card-text">"The food was amazing! Everyone at our event loved it. Will definitely order again."</p>
                        <p class="card-text text-muted mb-0">- Sarah A.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="card-text">"Great service and delicious food. The delivery was on time and everything was perfect."</p>
                        <p class="card-text text-muted mb-0">- John B.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-fill text-warning me-1"></i>
                            <i class="bi bi-star-half text-warning"></i>
                        </div>
                        <p class="card-text">"Excellent catering service. The food was fresh and the staff was very professional."</p>
                        <p class="card-text text-muted mb-0">- Maria C.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-warning py-5">
        <div class="container text-center">
            <h2 class="text-white mb-4">Ready to Order?</h2>
            <p class="text-white mb-4">Let us cater your next event with our delicious menu options.</p>
            <a href="menu.php" class="btn btn-light btn-lg">Order Now</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 