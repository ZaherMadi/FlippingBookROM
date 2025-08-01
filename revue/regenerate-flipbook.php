<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$baseDir = __DIR__;
$templateFile = $baseDir . '/flipbook-template.php';

// Vérifier que le template existe
if (!file_exists($templateFile)) {
    die("❌ Template non trouvé : $templateFile\n");
}

// Inclure le template pour utiliser la fonction
require_once $templateFile;

// Vérifier que la fonction existe
if (!function_exists('generateSimpleFlipbook')) {
    die("❌ Fonction generateSimpleFlipbook non trouvée dans le template\n");
}

// En-tête avec bouton retour
echo '<br><a href="/revue/admin" style="display:inline-block;padding:8px 18px;margin-top:20px;background:#fff;color:#478cb3;border:2px solid #478cb3;border-radius:4px;text-decoration:none;font-weight:bold;">&larr; Retour à l\'administration</a><br><br>';

echo "═══════════════════════════════════════════════════════════════<br>";
echo "🚀 <strong>RÉGÉNÉRATION DE TOUS LES FLIPBOOKS HTML</strong><br>";
echo "═══════════════════════════════════════════════════════════════<br>";
echo "📂 Répertoire de base : $baseDir<br>";
echo "📄 Template utilisé  : flipbook-template.php<br>";
echo "═══════════════════════════════════════════════════════════════<br><br>";

$regenerated = 0;
$errors = 0;
$ignored = 0;

// Scanner tous les dossiers dans /Revue
$directories = scandir($baseDir);
$validDirs = [];

// Première passe : identifier les dossiers valides
foreach ($directories as $dir) {
    if ($dir === '.' || $dir === '..' || !is_dir($baseDir . '/' . $dir)) {
        continue;
    }
    
    if (preg_match('/^[a-z]+-\d{4}$/', $dir)) {
        $validDirs[] = $dir;
    }
}

// Trier les dossiers par ordre chronologique
usort($validDirs, function($a, $b) {
    $partsA = explode('-', $a);
    $partsB = explode('-', $b);
    $yearA = intval($partsA[1]);
    $yearB = intval($partsB[1]);
    
    if ($yearA !== $yearB) {
        return $yearA <=> $yearB;
    }
    
    // Si même année, trier par mois
    $months = ['janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 
               'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
    $monthA = array_search($partsA[0], $months);
    $monthB = array_search($partsB[0], $months);
    
    return $monthA <=> $monthB;
});

echo "📋 <strong>" . count($validDirs) . " dossiers de revue détectés</strong><br><br>";

// Traitement des dossiers valides
foreach ($validDirs as $index => $dir) {
    $revueDir = $baseDir . '/' . $dir;
    $indexFile = $revueDir . '/index.html';
    $imagesDir = $revueDir . '/images';
    
    $num = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
    
    // Vérifier que le dossier images existe
    if (!is_dir($imagesDir)) {
        echo "$num. 📁 <strong>$dir</strong> → ⚠️ Aucun dossier images trouvé - <span style='color:#ff6b35'>IGNORÉ</span><br>";
        $ignored++;
        continue;
    }
    
    // Compter les pages dans le dossier images
    $imageFiles = glob($imagesDir . '/page*.jpg');
    if (empty($imageFiles)) {
        echo "$num. 📁 <strong>$dir</strong> → ⚠️ Aucune image page*.jpg trouvée - <span style='color:#ff6b35'>IGNORÉ</span><br>";
        $ignored++;
        continue;
    }
    
    // Trier les fichiers par numéro de page
    usort($imageFiles, function($a, $b) {
        preg_match('/page(\d+)\.jpg/', basename($a), $matchesA);
        preg_match('/page(\d+)\.jpg/', basename($b), $matchesB);
        return intval($matchesA[1]) <=> intval($matchesB[1]);
    });
    
    $pageCount = count($imageFiles);
    
    // Extraire le nom du mois et l'année
    $parts = explode('-', $dir);
    $mois = ucfirst($parts[0]);
    $annee = $parts[1];
    
    // Générer le titre
    $titre = "Extraits Revue - $mois $annee";
    
    // Appeler la fonction du template avec les bonnes données
    $content = generateSimpleFlipbook($dir, $titre, $pageCount);
    
    // Sauvegarder le fichier index.html
    $result = file_put_contents($indexFile, $content);
    
    if ($result !== false) {
        echo "$num. 📁 <strong>$dir</strong> → 📄 $pageCount pages → <span style='color:#27ae60'>✅ SUCCÈS</span><br>";
        $regenerated++;
    } else {
        echo "$num. 📁 <strong>$dir</strong> → 📄 $pageCount pages → <span style='color:#e74c3c'>❌ ERREUR</span><br>";
        $errors++;
    }
}

// Résumé final
echo "<br>═══════════════════════════════════════════════════════════════<br>";
echo " <strong>          Régénération Terminée </strong><br>";
echo "═══════════════════════════════════════════════════════════════<br>";
echo "✅ <span style='color:#27ae60'><strong>Fichiers HTML régénérés : $regenerated</strong></span><br>";
echo "❌ <span style='color:#e74c3c'><strong>Erreurs : $errors</strong></span><br>";
echo "⏭️ <span style='color:#ff6b35'><strong>Dossiers ignorés : $ignored</strong></span><br>";
echo "───────────────────────────────────────────────────────────────<br>";
echo "📊 <strong>Total traité : " . ($regenerated + $errors + $ignored) . "</strong><br>";
echo "═══════════════════════════════════════════════════════════════<br>";

if ($regenerated > 0) {
    echo "<br>💡 <em>Tous les fichiers index.html ont été remplacés par le template mis à jour.</em><br>";
    echo "🔗 <em>Testez vos flipbooks dans le navigateur pour vérifier le bon fonctionnement.</em><br>";
}
?>