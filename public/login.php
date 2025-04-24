<?php
// Include necessary files
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/includes/functions.php';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect based on user role
    $role = get_user_role();
    if ($role === 'Admin' || $role === 'Staff') {
        redirect('staff/index.php');
    } else {
        redirect('index.php');
    }
}

// Process login form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Sila masukkan nama pengguna dan kata laluan.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] === 'Admin' || $user['role'] === 'Staff') {
                        redirect('staff/index.php');
                    } else {
                        redirect('index.php');
                    }
                } else {
                    $error = 'Kata laluan tidak sah.';
                }
            } else {
                $error = 'Nama pengguna tidak dijumpai.';
            }
        } catch (Exception $e) {
            $error = 'Ralat sistem. Sila cuba lagi sebentar.';
            error_log($e->getMessage());
        }
    }
}

// Set page title
$page_title = 'Log Masuk - Armaya Enterprise';

// Include header
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0" style="height: 600px;">
        <!-- Left side - Image -->
        <div class="col-lg-6 d-none d-lg-block h-100">
            <img src="<?php echo public_url('images/login.jpg'); ?>" 
                 alt="Various Malaysian dishes" 
                 class="w-100 h-100 object-fit-cover">
        </div>
        
        <!-- Right side - Login Form -->
        <div class="col-lg-6 bg-warning-subtle d-flex align-items-center h-100">
            <div class="w-100 px-4 px-lg-5 py-4">
                <div class="mx-auto" style="max-width: 360px;">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold">Log Masuk</h1>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Nama Pengguna</label>
                            <input type="text" class="form-control form-control-lg rounded-3 border-0" 
                                   id="username" name="username" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Kata Laluan</label>
                            <input type="password" class="form-control form-control-lg rounded-3 border-0" 
                                   id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 py-2 rounded-3 fw-bold mb-3">
                            Log Masuk
                        </button>

                        <p class="text-center mb-0 small">
                            Tiada akaun? 
                            <a href="<?php echo public_url('register.php'); ?>" class="text-decoration-none fw-bold">
                                Daftar Sekarang
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 