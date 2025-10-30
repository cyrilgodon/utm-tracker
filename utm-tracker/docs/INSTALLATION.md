# 📥 Guide d'Installation - UTM Tracker

**Version** : 1.0.0  
**Date** : 2025-10-30

---

## 🎯 Prérequis

Avant d'installer le plugin, vérifiez que votre environnement répond aux exigences suivantes :

### Serveur

- ✅ **WordPress** : 5.8 ou supérieur
- ✅ **PHP** : 7.4, 8.0, 8.1, 8.2 ou 8.3
- ✅ **MySQL** : 5.6+ ou MariaDB 10.0+
- ✅ **Sessions PHP** : Activées (par défaut sur la plupart des hébergements)

### Permissions

- ✅ Capacité de créer des tables MySQL (`CREATE TABLE`)
- ✅ Capacité de modifier les tables (`ALTER TABLE`)
- ✅ Capacité d'insérer/mettre à jour/supprimer des données

### Vérification

Pour vérifier que les sessions PHP sont activées, créez un fichier `test-session.php` :

```php
<?php
session_start();
echo session_id() ? 'Sessions PHP : ✅ OK' : 'Sessions PHP : ❌ Désactivées';
?>
```

---

## 📦 Installation

### Méthode 1 : Installation Manuelle (Recommandée)

1. **Télécharger** le dossier `utm-tracker/` complet

2. **Uploader** via FTP/SFTP dans `wp-content/plugins/` :
   ```
   wp-content/
   └── plugins/
       └── utm-tracker/
           ├── utm-tracker.php
           ├── includes/
           ├── docs/
           └── README.md
   ```

3. **Se connecter** à l'admin WordPress

4. **Activer** le plugin :
   - Aller dans **Extensions** > **Extensions installées**
   - Trouver **UTM Tracker**
   - Cliquer sur **Activer**

5. **Vérifier** la création des tables (voir section Vérification)

---

### Méthode 2 : Installation via WP-CLI

Si vous utilisez WP-CLI :

```bash
# Se placer dans le dossier WordPress
cd /path/to/wordpress

# Copier le plugin
cp -r /path/to/utm-tracker wp-content/plugins/

# Activer le plugin
wp plugin activate utm-tracker

# Vérifier l'activation
wp plugin list | grep utm-tracker
```

---

## ✅ Vérification de l'Installation

### 1. Vérifier les Tables Créées

Connectez-vous à **phpMyAdmin** ou via **MySQL CLI** et vérifiez la présence de 3 tables :

```sql
SHOW TABLES LIKE '%utm%';
```

Résultat attendu :
```
wp_utm_campaigns
wp_utm_events
wp_user_tags
```

### 2. Vérifier la Structure des Tables

```sql
DESCRIBE wp_utm_campaigns;
DESCRIBE wp_user_tags;
DESCRIBE wp_utm_events;
```

### 3. Vérifier dans WordPress

Créez un fichier `test-utm-tracker.php` dans votre thème :

```php
<?php
// Vérifier que le plugin est chargé
if ( function_exists( 'utm_tracker' ) ) {
    echo '✅ Plugin UTM Tracker chargé !<br>';
    
    // Vérifier les tables
    global $wpdb;
    $table_campaigns = $wpdb->prefix . 'utm_campaigns';
    $exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_campaigns}'" );
    
    if ( $exists ) {
        echo '✅ Tables créées !<br>';
    } else {
        echo '❌ Tables non créées<br>';
    }
} else {
    echo '❌ Plugin non chargé<br>';
}
?>
```

---

## ⚙️ Configuration Initiale

### Étape 1 : Créer Votre Première Campagne

#### Option A : Via SQL (Rapide)

Connectez-vous à **phpMyAdmin** et exécutez :

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

#### Option B : Via PHP

Ajoutez dans `functions.php` de votre thème (temporairement) :

```php
// Créer une campagne de test au chargement du site (1 fois)
add_action( 'init', function() {
    if ( ! get_option( 'utm_tracker_demo_campaign_created' ) ) {
        utm_add_campaign([
            'name' => 'Test Campaign',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'test_campaign',
            'user_tags' => ['test_tag', 'demo'],
            'status' => 'active'
        ]);
        
        update_option( 'utm_tracker_demo_campaign_created', true );
    }
});
```

