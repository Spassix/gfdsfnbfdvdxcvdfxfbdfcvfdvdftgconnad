# 🎨 PROMPT D'AMÉLIORATION DU STYLE - THÈME SOMBRE MODERNE & DYNAMIQUE

## 📋 CONTEXTE
Améliorer complètement le design visuel d'un site e-commerce PHP avec un thème sombre ultra-moderne, dynamique et premium. Le site doit avoir un style visuellement impressionnant avec de nombreux éléments animés et interactifs.

## 🎯 OBJECTIFS PRINCIPAUX

### 1. **PALETTE DE COULEURS SOMBRE MONOCHROME PREMIUM**
- **Couleurs de base** : 
  - Fond principal : `#000000` (noir pur)
  - Fond secondaire : `#0a0a0a` (noir très légèrement éclairci)
  - Fond cartes : `#111111` avec transparence `rgba(17, 17, 17, 0.8)`
  - Gris foncé : `#1a1a1a`, `#222222`
  - Gris moyen : `#2a2a2a`, `#333333`
  - Gris clair : `#444444`, `#555555`
  
- **Accents néon noir/gris (très subtils)** :
  - Néon gris foncé : `#1a1a1a`, `#2a2a2a`
  - Néon gris moyen : `#3a3a3a`, `#4a4a4a`
  - Reflets blancs subtils : `rgba(255, 255, 255, 0.05)` à `rgba(255, 255, 255, 0.15)`
  - Glow très discret : `rgba(255, 255, 255, 0.1)` à `rgba(255, 255, 255, 0.2)`
  
- **Dégradés dynamiques noir/gris** :
  - Principal : `linear-gradient(135deg, #000000 0%, #1a1a1a 25%, #2a2a2a 50%, #1a1a1a 75%, #000000 100%)`
  - Hover : `linear-gradient(135deg, #0a0a0a 0%, #222222 25%, #333333 50%, #222222 75%, #0a0a0a 100%)`
  - Background : `radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.03) 0%, transparent 50%)`
  - Accent subtil : `linear-gradient(135deg, #111111 0%, #2a2a2a 50%, #111111 100%)`

### 2. **TYPOGRAPHIE MODERNE**
- **Police principale** : `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`
- **Police accent** : `'Space Grotesk'` ou `'Poppins'` pour les titres
- **Hiérarchie** :
  - H1 : `font-size: clamp(2.5rem, 5vw, 4.5rem)`, `font-weight: 800`, avec effet néon
  - H2 : `font-size: clamp(1.75rem, 3vw, 2.5rem)`, `font-weight: 700`
  - Body : `font-size: 1rem`, `line-height: 1.7`
- **Effets texte** :
  - Texte avec glow subtil gris : `text-shadow: 0 0 10px rgba(255, 255, 255, 0.1), 0 0 20px rgba(255, 255, 255, 0.05)`
  - Gradient text noir/gris animé sur les titres : `linear-gradient(135deg, #ffffff 0%, #aaaaaa 50%, #ffffff 100%)`
  - Letter-spacing augmenté sur les titres : `letter-spacing: -0.02em`

### 3. **ÉLÉMENTS DYNAMIQUES & ANIMATIONS**

#### **A. Background Animé**
- Particules flottantes grises très subtiles (CSS ou JS) - opacité très faible
- Effet de parallaxe sur le scroll
- Gradient noir/gris animé en mouvement continu (très discret)
- Orbes gris foncé qui pulsent légèrement (opacité 0.03-0.08)
- Lignes de connexion grises animées (style réseau neural, très subtiles)
- Effet de profondeur avec plusieurs couches de blur noir/gris

#### **B. Cartes Produits Premium**
- **Style** :
  - Fond : `rgba(17, 17, 17, 0.6)` avec `backdrop-filter: blur(20px)`
  - Bordure néon gris animée : `border: 2px solid rgba(255, 255, 255, 0.1)` avec gradient gris animé très subtil
  - Ombre : `box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05)`
  - Border-radius : `20px`
  
- **Animations au hover** :
  - Scale : `transform: scale(1.05) translateY(-8px)`
  - Glow gris intensifié : `box-shadow: 0 12px 48px rgba(255, 255, 255, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.15)`
  - Rotation subtile : `transform: rotate(1deg)`
  - Image zoom : `transform: scale(1.1)`
  - Transition fluide : `transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1)`

