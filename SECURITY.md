# 🔒 Guide de Sécurité

Ce document décrit les mesures de sécurité implémentées dans l'application.

## 🔐 Authentification Admin

### Hashage des Mots de Passe

- **Algorithme** : bcrypt avec cost factor 12
- **Fonction PHP** : `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`
- **Vérification** : `password_verify($password, $hash)`
- **Stockage** : Les mots de passe sont stockés dans la colonne `password_hash` de la table `admins`

### Protection contre les Attaques

1. **Brute Force** : Limitation à 5 tentatives de connexion par 15 minutes
2. **Timing Attacks** : Utilisation de `password_verify()` qui est constant-time
3. **Session Fixation** : Régénération de l'ID de session à chaque connexion
4. **Session Hijacking** : Cookies HTTPOnly et Secure (en HTTPS)

### Création d'un Administrateur

Utilisez le script `admin/create_admin.php` pour créer un administrateur :

```bash
# Accédez à : http://localhost/admin/create_admin.php
# OU avec un secret : http://localhost/admin/create_admin.php?secret=VOTRE_SECRET
```

⚠️ **IMPORTANT** : Supprimez ce fichier après utilisation en production !

## 🛡️ Row Level Security (RLS) dans Supabase

### Tables Protégées

- **`admins`** : Accès uniquement via service role key (RLS bloque tout accès public)
- **`orders`** : Accès uniquement via service role key
- **`products`** : Lecture publique (actifs uniquement), écriture admin uniquement
- **`categories`** : Lecture publique (activées uniquement), écriture admin uniquement
- **`farms`** : Lecture publique (activées uniquement), écriture admin uniquement
- **`reviews`** : Lecture publique (approuvés uniquement), création publique, modification admin uniquement
- **`coupons`** : Lecture publique (actifs uniquement), écriture admin uniquement
- **`settings`** : Lecture publique, écriture admin uniquement
- **`socials`** : Lecture publique (activés uniquement), écriture admin uniquement

### Application des Politiques

Exécutez le script `supabase_security_policies.sql` dans le SQL Editor de Supabase pour activer toutes les politiques RLS.

## 🔑 Clés API

### Service Role Key

- **Usage** : Opérations admin (CRUD sur toutes les tables)
- **Stockage** : Dans `config.php` (ne JAMAIS commiter dans Git)
- **Accès** : Uniquement dans le code PHP côté serveur

### Anonymous Key

- **Usage** : Requêtes publiques (lecture uniquement)
- **Stockage** : Dans `config.php`
- **Accès** : Peut être exposé côté client (lecture seule)

## 📁 Storage Supabase

### Buckets

- **`photos`** : Images des produits
- **`videos`** : Vidéos des produits

### Politiques Storage

- **SELECT (lecture)** : Public (tout le monde peut lire)
- **INSERT (upload)** : Service role uniquement (via code PHP)
- **UPDATE/DELETE** : Service role uniquement

Configurez ces politiques dans Supabase Dashboard > Storage > Policies.

## 🚨 Bonnes Pratiques

### En Production

1. ✅ Supprimez `admin/create_admin.php` après création des admins
2. ✅ Activez HTTPS (obligatoire pour les cookies Secure)
3. ✅ Configurez les variables d'environnement dans `.env` (ne pas commiter)
4. ✅ Activez toutes les politiques RLS dans Supabase
5. ✅ Limitez les tentatives de connexion (déjà implémenté)
6. ✅ Utilisez des mots de passe forts (min. 12 caractères recommandé)
7. ✅ Régénérez les clés API régulièrement
8. ✅ Activez les logs de sécurité et surveillez-les

### Variables d'Environnement

Créez un fichier `.env` à la racine (non commité) :

```env
# Supabase
SUPABASE_URL=https://votre-projet.supabase.co
SUPABASE_KEY=votre_anon_key
SUPABASE_SERVICE_KEY=votre_service_key

# API
API_SECRET_KEY=votre_secret_key_aleatoire

# Session
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

### Mots de Passe

- **Minimum** : 8 caractères (recommandé : 12+)
- **Recommandé** : Utilisez un gestionnaire de mots de passe
- **Complexité** : Majuscules, minuscules, chiffres, symboles
- **Rotation** : Changez régulièrement (tous les 90 jours)

## 🔍 Monitoring

### Logs de Sécurité

Les activités suspectes sont loggées dans `logs/security.log` :

- Tentatives de connexion échouées
- Rate limiting dépassé
- Erreurs d'authentification

### Vérifications Régulières

1. Vérifiez les logs de sécurité hebdomadairement
2. Surveillez les tentatives de connexion suspectes
3. Vérifiez les accès aux tables sensibles dans Supabase
4. Testez les politiques RLS régulièrement

## 📞 Support

En cas de problème de sécurité, contactez immédiatement l'administrateur système.
