<?php 
require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/includes/db.php';
require_once __DIR__ . '/../src/includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php', ['type' => 'error', 'message' => 'Please log in to view the menu.']);
}
?>

<div class="container py-5">
    <h1 class="display-5 fw-bold text-center mb-5">Menu Katering</h1>

    <!-- Category Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-center flex-wrap gap-2">
                <?php
                // Fetch categories using PDO
                $sql = "SELECT DISTINCT category FROM menu_items ORDER BY category";
                $stmt = $pdo->query($sql);
                
                if ($stmt && $stmt->rowCount() > 0):
                    while ($row = $stmt->fetch()):
                ?>
                    <button class="btn btn-outline-warning category-btn" data-category="<?php echo escape($row['category']); ?>">
                        <?php echo escape($row['category']); ?>
                    </button>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- Menu Items Grid -->
    <div class="row g-4" id="menuGrid">
        <?php
        // Fetch menu items using PDO
        $sql = "SELECT * FROM menu_items ORDER BY category, name";
        $stmt = $pdo->query($sql);
        
        if ($stmt && $stmt->rowCount() > 0):
            while ($item = $stmt->fetch()):
        ?>
            <div class="col-md-6 col-lg-4 menu-item" data-category="<?php echo escape($item['category']); ?>">
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
                            <?php 
                            $details = [];
                            if (!empty($item['description'])) $details[] = $item['description'];
                            if (!empty($item['serving_methods'])) $details[] = $item['serving_methods'];
                            if (!empty($item['min_pax'])) $details[] = "Min: {$item['min_pax']} pax";
                            echo escape(implode(' • ', $details));
                            ?>
                        </p>
                        <p class="card-text fw-bold text-warning mb-3">
                            RM <?php echo number_format($item['price_per_pax'], 2); ?>
                            <small class="text-muted fw-normal">/pax</small>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button class="btn btn-outline-secondary decrease-quantity" type="button">-</button>
                                <input type="number" class="form-control text-center item-quantity" 
                                       value="0" min="<?php echo $item['min_pax']; ?>" max="<?php echo $item['max_pax']; ?>"
                                       data-item-id="<?php echo $item['item_id']; ?>">
                                <button class="btn btn-outline-secondary increase-quantity" type="button">+</button>
                            </div>
                            
                            <button class="btn btn-warning btn-sm add-to-cart"
                                    data-item-id="<?php echo $item['item_id']; ?>"
                                    data-item-name="<?php echo escape($item['name']); ?>"
                                    data-item-price="<?php echo $item['price_per_pax']; ?>">
                                Tambah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <div class="col-12 text-center">
                <p class="text-muted">Tiada menu tersedia pada masa ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Cart Button -->
    <div class="position-fixed bottom-0 end-0 m-4">
        <button class="btn btn-warning btn-lg rounded-circle shadow-lg" 
                id="cartButton"
                data-bs-toggle="modal" 
                data-bs-target="#cartModal">
            <i class="bi bi-cart-fill"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count">
                0
            </span>
        </button>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">Troli Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItems">
                        <!-- Cart items will be dynamically added here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Jumlah:</h5>
                            <h5 id="cartTotal">RM 0.00</h5>
                        </div>
                        <div class="d-grid">
                            <button type="button" class="btn btn-warning" id="proceedToOrder">
                                Teruskan ke Pesanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart from session storage if it exists
    let cart = JSON.parse(sessionStorage.getItem('cart') || '{}');
    updateCart(); // Update cart display on page load
    
    // Category filter
    const categoryButtons = document.querySelectorAll('.category-btn');
    const menuItems = document.querySelectorAll('.menu-item');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Toggle active state
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter items
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Quantity controls
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.item-quantity');
            const currentValue = parseInt(input.value);
            const minValue = parseInt(input.min);
            if (currentValue > minValue) {
                input.value = currentValue - 1;
            }
        });
    });

    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.item-quantity');
            const currentValue = parseInt(input.value);
            const maxValue = parseInt(input.max);
            if (currentValue < maxValue) {
                input.value = currentValue + 1;
            }
        });
    });

    // Add to cart
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            const itemPrice = parseFloat(this.dataset.itemPrice);
            const quantity = parseInt(this.closest('.card-body').querySelector('.item-quantity').value);
            const minPax = parseInt(this.closest('.card-body').querySelector('.item-quantity').min);

            if (quantity >= minPax) {
                if (cart[itemId]) {
                    cart[itemId].quantity = quantity; // Update quantity instead of adding
                } else {
                    cart[itemId] = {
                        name: itemName,
                        price: itemPrice,
                        quantity: quantity
                    };
                }
                updateCart();
                
                // Reset quantity input
                this.closest('.card-body').querySelector('.item-quantity').value = 0;
                
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '5';
                toast.innerHTML = `
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                Item ditambah ke troli!
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
                bsToast.show();
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            } else {
                alert(`Minimum pesanan untuk item ini adalah ${minPax} pax`);
            }
        });
    });

    // Update cart display
    function updateCart() {
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        const cartCount = document.querySelector('.cart-count');
        
        let total = 0;
        let count = 0;
        let html = '';

        for (const [itemId, item] of Object.entries(cart)) {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            count += item.quantity;
            
            html += `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="mb-0">${item.name}</h6>
                        <small class="text-muted">RM ${item.price.toFixed(2)} × ${item.quantity}</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-3">RM ${itemTotal.toFixed(2)}</span>
                        <button class="btn btn-sm btn-outline-danger remove-item" data-item-id="${itemId}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        cartItems.innerHTML = html || '<p class="text-center text-muted">Troli anda kosong</p>';
        cartTotal.textContent = `RM ${total.toFixed(2)}`;
        cartCount.textContent = count;
        
        // Store cart in session storage
        sessionStorage.setItem('cart', JSON.stringify(cart));
        
        // Update remove buttons
        document.querySelectorAll('.remove-item').forEach(button => {
            button.addEventListener('click', function() {
                delete cart[this.dataset.itemId];
                updateCart();
            });
        });
    }

    // Proceed to order
    document.getElementById('proceedToOrder').addEventListener('click', function() {
        if (Object.keys(cart).length === 0) {
            alert('Sila tambah item ke dalam troli terlebih dahulu.');
            return;
        }
        
        // Create a form to submit cart data
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'order.php';
        
        // Add cart data as hidden input
        const cartInput = document.createElement('input');
        cartInput.type = 'hidden';
        cartInput.name = 'cart';
        cartInput.value = JSON.stringify(cart);
        form.appendChild(cartInput);
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    });
});
</script>

<?php require_once __DIR__ . '/../src/includes/footer.php'; ?> 