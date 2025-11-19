# 📋 Guide d'Installation - Boutique PHP + Panel Admin

## 🚀 Étape 1 : Créer les tables dans Supabase

### 1.1 Accéder au SQL Editor
1. Allez sur https://supabase.com/dashboard
2. Sélectionnez votre projet
3. Cliquez sur **SQL Editor** dans le menu de gauche

### 1.2 Exécuter le script SQL
1. Ouvrez le fichier `supabase_schema.sql` dans votre éditeur
2. Copiez **TOUT le contenu** du fichier
3. Collez-le dans le SQL Editor de Supabase
4. Cliquez sur **Run** (ou appuyez sur Ctrl+Enter)

### 1.3 Vérifier que les tables sont créées
Dans Supabase Dashboard > **Table Editor**, vous devriez voir :
- ✅ admins
- ✅ categories
- ✅ farms
- ✅ products
- ✅ orders
- ✅ socials
- ✅ settings
- ✅ theme_settings
- ✅ typography
- ✅ maintenance
- ✅ loading_page
- ✅ season_events
- ✅ coupons
- ✅ reviews

## 🔐 Étape 2 : Créer votre compte admin

### 2.1 Démarrer le serveur PHP
```bash
cd C:\Users\fxxre\Desktop\gay
php -S localhost:8000
```

Ou double-cliquez sur `start-server.bat`

### 2.2 Créer le compte
1. Accédez à : `http://localhost:8000/create_admin.php`
2. Remplissez le formulaire :
   - Nom d'utilisateur (ex: `admin`)
   - Email (ex: `admin@example.com`)
   - Mot de passe (minimum 6 caractères)
   - Confirmez le mot de passe
3. Cliquez sur **Créer le compte admin**

### 2.3 Se connecter
1. Accédez à : `http://localhost:8000/admin/login.php`
2. Connectez-vous avec vos identifiants

### 2.4 ⚠️ SÉCURITÉ IMPORTANTE
**Supprimez le fichier `create_admin.php` après avoir créé votre compte !**

## 🛒 Étape 3 : Tester la boutique

### 3.1 Accéder à la boutique
- Accueil : `http://localhost:8000/shop/index.php`
- Produits : `http://localhost:8000/shop/products.php`

### 3.2 Ajouter des produits (via Supabase)
1. Allez dans Supabase Dashboard > **Table Editor** > `products`
2. Cliquez sur **Insert** > **Insert row**
3. Remplissez :
   - `name` : Nom du produit
   - `description` : Description
   - `price` : Prix (ex: 10.00)
   - `active` : `true`
   - `variants` : `[{"name": "3.5g", "price": 10, "qty": 1}]` (format JSON)
4. Cliquez sur **Save**

## 📁 Structure des fichiers

```
gay/
├── shop/              # Boutique publique
│   ├── index.php      # Accueil
│   ├── products.php   # Liste produits
│   ├── product.php    # Détail produit
│   ├── cart.php       # Panier
│   └── checkout.php   # Commande
│
├── admin/             # Panel admin
│   ├── login.php      # Connexion
│   └── config.php     # Config admin
│
├── checkout.php       # Traitement commandes
├── config.php         # Config Supabase
├── supabase_client.php # Client Supabase
├── create_admin.php   # Créer admin (à supprimer après)
└── supabase_schema.sql # Script SQL
```

## ✅ Checklist de vérification

- [ ] Tables créées dans Supabase
- [ ] Compte admin créé
- [ ] Fichier `create_admin.php` supprimé
- [ ] Boutique accessible
- [ ] Panel admin accessible
- [ ] Au moins un produit ajouté dans Supabase

## 🆘 Dépannage

### Erreur "Table not found"
→ Vérifiez que vous avez bien exécuté `supabase_schema.sql`

### Erreur "Invalid API key"
→ Vérifiez vos clés dans `config.php`

### Erreur de connexion admin
→ Vérifiez que la table `admins` existe et contient votre compte

### Produits ne s'affichent pas
→ Vérifiez que les produits ont `active = true` dans Supabase

## 📞 Support

Si vous rencontrez des problèmes, vérifiez :
1. Les logs PHP (erreurs affichées)
2. Les logs Supabase (Dashboard > Logs)
3. La console du navigateur (F12)

