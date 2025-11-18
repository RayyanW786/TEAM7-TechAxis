-- Tech Axis DB schema designed and authored by Rayyan (240212160)

-- Notes:
-- NOTIFY is used to create stock alerts like an event system and we need to LISTEN to it within the backend.

-- Extensions
CREATE EXTENSION IF NOT EXISTS pg_trgm;         
-- documentation reference: https://www.postgresql.org/docs/current/pgtrgm.html
-- tsvector: https://www.postgresql.org/docs/current/datatype-textsearch.html
-- ts_rank, websearch_to_tsquery, setweight: https://www.postgresql.org/docs/current/textsearch-controls.html

-- I ended up choosing websearch_to_tsquery instead of plainto_tsquery or to_tsquery for the following reasons:
-- 1) websearch_to_tsquery is a simplified version of to_tsquery
-- 2) websearch_to_tsquery provides a common syntax used by modern web search engines such as google

-- For example the following syntax is supported while making use of the GIN indexes 
-- 1) "nintendo switch" -> exact phrase
-- 2) ps5 -controller -> exclude controller
-- 3) pc OR console -> boolean OR
-- By default unquoted text: text not inside quote marks will be converted to terms separated by & operators, as if processed by plainto_tsquery.

CREATE EXTENSION IF NOT EXISTS citext;          
-- case insensitive emails & usernames reference: https://www.postgresql.org/docs/current/citext.html

-- The standard approach to doing case-insensitive matches in PostgreSQL has been to use the lower function when comparing values, for example
-- SELECT * FROM tab WHERE lower(col) = LOWER(?);
-- This works reasonably well, but has a number of drawbacks:
-- It makes your SQL statements verbose, and you always have to remember to use lower on both the column and the query value.
-- It won't use an index, unless you create a functional index using lower.
-- If you declare a column as UNIQUE or PRIMARY KEY, the implicitly generated index is case-sensitive. So it's useless for case-insensitive searches, and it won't enforce uniqueness case-insensitively.
-- The citext data type allows you to eliminate calls to lower in SQL queries, and allows a primary key to be case-insensitive.


-- Enums
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
    CREATE TYPE user_role AS ENUM ('customer','admin');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'product_status') THEN
    CREATE TYPE product_status AS ENUM ('draft','active','archived');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'order_status') THEN
    CREATE TYPE order_status AS ENUM ('pending','placed','processing','shipped','completed','cancelled','returned');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'return_status') THEN
    CREATE TYPE return_status AS ENUM ('requested','approved','rejected','received','restocked','closed');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'inventory_alert_type') THEN
    CREATE TYPE inventory_alert_type AS ENUM ('OUT_OF_STOCK','LOW_STOCK','IN_STOCK');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'discount_type') THEN
    CREATE TYPE discount_type AS ENUM ('percentage','fixed');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'inventory_item_type') THEN
    CREATE TYPE inventory_item_type AS ENUM ('product','variant');
  END IF;
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ticket_status') THEN
    CREATE TYPE ticket_status AS ENUM ('open', 'waiting_on_admin', 'waiting_on_customer', 'closed');
  END IF;
END$$;

-- helper function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION fn_touch_updated_at()
RETURNS TRIGGER
LANGUAGE PLPGSQL
AS $$
BEGIN
  NEW.updated_at := NOW();
  RETURN NEW;
END$$;


-- User table
CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  role user_role NOT NULL DEFAULT 'customer',
  name TEXT NOT NULL,
  email CITEXT NOT NULL UNIQUE,
  email_verified_at TIMESTAMPTZ,
  -- store hash only Argon2id or bcrypt
  password_hash TEXT NOT NULL,
  CONSTRAINT users_password_hash_ck CHECK (
    password_hash ~ '^\$argon2id\$' OR password_hash ~ '^\$2[aby]\$'
  ),
  -- Force password change after a year since password_changed_at
  password_must_change BOOLEAN NOT NULL DEFAULT FALSE, 
  password_changed_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE TRIGGER trg_users_touch_upd BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


