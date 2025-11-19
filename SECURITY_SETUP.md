# 🔐 Configuration de la Sécurité API

## Clé API Secrète Configurée

Votre clé API secrète a été configurée avec succès :
```
DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==
```

## 📋 Comment Utiliser la Clé API

### Pour les Requêtes Authentifiées

Pour protéger un endpoint et exiger l'authentification, ajoutez dans le fichier API :

```php
// Au début du fichier, après require security.php
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
    requireAuth(); // Nécessite la clé API
}
```

### Exemple d'Utilisation depuis JavaScript

```javascript
fetch('/api/orders.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw=='
    },
    body: JSON.stringify({
        // vos données
    })
})
```

### Exemple avec cURL

```bash
curl -X POST https://votre-domaine.com/api/orders.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==" \
  -d '{"items": [], "client": {}}'
```

## 🔒 Endpoints Actuellement Protégés

### Endpoints Publics (Pas d'authentification)
- `GET /api/products.php` - Liste des produits
- `GET /api/categories.php` - Liste des catégories
- `GET /api/farms.php` - Liste des farms
- `GET /api/reviews.php` - Avis approuvés
- `GET /api/settings.php` - Paramètres publics
- `GET /api/socials.php` - Liens sociaux

### Endpoints Semi-Protégés (Rate limiting + Validation)
- `GET /api/cart.php` - Panier utilisateur
- `POST /api/cart.php` - Ajouter au panier
- `POST /api/reviews.php` - Soumettre un avis

### Endpoints à Protéger avec Clé API (Recommandé)
Pour activer l'authentification sur ces endpoints, ajoutez `requireAuth()` :

- `POST /api/orders.php` - Créer une commande ⚠️ **RECOMMANDÉ**
- Tous les endpoints admin (déjà protégés par session PHP)

## 🛡️ Protection Actuelle

✅ **Rate Limiting** : 100 requêtes/minute par IP
✅ **CORS** : Domaines autorisés configurés
✅ **Validation** : Tous les inputs sont validés
✅ **Sanitization** : Protection XSS et injections
✅ **Headers de Sécurité** : XSS Protection, Content-Type, Frame Options
✅ **Logging** : Activités suspectes enregistrées dans `logs/security.log`

## ⚠️ Actions Recommandées

### 1. Protéger l'Endpoint Orders

Modifiez `api/orders.php` :

```php
require_once __DIR__ . '/security.php';

session_start();

// Protéger les commandes avec authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth(); // Ajoutez cette ligne
    // ... reste du code
}
```

### 2. Ne JAMAIS Exposer la Clé dans le Code Frontend

❌ **MAUVAIS** (ne faites jamais ça) :
```javascript
// Dans votre code JavaScript public
const API_KEY = 'DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==';
```

✅ **BON** : Utilisez la clé uniquement côté serveur ou via un proxy

### 3. Utiliser un Proxy pour les Requêtes Frontend

Créez un endpoint proxy dans votre application PHP qui utilise la clé :

```php
// proxy.php
<?php
require_once __DIR__ . '/api/security.php';

$apiKey = 'DQjVy7UkeA/RiQdBKYGobB1aDfEiPT/7vHHT63kuq0e9fGPmI1ThQaSzKwxt3kT8OggtNkN6eP2WPiGJVIUZXw==';

// Faire la requête avec la clé
$ch = curl_init('https://votre-domaine.com/api/orders.php');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $apiKey
]);
// ...
```

## 📝 Fichiers de Configuration

- **Clé API** : Définie dans `config.php` et peut être surchargée par `.env`
- **Origines autorisées** : Configurées dans `api/security.php` ligne 9-13
- **Rate limiting** : Configuré dans `api/security.php` ligne 7-8

## 🔄 Changer la Clé API

Si vous devez changer la clé :

1. Modifiez `config.php` ligne avec `API_SECRET_KEY`
2. Ou créez un fichier `.env` avec :
   ```
   API_SECRET_KEY=votre-nouvelle-cle
   ```
3. Mettez à jour tous les clients qui utilisent l'API

## 📊 Monitoring

Vérifiez les tentatives d'authentification échouées :

```bash
grep "Authentification requise" logs/security.log
```

## ✅ Checklist de Sécurité

- [x] Clé API secrète configurée
- [x] Rate limiting activé
- [x] CORS configuré
- [x] Validation des inputs
- [x] Sanitization activée
- [x] Headers de sécurité
- [x] Logging activé
- [ ] Endpoint orders protégé (à faire)
- [ ] HTTPS activé en production
- [ ] Clé API jamais exposée côté client

