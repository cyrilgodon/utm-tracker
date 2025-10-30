<?php
/**
 * Plugin Name: UTM Tracker
 * Plugin URI: https://elevatio.fr
 * Description: Suivi des paramètres UTM et attribution automatique de tags utilisateur basée sur les campagnes marketing. Pas de cookies, tracking via session PHP.
 * Version: 1.0.0
 * Author: Elevatio
 * Author URI: https://elevatio.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: utm-tracker
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package UTM_Tracker
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constantes du plugin
 */
define( 'UTM_TRACKER_VERSION', '1.0.0' );
define( 'UTM_TRACKER_PLUGIN_FILE', __FILE__ );
define( 'UTM_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UTM_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UTM_TRACKER_INCLUDES_DIR', UTM_TRACKER_PLUGIN_DIR . 'includes/' );

/**
 * Classe principale du plugin UTM Tracker
 *
 * @since 1.0.0
 */
class UTM_Tracker {

	/**
	 * Instance unique du plugin (Singleton)
	 *
	 * @var UTM_Tracker|null
	 */
	private static $instance = null;

	/**
	 * Capteur UTM
	 *
	 * @var UTM_Capture|null
	 */
	public $capture = null;

	/**
	 * Matcher de campagnes
	 *
	 * @var UTM_Matcher|null
	 */
	public $matcher = null;

	/**
	 * Applicateur de tags
	 *
	 * @var UTM_Tag_Applicator|null
	 */
	public $tag_applicator = null;

	/**
	 * Installateur de base de données
	 *
	 * @var UTM_DB_Installer|null
	 */
	public $db_installer = null;

	/**
	 * Page d'administration
	 *
	 * @var UTM_Admin_Page|null
	 */
	public $admin_page = null;

	/**
	 * Obtenir l'instance unique du plugin
	 *
	 * @since 1.0.0
	 * @return UTM_Tracker
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructeur privé (Singleton)
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Charger les dépendances du plugin
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		// Classes core
		require_once UTM_TRACKER_INCLUDES_DIR . 'class-db-installer.php';
		require_once UTM_TRACKER_INCLUDES_DIR . 'class-utm-capture.php';
		require_once UTM_TRACKER_INCLUDES_DIR . 'class-utm-matcher.php';
		require_once UTM_TRACKER_INCLUDES_DIR . 'class-tag-applicator.php';
		require_once UTM_TRACKER_INCLUDES_DIR . 'class-admin-page.php';

		// Fonctions helper
		require_once UTM_TRACKER_INCLUDES_DIR . 'functions-helpers.php';

		// Initialiser les composants
		$this->db_installer    = new UTM_DB_Installer();
		$this->capture         = new UTM_Capture();
		$this->matcher         = new UTM_Matcher();
		$this->tag_applicator  = new UTM_Tag_Applicator();
		$this->admin_page      = new UTM_Admin_Page();
	}

	/**
	 * Initialiser les hooks WordPress
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Activation/Désactivation du plugin
		register_activation_hook( UTM_TRACKER_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( UTM_TRACKER_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Hooks WordPress
		add_action( 'plugins_loaded', array( $this, 'start_session' ), 1 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		add_action( 'wp_login', array( $this, 'destroy_session' ) );
		
		// Hook pour l'inscription utilisateur
		add_action( 'user_register', array( $this, 'on_user_register' ), 10, 1 );
	}

	/**
	 * Activation du plugin
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		// Créer les tables
		$this->db_installer->create_tables();

		// Flush rewrite rules si nécessaire
		flush_rewrite_rules();

		// Log activation
		if ( function_exists( 'error_log' ) ) {
			error_log( '[UTM Tracker] Plugin activé - Version ' . UTM_TRACKER_VERSION );
		}
	}

	/**
	 * Désactivation du plugin
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();

		// Log désactivation
		if ( function_exists( 'error_log' ) ) {
			error_log( '[UTM Tracker] Plugin désactivé' );
		}
	}

	/**
	 * Démarrer la session PHP si pas déjà démarrée
	 *
	 * @since 1.0.0
	 */
	public function start_session() {
		if ( ! session_id() && ! headers_sent() ) {
			session_start();
		}
	}

	/**
	 * Détruire la session PHP
	 *
	 * @since 1.0.0
	 */
	public function destroy_session() {
		if ( session_id() ) {
			session_destroy();
		}
	}

