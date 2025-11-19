# Guide de Déploiement sur VPS

## 📋 Fichiers modifiés à transférer

Les fichiers suivants ont été modifiés et doivent être transférés sur le VPS :

### Fichiers principaux
- `telegram_guard.php` - Vérification Telegram WebApp
- `config.php` - Configuration Supabase
- `shop/config.php` - Ajout fonction checkMaintenance()
- `shop/maintenance.php` - Nouvelle page de maintenance
- `shop/devtools_blocker.js` - Nouveau script de blocage DevTools

### Pages shop modifiées
- `shop/index.php` - Vérification maintenance + DevTools blocker
- `shop/products.php` - Vérification maintenance + DevTools blocker
- `shop/cart.php` - Vérification maintenance + DevTools blocker + styles améliorés
- `shop/reviews.php` - Vérification maintenance + DevTools blocker + messages d'erreur améliorés
- `shop/product.php` - Vérification maintenance + DevTools blocker
- `shop/contact.php` - Vérification maintenance + DevTools blocker
- `shop/categories.php` - Vérification maintenance + DevTools blocker

### Admin modifié
- `admin/cart_settings.php` - Correction sauvegarde services

---

## 🚀 Méthode 1 : Transfert via SCP (recommandé)

### Depuis votre PC Windows (PowerShell ou CMD)

```powershell
# Se connecter au VPS et transférer tous les fichiers
scp -r C:\Users\fxxre\Desktop\gay\* root@65.21.177.151:/var/www/html/votre-site/

# Ou transférer uniquement les fichiers modifiés
scp C:\Users\fxxre\Desktop\gay\telegram_guard.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\config.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\config.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\maintenance.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\devtools_blocker.js root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\index.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\products.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\cart.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\reviews.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\product.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\contact.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\shop\categories.php root@65.21.177.151:/var/www/html/votre-site/
scp C:\Users\fxxre\Desktop\gay\admin\cart_settings.php root@65.21.177.151:/var/www/html/votre-site/admin/
```

---

## 🚀 Méthode 2 : Transfert via SFTP (FileZilla, WinSCP, etc.)

1. **Ouvrir votre client SFTP** (FileZilla, WinSCP, etc.)
2. **Se connecter au VPS** :
   - Host: `65.21.177.151`
   - Username: `root`
   - Password: (votre mot de passe)
   - Port: `22`

3. **Transférer les fichiers** vers `/var/www/html/votre-site/`

---

## 🚀 Méthode 3 : Via SSH (copier-coller)

### Se connecter au VPS
```bash
ssh root@65.21.177.151
```

### Sur le VPS, créer les fichiers modifiés

Vous pouvez copier-coller le contenu des fichiers modifiés directement via `nano` ou `vi`.

---

## ✅ Après le transfert

### 1. Vérifier les permissions
```bash
cd /var/www/html/votre-site
chown -R www-data:www-data shop/
chown -R www-data:www-data admin/
chmod -R 755 shop/
chmod -R 755 admin/
```

### 2. Vérifier que Nginx fonctionne
```bash
systemctl status nginx
```

### 3. Redémarrer Nginx si nécessaire
```bash
systemctl restart nginx
```

### 4. Vérifier PHP-FPM
```bash
systemctl status php8.3-fpm
systemctl restart php8.3-fpm
```

### 5. Vérifier les logs en cas d'erreur
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.3-fpm.log
```

---

## 🔍 Vérification finale

1. **Tester l'accès au site** : `http://lamainverte.xyz` ou `http://65.21.177.151`
2. **Vérifier Telegram Guard** : Tester depuis Telegram Mini App
3. **Vérifier DevTools Blocker** : Essayer F12 (devrait bloquer)
4. **Tester la maintenance** : Activer dans l'admin et vérifier l'affichage
5. **Vérifier les services** : Ajouter un service dans l'admin et vérifier qu'il ne disparaît pas

---

## 📝 Notes importantes

- **Backup avant déploiement** : Faire une sauvegarde des fichiers existants sur le VPS
- **Vérifier les chemins** : S'assurer que les chemins dans les fichiers correspondent à la structure du VPS
- **Variables d'environnement** : Vérifier que `config.php` contient les bonnes clés Supabase
- **Permissions** : S'assurer que PHP peut écrire dans les dossiers nécessaires

---

## 🆘 En cas de problème

1. Vérifier les logs Nginx : `/var/log/nginx/error.log`
2. Vérifier les logs PHP : `/var/log/php8.3-fpm.log`
3. Vérifier les permissions : `ls -la /var/www/html/votre-site/`
4. Tester PHP : `php -v` et `php -m`
5. Vérifier la configuration Nginx : `nginx -t`

