-- CEK DULU PATH GAMBAR DI DATABASE
SELECT product_id, name, image FROM products;

-- Jika image mengandung 'uploads/', HAPUS
UPDATE products 
SET image = REPLACE(image, 'uploads/', '') 
WHERE image LIKE '%uploads/%';

-- Jika image mengandung 'assets/', HAPUS
UPDATE products 
SET image = REPLACE(image, 'assets/images/', '') 
WHERE image LIKE '%assets/images/%';

-- Jika image mengandung 'uploads/' di tengah, HAPUS
UPDATE products 
SET image = REPLACE(image, '/uploads/', '') 
WHERE image LIKE '%/uploads/%';

-- Set default image jika kosong
UPDATE products 
SET image = 'default-product.jpg' 
WHERE image = '' OR image IS NULL;