<?php
// create-flipbook.php - Générateur de flipbooks
require_once 'flipbook-template.php';

class FlipbookGenerator {
    private $baseDir;
    
    public function __construct($baseDir = './') {
        $this->baseDir = rtrim($baseDir, '/') . '/';
    }
    
    public function createFlipbook($moisAnnee, $titre = null) {
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $moisAnnee)) {
            throw new Exception("Nom invalide. Utilisez uniquement lettres, chiffres, tirets et underscores");
        }
        
        $dossier = $this->baseDir . $moisAnnee;
        
        if (!file_exists($dossier)) mkdir($dossier, 0755, true);
        if (!file_exists($dossier . '/images')) mkdir($dossier . '/images', 0755, true);
        
        // Compter les images existantes
        $images = glob($dossier . '/images/page*.jpg');
        $imageCount = count($images);
        
        $html = generateSimpleFlipbook($moisAnnee, $titre, max(1, $imageCount));
        file_put_contents($dossier . '/index.html', $html);
        
        $readme = "# Flipbook {$moisAnnee}\n\n## Instructions:\n1. Utilisez l'interface admin pour uploader vos images\n2. Les images seront automatiquement renommées\n3. URL: votre-site.com/revue/{$moisAnnee}\n\nCréé le: " . date('Y-m-d H:i:s') . "\nImages: {$imageCount}";
        file_put_contents($dossier . '/README.md', $readme);
        
        return ['success' => true, 'dossier' => $moisAnnee, 'path' => $dossier, 'url' => $moisAnnee . '/', 'images' => $imageCount];
    }
    
    public function listFlipbooks() {
        $flipbooks = [];
        $dirs = glob($this->baseDir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            if (file_exists($dir . '/index.html')) {
                $flipbooks[] = [
                    'name' => $name,
                    'url' => $name . '/',
                    'created' => date('Y-m-d H:i:s', filemtime($dir . '/index.html')),
                    'images_count' => count(glob($dir . '/images/*.{jpg,jpeg,png}', GLOB_BRACE))
                ];
            }
        }
        return $flipbooks;
    }
}

// Usage en ligne de commande
if (php_sapi_name() === 'cli' && $argc >= 2) {
    $generator = new FlipbookGenerator();
    try {
        $result = $generator->createFlipbook($argv[1], $argv[2] ?? null);
        echo "✅ Flipbook créé: {$result['dossier']}\n📁 Dossier: {$result['path']}\n🌐 URL: {$result['url']}\n";
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}
?>