- **Badge prix** :
  - Fond gradient noir/gris animé
  - Effet shimmer gris très subtil
  - Animation pulse discrète

#### **C. Boutons & CTA**
- **Style** :
  - Fond gradient noir/gris animé : `linear-gradient(135deg, #111111 0%, #2a2a2a 100%)`
  - Bordure gris avec glow très subtil : `border: 1px solid rgba(255, 255, 255, 0.15)`
  - Texte avec shadow gris discret : `text-shadow: 0 0 10px rgba(255, 255, 255, 0.1)`
  - Padding généreux : `padding: 1rem 2.5rem`
  
- **Animations** :
  - Hover : scale + glow gris intensifié (très subtil)
  - Active : scale down légèrement
  - Ripple effect gris au clic
  - Loading spinner avec animation grise discrète

#### **D. Header/Navigation**
- **Style** :
  - Fond : `rgba(0, 0, 0, 0.85)` avec `backdrop-filter: blur(20px)`
  - Bordure bottom : gradient noir/gris animé très subtil
  - Ombre portée : `box-shadow: 0 4px 24px rgba(0, 0, 0, 0.8)`
  
- **Navigation items** :
  - Hover : glow gris très subtil + scale
  - Active : indicateur animé avec gradient noir/gris
  - Transition fluide : `transition: all 0.3s ease`

#### **E. Formulaires**
- **Inputs** :
  - Fond : `rgba(17, 17, 17, 0.5)`
  - Bordure : `2px solid rgba(255, 255, 255, 0.1)`
  - Focus : bordure gris clair + glow très subtil : `border-color: rgba(255, 255, 255, 0.2); box-shadow: 0 0 10px rgba(255, 255, 255, 0.05)`
  - Placeholder avec animation fade
  - Label flottant animé

#### **F. Panier**
- Badge compteur avec animation pulse grise discrète
- Items avec animation d'entrée (fade + slide)
- Total avec highlight gris très subtil
- Bouton checkout avec effet premium noir/gris

### 4. **EFFETS VISUELS AVANCÉS**

#### **Glassmorphism**
- Utiliser `backdrop-filter: blur(20px)` partout
- Transparence : `rgba(17, 17, 17, 0.6-0.8)` (noir/gris)
- Bordure subtile : `1px solid rgba(255, 255, 255, 0.1)`

#### **Neon Glow Effects (Gris très subtils)**
- Glow gris très discret sur les éléments interactifs : `rgba(255, 255, 255, 0.05-0.15)`
- Multiples couches de shadow noir pour profondeur
- Animation de glow pulsant gris très subtil sur les éléments importants

#### **Micro-interactions**
- Hover sur tous les éléments cliquables
- Feedback visuel immédiat
- Animations de transition fluides
- Loading states élégants

#### **Scroll Animations**
- Fade in au scroll (Intersection Observer)
- Parallax sur les images
- Sticky elements avec transformation
- Progress bar de scroll

### 5. **COMPOSANTS SPÉCIFIQUES**

#### **Hero Section**
- Titre avec animation typewriter ou fade in
- Sous-titre avec delay d'animation
- CTA avec effet magnétique (suivre la souris)
- Background avec particules grises animées (très subtiles)
- Gradient overlay noir/gris animé très discret

#### **Grille Produits**
- Animation stagger (apparition décalée)
- Hover effect sur toute la carte
- Image avec overlay gradient noir/gris au hover
- Badge "Nouveau" / "Promo" animé avec style gris discret

#### **Modal/Popup**
- Backdrop blur intense
- Animation d'entrée : scale + fade
- Fermeture avec animation reverse
- Contenu avec slide in

#### **Footer**
- Fond noir avec gradient gris très subtil
- Liens avec hover glow gris discret
- Social icons avec animation grise au hover
- Séparateur avec gradient noir/gris

### 6. **RESPONSIVE & PERFORMANCE**
- Mobile-first approach
- Animations réduites sur mobile (respect prefers-reduced-motion)
- Images optimisées avec lazy loading
- CSS optimisé (éviter les animations coûteuses)
- Utiliser `will-change` judicieusement

### 7. **DÉTAILS PREMIUM**