	/**
	 * Hook appelé lors de l'inscription d'un nouvel utilisateur
	 * Applique les tags basés sur les UTM capturés en session
	 *
	 * @since 1.0.0
	 * @param int $user_id ID du nouvel utilisateur
	 */
	public function on_user_register( $user_id ) {
		// Envelopper dans un try-catch pour éviter de casser l'inscription
		try {
			// Vérifier que les objets sont initialisés (noms corrects des propriétés)
			if ( ! isset( $this->capture ) || ! isset( $this->matcher ) || ! isset( $this->tag_applicator ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[UTM Tracker] ⚠️ Objets non initialisés lors de user_register' );
				}
				return;
			}

			// Vérifier que la session existe
			if ( ! session_id() ) {
				return;
			}

			// Récupérer les données UTM de la session
			if ( ! is_callable( array( $this->capture, 'get_session_utm_data' ) ) ) {
				return;
			}

			$utm_data = $this->capture->get_session_utm_data();
			
			if ( empty( $utm_data ) ) {
				// Aucune donnée UTM en session - c'est normal, on sort silencieusement
				return;
			}

			// Log pour debug
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[UTM Tracker] Traitement inscription utilisateur ' . $user_id . ' avec UTM : ' . wp_json_encode( $utm_data ) );
			}

			// Sauvegarder les UTM dans les user meta
			$this->save_utm_to_user_meta( $user_id, $utm_data );

			// Extraire les paramètres UTM
			$utm_params = array(
				'utm_source'   => isset( $utm_data['utm_source'] ) ? $utm_data['utm_source'] : '',
				'utm_medium'   => isset( $utm_data['utm_medium'] ) ? $utm_data['utm_medium'] : '',
				'utm_campaign' => isset( $utm_data['utm_campaign'] ) ? $utm_data['utm_campaign'] : '',
			);

			// Vérifier qu'on a les paramètres minimum
			if ( empty( $utm_params['utm_source'] ) || empty( $utm_params['utm_medium'] ) || empty( $utm_params['utm_campaign'] ) ) {
				return;
			}

			// Matcher la campagne
			$campaign = $this->matcher->match_campaign( $utm_params );

			if ( ! $campaign ) {
				// Aucune campagne correspondante - c'est normal, on sort silencieusement
				return;
			}

			// Appliquer les tags de la campagne
			$tags_count = $this->tag_applicator->apply_tags_to_user( $user_id, $campaign );

			// Mettre à jour l'événement UTM avec le user_id
			if ( isset( $utm_data['session_id'] ) ) {
				$this->update_utm_event_with_user_id( $user_id, $utm_data['session_id'] );
			}

			// Log succès
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[UTM Tracker] ✅ ' . $tags_count . ' tag(s) appliqué(s) à l\'utilisateur ' . $user_id . ' depuis la campagne "' . $campaign->name . '"' );
			}

			// Hook personnalisé après attribution
			do_action( 'utm_tracker_user_registered', $user_id, $campaign, $utm_data );

		} catch ( Exception $e ) {
			// En cas d'erreur, on log mais on ne fait pas planter l'inscription
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[UTM Tracker] ❌ Erreur lors de user_register : ' . $e->getMessage() );
				error_log( '[UTM Tracker] Trace : ' . $e->getTraceAsString() );
			}
			// On ne fait rien d'autre - l'inscription continue normalement
		}
	}

	/**
	 * Sauvegarder les UTM dans les user meta
	 *
	 * @since 1.0.0
	 * @param int   $user_id  ID de l'utilisateur
	 * @param array $utm_data Données UTM
	 */
	private function save_utm_to_user_meta( $user_id, $utm_data ) {
		// Sauvegarder chaque paramètre UTM
		$utm_params = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid' );
		
		foreach ( $utm_params as $param ) {
			if ( isset( $utm_data[ $param ] ) && ! empty( $utm_data[ $param ] ) ) {
				update_user_meta( $user_id, $param, sanitize_text_field( $utm_data[ $param ] ) );
			}
		}

		// Sauvegarder les métadonnées supplémentaires
		if ( isset( $utm_data['referrer'] ) && ! empty( $utm_data['referrer'] ) ) {
			update_user_meta( $user_id, 'utm_referrer', esc_url_raw( $utm_data['referrer'] ) );
		}

		if ( isset( $utm_data['landing_page'] ) && ! empty( $utm_data['landing_page'] ) ) {
			update_user_meta( $user_id, 'utm_landing_page', esc_url_raw( $utm_data['landing_page'] ) );
		}

		if ( isset( $utm_data['timestamp'] ) ) {
			update_user_meta( $user_id, 'utm_first_visit', $utm_data['timestamp'] );
		}
	}

	/**
	 * Mettre à jour les événements UTM avec le user_id après inscription
	 *
	 * @since 1.0.0
	 * @param int    $user_id    ID de l'utilisateur
	 * @param string $session_id ID de session
	 */
	private function update_utm_event_with_user_id( $user_id, $session_id ) {
		if ( empty( $session_id ) ) {
			return;
		}

		global $wpdb;
		$table_utm_events = $wpdb->prefix . 'utm_events';

		// Mettre à jour tous les événements de cette session avec le user_id
		$updated = $wpdb->update(
			$table_utm_events,
			array( 'user_id' => $user_id ),
			array( 
				'session_id' => $session_id,
				'user_id'    => null, // Seulement les événements sans user_id
			),
			array( '%d' ),
			array( '%s', '%d' )
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $updated ) {
			error_log( '[UTM Tracker] ' . $updated . ' événement(s) UTM mis à jour avec user_id ' . $user_id );
		}
	}

	/**
	 * Obtenir la version du plugin
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_version() {
		return UTM_TRACKER_VERSION;
	}
}

/**
 * Fonction helper pour obtenir l'instance du plugin
 *
 * @since 1.0.0
 * @return UTM_Tracker
 */
function utm_tracker() {
	return UTM_Tracker::get_instance();
}

// Démarrer le plugin
utm_tracker();

