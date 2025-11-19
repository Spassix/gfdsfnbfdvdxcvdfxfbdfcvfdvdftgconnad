/**
 * Script de chargement de la page
 * Charge la configuration depuis l'API et affiche l'écran de chargement
 */

(function() {
    'use strict';
    
    // Récupérer la configuration de la page de chargement
    async function loadLoadingConfig() {
        try {
            const protocol = window.location.protocol;
            const host = window.location.host;
            
            // Récupérer directement depuis loading_page
            const loadingUrl = protocol + '//' + host + '/api/loading_page.php';
            const loadingResponse = await fetch(loadingUrl);
            if (loadingResponse.ok) {
                const loadingData = await loadingResponse.json();
                if (loadingData && loadingData.enabled === true) {
                    return loadingData;
                }
            }
            
            return null;
        } catch (error) {
            console.error('Erreur chargement config:', error);
            return null;
        }
    }
    
    // Créer l'écran de chargement
    function createLoadingScreen(config) {
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'loading-screen';
        loadingDiv.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.5s ease-out;
        `;
        
        // Configuration du fond
        const bgType = config.background_type || config.backgroundType || 'color';
        const bgColor = config.background_color || config.backgroundColor || '#0d0f17';
        const bgImage = config.background_image_url || config.backgroundImage || '';
        const bgVideo = config.background_video_url || config.backgroundVideo || '';
        
        if (bgType === 'image' && bgImage) {
            loadingDiv.style.backgroundImage = `url(${bgImage})`;
            loadingDiv.style.backgroundSize = 'cover';
            loadingDiv.style.backgroundPosition = 'center';
        } else if (bgType === 'video' && bgVideo) {
            const video = document.createElement('video');
            video.src = bgVideo;
            video.autoplay = true;
            video.loop = true;
            video.muted = true;
            video.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1;';
            loadingDiv.appendChild(video);
            loadingDiv.style.backgroundColor = bgColor;
        } else {
            loadingDiv.style.backgroundColor = bgColor;
        }
        
        // Contenu
        const content = document.createElement('div');
        content.style.cssText = 'text-align: center; z-index: 1;';
        
        const text = config.text || 'Chargement...';
        const style = config.style || 'spinner';
        
        // Créer l'animation selon le style
        let animationHtml = '';
        switch(style) {
            case 'spinner':
                animationHtml = '<div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>';
                break;
            case 'dots':
                animationHtml = '<div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 20px;"><div style="width: 12px; height: 12px; background: #fff; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both;"></div><div style="width: 12px; height: 12px; background: #fff; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: 0.2s;"></div><div style="width: 12px; height: 12px; background: #fff; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: 0.4s;"></div></div>';
                break;
            case 'pulse':
                animationHtml = '<div style="width: 50px; height: 50px; background: #fff; border-radius: 50%; margin: 0 auto 20px; animation: pulse 1.5s ease-in-out infinite;"></div>';
                break;
            case 'wave':
                animationHtml = '<div style="display: flex; gap: 5px; justify-content: center; margin-bottom: 20px;"><div style="width: 4px; height: 40px; background: #fff; animation: wave 1.2s ease-in-out infinite;"></div><div style="width: 4px; height: 40px; background: #fff; animation: wave 1.2s ease-in-out infinite; animation-delay: 0.1s;"></div><div style="width: 4px; height: 40px; background: #fff; animation: wave 1.2s ease-in-out infinite; animation-delay: 0.2s;"></div><div style="width: 4px; height: 40px; background: #fff; animation: wave 1.2s ease-in-out infinite; animation-delay: 0.3s;"></div><div style="width: 4px; height: 40px; background: #fff; animation: wave 1.2s ease-in-out infinite; animation-delay: 0.4s;"></div></div>';
                break;
            default:
                animationHtml = '<div style="width: 50px; height: 50px; border: 4px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>';
        }
        
        content.innerHTML = animationHtml + '<p style="color: #fff; font-size: 1.25rem; font-weight: 600; margin: 0;">' + text + '</p>';
        
        loadingDiv.appendChild(content);
        document.body.appendChild(loadingDiv);
        
        // Ajouter les animations CSS
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            @keyframes bounce {
                0%, 80%, 100% { transform: scale(0); }
                40% { transform: scale(1); }
            }
            @keyframes pulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(0.9); }
            }
            @keyframes wave {
                0%, 40%, 100% { transform: scaleY(0.4); }
                20% { transform: scaleY(1); }
            }
        `;
        document.head.appendChild(styleSheet);
        
        // Durée de l'écran de chargement
        const duration = config.duration_ms || config.duration || 2000;
        
        // Masquer l'écran de chargement après la durée ou quand la page est chargée
        const hideLoading = () => {
            loadingDiv.style.opacity = '0';
            setTimeout(() => {
                if (loadingDiv.parentNode) {
                    loadingDiv.parentNode.removeChild(loadingDiv);
                }
            }, 500);
        };
        
        // Masquer après la durée configurée
        setTimeout(hideLoading, duration);
        
        // Masquer aussi quand la page est complètement chargée
        if (document.readyState === 'complete') {
            setTimeout(hideLoading, Math.min(duration, 1000));
        } else {
            window.addEventListener('load', () => {
                setTimeout(hideLoading, Math.min(duration, 500));
            });
        }
    }
    
    // Initialiser
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', async () => {
            const config = await loadLoadingConfig();
            if (config) {
                createLoadingScreen(config);
            }
        });
    } else {
        (async () => {
            const config = await loadLoadingConfig();
            if (config) {
                createLoadingScreen(config);
            }
        })();
    }
})();

