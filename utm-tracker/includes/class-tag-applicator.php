<?php
/**
 * Applicateur de tags utilisateur
 *
 * Applique les tags configurés dans une campagne aux utilisateurs.
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe UTM_Tag_Applicator
 *
 * @since 1.0.0
 */
class UTM_Tag_Applicator {

	/**
	 * Appliquer les tags d'une campagne à un utilisateur
	 *
	 * @since 1.0.0
	 * @param int    $user_id  ID de l'utilisateur.
	 * @param object $campaign Objet campagne contenant les tags.
	 * @return int Nombre de tags appliqués
	 */
	public function apply_tags_to_user( $user_id, $campaign ) {
		// Vérifier que l'utilisateur existe
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return 0;
		}

		// Vérifier que la campagne a des tags
		if ( empty( $campaign->user_tags ) ) {
			return 0;
		}

		// Décoder les tags JSON
		$tags = json_decode( $campaign->user_tags, true );
		if ( ! is_array( $tags ) || empty( $tags ) ) {
			return 0;
		}

		global $wpdb;
		$table_user_tags = $wpdb->prefix . 'user_tags';
		$applied_count   = 0;

		// Appliquer chaque tag
		foreach ( $tags as $tag_slug ) {
			// Sanitize le tag
			$tag_slug = sanitize_title( $tag_slug );
			if ( empty( $tag_slug ) ) {
				continue;
			}

			// Vérifier si le tag existe déjà pour cet utilisateur
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_user_tags} WHERE user_id = %d AND tag_slug = %s LIMIT 1",
					$user_id,
					$tag_slug
				)
			);

			// Si le tag n'existe pas, l'ajouter
			if ( ! $existing ) {
				$result = $wpdb->insert(
					$table_user_tags,
					array(
						'user_id'     => $user_id,
						'tag_slug'    => $tag_slug,
						'campaign_id' => $campaign->id,
						'applied_at'  => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%d', '%s' )
				);

				if ( $result ) {
					$applied_count++;
				}
			}
		}

		// Log pour debug
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $applied_count > 0 ) {
			error_log( '[UTM Tracker] ' . $applied_count . ' tag(s) appliqué(s) à l\'utilisateur ' . $user_id . ' depuis la campagne ' . $campaign->name );
		}

		// Hook après application des tags
		do_action( 'utm_tracker_tags_applied', $user_id, $tags, $campaign->id );

		return $applied_count;
	}

	/**
	 * Retirer un tag d'un utilisateur
	 *
	 * @since 1.0.0
	 * @param int    $user_id  ID de l'utilisateur.
	 * @param string $tag_slug Slug du tag à retirer.
	 * @return bool True si supprimé, false sinon
	 */
	public function remove_tag_from_user( $user_id, $tag_slug ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';
		$tag_slug        = sanitize_title( $tag_slug );

		$result = $wpdb->delete(
			$table_user_tags,
			array(
				'user_id'  => $user_id,
				'tag_slug' => $tag_slug,
			),
			array( '%d', '%s' )
		);

		return (bool) $result;
	}

	/**
	 * Obtenir tous les tags d'un utilisateur
	 *
	 * @since 1.0.0
	 * @param int $user_id ID de l'utilisateur.
	 * @return array Liste des tags (slugs)
	 */
	public function get_user_tags( $user_id ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tag_slug FROM {$table_user_tags} WHERE user_id = %d ORDER BY applied_at DESC",
				$user_id
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Obtenir les détails complets des tags d'un utilisateur
	 *
	 * @since 1.0.0
	 * @param int $user_id ID de l'utilisateur.
	 * @return array Liste des objets tag (avec campaign_id, applied_at, etc.)
	 */
	public function get_user_tags_details( $user_id ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_user_tags} WHERE user_id = %d ORDER BY applied_at DESC",
				$user_id
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Vérifier si un utilisateur a un tag spécifique
	 *
	 * @since 1.0.0
	 * @param int    $user_id  ID de l'utilisateur.
	 * @param string $tag_slug Slug du tag.
	 * @return bool True si l'utilisateur a le tag, false sinon
	 */
	public function user_has_tag( $user_id, $tag_slug ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';
		$tag_slug        = sanitize_title( $tag_slug );

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_user_tags} WHERE user_id = %d AND tag_slug = %s LIMIT 1",
				$user_id,
				$tag_slug
			)
		);

		return (bool) $exists;
	}

	/**
	 * Obtenir tous les utilisateurs ayant un tag spécifique
	 *
	 * @since 1.0.0
	 * @param string $tag_slug Slug du tag.
	 * @return array Liste des IDs utilisateur
	 */
	public function get_users_by_tag( $tag_slug ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';
		$tag_slug        = sanitize_title( $tag_slug );

		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$table_user_tags} WHERE tag_slug = %s ORDER BY applied_at DESC",
				$tag_slug
			)
		);

		return $user_ids ? $user_ids : array();
	}

	/**
	 * Compter le nombre d'utilisateurs ayant un tag
	 *
	 * @since 1.0.0
	 * @param string $tag_slug Slug du tag.
	 * @return int Nombre d'utilisateurs
	 */
	public function count_users_by_tag( $tag_slug ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';
		$tag_slug        = sanitize_title( $tag_slug );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$table_user_tags} WHERE tag_slug = %s",
				$tag_slug
			)
		);

		return (int) $count;
	}

	/**
	 * Obtenir tous les tags distincts utilisés
	 *
	 * @since 1.0.0
	 * @return array Liste des tags avec leur nombre d'utilisateurs
	 */
	public function get_all_tags_with_counts() {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';

		$results = $wpdb->get_results(
			"SELECT tag_slug, COUNT(DISTINCT user_id) as user_count 
			FROM {$table_user_tags} 
			GROUP BY tag_slug 
			ORDER BY user_count DESC, tag_slug ASC"
		);

		return $results ? $results : array();
	}

	/**
	 * Supprimer tous les tags d'un utilisateur
	 *
	 * @since 1.0.0
	 * @param int $user_id ID de l'utilisateur.
	 * @return int Nombre de tags supprimés
	 */
	public function remove_all_user_tags( $user_id ) {
		global $wpdb;

		$table_user_tags = $wpdb->prefix . 'user_tags';

		$result = $wpdb->delete(
			$table_user_tags,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		return $result ? (int) $result : 0;
	}
}

