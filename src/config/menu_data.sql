-- Menu Items Data
INSERT INTO menu_items (name, category, subcategory, price_per_pax, min_pax, max_pax, serving_methods, event_types, is_available) VALUES
-- Nasi Category
('Nasi Lemak', 'Nasi', 'Tradisional', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Goreng', 'Nasi', 'Goreng', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih', 'Nasi', 'Tradisional', 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Ayam Hainan', 'Nasi', 'Ayam', 5.00, 1, 500, 'Buffet,Packed,Social', 'All', 1),
('Nasi Ayam Geprek', 'Nasi', 'Ayam', 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Ayam Gepuk', 'Nasi', 'Ayam', 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Ayam Penyet', 'Nasi', 'Ayam', 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Padang', 'Nasi', 'Tradisional', 7.00, 1, 300, 'Buffet,Packed,Social,Corporate', 'All', 1),

-- Mi/Bihun/Kuey Teow Category
('Kuey Teow Goreng', 'Mi/Bihun/Kuey Teow', 'Kuey Teow', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Mi Goreng', 'Mi/Bihun/Kuey Teow', 'Mi', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Maggi Goreng', 'Mi/Bihun/Kuey Teow', 'Mi', 3.00, 1, 1000, 'Buffet,Packed,Social', 'All', 1),
('Bihun Goreng', 'Mi/Bihun/Kuey Teow', 'Bihun', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),

-- Lauk Pauk Category
('Sambal Goreng', 'Lauk Pauk', 'Seafood', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Sambal Telur', 'Lauk Pauk', 'Telur', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Telur Goreng', 'Lauk Pauk', 'Telur', 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Sambal Sardin', 'Lauk Pauk', 'Ikan', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Sambal Ayam', 'Lauk Pauk', 'Ayam', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Kari Ayam', 'Lauk Pauk', 'Ayam', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Ayam Masak Lemak', 'Lauk Pauk', 'Ayam', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Ayam Goreng', 'Lauk Pauk', 'Ayam', 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Ayam Buttermilk', 'Lauk Pauk', 'Ayam', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),

-- Sayur Category
('Sayur Campur', 'Sayur', 'Campur', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Kangkung Goreng Belacan', 'Sayur', 'Goreng', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Terung Goreng Berlada', 'Sayur', 'Goreng', 3.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),

-- Air Category
('Air Teh O', 'Air', 'Teh', 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Air Sirap', 'Air', 'Sirap', 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Air Teh Tarik', 'Air', 'Teh', 1.50, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),

-- Package Category
('Nasi Lemak + Sambal Kerang', 'Package', 'Nasi Lemak', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Telur', 'Package', 'Nasi Lemak', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Sardin', 'Package', 'Nasi Lemak', 5.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Ayam', 'Package', 'Nasi Lemak', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Kerang + Air Teh O', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Telur + Air Teh O', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Sardin + Air Teh O', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Ayam + Air Teh O', 'Package', 'Nasi Lemak Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Kerang + Air Sirap', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Telur + Air Sirap', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Sardin + Air Sirap', 'Package', 'Nasi Lemak Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Lemak + Sambal Ayam + Air Sirap', 'Package', 'Nasi Lemak Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Goreng + Telur Goreng', 'Package', 'Goreng Set', 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Mi Goreng + Telur Goreng', 'Package', 'Goreng Set', 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Kuey Teow Goreng + Telur Goreng', 'Package', 'Goreng Set', 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Maggi Goreng + Telur Goreng', 'Package', 'Goreng Set', 4.00, 1, 1000, 'Buffet,Packed,Social', 'All', 1),
('Bihun Goreng + Telur Goreng', 'Package', 'Goreng Set', 4.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih + Sambal Ayam + Sayur Campur', 'Package', 'Nasi Putih Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih + Kari Ayam + Sayur Campur', 'Package', 'Nasi Putih Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih + Ayam Masak Lemak + Sayur Campur', 'Package', 'Nasi Putih Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih + Ayam Goreng + Sayur Campur', 'Package', 'Nasi Putih Set', 7.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1),
('Nasi Putih + Ayam Buttermilk', 'Package', 'Nasi Putih Set', 6.00, 1, 1000, 'Buffet,Packed,Social,Corporate', 'All', 1); 