-- Tickets for contact system
CREATE TABLE IF NOT EXISTS support_tickets (
  id BIGSERIAL PRIMARY KEY,
  created_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,  -- requester (customer)
  subject TEXT NOT NULL,
  status ticket_status NOT NULL DEFAULT 'open',
  assigned_to_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,  -- optional, which admin owns it
  last_message_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),  -- for sorting inboxes
  closed_at TIMESTAMPTZ,
  closed_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,  -- admin who closed it
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status);
CREATE INDEX IF NOT EXISTS idx_support_tickets_created_by ON support_tickets(created_by_user_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_assigned_to ON support_tickets(assigned_to_user_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_last_msg ON support_tickets(last_message_at DESC);

CREATE TRIGGER trg_support_tickets_touch_upd BEFORE UPDATE ON support_tickets
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


-- Ticket Messages 
CREATE TABLE IF NOT EXISTS support_messages (
  id BIGSERIAL PRIMARY KEY,
  ticket_id BIGINT NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
  sender_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,  -- customer or admin
  body TEXT NOT NULL,
  is_internal BOOLEAN NOT NULL DEFAULT FALSE,  -- admin-only note
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_support_messages_ticket ON support_messages(ticket_id);
CREATE INDEX IF NOT EXISTS idx_support_messages_created_at ON support_messages(created_at);


-- Customer profile and addresses
CREATE TABLE IF NOT EXISTS customer_profiles (
  user_id BIGINT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  phone VARCHAR(30),
  date_of_birth DATE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE TRIGGER trg_customer_profiles_touch_upd BEFORE UPDATE ON customer_profiles
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS addresses (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  label TEXT, -- "Home", "Office"
  recipient_name TEXT,
  line1 TEXT NOT NULL,
  line2 TEXT,
  city TEXT NOT NULL,
  region TEXT,
  postal_code TEXT NOT NULL,
  is_default_shipping BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE UNIQUE INDEX ux_addresses_default_shipping ON addresses(user_id) WHERE is_default_shipping;
CREATE INDEX idx_addresses_user_id ON addresses(user_id);

CREATE TRIGGER trg_addresses_touch_upd BEFORE UPDATE ON addresses
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


-- Product Information (Miscellaneous)
CREATE TABLE IF NOT EXISTS brands (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,  -- TODO: might not be needed
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT brands_slug_ck CHECK (slug = LOWER(slug) AND slug ~ '^[a-z0-9]+(?:-[a-z0-9]+)*$')
);
CREATE TRIGGER trg_brands_touch_upd BEFORE UPDATE ON brands
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS categories (
  id BIGSERIAL PRIMARY KEY,
  parent_id BIGINT REFERENCES categories(id) ON DELETE SET NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT categories_name_ck CHECK (LENGTH(name) > 0),
  CONSTRAINT categories_sibling_sort_unique UNIQUE (parent_id, sort_order) DEFERRABLE INITIALLY DEFERRED,
  CONSTRAINT categories_slug_ck CHECK (slug = LOWER(slug) AND slug ~ '^[a-z0-9]+(?:-[a-z0-9]+)*$')
);
CREATE INDEX idx_categories_parent_id ON categories(parent_id);


CREATE TABLE IF NOT EXISTS products (
  id BIGSERIAL PRIMARY KEY,
  category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
  brand_id BIGINT REFERENCES brands(id) ON DELETE SET NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  sku TEXT UNIQUE, -- for simple products
  status product_status NOT NULL DEFAULT 'draft',
  summary TEXT,
  description TEXT,
  price NUMERIC(12,2) NOT NULL DEFAULT 0.00, -- simple product price
  stock_quantity INT NOT NULL DEFAULT 0,     -- simple product stock
  has_variants BOOLEAN NOT NULL DEFAULT FALSE,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT products_price_ck CHECK (price >= 0),
  CONSTRAINT products_stock_ck CHECK (stock_quantity >= 0),
  CONSTRAINT products_slug_ck CHECK (slug = LOWER(slug) AND slug ~ '^[a-z0-9]+(?:-[a-z0-9]+)*$')
);
CREATE INDEX idx_products_category_id ON products(category_id);
CREATE INDEX idx_products_brand_id ON products(brand_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_price ON products(price);
-- FTS (name > description)
CREATE INDEX idx_products_fts ON products USING GIN (
  to_tsvector('english', COALESCE(name,'') || ' ' || COALESCE(description,''))
);
CREATE INDEX idx_products_name_trgm ON products USING GIN (name gin_trgm_ops);

CREATE TRIGGER trg_products_touch_upd BEFORE UPDATE ON products
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS product_images (
  id BIGSERIAL PRIMARY KEY,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  url TEXT NOT NULL,
  alt_text TEXT,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT product_images_sort_unique
    UNIQUE (product_id, sort_order)
    DEFERRABLE INITIALLY DEFERRED 
);
CREATE INDEX idx_product_images_product_id ON product_images(product_id);


-- Options/Variants
CREATE TABLE IF NOT EXISTS option_types (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL UNIQUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS option_values (
  id BIGSERIAL PRIMARY KEY,
  option_type_id BIGINT NOT NULL REFERENCES option_types(id) ON DELETE CASCADE,
  value TEXT NOT NULL,
  UNIQUE(option_type_id, value)
);
CREATE INDEX idx_option_values_type ON option_values(option_type_id);


CREATE TABLE IF NOT EXISTS product_option_types (
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  option_type_id BIGINT NOT NULL REFERENCES option_types(id) ON DELETE RESTRICT,
  PRIMARY KEY (product_id, option_type_id)
);


CREATE TABLE IF NOT EXISTS product_variants (
  id BIGSERIAL PRIMARY KEY,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  sku TEXT NOT NULL UNIQUE,
  title TEXT, -- "Black / Large"
  price NUMERIC(12,2) NOT NULL,
  stock_quantity INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT product_variants_price_ck CHECK (price >= 0),
  CONSTRAINT product_variants_stock_ck CHECK (stock_quantity >= 0)
);
CREATE INDEX idx_product_variants_product_id ON product_variants(product_id);
CREATE INDEX idx_product_variants_product_price ON product_variants(product_id, price);

CREATE TRIGGER trg_product_variants_touch_upd BEFORE UPDATE ON product_variants
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS product_variant_option_values (
  variant_id BIGINT NOT NULL REFERENCES product_variants(id) ON DELETE CASCADE,
  option_value_id BIGINT NOT NULL REFERENCES option_values(id) ON DELETE RESTRICT,
  PRIMARY KEY (variant_id, option_value_id)
);


-- Cart
CREATE TABLE IF NOT EXISTS shopping_carts (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL, -- guest carts allowed
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_carts_user_id ON shopping_carts(user_id);

CREATE TRIGGER trg_carts_touch_upd BEFORE UPDATE ON shopping_carts
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS cart_items (
  id BIGSERIAL PRIMARY KEY,
  cart_id BIGINT NOT NULL REFERENCES shopping_carts(id) ON DELETE CASCADE,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  variant_id BIGINT REFERENCES product_variants(id) ON DELETE RESTRICT,
  quantity INT NOT NULL CHECK (quantity > 0),
  unit_price NUMERIC(12,2) NOT NULL CHECK (unit_price >= 0), -- snapshot at add time
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
  
);
-- one item per (cart, product) when variant_id IS NULL
CREATE UNIQUE INDEX ux_cart_items_no_variant
  ON cart_items(cart_id, product_id)
  WHERE variant_id IS NULL;

-- one item per (cart, product, variant) when variant_id IS NOT NULL
CREATE UNIQUE INDEX ux_cart_items_with_variant
  ON cart_items(cart_id, product_id, variant_id)
  WHERE variant_id IS NOT NULL;

CREATE INDEX idx_cart_items_cart_id ON cart_items(cart_id);


-- Orders / discount / shipments / returns
CREATE TABLE IF NOT EXISTS orders (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  status order_status NOT NULL DEFAULT 'pending',
  total_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,  -- the amount that is charged
  subtotal_amount NUMERIC(12,2) NOT NULL DEFAULT 0.00,  -- the total before discount
  discount_total NUMERIC(12,2) NOT NULL DEFAULT 0.00,  -- the discounted amount
  billing_address_id BIGINT REFERENCES addresses(id) ON DELETE SET NULL,
  shipping_address_id BIGINT REFERENCES addresses(id) ON DELETE SET NULL,
  notes TEXT,
  placed_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);

CREATE TRIGGER trg_orders_touch_upd BEFORE UPDATE ON orders
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS discount_codes (
  id BIGSERIAL PRIMARY KEY,
  code TEXT NOT NULL UNIQUE,
  type discount_type NOT NULL,
  amount NUMERIC(12,2) NOT NULL CHECK (amount > 0),
  max_uses INT,
  max_uses_per_user INT,
  min_order_total NUMERIC(12,2),
  starts_at TIMESTAMPTZ,
  expires_at TIMESTAMPTZ,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE TRIGGER trg_discount_codes_touch_upd BEFORE UPDATE ON discount_codes
FOR EACH ROW EXECUTE FUNCTION fn_touch_updated_at();


CREATE TABLE IF NOT EXISTS discount_redemptions (
  id BIGSERIAL PRIMARY KEY,
  discount_code_id BIGINT NOT NULL REFERENCES discount_codes(id) ON DELETE CASCADE,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  amount_applied NUMERIC(12,2) NOT NULL CHECK (amount_applied >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (discount_code_id, order_id)
);


CREATE TABLE IF NOT EXISTS order_items (
  id BIGSERIAL PRIMARY KEY,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  variant_id BIGINT REFERENCES product_variants(id) ON DELETE RESTRICT,
  name_snapshot TEXT NOT NULL,
  sku_snapshot TEXT,
  unit_price NUMERIC(12,2) NOT NULL CHECK (unit_price >= 0),
  quantity INT NOT NULL CHECK (quantity > 0),
  line_total NUMERIC(12,2) NOT NULL CHECK (line_total >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);


CREATE TABLE IF NOT EXISTS shipments (
  id BIGSERIAL PRIMARY KEY,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  carrier TEXT,
  tracking_number TEXT,
  shipped_at TIMESTAMPTZ,
  delivered_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_shipments_order_id ON shipments(order_id);


CREATE TABLE IF NOT EXISTS return_requests (
  id BIGSERIAL PRIMARY KEY,
  order_item_id BIGINT NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  quantity INT NOT NULL CHECK (quantity > 0),
  reason TEXT,
  status return_status NOT NULL DEFAULT 'requested',
  processed_by BIGINT REFERENCES users(id) ON DELETE SET NULL, -- admin
  approved_quantity INT,
  restock BOOLEAN,
  requested_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  processed_at TIMESTAMPTZ
);
CREATE INDEX idx_returns_user ON return_requests(user_id);
CREATE INDEX idx_returns_status ON return_requests(status);


-- Inventory ledger + alerts
CREATE TABLE IF NOT EXISTS inventory_transactions (
  id BIGSERIAL PRIMARY KEY,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  variant_id BIGINT REFERENCES product_variants(id) ON DELETE RESTRICT,
  quantity_change INT NOT NULL, -- +in / -out
  reason TEXT NOT NULL,         -- "order_placed","order_cancelled","manual_adjustment","incoming_stock","return_received"
  order_id BIGINT REFERENCES orders(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT inventory_qty_nonzero CHECK (quantity_change <> 0)
);
CREATE INDEX idx_inventory_product ON inventory_transactions(product_id, variant_id);
CREATE INDEX idx_inventory_order ON inventory_transactions(order_id);


CREATE TABLE IF NOT EXISTS inventory_alerts (
  id BIGSERIAL PRIMARY KEY,
  item_type inventory_item_type NOT NULL, -- "product" | "variant"
  product_id BIGINT NOT NULL,
  variant_id BIGINT,
  previous_qty INT,
  new_qty INT,
  alert_type inventory_alert_type NOT NULL, -- "OUT_OF_STOCK","LOW_STOCK","IN_STOCK"
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

  CONSTRAINT inventory_alerts_item_type_ck CHECK (
    (item_type = 'product' AND variant_id IS NULL)
    OR
    (item_type = 'variant' AND variant_id IS NOT NULL)
  )
);
CREATE INDEX idx_inventory_alerts_product ON inventory_alerts(product_id);
CREATE INDEX idx_inventory_alerts_variant ON inventory_alerts(variant_id);


CREATE OR REPLACE FUNCTION fn_emit_stock_alert_product()
RETURNS TRIGGER
LANGUAGE PLPGSQL
AS $$
DECLARE
  v_alert inventory_alert_type;
BEGIN
  IF NEW.stock_quantity <= 0 AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity > 0) THEN
    v_alert := 'OUT_OF_STOCK';
  ELSIF NEW.stock_quantity > 0 AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity <= 0) THEN
    v_alert := 'IN_STOCK';
  ELSIF NEW.stock_quantity <= NEW.low_stock_threshold
        AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity > NEW.low_stock_threshold) THEN
    v_alert := 'LOW_STOCK';
  END IF;

  IF v_alert IS NOT NULL THEN
    INSERT INTO inventory_alerts(item_type, product_id, previous_qty, new_qty, alert_type)
    VALUES ('product', NEW.id, COALESCE(OLD.stock_quantity, 0), NEW.stock_quantity, v_alert);

    PERFORM pg_notify(
      'stock_alerts',
      json_build_object(
        'item_type','product',
        'product_id',NEW.id,
        'alert_type',v_alert,
        'qty',NEW.stock_quantity
      )::TEXT
    );
  END IF;

  RETURN NEW;
END$$;



CREATE OR REPLACE FUNCTION fn_emit_stock_alert_variant()
RETURNS TRIGGER
LANGUAGE PLPGSQL
AS $$
DECLARE
  v_alert inventory_alert_type;
BEGIN
  IF NEW.stock_quantity <= 0 AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity > 0) THEN
    v_alert := 'OUT_OF_STOCK';
  ELSIF NEW.stock_quantity > 0 AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity <= 0) THEN
    v_alert := 'IN_STOCK';
  ELSIF NEW.stock_quantity <= NEW.low_stock_threshold
        AND (OLD.stock_quantity IS NULL OR OLD.stock_quantity > NEW.low_stock_threshold) THEN
    v_alert := 'LOW_STOCK';
  END IF;

  IF v_alert IS NOT NULL THEN
    INSERT INTO inventory_alerts(item_type, product_id, variant_id, previous_qty, new_qty, alert_type)
    VALUES ('variant', NEW.product_id, NEW.id, COALESCE(OLD.stock_quantity, 0), NEW.stock_quantity, v_alert);

    PERFORM pg_notify(
      'stock_alerts',
      json_build_object(
        'item_type','variant',
        'product_id',NEW.product_id,
        'variant_id',NEW.id,
        'alert_type',v_alert,
        'qty',NEW.stock_quantity
      )::TEXT
    );
  END IF;

  RETURN NEW;
END$$;



CREATE TRIGGER trg_products_stock_alert
AFTER INSERT OR UPDATE OF stock_quantity ON products
FOR EACH ROW EXECUTE FUNCTION fn_emit_stock_alert_product();


CREATE TRIGGER trg_variants_stock_alert
AFTER INSERT OR UPDATE OF stock_quantity ON product_variants
FOR EACH ROW EXECUTE FUNCTION fn_emit_stock_alert_variant();


-- Reviews
CREATE TABLE IF NOT EXISTS product_reviews (
  id BIGSERIAL PRIMARY KEY,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  order_item_id BIGINT REFERENCES order_items(id) ON DELETE SET NULL, -- for verified purchase
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title TEXT,
  body TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (product_id, user_id)
);
CREATE INDEX idx_product_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_product_reviews_user ON product_reviews(user_id);


CREATE TABLE IF NOT EXISTS service_reviews (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (user_id)
);

-- Search function
CREATE OR REPLACE FUNCTION fn_search_products(
  query_text TEXT,
  category_slugs TEXT[] DEFAULT NULL,
  min_price NUMERIC DEFAULT NULL,
  max_price NUMERIC DEFAULT NULL,
  limit_count INT DEFAULT 20,
  offset_count INT DEFAULT 0
)
RETURNS TABLE(
  product_id BIGINT,
  product_name TEXT,
  product_slug TEXT,
  product_price NUMERIC,
  category_slug TEXT,
  search_rank REAL
)
LANGUAGE SQL
AS $$
WITH q AS (
  SELECT
    NULLIF(BTRIM(query_text, E' \t\n\r\f\v'), '') AS qt,
    websearch_to_tsquery('english', query_text) AS tsq
)
SELECT
  p.id AS product_id,
  p.name AS product_name,
  p.slug AS product_slug,
  COALESCE(vmin.min_variant_price, p.price) AS product_price,
  c.slug AS category_slug,
  ts_rank(
    setweight(to_tsvector('english', COALESCE(p.name,'')), 'A') ||
    setweight(to_tsvector('english', COALESCE(p.description,'')), 'C'),
    q.tsq
  ) AS search_rank
FROM products p
JOIN categories c ON c.id = p.category_id
CROSS JOIN q
LEFT JOIN LATERAL (
  SELECT MIN(v.price) AS min_variant_price
  FROM product_variants v
  WHERE v.product_id = p.id
) vmin ON TRUE
WHERE p.status = 'active'
  AND q.qt IS NOT NULL
  AND (
    (
      setweight(to_tsvector('english', COALESCE(p.name,'')), 'A') ||
      setweight(to_tsvector('english', COALESCE(p.description,'')), 'C')
    ) @@ q.tsq
    OR (q.tsq::TEXT = '' AND p.name ILIKE '%' || q.qt || '%')
  )
  AND (category_slugs IS NULL OR c.slug = ANY(category_slugs))
  AND (
    (p.has_variants = FALSE AND
      (min_price IS NULL OR p.price >= min_price) AND
      (max_price IS NULL OR p.price <= max_price)
    )
    OR
    (p.has_variants = TRUE AND EXISTS (
      SELECT 1 FROM product_variants v
      WHERE v.product_id = p.id
        AND (min_price IS NULL OR v.price >= min_price)
        AND (max_price IS NULL OR v.price <= max_price)
    ))
  )
ORDER BY search_rank DESC, p.created_at DESC
LIMIT limit_count OFFSET offset_count;
$$;


-- Cart + checkout helpers
CREATE OR REPLACE FUNCTION fn_add_to_cart(
  p_cart_id     BIGINT,
  p_product_id  BIGINT,
  p_variant_id  BIGINT,
  p_quantity    INT,
  p_unit_price  NUMERIC
)
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
BEGIN
  IF p_variant_id IS NULL THEN
    INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, unit_price)
    VALUES (p_cart_id, p_product_id, NULL, p_quantity, p_unit_price)
    ON CONFLICT (cart_id, product_id) WHERE variant_id IS NULL
    DO UPDATE
      SET quantity = cart_items.quantity + EXCLUDED.quantity;
  ELSE
    INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, unit_price)
    VALUES (p_cart_id, p_product_id, p_variant_id, p_quantity, p_unit_price)
    ON CONFLICT (cart_id, product_id, variant_id) WHERE variant_id IS NOT NULL
    DO UPDATE
      SET quantity = cart_items.quantity + EXCLUDED.quantity;
  END IF;
END;
$$;

CREATE OR REPLACE FUNCTION fn_update_cart_item_quantity(
  p_cart_item_id BIGINT,
  p_quantity INT
)
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
BEGIN
  IF p_quantity <= 0 THEN
    DELETE FROM cart_items WHERE id = p_cart_item_id;
  ELSE
    UPDATE cart_items SET quantity = p_quantity WHERE id = p_cart_item_id;
  END IF;
END$$;


CREATE OR REPLACE FUNCTION fn_remove_from_cart(
  p_cart_id BIGINT,
  p_product_id BIGINT,
  p_variant_id BIGINT
)
RETURNS VOID
LANGUAGE SQL
AS $$
DELETE FROM cart_items
WHERE cart_id = p_cart_id
  AND product_id = p_product_id
  AND ((p_variant_id IS NULL AND variant_id IS NULL) OR variant_id = p_variant_id);
$$;


-- Creates order from cart, posts inventory, clears cart
CREATE OR REPLACE FUNCTION fn_checkout_cart(
  p_cart_id BIGINT,
  p_user_id BIGINT,
  p_billing_address_id BIGINT,
  p_shipping_address_id BIGINT,
  p_discount_code TEXT DEFAULT NULL
)
RETURNS BIGINT
LANGUAGE PLPGSQL
AS $$
DECLARE
  v_order_id BIGINT;
  v_now TIMESTAMPTZ := NOW();
  v_subtotal NUMERIC(12,2);
  v_discount NUMERIC(12,2) := 0;
  v_total NUMERIC(12,2);

  v_code_id BIGINT;
  v_code_type discount_type;
  v_code_amount NUMERIC(12,2);
  v_max_uses INT;
  v_max_uses_per_user INT;
  v_min_order_total NUMERIC(12,2);
  v_code_starts_at TIMESTAMPTZ;
  v_code_expires_at TIMESTAMPTZ;

  r_item RECORD;
BEGIN
  -- 1) Compute subtotal
  SELECT COALESCE(SUM(quantity * unit_price), 0)::NUMERIC(12,2)
  INTO v_subtotal
  FROM cart_items
  WHERE cart_id = p_cart_id;

  IF v_subtotal <= 0 THEN
    RAISE EXCEPTION 'Cart % is empty', p_cart_id;
  END IF;

  -- 2) Validate and compute discount
  IF p_discount_code IS NOT NULL THEN
    SELECT
      id,
      type,
      amount,
      max_uses,
      max_uses_per_user,
      min_order_total,
      starts_at,
      expires_at
    INTO
      v_code_id,
      v_code_type,
      v_code_amount,
      v_max_uses,
      v_max_uses_per_user,
      v_min_order_total,
      v_code_starts_at,
      v_code_expires_at
    FROM discount_codes
    WHERE code = p_discount_code
      AND is_active = TRUE
    FOR UPDATE; -- lock this code row for concurrency

    IF NOT FOUND THEN
      RAISE EXCEPTION 'Invalid discount code';
    END IF;

    IF v_code_starts_at IS NOT NULL AND v_now < v_code_starts_at THEN
      RAISE EXCEPTION 'Discount code not yet active';
    END IF;

    IF v_code_expires_at IS NOT NULL AND v_now >= v_code_expires_at THEN
      RAISE EXCEPTION 'Discount code expired';
    END IF;

    IF v_min_order_total IS NOT NULL AND v_subtotal < v_min_order_total THEN
      RAISE EXCEPTION 'Order subtotal % is below required minimum % for code %',
        v_subtotal, v_min_order_total, p_discount_code;
    END IF;

    IF v_max_uses IS NOT NULL THEN
      PERFORM 1
      FROM discount_redemptions
      WHERE discount_code_id = v_code_id
      GROUP BY discount_code_id
      HAVING COUNT(*) >= v_max_uses;

      IF FOUND THEN
        RAISE EXCEPTION 'Discount code usage limit reached';
      END IF;
    END IF;

    IF v_max_uses_per_user IS NOT NULL AND p_user_id IS NOT NULL THEN
      PERFORM 1
      FROM discount_redemptions
      WHERE discount_code_id = v_code_id
        AND user_id = p_user_id
      GROUP BY discount_code_id
      HAVING COUNT(*) >= v_max_uses_per_user;

      IF FOUND THEN
        RAISE EXCEPTION 'You have used this discount code the maximum allowed times';
      END IF;
    END IF;

    IF v_code_type = 'percentage' THEN
      v_discount := ROUND(v_subtotal * (v_code_amount / 100.0), 2);
    ELSE
      v_discount := v_code_amount;
    END IF;

    IF v_discount > v_subtotal THEN
      v_discount := v_subtotal;
    END IF;
  END IF;

  v_total := v_subtotal - v_discount;

  -- 3) Create order
  INSERT INTO orders (
    user_id,
    status,
    subtotal_amount,
    discount_total,
    total_amount,
    billing_address_id,
    shipping_address_id,
    placed_at
  )
  VALUES (
    p_user_id,
    'pending',
    v_subtotal,
    v_discount,
    v_total,
    p_billing_address_id,
    p_shipping_address_id,
    v_now
  )
  RETURNING id INTO v_order_id;

  -- 4) Copy items + adjust stock (concurrency safe) + ledger
  FOR r_item IN
    SELECT
      ci.*,
      p.name AS product_name,
      COALESCE(v.sku, p.sku) AS sku_effective
    FROM cart_items ci
    JOIN products p ON p.id = ci.product_id
    LEFT JOIN product_variants v ON v.id = ci.variant_id
    WHERE ci.cart_id = p_cart_id
  LOOP
    -- stock check + decrement
    IF r_item.variant_id IS NOT NULL THEN
      -- variant based stock
      UPDATE product_variants
      SET stock_quantity = stock_quantity - r_item.quantity,
          updated_at = v_now
      WHERE id = r_item.variant_id
        AND stock_quantity >= r_item.quantity;

      IF NOT FOUND THEN
        RAISE EXCEPTION
          'Insufficient stock for variant % (product %)',
          r_item.variant_id, r_item.product_id;
      END IF;

      INSERT INTO inventory_transactions (
        product_id,
        variant_id,
        quantity_change,
        reason,
        order_id
      )
      VALUES (
        r_item.product_id,
        r_item.variant_id,
        -r_item.quantity,
        'order_placed',
        v_order_id
      );
    ELSE
      -- simple product stock
      UPDATE products
      SET stock_quantity = stock_quantity - r_item.quantity,
          updated_at = v_now
      WHERE id = r_item.product_id
        AND stock_quantity >= r_item.quantity;

      IF NOT FOUND THEN
        RAISE EXCEPTION
          'Insufficient stock for product %',
          r_item.product_id;
      END IF;

      INSERT INTO inventory_transactions (
        product_id,
        variant_id,
        quantity_change,
        reason,
        order_id
      )
      VALUES (
        r_item.product_id,
        NULL,
        -r_item.quantity,
        'order_placed',
        v_order_id
      );
    END IF;

    -- order item snapshot
    INSERT INTO order_items (
      order_id,
      product_id,
      variant_id,
      name_snapshot,
      sku_snapshot,
      unit_price,
      quantity,
      line_total
    )
    VALUES (
      v_order_id,
      r_item.product_id,
      r_item.variant_id,
      r_item.product_name,
      r_item.sku_effective,
      r_item.unit_price,
      r_item.quantity,
      r_item.unit_price * r_item.quantity
    );
  END LOOP;

  -- 5) Record discount redemption
  IF p_discount_code IS NOT NULL AND v_discount > 0 THEN
    INSERT INTO discount_redemptions (
      discount_code_id,
      order_id,
      user_id,
      amount_applied
    )
    VALUES (
      v_code_id,
      v_order_id,
      p_user_id,
      v_discount
    );
  END IF;

  -- 6) Clear cart
  DELETE FROM cart_items WHERE cart_id = p_cart_id;

  RETURN v_order_id;
