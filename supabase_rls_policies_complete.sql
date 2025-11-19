-- ============================================
-- 🔒 POLITIQUES RLS COMPLÈTES POUR SUPABASE
-- ============================================
-- 
-- INSTRUCTIONS:
-- 1. Allez sur https://supabase.com/dashboard
-- 2. Sélectionnez votre projet
-- 3. Allez dans SQL Editor
-- 4. Collez ce script et exécutez-le
-- ============================================

-- ============================================
-- 1️⃣ TABLE: ADMINS (Sécurisation maximale)
-- ============================================

-- Activer RLS
ALTER TABLE admins ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Admins table is private" ON admins;

-- Politique : Personne ne peut lire les admins via l'API publique
-- Seul le service role key peut accéder à cette table
CREATE POLICY "Admins table is private"
ON admins
FOR ALL
USING (false)
WITH CHECK (false);

-- ============================================
-- 2️⃣ TABLE: PRODUCTS (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Products are viewable by everyone" ON products;

-- Politique : Tout le monde peut lire les produits actifs
CREATE POLICY "Products are viewable by everyone"
ON products
FOR SELECT
USING (active = true);

-- ============================================
-- 3️⃣ TABLE: CATEGORIES (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE categories ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Categories are viewable by everyone" ON categories;

-- Politique : Tout le monde peut lire les catégories activées
CREATE POLICY "Categories are viewable by everyone"
ON categories
FOR SELECT
USING (enabled = true);

-- ============================================
-- 4️⃣ TABLE: FARMS (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE farms ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Farms are viewable by everyone" ON farms;

-- Politique : Tout le monde peut lire les farms activées
CREATE POLICY "Farms are viewable by everyone"
ON farms
FOR SELECT
USING (enabled = true);

-- ============================================
-- 5️⃣ TABLE: ORDERS (Privée, admin uniquement)
-- ============================================

-- Activer RLS
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Orders are private" ON orders;

-- Politique : Personne ne peut lire les commandes via l'API publique
CREATE POLICY "Orders are private"
ON orders
FOR ALL
USING (false)
WITH CHECK (false);

-- ============================================
-- 6️⃣ TABLE: REVIEWS (Lecture publique pour approuvés, écriture publique)
-- ============================================

-- Activer RLS
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Approved reviews are viewable by everyone" ON reviews;
DROP POLICY IF EXISTS "Anyone can create a review" ON reviews;

-- Politique : Tout le monde peut lire les avis approuvés
CREATE POLICY "Approved reviews are viewable by everyone"
ON reviews
FOR SELECT
USING (approved = true);

-- Politique : Tout le monde peut créer un avis (sera approuvé par admin)
CREATE POLICY "Anyone can create a review"
ON reviews
FOR INSERT
WITH CHECK (true);

-- ============================================
-- 7️⃣ TABLE: COUPONS (Lecture publique pour actifs, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE coupons ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Active coupons are viewable by everyone" ON coupons;

-- Politique : Tout le monde peut lire les coupons actifs et non expirés
CREATE POLICY "Active coupons are viewable by everyone"
ON coupons
FOR SELECT
USING (
    enabled = true 
    AND (expires_at IS NULL OR expires_at > NOW())
    AND (max_usage IS NULL OR usage_count < max_usage)
);

-- ============================================
-- 8️⃣ TABLE: SETTINGS (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Settings are viewable by everyone" ON settings;

-- Politique : Tout le monde peut lire les settings
CREATE POLICY "Settings are viewable by everyone"
ON settings
FOR SELECT
USING (true);

-- ============================================
-- 9️⃣ TABLE: SOCIALS (Lecture publique pour activés)
-- ============================================

-- Activer RLS
ALTER TABLE socials ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Enabled socials are viewable by everyone" ON socials;

-- Politique : Tout le monde peut lire les réseaux sociaux activés
CREATE POLICY "Enabled socials are viewable by everyone"
ON socials
FOR SELECT
USING (enabled = true);

-- ============================================
-- 🔟 TABLE: THEME_SETTINGS (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE theme_settings ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Theme settings are viewable by everyone" ON theme_settings;

-- Politique : Tout le monde peut lire les paramètres de thème
CREATE POLICY "Theme settings are viewable by everyone"
ON theme_settings
FOR SELECT
USING (true);

-- ============================================
-- 1️⃣1️⃣ TABLE: TYPOGRAPHY (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE typography ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Typography is viewable by everyone" ON typography;

-- Politique : Tout le monde peut lire la typographie
CREATE POLICY "Typography is viewable by everyone"
ON typography
FOR SELECT
USING (true);

-- ============================================
-- 1️⃣2️⃣ TABLE: MAINTENANCE (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE maintenance ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Maintenance is viewable by everyone" ON maintenance;

-- Politique : Tout le monde peut lire les paramètres de maintenance
CREATE POLICY "Maintenance is viewable by everyone"
ON maintenance
FOR SELECT
USING (true);

-- ============================================
-- 1️⃣3️⃣ TABLE: LOADING_PAGE (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS
ALTER TABLE loading_page ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Loading page is viewable by everyone" ON loading_page;

-- Politique : Tout le monde peut lire les paramètres de la page de chargement
CREATE POLICY "Loading page is viewable by everyone"
ON loading_page
FOR SELECT
USING (true);

-- ============================================
-- 1️⃣4️⃣ TABLE: SEASON_EVENTS (Lecture publique pour activés)
-- ============================================

-- Activer RLS
ALTER TABLE season_events ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Enabled season events are viewable by everyone" ON season_events;

-- Politique : Tout le monde peut lire les événements saisonniers activés
CREATE POLICY "Enabled season events are viewable by everyone"
ON season_events
FOR SELECT
USING (enabled = true);

-- ============================================
-- 📝 NOTES IMPORTANTES
-- ============================================
-- 
-- 1. Les opérations d'écriture (INSERT, UPDATE, DELETE) sont gérées
--    uniquement via le service role key dans le code PHP
-- 2. Les requêtes admin utilisent toujours le service role key
-- 3. Les requêtes publiques utilisent l'anonymous key
-- 4. Les mots de passe ne sont JAMAIS exposés dans les réponses API
-- 5. Les sessions PHP sont sécurisées (httponly, secure en production)
-- 
-- ============================================

