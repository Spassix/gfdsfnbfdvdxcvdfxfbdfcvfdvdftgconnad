# 🚀 Installation Complète - Boutique + Panel Admin

## 📋 Étapes d'installation

### Étape 1 : Créer toutes les tables dans Supabase

1. Allez sur **https://supabase.com/dashboard**
2. Sélectionnez votre projet
3. Allez dans **SQL Editor** (éditeur SQL)
4. Ouvrez le fichier `supabase_schema.sql` dans votre projet
5. **Copiez tout le contenu** du fichier
6. **Collez-le dans l'éditeur SQL** de Supabase
7. Cliquez sur **Run** (Exécuter)

✅ Ce script crée **14 tables** :
- `admins` - Comptes administrateurs
- `categories` - Catégories de produits
- `farms` - Fermes/producteurs
- `products` - Produits
- `orders` - Commandes
- `socials` - Réseaux sociaux
- `settings` - Paramètres généraux
- `theme_settings` - Couleurs/thème
- `typography` - Typographie
- `maintenance` - Mode maintenance
- `loading_page` - Page de chargement
- `season_events` - Événements saisonniers
- `coupons` - Codes promo
- `reviews` - Avis clients

### Étape 2 : Configurer les politiques RLS (Row Level Security)

1. Toujours dans **SQL Editor** de Supabase
2. Ouvrez le fichier `supabase_rls_policies_complete.sql`
3. **Copiez tout le contenu** du fichier
4. **Collez-le dans l'éditeur SQL** de Supabase
5. Cliquez sur **Run** (Exécuter)

✅ Ce script configure les permissions pour que :
- La boutique puisse lire les produits, catégories, etc. (clé anonyme)
- Le panel admin puisse tout gérer (service key)

### Étape 3 : Créer votre compte administrateur

1. Ouvrez dans votre navigateur : `http://localhost:8000/create_admin.php`
2. Remplissez le formulaire avec :
   - **Username** : votre nom d'utilisateur
   - **Email** : votre email
   - **Password** : votre mot de passe
3. Cliquez sur **Créer l'administrateur**

✅ Votre compte admin est créé !

### Étape 4 : Se connecter au panel admin

1. Ouvrez : `http://localhost:8000/admin/login.php`
2. Connectez-vous avec vos identifiants
3. Vous accédez au **Panel Admin**

## ✅ Vérification

Après l'installation, vérifiez que :

1. ✅ **Toutes les tables existent** dans Supabase (Table Editor)
2. ✅ **Les politiques RLS sont actives** (Authentication > Policies)
3. ✅ **Vous pouvez vous connecter** au panel admin
4. ✅ **La boutique s'affiche** (`/shop/index.php`)
5. ✅ **Les produits s'affichent** (`/shop/products.php`)

## 📝 Données par défaut créées

Le script SQL crée automatiquement :

- ✅ **5 événements saisonniers** : Noël, Halloween, St-Valentin, Pâques, Nouvel An
- ✅ **Thème par défaut** : Mode sombre avec couleurs configurées
- ✅ **Typographie par défaut** : Police Inter
- ✅ **Maintenance** : Désactivée par défaut
- ✅ **Loading page** : Désactivée par défaut

## 🔧 Configuration

### Clés API (déjà configurées dans `config.php`)
- ✅ **SUPABASE_URL** : Votre URL Supabase
- ✅ **SUPABASE_ANON_KEY** : Clé anonyme (pour la boutique)
- ✅ **SUPABASE_SERVICE_KEY** : Clé service (pour l'admin)

## 🎯 Prochaines étapes

1. **Créer des catégories** : `/admin/categories.php`
2. **Créer des farms** : `/admin/farms.php`
3. **Créer des produits** : `/admin/products.php`
4. **Configurer les paramètres** : `/admin/settings.php`
5. **Personnaliser les couleurs** : `/admin/colors.php`

## ⚠️ Problèmes courants

### "Invalid API key"
- ✅ Vérifiez que `SUPABASE_ANON_KEY` est correcte dans `config.php`
- ✅ La clé doit commencer par `eyJ...` (JWT)

### "No data will be selectable"
- ✅ Exécutez le script `supabase_rls_policies_complete.sql`
- ✅ Vérifiez que RLS est activé avec des politiques

### Les produits ne s'affichent pas
- ✅ Vérifiez que les produits ont `active = true`
- ✅ Vérifiez que les catégories ont `enabled = true`
- ✅ Vérifiez que les farms ont `enabled = true`

---

**Tout est prêt ! 🎉**

