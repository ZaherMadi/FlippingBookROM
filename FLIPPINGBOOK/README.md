# 📖 Mon Flipbook Personnel

## 🚀 Comment utiliser :

### 1. Ajouter vos images
- Mettez toutes vos images dans le dossier `images/`
- Formats supportés : **JPG, JPEG, PNG, GIF**
- **Le nom n'a pas d'importance** : `photo1.jpg`, `scan.png`, `toto.jpeg`, etc.

### 2. Modifier la liste des pages
Dans le fichier `index.html`, ligne 87, modifiez cette partie :

```javascript
pages: [
  null, // Page de couverture vide (supprimez si pas voulu)
  'images/page1.jpg',
  'images/page2.jpg', 
  'images/page3.jpg',
  'images/mon-image.jpeg',
  // Ajoutez vos images ici
]
```

### 3. Lancer le flipbook
```bash
cd FLIPPINGBOOK
python3 -m http.server 8080
```
Puis ouvrez : http://localhost:8080

## 🎮 Contrôles :
- **Clic** : Zoom in/out
- **Flèches ← →** : Navigation
- **Q / D** : Navigation alternative  
- **Espace/Entrée** : Zoom
- **Échap** : Zoom out
- **URL** : `#page/3` pour aller directement à la page 3

## 📝 Exemples de noms d'images valides :
- `images/page1.jpg`
- `images/ma-photo.jpeg` 
- `images/document-scan.png`
- `images/123.jpg`
- `images/IMG_001.JPG`
- `images/whatever.png`

## 🔥 Important :
- **Mettez `null` en premier** si vous voulez une page de couverture vide
- **Supprimez `null`** si vous voulez commencer directement par la première image
- L'ordre dans le code = l'ordre dans le flipbook
