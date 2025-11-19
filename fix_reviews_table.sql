-- Script de correction pour ajouter product_id à la table reviews existante
-- À exécuter dans l'éditeur SQL de Supabase si vous avez l'erreur "column product_id does not exist"

-- Ajouter la colonne product_id si elle n'existe pas
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'reviews' 
        AND column_name = 'product_id'
    ) THEN
        ALTER TABLE reviews ADD COLUMN product_id UUID REFERENCES products(id) ON DELETE SET NULL;
        RAISE NOTICE 'Colonne product_id ajoutée avec succès';
    ELSE
        RAISE NOTICE 'Colonne product_id existe déjà';
    END IF;
END $$;

-- Créer l'index sur product_id si nécessaire
CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_product_approved ON reviews(product_id, approved) WHERE approved = true;

