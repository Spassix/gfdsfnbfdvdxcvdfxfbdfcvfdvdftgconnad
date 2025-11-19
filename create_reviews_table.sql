-- Script pour créer/mettre à jour la table reviews sur Supabase
-- À exécuter dans l'éditeur SQL de Supabase

-- ============================================
-- TABLE: REVIEWS (Avis clients)
-- ============================================

-- Créer la table si elle n'existe pas
CREATE TABLE IF NOT EXISTS reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    author VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5) DEFAULT 5,
    image_url TEXT,
    approved BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

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
    END IF;
END $$;

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_reviews_approved ON reviews(approved);
CREATE INDEX IF NOT EXISTS idx_reviews_created ON reviews(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_product_approved ON reviews(product_id, approved) WHERE approved = true;

-- Fonction pour mettre à jour updated_at automatiquement
CREATE OR REPLACE FUNCTION update_reviews_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour updated_at
DROP TRIGGER IF EXISTS update_reviews_updated_at ON reviews;
CREATE TRIGGER update_reviews_updated_at 
    BEFORE UPDATE ON reviews 
    FOR EACH ROW 
    EXECUTE FUNCTION update_reviews_updated_at();

-- Activer RLS (Row Level Security)
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

-- Supprimer les anciennes politiques si elles existent
DROP POLICY IF EXISTS "Anyone can read approved reviews" ON reviews;
DROP POLICY IF EXISTS "Anyone can create a review" ON reviews;

-- Politique: Tout le monde peut lire les avis approuvés
CREATE POLICY "Anyone can read approved reviews" 
    ON reviews FOR SELECT 
    USING (approved = true);

-- Politique: Tout le monde peut créer un avis
CREATE POLICY "Anyone can create a review" 
    ON reviews FOR INSERT 
    WITH CHECK (true);

-- Politique: Seuls les admins peuvent modifier/supprimer (via service key)
-- Les admins utilisent la service key donc pas besoin de politique spécifique
