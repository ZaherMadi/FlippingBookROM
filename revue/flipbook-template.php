<?php
// flipbook-template-simple.php - Template sans auto-détection
function generateSimpleFlipbook($moisAnnee, $titre = null, $nbPages = 10) {
    if (!$titre) {
        $titre = "Extraits Revue Sainte Rita - " . ucfirst(str_replace('-', ' ', $moisAnnee));
    }
    
    // Générer la liste des pages statiquement
    $pagesArray = ['null'];
    for ($i = 1; $i <= $nbPages; $i++) {
        $pagesArray[] = "'images/page{$i}.jpg'";
    }
    $pagesJS = '[' . implode(', ', $pagesArray) . ']';
    
    return <<<HTML
<!DOCTYPE html> 
<html>
<head>
    <title>{$titre}</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; background-color: #333; font-family: Arial, sans-serif; overflow: hidden; }
        .flipbook { width: 100vw; height: 100vh; }
        .flipbook .viewport { cursor: url("https://www.sainte-rita.net/components/com_html5flippingbook/assets/images/zoom-ico.png") 16 16, zoom-in !important; }
        .flipbook .viewport.zoom { cursor: url("https://www.sainte-rita.net/components/com_html5flippingbook/assets/images/zoom-ico.png") 16 16, zoom-out !important; }
        .page-indicator { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0,0,0,0.8); padding: 8px 16px; border-radius: 20px; z-index: 1000; font-size: 14px; font-weight: bold; }
        .instructions { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); color: #ccc; background: rgba(0,0,0,0.6); padding: 8px 16px; border-radius: 15px; z-index: 1000; font-size: 12px; text-align: center; }
        .loading { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 18px; z-index: 2000; }
        
        /* FLÈCHES DE NAVIGATION */
        .nav-arrow {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            width: 80px;
            height: 100px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-arrow::before {
            content: '';
            width: 0;
            height: 0;
            transition: all 0.3s ease;
            filter: drop-shadow(0 8px 15px rgba(71, 140, 179, 0.4));
        }
        
        .nav-arrow-left {
            left: 30px;
        }
        
        .nav-arrow-left::before {
            border-top: 50px solid transparent;
            border-bottom: 50px solid transparent;
            border-right: 60px solid #fcf6ef;
        }
        
        .nav-arrow-right {
            right: 30px;
        }
        
        .nav-arrow-right::before {
            border-top: 50px solid transparent;
            border-bottom: 50px solid transparent;
            border-left: 60px solid #fcf6ef;
        }
        
        .nav-arrow:hover {
            transform: translateY(-50%) scale(1.15);
        }
        
        .nav-arrow:hover::before {
            filter: drop-shadow(0 12px 25px rgba(255, 255, 255, 0.4));
        }
        
        .nav-arrow:active {
            transform: translateY(-50%) scale(1.05);
        }
        
        .nav-arrow.disabled {
            opacity: 0.3;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .nav-arrow-left:hover::before {
            border-right-color: #fff;
            animation: pulse-left 1.5s infinite;
        }
        
        .nav-arrow-left:active::before {
            border-right-color: #fff;
        }
        
        .nav-arrow-right:hover::before {
            border-left-color: #fff;
            animation: pulse-right 1.5s infinite;
        }
        
        .nav-arrow-right:active::before {
            border-left-color: #fff;
        }
        
        @keyframes pulse-left {
            0% { border-right-color: #fcf6ef; }
            50% { border-right-color: #fff; }
            100% { border-right-color: #fcf6ef; }
        }
        
        @keyframes pulse-right {
            0% { border-left-color: #fcf6ef; }
            50% { border-left-color: #fff; }
            100% { border-left-color: #fcf6ef; }
        }
        
        /* Adaptation mobile */
        @media (max-width: 768px) {
            .nav-arrow {
                width: 50px;
                height: 70px;
                top: auto;
                bottom: 80px;
                transform: none;
            }
            .nav-arrow-left {
                left: 20%;
                transform: translateX(-50%);
            }
            .nav-arrow-right {
                right: 20%;
                transform: translateX(50%);
            }
            .nav-arrow-left::before {
                border-top-width: 25px;
                border-bottom-width: 25px;
                border-right-width: 35px;
            }
            .nav-arrow-right::before {
                border-top-width: 25px;
                border-bottom-width: 25px;
                border-left-width: 35px;
            }
            .nav-arrow:hover {
                transform: scale(1.15);
            }
            .nav-arrow-left:hover {
                transform: translateX(-50%) scale(1.15);
            }
            .nav-arrow-right:hover {
                transform: translateX(50%) scale(1.15);
            }
            .nav-arrow:active {
                transform: scale(1.05);
            }
            .nav-arrow-left:active {
                transform: translateX(-50%) scale(1.05);
            }
            .nav-arrow-right:active {
                transform: translateX(50%) scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="loading" v-if="!flipbookReady">Chargement de {$moisAnnee}...</div>
        <div class="page-indicator" v-if="flipbookReady">Page {{ pageNum || 1 }} / {{ pages.length - 1 }}</div>
        <div class="instructions" v-if="flipbookReady">{$titre}</div>
        
        <!-- FLÈCHES DE NAVIGATION -->
        <div v-if="flipbookReady" class="nav-arrow nav-arrow-left" 
             @click="goToPreviousPage"
             title="Page précédente (← ou Q)">
        </div>
        
        <div v-if="flipbookReady" class="nav-arrow nav-arrow-right" 
             @click="goToNextPage"
             title="Page suivante (→ ou D)">
        </div>
        
        <flipbook ref="flipbook" class="flipbook" :pages="pages" :start-page="pageNum" :click-to-zoom="true" @flip-left-end="onFlipLeftEnd" @flip-right-end="onFlipRightEnd" v-slot="flipbookSlot">
            <div class="page-indicator" v-if="flipbookSlot && flipbookReady">Page {{ flipbookSlot.page }} / {{ flipbookSlot.numPages }}</div>
        </flipbook>
    </div>
    
    <script src="https://unpkg.com/vue@3"></script>
    <script src="https://unpkg.com/flipbook-vue@latest/dist/flipbook.min.js"></script>
    <script>
        const { createApp } = Vue;
        createApp({
            data() { 
                return { 
                    flipbookReady: false, 
                    flipbookData: null, 
                    pageNum: null, 
                    pages: {$pagesJS}
                }; 
            },
            methods: {
                onFlipLeftEnd(page) { 
                    this.pageNum = page;
                    window.location.hash = '#page/' + page; 
                },
                onFlipRightEnd(page) { 
                    this.pageNum = page;
                    window.location.hash = '#page/' + page; 
                },
                setPageFromHash() {
                    const match = window.location.hash.match(/#page\/(\d+)/);
                    if (match) this.pageNum = parseInt(match[1], 10);
                },
                goToPreviousPage() {
                    const flipbook = this.\$refs.flipbook;
                    if (flipbook && flipbook.canFlipLeft) {
                        flipbook.flipLeft();
                    }
                },
                goToNextPage() {
                    const flipbook = this.\$refs.flipbook;
                    if (flipbook && flipbook.canFlipRight) {
                        flipbook.flipRight();
                    }
                }
            },
            mounted() {
                console.log('🚀 Initialisation SIMPLE du flipbook {$moisAnnee}...');
                console.log('Vue et Flipbook chargés:', typeof Vue !== 'undefined', typeof Flipbook !== 'undefined');
                console.log('Pages prédéfinies:', this.pages);
                console.log('Nombre de pages:', this.pages.length - 1);
                
                // Activation immédiate sans détection
                setTimeout(() => {
                    this.flipbookReady = true;
                    console.log('✅ Flipbook activé immédiatement!');
                }, 500);
                
                // Gestion des URL et raccourcis clavier
                window.addEventListener('hashchange', this.setPageFromHash);
                this.setPageFromHash();
                window.addEventListener('keydown', (e) => {
                    const flipbook = this.\$refs.flipbook;
                    if (!flipbook || !this.flipbookReady) return;
                    let handled = false;
                    if (e.key === 'ArrowLeft' || e.key === 'q') { if (flipbook.canFlipLeft) { flipbook.flipLeft(); handled = true; } }
                    else if (e.key === 'ArrowRight' || e.key === 'd') { if (flipbook.canFlipRight) { flipbook.flipRight(); handled = true; } }
                    else if (e.key === ' ') { flipbook.canZoomIn ? flipbook.zoomIn() : flipbook.zoomOut(); handled = true; }
                    if (handled) e.preventDefault();
                });
            }
        }).component('Flipbook', Flipbook).mount('#app');
    </script>
</body>
</html>
HTML;
}
?>
