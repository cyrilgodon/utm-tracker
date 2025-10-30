# 📊 UTM Tracker - Plugin WordPress

**Version:** 1.0.0  
**Auteur:** Elevatio  
**Licence:** GPL v2 or later

## 🎯 Description

**UTM Tracker** est un plugin WordPress permettant de capturer les paramètres UTM des visiteurs et d'attribuer automatiquement des **tags utilisateur** basés sur des campagnes marketing configurées.

### ✨ Fonctionnalités Principales

- ✅ **Capture UTM sans cookies** : Utilise la session PHP pour un tracking simple et conforme RGPD
- ✅ **Matching automatique** : Associe les UTM à des campagnes prédéfinies
- ✅ **Tags utilisateur** : Attribution automatique de tags lors de l'inscription
- ✅ **Historique complet** : Enregistre tous les événements UTM en base de données
- ✅ **API simple** : Fonctions helper pour interroger et gérer les campagnes/tags
- ✅ **Pas d'interface admin** (MVP) : Configuration directe en base de données

---

## 📦 Structure du Plugin

```
utm-tracker/
├── utm-tracker.php              # Fichier principal du plugin
├── includes/
│   ├── class-db-installer.php   # Installation des tables SQL
│   ├── class-utm-capture.php    # Capture des UTM en session
│   ├── class-utm-matcher.php    # Matching UTM → Campagne
│   ├── class-tag-applicator.php # Application des tags
│   └── functions-helpers.php    # Fonctions helper globales
├── docs/
│   └── INSTALLATION.md          # Guide d'installation
├── examples/
│   └── sample-campaigns.sql     # Exemples de campagnes
└── README.md                    # Ce fichier
```

---

## 🚀 Installation

### Prérequis

- **WordPress** : 5.8 ou supérieur
- **PHP** : 7.4 ou supérieur
- **Base de données** : MySQL 5.6+ ou MariaDB 10.0+

### Étapes d'Installation

1. **Télécharger** le plugin dans `wp-content/plugins/utm-tracker/`
2. **Activer** le plugin depuis l'admin WordPress
3. **Vérifier** que les 3 tables ont été créées :
   - `wp_utm_campaigns` : Configuration des campagnes
   - `wp_user_tags` : Tags utilisateur
   - `wp_utm_events` : Historique des événements

4. **Ajouter des campagnes** (voir section Configuration)

📖 **Guide détaillé** : [docs/INSTALLATION.md](docs/INSTALLATION.md)

---

## ⚙️ Configuration

### 1. Créer une Campagne

Insérez directement en base de données :

```sql
INSERT INTO wp_utm_campaigns (
    name,
    utm_source,
    utm_medium,
    utm_campaign,
    user_tags,
    status
) VALUES (
    'Google Ads Coaching Q1 2025',
    'google',
    'cpc',
    'coaching_q1_2025',
    '["lead_google", "coaching", "q1_2025"]',
    'active'
);
```

**Ou via PHP** :

```php
utm_add_campaign([
    'name' => 'Google Ads Coaching Q1 2025',
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'coaching_q1_2025',
    'user_tags' => ['lead_google', 'coaching', 'q1_2025'],
    'status' => 'active'
]);
```

### 2. Tester la Capture

Visitez votre site avec des paramètres UTM :

```
https://votresite.com/?utm_source=google&utm_medium=cpc&utm_campaign=coaching_q1_2025
```

Les UTM sont capturés en session PHP et enregistrés dans `wp_utm_events`.

### 3. Inscription Utilisateur

Lors de l'inscription d'un utilisateur, le plugin :
1. Récupère les UTM de la session
2. Matche la campagne correspondante
3. Applique automatiquement les tags configurés dans `wp_user_tags`

---

## 📚 Utilisation - API PHP

### Gestion des Campagnes

