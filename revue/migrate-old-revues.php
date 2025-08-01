<?php
// migrate-old-revues.php - Script de migration des anciennes revues
require_once 'create-flipbook.php';
require_once 'flipbook-template.php';

class RevueMigrator {
    private $sourceDir;
    private $targetDir;
    private $generator;
    
    public function __construct() {
        $this->sourceDir = realpath('../media/com_html5flippingbook/images');
        $this->targetDir = realpath('.');
        $this->generator = new FlipbookGenerator();
        
        echo "Migration des anciennes revues vers le nouveau système\n";
        echo "Source: {$this->sourceDir}\n";
        echo "Cible: {$this->targetDir}\n\n";
    }
    
    public function migrate() {
        if (!is_dir($this->sourceDir)) {
            echo "❌ Erreur: Le dossier source n'existe pas: {$this->sourceDir}\n";
            return;
        }
        
        $folders = $this->getRevueFolders();
        echo "\n";
echo '<br><a href="/revue/admin" style="display:inline-block;padding:8px 18px;margin-top:20px;background:#fff;color:#478cb3;border:2px solid #478cb3;border-radius:4px;text-decoration:none;font-weight:bold;">&larr; Retour à l\'administration</a>';
echo "\n\n";
        echo "📂 Trouvé " . count($folders) . " dossiers de revues à migrer\n\n";
        
        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($folders as $folder) {
            try {
                $result = $this->migrateRevue($folder);
                if ($result['success']) {
                    $migrated++;
                    echo "✅ {$folder} → {$result['target']} ({$result['pages']} pages)\n";
                } else {
                    $skipped++;
                    echo "⏭️  {$folder} → {$result['reason']}\n";
                }
            } catch (Exception $e) {
                $errors++;
                echo "❌ {$folder} → Erreur: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Résumé de la migration:\n";
        echo "✅ Migrées: $migrated\n";
        echo "⏭️  Ignorées: $skipped\n";
        echo "❌ Erreurs: $errors\n";
        echo '<br><a href="/revue/admin" style="display:inline-block;padding:8px 18px;margin-top:20px;background:#fff;color:#478cb3;border:2px solid #478cb3;border-radius:4px;text-decoration:none;font-weight:bold;">&larr; Retour à l\'administration</a>';
    }
    
    private function getRevueFolders() {
        $folders = [];
        $items = scandir($this->sourceDir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === 'index.html') continue;
            
            $path = $this->sourceDir . '/' . $item;
            if (is_dir($path) && preg_match('/^revue-(\d{4})-(\d{2}|\d{4})$/', $item)) {
                $folders[] = $item;
            }
        }
        
        sort($folders);
        return $folders;
    }
    
    private function migrateRevue($folderName) {
        $sourcePath = $this->sourceDir . '/' . $folderName;
        
        // Extraire la date du nom du dossier
        if (!preg_match('/^revue-(\d{4})-(\d{2}|\d{4})$/', $folderName, $matches)) {
            return ['success' => false, 'reason' => 'Format de nom invalide'];
        }
        
        $year = $matches[1];
        $monthOrDate = $matches[2];
        
        // Convertir le format de date
        if (strlen($monthOrDate) == 2) {
            // Format MM
            $month = $monthOrDate;
        } else {
            // Format MMDD (comme 0708 pour juillet-août)
            $month = substr($monthOrDate, 0, 2);
        }
        
        // Créer le nom de cible
        $targetName = $this->generateTargetName($year, $month, $monthOrDate);
        $targetPath = $this->targetDir . '/' . $targetName;
        
        // Vérifier si déjà migré
        if (is_dir($targetPath)) {
            return ['success' => false, 'reason' => 'Déjà migré'];
        }
        
        // Trouver les images sources
        $images = $this->findImages($sourcePath);
        if (empty($images)) {
            return ['success' => false, 'reason' => 'Aucune image trouvée'];
        }
        
        // Créer le flipbook
        $this->generator->createFlipbook($targetName, $this->generateTitle($year, $month, $monthOrDate));
        
        // Copier et renommer les images
        $this->copyAndRenameImages($images, $targetPath . '/images');
        
        // Générer le flipbook HTML
        $this->regenerateFlipbook($targetName, count($images));
        
        return [
            'success' => true,
            'target' => $targetName,
            'pages' => count($images)
        ];
    }
    
    private function generateTargetName($year, $month, $originalMonth) {
        $monthNames = [
            '01' => 'janvier',
            '02' => 'fevrier',
            '03' => 'mars',
            '04' => 'avril',
            '05' => 'mai',
            '06' => 'juin',
            '07' => 'juillet',
            '08' => 'aout',
            '09' => 'septembre',
            '10' => 'octobre',
            '11' => 'novembre',
            '12' => 'decembre'
        ];
        
        // Cas spéciaux pour les numéros doubles
        if ($originalMonth === '0708') {
            return "juillet-aout-$year";
        }
        
        $monthName = $monthNames[$month] ?? $month;
        return "$monthName-$year";
    }
    
    private function generateTitle($year, $month, $originalMonth) {
        $monthNames = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre'
        ];
        
        if ($originalMonth === '0708') {
            return "Extraits Revue Sainte Rita - Juillet-Août $year";
        }
        
        $monthName = $monthNames[$month] ?? $month;
        return "Extraits Revue Sainte Rita - $monthName $year";
    }
    
    private function findImages($sourcePath) {
        $images = [];
        
        // D'abord chercher dans le dossier original-XXX/
        $originalDirs = glob($sourcePath . '/original-*');
        if (!empty($originalDirs)) {
            $originalDir = $originalDirs[0];
            $files = glob($originalDir . '/*.jpg');
            if (!empty($files)) {
                sort($files);
                return $files;
            }
        }
        
        // Sinon chercher les thumb_ directement
        $thumbFiles = glob($sourcePath . '/thumb_*.jpg');
        if (!empty($thumbFiles)) {
            sort($thumbFiles);
            return $thumbFiles;
        }
        
        // Sinon tous les .jpg
        $allJpg = glob($sourcePath . '/*.jpg');
        if (!empty($allJpg)) {
            sort($allJpg);
            return $allJpg;
        }
        
        return [];
    }
    
    private function copyAndRenameImages($sourceImages, $targetDir) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $pageNum = 1;
        foreach ($sourceImages as $sourceImage) {
            $targetImage = $targetDir . "/page$pageNum.jpg";
            copy($sourceImage, $targetImage);
            $pageNum++;
        }
    }
    
    private function regenerateFlipbook($flipbookName, $imageCount) {
        $dossier = "./$flipbookName";
        $titre = "Extraits Revue Sainte Rita - " . ucfirst(str_replace('-', ' ', $flipbookName));
        
        $html = generateSimpleFlipbook($flipbookName, $titre, $imageCount);
        file_put_contents($dossier . '/index.html', $html);
    }
}

// Exécution du script
if (php_sapi_name() === 'cli') {
    $migrator = new RevueMigrator();
    $migrator->migrate();
} else {
    echo "<pre>";
    $migrator = new RevueMigrator();
    $migrator->migrate();
    echo "</pre>";
}
?>
