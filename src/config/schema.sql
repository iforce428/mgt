-- Insert menu items
INSERT INTO menu_items (name, category, subcategory, price, min_pax, is_active) VALUES
-- Nasi Category
('Nasi Lemak', 'Nasi', NULL, 3.00, 1000, 1),
('Nasi Goreng', 'Nasi', NULL, 3.00, 1000, 1),
('Nasi Putih', 'Nasi', NULL, 1.50, 1000, 1),
('Nasi Ayam Hainan', 'Nasi', 'Ayam', 5.00, 500, 1),
('Nasi Ayam Geprek', 'Nasi', 'Ayam', 7.00, 300, 1),
('Nasi Ayam Gepuk', 'Nasi', 'Ayam', 7.00, 300, 1),
('Nasi Ayam Penyet', 'Nasi', 'Ayam', 7.00, 300, 1),
('Nasi Padang', 'Nasi', NULL, 7.00, 300, 1),

-- Mi/Bihun/Kuey Teow Category
('Kuey Teow Goreng', 'Mi/Bihun/Kuey Teow', NULL, 3.00, 1000, 1),
('Mi Goreng', 'Mi/Bihun/Kuey Teow', NULL, 3.00, 1000, 1),
('Maggi Goreng', 'Mi/Bihun/Kuey Teow', NULL, 3.00, 1000, 1),
('Bihun Goreng', 'Mi/Bihun/Kuey Teow', NULL, 3.00, 1000, 1),

-- Lauk Pauk Category
('Sambal Goreng', 'Lauk Pauk', 'Seafood', 3.00, 1000, 1),
('Sambal Telur', 'Lauk Pauk', 'Telur', 3.00, 1000, 1),
('Telur Goreng', 'Lauk Pauk', 'Telur', 1.50, 1000, 1),
('Sambal Sardin', 'Lauk Pauk', 'Ikan', 3.00, 1000, 1),
('Sambal Ayam', 'Lauk Pauk', 'Ayam', 5.00, 1000, 1),
('Kari Ayam', 'Lauk Pauk', 'Ayam', 5.00, 1000, 1),
('Ayam Masak Lemak', 'Lauk Pauk', 'Ayam', 5.00, 1000, 1),
('Ayam Goreng', 'Lauk Pauk', 'Ayam', 4.00, 1000, 1),
('Ayam Buttermilk', 'Lauk Pauk', 'Ayam', 5.00, 1000, 1),

-- Sayur Category
('Sayur Campur', 'Sayur', NULL, 3.00, 1000, 1),
('Kangkung Goreng Belacan', 'Sayur', NULL, 3.00, 1000, 1),
('Terung Goreng Berlada', 'Sayur', NULL, 3.00, 1000, 1),

-- Air Category
('Air Teh O', 'Air', NULL, 1.50, 1000, 1),
('Air Sirap', 'Air', NULL, 1.50, 1000, 1),
('Air Teh Tarik', 'Air', NULL, 1.50, 1000, 1),

-- Package Category
('Nasi Lemak + Sambal Kerang', 'Package', NULL, 5.00, 1000, 1),
('Nasi Lemak + Sambal Telur', 'Package', NULL, 5.00, 1000, 1),
('Nasi Lemak + Sambal Sardin', 'Package', NULL, 5.00, 1000, 1),
('Nasi Lemak + Sambal Ayam', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Kerang + Air Teh O', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Telur + Air Teh O', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Sardin + Air Teh O', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Ayam + Air Teh O', 'Package', NULL, 7.00, 1000, 1),
('Nasi Lemak + Sambal Kerang + Air Sirap', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Telur + Air Sirap', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Sardin + Air Sirap', 'Package', NULL, 6.00, 1000, 1),
('Nasi Lemak + Sambal Ayam + Air Sirap', 'Package', NULL, 7.00, 1000, 1),
('Nasi Goreng + Telur Goreng', 'Package', NULL, 4.00, 1000, 1),
('Mi Goreng + Telur Goreng', 'Package', NULL, 4.00, 1000, 1),
('Kuey Teow Goreng + Telur Goreng', 'Package', NULL, 4.00, 1000, 1),
('Maggi Goreng + Telur Goreng', 'Package', NULL, 4.00, 1000, 1),
('Bihun Goreng + Telur Goreng', 'Package', NULL, 4.00, 1000, 1),
('Nasi Putih + Sambal Ayam + Sayur Campur', 'Package', NULL, 7.00, 1000, 1),
('Nasi Putih + Kari Ayam + Sayur Campur', 'Package', NULL, 7.00, 1000, 1),
('Nasi Putih + Ayam Masak Lemak + Sayur Campur', 'Package', NULL, 7.00, 1000, 1),
('Nasi Putih + Ayam Goreng + Sayur Campur', 'Package', NULL, 7.00, 1000, 1),
('Nasi Putih + Ayam Buttermilk', 'Package', NULL, 6.00, 1000, 1);

-- Add serving types for each menu item
INSERT INTO menu_serving_types (item_id, serving_type) 
SELECT item_id, 'Buffet' FROM menu_items;

INSERT INTO menu_serving_types (item_id, serving_type) 
SELECT item_id, 'Packed' FROM menu_items;

INSERT INTO menu_serving_types (item_id, serving_type) 
SELECT item_id, 'Social' FROM menu_items;

INSERT INTO menu_serving_types (item_id, serving_type) 
SELECT item_id, 'Corporate' FROM menu_items; 