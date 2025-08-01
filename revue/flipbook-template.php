<?php
function generateSimpleFlipbook($moisAnnee, $titre = null, $nbPages = 10) {
    if (!$titre) {
        $titre = ucfirst(str_replace('-', ' ', $moisAnnee));
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
    <link rel="shortcut icon" href="/images/favicon.png">
    <style>
        body { margin: 0; background-color: #333; font-family: Arial, sans-serif; overflow: hidden; }
        .flipbook { width: 100vw; height: 100vh; scale : 0.95; }
        .page-indicator { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); color: white; background: rgba(0,0,0,0.8); padding: 8px 16px; border-radius: 20px; z-index: 1000; font-size: 14px; font-weight: bold; }
        .instructions { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); color: #ccc; background: rgba(0,0,0,0.6); padding: 8px 16px; border-radius: 15px; z-index: 1000; font-size: 12px; text-align: center; }
        .loading { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 18px; z-index: 2000; }
        
        /* FLÈCHES DE NAVIGATION */
        .nav-arrow {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .nav-arrow::before {
            content: '';
            width: 0;
            height: 0;
            transition: all 0.3s ease;
        }
        
        .nav-arrow-left {
            left: 30px;
        }
        
        .nav-arrow-left::before {
            border-top: 16px solid transparent;
            border-bottom: 16px solid transparent;
            border-right: 22px solid #333;
            margin-left: -7px; /* Centrage visuel */
        }
        
        .nav-arrow-right {
            right: 30px;
        }
        
        .nav-arrow-right::before {
            border-top: 16px solid transparent;
            border-bottom: 16px solid transparent;
            border-left: 22px solid #333;
            margin-left: 7px; /* Centrage visuel */
        }
        
        .nav-arrow:hover {
            transform: translateY(-50%) scale(1.15);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .nav-arrow:hover::before {
            filter: none;
        }
        
        .nav-arrow:active {
            transform: translateY(-50%) scale(1.05);
        }
        
        .nav-arrow.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .nav-arrow-left:hover::before {
            border-right-color: #333;
        }
        
        .nav-arrow-right:hover::before {
            border-left-color: #333;
        }
        
        /* Adaptation mobile */
        @media (max-width: 768px) {
            .nav-arrow {
                width: 50px;
                height: 50px;
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
                border-top-width: 10px;
                border-bottom-width: 10px;
                border-right-width: 15px;
            }
            .nav-arrow-right::before {
                border-top-width: 10px;
                border-bottom-width: 10px;
                border-left-width: 15px;
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
        <!-- <div class="page-indicator" v-if="flipbookReady">Page {{ pageNum || 1 }} / {{ pages.length - 1 }}</div> -->
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
