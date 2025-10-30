# 🚀 Quick Start - UTM Tracker Plugin

**Plugin créé avec succès ! 🎉**

## 📦 Contenu du Plugin

Le plugin **UTM Tracker v1.0.0** est maintenant prêt à l'emploi. Voici ce qui a été créé :

### Structure Complète

```
plugin utm-tracker/
├── README.md                      # Documentation principale ⭐
├── QUICK-START.md                 # Ce fichier
│
├── utm-tracker/                   # Plugin WordPress
│   ├── utm-tracker.php            # Fichier principal du plugin
│   │
│   ├── includes/                  # Classes Core
│   │   ├── class-db-installer.php     # Installation des tables SQL
│   │   ├── class-utm-capture.php      # Capture UTM en session PHP
│   │   ├── class-utm-matcher.php      # Matching UTM → Campagne
│   │   ├── class-tag-applicator.php   # Application des tags
│   │   └── functions-helpers.php      # Fonctions helper globales
│   │
│   ├── docs/                      # Documentation
│   │   └── INSTALLATION.md            # Guide d'installation détaillé
│   │
│   └── examples/                  # Exemples
│       └── sample-campaigns.sql       # 20+ exemples de campagnes
```

---

## ⚡ Installation en 3 Étapes

### 1. Copier le Plugin

```bash
# Copier le dossier utm-tracker/ dans wp-content/plugins/
cp -r "plugin utm-tracker/utm-tracker" "/path/to/wordpress/wp-content/plugins/"
```

### 2. Activer le Plugin

Dans l'admin WordPress :
- **Extensions** > **Extensions installées**
- Trouver **UTM Tracker**
- Cliquer sur **Activer**

✅ Les 3 tables seront créées automatiquement :
- `wp_utm_campaigns`
- `wp_user_tags`
- `wp_utm_events`

### 3. Ajouter une Campagne de Test

Via **phpMyAdmin** ou **MySQL CLI** :

```sql
INSERT INTO wp_utm_campaigns (
    name,
    utm_source,
    utm_medium,
    utm_campaign,
    user_tags,
    status
) VALUES (
    'Test Campaign',
    'google',
    'cpc',
    'test_campaign',
    '["test_tag", "demo"]',
    'active'
);
```

---

## 🧪 Tester le Plugin

### Test 1 : Capture UTM

Visitez votre site avec des paramètres UTM :

```
https://votresite.com/?utm_source=google&utm_medium=cpc&utm_campaign=test_campaign
```

Vérifiez dans la base de données :

```sql
SELECT * FROM wp_utm_events ORDER BY created_at DESC LIMIT 1;
```

✅ Vous devriez voir un nouvel événement avec vos UTM.

### Test 2 : Application de Tags

1. **Inscrivez un utilisateur** de test
2. **Vérifiez les tags** appliqués :

```sql
SELECT * FROM wp_user_tags WHERE user_id = [ID_USER];
```

✅ Vous devriez voir les tags `test_tag` et `demo`.

---

## 📚 Documentation

### Documents Disponibles

1. **[README.md](README.md)** : Documentation complète du plugin
   - Fonctionnalités
   - API PHP
   - Structure de la base de données
   - Conformité RGPD

2. **[utm-tracker/docs/INSTALLATION.md](utm-tracker/docs/INSTALLATION.md)** : Guide d'installation détaillé
   - Prérequis
   - Installation pas à pas
   - Configuration avancée
   - Dépannage

3. **[utm-tracker/examples/sample-campaigns.sql](utm-tracker/examples/sample-campaigns.sql)** : 20+ exemples de campagnes
   - Google Ads
   - Facebook / Meta
   - LinkedIn
   - Email Marketing
   - Retargeting
   - QR Codes

---

## 🔧 Fonctions Helper Principales

### Gestion des Campagnes

```php
// Ajouter une campagne
utm_add_campaign([
    'name' => 'Ma Campagne',
    'utm_source' => 'google',
    'utm_medium' => 'cpc',
    'utm_campaign' => 'ma_campagne',
    'user_tags' => ['tag1', 'tag2'],
    'status' => 'active'
]);

// Lister les campagnes
$campaigns = utm_get_campaigns( 'active' );

// Obtenir les stats d'une campagne
$stats = utm_get_campaign_stats( $campaign_id );
```

### Gestion des Tags Utilisateur

```php
// Obtenir les tags d'un utilisateur
$tags = utm_get_user_tags( $user_id );

// Vérifier si un user a un tag
if ( utm_user_has_tag( $user_id, 'lead_google' ) ) {
    // Utilisateur vient de Google
}

// Obtenir tous les users avec un tag
$user_ids = utm_get_users_by_tag( 'coaching' );

// Compter les users par tag
$count = utm_count_users_by_tag( 'lead_google' );
```

---

## 🎯 Cas d'Usage Typiques

### 1. Attribution Marketing

