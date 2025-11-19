-- ============================================
-- 🗄️ SCHEMA SUPABASE COMPLET
-- Pour Boutique + Panel Admin
-- ============================================
-- 
-- INSTRUCTIONS:
-- 1. Allez sur https://supabase.com/dashboard
-- 2. Sélectionnez votre projet
-- 3. Allez dans SQL Editor
-- 4. Collez ce script et exécutez-le
-- ============================================

-- ============================================
-- 1️⃣ TABLE: ADMINS (Authentification)
-- ============================================
CREATE TABLE IF NOT EXISTS admins (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    active BOOLEAN DEFAULT true,
    last_login TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_admins_username ON admins(username);
CREATE INDEX IF NOT EXISTS idx_admins_email ON admins(email);

-- ============================================
-- 2️⃣ TABLE: CATEGORIES (Catégories)
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL UNIQUE,
    icon VARCHAR(10),
    image_url TEXT,
    description TEXT,
    enabled BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_categories_enabled ON categories(enabled);

-- ============================================
-- 3️⃣ TABLE: FARMS (Fermes)
-- ============================================
CREATE TABLE IF NOT EXISTS farms (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    image_url TEXT,
    location VARCHAR(255),
    enabled BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_farms_enabled ON farms(enabled);

-- ============================================
-- 4️⃣ TABLE: PRODUCTS (Produits)
-- ============================================
CREATE TABLE IF NOT EXISTS products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id UUID REFERENCES categories(id) ON DELETE SET NULL,
    farm_id UUID REFERENCES farms(id) ON DELETE SET NULL,
    photo_url TEXT,
    video_url TEXT,
    medias JSONB DEFAULT '[]'::jsonb,
    variants JSONB DEFAULT '[]'::jsonb,
    price DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'g',
    featured BOOLEAN DEFAULT false,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_farm ON products(farm_id);
CREATE INDEX IF NOT EXISTS idx_products_active ON products(active);
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(featured);

-- ============================================
-- 5️⃣ TABLE: ORDERS (Commandes)
-- ============================================
CREATE TABLE IF NOT EXISTS orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer JSONB NOT NULL,
    delivery_method VARCHAR(100) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    products JSONB NOT NULL DEFAULT '[]'::jsonb,
    subtotal DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    whatsapp_sent BOOLEAN DEFAULT false,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_orders_number ON orders(order_number);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders(created_at DESC);

-- Séquence pour les numéros de commande
CREATE SEQUENCE IF NOT EXISTS order_seq START 1;

-- Fonction pour générer le numéro de commande
CREATE OR REPLACE FUNCTION generate_order_number()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        NEW.order_number := 'CMD-' || TO_CHAR(NOW(), 'YYYYMMDD') || '-' || LPAD(NEXTVAL('order_seq')::TEXT, 6, '0');
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour générer automatiquement le numéro
DROP TRIGGER IF EXISTS set_order_number ON orders;
CREATE TRIGGER set_order_number
    BEFORE INSERT ON orders
    FOR EACH ROW
    EXECUTE FUNCTION generate_order_number();

-- ============================================
-- 6️⃣ TABLE: SOCIALS (Réseaux sociaux)
-- ============================================
CREATE TABLE IF NOT EXISTS socials (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(10),
    description TEXT,
    url TEXT NOT NULL,
    services JSONB DEFAULT '[]'::jsonb,
    enabled BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_socials_enabled ON socials(enabled);

-- ============================================
-- 7️⃣ TABLE: SETTINGS (Paramètres généraux)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    key VARCHAR(255) UNIQUE NOT NULL,
    value JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(key);

-- ============================================
-- 8️⃣ TABLE: THEME_SETTINGS (Couleurs)
-- ============================================
CREATE TABLE IF NOT EXISTS theme_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    preset VARCHAR(50),
    colors JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- 9️⃣ TABLE: TYPOGRAPHY (Typographie)
-- ============================================
CREATE TABLE IF NOT EXISTS typography (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    font_family VARCHAR(255) NOT NULL DEFAULT 'Inter',
    font_weights JSONB DEFAULT '[400, 500, 600, 700]'::jsonb,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- 🔟 TABLE: MAINTENANCE (Maintenance)
-- ============================================
CREATE TABLE IF NOT EXISTS maintenance (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    enabled BOOLEAN DEFAULT false,
    message TEXT,
    image_url TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- 1️⃣1️⃣ TABLE: LOADING_PAGE (Page de chargement)
-- ============================================
CREATE TABLE IF NOT EXISTS loading_page (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    enabled BOOLEAN DEFAULT false,
    text VARCHAR(255) DEFAULT 'Chargement...',
    style VARCHAR(50) DEFAULT 'spinner',
    duration_ms INTEGER DEFAULT 2000,
    background_type VARCHAR(20) DEFAULT 'color',
    background_color VARCHAR(20) DEFAULT '#0d0f17',
    background_image_url TEXT,
    background_video_url TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- ============================================
-- 1️⃣2️⃣ TABLE: SEASON_EVENTS (Événements saisonniers)
-- ============================================
CREATE TABLE IF NOT EXISTS season_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL UNIQUE,
    enabled BOOLEAN DEFAULT false,
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_season_events_enabled ON season_events(enabled);
CREATE INDEX IF NOT EXISTS idx_season_events_dates ON season_events(start_date, end_date);

-- ============================================
-- 1️⃣3️⃣ TABLE: COUPONS (Codes promo)
-- ============================================
CREATE TABLE IF NOT EXISTS coupons (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) UNIQUE NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'fixed',
    value DECIMAL(10,2) NOT NULL,
    min_amount DECIMAL(10,2) DEFAULT 0,
    expires_at TIMESTAMPTZ,
    enabled BOOLEAN DEFAULT true,
    usage_count INTEGER DEFAULT 0,
    max_usage INTEGER,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code);
CREATE INDEX IF NOT EXISTS idx_coupons_enabled ON coupons(enabled);
CREATE INDEX IF NOT EXISTS idx_coupons_expires ON coupons(expires_at);

-- ============================================
-- 1️⃣4️⃣ TABLE: REVIEWS (Avis clients)
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    author VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5) DEFAULT 5,
    image_url TEXT,
    product_id UUID REFERENCES products(id) ON DELETE SET NULL,
    approved BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_reviews_approved ON reviews(approved);
CREATE INDEX IF NOT EXISTS idx_reviews_created ON reviews(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_product_approved ON reviews(product_id, approved) WHERE approved = true;

-- ============================================
-- 🔧 FONCTIONS UTILITAIRES
-- ============================================

-- Fonction pour mettre à jour updated_at automatiquement
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Appliquer le trigger sur toutes les tables
DROP TRIGGER IF EXISTS update_admins_updated_at ON admins;
CREATE TRIGGER update_admins_updated_at BEFORE UPDATE ON admins FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_products_updated_at ON products;
CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_categories_updated_at ON categories;
CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON categories FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_farms_updated_at ON farms;
CREATE TRIGGER update_farms_updated_at BEFORE UPDATE ON farms FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_orders_updated_at ON orders;
CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_socials_updated_at ON socials;
CREATE TRIGGER update_socials_updated_at BEFORE UPDATE ON socials FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_settings_updated_at ON settings;
CREATE TRIGGER update_settings_updated_at BEFORE UPDATE ON settings FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_theme_settings_updated_at ON theme_settings;
CREATE TRIGGER update_theme_settings_updated_at BEFORE UPDATE ON theme_settings FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_typography_updated_at ON typography;
CREATE TRIGGER update_typography_updated_at BEFORE UPDATE ON typography FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_maintenance_updated_at ON maintenance;
CREATE TRIGGER update_maintenance_updated_at BEFORE UPDATE ON maintenance FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_loading_page_updated_at ON loading_page;
CREATE TRIGGER update_loading_page_updated_at BEFORE UPDATE ON loading_page FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_season_events_updated_at ON season_events;
CREATE TRIGGER update_season_events_updated_at BEFORE UPDATE ON season_events FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_coupons_updated_at ON coupons;
CREATE TRIGGER update_coupons_updated_at BEFORE UPDATE ON coupons FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

DROP TRIGGER IF EXISTS update_reviews_updated_at ON reviews;
CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- 📝 DONNÉES PAR DÉFAUT
-- ============================================

-- Insérer les événements saisonniers par défaut
INSERT INTO season_events (name, enabled) VALUES
    ('Noël', false),
    ('Halloween', false),
    ('St-Valentin', false),
    ('Pâques', false),
    ('Nouvel An', false)
ON CONFLICT (name) DO NOTHING;

-- Insérer les paramètres de thème par défaut
INSERT INTO theme_settings (id, preset, colors) 
VALUES (
    '00000000-0000-0000-0000-000000000001',
    'dark',
    '{
        "textPrimary": "#ffffff",
        "textSecondary": "#cccccc",
        "titles": "#ffffff",
        "backgroundMain": "#0d0f17",
        "backgroundCards": "#1a1d29",
        "borders": "#333333",
        "buttonText": "#000000",
        "buttonBg": "#ffffff",
        "links": "#667eea",
        "accent": "#764ba2"
    }'::jsonb
)
ON CONFLICT DO NOTHING;

-- Insérer la typographie par défaut
INSERT INTO typography (id, font_family) 
VALUES ('00000000-0000-0000-0000-000000000001', 'Inter')
ON CONFLICT DO NOTHING;

-- Insérer la maintenance par défaut
INSERT INTO maintenance (id, enabled, message) 
VALUES ('00000000-0000-0000-0000-000000000001', false, 'Site en maintenance')
ON CONFLICT DO NOTHING;

-- Insérer la loading page par défaut
INSERT INTO loading_page (id, enabled) 
VALUES ('00000000-0000-0000-0000-000000000001', false)
ON CONFLICT DO NOTHING;

-- ============================================
-- ✅ FIN DU SCHÉMA
-- ============================================
-- 
-- Après avoir exécuté ce script:
-- 1. Vous pouvez créer votre compte admin via create_admin.php
-- 2. Toutes les tables sont prêtes pour la boutique
-- ============================================

