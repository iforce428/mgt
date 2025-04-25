<?php
// Initialize variables if not set
if (!isset($is_logged_in)) $is_logged_in = false;
if (!isset($user_role)) $user_role = null;
if (!isset($user_name)) $user_name = null;
if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);

/**
 * Check if the current user is a staff member
 * @return bool True if user is staff, false otherwise
 */
function is_staff() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff';
}
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
    <!-- jQuery (required for some Bootstrap features) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .dropdown-menu {
            min-width: 200px;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            
            // Debug dropdown functionality
            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            console.log('Found dropdowns:', dropdowns.length);
            
            dropdowns.forEach(dropdown => {
                console.log('Setting up dropdown:', dropdown.id);
                
                // Add click event listener
                dropdown.addEventListener('click', function(e) {
                    console.log('Dropdown clicked:', this.id);
                    e.preventDefault();
                    
                    // Manually toggle the dropdown
                    const dropdownMenu = this.nextElementSibling;
                    console.log('Dropdown menu found:', dropdownMenu);
                    
                    if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                        const isShown = dropdownMenu.classList.contains('show');
                        console.log('Current state:', isShown ? 'shown' : 'hidden');
                        
                        // Close all other dropdowns
                        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                            if (menu !== dropdownMenu) {
                                menu.classList.remove('show');
                            }
                        });
                        
                        // Toggle this dropdown
                        dropdownMenu.classList.toggle('show');
                        this.setAttribute('aria-expanded', !isShown);
                        
                        console.log('New state:', !isShown ? 'shown' : 'hidden');
                    }
                });
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    console.log('Clicked outside dropdown');
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        const toggle = menu.previousElementSibling;
                        if (toggle) {
                            toggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            });
        });
    </script>
</head>
<body class="bg-light">
    <header class="bg-white shadow-sm sticky-top">
        <nav class="container navbar navbar-expand-lg navbar-light bg-white">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="<?php 
                    echo strpos($_SERVER['REQUEST_URI'], '/staff/') !== false 
                        ? public_url('staff/index.php') 
                        : public_url('public/index.php'); 
                ?>">
                    <img src="<?php echo public_url('assets/images/logo.png'); ?>" alt="Armaya Enterprise" style="height: 40px;">
                    <span class="ms-2">Armaya Enterprise</span>
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
                            <li class="nav-item">
                                <a class="nav-link <?= ($current_page == 'menu_management.php') ? 'active' : '' ?>" 
                                   href="<?php echo public_url('staff/menu_management.php'); ?>">
                                    Urus Menu
                                </a>
                            </li>
                            <?php if ($user_role === 'Admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= ($current_page == 'user_management.php') ? 'active' : '' ?>" 
                                       href="<?php echo public_url('staff/user_management.php'); ?>">
                                        Urus Pengguna
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="btn btn-warning dropdown-toggle rounded-pill px-3 ms-lg-2" 
                                   href="#"
                                   role="button"
                                   id="userDropdown"
                                   data-bs-toggle="dropdown" 
                                   aria-expanded="false">
                                    Hi <?php echo htmlspecialchars(strtok($user_name ?? '', " ")); ?>!
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo public_url('public/profile.php'); ?>">
                                            <i class="bi bi-person-circle me-2"></i>Profil
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo public_url('logout.php'); ?>">
                                            <i class="bi bi-box-arrow-right me-2"></i>Log Keluar
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
                            <li class="nav-item dropdown">
                                <a class="btn btn-warning dropdown-toggle rounded-pill px-3 ms-lg-2" 
                                   href="#"
                                   role="button"
                                   id="userDropdown"
                                   data-bs-toggle="dropdown" 
                                   aria-expanded="false">
                                    Hi <?php echo htmlspecialchars(strtok($user_name ?? '', " ")); ?>!
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo public_url('public/profile.php'); ?>">
                                            <i class="bi bi-person-circle me-2"></i>Profil
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo public_url('public/logout.php'); ?>">
                                            <i class="bi bi-box-arrow-right me-2"></i>Log Keluar
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