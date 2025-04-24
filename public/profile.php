<?php
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view your profile.']);
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate required fields
    if (empty($full_name)) $errors[] = 'Nama penuh diperlukan';
    if (empty($phone_number)) $errors[] = 'Nombor telefon diperlukan';

    // Validate phone number format
    if (!empty($phone_number) && !validate_my_phone($phone_number)) {
        $errors[] = 'Format nombor telefon tidak sah';
    }

    // Check if phone number is taken by another user
    if (!empty($phone_number)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone_number = ? AND user_id != ?");
        $stmt->execute([$phone_number, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Nombor telefon telah digunakan';
        }
    }

    // Handle password change if requested
    if (!empty($current_password)) {
        if (empty($new_password)) {
            $errors[] = 'Kata laluan baharu diperlukan';
        } elseif (empty($confirm_password)) {
            $errors[] = 'Sila sahkan kata laluan baharu';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Kata laluan tidak sepadan';
        } elseif (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = 'Kata laluan semasa tidak sah';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update basic info
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, 
                    phone_number = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$full_name, $phone_number, $_SESSION['user_id']]);

            // Update password if requested
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$password_hash, $_SESSION['user_id']]);
            }

            $pdo->commit();
            
            // Update session data
            $_SESSION['user_name'] = $full_name;
            
            // Set success message
            $success_message = 'Profil berjaya dikemaskini.';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Ralat semasa mengemaskini profil: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

require_once __DIR__ . '/../src/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold text-center mb-5">Profil Saya</h1>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="card shadow-sm">
                <div class="card-body">
                    <!-- Basic Information -->
                    <h5 class="card-title mb-4">Maklumat Asas</h5>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Nama Pengguna</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               value="<?php echo escape($user['username']); ?>"
                               disabled>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Penuh</label>
                        <input type="text" 
                               class="form-control" 
                               id="full_name" 
                               name="full_name"
                               value="<?php echo escape($user['full_name']); ?>"
                               required>
                    </div>

                    <div class="mb-4">
                        <label for="phone_number" class="form-label">Nombor Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone_number" 
                               name="phone_number"
                               value="<?php echo escape($user['phone_number']); ?>"
                               required>
                    </div>

                    <!-- Password Change -->
                    <h5 class="card-title mb-4">Tukar Kata Laluan</h5>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Kata Laluan Semasa</label>
                        <input type="password" 
                               class="form-control" 
                               id="current_password" 
                               name="current_password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Kata Laluan Baharu</label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password">
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Sahkan Kata Laluan</label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password">
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 