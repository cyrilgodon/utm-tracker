<?php
/**
 * Page d'administration UTM Tracker
 *
 * Affiche le statut du plugin, les statistiques et permet de tester.
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe UTM_Admin_Page
 *
 * @since 1.0.0
 */
class UTM_Admin_Page {

	/**
	 * Constructeur
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_post_utm_tracker_test_campaign', array( $this, 'handle_test_campaign' ) );
		add_action( 'admin_post_utm_tracker_clear_test_data', array( $this, 'handle_clear_test_data' ) );
		add_action( 'admin_post_utm_tracker_save_campaign', array( $this, 'handle_save_campaign' ) );
		add_action( 'admin_post_utm_tracker_delete_campaign', array( $this, 'handle_delete_campaign' ) );
		add_action( 'admin_post_utm_tracker_duplicate_campaign', array( $this, 'handle_duplicate_campaign' ) );
	}

	/**
	 * Ajouter le menu dans l'admin WordPress
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			'UTM Tracker',
			'UTM Tracker',
			'manage_options',
			'utm-tracker',
			array( $this, 'render_admin_page' ),
			'dashicons-chart-line',
			30
		);

		// Sous-menu : Dashboard (renomme le premier élément)
		add_submenu_page(
			'utm-tracker',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'utm-tracker',
			array( $this, 'render_admin_page' )
		);

		// Sous-menu : Toutes les Campagnes
		add_submenu_page(
			'utm-tracker',
			'Campagnes',
			'Campagnes',
			'manage_options',
			'utm-tracker-campaigns',
			array( $this, 'render_campaigns_page' )
		);

		// Sous-menu : Ajouter une Campagne
		add_submenu_page(
			'utm-tracker',
			'Ajouter une Campagne',
			'Ajouter une Campagne',
			'manage_options',
			'utm-tracker-add-campaign',
			array( $this, 'render_add_campaign_page' )
		);

		// Sous-menu : UTM Non Matchés
		add_submenu_page(
			'utm-tracker',
			'UTM Non Matchés',
			'UTM Non Matchés',
			'manage_options',
			'utm-tracker-unmatched',
			array( $this, 'render_unmatched_page' )
		);

		// Sous-menu : Éditer une Campagne (caché, accessible via paramètre)
		add_submenu_page(
			null, // Null = caché du menu
			'Éditer une Campagne',
			'Éditer une Campagne',
			'manage_options',
			'utm-tracker-edit-campaign',
			array( $this, 'render_edit_campaign_page' )
		);
	}

	/**
	 * Enqueue les styles admin
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_utm-tracker' !== $hook ) {
			return;
		}

		// Styles inline
		$css = "
		.utm-tracker-admin {
			max-width: 1200px;
			margin: 20px 0;
		}
		.utm-tracker-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
			margin-bottom: 20px;
			box-shadow: 0 1px 1px rgba(0,0,0,0.04);
		}
		.utm-tracker-card h2 {
			margin-top: 0;
			border-bottom: 1px solid #eee;
			padding-bottom: 10px;
		}
		.utm-status-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 15px;
			margin: 20px 0;
		}
		.utm-status-item {
			padding: 15px;
			background: #f8f9fa;
			border-left: 4px solid #2271b1;
			border-radius: 3px;
		}
		.utm-status-item.success {
			border-left-color: #00a32a;
		}
		.utm-status-item.warning {
			border-left-color: #dba617;
		}
		.utm-status-item.error {
			border-left-color: #d63638;
		}
		.utm-status-item .label {
			font-size: 12px;
			color: #646970;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.utm-status-item .value {
			font-size: 24px;
			font-weight: 600;
			margin: 5px 0;
		}
		.utm-table {
			width: 100%;
			border-collapse: collapse;
		}
		.utm-table th,
		.utm-table td {
			padding: 12px;
			text-align: left;
			border-bottom: 1px solid #eee;
		}
		.utm-table th {
			background: #f8f9fa;
			font-weight: 600;
		}
		.utm-table tr:hover {
			background: #f8f9fa;
		}
		.utm-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.utm-badge.active {
			background: #00a32a;
			color: #fff;
		}
		.utm-badge.paused {
			background: #dba617;
			color: #fff;
		}
		.utm-badge.archived {
			background: #646970;
			color: #fff;
		}
		.utm-test-section {
			background: #f0f6fc;
			border: 1px solid #0d5c99;
			padding: 20px;
			border-radius: 4px;
			margin: 20px 0;
		}
		.utm-test-url {
			background: #fff;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 3px;
			font-family: monospace;
			margin: 10px 0;
			word-break: break-all;
		}
		.utm-button {
			margin-right: 10px;
		}
		.utm-alert {
			padding: 12px 16px;
			border-left: 4px solid;
			margin: 15px 0;
			border-radius: 3px;
		}
		.utm-alert.success {
			background: #edfaef;
			border-left-color: #00a32a;
			color: #00761a;
		}
		.utm-alert.info {
			background: #f0f6fc;
			border-left-color: #2271b1;
			color: #135e96;
		}
		.utm-alert.warning {
			background: #fcf8e8;
			border-left-color: #dba617;
			color: #9d6e03;
		}
		.utm-alert.error {
			background: #fcf0f1;
			border-left-color: #d63638;
			color: #b32d2e;
		}
		";

		wp_add_inline_style( 'wp-admin', $css );
	}

	/**
	 * Afficher la page d'administration
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'utm-tracker' ) );
		}

		global $wpdb;

		// Récupérer les données
		$db_installer   = utm_tracker()->db_installer;
		$matcher        = utm_tracker()->matcher;
		$tag_applicator = utm_tracker()->tag_applicator;

		$tables_exist = $db_installer->tables_exist();
		$db_version   = $db_installer->get_db_version();

		// Stats globales
		$total_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}utm_campaigns" );
		$active_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}utm_campaigns WHERE status = 'active'" );
		$total_events = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}utm_events" );
		$total_user_tags = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}user_tags" );
		$unique_users_with_tags = $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}user_tags" );

		// Derniers événements
		$recent_events = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}utm_events ORDER BY created_at DESC LIMIT 5"
		);

		// Campagnes
		$campaigns = $matcher->get_all_campaigns();

		// Tags populaires
		$popular_tags = $tag_applicator->get_all_tags_with_counts();

		// Vérifications
		$checks = $this->run_system_checks();

		?>
		<div class="wrap utm-tracker-admin">
			<h1>
				<span class="dashicons dashicons-chart-line" style="font-size: 32px; margin-right: 10px;"></span>
				UTM Tracker - Dashboard
			</h1>

			<?php
			// Afficher les messages
			if ( isset( $_GET['test_success'] ) ) {
				echo '<div class="utm-alert success"><strong>✅ Succès !</strong> Campagne de test créée. Visitez l\'URL de test ci-dessous pour capturer les UTM.</div>';
			}
			if ( isset( $_GET['clear_success'] ) ) {
				echo '<div class="utm-alert success"><strong>✅ Nettoyé !</strong> Les données de test ont été supprimées.</div>';
			}
			?>

			<!-- Statut du Système -->
			<div class="utm-tracker-card">
				<h2>📊 Statut du Système</h2>

				<div class="utm-status-grid">
					<div class="utm-status-item <?php echo $tables_exist ? 'success' : 'error'; ?>">
						<div class="label">Tables SQL</div>
						<div class="value"><?php echo $tables_exist ? '✅ OK' : '❌ Manquantes'; ?></div>
					</div>

					<div class="utm-status-item <?php echo session_id() ? 'success' : 'error'; ?>">
						<div class="label">Session PHP</div>
						<div class="value"><?php echo session_id() ? '✅ Active' : '❌ Inactive'; ?></div>
					</div>

					<div class="utm-status-item success">
						<div class="label">Version Plugin</div>
						<div class="value"><?php echo esc_html( UTM_TRACKER_VERSION ); ?></div>
					</div>

					<div class="utm-status-item success">
						<div class="label">Version DB</div>
						<div class="value"><?php echo esc_html( $db_version ?: 'N/A' ); ?></div>
					</div>
				</div>

				<!-- Vérifications Système -->
				<h3>🔍 Vérifications</h3>
				<ul style="list-style: none; padding: 0;">
					<?php foreach ( $checks as $check ) : ?>
						<li style="margin: 10px 0;">
							<span style="font-size: 20px;"><?php echo $check['status'] ? '✅' : '❌'; ?></span>
							<strong><?php echo esc_html( $check['label'] ); ?></strong> :
							<?php echo esc_html( $check['message'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Statistiques Globales -->
			<div class="utm-tracker-card">
				<h2>📈 Statistiques Globales</h2>

				<div class="utm-status-grid">
					<div class="utm-status-item">
						<div class="label">Campagnes Totales</div>
						<div class="value"><?php echo esc_html( $total_campaigns ); ?></div>
						<small><?php echo esc_html( $active_campaigns ); ?> actives</small>
					</div>

					<div class="utm-status-item">
						<div class="label">Événements UTM</div>
						<div class="value"><?php echo esc_html( $total_events ); ?></div>
					</div>

					<div class="utm-status-item">
						<div class="label">Tags Appliqués</div>
						<div class="value"><?php echo esc_html( $total_user_tags ); ?></div>
					</div>

					<div class="utm-status-item">
						<div class="label">Utilisateurs Tagués</div>
						<div class="value"><?php echo esc_html( $unique_users_with_tags ); ?></div>
					</div>
				</div>
			</div>

			<!-- Zone de Test -->
			<div class="utm-tracker-card">
				<h2>🧪 Zone de Test</h2>

				<div class="utm-test-section">
					<h3>Créer une Campagne de Test</h3>
					<p>Crée automatiquement une campagne de test avec les tags : <code>test_tag</code>, <code>demo</code>, <code>google_lead</code></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'utm_tracker_test_campaign', 'utm_tracker_nonce' ); ?>
						<input type="hidden" name="action" value="utm_tracker_test_campaign">
						<button type="submit" class="button button-primary utm-button">
							<span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
							Créer Campagne de Test
						</button>
					</form>

					<?php
					// Vérifier si campagne de test existe
					$test_campaign = $wpdb->get_row(
						"SELECT * FROM {$wpdb->prefix}utm_campaigns WHERE utm_campaign = 'test_campaign' LIMIT 1"
					);

					if ( $test_campaign ) :
						$site_url = home_url();
						$test_url = add_query_arg(
							array(
								'utm_source'   => 'google',
								'utm_medium'   => 'cpc',
								'utm_campaign' => 'test_campaign',
							),
							$site_url
						);
						?>

						<div style="margin-top: 20px;">
							<h4>✅ Campagne de test active</h4>
							<p><strong>URL de Test :</strong></p>
							<div class="utm-test-url">
								<?php echo esc_html( $test_url ); ?>
							</div>
							<p>
								<a href="<?php echo esc_url( $test_url ); ?>" class="button" target="_blank">
									<span class="dashicons dashicons-external" style="vertical-align: middle;"></span>
									Ouvrir dans un nouvel onglet
								</a>
								<button onclick="navigator.clipboard.writeText('<?php echo esc_js( $test_url ); ?>'); alert('URL copiée !');" class="button">
									<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span>
									Copier l'URL
								</button>
							</p>

							<div class="utm-alert info">
								<strong>📝 Instructions :</strong>
								<ol>
									<li>Ouvre l'URL ci-dessus dans un <strong>onglet de navigation privée</strong></li>
									<li>Inscris un nouvel utilisateur</li>
									<li>Reviens ici et vérifie les statistiques ci-dessous</li>
								</ol>
							</div>
						</div>

					<?php endif; ?>
				</div>

				<div style="margin-top: 20px;">
					<h3>🗑️ Nettoyer les Données de Test</h3>
					<p>Supprime la campagne de test et tous les événements/tags associés.</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer les données de test ?');">
						<?php wp_nonce_field( 'utm_tracker_clear_test_data', 'utm_tracker_nonce' ); ?>
						<input type="hidden" name="action" value="utm_tracker_clear_test_data">
						<button type="submit" class="button">
							<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
							Nettoyer les Données de Test
						</button>
					</form>
				</div>
			</div>

			<!-- Campagnes -->
			<div class="utm-tracker-card">
				<h2>📋 Campagnes (<?php echo esc_html( $total_campaigns ); ?>)</h2>

				<?php if ( $campaigns ) : ?>
					<table class="utm-table">
						<thead>
							<tr>
								<th>ID</th>
								<th>Nom</th>
								<th>UTM</th>
								<th>Tags</th>
								<th>Statut</th>
								<th>Stats</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $campaigns as $campaign ) : ?>
								<?php
								$tags  = json_decode( $campaign->user_tags, true );
								$stats = utm_get_campaign_stats( $campaign->id );
								?>
								<tr>
									<td><?php echo esc_html( $campaign->id ); ?></td>
									<td><strong><?php echo esc_html( $campaign->name ); ?></strong></td>
									<td>
										<code><?php echo esc_html( $campaign->utm_source ); ?></code> /
										<code><?php echo esc_html( $campaign->utm_medium ); ?></code> /
										<code><?php echo esc_html( $campaign->utm_campaign ); ?></code>
									</td>
									<td>
										<?php
										if ( $tags ) {
											foreach ( $tags as $tag ) {
												echo '<span class="utm-badge" style="background: #ddd; color: #333; margin: 2px;">' . esc_html( $tag ) . '</span> ';
											}
										}
										?>
									</td>
									<td>
										<span class="utm-badge <?php echo esc_attr( $campaign->status ); ?>">
											<?php echo esc_html( $campaign->status ); ?>
										</span>
									</td>
									<td>
										<?php echo esc_html( $stats['visits'] ); ?> visites,
										<?php echo esc_html( $stats['conversions'] ); ?> conv.
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>Aucune campagne. Créez-en une avec le bouton ci-dessus !</p>
				<?php endif; ?>
			</div>

			<!-- Événements Récents -->
			<div class="utm-tracker-card">
				<h2>⏱️ Événements Récents (5 derniers)</h2>

				<?php if ( $recent_events ) : ?>
					<table class="utm-table">
						<thead>
							<tr>
								<th style="width: 150px;">Date</th>
								<th>UTM</th>
								<th style="width: 80px;">User ID</th>
								<th style="width: 200px;">Session</th>
								<th>Landing Page</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_events as $event ) : ?>
								<tr>
									<td><?php echo esc_html( $event->created_at ); ?></td>
									<td>
										<strong><?php echo esc_html( $event->utm_source ); ?></strong> /
										<?php echo esc_html( $event->utm_medium ); ?> /
										<?php echo esc_html( $event->utm_campaign ); ?>
									</td>
									<td><?php echo $event->user_id ? esc_html( $event->user_id ) : '<em>Anonyme</em>'; ?></td>
									<td><code style="font-size: 11px;"><?php echo esc_html( $event->session_id ); ?></code></td>
									<td><small><?php echo esc_html( $event->landing_page ); ?></small></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>Aucun événement UTM capturé pour le moment.</p>
				<?php endif; ?>
			</div>

			<!-- Tags Populaires -->
			<div class="utm-tracker-card">
				<h2>🏷️ Tags Populaires</h2>

				<?php if ( $popular_tags ) : ?>
					<table class="utm-table">
						<thead>
							<tr>
								<th>Tag</th>
								<th>Nombre d'Utilisateurs</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $popular_tags, 0, 10 ) as $tag ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $tag->tag_slug ); ?></strong></td>
									<td><?php echo esc_html( $tag->user_count ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>Aucun tag appliqué pour le moment.</p>
				<?php endif; ?>
			</div>

			<!-- Documentation -->
			<div class="utm-tracker-card">
				<h2>📚 Documentation</h2>
				<p>
					<a href="https://github.com/cyrilgodon/utm-tracker" class="button" target="_blank">
						<span class="dashicons dashicons-book" style="vertical-align: middle;"></span>
						Voir la Documentation Complète
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Vérifications système
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function run_system_checks() {
		global $wpdb;

		$checks = array();

		// Check 1 : Tables
		$tables_exist = utm_tracker()->db_installer->tables_exist();
		$checks[]     = array(
			'label'   => 'Tables SQL',
			'status'  => $tables_exist,
			'message' => $tables_exist ? 'Les 3 tables sont créées' : 'Tables manquantes',
		);

		// Check 2 : Session PHP
		$session_active = (bool) session_id();
		$checks[]       = array(
			'label'   => 'Session PHP',
			'status'  => $session_active,
			'message' => $session_active ? 'Session active (ID: ' . session_id() . ')' : 'Session non démarrée',
		);

		// Check 3 : Fonctions helper
		$helpers_loaded = function_exists( 'utm_add_campaign' );
		$checks[]       = array(
			'label'   => 'Fonctions Helper',
			'status'  => $helpers_loaded,
			'message' => $helpers_loaded ? 'Toutes les fonctions sont chargées' : 'Fonctions non chargées',
		);

		// Check 4 : Campagnes
		$has_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}utm_campaigns" ) > 0;
		$checks[]      = array(
			'label'   => 'Campagnes',
			'status'  => $has_campaigns,
			'message' => $has_campaigns ? 'Au moins 1 campagne configurée' : 'Aucune campagne (créez-en une ci-dessous)',
		);

		return $checks;
	}

	/**
	 * Gérer la création de campagne de test
	 *
	 * @since 1.0.0
	 */
	public function handle_test_campaign() {
		// Vérifier les permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permissions insuffisantes' );
		}

