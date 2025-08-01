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
                // Extraire la date pour le tri
                $sortDate = $this->extractSortDate($name);
                
                $flipbooks[] = [
                    'name' => $name,
                    'url' => $name . '/',
                    'created' => date('Y-m-d H:i:s', filemtime($dir . '/index.html')),
                    'images_count' => count(glob($dir . '/images/*.{jpg,jpeg,png}', GLOB_BRACE)),
                    'sort_date' => $sortDate
                ];
            }
        }
        
        // Trier par date décroissante (plus récent en premier)
        usort($flipbooks, function($a, $b) {
            return $b['sort_date'] <=> $a['sort_date'];
        });
        
        return $flipbooks;
    }
    
    private function extractSortDate($name) {
        // Extraire année et mois du nom du flipbook pour le tri
        // Formats supportés : mois-YYYY, juillet-aout-YYYY, etc.
        
        $monthMap = [
            'janvier' => '01', 'fevrier' => '02', 'mars' => '03', 'avril' => '04',
            'mai' => '05', 'juin' => '06', 'juillet' => '07', 'aout' => '08',
            'septembre' => '09', 'octobre' => '10', 'novembre' => '11', 'decembre' => '12'
        ];
        
        // Cas 1: juillet-aout-YYYY
        if (preg_match('/^juillet-aout-(\d{4})$/', $name, $matches)) {
            return $matches[1] . '-07'; // Utilise juillet pour le tri
        }
        
        // Cas 2: mois-YYYY
        if (preg_match('/^([a-z]+)-(\d{4})$/', $name, $matches)) {
            $monthName = $matches[1];
            $year = $matches[2];
            $monthNum = $monthMap[$monthName] ?? '00';
            return $year . '-' . $monthNum;
        }
        
        // Cas 3: YYYY-MM (format numérique)
        if (preg_match('/^(\d{4})-(\d{2})$/', $name, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }
        
        // Fallback: utiliser la date de création du fichier
        return date('Y-m', filemtime($this->baseDir . $name . '/index.html'));
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