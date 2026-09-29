# Éveil Intérieur™

Formation spirituelle en ligne — Retrouvez le calme intérieur en 6 semaines, avec un vrai coaching pour vous accompagner.

Design system : **Phlox 2.11.1** (police Raleway, couleur primaire `#1bb0ce`, boutons carrés uppercase, backgrounds pleine taille).

## Structure

| Fichier | Description |
|---------|-------------|
| `index.html` | Landing page : hero, problème, coût, méthode, slider modules, bonus, coaching, témoignages, pourquoi/pour qui, garantie, FAQ, formulaire |
| `login.html` | Page d'accès membre — inscription + démarrage automatique de la formation |
| `member-area.html` | Dashboard membre — barre de progression + 6 modules verrouillables |
| `lesson.html` | Template de module — lecteur audio, enseignement, navigation entre modules, coaching |
| `style.css` | Design system Phlox + composants Éveil Intérieur™ (module cards, progress bar, auth form) |
| `script.js` | 5 plugins JS : Slider, Accordion (FAQ), AudioPlayer, EmailForm, **MemberArea** (déblocage 7j/1module) + LoginForm |
| `images/` | 14 images : logo SVG, favicon, hero-bg, features, cours, témoignages |
| `ambiance2.mp3` | Son naturel — forêt/pluie (15 min) |
| `ambiance3.mp3` | Son naturel — océan/vagues (10 min) |

## Système de déblocage des modules

- L'inscription sur `login.html` enregistre la date de début dans `localStorage`
- **Module 1** : débloqué immédiatement
- **Module 2** : débloqué après 7 jours
- **Module 3** : débloqué après 14 jours
- **Module 4** : débloqué après 21 jours
- **Module 5** : débloqué après 28 jours
- **Module 6** : débloqué après 35 jours

La barre de progression sur `member-area.html` montre l'avancement en temps réel.

## Navigation

1. **Banner** — hero avec titre et CTA
2. **3 Columns** — bonus + coaching + modules
3. **Container** — callout / garantie
4. **2 Columns** — contenu module
5. **Slider** — modules du programme + témoignages
6. **Audio Player Plugin** — lecteur personnalisé HTML5
7. **Accordion** — FAQ interactive
8. **Email Capture Form** — formulaire d'abonnement
9. **Module Cards** — grille de modules avec état verrouillé/débloqué
10. **Who Cards** — pourquoi/pour qui (✓/✕)

## Navigation

```
index.html → login.html → member-area.html → lesson.html?m=N
```

## Utilisation standalone

```bash
npx serve .
```

Ensuite ouvrez `index.html` puis cliquez sur "Commencer".

## Import TinyPages

1. Copiez chaque bloc `<section>` dans l'éditeur TinyPages
2. Utilisez **Custom Code (Pro)** pour coller les plugins JS
3. Utilisez **Reusable Templates** pour les sections récurrentes
