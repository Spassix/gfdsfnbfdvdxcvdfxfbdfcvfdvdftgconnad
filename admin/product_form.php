<?php
require_once __DIR__ . '/config.php';
checkAuth();

require_once __DIR__ . '/../supabase_client.php';
require_once __DIR__ . '/helpers.php';

$product = null;
$isEdit = false;
$error = null;
$success = null;

// Récupérer le produit si on est en mode édition
if (isset($_GET['id'])) {
    $isEdit = true;
    try {
        $productRaw = $supabase->getProduct($_GET['id']);
        $product = normalizeProduct($productRaw);
        if (!$product) {
            header('Location: products.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer les catégories et farms
$categories = [];
$farms = [];
try {
    $categories = $supabase->getCategories();
    $farms = $supabase->getFarms();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $farm = $_POST['farm'] ?? '';
    $photo = $_POST['photo'] ?? '';
    $video = $_POST['video'] ?? '';
    $medias = [];
    if (!empty($_POST['medias'])) {
        $mediasArray = array_map('trim', explode(',', $_POST['medias']));
        $medias = array_filter($mediasArray, function($url) {
            return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
        });
    }
    $variants = [];
    
    // Récupérer les variantes
    if (isset($_POST['variant_name']) && isset($_POST['variant_price'])) {
        $names = $_POST['variant_name'];
        $prices = $_POST['variant_price'];
        for ($i = 0; $i < count($names); $i++) {
            if (!empty($names[$i]) && !empty($prices[$i])) {
                $variants[] = [
                    'name' => $names[$i],
                    'price' => $prices[$i]
                ];
            }
        }
    }
    
    if (empty($variants)) {
        $error = 'Veuillez ajouter au moins une variante valide';
    } else {
        try {
            $productData = [
                'name' => $name,
                'description' => $description,
                'category_id' => $category ?: null,
                'farm_id' => $farm ?: null,
                'photo_url' => $photo ?: null,
                'video_url' => $video ?: null,
                'medias' => json_encode($medias), // Stocker les médias dans un JSONB
                'variants' => $variants,
                'active' => true,
                'created_at' => $isEdit ? ($product['created_at'] ?? date('Y-m-d\TH:i:s.u\Z')) : date('Y-m-d\TH:i:s.u\Z'),
                'updated_at' => date('Y-m-d\TH:i:s.u\Z')
            ];
            
            if ($isEdit) {
                $productData['id'] = $product['id'];
                $supabase->updateProduct($product['id'], $productData);
                $success = 'Produit modifié avec succès !';
            } else {
                $supabase->createProduct($productData);
                $success = 'Produit créé avec succès !';
            }
            
            // Rediriger après 1 seconde
            header('Refresh: 1; url=products.php');
        } catch (Exception $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

$pageTitle = ($isEdit ? 'Modifier' : 'Ajouter') . ' un produit - Panel Admin';
?>
<?php include __DIR__ . '/components/layout.php'; ?>
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">
                <?php echo $isEdit ? 'Modifier le produit' : 'Ajouter un produit'; ?>
            </h1>
            <a href="products.php" style="color: #8b5cf6; text-decoration: none;">← Retour à la liste</a>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(127, 29, 29, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <p style="color: rgba(248, 113, 113, 1);">Erreur: <?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background: rgba(20, 83, 45, 0.2); border: 1px solid rgba(34, 197, 94, 0.5); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <p style="color: rgba(74, 222, 128, 1);"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" style="background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 1rem; padding: 2rem; backdrop-filter: blur(4px);">
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Nom -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Nom du produit *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required
                           style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                </div>

                <!-- Description -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Description *</label>
                    <textarea name="description" rows="3" required
                              style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <!-- Catégorie -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Catégorie *</label>
                    <select name="category" required
                            style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (($product['category'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Farm -->
                <div>
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">Farm</label>
                    <select name="farm"
                            style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        <option value="">Aucune farm</option>
                        <?php foreach ($farms as $farm): ?>
                            <option value="<?php echo $farm['id']; ?>" <?php echo (($product['farm'] ?? '') == $farm['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($farm['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Photo -->
                <div style="border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; padding: 1rem;">
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">📸 Photo du produit</label>
                    <?php if (!empty($product['photo'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <img src="<?php echo htmlspecialchars($product['photo']); ?>" alt="Aperçu" id="photo-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 1rem; display: none;" id="photo-preview-container">
                            <img src="" alt="Aperçu" id="photo-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;">
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">📤 Upload depuis votre appareil</label>
                            <input type="file" id="photo-upload" accept="image/*" 
                                   onchange="uploadPhoto(this)"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 0.875rem; cursor: pointer;">
                            <div id="photo-upload-status" style="margin-top: 0.5rem; font-size: 0.75rem; color: #fff;"></div>
                        </div>
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">🔗 Ou entrez une URL</label>
                            <input type="url" name="photo" id="photo-url" value="<?php echo htmlspecialchars($product['photo'] ?? ''); ?>"
                                   placeholder="https://example.com/image.jpg"
                                   onchange="updatePhotoPreview(this.value)"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        </div>
                    </div>
                    <p style="color: #fff; opacity: 0.7; font-size: 0.875rem; margin-top: 0.5rem;">Téléchargez une image depuis votre appareil ou entrez une URL</p>
                </div>

                <!-- Vidéo -->
                <div style="border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; padding: 1rem;">
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">🎥 Vidéo du produit</label>
                    <?php if (!empty($product['video'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <video src="<?php echo htmlspecialchars($product['video']); ?>" controls id="video-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;"></video>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 1rem; display: none;" id="video-preview-container">
                            <video src="" controls id="video-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;"></video>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">📤 Upload depuis votre appareil</label>
                            <input type="file" id="video-upload" accept="video/*" 
                                   onchange="uploadVideo(this)"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 0.875rem; cursor: pointer;">
                            <div id="video-upload-status" style="margin-top: 0.5rem; font-size: 0.75rem; color: #fff;"></div>
                        </div>
                        <div>
                            <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">🔗 Ou entrez une URL</label>
                            <input type="url" name="video" id="video-url" value="<?php echo htmlspecialchars($product['video'] ?? ''); ?>"
                                   placeholder="https://example.com/video.mp4"
                                   onchange="updateVideoPreview(this.value)"
                                   style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                        </div>
                    </div>
                    <p style="color: #fff; opacity: 0.7; font-size: 0.875rem; margin-top: 0.5rem;">Téléchargez une vidéo depuis votre appareil ou entrez une URL</p>
                </div>

                <!-- Galerie (médias multiples) -->
                <div style="border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; padding: 1rem;">
                    <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-weight: 500;">🖼️ Galerie</label>
                    <?php 
                    $medias = [];
                    if (!empty($product['medias']) && is_array($product['medias'])) {
                        $medias = $product['medias'];
                    } elseif (!empty($product['medias']) && is_string($product['medias'])) {
                        $medias = json_decode($product['medias'], true) ?: [];
                    }
                    $mediasString = implode(', ', array_filter($medias));
                    ?>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">📤 Upload plusieurs fichiers (images ou vidéos)</label>
                        <input type="file" id="medias-upload" accept="image/*,video/*" multiple
                               onchange="uploadMedias(this)"
                               style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 0.875rem; cursor: pointer;">
                        <div id="medias-upload-status" style="margin-top: 0.5rem; font-size: 0.75rem; color: #fff;"></div>
                    </div>
                    
                    <div>
                        <label style="display: block; color: #fff; margin-bottom: 0.5rem; font-size: 0.875rem;">🔗 Ou entrez des URLs séparées par des virgules</label>
                        <input type="text" name="medias" id="medias-url" value="<?php echo htmlspecialchars($mediasString); ?>"
                               placeholder="https://example.com/image1.jpg, https://example.com/image2.jpg, https://example.com/video.mp4"
                               style="width: 100%; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                    </div>
                    <p style="color: #fff; opacity: 0.7; font-size: 0.875rem; margin-top: 0.5rem;">Téléchargez plusieurs fichiers ou entrez des URLs séparées par des virgules</p>
                </div>

                <!-- Variantes -->
                <div style="border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <label style="display: block; color: #fff; font-weight: 500;">💰 Variantes (Quantité + Prix) *</label>
                        <button type="button" onclick="addVariant()" style="padding: 0.5rem 1rem; background: rgba(55, 65, 81, 1); color: #fff; border: none; border-radius: 0.5rem; cursor: pointer;">+ Ajouter</button>
                    </div>
                    <div id="variants-container" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php 
                        $existingVariants = $product['variants'] ?? [];
                        if (empty($existingVariants) && !empty($product['price'])) {
                            $existingVariants = [['name' => 'Standard', 'price' => $product['price']]];
                        }
                        if (empty($existingVariants)) {
                            $existingVariants = [['name' => '', 'price' => '']];
                        }
                        foreach ($existingVariants as $index => $variant): 
                        ?>
                            <div class="variant-row" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="text" name="variant_name[]" value="<?php echo htmlspecialchars($variant['name'] ?? ''); ?>" placeholder="5g" required
                                       style="flex: 1; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                                <input type="text" name="variant_price[]" value="<?php echo htmlspecialchars($variant['price'] ?? ''); ?>" placeholder="20€" required
                                       style="flex: 1; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
                                <button type="button" onclick="removeVariant(this)" style="padding: 0.75rem; background: rgba(127, 29, 29, 0.3); color: #fff; border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.5rem; cursor: pointer;">🗑️</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Boutons -->
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem 1.5rem; background: linear-gradient(to right, #9333ea, #ec4899); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; font-size: 1rem;">
                        Enregistrer
                    </button>
                    <a href="products.php" style="flex: 1; padding: 0.75rem 1.5rem; background: rgba(55, 65, 81, 1); color: #fff; border: none; border-radius: 0.5rem; font-weight: 600; text-align: center; text-decoration: none; display: block; font-size: 1rem;">
                        Annuler
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
    // Fonction pour uploader une photo
    async function uploadPhoto(input) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        const statusDiv = document.getElementById('photo-upload-status');
        statusDiv.textContent = '⏳ Upload en cours...';
        statusDiv.style.color = '#fff';
        
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'photo');
            
            const response = await fetch('upload.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success && result.url) {
                // Mettre à jour le champ URL
                document.getElementById('photo-url').value = result.url;
                
                // Afficher l'aperçu
                const preview = document.getElementById('photo-preview');
                const container = document.getElementById('photo-preview-container');
                if (preview) {
                    preview.src = result.url;
                    if (container) container.style.display = 'block';
                }
                
                statusDiv.textContent = '✅ Upload réussi !';
                statusDiv.style.color = '#4ade80';
            } else {
                throw new Error(result.error || 'Erreur lors de l\'upload');
            }
        } catch (error) {
            statusDiv.textContent = '❌ Erreur: ' + error.message;
            statusDiv.style.color = '#f87171';
        }
    }
    
    // Fonction pour uploader une vidéo
    async function uploadVideo(input) {
        if (!input.files || !input.files[0]) return;
        
        const file = input.files[0];
        const statusDiv = document.getElementById('video-upload-status');
        statusDiv.textContent = '⏳ Upload en cours...';
        statusDiv.style.color = '#fff';
        
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'video');
            
            const response = await fetch('upload.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success && result.url) {
                // Mettre à jour le champ URL
                document.getElementById('video-url').value = result.url;
                
                // Afficher l'aperçu
                const preview = document.getElementById('video-preview');
                const container = document.getElementById('video-preview-container');
                if (preview) {
                    preview.src = result.url;
                    if (container) container.style.display = 'block';
                }
                
                statusDiv.textContent = '✅ Upload réussi !';
                statusDiv.style.color = '#4ade80';
            } else {
                throw new Error(result.error || 'Erreur lors de l\'upload');
            }
        } catch (error) {
            statusDiv.textContent = '❌ Erreur: ' + error.message;
            statusDiv.style.color = '#f87171';
        }
    }
    
    // Fonction pour uploader plusieurs médias
    async function uploadMedias(input) {
        if (!input.files || input.files.length === 0) return;
        
        const statusDiv = document.getElementById('medias-upload-status');
        const mediasUrlInput = document.getElementById('medias-url');
        const files = Array.from(input.files);
        const uploadedUrls = [];
        
        statusDiv.textContent = `⏳ Upload de ${files.length} fichier(s)...`;
        statusDiv.style.color = '#fff';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            statusDiv.textContent = `⏳ Upload ${i + 1}/${files.length}...`;
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('type', file.type.startsWith('video/') ? 'video' : 'photo');
                
                const response = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.url) {
                    uploadedUrls.push(result.url);
                } else {
                    console.error('Erreur upload fichier:', file.name, result.error);
                }
            } catch (error) {
                console.error('Erreur upload fichier:', file.name, error);
            }
        }
        
        if (uploadedUrls.length > 0) {
            // Ajouter les nouvelles URLs aux URLs existantes
            const existingUrls = mediasUrlInput.value ? mediasUrlInput.value.split(',').map(u => u.trim()).filter(u => u) : [];
            const allUrls = [...existingUrls, ...uploadedUrls];
            mediasUrlInput.value = allUrls.join(', ');
            
            statusDiv.textContent = `✅ ${uploadedUrls.length} fichier(s) uploadé(s) avec succès !`;
            statusDiv.style.color = '#4ade80';
        } else {
            statusDiv.textContent = '❌ Aucun fichier n\'a pu être uploadé';
            statusDiv.style.color = '#f87171';
        }
    }
    
    function addVariant() {
        const container = document.getElementById('variants-container');
        const newRow = document.createElement('div');
        newRow.className = 'variant-row';
        newRow.style.cssText = 'display: flex; gap: 0.5rem; align-items: center;';
        newRow.innerHTML = `
            <input type="text" name="variant_name[]" placeholder="5g" required
                   style="flex: 1; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
            <input type="text" name="variant_price[]" placeholder="20€" required
                   style="flex: 1; padding: 0.75rem; background: rgba(30, 41, 59, 1); border: 1px solid rgba(75, 85, 99, 0.3); border-radius: 0.5rem; color: #fff; font-size: 1rem;">
            <button type="button" onclick="removeVariant(this)" style="padding: 0.75rem; background: rgba(127, 29, 29, 0.3); color: #fff; border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 0.5rem; cursor: pointer;">🗑️</button>
        `;
        container.appendChild(newRow);
    }

    function removeVariant(button) {
        const container = document.getElementById('variants-container');
        if (container.children.length > 1) {
            button.parentElement.remove();
        } else {
            alert('Vous devez avoir au moins une variante');
        }
    }

    function updatePhotoPreview(url) {
        if (url) {
            const preview = document.getElementById('photo-preview');
            const container = document.getElementById('photo-preview-container');
            if (preview) {
                preview.src = url;
                if (container) {
                    container.style.display = 'block';
                } else {
                    // Si le conteneur n'existe pas, créer l'aperçu dans le parent
                    const parent = document.getElementById('photo-url').parentElement.parentElement;
                    const existingPreview = parent.querySelector('#photo-preview-container');
                    if (!existingPreview) {
                        const newContainer = document.createElement('div');
                        newContainer.id = 'photo-preview-container';
                        newContainer.style.cssText = 'margin-bottom: 1rem;';
                        newContainer.innerHTML = `<img src="${url}" alt="Aperçu" id="photo-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;">`;
                        parent.insertBefore(newContainer, parent.firstChild);
                    }
                }
            }
        }
    }

    function updateVideoPreview(url) {
        if (url) {
            const preview = document.getElementById('video-preview');
            const container = document.getElementById('video-preview-container');
            if (preview) {
                preview.src = url;
                if (container) {
                    container.style.display = 'block';
                } else {
                    // Si le conteneur n'existe pas, créer l'aperçu dans le parent
                    const parent = document.getElementById('video-url').parentElement.parentElement;
                    const existingPreview = parent.querySelector('#video-preview-container');
                    if (!existingPreview) {
                        const newContainer = document.createElement('div');
                        newContainer.id = 'video-preview-container';
                        newContainer.style.cssText = 'margin-bottom: 1rem;';
                        newContainer.innerHTML = `<video src="${url}" controls id="video-preview" style="max-width: 200px; max-height: 200px; border-radius: 0.5rem; display: block;"></video>`;
                        parent.insertBefore(newContainer, parent.firstChild);
                    }
                }
            }
        }
    }
    </script>
<?php include __DIR__ . '/components/footer.php'; ?>

