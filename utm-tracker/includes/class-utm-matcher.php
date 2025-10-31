<?php
/**
 * Matcher de campagnes UTM
 *
 * Trouve la campagne correspondante aux paramètres UTM capturés.
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe UTM_Matcher
 *
 * @since 1.0.0
 */
class UTM_Matcher {

	/**
	 * Alias de compatibilité pour match_campaign (retourne l'objet campagne)
	 *
	 * @since 1.0.0
	 * @param array $utm_data
	 * @return object|null
	 */
	public function match_campaign( $utm_data ) {
		return $this->find_matching_campaign( $utm_data );
	}

	/**
	 * Trouver la campagne correspondante aux paramètres UTM
	 *
	 * Match exact : source + medium + campaign
	 * Retourne null si aucune campagne active ne correspond.
	 *
	 * @since 1.0.0
	 * @param array $utm_data Données UTM à matcher.
	 * @return object|null Objet campagne ou null si pas de match
	 */
	public function find_matching_campaign( $utm_data ) {
		global $wpdb;

		// Vérifier que les paramètres minimum sont présents
		if ( empty( $utm_data['utm_source'] ) || empty( $utm_data['utm_medium'] ) || empty( $utm_data['utm_campaign'] ) ) {
			return null;
		}

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';

		// Préparer la requête
		$query = $wpdb->prepare(
			"SELECT * FROM {$table_campaigns}
			WHERE utm_source = %s
			AND utm_medium = %s
			AND utm_campaign = %s
			AND status = 'active'
			LIMIT 1",
			strtolower( $utm_data['utm_source'] ),
			strtolower( $utm_data['utm_medium'] ),
			strtolower( $utm_data['utm_campaign'] )
		);

		// Exécuter la requête
		$campaign = $wpdb->get_row( $query );

		// Log pour debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( $campaign ) {
				error_log( '[UTM Tracker] Campagne trouvée : ' . $campaign->name . ' (ID: ' . $campaign->id . ')' );
			} else {
				error_log( '[UTM Tracker] Aucune campagne trouvée pour : ' . $utm_data['utm_source'] . '/' . $utm_data['utm_medium'] . '/' . $utm_data['utm_campaign'] );
			}
		}

		return $campaign;
	}

	/**
	 * Trouver une campagne par ID
	 *
	 * @since 1.0.0
	 * @param int $campaign_id ID de la campagne.
	 * @return object|null Objet campagne ou null si introuvable
	 */
	public function get_campaign_by_id( $campaign_id ) {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';

		$query = $wpdb->prepare(
			"SELECT * FROM {$table_campaigns} WHERE id = %d LIMIT 1",
			$campaign_id
		);

		return $wpdb->get_row( $query );
	}

	/**
	 * Lister toutes les campagnes actives
	 *
	 * @since 1.0.0
	 * @return array Liste des campagnes actives
	 */
	public function get_active_campaigns() {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';

		$query = "SELECT * FROM {$table_campaigns} WHERE status = 'active' ORDER BY created_at DESC";

		return $wpdb->get_results( $query );
	}

	/**
	 * Lister toutes les campagnes (tous statuts)
	 *
	 * @since 1.0.0
	 * @param string $status Filtrer par statut (optionnel).
	 * @return array Liste des campagnes
	 */
	public function get_all_campaigns( $status = '' ) {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';

		if ( ! empty( $status ) && in_array( $status, array( 'active', 'paused', 'archived' ), true ) ) {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table_campaigns} WHERE status = %s ORDER BY created_at DESC",
				$status
			);
		} else {
			$query = "SELECT * FROM {$table_campaigns} ORDER BY created_at DESC";
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Obtenir les statistiques d'une campagne
	 *
	 * @since 1.0.0
	 * @param int $campaign_id ID de la campagne.
	 * @return array Statistiques (visits, users, conversions)
	 */
	public function get_campaign_stats( $campaign_id ) {
		global $wpdb;

		$table_events    = $wpdb->prefix . 'utm_events';
		$table_user_tags = $wpdb->prefix . 'user_tags';

		// Nombre de visites (événements)
		$visits = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_events} WHERE campaign_id = %d",
				$campaign_id
			)
		);

		// Nombre d'utilisateurs uniques
		$users = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$table_events} WHERE campaign_id = %d AND user_id IS NOT NULL",
				$campaign_id
			)
		);

		// Nombre de conversions (users avec tags appliqués)
		$conversions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$table_user_tags} WHERE campaign_id = %d",
				$campaign_id
			)
		);

		return array(
			'visits'      => (int) $visits,
			'users'       => (int) $users,
			'conversions' => (int) $conversions,
			'conversion_rate' => $users > 0 ? round( ( $conversions / $users ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Vérifier si une campagne existe déjà (par paramètres UTM)
	 *
	 * @since 1.0.0
	 * @param string $source   Source UTM.
	 * @param string $medium   Medium UTM.
	 * @param string $campaign Campaign UTM.
	 * @return int|false ID de la campagne existante ou false
	 */
	public function campaign_exists( $source, $medium, $campaign ) {
		global $wpdb;

		$table_campaigns = $wpdb->prefix . 'utm_campaigns';

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_campaigns}
				WHERE utm_source = %s
				AND utm_medium = %s
				AND utm_campaign = %s
				LIMIT 1",
				strtolower( $source ),
				strtolower( $medium ),
				strtolower( $campaign )
			)
		);

		return $result ? (int) $result : false;
	}
}

