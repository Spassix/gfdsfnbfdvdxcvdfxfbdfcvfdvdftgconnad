# 🔒 Configuration Supabase - Guide Complet

## 📋 Résumé de la Configuration

Vous devez configurer les **politiques RLS (Row Level Security)** dans Supabase pour que votre site fonctionne correctement. Actuellement, certaines tables ont RLS activé mais sans politiques, ce qui empêche l'accès aux données.

## ✅ Ce qui a été fait

1. ✅ **Script SQL complet créé** : `supabase_rls_policies_complete.sql`
2. ✅ **Pages admin implémentées** :
   - Typographie (`admin/typography.php`)
   - Maintenance (`admin/maintenance.php`)
   - Codes Promo (`admin/promos.php`)
3. ✅ **Fonctionnalité codes promo** : Implémentée dans le panier
4. ✅ **Correction des erreurs** : Fonction `isActive()` corrigée

## 🚀 Instructions pour Supabase

### Étape 1 : Exécuter le script SQL

1. Allez sur **https://supabase.com/dashboard**
2. Sélectionnez votre projet
3. Allez dans **SQL Editor** (éditeur SQL)
4. Ouvrez le fichier `supabase_rls_policies_complete.sql` dans votre projet
5. **Copiez tout le contenu** du fichier
6. **Collez-le dans l'éditeur SQL** de Supabase
7. Cliquez sur **Run** (Exécuter)

### Étape 2 : Vérifier les politiques

Après avoir exécuté le script, allez dans **Authentication > Policies** et vérifiez que toutes les tables ont des politiques :

- ✅ **admins** : RLS activé, politique privée (service key uniquement)
- ✅ **products** : RLS activé, lecture publique pour produits actifs
- ✅ **categories** : RLS activé, lecture publique pour catégories activées
- ✅ **farms** : RLS activé, lecture publique pour farms activées
- ✅ **orders** : RLS activé, politique privée (service key uniquement)
- ✅ **reviews** : RLS activé, lecture publique pour avis approuvés, écriture publique
- ✅ **coupons** : RLS activé, lecture publique pour coupons actifs
- ✅ **settings** : RLS activé, lecture publique
- ✅ **socials** : RLS activé, lecture publique pour réseaux activés
- ✅ **theme_settings** : RLS activé, lecture publique
- ✅ **typography** : RLS activé, lecture publique
- ✅ **maintenance** : RLS activé, lecture publique
- ✅ **loading_page** : RLS activé, lecture publique
- ✅ **season_events** : RLS activé, lecture publique pour événements activés

## 📝 Notes importantes

### Sécurité

- Les **opérations d'écriture** (INSERT, UPDATE, DELETE) sont gérées uniquement via le **service role key** dans le code PHP
- Les requêtes **admin** utilisent toujours le **service role key**
- Les requêtes **publiques** (boutique) utilisent l'**anonymous key**
- Les mots de passe ne sont **JAMAIS** exposés dans les réponses API

### Fonctionnalités

1. **Codes Promo** :
   - Créez des codes dans `admin/promos.php`
   - Les clients peuvent les utiliser dans le panier
   - Les codes peuvent être en montant fixe ou pourcentage
   - Support des dates d'expiration et nombre maximum d'utilisations

2. **Typographie** :
   - Configurez la police et les graisses dans `admin/typography.php`
   - Les paramètres sont sauvegardés dans Supabase

3. **Maintenance** :
   - Activez/désactivez le mode maintenance dans `admin/maintenance.php`
   - Ajoutez un message et une image personnalisés

## 🔍 Vérification

Après avoir exécuté le script SQL, testez :

1. **Boutique** : Les produits, catégories et farms doivent s'afficher
2. **Admin** : Vous devez pouvoir créer/modifier des produits
3. **Codes Promo** : Créez un code dans l'admin et testez-le dans le panier
4. **Avis** : Les avis approuvés doivent s'afficher sur les produits

## ⚠️ Problèmes courants

### "No data will be selectable via Supabase APIs"
- **Cause** : RLS activé mais pas de politiques
- **Solution** : Exécutez le script SQL `supabase_rls_policies_complete.sql`

### "Anyone with your project's anonymous key can read, modify, or delete your data"
- **Cause** : RLS désactivé
- **Solution** : Le script SQL activera RLS et créera les politiques appropriées

### Les données ne s'affichent pas dans la boutique
- **Cause** : Politiques RLS trop restrictives ou données non actives
- **Solution** : Vérifiez que les produits ont `active = true`, les catégories `enabled = true`, etc.

## 📞 Support

Si vous rencontrez des problèmes après avoir exécuté le script SQL, vérifiez :

1. Les logs Supabase dans **Logs > Postgres Logs**
2. Les erreurs PHP dans les logs de votre serveur
3. La console du navigateur pour les erreurs JavaScript

---

**Date de création** : $(date)
**Dernière mise à jour** : $(date)