```php
// Ajouter une campagne
$campaign_id = utm_add_campaign([
    'name' => 'Facebook Lead Gen',
    'utm_source' => 'facebook',
    'utm_medium' => 'paid',
    'utm_campaign' => 'lead_gen_2025',
    'user_tags' => ['lead_facebook', 'premium'],
    'status' => 'active'
]);

// Obtenir une campagne
$campaign = utm_get_campaign( $campaign_id );

// Lister toutes les campagnes actives
$campaigns = utm_get_campaigns( 'active' );

// Mettre à jour une campagne
utm_update_campaign( $campaign_id, [
    'status' => 'paused'
]);

// Supprimer une campagne
utm_delete_campaign( $campaign_id );

// Obtenir les statistiques d'une campagne
$stats = utm_get_campaign_stats( $campaign_id );
// ['visits' => 234, 'users' => 42, 'conversions' => 8, 'conversion_rate' => 19.05]
```

### Gestion des Tags Utilisateur

```php
// Obtenir les tags d'un utilisateur
$tags = utm_get_user_tags( $user_id );
// ['lead_google', 'coaching', 'q1_2025']

// Vérifier si un utilisateur a un tag
if ( utm_user_has_tag( $user_id, 'lead_google' ) ) {
    // L'utilisateur vient de Google
}

// Ajouter un tag manuellement
utm_add_user_tag( $user_id, 'vip_customer', $campaign_id );

// Retirer un tag
utm_remove_user_tag( $user_id, 'trial' );

// Obtenir tous les utilisateurs ayant un tag
$user_ids = utm_get_users_by_tag( 'coaching' );

// Compter les utilisateurs avec un tag
$count = utm_count_users_by_tag( 'lead_google' );

// Lister tous les tags avec compteurs
$all_tags = utm_get_all_tags();
// [
//   { tag_slug: 'coaching', user_count: 35 },
//   { tag_slug: 'lead_google', user_count: 42 },
// ]
```

### Données de Session

```php
// Obtenir les données UTM de la session courante
$utm_data = utm_get_session_data();
// [
//   'utm_source' => 'google',
//   'utm_medium' => 'cpc',
//   'utm_campaign' => 'coaching_q1_2025',
//   'referrer' => 'https://google.com',
//   'landing_page' => 'https://votresite.com/landing',
//   'timestamp' => '2025-10-30 14:32:15'
// ]
```

### Utilitaires

```php
// Générer une URL avec UTM
$url = utm_generate_url( 'https://votresite.com/landing', [
    'utm_source' => 'email',
    'utm_medium' => 'newsletter',
    'utm_campaign' => 'monthly_digest'
]);
// https://votresite.com/landing?utm_source=email&utm_medium=newsletter&utm_campaign=monthly_digest
```

---

## 🗄️ Structure de la Base de Données

### Table : `wp_utm_campaigns`

Configuration des campagnes marketing.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | ID unique (auto-increment) |
| `name` | VARCHAR(255) | Nom de la campagne |
| `utm_source` | VARCHAR(255) | Source UTM (ex: google, facebook) |
| `utm_medium` | VARCHAR(255) | Medium UTM (ex: cpc, organic) |
| `utm_campaign` | VARCHAR(255) | Campagne UTM (ex: coaching_q1) |
| `utm_content` | VARCHAR(255) | Contenu UTM (optionnel) |
| `utm_term` | VARCHAR(255) | Terme UTM (optionnel) |
| `user_tags` | TEXT | JSON array des tags à appliquer |
| `status` | ENUM | `active`, `paused`, `archived` |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de mise à jour |

### Table : `wp_user_tags`

Tags appliqués aux utilisateurs.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | ID unique (auto-increment) |
| `user_id` | BIGINT | ID de l'utilisateur |
| `tag_slug` | VARCHAR(100) | Slug du tag |
| `campaign_id` | BIGINT | ID de la campagne source (optionnel) |
| `applied_at` | DATETIME | Date d'application |

### Table : `wp_utm_events`

Historique des événements UTM.

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | BIGINT | ID unique (auto-increment) |
| `user_id` | BIGINT | ID utilisateur (NULL si anonyme) |
| `session_id` | VARCHAR(64) | ID de session PHP |
| `campaign_id` | BIGINT | ID campagne matchée (optionnel) |
| `utm_source` | VARCHAR(255) | Source UTM |
| `utm_medium` | VARCHAR(255) | Medium UTM |
| `utm_campaign` | VARCHAR(255) | Campagne UTM |
| `utm_content` | VARCHAR(255) | Contenu UTM |
| `utm_term` | VARCHAR(255) | Terme UTM |
| `referrer` | TEXT | URL du referrer |
| `landing_page` | TEXT | URL de la landing page |
| `created_at` | DATETIME | Date de capture |

