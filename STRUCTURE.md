# 📁 Structure du Plugin UTM Tracker

**Version** : 1.0.0  
**Date** : 2025-10-30

---

## 📦 Arborescence Complète

```
plugin utm-tracker/
│
├── 📄 README.md                           # Documentation principale (⭐ LIRE EN PREMIER)
├── 📄 QUICK-START.md                      # Guide de démarrage rapide
├── 📄 CHANGELOG.md                        # Historique des versions
├── 📄 STRUCTURE.md                        # Ce fichier
│
└── 📁 utm-tracker/                        # Plugin WordPress (à copier dans wp-content/plugins/)
    │
    ├── 📄 utm-tracker.php                 # 🔌 Fichier principal du plugin
    │                                      #    - Headers WordPress
    │                                      #    - Classe UTM_Tracker (singleton)
    │                                      #    - Initialisation des composants
    │                                      #    - Gestion session PHP
    │
    ├── 📁 includes/                       # Classes PHP Core
    │   │
    │   ├── 📄 class-db-installer.php      # 🗄️ Installation des tables SQL
    │   │                                  #    - create_tables()
    │   │                                  #    - drop_tables()
    │   │                                  #    - tables_exist()
    │   │                                  #    - needs_update()
    │   │
    │   ├── 📄 class-utm-capture.php       # 📊 Capture des UTM
    │   │                                  #    - capture_utm_params()
    │   │                                  #    - log_utm_event()
    │   │                                  #    - process_user_registration()
    │   │                                  #    - Stockage en session PHP
    │   │
    │   ├── 📄 class-utm-matcher.php       # 🎯 Matching Campagnes
    │   │                                  #    - find_matching_campaign()
    │   │                                  #    - get_campaign_by_id()
    │   │                                  #    - get_active_campaigns()
    │   │                                  #    - get_campaign_stats()
    │   │                                  #    - campaign_exists()
    │   │
    │   ├── 📄 class-tag-applicator.php    # 🏷️ Application des Tags
    │   │                                  #    - apply_tags_to_user()
    │   │                                  #    - get_user_tags()
    │   │                                  #    - user_has_tag()
    │   │                                  #    - get_users_by_tag()
    │   │                                  #    - remove_tag_from_user()
    │   │
    │   └── 📄 functions-helpers.php       # 🛠️ Fonctions Helper Globales
    │                                      #    - utm_add_campaign()
    │                                      #    - utm_get_campaigns()
    │                                      #    - utm_get_user_tags()
    │                                      #    - utm_user_has_tag()
    │                                      #    - utm_generate_url()
    │                                      #    ... 20+ fonctions
    │
    ├── 📁 docs/                           # Documentation
    │   │
    │   └── 📄 INSTALLATION.md             # 📥 Guide d'installation détaillé
    │                                      #    - Prérequis
    │                                      #    - Installation pas à pas
    │                                      #    - Configuration initiale
    │                                      #    - Dépannage
    │                                      #    - Checklist post-install
    │
    └── 📁 examples/                       # Exemples & Scripts SQL
        │
        ├── 📄 sample-campaigns.sql        # 📊 20+ Exemples de Campagnes
        │                                  #    - Google Ads
        │                                  #    - Facebook / Meta
        │                                  #    - LinkedIn
        │                                  #    - Email Marketing
        │                                  #    - Retargeting
        │                                  #    - Webinaires / QR Codes
        │                                  #    - Requêtes utiles
        │
        └── 📄 create-tables.sql           # 🗄️ Script de Création des Tables
                                           #    - wp_utm_campaigns
                                           #    - wp_user_tags
                                           #    - wp_utm_events
                                           #    - Requêtes de vérification
```

---

## 📊 Statistiques du Plugin

| Catégorie | Quantité |
|-----------|----------|
| **Fichiers PHP** | 5 classes + 1 helper |
| **Lignes de code PHP** | ~1,500 |
| **Fonctions helper** | 20+ |
| **Tables SQL** | 3 |
| **Fichiers documentation** | 5 (MD + SQL) |
| **Lignes documentation** | ~2,000 |
| **Exemples campagnes** | 20+ |

---

## 🗄️ Base de Données

### Tables Créées Automatiquement

#### 1. `wp_utm_campaigns` (Configuration)
```
Colonnes : 11
- id, name, utm_source, utm_medium, utm_campaign
- utm_content, utm_term, user_tags (JSON)
- status, created_at, updated_at

Indexes :
- PRIMARY KEY (id)
- UNIQUE (utm_source, utm_medium, utm_campaign)
- KEY (status)
```

#### 2. `wp_user_tags` (Tags Utilisateur)
```
Colonnes : 5
- id, user_id, tag_slug, campaign_id, applied_at

Indexes :
- PRIMARY KEY (id)
- UNIQUE (user_id, tag_slug)
- KEY (user_id)
- KEY (tag_slug)
- KEY (campaign_id)
```

#### 3. `wp_utm_events` (Historique)
```
Colonnes : 12
- id, user_id, session_id, campaign_id
- utm_source, utm_medium, utm_campaign, utm_content, utm_term
- referrer, landing_page, created_at

Indexes :
- PRIMARY KEY (id)
- KEY (user_id, created_at)
- KEY (session_id)
- KEY (campaign_id, created_at)
```

