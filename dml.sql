-- Tech Axis DB DML designed and authored by Rayyan (240212160)
-- Data provided by Euan (240051695)


BEGIN;


-- upsert product_images WITHOUT ON CONFLICT
-- (this is needed because UNIQUE(product_id, sort_order) is DEFERRABLE)
CREATE OR REPLACE FUNCTION fn_upsert_product_image(
  p_product_slug TEXT,
  p_sort_order   INT,
  p_url          TEXT,
  p_alt_text     TEXT
) RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
  v_product_id BIGINT;
BEGIN
  SELECT id INTO v_product_id FROM products WHERE slug = p_product_slug;

  IF v_product_id IS NULL THEN
    RAISE EXCEPTION 'Product slug "%" not found (cannot insert product_images).', p_product_slug;
  END IF;

  -- Try update first
  UPDATE product_images
     SET url = p_url,
         alt_text = p_alt_text
   WHERE product_id = v_product_id
     AND sort_order = p_sort_order;

  -- If nothing updated, insert
  IF NOT FOUND THEN
    INSERT INTO product_images (product_id, url, alt_text, sort_order)
    SELECT v_product_id, p_url, p_alt_text, p_sort_order
    WHERE NOT EXISTS (
      SELECT 1 FROM product_images
      WHERE product_id = v_product_id AND sort_order = p_sort_order
    );
  END IF;
END;
$$;


-- Brands
INSERT INTO brands (name, slug, description)
VALUES
  ('Sony','sony','Japanese multinational conglomerate specializing in gaming and electronics'),
  ('Microsoft','microsoft','American technology company known for Xbox consoles'),
  ('Corsair','corsair','American computer peripherals and hardware manufacturer'),
  ('ASUS','asus','Taiwanese computer hardware and electronics company'),
  ('Logitech','logitech','Swiss manufacturer of computer peripherals'),
  ('Razer','razer','Global gaming hardware and software company'),
  ('SteelSeries','steelseries','Danish manufacturer of gaming peripherals'),
  ('Tech Axis','tech-axis','Your own gaming merchandise brand'),
  ('Nintendo','nintendo','Japanese gaming company behind Switch and Mario'),
  ('Samsung','samsung','South Korean electronics manufacturer'),
  ('Apple','apple','American technology company known for iPhone and Mac'),
  ('Anker','anker','Electronics brand specializing in charging technology')
ON CONFLICT (slug) DO UPDATE
SET name = EXCLUDED.name,
    description = EXCLUDED.description;


-- Categories
INSERT INTO categories (parent_id, name, slug, description, sort_order)
VALUES
  (NULL,'Consoles & Accessories','consoles-accessories','Gaming consoles and related accessories',0),
  (NULL,'PC Gaming','pc-gaming','PC gaming hardware and peripherals',1),
  (NULL,'Merchandise','merchandise','Branded clothing and collectibles',2),
  (NULL,'PC Components','pc-components','Computer hardware and upgrade parts',3),
  (NULL,'Phones & Gadgets','phones-gadgets','Mobile devices and tech gadgets',4),
  (NULL,'Controllers','controllers','Game controllers and input devices',5)
ON CONFLICT (slug) DO UPDATE
SET name = EXCLUDED.name,
    description = EXCLUDED.description,
    sort_order = EXCLUDED.sort_order;


-- Option types
INSERT INTO option_types (name)
VALUES ('Colour'),('Size'),('Storage')
ON CONFLICT (name) DO NOTHING;


-- Optional values 


-- Colour
INSERT INTO option_values (option_type_id, value)
SELECT ot.id, v.value
FROM option_types ot
CROSS JOIN (VALUES ('Black'),('White'),('Grey'),('Red'),('Blue')) AS v(value)
WHERE ot.name = 'Colour'
ON CONFLICT (option_type_id, value) DO NOTHING;


-- Size
INSERT INTO option_values (option_type_id, value)
SELECT ot.id, v.value
FROM option_types ot
CROSS JOIN (VALUES ('S'),('M'),('L'),('XL')) AS v(value)
WHERE ot.name = 'Size'
ON CONFLICT (option_type_id, value) DO NOTHING;