---

## 🔒 Conformité RGPD

### Pas de Cookies = Pas de Consentement

Le plugin **n'utilise PAS de cookies**, seulement la **session PHP native**. Les sessions PHP ne sont généralement **pas soumises au consentement RGPD** car :

1. Elles sont **techniques et nécessaires** au fonctionnement du site
2. Elles ne permettent **pas de tracking cross-domaines**
3. Elles sont **automatiquement détruites** à la fermeture du navigateur

### Données Personnelles

Les données stockées sont :
- **UTM** : paramètres marketing (non personnels)
- **Referrer** : URL de provenance (peut contenir des infos)
- **Session ID** : identifiant temporaire (non nominatif)
- **User ID** : lié uniquement après inscription (consentement implicite)

### Recommandations

Si vous souhaitez être encore plus strict RGPD :
1. Ajoutez une mention dans votre **Politique de Confidentialité**
2. Permettez l'**export des données UTM** via l'exporteur WordPress
3. Purger automatiquement les événements > 13 mois

---

## 🛠️ Développement & Hooks

### Hooks Disponibles

```php
// Avant enregistrement d'un événement UTM
apply_filters( 'utm_tracker_before_save_event', $event_data );

// Après matching d'une campagne
do_action( 'utm_tracker_campaign_matched', $campaign_id, $utm_params );

// Après application des tags
do_action( 'utm_tracker_tags_applied', $user_id, $tag_ids, $campaign_id );

// Modifier les tags à appliquer
apply_filters( 'utm_tracker_campaign_tags', $tag_ids, $campaign_id, $user_id );
```

### Exemple d'Extension

```php
// Envoyer un email à l'admin quand un lead Google s'inscrit
add_action( 'utm_tracker_tags_applied', function( $user_id, $tags, $campaign_id ) {
    if ( in_array( 'lead_google', $tags ) ) {
        $user = get_user_by( 'id', $user_id );
        wp_mail(
            get_option( 'admin_email' ),
            'Nouveau lead Google !',
            'Utilisateur : ' . $user->user_email
        );
    }
}, 10, 3 );
```

---

## 📈 Roadmap

### Version 1.1 (Prévue)
- ✅ Interface admin minimaliste (liste campagnes)
- ✅ Page de statistiques basique
- ✅ Export CSV des événements

### Version 2.0 (Future)
- 📊 Dashboard analytics complet avec graphiques
- 🔗 Générateur d'URL UTM avec QR Code
- 📧 Notifications email (nouveaux leads)
- 🎯 Attribution multi-touch
- 🔌 Intégration WooCommerce

---

## 🐛 Dépannage

### Les UTM ne sont pas capturés

1. Vérifier que la **session PHP** démarre correctement
2. Activer `WP_DEBUG` et vérifier les logs (`wp-content/debug.log`)
3. Tester avec : `<?php var_dump( $_SESSION ); ?>`

### Les tags ne sont pas appliqués

1. Vérifier qu'une **campagne active** correspond aux UTM
2. Vérifier le **format JSON** des `user_tags` dans la campagne
3. Consulter les logs avec `WP_DEBUG` activé

### Les tables ne sont pas créées

1. **Désactiver et réactiver** le plugin
2. Vérifier les **permissions MySQL** de l'utilisateur WordPress
3. Exécuter manuellement le SQL (voir `examples/create-tables.sql`)

---

## 📞 Support

- **Documentation** : [docs/](docs/)
- **Exemples** : [examples/](examples/)
- **Issues** : Contactez l'équipe Elevatio

---

## 📄 Licence

Ce plugin est distribué sous licence **GPL v2 or later**.

---

## 👨‍💻 Développé par Elevatio

Plugin développé avec ❤️ pour le tracking marketing et l'attribution de campagnes.

**Version** : 1.0.0  
**Date** : 2025-10-30

