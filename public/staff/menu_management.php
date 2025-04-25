<?php 
require_once __DIR__ . '/../../src/includes/functions.php';
require_once __DIR__ . '/../../src/includes/db.php';
require_once __DIR__ . '/../../src/includes/header.php';
require_once __DIR__ . '/../../src/includes/image_upload.php';
require_staff_login();

// Initialize variables
$error_message = '';
$success_message = '';
$item = [
    'name' => '',
    'category' => '',
    'subcategory' => '',
    'description' => '',
    'price_per_pax' => '',
    'min_pax' => 1,
    'max_pax' => 1000,
    'serving_methods' => '',
    'event_types' => '',
    'meal_tags' => '',
    'image_url' => '',
    'is_available' => 1
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $item = [
        'name' => $_POST['name'] ?? '',
        'category' => $_POST['category'] ?? '',
        'subcategory' => $_POST['subcategory'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price_per_pax' => $_POST['price_per_pax'] ?? '',
        'min_pax' => $_POST['min_pax'] ?? 1,
        'max_pax' => $_POST['max_pax'] ?? 1000,
        'serving_methods' => isset($_POST['serving_methods']) ? implode(',', $_POST['serving_methods']) : '',
        'event_types' => $_POST['event_types'] ?? '',
        'meal_tags' => $_POST['meal_tags'] ?? '',
        'is_available' => isset($_POST['is_available']) ? 1 : 0
    ];

    // Validate required fields
    $errors = [];
    if (empty($item['name'])) $errors[] = 'Nama menu diperlukan';
    if (empty($item['category'])) $errors[] = 'Kategori diperlukan';
    if (empty($item['subcategory'])) $errors[] = 'Subkategori diperlukan';
    if (empty($item['price_per_pax'])) $errors[] = 'Harga diperlukan';
    if (!is_numeric($item['price_per_pax'])) $errors[] = 'Harga mestilah nombor';
    if (!is_numeric($item['min_pax']) || $item['min_pax'] < 1) $errors[] = 'Minimum pax mestilah nombor positif';
    if (!is_numeric($item['max_pax']) || $item['max_pax'] < $item['min_pax']) $errors[] = 'Maximum pax mestilah lebih besar daripada minimum pax';

    if (empty($errors)) {
        // Start transaction
        $pdo->beginTransaction();

        try {
            // Handle image upload if a file was provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $item['image_url'] = handle_image_upload($_FILES['image']);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if (empty($errors)) {
                if ($action === 'edit' && !empty($_POST['item_id'])) {
                    // Get current item data
                    $stmt = $pdo->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
                    $stmt->execute([$_POST['item_id']]);
                    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Keep existing image if no new one was uploaded
                    if (!isset($item['image_url']) && $currentItem) {
                        $item['image_url'] = $currentItem['image_url'];
                    }

                    // Update existing item
                    $stmt = $pdo->prepare("
                        UPDATE menu_items 
                        SET name = ?, category = ?, subcategory = ?, description = ?, 
                            price_per_pax = ?, min_pax = ?, max_pax = ?, serving_methods = ?,
                            event_types = ?, meal_tags = ?, is_available = ?" . 
                            (isset($item['image_url']) ? ", image_url = ?" : "") . "
                        WHERE item_id = ?
                    ");

                    $params = [
                        $item['name'],
                        $item['category'],
                        $item['subcategory'],
                        $item['description'],
                        $item['price_per_pax'],
                        $item['min_pax'],
                        $item['max_pax'],
                        $item['serving_methods'],
                        $item['event_types'],
                        $item['meal_tags'],
                        $item['is_available']
                    ];
                    
                    if (isset($item['image_url'])) {
                        $params[] = $item['image_url'];
                    }
                    
                    $params[] = $_POST['item_id'];
                    $stmt->execute($params);
                    
                    $success_message = 'Menu berjaya dikemaskini.';
                } else {
                    // Insert new item
                    $stmt = $pdo->prepare("
                        INSERT INTO menu_items (
                            name, category, subcategory, description, price_per_pax, 
                            min_pax, max_pax, serving_methods, event_types, meal_tags,
                            image_url, is_available
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $item['name'],
                        $item['category'],
                        $item['subcategory'],
                        $item['description'],
                        $item['price_per_pax'],
                        $item['min_pax'],
                        $item['max_pax'],
                        $item['serving_methods'],
                        $item['event_types'],
                        $item['meal_tags'],
                        $item['image_url'] ?? null,
                        $item['is_available']
                    ]);
                    
                    $success_message = 'Menu berjaya ditambah.';
                }

                // Commit transaction
                $pdo->commit();
                
                // Clear form if it was a new item
                if ($action !== 'edit') {
                    $item = [
                        'name' => '',
                        'category' => '',
                        'subcategory' => '',
                        'description' => '',
                        'price_per_pax' => '',
                        'min_pax' => 1,
                        'max_pax' => 1000,
                        'serving_methods' => '',
                        'event_types' => '',
                        'meal_tags' => '',
                        'image_url' => '',
                        'is_available' => 1
                    ];
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error_message = 'Ralat semasa mengemaskini menu: ' . $e->getMessage();
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        // Check if the item has any orders
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as order_count 
            FROM order_items 
            WHERE item_id = ?
        ");
        $stmt->execute([$_POST['item_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['order_count'] > 0) {
            $error_message = 'Menu ini tidak boleh dipadam kerana ia mempunyai pesanan.';
        } else {
            // Get the image URL before deleting
            $stmt = $pdo->prepare("SELECT image_url FROM menu_items WHERE item_id = ?");
            $stmt->execute([$_POST['item_id']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            // Start transaction
            $pdo->beginTransaction();

            // Delete the menu item
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE item_id = ?");
            $stmt->execute([$_POST['item_id']]);

            // Delete the image file if it exists
            if (!empty($item['image_url'])) {
                $image_path = parse_url($item['image_url'], PHP_URL_PATH);
                $file_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $pdo->commit();
            $success_message = 'Menu berjaya dipadam.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = 'Ralat semasa memadam menu: ' . $e->getMessage();
    }
}

// Fetch categories
$stmt = $pdo->prepare("SELECT DISTINCT category FROM menu_items ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch menu items
$stmt = $pdo->prepare("
    SELECT 
        mi.item_id,
        mi.name,
        mi.description,
        mi.image_url,
        mi.price_per_pax,
        mi.min_pax,
        mi.max_pax,
        mi.serving_methods,
        mi.event_types,
        mi.meal_tags,
        mi.is_available,
        mi.created_at,
        mi.updated_at,
        mi.category,
        mi.subcategory,
        COUNT(DISTINCT oi.order_id) as order_count,
        SUM(oi.quantity) as total_quantity
    FROM menu_items mi
    LEFT JOIN order_items oi ON mi.item_id = oi.item_id
    GROUP BY mi.item_id, mi.name, mi.description, mi.image_url, mi.price_per_pax, 
             mi.min_pax, mi.max_pax, mi.serving_methods, mi.event_types, mi.meal_tags,
             mi.is_available, mi.created_at, mi.updated_at, mi.category, mi.subcategory
    ORDER BY mi.category, mi.subcategory, mi.name
");
$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0">Urus Menu</h1>
        
        <!-- Add New Item Button -->
        <button type="button" 
                class="btn btn-warning" 
                data-bs-toggle="modal" 
                data-bs-target="#itemModal">
            <i class="bi bi-plus-lg"></i> Tambah Menu
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

    <!-- Menu Items Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Menu</th>
                            <th>Kategori</th>
                            <th>Subkategori</th>
                            <th class="text-end">Harga/Pax</th>
                            <th class="text-center">Pax (Min-Max)</th>
                            <th>Kaedah Sajian</th>
                            <th>Jenis Acara</th>
                            <th>Tag Hidangan</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Pesanan</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menu_items as $menu_item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($menu_item['image_url'])): ?>
                                            <img src="<?php echo escape($menu_item['image_url']); ?>" 
                                                 alt="<?php echo escape($menu_item['name']); ?>"
                                                 class="rounded me-3"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo escape($menu_item['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo escape($menu_item['description'] ?: 'Tiada penerangan'); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                Dikemaskini: <?php echo date('d/m/Y H:i', strtotime($menu_item['updated_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo escape($menu_item['category']); ?></td>
                                <td><?php echo escape($menu_item['subcategory']); ?></td>
                                <td class="text-end">
                                    <strong class="text-warning">
                                        RM <?php echo number_format($menu_item['price_per_pax'], 2); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php echo $menu_item['min_pax']; ?> - <?php echo $menu_item['max_pax']; ?>
                                </td>
                                <td>
                                    <?php 
                                    $methods = explode(',', $menu_item['serving_methods']);
                                    foreach ($methods as $method): ?>
                                        <span class="badge bg-info"><?php echo escape($method); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php 
                                    $events = explode(',', $menu_item['event_types']);
                                    foreach ($events as $event): ?>
                                        <span class="badge bg-secondary"><?php echo escape($event); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if (!empty($menu_item['meal_tags'])): ?>
                                        <?php 
                                        $tags = explode(',', $menu_item['meal_tags']);
                                        foreach ($tags as $tag): ?>
                                            <span class="badge bg-primary"><?php echo escape($tag); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($menu_item['is_available']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo number_format($menu_item['order_count']); ?> pesanan
                                    <br>
                                    <small class="text-muted">
                                        <?php echo number_format($menu_item['total_quantity']); ?> unit
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning" 
                                                onclick="editItem(<?php echo htmlspecialchars(json_encode($menu_item)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?php echo $menu_item['item_id']; ?>, '<?php echo escape($menu_item['name']); ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="menu_management.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="item_id" id="item_id">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="itemModalLabel">Tambah Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Name -->
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Menu</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name"
                                       required>
                                <div class="invalid-feedback">Sila masukkan nama menu.</div>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Penerangan</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"></textarea>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="category" 
                                       name="category"
                                       list="categories"
                                       required>
                                <datalist id="categories">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo escape($category['category']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="invalid-feedback">Sila masukkan kategori.</div>
                            </div>

                            <!-- Subcategory -->
                            <div class="mb-3">
                                <label for="subcategory" class="form-label">Subkategori</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="subcategory" 
                                       name="subcategory"
                                       required>
                                <div class="invalid-feedback">Sila masukkan subkategori.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Price -->
                            <div class="mb-3">
                                <label for="price_per_pax" class="form-label">Harga Per Pax</label>
                                <div class="input-group">
                                    <span class="input-group-text">RM</span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="price_per_pax" 
                                           name="price_per_pax"
                                           step="0.01"
                                           required>
                                </div>
                                <div class="invalid-feedback">Sila masukkan harga.</div>
                            </div>

                            <!-- Min-Max Pax -->
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="min_pax" class="form-label">Minimum Pax</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="min_pax" 
                                               name="min_pax"
                                               value="1"
                                               min="1"
                                               required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="max_pax" class="form-label">Maximum Pax</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="max_pax" 
                                               name="max_pax"
                                               value="1000"
                                               min="1"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <!-- Serving Methods -->
                            <div class="mb-3">
                                <label class="form-label">Kaedah Sajian</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="method_buffet" name="serving_methods[]" value="Buffet">
                                    <label class="form-check-label" for="method_buffet">Buffet</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="method_packed" name="serving_methods[]" value="Packed">
                                    <label class="form-check-label" for="method_packed">Packed</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="method_social" name="serving_methods[]" value="Social">
                                    <label class="form-check-label" for="method_social">Social</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="method_corporate" name="serving_methods[]" value="Corporate">
                                    <label class="form-check-label" for="method_corporate">Corporate</label>
                                </div>
                            </div>

                            <!-- Event Types -->
                            <div class="mb-3">
                                <label for="event_types" class="form-label">Jenis Acara</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="event_types" 
                                       name="event_types"
                                       placeholder="Contoh: All, Wedding, Corporate, Social">
                                <div class="form-text">Pisahkan dengan koma untuk pelbagai jenis acara</div>
                            </div>

                            <!-- Meal Tags -->
                            <div class="mb-3">
                                <label for="meal_tags" class="form-label">Tag Hidangan</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="meal_tags" 
                                       name="meal_tags"
                                       placeholder="Contoh: Spicy, Vegetarian, Halal">
                                <div class="form-text">Pisahkan dengan koma untuk pelbagai tag</div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label for="image" class="form-label">Gambar Menu</label>
                        <input type="file" 
                               class="form-control" 
                               id="image" 
                               name="image"
                               accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Format yang dibenarkan: JPG, PNG, GIF. Saiz maksimum: 5MB</div>
                    </div>

                    <!-- Active Status -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_available" 
                                   name="is_available"
                                   value="1"
                                   checked>
                            <label class="form-check-label" for="is_available">Aktif</label>
                        </div>
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

// Edit item
function editItem(item) {
    console.log('Edit item:', item); // Debug log
    
    // Set the form action to edit
    document.querySelector('#itemModal form input[name="action"]').value = 'edit';
    
    // Populate the form fields
    document.getElementById('item_id').value = item.item_id;
    document.getElementById('name').value = item.name;
    document.getElementById('description').value = item.description || '';
    document.getElementById('category').value = item.category;
    document.getElementById('subcategory').value = item.subcategory;
    document.getElementById('price_per_pax').value = item.price_per_pax;
    document.getElementById('min_pax').value = item.min_pax;
    document.getElementById('max_pax').value = item.max_pax;
    document.getElementById('event_types').value = item.event_types;
    document.getElementById('meal_tags').value = item.meal_tags || '';
    document.getElementById('is_available').checked = item.is_available == 1;

    // Handle serving methods checkboxes
    const methods = item.serving_methods ? item.serving_methods.split(',') : [];
    document.getElementById('method_buffet').checked = methods.includes('Buffet');
    document.getElementById('method_packed').checked = methods.includes('Packed');
    document.getElementById('method_social').checked = methods.includes('Social');
    document.getElementById('method_corporate').checked = methods.includes('Corporate');
    
    // Update modal title
    document.getElementById('itemModalLabel').textContent = 'Edit Menu';
    
    // Show preview of existing image if available
    if (item.image_url) {
        const previewImg = document.createElement('img');
        previewImg.src = item.image_url;
        previewImg.alt = 'Current Image';
        previewImg.className = 'img-thumbnail mt-2';
        previewImg.style.maxWidth = '200px';
        
        const imageContainer = document.querySelector('#itemModal .modal-body .mb-3:has(#image)');
        const existingPreview = imageContainer.querySelector('img');
        if (existingPreview) {
            existingPreview.remove();
        }
        imageContainer.appendChild(previewImg);
    }
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('itemModal'));
    modal.show();
}

// Reset form when modal is closed
document.getElementById('itemModal').addEventListener('hidden.bs.modal', function() {
    const form = this.querySelector('form');
    form.reset();
    
    // Reset hidden fields and specific values
    document.getElementById('item_id').value = '';
    document.getElementById('min_pax').value = '1';
    document.getElementById('max_pax').value = '1000';
    document.querySelector('#itemModal form input[name="action"]').value = 'add';
    
    // Reset serving methods
    document.getElementById('method_buffet').checked = false;
    document.getElementById('method_packed').checked = false;
    document.getElementById('method_social').checked = false;
    document.getElementById('method_corporate').checked = false;
    
    // Reset modal title
    document.getElementById('itemModalLabel').textContent = 'Tambah Menu';
    
    // Remove validation classes
    form.classList.remove('was-validated');
    
    // Remove image preview if exists
    const imageContainer = document.querySelector('#itemModal .modal-body .mb-3:has(#image)');
    const existingPreview = imageContainer.querySelector('img');
    if (existingPreview) {
        existingPreview.remove();
    }
});

// Preview image before upload
function previewImage(input) {
    const imageContainer = input.parentElement;
    let previewImg = imageContainer.querySelector('img');
    
    if (!previewImg) {
        previewImg = document.createElement('img');
        previewImg.className = 'img-thumbnail mt-2';
        previewImg.style.maxWidth = '200px';
        imageContainer.appendChild(previewImg);
    }
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.alt = 'Preview';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Add event listener for image preview
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('image').addEventListener('change', function() {
        previewImage(this);
    });
});

function confirmDelete(itemId, itemName) {
    if (confirm('Adakah anda pasti mahu memadam menu "' + itemName + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="item_id" value="${itemId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../../src/includes/footer.php'; ?> 