END$$;



-- Returns flow
CREATE OR REPLACE FUNCTION fn_request_return(
  p_order_item_id BIGINT,
  p_user_id BIGINT,
  p_quantity INT,
  p_reason TEXT
)
RETURNS BIGINT
LANGUAGE SQL
AS $$
INSERT INTO return_requests(order_item_id, user_id, quantity, reason)
VALUES (p_order_item_id, p_user_id, p_quantity, p_reason)
RETURNING id;
$$;


CREATE OR REPLACE FUNCTION fn_process_return(
  p_return_id BIGINT,
  p_admin_user_id BIGINT,
  p_approve BOOLEAN,
  p_approved_quantity INT,
  p_restock BOOLEAN
)
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
DECLARE
  r_req return_requests%ROWTYPE;
  r_item order_items%ROWTYPE;
BEGIN
  -- lock the return
  SELECT * INTO r_req
  FROM return_requests
  WHERE id = p_return_id
  FOR UPDATE;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Return % not found', p_return_id;
  END IF;

  IF r_req.status <> 'requested' THEN
    RAISE EXCEPTION 'Return % already processed with status %',
      p_return_id, r_req.status;
  END IF;

  -- lock the order item
  SELECT * INTO r_item
  FROM order_items
  WHERE id = r_req.order_item_id
  FOR UPDATE;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Order item % not found for return %',
      r_req.order_item_id, p_return_id;
  END IF;

  IF NOT p_approve THEN
    UPDATE return_requests
    SET status = 'rejected',
        processed_by = p_admin_user_id,
        approved_quantity = NULL,
        restock = NULL,
        processed_at = NOW()
    WHERE id = p_return_id;
    RETURN;
  END IF;

  -- validate approved qty
  IF p_approved_quantity IS NULL OR p_approved_quantity <= 0 THEN
    RAISE EXCEPTION 'Approved quantity must be positive';
  END IF;

  IF p_approved_quantity > r_req.quantity THEN
    RAISE EXCEPTION 'Approved quantity % exceeds requested %',
      p_approved_quantity, r_req.quantity;
  END IF;

  IF p_approved_quantity > r_item.quantity THEN
    RAISE EXCEPTION 'Approved quantity % exceeds ordered quantity %',
      p_approved_quantity, r_item.quantity;
  END IF;

  -- restock or mark as received
  IF p_restock THEN
    IF r_item.variant_id IS NOT NULL THEN
      UPDATE product_variants
      SET stock_quantity = stock_quantity + p_approved_quantity,
          updated_at = NOW()
      WHERE id = r_item.variant_id;

      INSERT INTO inventory_transactions(
        product_id, variant_id, quantity_change, reason, order_id
      )
      VALUES (
        r_item.product_id,
        r_item.variant_id,
        p_approved_quantity,
        'return_received',
        r_item.order_id
      );
    ELSE
      UPDATE products
      SET stock_quantity = stock_quantity + p_approved_quantity,
          updated_at = NOW()
      WHERE id = r_item.product_id;

      INSERT INTO inventory_transactions(
        product_id, variant_id, quantity_change, reason, order_id
      )
      VALUES (
        r_item.product_id,
        NULL,
        p_approved_quantity,
        'return_received',
        r_item.order_id
      );
    END IF;

    UPDATE return_requests
    SET status = 'restocked',
        processed_by = p_admin_user_id,
        approved_quantity = p_approved_quantity,
        restock = TRUE,
        processed_at = NOW()
    WHERE id = p_return_id;
  ELSE
    UPDATE return_requests
    SET status = 'received',
        processed_by = p_admin_user_id,
        approved_quantity = p_approved_quantity,
        restock = FALSE,
        processed_at = NOW()
    WHERE id = p_return_id;
  END IF;
