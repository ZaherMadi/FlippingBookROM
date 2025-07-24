<?php
// Script de test pour régénérer le flipbook
require_once 'flipbook-template.php';

echo "🔄 Régénération du flipbook avril-2025...\n";

// Compter les images
$images = glob('./avril-2025/images/page*.jpg');
$imageCount = count($images);

echo "📸 Images trouvées: $imageCount\n";

// Générer le nouveau HTML
$html = generateSimpleFlipbook('avril-2025', 'Extraits Revue Sainte Rita - Avril 2025', $imageCount);

// Sauvegarder
file_put_contents('./avril-2025/index.html', $html);

echo "✅ Flipbook régénéré avec les nouvelles flèches triangulaires !\n";
echo "🌐 Testez sur: http://localhost/revue/avril-2025/\n";
?>
