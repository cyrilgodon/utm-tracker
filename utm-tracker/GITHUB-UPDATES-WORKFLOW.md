# 🚀 Workflow GitHub - Mises à Jour Automatiques WordPress

## 📋 Vue d'ensemble

Ce plugin utilise **Plugin Update Checker** pour détecter automatiquement les mises à jour depuis GitHub et les afficher dans WordPress Admin → Extensions.

## ✅ Configuration Actuelle

- **Repository GitHub** : `https://github.com/cyrilgodon/ai-engine-elevatio`
- **Branche surveillée** : `main`
- **Système de versioning** : Semantic Versioning (SemVer)
- **Plugin Update Checker** : v5.6

## 🔄 Workflow de Release

### Étape 1 : Préparer la nouvelle version

1. **Mettre à jour la version dans le fichier principal**
```php
// Dans ai-engine-elevatio.php
define( 'EAI_VERSION', '2.4.3' ); // Incrémenter
```

2. **Mettre à jour le CHANGELOG.md**
```markdown
## [2.4.3] - 2025-01-XX
### Added
- Nouvelle fonctionnalité X
### Fixed
- Correction du bug Y
```

3. **Commiter les changements**
```bash
git add .
git commit -m "chore: bump version to 2.4.3"
git push origin main
```

### Étape 2 : Créer une Release GitHub

1. **Créer un tag Git**
```bash
git tag v2.4.3
git push origin v2.4.3
```

2. **Sur GitHub.com**
   - Aller dans **Releases** → **Create a new release**
   - Sélectionner le tag `v2.4.3`
   - Titre : `Version 2.4.3`
   - Description : Copier-coller le contenu du CHANGELOG pour cette version
   - Cliquer sur **Publish release**

### Étape 3 : WordPress détecte automatiquement

- WordPress vérifie GitHub toutes les **12 heures** (par défaut)
- La mise à jour apparaît dans **Extensions** avec le bouton "Mettre à jour"
- L'admin peut installer en 1 clic

## 🔧 Forcer la vérification manuelle

Pour tester immédiatement sans attendre 12h :

1. Dans WordPress Admin : **Tableau de bord** → **Mises à jour**
2. Cliquer sur **Vérifier à nouveau**
3. Ou via WP-CLI :
```bash
wp transient delete update_plugins
```

## 📦 Format de Version (SemVer)

```
MAJOR.MINOR.PATCH
  |     |     |
  |     |     └─ Corrections de bugs (2.4.2 → 2.4.3)
  |     └─────── Nouvelles fonctionnalités compatibles (2.4.0 → 2.5.0)
  └───────────── Changements incompatibles (2.0.0 → 3.0.0)
```

### Exemples :
- `2.4.3` → Correction de bug
- `2.5.0` → Nouvelle fonctionnalité
- `3.0.0` → Refonte majeure avec breaking changes

## ⚠️ Important

1. **Le dossier `vendor/` est exclu de Git** (dans `.gitignore`)
2. **WordPress installe automatiquement les dépendances Composer** au chargement du plugin
3. **Toujours tester en local avant de créer une release**
4. **Ne jamais supprimer un tag/release GitHub** (risque de casser les installations existantes)

## 🐛 Dépannage

### La mise à jour n'apparaît pas dans WordPress

1. Vérifier que le tag GitHub est bien créé
2. Vérifier que la release GitHub est publiée (pas en draft)
3. Forcer la vérification : `wp transient delete update_plugins`
4. Vérifier les logs WordPress : `wp-content/debug.log`

### Erreur lors de la mise à jour

1. Vérifier que le plugin est bien dans un repo GitHub public
2. Vérifier que la branche `main` existe (pas `master`)
3. Si repo privé, ajouter un token d'authentification dans le code :
```php
$eaiUpdateChecker->setAuthentication('github_pat_xxx');
```

## 📚 Ressources

- **Plugin Update Checker** : https://github.com/YahnisElsts/plugin-update-checker
- **Semantic Versioning** : https://semver.org/
- **GitHub Releases** : https://docs.github.com/en/repositories/releasing-projects-on-github

---

**✅ Configuration terminée** - Les mises à jour automatiques sont actives !