### Étape 2 : Tester la Capture UTM

1. **Visitez votre site** avec des paramètres UTM :
   ```
   https://votresite.com/?utm_source=google&utm_medium=cpc&utm_campaign=test_campaign
   ```

2. **Vérifier dans la base de données** :
   ```sql
   SELECT * FROM wp_utm_events ORDER BY created_at DESC LIMIT 1;
   ```

   Vous devriez voir un nouvel événement avec vos UTM.

### Étape 3 : Tester l'Application de Tags

1. **Inscrivez un nouvel utilisateur** (ou testez avec un compte de test)

2. **Vérifier les tags appliqués** :
   ```sql
   SELECT * FROM wp_user_tags WHERE user_id = 123; -- Remplacer 123 par l'ID utilisateur
   ```

   Vous devriez voir les tags `test_tag` et `demo` appliqués.

---

## 🔧 Configuration Avancée

### Modifier le Timeout de Session

Par défaut, la session PHP expire après **24 minutes d'inactivité**. Pour modifier :

Ajoutez dans `wp-config.php` :

```php
// Définir le timeout à 1 heure (3600 secondes)
ini_set( 'session.gc_maxlifetime', 3600 );
```

### Activer le Mode Debug

Pour voir les logs du plugin, activez le debug WordPress :

Dans `wp-config.php` :

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Les logs seront dans `wp-content/debug.log`.

### Personnaliser les Hooks

Ajoutez dans `functions.php` :

```php
// Modifier les tags avant application
add_filter( 'utm_tracker_campaign_tags', function( $tags, $campaign_id, $user_id ) {
    // Ajouter un tag supplémentaire pour tous les utilisateurs
    $tags[] = 'custom_tag';
    return $tags;
}, 10, 3 );

// Action après application des tags
add_action( 'utm_tracker_tags_applied', function( $user_id, $tags, $campaign_id ) {
    error_log( 'Tags appliqués à l\'utilisateur ' . $user_id . ' : ' . implode( ', ', $tags ) );
}, 10, 3 );
```

---

## 🗄️ Import de Campagnes en Masse

Si vous avez plusieurs campagnes à créer, utilisez ce script SQL :

```sql
-- Exemple : 5 campagnes Google Ads
INSERT INTO wp_utm_campaigns (name, utm_source, utm_medium, utm_campaign, user_tags, status) VALUES
('Google Ads - Coaching Q1', 'google', 'cpc', 'coaching_q1', '["lead_google", "coaching"]', 'active'),
('Google Ads - Reflexivo', 'google', 'cpc', 'reflexivo_promo', '["lead_google", "reflexivo"]', 'active'),
('Facebook Ads - Lead Gen', 'facebook', 'paid', 'lead_gen_2025', '["lead_facebook", "premium"]', 'active'),
('LinkedIn Organic', 'linkedin', 'organic', 'post_share', '["lead_linkedin", "organic"]', 'active'),
('Newsletter Mensuelle', 'email', 'newsletter', 'monthly_digest', '["subscriber", "engaged"]', 'active');
```

Ou via PHP (script WP-CLI) :

```php
<?php
// campaigns-import.php
$campaigns = [
    ['name' => 'Google Ads - Coaching Q1', 'source' => 'google', 'medium' => 'cpc', 'campaign' => 'coaching_q1', 'tags' => ['lead_google', 'coaching']],
    ['name' => 'Facebook Ads - Lead Gen', 'source' => 'facebook', 'medium' => 'paid', 'campaign' => 'lead_gen_2025', 'tags' => ['lead_facebook', 'premium']],
];

foreach ( $campaigns as $camp ) {
    utm_add_campaign([
        'name' => $camp['name'],
        'utm_source' => $camp['source'],
        'utm_medium' => $camp['medium'],
        'utm_campaign' => $camp['campaign'],
        'user_tags' => $camp['tags'],
        'status' => 'active'
    ]);
    echo "✅ Campagne créée : {$camp['name']}\n";
}
```

Exécuter avec :
```bash
wp eval-file campaigns-import.php
```

