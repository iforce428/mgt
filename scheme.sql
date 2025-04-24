-- Database: armaya_catering

-- Users Table (Handles Admin, Staff, Customer)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Store hashed passwords!
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    role ENUM('Admin', 'Staff', 'Customer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu Items Table
CREATE TABLE menu_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL, -- Added category column
    image_url VARCHAR(255), -- Path to the item image
    price_per_pax DECIMAL(10, 2) NOT NULL,
    min_pax INT DEFAULT 1,
    max_pax INT DEFAULT 1000, -- Set a reasonable upper limit
    serving_methods VARCHAR(100), -- Store as comma-separated or JSON: e.g., "Buffet,Packed" or '["Buffet", "Packed"]'
    event_types VARCHAR(255), -- Store as comma-separated or JSON: e.g., "Wedding,Meeting" or '["Wedding", "Meeting"]'
    meal_tags VARCHAR(255), -- Store as comma-separated or JSON: e.g., "Ayam,Pedas,Nasi" or '["Ayam", "Pedas", "Nasi"]'
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_date DATETIME NOT NULL, -- Date and Time the order is *for*
    total_pax INT NOT NULL,
    delivery_option ENUM('Ambil Sendiri', 'Penghantaran') NOT NULL,
    delivery_location VARCHAR(255) NULL, -- Required if delivery_option is 'Penghantaran'
    serving_method ENUM('Hidang (Buffet)', 'Bungkus') NOT NULL,
    event_type VARCHAR(50), -- Optional, based on customer input perhaps during recommendation/ordering
    budget_per_pax DECIMAL(10, 2) NULL, -- Store the budget constraint used, if any
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Completed', 'Cancelled') DEFAULT 'Pending',
    placed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    staff_notes TEXT NULL, -- Notes added by staff
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE -- Or ON DELETE RESTRICT depending on policy
);

-- Order Items Table (Links orders to menu items)
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL, -- Usually corresponds to total_pax for catering, but could differ for specific items
    price_at_order DECIMAL(10, 2) NOT NULL, -- Store the price per pax at the time of ordering
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(item_id) ON DELETE RESTRICT -- Prevent deleting menu items if they are in orders
);

-- Financial Records Table (Simplified Example)
-- You might want a more robust double-entry bookkeeping system eventually
CREATE TABLE financial_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    record_date DATE NOT NULL,
    description VARCHAR(255) NOT NULL,
    type ENUM('Income', 'Expense') NOT NULL,
    category VARCHAR(50), -- e.g., 'Sales', 'Touch N Go', 'Gaji Pekerja', 'Kos Bahan Mentah', 'Kos Overhead'
    amount DECIMAL(10, 2) NOT NULL,
    related_order_id INT NULL, -- Link sales income to a specific order
    recorded_by_staff_id INT NULL, -- Who recorded this?
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by_staff_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Add some initial data (Example Admin/Staff/Customer, Menu Items)
-- HASH YOUR PASSWORDS PROPERLY! This is just an example. Use password_hash() in PHP.
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$ExampleHash...', 'Admin User', 'Admin'),
('staf1', '$2y$10$ExampleHash...', 'Staff One', 'Staff'),
('anis', '$2y$10$ExampleHash...', 'Anis Syahira Binti Zulkefli', 'Customer');

INSERT INTO menu_items (name, description, category, image_url, price_per_pax, serving_methods, event_types, meal_tags) VALUES
('Nasi Lemak Ayam', 'Nasi lemak with fried chicken', 'Nasi', 'images/nasi_lemak_ayam.jpg', 5.00, 'Packed,Buffet', 'Casual,Meeting', 'Nasi,Ayam,Pedas'),
('Nasi Ayam Hainan', 'Hainanese chicken rice', 'Nasi', 'images/nasi_ayam_hainan.jpg', 5.00, 'Packed,Buffet', 'Casual,Meeting', 'Nasi,Ayam,Sup'),
('Nasi Goreng', 'Fried rice', 'Nasi', 'images/nasi_goreng.jpg', 4.00, 'Packed,Buffet', 'Casual,Meeting', 'Nasi,Goreng'),
('Mi Goreng', 'Fried noodles', 'Mi', 'images/mi_goreng.jpg', 4.00, 'Packed,Buffet', 'Casual,Meeting', 'Mi,Goreng,Pedas'),
('Bihun Goreng', 'Fried rice vermicelli', 'Mi', 'images/bihun_goreng.jpg', 4.00, 'Packed,Buffet', 'Casual,Meeting', 'Bihun,Goreng'),
('Nasi Ayam Penyet', 'Smashed fried chicken with rice', 'Nasi', 'images/nasi_ayam_penyet.jpg', 7.00, 'Packed,Buffet', 'Casual,Meeting,Wedding', 'Nasi,Ayam,Pedas'),
('Bakso', 'Indonesian meatball soup', 'Sup', 'images/bakso.jpg', 6.00, 'Buffet', 'Casual', 'Sup,Daging'),
('Nasi Padang', 'Padang style rice with various side dishes', 'Nasi', 'images/nasi_padang.jpg', 7.00, 'Buffet', 'Casual,Wedding', 'Nasi,Daging,Ayam,Sayur,Pedas');
