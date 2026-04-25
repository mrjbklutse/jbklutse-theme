<?php
/**
 * Auto-Tag Original Content Module
 *
 * Every post JBKlutse publishes that is NOT a press release (category 1850)
 * gets tagged with "newstex" (tag ID 1846) on publish.
 *
 * Why: separates our original journalism from the ~2,068 paid press releases
 * at the data layer, so downstream workflows and archive queries can filter
 * cleanly without relying on "is this NOT in category 1850?" logic.
 *
 * This runs on:
 *   - Posts published through the WordPress UI (manual editorial)
 *   - Posts published through the REST API (the upcoming n8n pipelines)
 *   - Posts transitioning from draft/pending → publish
 *
 * It does NOT:
 *   - Touch existing published posts (retroactive tagging is a separate batch job)
 *   - Override manual edits after publish (if an editor removes the tag
 *     post-publish, this won't re-add it)
 *   - Tag press releases (category 1850 is excluded)
 *
 * Activation: already wired via functions.php require_once.
 *
 * @package jbklutse
 * @since 2026-04-24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'JBK_NEWSTEX_TAG_ID' ) ) {
	define( 'JBK_NEWSTEX_TAG_ID', 1846 );
}

/**
 * Attach the newstex tag when a post transitions into "publish" status,
 * provided it's not a press release.
 *
 * Fires on `transition_post_status` so we catch:
 *   - New post published immediately
 *   - Draft → publish
 *   - Pending → publish
 *   - Scheduled → publish (cron firing)
 *
 * @param string  $new_status
 * @param string  $old_status
 * @param WP_Post $post
 */
function jbk_auto_tag_newstex_on_publish( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status ) {
		return;
	}
	if ( ! $post || 'post' !== $post->post_type ) {
		return;
	}
	// Skip press releases.
	if ( in_category( JBK_PRESS_RELEASE_CAT_ID, $post->ID ) ) {
		return;
	}
	// Add the tag (wp_set_post_tags with append=true preserves existing tags).
	wp_set_post_tags( $post->ID, array( JBK_NEWSTEX_TAG_ID ), true );
}
add_action( 'transition_post_status', 'jbk_auto_tag_newstex_on_publish', 10, 3 );

/**
 * Also catch posts created via REST (n8n, the Block Editor's autosave,
 * programmatic publishing). `rest_after_insert_post` fires after every
 * post insert/update via the REST API.
 *
 * @param WP_Post $post
 * @param WP_REST_Request $request
 * @param bool $creating
 */
function jbk_auto_tag_newstex_on_rest( $post, $request, $creating ) {
	if ( ! $post || 'post' !== $post->post_type ) {
		return;
	}
	if ( 'publish' !== $post->post_status ) {
		return;
	}
	if ( in_category( JBK_PRESS_RELEASE_CAT_ID, $post->ID ) ) {
		return;
	}
	wp_set_post_tags( $post->ID, array( JBK_NEWSTEX_TAG_ID ), true );
}
add_action( 'rest_after_insert_post', 'jbk_auto_tag_newstex_on_rest', 10, 3 );

/**
 * WP-CLI command for one-time backfill:
 *   wp jbk backfill-newstex
 *
 * Applies the newstex tag to every currently-published non-press-release post.
 * Safe to re-run — `wp_set_post_tags` with append=true is idempotent.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'jbk backfill-newstex', function () {
		$args = array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'category__not_in' => array( JBK_PRESS_RELEASE_CAT_ID ),
		);
		$ids = get_posts( $args );
		$total = count( $ids );
		WP_CLI::log( "Found {$total} non-press-release posts." );

		$tagged = 0;
		foreach ( $ids as $id ) {
			wp_set_post_tags( $id, array( JBK_NEWSTEX_TAG_ID ), true );
			$tagged++;
			if ( 0 === $tagged % 25 ) {
				WP_CLI::log( "  Tagged {$tagged}/{$total}..." );
			}
		}
		WP_CLI::success( "Tagged {$tagged} posts with newstex (tag ID " . JBK_NEWSTEX_TAG_ID . ")." );
	} );
}