-- Storage
INSERT INTO option_values (option_type_id, value)
SELECT ot.id, v.value
FROM option_types ot
CROSS JOIN (VALUES ('256GB'),('512GB'),('1TB'),('2TB')) AS v(value)
WHERE ot.name = 'Storage'
ON CONFLICT (option_type_id, value) DO NOTHING;


-- Products


-- PlayStation 5 Console
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='sony'),
  'PlayStation 5 Console',
  'playstation-5-console',
  'PS5-DISC-001',
  'active',
  'Next-gen gaming console with lightning-fast SSD',
  'Experience lightning-fast loading with an ultra-high speed SSD, deeper immersion with support for haptic feedback, adaptive triggers, and 3D Audio.',
  449.99,
  50,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for PS5
SELECT fn_upsert_product_image('playstation-5-console', 0, 'https://media.currys.biz/i/currysprod/10291988?$l-large$&fmt=auto', 'PlayStation 5 Console - front view');
SELECT fn_upsert_product_image('playstation-5-console', 1, 'https://media.currys.biz/i/currysprod/10291988_001?$l-large$&fmt=auto', 'PlayStation 5 Console - angled view');
SELECT fn_upsert_product_image('playstation-5-console', 2, 'https://media.currys.biz/i/currysprod/10291988_005?$l-large$&fmt=auto', 'PlayStation 5 Console - retail box');


-- PlayStation 5 Pro Console
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='sony'),
  'PlayStation 5 Pro Console',
  'playstation-5-pro-console',
  'PS5-PRO-001',
  'active',
  'Upgraded PS5 with enhanced performance for faster frame rates and higher fidelity',
  'Pro model featuring upgraded GPU performance and additional optimizations for smoother gameplay and sharper visuals in supported titles.',
  699.99,
  20,
  FALSE,
  3
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for PS5 Pro
SELECT fn_upsert_product_image('playstation-5-pro-console', 0, 'https://media.currys.biz/i/currysprod/10272138?$l-large$&fmt=auto', 'PlayStation 5 Pro Console - front view');
SELECT fn_upsert_product_image('playstation-5-pro-console', 1, 'https://media.currys.biz/i/currysprod/10272138_005?$l-large$&fmt=auto', 'PlayStation 5 Pro Console - angled view');
SELECT fn_upsert_product_image('playstation-5-pro-console', 2, 'https://media.currys.biz/i/currysprod/10272138_003?$l-large$&fmt=auto', 'PlayStation 5 Pro Console - on vertical stand (not included)');



-- DualSense Wireless Controller
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='sony'),
  'DualSense Wireless Controller',
  'dualsense-wireless-controller',
  NULL,
  'active',
  'Immersive PS5 controller with haptic feedback',
  'Experience adaptive triggers and haptic feedback with DualSense wireless controller.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Product option types for DualSense
INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour')
WHERE p.slug='dualsense-wireless-controller'
ON CONFLICT DO NOTHING;