---

## 🚨 Dépannage

### Problème : Tables non créées

**Symptômes** : Erreurs SQL, plugin ne fonctionne pas

**Solutions** :

1. **Réactiver le plugin** :
   ```bash
   wp plugin deactivate utm-tracker
   wp plugin activate utm-tracker
   ```

2. **Créer les tables manuellement** (voir `examples/create-tables.sql`)

3. **Vérifier les permissions MySQL** :
   ```sql
   SHOW GRANTS FOR 'votre_user_mysql'@'localhost';
   ```

### Problème : Session non démarrée

**Symptômes** : UTM non capturés, `$_SESSION` vide

**Solutions** :

1. Vérifier que `session.auto_start = 0` dans `php.ini`
2. Vérifier qu'aucun autre plugin ne bloque les sessions
3. Tester avec le fichier `test-session.php` (voir Prérequis)

### Problème : Tags non appliqués

**Symptômes** : Utilisateurs inscrits sans tags dans `wp_user_tags`

**Solutions** :

1. **Vérifier qu'une campagne active existe** :
   ```sql
   SELECT * FROM wp_utm_campaigns WHERE status = 'active';
   ```

2. **Vérifier le format JSON des tags** :
   ```sql
   UPDATE wp_utm_campaigns 
   SET user_tags = '["tag1", "tag2"]' 
   WHERE id = 1;
   ```

3. **Activer le debug** et consulter `wp-content/debug.log`

### Problème : Conflit avec un autre plugin

**Symptômes** : Erreurs PHP, site cassé

**Solutions** :

1. **Désactiver tous les autres plugins** :
   ```bash
   wp plugin deactivate --all --exclude=utm-tracker
   ```

2. **Réactiver un par un** pour identifier le conflit

3. **Contacter le support** avec les détails du conflit

---

## 🔄 Mise à Jour du Plugin

### Méthode Manuelle

1. **Sauvegarder** la base de données (export via phpMyAdmin)
2. **Désactiver** le plugin
3. **Remplacer** le dossier `utm-tracker/` par la nouvelle version
4. **Réactiver** le plugin
5. **Vérifier** les logs pour d'éventuelles migrations

### Via WP-CLI

```bash
# Sauvegarder la base
wp db export backup.sql

# Mettre à jour (quand disponible via WordPress.org)
wp plugin update utm-tracker

# Vérifier la version
wp plugin list | grep utm-tracker
```

---

## 🗑️ Désinstallation Complète

### Si vous souhaitez supprimer le plugin ET les données

1. **Désactiver le plugin** dans WordPress

2. **Supprimer les tables** (⚠️ ATTENTION : Supprime toutes les données !) :
   ```sql
   DROP TABLE IF EXISTS wp_utm_events;
   DROP TABLE IF EXISTS wp_user_tags;
   DROP TABLE IF EXISTS wp_utm_campaigns;
   ```

3. **Supprimer le dossier** `wp-content/plugins/utm-tracker/`

4. **Supprimer l'option de version** :
   ```sql
   DELETE FROM wp_options WHERE option_name = 'utm_tracker_db_version';
   ```

---

## 📞 Support

Si vous rencontrez des problèmes non couverts par ce guide :

1. **Consulter** le [README.md](../README.md) principal
2. **Vérifier** les exemples dans [examples/](../examples/)
3. **Activer le debug** WordPress et consulter les logs
4. **Contacter** l'équipe Elevatio avec les détails de l'erreur

---

## ✅ Checklist Post-Installation

- [ ] Plugin activé dans WordPress
- [ ] 3 tables créées (`wp_utm_campaigns`, `wp_user_tags`, `wp_utm_events`)
- [ ] Au moins 1 campagne de test créée
- [ ] Test de capture UTM réussi (visiter avec `?utm_source=...`)
- [ ] Test d'inscription utilisateur avec tags appliqués
- [ ] Debug WordPress activé et logs vérifiés
- [ ] Campagnes de production créées
- [ ] Documentation sauvegardée pour référence future

---

**Installation terminée ! 🎉**  
Vous êtes prêt à tracker vos campagnes marketing et attribuer des tags automatiquement.

