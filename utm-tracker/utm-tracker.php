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