-- Variants for DualSense
INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='dualsense-wireless-controller'), 'DUALSENSE-WHITE', 'DualSense Wireless Controller - White', 69.99, 35, 5),
  ((SELECT id FROM products WHERE slug='dualsense-wireless-controller'), 'DUALSENSE-BLACK', 'DualSense Wireless Controller - Black', 69.99, 30, 5),
  ((SELECT id FROM products WHERE slug='dualsense-wireless-controller'), 'DUALSENSE-RED',   'DualSense Wireless Controller - Red',   74.99, 18, 5),
  ((SELECT id FROM products WHERE slug='dualsense-wireless-controller'), 'DUALSENSE-BLUE',  'DualSense Wireless Controller - Blue',  74.99, 16, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Variant option values for DualSense
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='White'
WHERE v.sku='DUALSENSE-WHITE'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Black'
WHERE v.sku='DUALSENSE-BLACK'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Red'
WHERE v.sku='DUALSENSE-RED'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Blue'
WHERE v.sku='DUALSENSE-BLUE'
ON CONFLICT DO NOTHING;

-- Images for DualSense
SELECT fn_upsert_product_image('dualsense-wireless-controller', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/dualsense-white.jpg', 'DualSense Wireless Controller - white');
SELECT fn_upsert_product_image('dualsense-wireless-controller', 1, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/dualsense-black.jpg', 'DualSense Wireless Controller - black');
SELECT fn_upsert_product_image('dualsense-wireless-controller', 2, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/dualsense-red.jpg', 'DualSense Wireless Controller - red');
SELECT fn_upsert_product_image('dualsense-wireless-controller', 3, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/dualsense-blue.jpg', 'DualSense Wireless Controller - blue');



-- Xbox Series X
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='microsoft'),
  'Xbox Series X',
  'xbox-series-x',
  NULL,
  'active',
  'Most powerful Xbox ever',
  'The fastest, most powerful Xbox ever with next-gen speed and performance, backwards compatibility, and thousands of games.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;


-- Product option types for Xbox
INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour')
WHERE p.slug='xbox-series-x'
ON CONFLICT DO NOTHING;


-- Variants for Xbox
INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='xbox-series-x'), 'XBOX-BLACK', 'Xbox Series X - Black', 449.99, 30, 5),
  ((SELECT id FROM products WHERE slug='xbox-series-x'), 'XBOX-WHITE', 'Xbox Series X - White', 449.99, 25, 5),
  ((SELECT id FROM products WHERE slug='xbox-series-x'), 'XBOX-RED',   'Xbox Series X - Red',   469.99, 10, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;


-- Variant option values for Xbox
-- Black
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Black'
WHERE v.sku='XBOX-BLACK'
ON CONFLICT DO NOTHING;

-- White
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='White'
WHERE v.sku='XBOX-WHITE'
ON CONFLICT DO NOTHING;

-- Red
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Red'
WHERE v.sku='XBOX-RED'
ON CONFLICT DO NOTHING;

-- Images for Xbox Series X base product
SELECT fn_upsert_product_image('xbox-series-x', 0, 'https://media.currys.biz/i/currysprod/10203371?$l-large$&fmt=auto', 'Xbox Series X - black front view');
SELECT fn_upsert_product_image('xbox-series-x', 1, 'https://media.currys.biz/i/currysprod/10269628?$l-large$&fmt=auto', 'Xbox Series X - white front view');
SELECT fn_upsert_product_image('xbox-series-x', 2, 'https://media.currys.biz/i/currysprod/10203371_008?$l-large$&fmt=auto', 'Xbox Series X - airflow');


-- Quantum Pro Gaming Mouse
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='logitech'),
  'Quantum Pro Gaming Mouse',
  'quantum-pro-gaming-mouse',
  'LOGITECH-MOUSE-001',
  'active',
  'High-precision RGB gaming mouse',
  'High-precision RGB gaming mouse with customizable buttons and advanced tracking technology.',
  79.99,
  100,
  FALSE,
  10
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for Mouse
SELECT fn_upsert_product_image('quantum-pro-gaming-mouse', 0, 'https://resource.logitechg.com/w_544,h_466,ar_7:6,c_pad,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/pro-wireless-gaming-mouse/pro-wireless-carbon-gallery-1.png', 'Quantum Pro Gaming Mouse - main');
SELECT fn_upsert_product_image('quantum-pro-gaming-mouse', 1, 'https://resource.logitechg.com/w_544,h_466,ar_7:6,c_pad,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/pro-wireless-gaming-mouse/pro-wireless-carbon-gallery-4.png', 'Quantum Pro Gaming Mouse - side buttons');
SELECT fn_upsert_product_image('quantum-pro-gaming-mouse', 2, 'https://resource.logitechg.com/w_544,h_466,ar_7:6,c_pad,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/pro-wireless-gaming-mouse/pro-wireless-carbon-gallery-5.png', 'Quantum Pro Gaming Mouse - sensor');


-- Corsair K100 RGB Mechanical Keyboard 
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='corsair'),
  'Corsair K100 RGB Mechanical Keyboard',
  'corsair-k100-rgb-mechanical-keyboard',
  NULL,
  'active',
  'Gaming keyboard with OPX optical-mechanical switches',
  'Corsair K100 RGB gaming keyboard with OPX optical-mechanical switches for ultra-fast response times.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Product option types for Keyboard
INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour')
WHERE p.slug='corsair-k100-rgb-mechanical-keyboard'
ON CONFLICT DO NOTHING;

-- Variants for Keyboard
INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='corsair-k100-rgb-mechanical-keyboard'), 'K100-BLACK', 'Corsair K100 - Black', 129.99, 25, 5),
  ((SELECT id FROM products WHERE slug='corsair-k100-rgb-mechanical-keyboard'), 'K100-WHITE', 'Corsair K100 - White', 129.99, 15, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Variant option values for Keyboard
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Black'
WHERE v.sku='K100-BLACK'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='White'
WHERE v.sku='K100-WHITE'
ON CONFLICT DO NOTHING;

-- Images for Keyboard
SELECT fn_upsert_product_image('corsair-k100-rgb-mechanical-keyboard', 0, 'https://assets.corsair.com/image/upload/c_pad,q_85,h_1100,w_1100,f_auto/products/Gaming-Keyboards/CH-912A01A-UK/Gallery/K100_RGB_RENDER_UK_03.webp', 'Corsair K100 RGB Keyboard - main');
SELECT fn_upsert_product_image('corsair-k100-rgb-mechanical-keyboard', 1, 'https://assets.corsair.com/image/upload/c_pad,q_85,h_1100,w_1100,f_auto/products/Gaming-Keyboards/CH-912A01A-UK/Gallery/K100_RGB_06.webp', 'Corsair K100 RGB Keyboard - keycaps');
SELECT fn_upsert_product_image('corsair-k100-rgb-mechanical-keyboard', 2, 'https://assets.corsair.com/image/upload/c_pad,q_85,h_1100,w_1100,f_auto/products/Gaming-Keyboards/CH-912A01A-UK/Gallery/K100_RGB_10.webp', 'Corsair K100 RGB Keyboard - wrist rest');



-- ASUS ROG Swift PG279QM Gaming Monitor
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-components'),
  (SELECT id FROM brands WHERE slug='asus'),
  'ASUS ROG Swift PG279QM Gaming Monitor',
  'asus-rog-swift-pg279qm-gaming-monitor',
  'ASUS-MONITOR-001',
  'active',
  '27-inch 1440p gaming monitor with 240Hz',
  'ASUS ROG Swift PG279QM 27-inch 1440p gaming monitor with 240Hz refresh rate and G-SYNC technology.',
  399.99,
  25,
  FALSE,
  3
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for Monitor
SELECT fn_upsert_product_image('asus-rog-swift-pg279qm-gaming-monitor', 0, 'https://uk.store.asus.com/media/catalog/product/p/g/pg279qm-2.jpg?width=1000&height=450&store=en_UK&image-type=image', 'ASUS ROG Swift PG279QM - main');
SELECT fn_upsert_product_image('asus-rog-swift-pg279qm-gaming-monitor', 1, 'https://uk.store.asus.com/media/catalog/product/P/G/PG279QM-4.jpg?width=1000&height=450&store=en_UK&image-type=image', 'ASUS ROG Swift PG279QM - back');
SELECT fn_upsert_product_image('asus-rog-swift-pg279qm-gaming-monitor', 2, 'https://uk.store.asus.com/media/catalog/product/P/G/PG279QM-5.jpg?width=1000&height=450&store=en_UK&image-type=image', 'ASUS ROG Swift PG279QM - ports');



-- Wireless Gaming Headset
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='steelseries'),
  'Wireless Gaming Headset',
  'wireless-gaming-headset',
  'STEELSERIES-HEADSET-001',
  'active',
  'Multi-platform gaming headset with ANC',
  'Multi-platform gaming headset with active noise cancellation and 50mm drivers for immersive audio.',
  149.99,
  60,
  FALSE,
  8
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for Headset
SELECT fn_upsert_product_image('wireless-gaming-headset', 0, 'https://media.currys.biz/i/currysprod/10263515?$l-large$&fmt=auto', 'Wireless Gaming Headset - main');
SELECT fn_upsert_product_image('wireless-gaming-headset', 1, 'https://media.currys.biz/i/currysprod/10263515_009?$l-large$&fmt=auto', 'Wireless Gaming Headset - microphone');
SELECT fn_upsert_product_image('wireless-gaming-headset', 2, 'https://media.currys.biz/i/currysprod/10263515_025?$l-large$&fmt=auto', 'Wireless Gaming Headset - ports');
  


