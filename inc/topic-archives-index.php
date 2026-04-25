<?php
/**
 * Topic / Category Archives — force INDEX
 *
 * Rank Math defaults category archives to noindex on this site, which
 * silently kills the topical-authority strategy: pages like
 * /topics/gaming/, /topics/fintech/, /topics/computing/ are the pillar
 * hubs we want Google to crawl, rank, and treat as authority pages.
 *
 * This module forces `index, follow` on every category archive EXCEPT
 * the press-releases category (ID 1850), which stays noindex via
 * inc/press-release-noindex.php.
 *
 * Note: on this site the "category" permalink base is rewritten to
 * /topics/, so this filter covers both /topics/foo/ and any legacy
 * /category/foo/ that resolves to the same term.
 *
 * @package jbklutse
 * @since 2026-04-25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override Rank Math robots meta on category archives.
 * Press releases (cat 1850) are intentionally left untouched so the
 * existing PR-noindex module continues to apply.
 */
function jbk_topic_archives_force_index( $robots ) {
	if ( ! is_category() ) {
		return $robots;
	}

	$term = get_queried_object();
	if ( ! $term || empty( $term->term_id ) ) {
		return $robots;
	}

	if ( defined( 'JBK_PRESS_RELEASE_CAT_ID' ) && (int) $term->term_id === JBK_PRESS_RELEASE_CAT_ID ) {
		return $robots;
	}

	$robots['index']  = 'index';
	$robots['follow'] = 'follow';
	unset( $robots['noindex'], $robots['nofollow'] );

	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'jbk_topic_archives_force_index', 20, 1 );
