-- Script pour corriger la politique RLS des reviews
-- À exécuter dans l'éditeur SQL de Supabase

-- Supprimer TOUTES les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Anyone can read approved reviews" ON reviews;
DROP POLICY IF EXISTS "Anyone can create a review" ON reviews;
DROP POLICY IF EXISTS "Approved reviews are viewable by everyone" ON reviews;

-- S'assurer que RLS est activé
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

-- Politique: Tout le monde peut lire les avis approuvés
CREATE POLICY "Anyone can read approved reviews" 
    ON reviews FOR SELECT 
    USING (approved = true);

-- Politique: Tout le monde peut créer un avis (sans restriction)
-- IMPORTANT: WITH CHECK (true) permet à n'importe qui d'insérer
CREATE POLICY "Anyone can create a review" 
    ON reviews FOR INSERT 
    WITH CHECK (true);

