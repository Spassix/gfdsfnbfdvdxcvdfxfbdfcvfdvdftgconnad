/**
 * Bloque l'accès aux DevTools (F12, clic droit, etc.)
 * Cache les fichiers dans les DevTools
 */

(function() {
    'use strict';
    
    // Empêcher l'affichage des fichiers dans les DevTools
    // Désactiver console.log pour cacher les logs
    const noop = function() {};
    const methods = ['log', 'debug', 'info', 'warn', 'error', 'assert', 'dir', 'dirxml', 'group', 'groupEnd', 'time', 'timeEnd', 'count', 'trace', 'profile', 'profileEnd'];
    methods.forEach(function(method) {
        if (window.console && window.console[method]) {
            window.console[method] = noop;
        }
    });
    
    // Empêcher l'inspection des éléments
    Object.defineProperty(window, 'devtools', {
        get: function() {
            return {};
        },
        set: function() {}
    });
    
    // Bloquer F12
    document.addEventListener('keydown', function(e) {
        // F12
        if (e.keyCode === 123) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+Shift+I (DevTools)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+Shift+J (Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        // Ctrl+Shift+C (Inspect Element)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });
    
    // Bloquer le clic droit
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Détecter l'ouverture des DevTools
    let devtools = {
        open: false,
        orientation: null
    };
    
    const threshold = 160;
    
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                // Rediriger vers la page de blocage
                window.location.href = 'maintenance.php?blocked=devtools';
            }
        } else {
            devtools.open = false;
        }
    }, 500);
    
    // Détecter l'ouverture des DevTools via la taille de la fenêtre
    let devtoolsDetected = false;
    const detectDevTools = function() {
        const threshold = 160;
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtoolsDetected) {
                devtoolsDetected = true;
                window.location.href = 'maintenance.php?blocked=devtools';
            }
        } else {
            devtoolsDetected = false;
        }
    };
    
    // Vérifier périodiquement (déjà fait plus haut, mais on garde cette méthode aussi)
    // La détection principale est déjà dans le setInterval plus haut
    
    // Empêcher la sélection de texte
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Empêcher le drag & drop
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Empêcher le copier/coller
    document.addEventListener('copy', function(e) {
        e.preventDefault();
        return false;
    });
    
    document.addEventListener('cut', function(e) {
        e.preventDefault();
        return false;
    });
    
    document.addEventListener('paste', function(e) {
        e.preventDefault();
        return false;
    });
})();

