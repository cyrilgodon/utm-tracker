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
								<th>Date</th>
								<th>UTM</th>
								<th>User ID</th>
								<th>Session</th>
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
									<td><code><?php echo esc_html( substr( $event->session_id, 0, 8 ) ); ?>...</code></td>
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
}