```php
// Identifier d'où viennent vos meilleurs clients
$google_users = utm_get_users_by_tag( 'lead_google' );
$facebook_users = utm_get_users_by_tag( 'lead_facebook' );

echo "Google : " . count( $google_users ) . " leads\n";
echo "Facebook : " . count( $facebook_users ) . " leads\n";
```

### 2. Segmentation par Campagne

```php
// Cibler les utilisateurs d'une campagne spécifique
$coaching_users = utm_get_users_by_tag( 'coaching' );

// Envoyer un email personnalisé
foreach ( $coaching_users as $user_id ) {
    $user = get_user_by( 'id', $user_id );
    // Envoyer email sur le coaching...
}
```

### 3. Analytics Personnalisés

```php
// Récupérer les stats de toutes les campagnes actives
$campaigns = utm_get_campaigns( 'active' );

foreach ( $campaigns as $campaign ) {
    $stats = utm_get_campaign_stats( $campaign->id );
    
    echo "{$campaign->name} :\n";
    echo "  - Visites : {$stats['visits']}\n";
    echo "  - Utilisateurs : {$stats['users']}\n";
    echo "  - Conversions : {$stats['conversions']}\n";
    echo "  - Taux : {$stats['conversion_rate']}%\n\n";
}
```

---

## 🔍 Vérification de l'Installation

### Checklist Rapide

```php
<?php
// Créer un fichier test-utm-tracker.php dans votre thème

// 1. Vérifier le plugin
if ( function_exists( 'utm_tracker' ) ) {
    echo "✅ Plugin chargé\n";
} else {
    echo "❌ Plugin non chargé\n";
}

// 2. Vérifier les tables
global $wpdb;
$tables = [
    $wpdb->prefix . 'utm_campaigns',
    $wpdb->prefix . 'user_tags',
    $wpdb->prefix . 'utm_events'
];

foreach ( $tables as $table ) {
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
    echo $exists ? "✅ Table {$table} OK\n" : "❌ Table {$table} manquante\n";
}

// 3. Compter les campagnes
$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}utm_campaigns" );
echo "📊 Campagnes : {$count}\n";
?>
```

---

## 🐛 Dépannage Rapide

### Problème : Tables non créées

```bash
# Réactiver le plugin via WP-CLI
wp plugin deactivate utm-tracker
wp plugin activate utm-tracker
```

### Problème : UTM non capturés

1. Vérifier que les sessions PHP sont activées
2. Activer `WP_DEBUG` dans `wp-config.php`
3. Consulter `wp-content/debug.log`

### Problème : Tags non appliqués

```sql
-- Vérifier qu'une campagne active existe
SELECT * FROM wp_utm_campaigns WHERE status = 'active';

-- Vérifier le format JSON des tags
SELECT id, name, user_tags FROM wp_utm_campaigns;
```

---

## 📈 Prochaines Étapes

### MVP Terminé ✅

Le plugin fonctionne maintenant avec :
- ✅ Capture UTM sans cookies
- ✅ Matching automatique campagnes
- ✅ Application automatique des tags
- ✅ Historique complet des événements
- ✅ API PHP complète

### Version 1.1 (Planifiée)

Ajouts prévus :
- 📊 Interface admin minimaliste (liste campagnes)
- 📈 Page de statistiques basique
- 📥 Export CSV des événements
- 🔄 CRUD campagnes via interface WordPress

### Développement Custom

Le plugin est conçu pour être **extensible** via hooks :

```php
// Hook : modifier les tags avant application
add_filter( 'utm_tracker_campaign_tags', function( $tags, $campaign_id, $user_id ) {
    // Logique custom
    return $tags;
}, 10, 3 );

// Hook : action après application des tags
add_action( 'utm_tracker_tags_applied', function( $user_id, $tags, $campaign_id ) {
    // Notification, log, intégration CRM, etc.
}, 10, 3 );
```

---

## 📞 Support

Pour toute question ou problème :

1. **Documentation** : Lire [README.md](README.md) et [INSTALLATION.md](utm-tracker/docs/INSTALLATION.md)
2. **Exemples** : Consulter [sample-campaigns.sql](utm-tracker/examples/sample-campaigns.sql)
3. **Debug** : Activer `WP_DEBUG` et consulter les logs
4. **Contact** : Équipe Elevatio

---

## ✅ Checklist Post-Installation

- [ ] Plugin activé dans WordPress
- [ ] Tables créées (vérifier via phpMyAdmin)
- [ ] Campagne de test créée
- [ ] Test de capture UTM réussi
- [ ] Test d'inscription avec tags appliqués
- [ ] Documentation sauvegardée
- [ ] Campagnes de production créées
- [ ] Backup de la base de données effectué

---

**🎉 Plugin UTM Tracker opérationnel !**

Tu peux maintenant tracker tes campagnes marketing et attribuer automatiquement des tags à tes utilisateurs.

**Bonne utilisation ! 🚀**

