<?php
/**
 * Fonctions helper pour UTM Tracker
 *
 * Fonctions globales pratiques pour interagir avec le plugin.
 *
 * @package UTM_Tracker
 * @since   1.0.0
 */

// Si accédé directement, on arrête
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajouter une nouvelle campagne UTM
 *
 * @since 1.0.0
 * @param array $args Arguments de la campagne.
 * @return int|false ID de la campagne créée ou false en cas d'erreur
 */
function utm_add_campaign( $args ) {
	global $wpdb;

	// Arguments par défaut
	$defaults = array(
		'name'         => '',
		'utm_source'   => '',
		'utm_medium'   => '',
		'utm_campaign' => '',
		'utm_content'  => '',
		'utm_term'     => '',
		'user_tags'    => array(),
		'status'       => 'active',
	);

	$args = wp_parse_args( $args, $defaults );

	// Validation
	if ( empty( $args['name'] ) || empty( $args['utm_source'] ) || empty( $args['utm_medium'] ) || empty( $args['utm_campaign'] ) ) {
		return false;
	}

	// Normaliser les tags
	if ( is_array( $args['user_tags'] ) ) {
		$args['user_tags'] = wp_json_encode( $args['user_tags'] );
	}

	// Normaliser les paramètres UTM en lowercase
	$args['utm_source']   = strtolower( sanitize_text_field( $args['utm_source'] ) );
	$args['utm_medium']   = strtolower( sanitize_text_field( $args['utm_medium'] ) );
	$args['utm_campaign'] = strtolower( sanitize_text_field( $args['utm_campaign'] ) );

	// Vérifier si la campagne existe déjà
	$matcher = utm_tracker()->matcher;
	if ( $matcher && $matcher->campaign_exists( $args['utm_source'], $args['utm_medium'], $args['utm_campaign'] ) ) {
		return false; // Campagne déjà existante
	}

	$table_campaigns = $wpdb->prefix . 'utm_campaigns';

	// Insérer la campagne
	$result = $wpdb->insert(
		$table_campaigns,
		array(
			'name'         => sanitize_text_field( $args['name'] ),
			'utm_source'   => $args['utm_source'],
			'utm_medium'   => $args['utm_medium'],
			'utm_campaign' => $args['utm_campaign'],
			'utm_content'  => sanitize_text_field( $args['utm_content'] ),
			'utm_term'     => sanitize_text_field( $args['utm_term'] ),
			'user_tags'    => $args['user_tags'],
			'status'       => $args['status'],
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( $result ) {
		return $wpdb->insert_id;
	}

	return false;
}

/**
 * Mettre à jour une campagne UTM
 *
 * @since 1.0.0
 * @param int   $campaign_id ID de la campagne.
 * @param array $args        Données à mettre à jour.
 * @return bool True si mis à jour, false sinon
 */
function utm_update_campaign( $campaign_id, $args ) {
	global $wpdb;

	$table_campaigns = $wpdb->prefix . 'utm_campaigns';

	// Normaliser les tags si présents
	if ( isset( $args['user_tags'] ) && is_array( $args['user_tags'] ) ) {
		$args['user_tags'] = wp_json_encode( $args['user_tags'] );
	}

	// Normaliser les paramètres UTM en lowercase
	if ( isset( $args['utm_source'] ) ) {
		$args['utm_source'] = strtolower( sanitize_text_field( $args['utm_source'] ) );
	}
	if ( isset( $args['utm_medium'] ) ) {
		$args['utm_medium'] = strtolower( sanitize_text_field( $args['utm_medium'] ) );
	}
	if ( isset( $args['utm_campaign'] ) ) {
		$args['utm_campaign'] = strtolower( sanitize_text_field( $args['utm_campaign'] ) );
	}

	$result = $wpdb->update(
		$table_campaigns,
		$args,
		array( 'id' => $campaign_id ),
		null,
		array( '%d' )
	);

	return (bool) $result;
}

/**
 * Supprimer une campagne UTM
 *
 * @since 1.0.0
 * @param int $campaign_id ID de la campagne.
 * @return bool True si supprimée, false sinon
 */
function utm_delete_campaign( $campaign_id ) {
	global $wpdb;

	$table_campaigns = $wpdb->prefix . 'utm_campaigns';

	$result = $wpdb->delete(
		$table_campaigns,
		array( 'id' => $campaign_id ),
		array( '%d' )
	);

	return (bool) $result;
}

/**
 * Obtenir une campagne par ID
 *
 * @since 1.0.0
 * @param int $campaign_id ID de la campagne.
 * @return object|null Objet campagne ou null
 */
function utm_get_campaign( $campaign_id ) {
	$matcher = utm_tracker()->matcher;
	if ( ! $matcher ) {
		return null;
	}

	return $matcher->get_campaign_by_id( $campaign_id );
}

/**
 * Lister toutes les campagnes
 *
 * @since 1.0.0
 * @param string $status Filtrer par statut (optionnel).
 * @return array Liste des campagnes
 */
function utm_get_campaigns( $status = '' ) {
	$matcher = utm_tracker()->matcher;
	if ( ! $matcher ) {
		return array();
	}

	return $matcher->get_all_campaigns( $status );
}

/**
 * Obtenir les statistiques d'une campagne
 *
 * @since 1.0.0
 * @param int $campaign_id ID de la campagne.
 * @return array Statistiques
 */
function utm_get_campaign_stats( $campaign_id ) {
	$matcher = utm_tracker()->matcher;
	if ( ! $matcher ) {
		return array();
	}

	return $matcher->get_campaign_stats( $campaign_id );
}

/**
 * Obtenir les tags d'un utilisateur
 *
 * @since 1.0.0
 * @param int $user_id ID de l'utilisateur.
 * @return array Liste des tags (slugs)
 */
function utm_get_user_tags( $user_id ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return array();
	}

	return $tag_applicator->get_user_tags( $user_id );
}

/**
 * Obtenir les détails des tags d'un utilisateur
 *
 * @since 1.0.0
 * @param int $user_id ID de l'utilisateur.
 * @return array Liste des objets tag
 */
function utm_get_user_tags_details( $user_id ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return array();
	}

	return $tag_applicator->get_user_tags_details( $user_id );
}

