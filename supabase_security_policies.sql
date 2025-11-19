-- ============================================
-- 🔒 POLITIQUES DE SÉCURITÉ SUPABASE (RLS)
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

-- Activer RLS sur la table admins
ALTER TABLE admins ENABLE ROW LEVEL SECURITY;

-- Politique : Personne ne peut lire les admins (même pas les admins eux-mêmes via l'API publique)
-- Seul le service role key peut accéder à cette table
CREATE POLICY "Admins table is private"
ON admins
FOR ALL
USING (false)
WITH CHECK (false);

-- Note: L'accès aux admins se fait uniquement via le service role key dans le code PHP
-- Les requêtes utilisent $useServiceKey = true pour les opérations admin

-- ============================================
-- 2️⃣ TABLE: PRODUCTS (Lecture publique, écriture admin)
-- ============================================

-- Activer RLS sur products
ALTER TABLE products ENABLE ROW LEVEL SECURITY;

-- Politique : Tout le monde peut lire les produits actifs
CREATE POLICY "Products are viewable by everyone"
ON products
FOR SELECT
USING (active = true);

-- Politique : Seul le service role peut créer/modifier/supprimer
-- (géré via le code PHP avec service key)

-- ============================================
-- 3️⃣ TABLE: CATEGORIES (Lecture publique, écriture admin)
-- ============================================

ALTER TABLE categories ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Categories are viewable by everyone"
ON categories
FOR SELECT
USING (enabled = true);

-- ============================================
-- 4️⃣ TABLE: FARMS (Lecture publique, écriture admin)
-- ============================================

ALTER TABLE farms ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Farms are viewable by everyone"
ON farms
FOR SELECT
USING (enabled = true);

-- ============================================
-- 5️⃣ TABLE: ORDERS (Privée, admin uniquement)
-- ============================================

ALTER TABLE orders ENABLE ROW LEVEL SECURITY;

-- Personne ne peut lire les commandes via l'API publique
CREATE POLICY "Orders are private"
ON orders
FOR ALL
USING (false)
WITH CHECK (false);

-- Note: L'accès aux commandes se fait uniquement via le service role key

-- ============================================
-- 6️⃣ TABLE: REVIEWS (Lecture publique pour approuvés, écriture publique)
-- ============================================

ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

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

-- Politique : Seul le service role peut modifier/supprimer (géré via code PHP)

-- ============================================
-- 7️⃣ TABLE: COUPONS (Lecture publique pour actifs, écriture admin)
-- ============================================

ALTER TABLE coupons ENABLE ROW LEVEL SECURITY;

-- Politique : Tout le monde peut lire les coupons actifs
CREATE POLICY "Active coupons are viewable by everyone"
ON coupons
FOR SELECT
USING (enabled = true);

-- ============================================
-- 8️⃣ TABLE: SETTINGS (Lecture publique, écriture admin)
-- ============================================

ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- Politique : Tout le monde peut lire les settings
CREATE POLICY "Settings are viewable by everyone"
ON settings
FOR SELECT
USING (true);

-- ============================================
-- 9️⃣ TABLE: SOCIALS (Lecture publique pour activés)
-- ============================================

ALTER TABLE socials ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Enabled socials are viewable by everyone"
ON socials
FOR SELECT
USING (enabled = true);

-- ============================================
-- 🔟 STORAGE BUCKETS (Sécurisation)
-- ============================================

-- Les buckets 'photos' et 'videos' doivent être configurés dans Supabase Dashboard :
-- 1. Allez dans Storage > Policies
-- 2. Pour chaque bucket, créez une politique :
--    - SELECT (lecture) : Public (tout le monde peut lire)
--    - INSERT (upload) : Service role uniquement (via code PHP)
--    - UPDATE/DELETE : Service role uniquement

-- ============================================
-- 📝 NOTES IMPORTANTES
-- ============================================
-- 
-- 1. Les mots de passe sont hashés avec bcrypt (cost 12) dans le code PHP
-- 2. Les requêtes admin utilisent toujours le service role key
-- 3. Les requêtes publiques utilisent l'anonymous key
-- 4. Les mots de passe ne sont JAMAIS exposés dans les réponses API
-- 5. Les sessions PHP sont sécurisées (httponly, secure en production)
-- 
-- ============================================

