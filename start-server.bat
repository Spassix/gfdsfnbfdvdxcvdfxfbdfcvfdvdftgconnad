@echo off
echo ========================================
echo   Serveur PHP Local - Checkout System
echo ========================================
echo.

REM Changer vers le repertoire du fichier batch
cd /d "%~dp0"

REM Afficher le repertoire actuel pour verification
echo Repertoire actuel: %CD%
echo.

REM Verifier si PHP est disponible
php -v >nul 2>&1
if errorlevel 1 (
    echo ERREUR: PHP n'est pas installe ou n'est pas dans le PATH
    echo.
    pause
    exit /b 1
)

echo Demarrage du serveur sur http://localhost:8000
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.
echo ========================================
echo.

REM Demarrer le serveur avec le router
php -S localhost:8000 -t . router.php

pause

