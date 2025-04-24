<?php
// Initialize variables if not set
if (!isset($is_logged_in)) $is_logged_in = false;
if (!isset($user_role)) $user_role = null;
if (!isset($user_name)) $user_name = null;
if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Armaya Enterprise'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
    <header class="bg-white shadow-sm sticky-top">
        <nav class="container navbar navbar-expand-lg navbar-light bg-white">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo public_url('index.php'); ?>">
                    <img src="<?php echo public_url('images/logo.png'); ?>" alt="Armaya Enterprise Logo" class="me-2" style="height: 40px; width: auto;">
                    <span class="fw-bold">Armaya Enterprise</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                        <?php if ($is_logged_in && ($user_role === 'Staff' || $user_role === 'Admin')): ?>
                            <!-- Staff/Admin Navigation -->
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/staff') !== false) ? 'active' : '' ?>" 
                                   href="<?php echo public_url('staff/index.php'); ?>">
                                    Senarai Pesanan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'finance.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('staff/finance.php'); ?>">
                                    Data Jualan & Keuntungan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('staff/reports.php'); ?>">
                                    Laporan
                                </a>
                            </li>
                            <?php if ($user_role === 'Admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'menu_management.php') ? 'active' : '' ?>" 
                                       href="<?php echo public_url('staff/menu_management.php'); ?>">
                                        Urus Menu
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'user_management.php') ? 'active' : '' ?>" 
                                       href="<?php echo public_url('staff/user_management.php'); ?>">
                                        Urus Pengguna
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <button class="btn btn-warning dropdown-toggle rounded-pill px-3 ms-lg-2" 
                                        type="button"
                                        id="userDropdown"
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false">
                                    Hi <?php echo htmlspecialchars(strtok($user_name ?? '', " ")); ?>!
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo public_url('public/login.php'); ?>" 
                                           onclick="document.cookie.split(';').forEach(function(c) { document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/'); }); sessionStorage.clear(); localStorage.clear();">
                                            Log Keluar
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php elseif ($is_logged_in && $user_role === 'Customer'): ?>
                            <!-- Customer Navigation -->
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'menu.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('public/menu.php'); ?>">
                                    Menu
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'recommendation.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('public/recommendation.php'); ?>">
                                    Rekomendasi Menu
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'order.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('public/order.php'); ?>">
                                    Pesanan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'order_history.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('public/order_history.php'); ?>">
                                    Sejarah Pesanan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('public/profile.php'); ?>">
                                    Profil
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <button class="btn btn-warning dropdown-toggle rounded-pill px-3 ms-lg-2" 
                                        type="button"
                                        id="userDropdown"
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false">
                                    Hi <?php echo htmlspecialchars(strtok($user_name ?? '', " ")); ?>!
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo public_url('public/login.php'); ?>" 
                                           onclick="document.cookie.split(';').forEach(function(c) { document.cookie = c.replace(/^ +/, '').replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/'); }); sessionStorage.clear(); localStorage.clear();">
                                            Log Keluar
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Logged Out Navigation -->
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>" 
                                   aria-current="page" 
                                   href="<?php echo public_url('index.php'); ?>">
                                    Halaman Utama
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#about">Mengenai Kami</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#reviews">Ulasan Pelanggan</a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo public_url('public/login.php'); ?>" 
                                   class="btn btn-warning rounded-pill px-4 ms-lg-2 <?= ($current_page == 'login.php') ? 'active' : '' ?>">
                                    Log Masuk / Daftar
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container mt-4 mb-5">
    <!-- Page specific content starts here -->
</body>
</html> 