END$$;


-- Admin helpers
-- Update a user's password hash (app should pass Hash::make output)
CREATE OR REPLACE FUNCTION fn_user_set_password(
  p_user_id BIGINT,
  p_new_hash TEXT,
  p_force_change BOOLEAN DEFAULT FALSE
)
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
BEGIN
  IF NOT (p_new_hash ~ '^\$argon2id\$' OR p_new_hash ~ '^\$2[aby]\$') THEN
    RAISE EXCEPTION 'Password must be a hash (argon2id or bcrypt)';
  END IF;

  UPDATE users
  SET password_hash = p_new_hash,
      password_must_change = p_force_change,
      password_changed_at = NOW(),
      updated_at = NOW()
  WHERE id = p_user_id;
END$$;


-- Sales snapshot
CREATE OR REPLACE FUNCTION fn_admin_sales_summary(
  from_inclusive TIMESTAMPTZ,
  to_exclusive TIMESTAMPTZ
)
RETURNS TABLE(
  total_orders INT,
  items_sold INT,
  gross_amount NUMERIC
)
LANGUAGE SQL
AS $$
WITH scoped AS (
  SELECT id, total_amount
  FROM orders
  WHERE placed_at >= from_inclusive
    AND placed_at < to_exclusive
    AND status IN ('placed','processing','shipped','completed')
),
items AS (
  SELECT oi.order_id, SUM(oi.quantity) AS qty
  FROM order_items oi
  JOIN scoped s ON s.id = oi.order_id
  GROUP BY oi.order_id
)
SELECT
  (SELECT COUNT(*) FROM scoped)::INT AS total_orders,
  COALESCE((SELECT SUM(qty) FROM items), 0)::INT AS items_sold,
  COALESCE((SELECT SUM(total_amount) FROM scoped), 0)::NUMERIC AS gross_amount;