-- Tech Axis Gaming Hoodie
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='merchandise'),
  (SELECT id FROM brands WHERE slug='tech-axis'),
  'Tech Axis Gaming Hoodie',
  'tech-axis-gaming-hoodie',
  NULL,
  'active',
  'Premium gaming hoodie with Tech Axis logo',
  'High-quality cotton blend hoodie with embroidered Tech Axis logo, perfect for gamers and tech enthusiasts.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Product option types for Hoodie
INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour','Size')
WHERE p.slug='tech-axis-gaming-hoodie'
ON CONFLICT DO NOTHING;

-- Variants for Hoodie
INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-BLACK-S',  'Tech Axis Hoodie - Black / S',  39.99, 15, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-BLACK-M',  'Tech Axis Hoodie - Black / M',  39.99, 20, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-BLACK-L',  'Tech Axis Hoodie - Black / L',  39.99, 15, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-BLACK-XL', 'Tech Axis Hoodie - Black / XL', 39.99, 10, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-GREY-S',   'Tech Axis Hoodie - Grey / S',   39.99, 15, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-GREY-M',   'Tech Axis Hoodie - Grey / M',   39.99, 15, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-GREY-L',   'Tech Axis Hoodie - Grey / L',   39.99, 13, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-gaming-hoodie'), 'HOODIE-GREY-XL',  'Tech Axis Hoodie - Grey / XL',  39.99, 10, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Variant option values for Hoodie
-- Black S/M/L/XL
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Black'
WHERE v.sku IN ('HOODIE-BLACK-S','HOODIE-BLACK-M','HOODIE-BLACK-L','HOODIE-BLACK-XL')
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Size'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value = CASE
  WHEN v.sku='HOODIE-BLACK-S'  THEN 'S'
  WHEN v.sku='HOODIE-BLACK-M'  THEN 'M'
  WHEN v.sku='HOODIE-BLACK-L'  THEN 'L'
  WHEN v.sku='HOODIE-BLACK-XL' THEN 'XL'
