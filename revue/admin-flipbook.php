<?php
// admin-flipbook.php - Interface d'administration (protection par IP)
require_once 'create-flipbook.php';
require_once 'flipbook-template.php';

$generator = new FlipbookGenerator();
$message = $error = '';

if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'create') {
            $result = $generator->createFlipbook($_POST['mois_annee'], $_POST['titre']);
            $message = "FlippingBook '{$result['dossier']}' créé avec succès !";
        } elseif ($_POST['action'] === 'upload') {
            $flipbookName = $_POST['flipbook_name'];
            $uploadResult = handleImageUpload($flipbookName);
            if ($uploadResult['success']) {
                $message = "{$uploadResult['count']} images uploadées et renommées ! FlippingBook régénéré automatiquement.";
            } else {
                $error = $uploadResult['error'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


function handleImageUpload($flipbookName) {
    $targetDir = "./{$flipbookName}/images/";
    
    if (!file_exists($targetDir)) {
        return ['success' => false, 'error' => "Le dossier du flipbook '$flipbookName' n'existe pas."];
    }
    
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        return ['success' => false, 'error' => "Aucune image sélectionnée."];
    }
    
    $uploadedCount = 0;
    $errors = [];
    
    // Supprimer les anciennes images
    $oldImages = glob($targetDir . "page*.jpg");
    foreach ($oldImages as $oldImage) {
        unlink($oldImage);
    }
    
    // Traiter chaque image uploadée
    foreach ($_FILES['images']['name'] as $key => $filename) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['images']['tmp_name'][$key];
            $uploadedCount++;
            $newName = "page{$uploadedCount}.jpg";
            $targetPath = $targetDir . $newName;
            
            if (move_uploaded_file($tmpName, $targetPath)) {
                // Optimiser l'image si possible
                if (function_exists('imagecreatefromjpeg')) {
                    optimizeImage($targetPath);
                }
            } else {
                $errors[] = "Erreur lors de l'upload de $filename";
            }
        }
    }
    
    if ($uploadedCount > 0) {
        // Régénérer le flipbook avec le bon nombre de pages
        regenerateFlipbookWithImageCount($flipbookName, $uploadedCount);
        return ['success' => true, 'count' => $uploadedCount];
    }
    
    return ['success' => false, 'error' => implode(', ', $errors)];
}

function optimizeImage($imagePath) {
    try {
        $image = imagecreatefromjpeg($imagePath);
        if ($image) {
            // Réduire la qualité pour optimiser la taille
            imagejpeg($image, $imagePath, 85);
            imagedestroy($image);
        }
    } catch (Exception $e) {
        // Silencieux si l'optimisation échoue
    }
}

function regenerateFlipbookWithImageCount($flipbookName, $imageCount) {
    $dossier = "./{$flipbookName}";
    $titre =  ucfirst(str_replace('-', ' ', $flipbookName));
    
    $html = generateSimpleFlipbook($flipbookName, $titre, $imageCount);
    file_put_contents($dossier . '/index.html', $html);
}

