-- ============================================
-- 🔒 CORRECTION FINALE DES POLITIQUES RLS POUR REVIEWS
-- ============================================
-- À exécuter dans l'éditeur SQL de Supabase
-- 
-- Ce script corrige le problème "new row violates row-level security policy"
-- en s'assurant que la politique INSERT permet bien l'insertion publique
-- ============================================

-- S'assurer que RLS est activé
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

-- Supprimer TOUTES les anciennes politiques pour recommencer proprement
DROP POLICY IF EXISTS "Anyone can read approved reviews" ON reviews;
DROP POLICY IF EXISTS "Anyone can create a review" ON reviews;
DROP POLICY IF EXISTS "Approved reviews are viewable by everyone" ON reviews;
DROP POLICY IF EXISTS "Public can read approved reviews" ON reviews;
DROP POLICY IF EXISTS "Public can insert reviews" ON reviews;
DROP POLICY IF EXISTS "Reviews are viewable by everyone" ON reviews;

-- Politique 1: Lecture publique des avis approuvés
CREATE POLICY "Approved reviews are viewable by everyone" 
    ON reviews 
    FOR SELECT 
    USING (approved = true);

-- Politique 2: Insertion publique (CRITIQUE - doit permettre l'insertion sans restriction)
-- IMPORTANT: WITH CHECK (true) permet à n'importe qui d'insérer un avis
CREATE POLICY "Anyone can create a review" 
    ON reviews 
    FOR INSERT 
    WITH CHECK (true);

-- Vérifier que les politiques sont bien créées
SELECT 
    schemaname, 
    tablename, 
    policyname, 
    permissive, 
    roles, 
    cmd, 
    qual, 
    with_check
FROM pg_policies 
WHERE tablename = 'reviews'
ORDER BY policyname;

-- ============================================
-- ✅ VÉRIFICATION
-- ============================================
-- Après avoir exécuté ce script, vous devriez voir 2 politiques :
-- 1. "Approved reviews are viewable by everyone" (SELECT)
-- 2. "Anyone can create a review" (INSERT)
-- 
-- La politique INSERT doit avoir with_check = 'true'
-- ============================================