END
WHERE v.sku IN ('HOODIE-BLACK-S','HOODIE-BLACK-M','HOODIE-BLACK-L','HOODIE-BLACK-XL')
ON CONFLICT DO NOTHING;

-- Grey S/M/L/XL
INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Grey'
WHERE v.sku IN ('HOODIE-GREY-S','HOODIE-GREY-M','HOODIE-GREY-L','HOODIE-GREY-XL')
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Size'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value = CASE
  WHEN v.sku='HOODIE-GREY-S'  THEN 'S'
  WHEN v.sku='HOODIE-GREY-M'  THEN 'M'
  WHEN v.sku='HOODIE-GREY-L'  THEN 'L'
  WHEN v.sku='HOODIE-GREY-XL' THEN 'XL'
END
WHERE v.sku IN ('HOODIE-GREY-S','HOODIE-GREY-M','HOODIE-GREY-L','HOODIE-GREY-XL')
ON CONFLICT DO NOTHING;

-- Images for Hoodie
SELECT fn_upsert_product_image('tech-axis-gaming-hoodie', 0, 'https://cdn.imgchest.com/files/fda33d001bfa.png', 'Tech Axis Hoodie - black');
SELECT fn_upsert_product_image('tech-axis-gaming-hoodie', 1, 'https://cdn.imgchest.com/files/083c74aec3b4.png', 'Tech Axis Hoodie - grey');



-- ASUS ROG Strix GeForce RTX 4080
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-components'),
  (SELECT id FROM brands WHERE slug='asus'),
  'ASUS ROG Strix GeForce RTX 4080',
  'asus-rog-strix-geforce-rtx-4080',
  'ASUS-RTX4080-001',
  'active',
  'High-performance graphics card for 4K gaming',
  'High-performance graphics card with advanced cooling design, aimed at smooth 4K gaming and high refresh-rate play.',
  1199.99,
  15,
  FALSE,
  2
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

-- Images for RTX 4080
SELECT fn_upsert_product_image('asus-rog-strix-geforce-rtx-4080', 0, 'https://m.media-amazon.com/images/I/71uLe6XTV4L._AC_SL1500_.jpg', 'ASUS ROG Strix RTX 4080 - main');
SELECT fn_upsert_product_image('asus-rog-strix-geforce-rtx-4080', 1, 'https://m.media-amazon.com/images/I/71T3YjCKVtL._AC_SL1500_.jpg', 'ASUS ROG Strix RTX 4080 - angled');
SELECT fn_upsert_product_image('asus-rog-strix-geforce-rtx-4080', 2, 'https://m.media-amazon.com/images/I/71BL-d-zosL._AC_SL1500_.jpg', 'ASUS ROG Strix RTX 4080 - flat');


