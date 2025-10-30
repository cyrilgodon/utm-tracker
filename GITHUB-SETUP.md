# 🚀 Setup GitHub pour UTM Tracker

## Option 1 : Via GitHub CLI (Recommandé)

Si tu as GitHub CLI installé :

```bash
cd "c:\Users\cyril\OneDrive - ZEOLITOP\Elevatio\Projets\Développements wordpress\plugin utm-tracker"

# Créer le repo sur GitHub et pousser
gh repo create utm-tracker --public --source=. --remote=origin --push

# Ou si tu veux un repo privé
gh repo create utm-tracker --private --source=. --remote=origin --push
```

---

## Option 2 : Via l'Interface GitHub (Manuel)

### Étape 1 : Créer le Repo sur GitHub

1. Va sur https://github.com/new
2. **Repository name** : `utm-tracker`
3. **Description** : `WordPress plugin for UTM tracking and automatic user tagging without cookies`
4. **Visibility** : Public ou Private
5. ❌ **Ne PAS initialiser** avec README, .gitignore, ou license (déjà créés localement)
6. Cliquer sur **Create repository**

### Étape 2 : Lier le Repo Local et Pousser

GitHub va te donner des commandes. Utilise celles-ci :

```bash
cd "c:\Users\cyril\OneDrive - ZEOLITOP\Elevatio\Projets\Développements wordpress\plugin utm-tracker"

# Ajouter le remote GitHub (remplace TON_USERNAME par ton username GitHub)
git remote add origin https://github.com/TON_USERNAME/utm-tracker.git

# Renommer la branche en main (convention GitHub)
git branch -M main

# Pousser vers GitHub
git push -u origin main
```

---

## Option 3 : Via SSH (Si configuré)

```bash
cd "c:\Users\cyril\OneDrive - ZEOLITOP\Elevatio\Projets\Développements wordpress\plugin utm-tracker"

# Ajouter le remote via SSH
git remote add origin git@github.com:TON_USERNAME/utm-tracker.git

# Renommer la branche en main
git branch -M main

# Pousser vers GitHub
git push -u origin main
```

---

## Vérification

Après avoir poussé, vérifie sur GitHub :

```
https://github.com/TON_USERNAME/utm-tracker
```

Tu devrais voir :
- ✅ 14 fichiers
- ✅ README.md affiché sur la page d'accueil
- ✅ 3874 lignes de code
- ✅ Commit initial avec message détaillé

---

## Topics Recommandés pour GitHub

Ajoute ces topics à ton repo GitHub (Settings → Topics) :

```
wordpress
wordpress-plugin
utm-tracking
marketing-attribution
user-tagging
analytics
php
mysql
session-tracking
gdpr-compliant
```

---

## GitHub Description Suggérée

```
🎯 WordPress plugin for UTM tracking and automatic user tagging without cookies. 
Track marketing campaigns, match UTM parameters to configured campaigns, 
and automatically apply tags to users. GDPR-friendly (session-based, no cookies). 
Complete API, detailed documentation, 20+ campaign examples.
```

---

## README Badge (Optionnel)

Ajoute en haut du README.md :

```markdown
![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPL%20v2-blue)
```

---

## Prochaines Commandes Git Utiles

```bash
# Voir le statut
git status

# Voir l'historique
git log --oneline --graph --all

# Créer une nouvelle branche pour v1.1
git checkout -b feature/admin-interface

# Pousser une nouvelle branche
git push -u origin feature/admin-interface

# Merger une branche
git checkout main
git merge feature/admin-interface
git push
```

---

## Structure GitHub Recommandée

### Issues à Créer (Roadmap v1.1)

1. **Admin Interface** : Minimal admin interface for campaign management
2. **Statistics Dashboard** : Basic stats page with charts
3. **CSV Export** : Export events and campaigns to CSV
4. **Campaign CRUD** : Create/Edit/Delete campaigns via WordPress admin

### Labels à Créer

- `enhancement` : Nouvelles fonctionnalités
- `bug` : Bugs à corriger
- `documentation` : Améliorations de doc
- `v1.1` : Milestone v1.1
- `v2.0` : Milestone v2.0
- `good first issue` : Pour contributeurs

### Milestones

- **v1.1.0** : Admin interface + stats
- **v2.0.0** : Full analytics dashboard
- **v3.0.0** : Performance & scalability

---

**Une fois poussé sur GitHub, supprime ce fichier (il n'est pas commité) :**

```bash
rm GITHUB-SETUP.md
```

