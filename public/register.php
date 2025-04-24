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

// Process registration form
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($phone_number)) {
        $error = 'Sila isi semua maklumat yang diperlukan.';
    } elseif ($password !== $confirm_password) {
        $error = 'Kata laluan tidak sepadan.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara.';
    } else {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Nama pengguna telah digunakan. Sila pilih nama pengguna lain.';
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, phone_number, role) VALUES (?, ?, ?, ?, 'Customer')");
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param("ssss", $username, $password_hash, $full_name, $phone_number);
                
                if ($stmt->execute()) {
                    $success = 'Pendaftaran berjaya! Sila log masuk dengan akaun anda.';
                } else {
                    $error = 'Ralat semasa mendaftar. Sila cuba lagi.';
                }
            }
        } catch (Exception $e) {
            $error = 'Ralat sistem. Sila cuba lagi sebentar.';
            error_log($e->getMessage());
        }
    }
}

// Set page title
$page_title = 'Daftar Akaun - Armaya Enterprise';

// Include header
require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 mt-5">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Daftar Akaun Baru</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <br>
                            <a href="login.php" class="alert-link">Klik di sini untuk log masuk</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Nama Pengguna <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   pattern="[a-zA-Z0-9_]+" title="Hanya huruf, nombor dan underscore (_) dibenarkan"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <div class="form-text">Hanya huruf, nombor dan underscore (_) dibenarkan</div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nama Penuh <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Nombor Telefon <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" required
                                   pattern="[0-9\-\+]+" title="Sila masukkan nombor telefon yang sah"
                                   value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Kata Laluan <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   minlength="6">
                            <div class="form-text">Minimum 6 aksara</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Sahkan Kata Laluan <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                   minlength="6">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">Daftar</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p class="mb-0">Sudah mempunyai akaun?</p>
                        <a href="login.php" class="text-warning">Log masuk di sini</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add form validation script -->
<script>
(function () {
    'use strict'

    // Fetch all forms that need validation
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})()

// Add password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    if (this.value !== document.getElementById('password').value) {
        this.setCustomValidity('Kata laluan tidak sepadan');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    document.getElementById('confirm_password').dispatchEvent(new Event('input'));
});
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 