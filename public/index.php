<?php
// Include necessary files
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/functions.php';

// Set page title
$page_title = 'Armaya Enterprise - Catering Services';

// Include header
require_once __DIR__ . '/../src/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero bg-light py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-3">Perkhidmatan Katering Berkualiti</h1>
                <p class="lead mb-4">Kami menyediakan perkhidmatan katering untuk pelbagai majlis dengan menu yang lazat dan perkhidmatan yang profesional.</p>
                <?php if (!$is_logged_in): ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="<?php echo get_public_url('register.php'); ?>" class="btn btn-warning btn-lg px-4 me-md-2">Daftar Sekarang</a>
                        <a href="<?php echo get_public_url('menu.php'); ?>" class="btn btn-outline-secondary btn-lg px-4">Lihat Menu</a>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="<?php echo get_public_url('order.php'); ?>" class="btn btn-warning btn-lg px-4 me-md-2">Buat Pesanan</a>
                        <a href="<?php echo get_public_url('menu.php'); ?>" class="btn btn-outline-secondary btn-lg px-4">Lihat Menu</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo get_public_url('images/hero-image.jpg'); ?>" alt="Katering Armaya Enterprise" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="fw-bold mb-4">Mengenai Kami</h2>
                <p class="lead mb-4">Armaya Enterprise telah beroperasi sejak tahun 2015, menyediakan perkhidmatan katering yang berkualiti tinggi untuk pelbagai majlis dan acara.</p>
                <div class="row g-4 py-4">
                    <div class="col-md-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-star-fill display-6"></i>
                        </div>
                        <h5 class="fw-bold">Kualiti Terjamin</h5>
                        <p>Kami menggunakan bahan-bahan segar dan berkualiti tinggi</p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-clock-fill display-6"></i>
                        </div>
                        <h5 class="fw-bold">Tepat Masa</h5>
                        <p>Penghantaran makanan tepat pada masanya</p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-heart-fill display-6"></i>
                        </div>
                        <h5 class="fw-bold">Perkhidmatan Mesra</h5>
                        <p>Staf yang profesional dan mesra pelanggan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section id="reviews" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">Ulasan Pelanggan</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="card-text">"Makanan yang sangat sedap dan khidmat yang memuaskan. Akan menggunakan perkhidmatan mereka lagi!"</p>
                        <p class="card-text"><small class="text-muted">- Sarah Ahmad</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="card-text">"Perkhidmatan yang sangat profesional. Makanan sampai tepat pada masanya dan masih panas!"</p>
                        <p class="card-text"><small class="text-muted">- Muhammad Azmi</small></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                        </div>
                        <p class="card-text">"Harga yang berpatutan untuk kualiti makanan yang sangat baik. Sangat mengesyorkan!"</p>
                        <p class="card-text"><small class="text-muted">- Nurul Huda</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="bg-warning rounded-3 p-5 text-center">
                    <h2 class="fw-bold mb-3">Buat Tempahan Sekarang!</h2>
                    <p class="lead mb-4">Dapatkan pengalaman katering yang terbaik untuk majlis anda.</p>
                    <?php if (!$is_logged_in): ?>
                        <a href="<?php echo get_public_url('register.php'); ?>" class="btn btn-dark btn-lg px-5">Daftar & Tempah</a>
                    <?php else: ?>
                        <a href="<?php echo get_public_url('order.php'); ?>" class="btn btn-dark btn-lg px-5">Buat Tempahan</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 