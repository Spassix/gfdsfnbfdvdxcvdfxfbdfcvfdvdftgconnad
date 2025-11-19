-- ============================================
-- 🔄 MISE À JOUR TABLE REVIEWS
-- Ajouter product_id et image_url
-- ============================================

-- Ajouter la colonne product_id si elle n'existe pas
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS product_id UUID REFERENCES products(id) ON DELETE SET NULL;

-- Renommer image en image_url si nécessaire
DO $$ 
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_name='reviews' AND column_name='image') THEN
        ALTER TABLE reviews RENAME COLUMN image TO image_url;
    END IF;
END $$;

-- Créer un index sur product_id pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);

-- Créer un index composite pour les requêtes fréquentes
CREATE INDEX IF NOT EXISTS idx_reviews_product_approved ON reviews(product_id, approved) WHERE approved = true;

