<?php 
require_once __DIR__ . '/../../src/includes/header.php';
require_staff_login();

// Initialize variables
$error_message = '';
$success_message = '';
$user = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'Customer'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = [
        'username' => $_POST['username'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'role' => $_POST['role'] ?? 'Customer'
    ];
    $password = $_POST['password'] ?? '';

    // Validate required fields
    $errors = [];
    if (empty($user['username'])) $errors[] = 'Nama pengguna diperlukan';
    if (empty($user['full_name'])) $errors[] = 'Nama penuh diperlukan';
    if (empty($user['email'])) $errors[] = 'Emel diperlukan';
    if (empty($user['phone'])) $errors[] = 'Nombor telefon diperlukan';
    if (empty($password) && !isset($_POST['user_id'])) $errors[] = 'Kata laluan diperlukan';

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            if (isset($_POST['user_id'])) {
                // Update existing user
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?";
                $params = [$user['full_name'], $user['email'], $user['phone'], $user['role']];
                $types = "ssss";

                if (!empty($password)) {
                    $sql .= ", password_hash = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                    $types .= "s";
                }

                $sql .= " WHERE user_id = ?";
                $params[] = $_POST['user_id'];
                $types .= "i";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
            } else {
                // Insert new user
                $stmt = $conn->prepare("
                    INSERT INTO users (
                        username, password_hash, full_name, email, phone, role
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param(
                    "ssssss",
                    $user['username'],
                    $password_hash,
                    $user['full_name'],
                    $user['email'],
                    $user['phone'],
                    $user['role']
                );
            }

            $stmt->execute();

            // Commit transaction
            $conn->commit();

            $success_message = 'Pengguna berjaya dikemaskini.';
            
            // Clear form if it was a new user
            if (!isset($_POST['user_id'])) {
                $user = [
                    'username' => '',
                    'full_name' => '',
                    'email' => '',
                    'phone' => '',
                    'role' => 'Customer'
                ];
            }

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = 'Ralat semasa mengemaskini pengguna. Sila cuba lagi.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Fetch users
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT o.order_id) as order_count,
           SUM(o.total_amount) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    GROUP BY u.user_id
    ORDER BY u.role, u.full_name
");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Urus Pengguna</h1>
        
        <!-- Add New User Button -->
        <button type="button" 
                class="btn btn-warning" 
                data-bs-toggle="modal" 
                data-bs-target="#userModal">
            <i class="bi bi-plus-lg"></i> Tambah Pengguna
        </button>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo escape($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo escape($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Pengguna</th>
                            <th class="border-0">Emel</th>
                            <th class="border-0">Telefon</th>
                            <th class="border-0 text-center">Peranan</th>
                            <th class="border-0 text-center">Pesanan</th>
                            <th class="border-0">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user_data): ?>
                            <tr>
                                <td>
                                    <h6 class="mb-0"><?php echo escape($user_data['full_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo escape($user_data['username']); ?>
                                    </small>
                                </td>
                                <td><?php echo escape($user_data['email']); ?></td>
                                <td><?php echo escape($user_data['phone']); ?></td>
                                <td class="text-center">
                                    <?php
                                    $role_class = [
                                        'Admin' => 'danger',
                                        'Staff' => 'warning',
                                        'Customer' => 'success'
                                    ][$user_data['role']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $role_class; ?>">
                                        <?php echo $user_data['role']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($user_data['order_count']); ?> pesanan
                                    <br>
                                    <small class="text-muted">
                                        RM <?php echo number_format($user_data['total_spent'], 2); ?>
                                    </small>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user_data)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="user_management.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Nama Pengguna</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username"
                               value="<?php echo escape($user['username']); ?>" 
                               required>
                        <div class="invalid-feedback">Sila masukkan nama pengguna.</div>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Penuh</label>
                        <input type="text" 
                               class="form-control" 
                               id="full_name" 
                               name="full_name"
                               value="<?php echo escape($user['full_name']); ?>" 
                               required>
                        <div class="invalid-feedback">Sila masukkan nama penuh.</div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Emel</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?php echo escape($user['email']); ?>" 
                               required>
                        <div class="invalid-feedback">Sila masukkan emel yang sah.</div>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Nombor Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone"
                               value="<?php echo escape($user['phone']); ?>" 
                               required>
                        <div class="invalid-feedback">Sila masukkan nombor telefon.</div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Kata Laluan</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password">
                        <div class="form-text">
                            Biarkan kosong jika tidak mahu menukar kata laluan.
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="mb-3">
                        <label for="role" class="form-label">Peranan</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="Customer" <?php echo $user['role'] === 'Customer' ? 'selected' : ''; ?>>
                                Pelanggan
                            </option>
                            <option value="Staff" <?php echo $user['role'] === 'Staff' ? 'selected' : ''; ?>>
                                Kakitangan
                            </option>
                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>
                                Pentadbir
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Edit user
function editUser(user) {
    document.getElementById('user_id').value = user.user_id;
    document.getElementById('username').value = user.username;
    document.getElementById('full_name').value = user.full_name;
    document.getElementById('email').value = user.email;
    document.getElementById('phone').value = user.phone;
    document.getElementById('role').value = user.role;
    document.getElementById('password').value = '';
    
    document.getElementById('userModalLabel').textContent = 'Edit Pengguna';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

// Reset form when modal is closed
document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('user_id').value = '';
    document.getElementById('username').value = '';
    document.getElementById('full_name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('password').value = '';
    document.getElementById('role').value = 'Customer';
    document.getElementById('userModalLabel').textContent = 'Tambah Pengguna';
});
</script>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 