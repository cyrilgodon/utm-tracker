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

		// Rediriger vers l'URL propre (sans paramètres UTM)
		$this->redirect_clean_url();

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

		// Essayer de matcher une campagne (mais on enregistre même si pas de match)
		$campaign_id = null;
		if ( isset( $utm_data['utm_source'], $utm_data['utm_medium'], $utm_data['utm_campaign'] ) ) {
			// Utiliser le matcher via l'instance globale du plugin
			$plugin = utm_tracker();
			if ( $plugin && isset( $plugin->matcher ) ) {
				$campaign = $plugin->matcher->match_campaign( array(
					'utm_source'   => $utm_data['utm_source'],
					'utm_medium'   => $utm_data['utm_medium'],
					'utm_campaign' => $utm_data['utm_campaign'],
				) );
				if ( $campaign ) {
					$campaign_id = $campaign->id;
				}
			}
		}

		// Préparer les données (TOUS les événements sont enregistrés, même sans campagne)
		$event_data = array(
			'user_id'       => get_current_user_id() ?: null,
			'session_id'    => $utm_data['session_id'] ?? null,
			'campaign_id'   => $campaign_id,
			'utm_source'    => $utm_data['utm_source'] ?? null,
			'utm_medium'    => $utm_data['utm_medium'] ?? null,
			'utm_campaign'  => $utm_data['utm_campaign'] ?? null,
			'utm_content'   => $utm_data['utm_content'] ?? null,
			'utm_term'      => $utm_data['utm_term'] ?? null,
			'gclid'         => $utm_data['gclid'] ?? null,
			'fbclid'        => $utm_data['fbclid'] ?? null,
			'referrer'      => $utm_data['referrer'] ?? null,
			'landing_page'  => $utm_data['landing_page'] ?? null,
			'created_at'    => current_time( 'mysql' ),
		);

		// Insérer dans la base de données
		$result = $wpdb->insert(
			$table_events,
			$event_data,
			array(
				'%d', // user_id
				'%s', // session_id
				'%d', // campaign_id
				'%s', // utm_source
				'%s', // utm_medium
				'%s', // utm_campaign
				'%s', // utm_content
				'%s', // utm_term
				'%s', // gclid
				'%s', // fbclid
				'%s', // referrer
				'%s', // landing_page
				'%s', // created_at
			)
		);

		// Log si pas de campagne matchée
		if ( ! $campaign_id && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[UTM Tracker] ⚠️ Événement UTM enregistré SANS campagne : ' . $utm_data['utm_source'] . '/' . $utm_data['utm_medium'] . '/' . $utm_data['utm_campaign'] );
		}

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

	/**
	 * Rediriger vers l'URL sans paramètres UTM (URL propre)
	 *
	 * @since 1.0.0
	 */
	private function redirect_clean_url() {
		// Ne pas rediriger si on est en admin ou en AJAX
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Ne pas rediriger si on est déjà en POST (évite de perdre des données)
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// Construire l'URL propre sans les paramètres UTM
		$current_url = $this->get_current_url();
		$parsed_url  = wp_parse_url( $current_url );

		// Si pas de query string, pas besoin de rediriger
		if ( ! isset( $parsed_url['query'] ) || empty( $parsed_url['query'] ) ) {
			return;
		}

		// Parser les paramètres de l'URL
		parse_str( $parsed_url['query'], $query_params );

		// Retirer tous les paramètres UTM
		$utm_params_to_remove = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid' );
		$has_utm = false;

		foreach ( $utm_params_to_remove as $param ) {
			if ( isset( $query_params[ $param ] ) ) {
				unset( $query_params[ $param ] );
				$has_utm = true;
			}
		}

		// Si aucun paramètre UTM n'a été trouvé, pas besoin de rediriger
		if ( ! $has_utm ) {
			return;
		}

		// Reconstruire l'URL propre
		$clean_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

		if ( isset( $parsed_url['port'] ) ) {
			$clean_url .= ':' . $parsed_url['port'];
		}

		$clean_url .= $parsed_url['path'];

		// Rajouter les paramètres restants (non-UTM)
		if ( ! empty( $query_params ) ) {
			$clean_url .= '?' . http_build_query( $query_params );
		}

		// Ajouter le fragment si présent
		if ( isset( $parsed_url['fragment'] ) ) {
			$clean_url .= '#' . $parsed_url['fragment'];
		}

		// Redirection 302 (temporaire) pour éviter de casser le SEO
		wp_safe_redirect( $clean_url, 302 );
		exit;
	}
}