$$;


CREATE OR REPLACE FUNCTION fn_get_product_images(p_product_id BIGINT)
RETURNS TABLE(
  image_id BIGINT,
  url TEXT,
  alt_text TEXT,
  sort_order INT
)
LANGUAGE SQL
AS $$
SELECT id AS image_id, url, alt_text, sort_order
FROM product_images
WHERE product_id = p_product_id
ORDER BY sort_order ASC, id ASC;
$$;


-- categories for a parent slug, ordered
CREATE OR REPLACE FUNCTION fn_list_categories_by_parent_slug(p_parent_slug TEXT)
RETURNS TABLE(
  category_id BIGINT,
  name TEXT,
  slug TEXT,
  sort_order INT
)
LANGUAGE SQL
AS $$
SELECT c.id, c.name, c.slug, c.sort_order
FROM categories c
LEFT JOIN categories p ON p.id = c.parent_id
WHERE (p_parent_slug IS NULL AND c.parent_id IS NULL)
   OR (p_parent_slug IS NOT NULL AND p.slug = p_parent_slug)
ORDER BY c.sort_order ASC, c.name ASC;
$$;


-- set one image as cover (becomes sort 0), shift others down
CREATE OR REPLACE FUNCTION fn_set_primary_product_image(
  p_product_id BIGINT,
  p_image_id BIGINT
)
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
BEGIN
  -- Serialize reorders per product to avoid races
  PERFORM pg_advisory_xact_lock(1, p_product_id);
  
  WITH imgs AS (
    SELECT id, sort_order
    FROM product_images
    WHERE product_id = p_product_id
  ),
  non_primary AS (
    SELECT
      id,
      ROW_NUMBER() OVER (ORDER BY sort_order, id) AS rn
    FROM imgs
    WHERE id <> p_image_id
  ),
  ordered AS (
    -- primary image becomes 0
    SELECT id, 0 AS new_sort
    FROM imgs
    WHERE id = p_image_id

    UNION ALL

    -- all others become 1..n in existing order
    SELECT id, rn AS new_sort
    FROM non_primary
  )
  UPDATE product_images pi
  SET sort_order = o.new_sort
  FROM ordered o
  WHERE pi.id = o.id;