-- Nintendo Switch OLED
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='nintendo'),
  'Nintendo Switch OLED',
  'nintendo-switch-oled',
  'NSW-OLED-001',
  'active',
  'Hybrid console with vibrant 7-inch OLED screen',
  'Nintendo Switch OLED model features a vivid 7-inch OLED display, enhanced audio, and 64GB internal storage. Play at home or on the go with detachable Joy-Con controllers.',
  309.99,
  40,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('nintendo-switch-oled', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/nintendo-switch-oled.jpg', 'Nintendo Switch OLED - white');


-- Razer BlackWidow V4 Mechanical Keyboard
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='razer'),
  'Razer BlackWidow V4 Mechanical Keyboard',
  'razer-blackwidow-v4-keyboard',
  'RAZER-KB-001',
  'active',
  'RGB mechanical keyboard with tactile switches',
  'Razer BlackWidow V4 mechanical keyboard with customizable RGB lighting, programmable macros, and durable tactile switches.',
  139.99,
  35,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('razer-blackwidow-v4-keyboard', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/razer-blackwidow-v4-keyboard.jpg', 'Razer BlackWidow V4 Mechanical Keyboard');


-- SteelSeries Apex Pro TKL Keyboard
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='steelseries'),
  'SteelSeries Apex Pro TKL Keyboard',
  'steelseries-apex-pro-tkl',
  'SS-APEXPRO-001',
  'active',
  'Adjustable actuation gaming keyboard',
  'Compact tenkeyless gaming keyboard with adjustable actuation switches and per-key RGB illumination.',
  169.99,
  28,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('steelseries-apex-pro-tkl', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/steelseries-apex-pro-tkl.jpg', 'SteelSeries Apex Pro TKL Keyboard');


-- Logitech G Pro X Superlight Mouse
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-gaming'),
  (SELECT id FROM brands WHERE slug='logitech'),
  'Logitech G Pro X Superlight Mouse',
  'logitech-g-pro-x-superlight',
  'LOGI-SUPERLIGHT-001',
  'active',
  'Ultra-lightweight esports gaming mouse',
  'Weighing under 63g, this wireless esports mouse offers HERO sensor precision and ultra-low latency performance.',
  129.99,
  45,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('logitech-g-pro-x-superlight', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/logitech-g-pro-x-superlight.jpg', 'Logitech G Pro X Superlight Mouse');


-- Corsair Vengeance RGB DDR5 32GB RAM
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-components'),
  (SELECT id FROM brands WHERE slug='corsair'),
  'Corsair Vengeance RGB DDR5 32GB RAM',
  'corsair-vengeance-rgb-ddr5-32gb',
  'COR-DDR5-32GB',
  'active',
  'High-speed DDR5 memory with RGB',
  '32GB (2x16GB) Corsair Vengeance DDR5 RAM delivering blazing speeds and dynamic RGB lighting for high-performance builds.',
  179.99,
  30,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('corsair-vengeance-rgb-ddr5-32gb', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/Vengeance-RGB-DDR5-2UP-BLACK_01.avif', 'Corsair Vengeance RGB DDR5 32GB RAM');


-- Anker PowerCore 20K Power Bank
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='phones-gadgets'),
  (SELECT id FROM brands WHERE slug='anker'),
  'Anker PowerCore 20K Power Bank',
  'anker-powercore-20k',
  'ANKER-20K-001',
  'active',
  'High-capacity fast-charging power bank',
  '20,000mAh portable charger with PowerIQ technology for fast and safe charging of phones and tablets.',
  39.99,
  75,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('anker-powercore-20k', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/anker-powercore-20k.jpg', 'Anker PowerCore 20K Power Bank');


-- Apple AirPods Pro (2nd Gen)
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='phones-gadgets'),
  (SELECT id FROM brands WHERE slug='apple'),
  'Apple AirPods Pro (2nd Gen)',
  'apple-airpods-pro-2',
  'APPLE-AIRPODS-2',
  'active',
  'Active noise-cancelling wireless earbuds',
  'Apple AirPods Pro with improved ANC, spatial audio, and MagSafe charging case.',
  229.99,
  50,
  FALSE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

SELECT fn_upsert_product_image('apple-airpods-pro-2', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/apple-airpods-pro-2.jpg', 'Apple AirPods Pro 2');


-- Xbox Wireless Controller
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='consoles-accessories'),
  (SELECT id FROM brands WHERE slug='microsoft'),
  'Xbox Wireless Controller',
  'xbox-wireless-controller',
  NULL,
  'active',
  'Textured grip controller for Xbox and PC',
  'Microsoft Xbox Wireless Controller with textured grip, Bluetooth support, and share button.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour')
WHERE p.slug='xbox-wireless-controller'
ON CONFLICT DO NOTHING;

INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='xbox-wireless-controller'), 'XBOX-CONTROLLER-WHITE', 'Xbox Wireless Controller - White', 59.99, 24, 5),
  ((SELECT id FROM products WHERE slug='xbox-wireless-controller'), 'XBOX-CONTROLLER-BLUE',  'Xbox Wireless Controller - Blue',  64.99, 18, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='White'
WHERE v.sku='XBOX-CONTROLLER-WHITE'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Blue'
WHERE v.sku='XBOX-CONTROLLER-BLUE'
ON CONFLICT DO NOTHING;

SELECT fn_upsert_product_image('xbox-wireless-controller', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/xbox-controller-white.jpg', 'Xbox Wireless Controller - white');
SELECT fn_upsert_product_image('xbox-wireless-controller', 1, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/xbox-controller-blue.jpg', 'Xbox Wireless Controller - blue');


-- Samsung 980 PRO 1TB NVMe SSD
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='pc-components'),
  (SELECT id FROM brands WHERE slug='samsung'),
  'Samsung 980 PRO 1TB NVMe SSD',
  'samsung-980-pro-1tb',
  NULL,
  'active',
  'Ultra-fast PCIe Gen4 SSD',
  'Samsung 980 PRO NVMe SSD with PCIe 4.0 interface, delivering up to 7,000 MB/s read speeds for extreme performance.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Storage')
WHERE p.slug='samsung-980-pro-1tb'
ON CONFLICT DO NOTHING;

INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='samsung-980-pro-1tb'), 'SAMSUNG-980PRO-512', 'Samsung 980 PRO - 512GB', 79.99, 20, 5),
  ((SELECT id FROM products WHERE slug='samsung-980-pro-1tb'), 'SAMSUNG-980PRO-1TB', 'Samsung 980 PRO - 1TB', 129.99, 18, 5),
  ((SELECT id FROM products WHERE slug='samsung-980-pro-1tb'), 'SAMSUNG-980PRO-2TB', 'Samsung 980 PRO - 2TB', 219.99, 10, 3)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Storage'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='512GB'
