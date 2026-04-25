<?php
/**
 * Rank Math REST Exposure
 *
 * Exposes Rank Math's per-post SEO meta fields to the WordPress REST API
 * so our content-publishing pipeline can set focus keyword, meta description,
 * Pillar Content flag, social images, and canonical URL programmatically.
 *
 * By default Rank Math meta keys have an `auth_callback` that prevents REST
 * writes. We re-register them with explicit `show_in_rest => true` and a
 * permission callback restricted to `edit_posts` capability.
 *
 * Activation: required from functions.php.
 *
 * @package jbklutse
 * @since 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Rank Math meta fields for REST.
 *
 * Field semantics (per Rank Math docs / DB inspection):
 *   rank_math_focus_keyword     — comma-separated, first is primary focus keyword
 *   rank_math_description       — meta description override
 *   rank_math_title             — SEO title override
 *   rank_math_canonical_url     — canonical URL override
 *   rank_math_pillar_content    — 'on' (true) or '' (false), tells Rank Math
 *                                 this post is cornerstone/pillar content
 *   rank_math_facebook_image    — OG image URL override
 *   rank_math_facebook_image_id — OG image attachment ID
 *   rank_math_twitter_image     — Twitter Card image URL override
 *   rank_math_twitter_image_id  — Twitter Card image attachment ID
 */
function jbk_rank_math_rest_register() {
	$fields = array(
		'rank_math_focus_keyword'     => 'string',
		'rank_math_description'       => 'string',
		'rank_math_title'             => 'string',
		'rank_math_canonical_url'     => 'string',
		'rank_math_pillar_content'    => 'string',
		'rank_math_facebook_image'    => 'string',
		'rank_math_facebook_image_id' => 'integer',
		'rank_math_twitter_image'     => 'string',
		'rank_math_twitter_image_id'  => 'integer',
	);

	$auth_callback = function () {
		return current_user_can( 'edit_posts' );
	};

	foreach ( $fields as $key => $type ) {
		register_post_meta( 'post', $key, array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => $type,
			'auth_callback' => $auth_callback,
		) );
	}
}
add_action( 'init', 'jbk_rank_math_rest_register', 100 );  // priority 100 = after Rank Math's own registration