#### **Scrollbar personnalisée**
```css
::-webkit-scrollbar {
  width: 10px;
  background: #000000;
}
::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}
::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #2a2a2a, #3a3a3a);
}
```

#### **Sélection de texte**
```css
::selection {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}
```

#### **Focus visible**
- Outline avec glow gris très subtil : `outline: 2px solid rgba(255, 255, 255, 0.2); box-shadow: 0 0 10px rgba(255, 255, 255, 0.1)`
- Accessibilité maintenue

#### **Loading states**
- Skeleton loaders avec animation shimmer gris
- Spinners avec effet gris discret
- Progress indicators élégants noir/gris

### 8. **ANIMATIONS CSS CLÉS À CRÉER**

```css
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

@keyframes glow-pulse {
  0%, 100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.1); }
  50% { box-shadow: 0 0 40px rgba(255, 255, 255, 0.15); }
}

@keyframes gradient-shift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

@keyframes shimmer {
  0% { background-position: -1000px 0; }
  100% { background-position: 1000px 0; }
}

@keyframes slide-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### 9. **ÉLÉMENTS JAVASCRIPT RECOMMANDÉS**
- Intersection Observer pour animations au scroll
- GSAP (optionnel) pour animations complexes
- Effet parallaxe sur le scroll
- Cursor personnalisé (optionnel, style gris très discret)
- Animations de particules grises (Three.js ou CSS, très subtiles)

### 10. **CHECKLIST DE RÉALISATION**

- [ ] Palette de couleurs noir/gris monochrome appliquée partout
- [ ] Typographie moderne avec Google Fonts
- [ ] Background animé avec particules/gradients gris très subtils
- [ ] Cartes produits avec glassmorphism + néon gris discret
- [ ] Boutons avec gradient noir/gris animé + glow très subtil
- [ ] Header avec backdrop blur + bordure grise animée
- [ ] Navigation avec hover effects gris discrets
- [ ] Formulaires avec focus states premium gris
- [ ] Panier avec animations fluides
- [ ] Scrollbar personnalisée noir/gris
- [ ] Animations au scroll (fade in)
- [ ] Micro-interactions partout (gris subtils)
- [ ] Responsive parfaitement optimisé
- [ ] Performance vérifiée (60fps)
- [ ] Accessibilité maintenue

## 🚀 RÉSULTAT ATTENDU

Un site e-commerce avec un design **ultra-moderne**, **dynamique** et **premium** qui :
- Impressionne visuellement dès le premier regard avec un style sobre et élégant
- Offre une expérience utilisateur fluide et engageante
- Reste performant et accessible
- Utilise un thème **noir/gris monochrome** avec des accents néon gris très subtils
- Intègre de nombreuses animations subtiles mais impactantes (toutes en tons gris/noir)
- Donne une sensation de qualité, de modernité et de sophistication minimaliste
- **PAS de couleurs vives** - uniquement des nuances de noir, gris foncé, gris moyen avec des reflets blancs très discrets

## 📝 NOTES IMPORTANTES

- **Cohérence** : Tous les éléments doivent suivre le même système de design
- **Hiérarchie** : Les éléments importants doivent attirer l'attention naturellement
- **Performance** : Privilégier les animations CSS aux animations JS lourdes
- **Accessibilité** : Respecter `prefers-reduced-motion` et maintenir les contrastes
- **Mobile** : Adapter les animations pour les écrans tactiles

---

**Ce prompt doit servir de guide complet pour transformer le site en une expérience visuelle premium et moderne avec un thème sombre monochrome (noir/gris) dynamique et sobre.**

## ⚠️ RÈGLE ABSOLUE : PALETTE MONOCHROME
- **AUCUNE couleur vive** (pas de rose, violet, bleu, cyan, etc.)
- **UNIQUEMENT** des nuances de noir (`#000000`, `#0a0a0a`, `#111111`) et gris (`#1a1a1a`, `#2a2a2a`, `#3a3a3a`, `#444444`)
- Les effets "néon" sont en réalité des **reflets gris/blanc très subtils** (`rgba(255, 255, 255, 0.05)` à `rgba(255, 255, 255, 0.2)`)
- Les dégradés sont **noir → gris foncé → gris moyen → gris foncé → noir**
- Style **minimaliste, sobre, élégant** mais avec beaucoup de dynamisme et d'animations subtiles