function countImages($flipbookName) {
    $targetDir = "./{$flipbookName}/images/";
    if (!file_exists($targetDir)) {
        return 0;
    }
    $images = glob($targetDir . "page*.jpg");
    return count($images);
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 4;
$allFlipbooks = $generator->listFlipbooks();
$totalFlipbooks = count($allFlipbooks);
$totalPages = ceil($totalFlipbooks / $perPage);
$offset = ($page - 1) * $perPage;
$flipbooks = array_slice($allFlipbooks, $offset, $perPage);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestionnaire FlippingBooks</title>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #fcf6ef;
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
            padding-bottom: 20px;
            border-bottom: 3px solid #478cb3;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .header-title-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .header h1 { 
            color: #2c3e50; 
            margin: 0; 
            font-size: 2.5em;
            font-weight: 700;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 1.2em;
            margin: 10px 0 0 0;
        }
        
        .form-section { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 30px; 
            margin-bottom: 30px; 
        }
        
        @media (max-width: 768px) {
            .form-section { grid-template-columns: 1fr; }
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: #2c3e50; 
            font-size: 1.1em;
        }
        
        .form-group input { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e0e6ed; 
            border-radius: 8px; 
            font-size: 16px; 
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus { 
            border-color: #478cb3; 
            outline: none; 
            background: white;
            box-shadow: 0 0 0 3px rgba(71, 140, 179, 0.1);
        }
        
        .form-group small {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        
        /* BOUTONS UNIFIÉS */
        .btn { 
            padding: 15px 30px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center; 
            transition: all 0.3s ease;
            background: #fff;
            color: #478cb3;
            border: 2px solid #478cb3;
            min-width: 150px;
        }
        
        .btn:hover { 
            background: #478cb3;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(71, 140, 179, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* VARIANTES DE BOUTONS - Même comportement hover */
        .btn-primary { 
            background: #fff; 
            color: #478cb3;
            border-color: #478cb3;
        }
        
        .btn-primary:hover {
            background: #478cb3;
            color: #fff;
            box-shadow: 0 5px 15px rgba(71, 140, 179, 0.3);
        }
        
        .btn-success { 
            background: #fff; 
            color: #478cb3;
            border-color: #478cb3;
        }
        
        .btn-success:hover {
            background: #478cb3;
            color: #fff;
            box-shadow: 0 5px 15px rgba(71, 140, 179, 0.3);
        }
        
        .btn-upload { 
            background: #fff; 
            color: #478cb3;
            border-color: #478cb3;
        }
        
        .btn-upload:hover {
            background: #478cb3;
            color: #fff;
            box-shadow: 0 5px 15px rgba(71, 140, 179, 0.3);
        }
        
        .alert { 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            font-weight: 600;
            font-size: 1.1em;
        }
        
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            border: 2px solid #c3e6cb; 
            border-left: 5px solid #27ae60;
        }
        
        .alert-error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 2px solid #f5c6cb; 
            border-left: 5px solid #e74c3c;
        }
        
        .flipbook-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); 
            gap: 25px; 
            margin-top: 40px; 
        }
        
        .flipbook-card { 
            background: #f8f9fa; 
            padding: 25px; 
            border-radius: 12px; 
            border: 2px solid #e9ecef; 
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .flipbook-card:hover { 
            border-color: #478cb3; 
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(71, 140, 179, 0.15);
        }
        
        .flipbook-title { 
            font-size: 1.3em; 
            font-weight: 700; 
            margin-bottom: 15px; 
            color: #2c3e50; 
        }
        
        .flipbook-info { 
            color: #6c757d; 
            font-size: 0.95em; 
            margin-bottom: 20px; 
            line-height: 1.6;
        }
        
        .upload-section { 
            background: #f1f3f4; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px dashed #dee2e6; 
            transition: all 0.3s ease;
        }
        
        .upload-section.has-images { 
            border-color: #27ae60; 
            background: #d4edda; 
            border-style: solid;
        }
        
        .upload-section h4 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .file-input { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e0e6ed; 
            border-radius: 8px; 
            margin: 15px 0; 
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .file-input:focus {
            border-color: #478cb3;
            outline: none;
            box-shadow: 0 0 0 3px rgba(71, 140, 179, 0.1);
        }
        
        .instructions { 
            background: linear-gradient(135deg, #f8f5f0 0%, #faf7f2 100%); 
            padding: 30px; 
            border-radius: 12px; 
            margin-top: 40px; 
            border-left: 5px solid #478cb3;
            box-shadow: 0 2px 10px rgba(71, 140, 179, 0.1);
        }
        
        .instructions h4 {
            color: #2c3e50;
            margin-top: 0;
            font-size: 1.3em;
        }
        
        .instructions ol, .instructions ul {
            line-height: 1.8;
            color: #2c3e50;
        }
        
        .instructions code {
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e74c3c;
            border: 1px solid #e9ecef;
        }
        
        .create-form { 
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); 
            padding: 30px; 
            border-radius: 12px; 
            border: 2px solid #e9ecef; 
            margin-bottom: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .create-form h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.5em;
            font-weight: 700;
        }
        
        /* Responsiveness */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { padding: 20px; }
            .flipbook-grid { grid-template-columns: 1fr; }
            .btn { min-width: auto; width: 100%; margin-bottom: 10px; }
            .rom-logo-header { width: 30px !important; height: 30px !important; }
            .rom-logo-footer { width: 40px !important; height: 40px !important; }
        }
        
        /* LOGOS ROM */
        .rom-logo-header {
            width: 45px;
            height: 45px;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .rom-logo-header:hover {
            opacity: 1;
            transform: scale(1.05);
        }
        
        .rom-logo-footer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            opacity: 0.6;
            transition: all 0.3s ease;
            cursor: pointer;
            z-index: 1000;
        }
        
        .rom-logo-footer:hover {
            opacity: 1;
            transform: scale(1.05);
        }
        
        /* STYLES PAGINATION */
        .pagination {
            margin: 30px 0;
            text-align: center;
        }
        
        .pagination-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            padding: 10px 15px !important;
            min-width: auto !important;
            font-size: 14px !important;
            border-radius: 6px !important;
            transition: all 0.3s ease !important;
        }
        
        .pagination-btn.active {
            background: #478cb3 !important;
            color: white !important;
            border-color: #478cb3 !important;
            font-weight: 700 !important;
        }
        
        .pagination-btn.active:hover {
            background: #3a7096 !important;
            transform: none !important;
        }
        
        .pagination-btn.disabled {
            background: #f8f9fa !important;
            color: #9ca3af !important;
            border-color: #e9ecef !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }
        
        .pagination-btn.disabled:hover {
            background: #f8f9fa !important;
            color: #9ca3af !important;
            transform: none !important;
            box-shadow: none !important;
        }
        
        @media (max-width: 768px) {
            .pagination-nav {
                gap: 5px;
            }
            
            .pagination-btn {
                padding: 8px 12px !important;
                font-size: 13px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title-row">
                <h1>Gestionnaire de FlippingBooks</h1>
                <span style="color: #7f8c8d; font-size: 18px;">by</span>
                    <a href="https://rom.fr" target="_blank">
                <img src="ROMblack.svg" alt="ROM" class="rom-logo-header">
                </a>
            </div>
            <p style="color: #7f8c8d; font-size: 18px; margin: 0;">Système automatisé</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="create-form">
            <h3 style="margin-top: 0; color: #2c3e50;">Créer un nouveau FlippingBook</h3>
            
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-section">
                    <div class="form-group">
                        <label>Nom du FlippingBook</label>
                        <input type="text" name="mois_annee" placeholder="aout-2025" required 
                               pattern="[a-zA-Z0-9\-_]+" title="Lettres, chiffres, tirets et underscores uniquement">
                        <small style="color: #7f8c8d;">Exemples: aout-2025, revue-mars-2026, special-2027</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Titre (optionnel)</label>
                        <input type="text" name="titre" placeholder=" FlippingBook - Août 2025">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Créer le FlippingBook</button>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #2c3e50;">FlippingBooks créés</h3>
            <div style="color: #7f8c8d; font-size: 1em;">
                <?php if ($totalFlipbooks > 0): ?>
                    Affichage de <?= $offset + 1 ?> à <?= min($offset + $perPage, $totalFlipbooks) ?> sur <?= $totalFlipbooks ?> flipbooks
                    (Page <?= $page ?> sur <?= $totalPages ?>)
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($flipbooks)): ?>
            <p style="text-align: center; color: #7f8c8d; font-style: italic; padding: 40px;">Aucun FlippingBook créé pour le moment. Commencez par en créer un !</p>
        <?php else: ?>
            <div class="flipbook-grid">
                <?php foreach ($flipbooks as $flipbook): ?>
                    <div class="flipbook-card">
                        <div class="flipbook-title"><?= htmlspecialchars($flipbook['name']) ?></div>
                        <div class="flipbook-info">
                            Créé: <?= $flipbook['created'] ?><br>
                            Images: <?= $flipbook['images_count'] ?><br>
                            URL: <?= $flipbook['url'] ?>
                        </div>
                        <a href="<?= $flipbook['url'] ?>" target="_blank" class="btn btn-success">Visionner</a>
                        
                        <div class="upload-section <?= $flipbook['images_count'] > 0 ? 'has-images' : '' ?>">
                            <h4 style="margin-top: 0;">Gérer les images</h4>
                            
                            <?php if ($flipbook['images_count'] > 0): ?>
                                <p style="color: #155724; font-weight: bold;"><?= $flipbook['images_count'] ?> images présentes</p>
                            <?php else: ?>
                                <p style="color: #856404;">Aucune image présente</p>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                                <input type="hidden" name="action" value="upload">
                                <input type="hidden" name="flipbook_name" value="<?= htmlspecialchars($flipbook['name']) ?>">
                                
                                <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #34495e;">
                                    Sélectionner les images (JPG/PNG) :
                                </label>
                                <input type="file" name="images[]" multiple accept="image/*" class="file-input" required>
                                <small style="color: #7f8c8d; display: block; margin-bottom: 10px;">
                                    Les images seront automatiquement renommées en page1.jpg, page2.jpg, etc.
                                </small>
                                
                                <button type="submit" class="btn btn-upload">
                                    <?= $flipbook['images_count'] > 0 ? 'Remplacer les images' : 'Uploader les images' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Navigation pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 30px; text-align: center;">
                    <div class="pagination-nav">
                        <!-- Bouton Précédent -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="btn pagination-btn">‹ Précédent</a>
                        <?php else: ?>
                            <span class="btn pagination-btn disabled">‹ Précédent</span>
                        <?php endif; ?>
                        
                        <!-- Numéros de page -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="btn pagination-btn active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>" class="btn pagination-btn"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <!-- Bouton Suivant -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="btn pagination-btn">Suivant ›</a>
                        <?php else: ?>
                            <span class="btn pagination-btn disabled">Suivant ›</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 15px; color: #7f8c8d; font-size: 0.9em;">
                        Page <?= $page ?> sur <?= $totalPages ?> | Total: <?= $totalFlipbooks ?> flipbooks
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="instructions">
            <h4 style="margin-top: 0; color: #2c3e50;">Mode d'emploi :</h4>
            <ol style="line-height: 1.8;">
                <li><strong>Créer un FlippingBook</strong> avec le formulaire ci-dessus</li>
                <li><strong>Uploader vos images</strong> directement via l'interface (JPG/PNG)</li>
                <li><strong>Renommage automatique</strong> en page1.jpg, page2.jpg, etc.</li>
                <li><strong>Génération automatique</strong> du flipbook avec le bon nombre de pages</li>
                <li><strong>Tester</strong> en cliquant sur "Visionner"</li>
            </ol>
            
            <h4 style="color: #2c3e50;">Intégration Joomla :</h4>
            <ul style="line-height: 1.8;">
                <li><strong>Article Joomla :</strong> <code>&lt;a href="/revue/nom-flipbook/" target="_blank"&gt;Voir la revue&lt;/a&gt;</code></li>
                <li><strong>Iframe :</strong> <code>&lt;iframe src="/revue/nom-flipbook/" width="100%" height="600px"&gt;&lt;/iframe&gt;</code></li>
                <li><strong>URL directe :</strong> <code>votre-site.com/revue/nom-flipbook/</code></li>
            </ul>
        </div>
        <div style="text-align: center; margin-top: 40px;">
            <form action="regenerate-flipbook.php" method="post" style="display: inline-block;">
                <button type="submit" class="btn btn-primary">Régénérer template</button>
            </form>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <form action="migrate-old-revues.php" method="post" style="display: inline-block;">
                <button type="submit" class="btn btn-upload">Migrer anciens FP</button>
            </form>
        </div>
    </div>
    

</body>
</html>