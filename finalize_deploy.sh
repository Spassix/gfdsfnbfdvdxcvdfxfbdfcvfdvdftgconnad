#!/bin/bash
# Script à exécuter sur le VPS pour finaliser le déploiement
# Usage: ssh root@65.21.177.151 'bash -s' < finalize_deploy.sh

cd /var/www/html/votre-site

echo "🔧 Configuration des permissions..."
chown -R www-data:www-data shop/ admin/
chmod -R 755 shop/ admin/

echo "🔄 Redémarrage des services..."
systemctl restart nginx
systemctl restart php8.3-fpm

echo "✅ Vérification des services..."
systemctl status nginx --no-pager -l
systemctl status php8.3-fpm --no-pager -l

echo "✅ Déploiement finalisé !"
echo "🌐 Votre site est accessible sur: http://lamainverte.xyz"