END$$;

-- reorder images exactly as provided (0..n-1), any missing keep their current spot
CREATE OR REPLACE FUNCTION fn_reorder_product_images(p_product_id BIGINT, p_image_ids BIGINT[])
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
BEGIN
  PERFORM pg_advisory_xact_lock(1, p_product_id);

  WITH ord AS (
    SELECT img_id, ord - 1 AS new_sort
    FROM UNNEST(p_image_ids) WITH ORDINALITY AS t(img_id, ord)
  )
  UPDATE product_images pi
  SET sort_order = ord.new_sort
  FROM ord
  WHERE pi.product_id = p_product_id
    AND pi.id = ord.img_id;
END;
$$;


-- reorder category siblings (for a given parent) to 0..n-1 in the array order
CREATE OR REPLACE FUNCTION fn_reorder_category_siblings(p_parent_id BIGINT, p_category_ids BIGINT[])
RETURNS VOID
LANGUAGE PLPGSQL
AS $$
DECLARE
  v_parent BIGINT := COALESCE(p_parent_id, 0);
BEGIN
  PERFORM pg_advisory_xact_lock(2, v_parent);

  WITH ord AS (
    SELECT cat_id, ord - 1 AS new_sort
    FROM UNNEST(p_category_ids) WITH ORDINALITY AS t(cat_id, ord)
  )
  UPDATE categories c
  SET sort_order = ord.new_sort
  FROM ord
  WHERE c.parent_id IS NOT DISTINCT FROM p_parent_id
    AND c.id = ord.cat_id;
END;
$$;


-- Views
CREATE OR REPLACE VIEW v_product_stock_overview AS
SELECT
  p.id AS product_id,
  p.name,
  p.has_variants,
  CASE
    WHEN p.has_variants = FALSE THEN p.stock_quantity
    ELSE COALESCE(v.total_stock, 0)
  END AS product_stock_qty,
  CASE
    WHEN (p.has_variants = FALSE AND p.stock_quantity <= 0)
      OR (p.has_variants = TRUE AND COALESCE(v.total_stock,0) <= 0)
      THEN 'OUT_OF_STOCK'
    WHEN (p.has_variants = FALSE AND p.stock_quantity <= p.low_stock_threshold)
      OR (p.has_variants = TRUE AND COALESCE(v.total_stock,0) <= p.low_stock_threshold)
      THEN 'LOW_STOCK'
    ELSE 'IN_STOCK'
  END AS product_stock_status
FROM products p
LEFT JOIN LATERAL (
  SELECT SUM(v.stock_quantity) AS total_stock
  FROM product_variants v
  WHERE v.product_id = p.id
) v ON TRUE;


CREATE OR REPLACE VIEW v_order_status_summary AS
SELECT status, COUNT(*) AS count
FROM orders
GROUP BY status;