WHERE v.sku='SAMSUNG-980PRO-512'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Storage'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='1TB'
WHERE v.sku='SAMSUNG-980PRO-1TB'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Storage'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='2TB'
WHERE v.sku='SAMSUNG-980PRO-2TB'
ON CONFLICT DO NOTHING;

SELECT fn_upsert_product_image('samsung-980-pro-1tb', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/samsung-980-pro-512gb.jpg', 'Samsung 980 PRO - 512GB');
SELECT fn_upsert_product_image('samsung-980-pro-1tb', 1, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/samsung-980-pro-1tb.jpg', 'Samsung 980 PRO - 1TB');
SELECT fn_upsert_product_image('samsung-980-pro-1tb', 2, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/samsung-980-pro-2tb.jpg', 'Samsung 980 PRO - 2TB');


-- Samsung Galaxy Tab S9
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='phones-gadgets'),
  (SELECT id FROM brands WHERE slug='samsung'),
  'Galaxy Tab S9',
  'samsung-galaxy-tab-s9',
  NULL,
  'active',
  'Premium Android tablet with AMOLED display',
  'Galaxy Tab S9 with 11-inch Dynamic AMOLED 2X display, S Pen included, and powerful Snapdragon processor.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Storage')
WHERE p.slug='samsung-galaxy-tab-s9'
ON CONFLICT DO NOTHING;

INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='samsung-galaxy-tab-s9'), 'TABS9-256', 'Galaxy Tab S9 - 256GB', 799.99, 18, 5),
  ((SELECT id FROM products WHERE slug='samsung-galaxy-tab-s9'), 'TABS9-512', 'Galaxy Tab S9 - 512GB', 899.99, 12, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Storage'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='256GB'
WHERE v.sku='TABS9-256'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Storage'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='512GB'
WHERE v.sku='TABS9-512'
ON CONFLICT DO NOTHING;

SELECT fn_upsert_product_image('samsung-galaxy-tab-s9', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/samsung-galaxy-tab-s9-front.jpg', 'Samsung Galaxy Tab S9 - front');
SELECT fn_upsert_product_image('samsung-galaxy-tab-s9', 1, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/samsung-galaxy-tab-s9-with-pen.jpg', 'Samsung Galaxy Tab S9 - with S Pen');


-- Tech Axis Snapback Cap
INSERT INTO products (
  category_id, brand_id, name, slug, sku, status,
  summary, description, price, stock_quantity, has_variants, low_stock_threshold
)
VALUES (
  (SELECT id FROM categories WHERE slug='merchandise'),
  (SELECT id FROM brands WHERE slug='tech-axis'),
  'Tech Axis Snapback Cap',
  'tech-axis-snapback-cap',
  NULL,
  'active',
  'Adjustable gaming snapback hat',
  'Flat-brim snapback cap featuring embroidered Tech Axis logo. Breathable and stylish.',
  0.00,
  0,
  TRUE,
  5
)
ON CONFLICT (slug) DO UPDATE SET
  category_id = EXCLUDED.category_id,
  brand_id = EXCLUDED.brand_id,
  name = EXCLUDED.name,
  sku = EXCLUDED.sku,
  status = EXCLUDED.status,
  summary = EXCLUDED.summary,
  description = EXCLUDED.description,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  has_variants = EXCLUDED.has_variants,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_option_types (product_id, option_type_id)
SELECT p.id, ot.id
FROM products p
JOIN option_types ot ON ot.name IN ('Colour')
WHERE p.slug='tech-axis-snapback-cap'
ON CONFLICT DO NOTHING;

INSERT INTO product_variants (product_id, sku, title, price, stock_quantity, low_stock_threshold)
VALUES
  ((SELECT id FROM products WHERE slug='tech-axis-snapback-cap'), 'CAP-BLACK', 'Tech Axis Snapback Cap - Black', 24.99, 20, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-snapback-cap'), 'CAP-GREY',  'Tech Axis Snapback Cap - Grey',  24.99, 18, 5),
  ((SELECT id FROM products WHERE slug='tech-axis-snapback-cap'), 'CAP-RED',   'Tech Axis Snapback Cap - Red',   24.99, 15, 5)
ON CONFLICT (sku) DO UPDATE SET
  product_id = EXCLUDED.product_id,
  title = EXCLUDED.title,
  price = EXCLUDED.price,
  stock_quantity = EXCLUDED.stock_quantity,
  low_stock_threshold = EXCLUDED.low_stock_threshold;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Black'
WHERE v.sku='CAP-BLACK'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Grey'
WHERE v.sku='CAP-GREY'
ON CONFLICT DO NOTHING;

INSERT INTO product_variant_option_values (variant_id, option_value_id)
SELECT v.id, ov.id
FROM product_variants v
JOIN option_types ot ON ot.name='Colour'
JOIN option_values ov ON ov.option_type_id=ot.id AND ov.value='Red'
WHERE v.sku='CAP-RED'
ON CONFLICT DO NOTHING;

SELECT fn_upsert_product_image('tech-axis-snapback-cap', 0, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/tech-axis-snapback-black.jpg', 'Tech Axis Snapback Cap - black');
SELECT fn_upsert_product_image('tech-axis-snapback-cap', 1, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/tech-axis-snapback-grey.jpg', 'Tech Axis Snapback Cap - grey');
SELECT fn_upsert_product_image('tech-axis-snapback-cap', 2, 'https://cs2team7.cs2410-web01pvm.aston.ac.uk/images/tech-axis-snapback-red.jpg', 'Tech Axis Snapback Cap - red');


COMMIT;