		// Vérifier le nonce
		check_admin_referer( 'utm_tracker_test_campaign', 'utm_tracker_nonce' );

		// Créer la campagne de test
		$campaign_id = utm_add_campaign(
			array(
				'name'         => 'Test Campaign - Google Ads',
				'utm_source'   => 'google',
				'utm_medium'   => 'cpc',
				'utm_campaign' => 'test_campaign',
				'user_tags'    => array( 'test_tag', 'demo', 'google_lead' ),
				'status'       => 'active',
			)
		);

		// Redirection
		wp_safe_redirect(
			add_query_arg(
				'test_success',
				'1',
				admin_url( 'admin.php?page=utm-tracker' )
			)
		);
		exit;
	}

	/**
	 * Gérer le nettoyage des données de test
	 *
	 * @since 1.0.0
	 */
	public function handle_clear_test_data() {
		// Vérifier les permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permissions insuffisantes' );
		}

		// Vérifier le nonce
		check_admin_referer( 'utm_tracker_clear_test_data', 'utm_tracker_nonce' );

		global $wpdb;

		// Supprimer la campagne de test
		$wpdb->delete(
			$wpdb->prefix . 'utm_campaigns',
			array( 'utm_campaign' => 'test_campaign' ),
			array( '%s' )
		);

		// Supprimer les événements de test
		$wpdb->delete(
			$wpdb->prefix . 'utm_events',
			array( 'utm_campaign' => 'test_campaign' ),
			array( '%s' )
		);

		// Supprimer les tags de test
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}user_tags WHERE tag_slug IN ('test_tag', 'demo', 'google_lead')"
		);

		// Redirection
		wp_safe_redirect(
			add_query_arg(
				'clear_success',
				'1',
				admin_url( 'admin.php?page=utm-tracker' )
			)
		);
		exit;
	}

	/**
	 * Page : Liste des Campagnes
	 *
	 * @since 1.0.0
	 */
	public function render_campaigns_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'utm-tracker' ) );
		}

		global $wpdb;
		$matcher = utm_tracker()->matcher;

		// Messages
		if ( isset( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Campagne supprimée avec succès.</strong></p></div>';
		}
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Campagne enregistrée avec succès.</strong></p></div>';
		}
		if ( isset( $_GET['duplicated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Campagne dupliquée.</strong> N\'oubliez pas de vérifier les UTM avant activation.</p></div>';
		}
		if ( isset( $_GET['duplicate_error'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p><strong>❌ Duplication impossible.</strong> Vérifiez que la combinaison UTM n\'existe pas déjà.</p></div>';
		}

		// Récupérer toutes les campagnes
		$campaigns = $matcher->get_all_campaigns();

		?>
		<div class="wrap utm-tracker-admin">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-megaphone" style="font-size: 32px; margin-right: 10px;"></span>
				Toutes les Campagnes
			</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=utm-tracker-add-campaign' ) ); ?>" class="page-title-action">
				<span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
				Ajouter une Campagne
			</a>
			<hr class="wp-header-end">

			<div class="utm-tracker-card" style="margin-top: 20px;">
				<?php if ( $campaigns ) : ?>
					<table class="wp-list-table widefat fixed striped">
					<thead>
							<tr>
								<th style="width: 50px;">ID</th>
								<th>Nom</th>
								<th>Source</th>
								<th>Medium</th>
								<th>Campagne</th>
								<th>Tags</th>
								<th>Statut</th>
								<th>Stats</th>
							<th style="width: 220px;">Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $campaigns as $campaign ) : ?>
								<?php
								$tags  = json_decode( $campaign->user_tags, true );
								$stats = utm_get_campaign_stats( $campaign->id );
								?>
								<tr>
									<td><strong><?php echo esc_html( $campaign->id ); ?></strong></td>
									<td><strong><?php echo esc_html( $campaign->name ); ?></strong></td>
									<td><code><?php echo esc_html( $campaign->utm_source ); ?></code></td>
									<td><code><?php echo esc_html( $campaign->utm_medium ); ?></code></td>
									<td><code><?php echo esc_html( $campaign->utm_campaign ); ?></code></td>
									<td>
										<?php
										if ( $tags ) {
											foreach ( $tags as $tag ) {
												echo '<span class="utm-badge" style="background: #ddd; color: #333; margin: 2px;">' . esc_html( $tag ) . '</span> ';
											}
										} else {
											echo '<em>Aucun tag</em>';
										}
										?>
									</td>
									<td>
										<span class="utm-badge <?php echo esc_attr( $campaign->status ); ?>">
											<?php echo esc_html( $campaign->status ); ?>
										</span>
									</td>
									<td>
										<strong><?php echo esc_html( $stats['visits'] ); ?></strong> visites<br>
										<strong><?php echo esc_html( $stats['conversions'] ); ?></strong> conversions
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=utm-tracker-edit-campaign&id=' . $campaign->id ) ); ?>" class="button button-small">
											<span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
											Éditer
										</a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
										<?php wp_nonce_field( 'utm_tracker_duplicate_campaign', 'utm_tracker_nonce' ); ?>
										<input type="hidden" name="action" value="utm_tracker_duplicate_campaign">
										<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $campaign->id ); ?>">
										<button type="submit" class="button button-small">
											<span class="dashicons dashicons-admin-page" style="vertical-align: middle;"></span>
											Dupliquer
										</button>
									</form>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?');">
											<?php wp_nonce_field( 'utm_tracker_delete_campaign', 'utm_tracker_nonce' ); ?>
											<input type="hidden" name="action" value="utm_tracker_delete_campaign">
											<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $campaign->id ); ?>">
											<button type="submit" class="button button-small button-link-delete" style="color: #b32d2e;">
												<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
												Supprimer
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="utm-alert info">
						<p><strong>Aucune campagne pour le moment.</strong></p>
						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=utm-tracker-add-campaign' ) ); ?>" class="button button-primary">
								<span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
								Créer votre première campagne
							</a>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Page : Ajouter une Campagne
	 *
	 * @since 1.0.0
	 */
	public function render_add_campaign_page() {
		$this->render_campaign_form();
	}

	/**
	 * Page : Éditer une Campagne
	 *
	 * @since 1.0.0
	 */
	public function render_edit_campaign_page() {
		if ( ! isset( $_GET['id'] ) ) {
			wp_die( 'ID de campagne manquant' );
		}

		$campaign_id = intval( $_GET['id'] );
		$campaign    = utm_get_campaign( $campaign_id );

		if ( ! $campaign ) {
			wp_die( 'Campagne introuvable' );
		}

		$this->render_campaign_form( $campaign );
	}

	/**
	 * Formulaire de Campagne (Création/Édition)
	 *
	 * @since 1.0.0
	 * @param object|null $campaign Campagne à éditer (null pour création).
	 */
	private function render_campaign_form( $campaign = null ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'utm-tracker' ) );
		}

		$is_edit = ! empty( $campaign );
		$title   = $is_edit ? 'Éditer la Campagne' : 'Ajouter une Campagne';

		// Valeurs par défaut
		$name         = $is_edit ? $campaign->name : '';
		$utm_source   = $is_edit ? $campaign->utm_source : '';
		$utm_medium   = $is_edit ? $campaign->utm_medium : '';
		$utm_campaign = $is_edit ? $campaign->utm_campaign : '';
		$utm_content  = $is_edit ? $campaign->utm_content : '';
		$utm_term     = $is_edit ? $campaign->utm_term : '';
		$user_tags    = $is_edit ? json_decode( $campaign->user_tags, true ) : array();
		$status       = $is_edit ? $campaign->status : 'active';

		// Si création et paramètres de pré-remplissage dans l'URL (depuis UTM Non Matchés)
		if ( ! $is_edit ) {
			if ( isset( $_GET['prefill_source'] ) && ! empty( $_GET['prefill_source'] ) ) {
				$utm_source = sanitize_text_field( wp_unslash( $_GET['prefill_source'] ) );
			}
			if ( isset( $_GET['prefill_medium'] ) && ! empty( $_GET['prefill_medium'] ) ) {
				$utm_medium = sanitize_text_field( wp_unslash( $_GET['prefill_medium'] ) );
			}
			if ( isset( $_GET['prefill_campaign'] ) && ! empty( $_GET['prefill_campaign'] ) ) {
				$utm_campaign = sanitize_text_field( wp_unslash( $_GET['prefill_campaign'] ) );
			}
			if ( isset( $_GET['prefill_content'] ) && ! empty( $_GET['prefill_content'] ) ) {
				$utm_content = sanitize_text_field( wp_unslash( $_GET['prefill_content'] ) );
			}
			if ( isset( $_GET['prefill_term'] ) && ! empty( $_GET['prefill_term'] ) ) {
				$utm_term = sanitize_text_field( wp_unslash( $_GET['prefill_term'] ) );
			}
			// Générer un nom par défaut
			if ( $utm_source && $utm_medium && $utm_campaign ) {
				$name = ucfirst( $utm_source ) . ' - ' . ucfirst( $utm_medium ) . ' - ' . ucfirst( str_replace( '_', ' ', $utm_campaign ) );
			}
		}

		// Générer l'URL de prévisualisation
		$preview_url = home_url() . '?utm_source=' . urlencode( $utm_source ) . '&utm_medium=' . urlencode( $utm_medium ) . '&utm_campaign=' . urlencode( $utm_campaign );
		if ( $utm_content ) {
			$preview_url .= '&utm_content=' . urlencode( $utm_content );
		}
		if ( $utm_term ) {
			$preview_url .= '&utm_term=' . urlencode( $utm_term );
		}

		?>
		<div class="wrap utm-tracker-admin">
			<h1>
				<span class="dashicons dashicons-<?php echo $is_edit ? 'edit' : 'plus-alt'; ?>" style="font-size: 32px; margin-right: 10px;"></span>
				<?php echo esc_html( $title ); ?>
			</h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="utm-campaign-form">
				<?php wp_nonce_field( 'utm_tracker_save_campaign', 'utm_tracker_nonce' ); ?>
				<input type="hidden" name="action" value="utm_tracker_save_campaign">
				<?php if ( $is_edit ) : ?>
					<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $campaign->id ); ?>">
				<?php endif; ?>

				<div class="utm-tracker-card">
					<h2>📝 Informations de la Campagne</h2>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="campaign_name">Nom de la Campagne <span style="color: red;">*</span></label>
								</th>
								<td>
									<input type="text" id="campaign_name" name="campaign_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required>
									<p class="description">Nom descriptif pour identifier facilement cette campagne (ex: "Google Ads Q1 2025")</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="utm_source">UTM Source <span style="color: red;">*</span></label>
								</th>
								<td>
									<input type="text" id="utm_source" name="utm_source" value="<?php echo esc_attr( $utm_source ); ?>" class="regular-text" required oninput="updatePreview()">
									<p class="description">Exemple : google, facebook, newsletter</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="utm_medium">UTM Medium <span style="color: red;">*</span></label>
								</th>
								<td>
									<input type="text" id="utm_medium" name="utm_medium" value="<?php echo esc_attr( $utm_medium ); ?>" class="regular-text" required oninput="updatePreview()">
									<p class="description">Exemple : cpc, organic, email</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="utm_campaign">UTM Campaign <span style="color: red;">*</span></label>
								</th>
								<td>
									<input type="text" id="utm_campaign" name="utm_campaign" value="<?php echo esc_attr( $utm_campaign ); ?>" class="regular-text" required oninput="updatePreview()">
									<p class="description">Exemple : spring_sale, coaching_q1</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="utm_content">UTM Content</label>
								</th>
								<td>
									<input type="text" id="utm_content" name="utm_content" value="<?php echo esc_attr( $utm_content ); ?>" class="regular-text" oninput="updatePreview()">
									<p class="description">Optionnel : variante de l'annonce (ex: banner_a)</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="utm_term">UTM Term</label>
								</th>
								<td>
									<input type="text" id="utm_term" name="utm_term" value="<?php echo esc_attr( $utm_term ); ?>" class="regular-text" oninput="updatePreview()">
									<p class="description">Optionnel : mots-clés (ex: coach professionnel)</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="utm-tracker-card">
					<h2>🏷️ Tags à Appliquer Automatiquement</h2>

					<p>Ces tags seront appliqués automatiquement aux utilisateurs qui s'inscrivent via cette campagne.</p>

					<div id="tags-container" style="margin: 15px 0;">
						<?php if ( $user_tags ) : ?>
							<?php foreach ( $user_tags as $index => $tag ) : ?>
								<div class="tag-input-row" style="margin-bottom: 10px;">
									<input type="text" name="user_tags[]" value="<?php echo esc_attr( $tag ); ?>" class="regular-text" placeholder="tag_slug" required>
									<button type="button" class="button" onclick="removeTag(this)">
										<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
										Retirer
									</button>
								</div>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="tag-input-row" style="margin-bottom: 10px;">
								<input type="text" name="user_tags[]" class="regular-text" placeholder="tag_slug" required>
								<button type="button" class="button" onclick="removeTag(this)">
									<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
									Retirer
								</button>
							</div>
						<?php endif; ?>
					</div>

					<button type="button" class="button" onclick="addTag()">
						<span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
						Ajouter un Tag
					</button>

					<p class="description" style="margin-top: 10px;">
						<strong>Format :</strong> Utilisez des slugs en minuscules avec underscores (ex: lead_google, coaching, premium)
					</p>
				</div>

				<div class="utm-tracker-card">
					<h2>⚙️ Configuration</h2>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="campaign_status">Statut</label>
								</th>
								<td>
									<select id="campaign_status" name="campaign_status" class="regular-text">
										<option value="active" <?php selected( $status, 'active' ); ?>>Active</option>
										<option value="paused" <?php selected( $status, 'paused' ); ?>>En Pause</option>
										<option value="archived" <?php selected( $status, 'archived' ); ?>>Archivée</option>
									</select>
									<p class="description">Seules les campagnes actives effectuent le matching</p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="utm-tracker-card">
					<h2>🔗 URL de Prévisualisation</h2>

					<div class="utm-test-url" id="preview-url">
						<?php echo esc_html( $preview_url ); ?>
					</div>

					<button type="button" class="button" onclick="copyPreviewUrl()">
						<span class="dashicons dashicons-clipboard" style="vertical-align: middle;"></span>
						Copier l'URL
					</button>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
						<?php echo $is_edit ? 'Mettre à Jour la Campagne' : 'Créer la Campagne'; ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=utm-tracker-campaigns' ) ); ?>" class="button button-large">
						Annuler
					</a>
				</p>
			</form>
		</div>

		<script>
		function addTag() {
			const container = document.getElementById('tags-container');
			const div = document.createElement('div');
			div.className = 'tag-input-row';
			div.style.marginBottom = '10px';
			div.innerHTML = `
				<input type="text" name="user_tags[]" class="regular-text" placeholder="tag_slug" required>
				<button type="button" class="button" onclick="removeTag(this)">
					<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
					Retirer
				</button>
			`;
			container.appendChild(div);
		}

		function removeTag(button) {
			const container = document.getElementById('tags-container');
			if (container.children.length > 1) {
				button.parentElement.remove();
			} else {
				alert('Au moins un tag est requis');
			}
		}

		function updatePreview() {
			const source = document.getElementById('utm_source').value;
			const medium = document.getElementById('utm_medium').value;
			const campaign = document.getElementById('utm_campaign').value;
			const content = document.getElementById('utm_content').value;
			const term = document.getElementById('utm_term').value;

			let url = '<?php echo home_url(); ?>?utm_source=' + encodeURIComponent(source) +
					  '&utm_medium=' + encodeURIComponent(medium) +
					  '&utm_campaign=' + encodeURIComponent(campaign);

			if (content) url += '&utm_content=' + encodeURIComponent(content);
			if (term) url += '&utm_term=' + encodeURIComponent(term);

			document.getElementById('preview-url').textContent = url;
		}

		function copyPreviewUrl() {
			const url = document.getElementById('preview-url').textContent;
			navigator.clipboard.writeText(url).then(() => {
				alert('URL copiée dans le presse-papier !');
			});
		}
		</script>
		<?php
	}

	/**
	 * Gérer la sauvegarde d'une campagne
	 *
	 * @since 1.0.0
	 */
	public function handle_save_campaign() {
		// Vérifier les permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permissions insuffisantes' );
		}

		// Vérifier le nonce
		check_admin_referer( 'utm_tracker_save_campaign', 'utm_tracker_nonce' );

		// Récupérer les données
		$campaign_id  = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		$name         = sanitize_text_field( $_POST['campaign_name'] );
		$utm_source   = sanitize_text_field( $_POST['utm_source'] );
		$utm_medium   = sanitize_text_field( $_POST['utm_medium'] );
		$utm_campaign = sanitize_text_field( $_POST['utm_campaign'] );
		$utm_content  = sanitize_text_field( $_POST['utm_content'] );
		$utm_term     = sanitize_text_field( $_POST['utm_term'] );
		$user_tags    = isset( $_POST['user_tags'] ) ? array_map( 'sanitize_text_field', $_POST['user_tags'] ) : array();
		$status       = sanitize_text_field( $_POST['campaign_status'] );

		// Filtrer les tags vides
		$user_tags = array_filter( $user_tags );

		if ( empty( $user_tags ) ) {
			wp_die( 'Au moins un tag est requis' );
		}

		// Données de la campagne
		$campaign_data = array(
			'name'         => $name,
			'utm_source'   => strtolower( $utm_source ),
			'utm_medium'   => strtolower( $utm_medium ),
			'utm_campaign' => strtolower( $utm_campaign ),
			'utm_content'  => $utm_content,
			'utm_term'     => $utm_term,
			'user_tags'    => $user_tags,
			'status'       => $status,
		);

	// Créer ou mettre à jour
	if ( $campaign_id > 0 ) {
		// Mise à jour
		$result = utm_update_campaign( $campaign_id, $campaign_data );
	} else {
		// Création
		$result = utm_add_campaign( $campaign_data );
	}

	// Redirection avec gestion d'erreur détaillée
	if ( $result ) {
		wp_safe_redirect(
			add_query_arg(
				'saved',
				'1',
				admin_url( 'admin.php?page=utm-tracker-campaigns' )
			)
		);
	} else {
		// Vérifier si c'est une erreur de duplicate
		global $wpdb;
		$last_error = $wpdb->last_error;
		
		if ( strpos( $last_error, 'Duplicate entry' ) !== false && strpos( $last_error, 'unique_utm' ) !== false ) {
			// Extraire la combinaison UTM de l'erreur
			preg_match( "/'([^']+)' for key 'wp_utm_campaigns\.unique_utm'/", $last_error, $matches );
			$duplicate_key = isset( $matches[1] ) ? $matches[1] : 'inconnue';
			
			wp_die(
				sprintf(
					'<h1>❌ Erreur : Campagne existante</h1><p>Une campagne avec la combinaison <strong>%s</strong> existe déjà.</p><p>Chaque combinaison source/medium/campaign doit être unique.</p><p><a href="%s" class="button button-primary">← Retour</a></p>',
					esc_html( $duplicate_key ),
					esc_url( admin_url( 'admin.php?page=utm-tracker-campaigns' ) )
				),
				'Campagne existante',
				array( 'back_link' => true )
			);
		} else {
			// Erreur générique avec détails
			wp_die(
				sprintf(
					'<h1>❌ Erreur lors de la sauvegarde</h1><p>Erreur technique : <code>%s</code></p><p><a href="%s" class="button button-primary">← Retour</a></p>',
					esc_html( $last_error ?: 'Inconnue' ),
					esc_url( admin_url( 'admin.php?page=utm-tracker-campaigns' ) )
				),
				'Erreur de sauvegarde',
				array( 'back_link' => true )
			);
		}
	}

	exit;
	}

	/**
	 * Gérer la suppression d'une campagne
	 *
	 * @since 1.0.0
	 */
	public function handle_delete_campaign() {
		// Vérifier les permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permissions insuffisantes' );
		}

		// Vérifier le nonce
		check_admin_referer( 'utm_tracker_delete_campaign', 'utm_tracker_nonce' );

		// Récupérer l'ID
		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;

		if ( $campaign_id > 0 ) {
			utm_delete_campaign( $campaign_id );
		}

		// Redirection
		wp_safe_redirect(
			add_query_arg(
				'deleted',
				'1',
				admin_url( 'admin.php?page=utm-tracker-campaigns' )
			)
		);
		exit;
	}

	/**
	 * Dupliquer une campagne existante
	 *
	 * @since 1.0.0
	 */
	public function handle_duplicate_campaign() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permissions insuffisantes.', 'utm-tracker' ) );
		}

		check_admin_referer( 'utm_tracker_duplicate_campaign', 'utm_tracker_nonce' );

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		if ( $campaign_id <= 0 ) {
			wp_safe_redirect( add_query_arg( 'duplicate_error', '1', admin_url( 'admin.php?page=utm-tracker-campaigns' ) ) );
			exit;
		}

		$campaign = utm_get_campaign( $campaign_id );
		if ( ! $campaign ) {
			wp_safe_redirect( add_query_arg( 'duplicate_error', '1', admin_url( 'admin.php?page=utm-tracker-campaigns' ) ) );
			exit;
		}

		$matcher = utm_tracker()->matcher;
		$source  = $campaign->utm_source;
		$medium  = $campaign->utm_medium;
		$base_slug = $campaign->utm_campaign;
		$content = $campaign->utm_content;

		$new_slug = $base_slug . '-copy';
		$counter  = 1;
		while ( $matcher->campaign_exists( $source, $medium, $new_slug, $content ) ) {
			$counter++;
			$new_slug = $base_slug . '-copy-' . $counter;
		}

		$new_name = $campaign->name . ' (Copie' . ( $counter > 1 ? ' ' . $counter : '' ) . ')';
		$user_tags = json_decode( $campaign->user_tags, true );
		if ( ! is_array( $user_tags ) ) {
			$user_tags = array();
		}

		$new_campaign_id = utm_add_campaign( array(
			'name'         => $new_name,
			'utm_source'   => $source,
			'utm_medium'   => $medium,
			'utm_campaign' => $new_slug,
			'utm_content'  => $campaign->utm_content,
			'utm_term'     => $campaign->utm_term,
			'user_tags'    => $user_tags,
			'status'       => 'paused',
		) );

		if ( $new_campaign_id ) {
			wp_safe_redirect( add_query_arg( 'duplicated', '1', admin_url( 'admin.php?page=utm-tracker-campaigns' ) ) );
		} else {
			wp_safe_redirect( add_query_arg( 'duplicate_error', '1', admin_url( 'admin.php?page=utm-tracker-campaigns' ) ) );
		}
		exit;
	}

	/**
	 * Page : UTM Non Matchés
	 *
	 * @since 1.0.0
	 */
	public function render_unmatched_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n\'avez pas les permissions nécessaires.', 'utm-tracker' ) );
		}

		global $wpdb;
		$table_events = $wpdb->prefix . 'utm_events';

		// Récupérer les combinaisons UTM uniques sans campagne matchée
		$unmatched_utms = $wpdb->get_results(
			"SELECT 
				utm_source, 
				utm_medium, 
				utm_campaign,
				utm_content,
				utm_term,
				COUNT(*) as event_count,
				COUNT(DISTINCT user_id) as user_count,
				MIN(created_at) as first_seen,
				MAX(created_at) as last_seen
			FROM {$table_events}
			WHERE campaign_id IS NULL
				AND utm_source IS NOT NULL
				AND utm_medium IS NOT NULL
				AND utm_campaign IS NOT NULL
			GROUP BY utm_source, utm_medium, utm_campaign, utm_content, utm_term
			ORDER BY event_count DESC, last_seen DESC"
		);

		?>
		<div class="wrap utm-tracker-admin">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-warning" style="font-size: 32px; margin-right: 10px; color: #f0ad4e;"></span>
				UTM Non Matchés
			</h1>
			<hr class="wp-header-end">

			<div class="utm-alert warning" style="margin-top: 20px;">
				<p><strong>ℹ️ Ces UTM ont été capturés mais n'ont pas de campagne correspondante.</strong></p>
				<p>Vous pouvez créer une campagne pour les attribuer automatiquement aux futurs utilisateurs.</p>
			</div>

			<div class="utm-tracker-card" style="margin-top: 20px;">
				<?php if ( $unmatched_utms ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th>Source</th>
								<th>Medium</th>
								<th>Campaign</th>
								<th>Content</th>
								<th>Term</th>
								<th>Événements</th>
								<th>Utilisateurs</th>
								<th>Première Visite</th>
								<th>Dernière Visite</th>
								<th style="width: 150px;">Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $unmatched_utms as $utm ) : ?>
								<tr>
									<td><code><?php echo esc_html( $utm->utm_source ); ?></code></td>
									<td><code><?php echo esc_html( $utm->utm_medium ); ?></code></td>
									<td><code><?php echo esc_html( $utm->utm_campaign ); ?></code></td>
									<td><?php echo $utm->utm_content ? '<code>' . esc_html( $utm->utm_content ) . '</code>' : '<em>-</em>'; ?></td>
									<td><?php echo $utm->utm_term ? '<code>' . esc_html( $utm->utm_term ) . '</code>' : '<em>-</em>'; ?></td>
									<td><strong><?php echo esc_html( $utm->event_count ); ?></strong></td>
									<td><strong><?php echo esc_html( $utm->user_count ); ?></strong></td>
									<td><?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime( $utm->first_seen ) ) ); ?></td>
									<td><?php echo esc_html( date_i18n( 'Y-m-d H:i', strtotime( $utm->last_seen ) ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=utm-tracker-add-campaign&prefill_source=' . urlencode( $utm->utm_source ) . '&prefill_medium=' . urlencode( $utm->utm_medium ) . '&prefill_campaign=' . urlencode( $utm->utm_campaign ) . '&prefill_content=' . urlencode( $utm->utm_content ) . '&prefill_term=' . urlencode( $utm->utm_term ) ) ); ?>" class="button button-primary button-small">
											<span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
											Créer Campagne
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin-top: 20px;">
						<p><strong>Total : <?php echo count( $unmatched_utms ); ?> combinaison(s) UTM non matchée(s)</strong></p>
					</div>
				<?php else : ?>
					<div class="utm-alert success">
						<p><strong>✅ Parfait ! Tous les UTM sont matchés avec des campagnes.</strong></p>
						<p>Aucun UTM non matché pour le moment.</p>
					</div>
				<?php endif; ?>
			</div>

			<div class="utm-tracker-card" style="margin-top: 20px;">
				<h2>📊 Statistiques</h2>
				<?php
				$total_events = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_events}" );
				$matched_events = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_events} WHERE campaign_id IS NOT NULL" );
				$unmatched_events = $total_events - $matched_events;
				$match_rate = $total_events > 0 ? round( ( $matched_events / $total_events ) * 100, 1 ) : 0;
				?>
				<table class="form-table">
					<tr>
						<th>Total d'événements :</th>
						<td><strong><?php echo esc_html( $total_events ); ?></strong></td>
					</tr>
					<tr>
						<th>Événements matchés :</th>
						<td><strong style="color: green;"><?php echo esc_html( $matched_events ); ?></strong></td>
					</tr>
					<tr>
						<th>Événements non matchés :</th>
						<td><strong style="color: orange;"><?php echo esc_html( $unmatched_events ); ?></strong></td>
					</tr>
					<tr>
						<th>Taux de matching :</th>
						<td>
							<strong style="color: <?php echo $match_rate >= 80 ? 'green' : ( $match_rate >= 50 ? 'orange' : 'red' ); ?>;">
								<?php echo esc_html( $match_rate ); ?>%
							</strong>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}
}

