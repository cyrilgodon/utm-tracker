<?php
/**
 * Installateur de base de données pour UTM Tracker
 *
 * Gère la création et la mise à jour des tables SQL nécessaires au plugin.
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe UTM_DB_Installer
 *
 * @since 1.0.0
 */
class UTM_DB_Installer {

	/**
	 * Créer les tables de la base de données
	 *
	 * @since 1.0.0
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Table 1 : Campagnes UTM (configuration)
		$table_campaigns = $wpdb->prefix . 'utm_campaigns';
		$sql_campaigns   = "CREATE TABLE IF NOT EXISTS {$table_campaigns} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL COMMENT 'Nom de la campagne',
			utm_source VARCHAR(255) NOT NULL,
			utm_medium VARCHAR(255) NOT NULL,
			utm_campaign VARCHAR(255) NOT NULL,
			utm_content VARCHAR(255) DEFAULT NULL,
			utm_term VARCHAR(255) DEFAULT NULL,
			user_tags TEXT NOT NULL COMMENT 'JSON array des tags à appliquer',
			status ENUM('active', 'paused', 'archived') DEFAULT 'active',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_utm (utm_source, utm_medium, utm_campaign, utm_content),
			KEY idx_status (status)
		) $charset_collate;";

		// Table 2 : Tags utilisateur (résultat de l'application)
		$table_user_tags = $wpdb->prefix . 'user_tags';
		$sql_user_tags   = "CREATE TABLE IF NOT EXISTS {$table_user_tags} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			tag_slug VARCHAR(100) NOT NULL,
			campaign_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'ID campagne source',
			applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_user_tag (user_id, tag_slug),
			KEY idx_user (user_id),
			KEY idx_tag (tag_slug),
			KEY idx_campaign (campaign_id)
		) $charset_collate;";

		// Table 3 : Événements UTM (historique tracking)
		$table_events = $wpdb->prefix . 'utm_events';
		$sql_events   = "CREATE TABLE IF NOT EXISTS {$table_events} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'NULL si anonyme',
			session_id VARCHAR(64) DEFAULT NULL COMMENT 'Session PHP ID',
			campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
			utm_source VARCHAR(255) DEFAULT NULL,
			utm_medium VARCHAR(255) DEFAULT NULL,
			utm_campaign VARCHAR(255) DEFAULT NULL,
			utm_content VARCHAR(255) DEFAULT NULL,
			utm_term VARCHAR(255) DEFAULT NULL,
			referrer TEXT DEFAULT NULL,
			landing_page TEXT DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_user (user_id, created_at),
			KEY idx_session (session_id),
			KEY idx_campaign (campaign_id, created_at)
		) $charset_collate;";

		// Exécuter les requêtes
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_campaigns );
		dbDelta( $sql_user_tags );
		dbDelta( $sql_events );

		// Sauvegarder la version de la base de données
		update_option( 'utm_tracker_db_version', UTM_TRACKER_VERSION );

		// Log création tables
		if ( function_exists( 'error_log' ) ) {
			error_log( '[UTM Tracker] Tables créées : ' . $table_campaigns . ', ' . $table_user_tags . ', ' . $table_events );
		}
	}

	/**
	 * Supprimer les tables de la base de données
	 *
	 * ⚠️ À utiliser avec précaution ! Supprime toutes les données.
	 *
	 * @since 1.0.0
	 */
	public function drop_tables() {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';
		$table_user_tags = $wpdb->prefix . 'user_tags';
		$table_events    = $wpdb->prefix . 'utm_events';

		// Supprimer les tables
		$wpdb->query( "DROP TABLE IF EXISTS {$table_events}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_user_tags}" );
		$wpdb->query( "DROP TABLE IF EXISTS {$table_campaigns}" );

		// Supprimer l'option de version
		delete_option( 'utm_tracker_db_version' );

		// Log suppression
		if ( function_exists( 'error_log' ) ) {
			error_log( '[UTM Tracker] Tables supprimées' );
		}
	}

	/**
	 * Vérifier si les tables existent
	 *
	 * @since 1.0.0
	 * @return bool True si toutes les tables existent, false sinon
	 */
	public function tables_exist() {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';
		$table_user_tags = $wpdb->prefix . 'user_tags';
		$table_events    = $wpdb->prefix . 'utm_events';

		$campaigns_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_campaigns}'" ) === $table_campaigns;
		$user_tags_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_user_tags}'" ) === $table_user_tags;
		$events_exists    = $wpdb->get_var( "SHOW TABLES LIKE '{$table_events}'" ) === $table_events;

		return $campaigns_exists && $user_tags_exists && $events_exists;
	}

	/**
	 * Obtenir la version de la base de données
	*
	 * @since 1.0.0
	 * @return string|false Version ou false si non trouvée
	 */
	public function get_db_version() {
		return get_option( 'utm_tracker_db_version', false );
	}

	/**
	 * Vérifier si une mise à jour de la base de données est nécessaire
	 *
	 * @since 1.0.0
	 * @return bool True si mise à jour nécessaire, false sinon
	 */
	public function needs_update() {
		$db_version = $this->get_db_version();

		// Si pas de version ou version différente, mise à jour nécessaire
		if ( false === $db_version || version_compare( $db_version, UTM_TRACKER_VERSION, '<' ) ) {
			return true;
		}

		// Si tables n'existent pas, mise à jour nécessaire
		if ( ! $this->tables_exist() ) {
			return true;
		}

		return false;
	}
}