---

## 🔌 Intégration WordPress

### Hooks Utilisés

| Hook | Priorité | Fonction | Description |
|------|----------|----------|-------------|
| `plugins_loaded` | 1 | `start_session()` | Démarre la session PHP |
| `template_redirect` | 1 | `capture_utm_params()` | Capture les UTM de l'URL |
| `user_register` | 10 | `process_user_registration()` | Applique les tags |
| `wp_logout` | - | `destroy_session()` | Nettoie la session |
| `wp_login` | - | `destroy_session()` | Nettoie la session |

### Hooks Custom Fournis

```php
// Modifier un événement avant enregistrement
apply_filters( 'utm_tracker_before_save_event', $event_data );

// Action après matching de campagne
do_action( 'utm_tracker_campaign_matched', $campaign_id, $utm_params );

// Action après application des tags
do_action( 'utm_tracker_tags_applied', $user_id, $tags, $campaign_id );

// Modifier les tags avant application
apply_filters( 'utm_tracker_campaign_tags', $tag_ids, $campaign_id, $user_id );
```

---

## 📚 Documentation Disponible

### 1. README.md (Principal)
- Description complète du plugin
- Fonctionnalités
- Installation (vue d'ensemble)
- Configuration
- API PHP complète avec exemples
- Structure base de données
- Conformité RGPD
- Dépannage
- Roadmap

### 2. QUICK-START.md (Démarrage Rapide)
- Installation en 3 étapes
- Tests rapides
- Exemples d'utilisation
- Cas d'usage typiques
- Checklist post-installation

### 3. INSTALLATION.md (Détaillé)
- Prérequis complets
- Installation manuelle pas à pas
- Installation via WP-CLI
- Configuration avancée
- Import de campagnes en masse
- Dépannage détaillé
- Désinstallation complète

### 4. CHANGELOG.md (Historique)
- Version 1.0.0 détaillée
- Roadmap v1.1, v2.0, v3.0
- Notes de version
- Support & contributions

### 5. STRUCTURE.md (Ce Fichier)
- Arborescence complète
- Description de chaque fichier
- Statistiques
- Tables SQL
- Hooks WordPress

---

## 🎯 Workflows Typiques

### Workflow 1 : Nouvelle Campagne Google Ads

```sql
-- 1. Créer la campagne en SQL
INSERT INTO wp_utm_campaigns (name, utm_source, utm_medium, utm_campaign, user_tags, status)
VALUES ('Google Ads Q1', 'google', 'cpc', 'q1_2025', '["lead_google", "q1"]', 'active');
```

```
-- 2. Générer l'URL trackée
https://votresite.com/?utm_source=google&utm_medium=cpc&utm_campaign=q1_2025

-- 3. Utilisateur visite → UTM capturés en session
-- 4. Utilisateur s'inscrit → Tags appliqués automatiquement
```

```php
// 5. Vérifier les tags appliqués
$tags = utm_get_user_tags( $user_id );
// ['lead_google', 'q1']
```

### Workflow 2 : Segmentation Email

```php
// Récupérer tous les leads Google pour email ciblé
$google_users = utm_get_users_by_tag( 'lead_google' );

foreach ( $google_users as $user_id ) {
    $user = get_user_by( 'id', $user_id );
    // Envoyer email personnalisé
    wp_mail( $user->user_email, 'Offre spéciale Google', $content );
}
```

### Workflow 3 : Analytics Campagne

```php
// Obtenir les stats d'une campagne
$campaign = utm_get_campaign( 42 );
$stats = utm_get_campaign_stats( 42 );

echo "{$campaign->name} :\n";
echo "Visites : {$stats['visits']}\n";
echo "Utilisateurs : {$stats['users']}\n";
echo "Conversions : {$stats['conversions']}\n";
echo "Taux : {$stats['conversion_rate']}%\n";
```

---

## 🚀 Démarrage Rapide (Résumé)

### 1. Installation
```bash
cp -r utm-tracker /path/to/wordpress/wp-content/plugins/
```

### 2. Activation
WordPress Admin → Extensions → Activer "UTM Tracker"

### 3. Première Campagne
```sql
INSERT INTO wp_utm_campaigns (name, utm_source, utm_medium, utm_campaign, user_tags, status)
VALUES ('Test', 'google', 'cpc', 'test', '["test"]', 'active');
```

### 4. Test
```
https://votresite.com/?utm_source=google&utm_medium=cpc&utm_campaign=test
```

### 5. Vérification
```php
$tags = utm_get_user_tags( $user_id );
```

---

## 📞 Support

- **Documentation** : Lire README.md en priorité
- **Installation** : Consulter INSTALLATION.md
- **Exemples** : Voir sample-campaigns.sql
- **Contact** : Équipe Elevatio

---

**Plugin développé avec ❤️ pour le tracking marketing intelligent**

**Version** : 1.0.0  
**Auteur** : Elevatio  
**Licence** : GPL v2 or later