/**
 * Vérifier si un utilisateur a un tag
 *
 * @since 1.0.0
 * @param int    $user_id  ID de l'utilisateur.
 * @param string $tag_slug Slug du tag.
 * @return bool True si le tag existe, false sinon
 */
function utm_user_has_tag( $user_id, $tag_slug ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return false;
	}

	return $tag_applicator->user_has_tag( $user_id, $tag_slug );
}

/**
 * Ajouter un tag à un utilisateur
 *
 * @since 1.0.0
 * @param int    $user_id     ID de l'utilisateur.
 * @param string $tag_slug    Slug du tag.
 * @param int    $campaign_id ID de la campagne (optionnel).
 * @return bool True si ajouté, false sinon
 */
function utm_add_user_tag( $user_id, $tag_slug, $campaign_id = null ) {
	global $wpdb;

	$table_user_tags = $wpdb->prefix . 'user_tags';
	$tag_slug        = sanitize_title( $tag_slug );

	// Vérifier si le tag existe déjà
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table_user_tags} WHERE user_id = %d AND tag_slug = %s LIMIT 1",
			$user_id,
			$tag_slug
		)
	);

	if ( $existing ) {
		return false; // Tag déjà existant
	}

	// Insérer le tag
	$result = $wpdb->insert(
		$table_user_tags,
		array(
			'user_id'     => $user_id,
			'tag_slug'    => $tag_slug,
			'campaign_id' => $campaign_id,
			'applied_at'  => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%d', '%s' )
	);

	return (bool) $result;
}

/**
 * Retirer un tag d'un utilisateur
 *
 * @since 1.0.0
 * @param int    $user_id  ID de l'utilisateur.
 * @param string $tag_slug Slug du tag.
 * @return bool True si supprimé, false sinon
 */
function utm_remove_user_tag( $user_id, $tag_slug ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return false;
	}

	return $tag_applicator->remove_tag_from_user( $user_id, $tag_slug );
}

/**
 * Obtenir tous les utilisateurs ayant un tag
 *
 * @since 1.0.0
 * @param string $tag_slug Slug du tag.
 * @return array Liste des IDs utilisateur
 */
function utm_get_users_by_tag( $tag_slug ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return array();
	}

	return $tag_applicator->get_users_by_tag( $tag_slug );
}

/**
 * Compter les utilisateurs ayant un tag
 *
 * @since 1.0.0
 * @param string $tag_slug Slug du tag.
 * @return int Nombre d'utilisateurs
 */
function utm_count_users_by_tag( $tag_slug ) {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return 0;
	}

	return $tag_applicator->count_users_by_tag( $tag_slug );
}

/**
 * Obtenir tous les tags avec leurs compteurs
 *
 * @since 1.0.0
 * @return array Liste des tags avec user_count
 */
function utm_get_all_tags() {
	$tag_applicator = utm_tracker()->tag_applicator;
	if ( ! $tag_applicator ) {
		return array();
	}

	return $tag_applicator->get_all_tags_with_counts();
}

/**
 * Obtenir les données UTM de la session courante
 *
 * @since 1.0.0
 * @return array|null Données UTM ou null
 */
function utm_get_session_data() {
	$capture = utm_tracker()->capture;
	if ( ! $capture ) {
		return null;
	}

	return $capture->get_session_utm_data();
}

/**
 * Générer une URL avec paramètres UTM
 *
 * @since 1.0.0
 * @param string $url          URL de base.
 * @param array  $utm_params   Paramètres UTM à ajouter.
 * @return string URL avec paramètres UTM
 */
function utm_generate_url( $url, $utm_params ) {
	$url_parts = wp_parse_url( $url );
	$query     = isset( $url_parts['query'] ) ? $url_parts['query'] : '';

	parse_str( $query, $query_params );

	// Ajouter les paramètres UTM
	$query_params = array_merge( $query_params, $utm_params );

	// Reconstruire l'URL
	$new_query = http_build_query( $query_params );
	$base_url  = $url_parts['scheme'] . '://' . $url_parts['host'];

	if ( isset( $url_parts['port'] ) ) {
		$base_url .= ':' . $url_parts['port'];
	}

	if ( isset( $url_parts['path'] ) ) {
		$base_url .= $url_parts['path'];
	}

	return $base_url . ( $new_query ? '?' . $new_query : '' );
}

