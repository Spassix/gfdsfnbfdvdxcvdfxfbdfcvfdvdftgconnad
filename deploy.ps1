# Script PowerShell pour deployer sur le VPS
# Usage: .\deploy.ps1

$VPS_IP = "65.21.177.151"
$VPS_USER = "root"
$VPS_PATH = "/var/www/html/votre-site"
$LOCAL_PATH = "C:\Users\fxxre\Desktop\gay"

Write-Host "`nDeploiement sur le VPS..." -ForegroundColor Green

# Liste des fichiers a transferer (tous les fichiers modifies recemment)
$files = @(
    "telegram_guard.php",
    "config.php",
    "shop\config.php",
    "shop\maintenance.php",
    "shop\devtools_blocker.js",
    "shop\index.php",
    "shop\products.php",
    "shop\cart.php",
    "shop\reviews.php",
    "shop\product.php",
    "shop\contact.php",
    "shop\categories.php",
    "shop\assets\css\style.css",
    "shop\components\header.php",
    "api\promos.php",
    "admin\cart_settings.php",
    "fix_reviews_rls_complete.sql"
)

Write-Host "`nFichiers a transferer:" -ForegroundColor Yellow
foreach ($file in $files) {
    Write-Host "  - $file" -ForegroundColor Gray
}

$confirm = Read-Host "`nContinuer le deploiement? (O/N)"
if ($confirm -ne "O" -and $confirm -ne "o") {
    Write-Host "Deploiement annule" -ForegroundColor Red
    exit
}

Write-Host "`nTransfert des fichiers..." -ForegroundColor Cyan

foreach ($file in $files) {
    $localFile = Join-Path $LOCAL_PATH $file
    $remoteFile = $file -replace "\\", "/"
    
    if (Test-Path $localFile) {
        Write-Host "  -> $file" -ForegroundColor Gray
        
        # Determiner le chemin distant
        if ($file -like "shop\*") {
            $remotePath = "$VPS_PATH/shop/$($remoteFile -replace 'shop/', '')"
        } elseif ($file -like "admin\*") {
            $remotePath = "$VPS_PATH/admin/$($remoteFile -replace 'admin/', '')"
        } elseif ($file -like "api\*") {
            $remotePath = "$VPS_PATH/api/$($remoteFile -replace 'api/', '')"
        } else {
            $remotePath = "$VPS_PATH/$remoteFile"
        }
        
        # Commande SCP
        $scpCommand = "scp `"$localFile`" ${VPS_USER}@${VPS_IP}:`"$remotePath`""
        
        try {
            Invoke-Expression $scpCommand
            Write-Host "    OK - Transfere" -ForegroundColor Green
        } catch {
            Write-Host "    ERREUR: $_" -ForegroundColor Red
        }
    } else {
        Write-Host "  ATTENTION - Fichier non trouve: $file" -ForegroundColor Yellow
    }
}

Write-Host "`nDeploiement termine!" -ForegroundColor Green
Write-Host "`nProchaines etapes sur le VPS:" -ForegroundColor Yellow
Write-Host "  1. ssh root@$VPS_IP" -ForegroundColor Gray
Write-Host "  2. cd $VPS_PATH" -ForegroundColor Gray
Write-Host "  3. chown -R www-data:www-data shop/ admin/ api/" -ForegroundColor Gray
Write-Host "  4. chmod -R 755 shop/ admin/ api/" -ForegroundColor Gray
Write-Host "  5. systemctl restart nginx" -ForegroundColor Gray
Write-Host "  6. systemctl restart php8.3-fpm" -ForegroundColor Gray
Write-Host "`nIMPORTANT: Executer fix_reviews_rls_complete.sql dans Supabase!" -ForegroundColor Red
