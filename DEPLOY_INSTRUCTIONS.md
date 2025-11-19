# Instructions de Déploiement VPS

## ✅ Déploiement terminé !

Tous les fichiers ont été transférés avec succès sur le VPS.

## 📋 Prochaines étapes

### 1. Se connecter au VPS
```bash
ssh root@65.21.177.151
```

### 2. Aller dans le répertoire du site
```bash
cd /var/www/html/votre-site
```

### 3. Configurer les permissions
```bash
chown -R www-data:www-data shop/ admin/
chmod -R 755 shop/ admin/
```

### 4. Redémarrer les services
```bash
systemctl restart nginx
systemctl restart php8.3-fpm
```

### 5. Vérifier que tout fonctionne
```bash
systemctl status nginx
systemctl status php8.3-fpm
```

## 🌐 Accès au site

- **URL principale**: http://lamainverte.xyz
- **Admin panel**: http://lamainverte.xyz/admin/

## 📝 Fichiers déployés

Les fichiers suivants ont été transférés :
- ✅ `telegram_guard.php` - Protection Telegram WebApp
- ✅ `config.php` - Configuration Supabase
- ✅ `shop/config.php` - Configuration shop + fonction checkMaintenance()
- ✅ `shop/maintenance.php` - Page de maintenance
- ✅ `shop/devtools_blocker.js` - Blocage DevTools
- ✅ `shop/index.php` - Page d'accueil
- ✅ `shop/products.php` - Page produits
- ✅ `shop/cart.php` - Page panier (lisibilité améliorée)
- ✅ `shop/reviews.php` - Page avis (lisibilité améliorée)
- ✅ `shop/product.php` - Page détail produit
- ✅ `shop/contact.php` - Page contact
- ✅ `shop/categories.php` - Page catégories
- ✅ `admin/cart_settings.php` - Paramètres panier (correction services)

## 🔍 Vérifications

1. **Tester l'accès au site**: http://lamainverte.xyz
2. **Tester Telegram Guard**: Depuis Telegram Mini App
3. **Tester DevTools Blocker**: Essayer F12 (devrait bloquer)
4. **Tester la maintenance**: Activer dans l'admin et vérifier
5. **Vérifier la lisibilité**: Pages avis et panier doivent être lisibles

## 🆘 En cas de problème

### Vérifier les logs
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.3-fpm.log
```

### Vérifier les permissions
```bash
ls -la /var/www/html/votre-site/shop/
ls -la /var/www/html/votre-site/admin/
```

### Tester PHP
```bash
php -v
php -m | grep -i curl
```

### Vérifier Nginx
```bash
nginx -t
systemctl status nginx
```

