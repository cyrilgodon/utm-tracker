<?php
/**
 * Capture des paramètres UTM depuis l'URL
 *
 * Capture les paramètres UTM et les stocke en session PHP (pas de cookies).
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe UTM_Capture
 *
 * @since 1.0.0
 */
class UTM_Capture {

	/**
	 * Paramètres UTM supportés
	 *
	 * @var array
	 */
	private $utm_params = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_content',
		'utm_term',
		'gclid',  // Google Click ID
		'fbclid', // Facebook Click ID
	);

	/**
	 * Constructeur
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'capture_utm_params' ), 1 );
		add_action( 'user_register', array( $this, 'process_user_registration' ), 10, 1 );
	}

	/**
	 * Capturer les paramètres UTM depuis l'URL
	 *
	 * @since 1.0.0
	 */
	public function capture_utm_params() {
		// Vérifier si la session est démarrée
		if ( ! session_id() ) {
			return;
		}

		// Vérifier s'il y a des paramètres UTM dans l'URL
		$has_utm = false;
		foreach ( $this->utm_params as $param ) {
			if ( isset( $_GET[ $param ] ) && ! empty( $_GET[ $param ] ) ) {
				$has_utm = true;
				break;
			}
		}

		// Si pas de paramètres UTM, on ne fait rien
		if ( ! $has_utm ) {
			return;
		}

		// Capturer les paramètres UTM
		$utm_data = array();
		foreach ( $this->utm_params as $param ) {
			if ( isset( $_GET[ $param ] ) && ! empty( $_GET[ $param ] ) ) {
				// Sanitize et normaliser
				$value = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
				$value = trim( $value );
				$value = substr( $value, 0, 255 ); // Limite à 255 caractères

				// Normaliser en lowercase pour source, medium, campaign
				if ( in_array( $param, array( 'utm_source', 'utm_medium', 'utm_campaign' ), true ) ) {
					$value = strtolower( $value );
				}

				$utm_data[ $param ] = $value;
			}
		}

		// Ajouter des métadonnées
		$utm_data['referrer']      = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$utm_data['landing_page']  = esc_url_raw( $this->get_current_url() );
		$utm_data['timestamp']     = current_time( 'mysql' );
		$utm_data['session_id']    = session_id();

		// Stocker en session
		$_SESSION['utm_data'] = $utm_data;

		// Enregistrer l'événement dans la base de données
		$this->log_utm_event( $utm_data );

		// Log pour debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[UTM Tracker] UTM capturés : ' . wp_json_encode( $utm_data ) );
		}
	}

	/**
	 * Enregistrer un événement UTM dans la base de données
	 *
	 * @since 1.0.0
	 * @param array $utm_data Données UTM à enregistrer.
	 */
	private function log_utm_event( $utm_data ) {
		global $wpdb;

		$table_events = $wpdb->prefix . 'utm_events';

		// Préparer les données
		$event_data = array(
			'user_id'       => get_current_user_id() ?: null,
			'session_id'    => $utm_data['session_id'] ?? null,
			'utm_source'    => $utm_data['utm_source'] ?? null,
			'utm_medium'    => $utm_data['utm_medium'] ?? null,
			'utm_campaign'  => $utm_data['utm_campaign'] ?? null,
			'utm_content'   => $utm_data['utm_content'] ?? null,
			'utm_term'      => $utm_data['utm_term'] ?? null,
			'referrer'      => $utm_data['referrer'] ?? null,
			'landing_page'  => $utm_data['landing_page'] ?? null,
			'created_at'    => current_time( 'mysql' ),
		);

		// Insérer dans la base de données
		$wpdb->insert(
			$table_events,
			$event_data,
			array(
				'%d', // user_id
				'%s', // session_id
				'%s', // utm_source
				'%s', // utm_medium
				'%s', // utm_campaign
				'%s', // utm_content
				'%s', // utm_term
				'%s', // referrer
				'%s', // landing_page
				'%s', // created_at
			)
		);

		// Log pour debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $wpdb->insert_id ) {
			error_log( '[UTM Tracker] Événement enregistré : ID ' . $wpdb->insert_id );
		}
	}

	/**
	 * Traiter l'inscription d'un utilisateur
	 *
	 * @since 1.0.0
	 * @param int $user_id ID de l'utilisateur nouvellement inscrit.
	 */
	public function process_user_registration( $user_id ) {
		// Vérifier si la session est démarrée
		if ( ! session_id() ) {
			return;
		}

		// Récupérer les données UTM de la session
		$utm_data = isset( $_SESSION['utm_data'] ) ? $_SESSION['utm_data'] : null;

		if ( empty( $utm_data ) ) {
			return;
		}

		// Enregistrer les données UTM en user_meta
		update_user_meta( $user_id, 'utm_source', $utm_data['utm_source'] ?? '' );
		update_user_meta( $user_id, 'utm_medium', $utm_data['utm_medium'] ?? '' );
		update_user_meta( $user_id, 'utm_campaign', $utm_data['utm_campaign'] ?? '' );
		update_user_meta( $user_id, 'utm_content', $utm_data['utm_content'] ?? '' );
		update_user_meta( $user_id, 'utm_term', $utm_data['utm_term'] ?? '' );
		update_user_meta( $user_id, 'utm_referrer', $utm_data['referrer'] ?? '' );
		update_user_meta( $user_id, 'utm_landing_page', $utm_data['landing_page'] ?? '' );
		update_user_meta( $user_id, 'utm_timestamp', $utm_data['timestamp'] ?? '' );

		// Matcher la campagne et appliquer les tags
		$matcher = utm_tracker()->matcher;
		if ( $matcher ) {
			$campaign = $matcher->find_matching_campaign( $utm_data );

			if ( $campaign ) {
				// Enregistrer l'ID de la campagne
				update_user_meta( $user_id, 'utm_campaign_id', $campaign->id );

				// Appliquer les tags
				$tag_applicator = utm_tracker()->tag_applicator;
				if ( $tag_applicator ) {
					$tag_applicator->apply_tags_to_user( $user_id, $campaign );
				}

				// Log pour debug
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[UTM Tracker] Campagne matchée : ' . $campaign->name . ' (ID: ' . $campaign->id . ') pour user ID: ' . $user_id );
				}
			}
		}

		// Nettoyer la session
		unset( $_SESSION['utm_data'] );
	}

	/**
	 * Obtenir l'URL courante
	 *
	 * @since 1.0.0
	 * @return string URL courante
	 */
	private function get_current_url() {
		$protocol = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
		$host     = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$uri      = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return $protocol . '://' . $host . $uri;
	}

	/**
	 * Obtenir les données UTM de la session courante
	 *
	 * @since 1.0.0
	 * @return array|null Données UTM ou null si pas de session
	 */
	public function get_session_utm_data() {
		if ( ! session_id() ) {
			return null;
		}

		return isset( $_SESSION['utm_data'] ) ? $_SESSION['utm_data'] : null;
	}
}

