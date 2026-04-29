<?php
/**
 * Press Release Noindex Module
 *
 * JBKlutse has ~2,228 posts, ~93% of which are paid press releases
 * (category ID 1850). These dilute our topical authority because Google
 * evaluates our domain partly on their thin, promotional nature.
 *
 * This module does FOUR things to insulate our real content from PR weight:
 *
 * 1. Adds <meta name="robots" content="noindex, follow"> on single PR posts
 * 2. Excludes PRs from Rank Math XML sitemap
 * 3. Adds rel="nofollow" on links in NON-PR posts that point to PR posts
 *    (prevents link-equity leakage into the PR ghetto)
 * 4. Removes PRs from internal search results
 *
 * Activation: add one line to functions.php near the bottom:
 *   require_once get_theme_file_path( 'inc/press-release-noindex.php' );
 *
 * @package jbklutse
 * @since 2026-04-24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Press release category ID on jbklutse.com.
 * Verified in project_site_revival.md memory: category ID 1850.
 */
if ( ! defined( 'JBK_PRESS_RELEASE_CAT_ID' ) ) {
	define( 'JBK_PRESS_RELEASE_CAT_ID', 1850 );
}

/**
 * Check if a given post is in the press release category.
 *
 * @param int|WP_Post|null $post_id Post ID or object; falls back to current post.
 * @return bool
 */
function jbk_is_press_release( $post_id = null ) {
	$post_id = $post_id ? ( is_object( $post_id ) ? $post_id->ID : (int) $post_id ) : get_the_ID();
	if ( ! $post_id ) {
		return false;
	}
	return in_category( JBK_PRESS_RELEASE_CAT_ID, $post_id );
}

/* ────────────────────────────────────────────────────────────
 * 1. Inject <meta name="robots" content="noindex, follow"> on PR singles
 * ──────────────────────────────────────────────────────────── */

/**
 * Output robots meta on press release single posts.
 * Uses `follow` so outbound links still pass signal, just not the page itself.
 */
function jbk_pr_noindex_meta() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	if ( ! jbk_is_press_release() ) {
		return;
	}
	echo "\n<!-- JBKlutse: press release noindex -->\n";
	echo '<meta name="robots" content="noindex, follow">' . "\n";
}
add_action( 'wp_head', 'jbk_pr_noindex_meta', 1 );

/**
 * Rank Math's robots filter — tell it to noindex + follow on PR posts.
 * Runs even if Rank Math's own "noindex this post" checkbox isn't set.
 */
function jbk_pr_rank_math_robots( $robots ) {
	if ( is_singular( 'post' ) && jbk_is_press_release() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'jbk_pr_rank_math_robots', 10, 1 );

/* ────────────────────────────────────────────────────────────
 * 2. Exclude press releases from Rank Math XML sitemap
 * ──────────────────────────────────────────────────────────── */

/**
 * Remove press release posts from the Rank Math sitemap entirely.
 *
 * Current Rank Math versions build a cached post exclusion list via
 * `rank_math/sitemap/posts_to_exclude`. Keep the older per-post filter below
 * for backward compatibility, but this list filter is the one live XML
 * sitemap generation uses.
 *
 * @param array $posts_to_exclude Existing excluded post IDs.
 * @return array
 */
function jbk_pr_sitemap_posts_to_exclude( $posts_to_exclude ) {
	$pr_posts = get_posts( array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'              => array(
			array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => JBK_PRESS_RELEASE_CAT_ID,
			),
		),
	) );

	return array_values( array_unique( array_map( 'absint', array_merge(
		(array) $posts_to_exclude,
		$pr_posts
	) ) ) );
}
add_filter( 'rank_math/sitemap/posts_to_exclude', 'jbk_pr_sitemap_posts_to_exclude', 10, 1 );

// Keep XML sitemaps generated fresh so PR exclusions are not hidden by stale
// Rank Math file caches after publication/category changes.
add_filter( 'rank_math/sitemap/enable_caching', '__return_false' );

/**
 * Remove press release posts from older Rank Math sitemap generation paths.
 * Filter fires per-post when Rank Math builds the sitemap.
 *
 * @param bool $exclude Whether to exclude.
 * @param WP_Post|int $post Post being evaluated.
 */
function jbk_pr_sitemap_exclude( $exclude, $post ) {
	if ( jbk_is_press_release( $post ) ) {
		return true;
	}
	return $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post', 'jbk_pr_sitemap_exclude', 10, 2 );

// Press-release category archive exclusion is handled via Rank Math's
// `sitemap.exclude_terms` setting (rank-math-options-sitemap option), set
// directly to "1850" on 2026-04-25. The old `rank_math/sitemap/terms`
// filter no longer fires in current Rank Math versions.

/**
 * Keep PR posts out of Rank Math Pro's Google News sitemap.
 *
 * Rank Math's News Sitemap checks the per-post
 * `rank_math_news_sitemap_robots` meta key separately from the normal robots
 * meta, so set it whenever a PR is saved/published.
 */
function jbk_pr_set_news_sitemap_noindex( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( jbk_is_press_release( $post_id ) ) {
		update_post_meta( $post_id, 'rank_math_news_sitemap_robots', 'noindex' );
		return;
	}

	delete_post_meta( $post_id, 'rank_math_news_sitemap_robots' );
}
add_action( 'save_post_post', 'jbk_pr_set_news_sitemap_noindex', 20, 1 );

/* ────────────────────────────────────────────────────────────
 * 3. Add rel="nofollow" to outbound links pointing AT press releases
 *    from NON-press-release posts
 * ────────────────────────────────────────────────────────────
 *
 * Rationale: we still link out of non-PR articles to PR posts sometimes
 * (e.g. related-post widgets, tag archives, old internal links). Those
 * links otherwise bleed PageRank into posts we've marked noindex.
 * Making them nofollow prevents that leak.
 */

/**
 * Filter the_content on NON-PR posts and add rel="nofollow" to any
 * internal anchor whose href resolves to a PR post.
 */
function jbk_pr_nofollow_inbound_links( $content ) {
	// Only filter single-post view of NON-PR posts.
	if ( ! is_singular( 'post' ) || is_admin() ) {
		return $content;
	}
	if ( jbk_is_press_release() ) {
		return $content;
	}

	// Fast path: no anchor tags.
	if ( false === strpos( $content, '<a ' ) ) {
		return $content;
	}

	$home = home_url();
	$content = preg_replace_callback(
		'#<a\s+([^>]*?)href=(["\'])([^"\']+)\2([^>]*)>#i',
		function ( $m ) use ( $home ) {
			$before_href = $m[1];
			$quote       = $m[2];
			$href        = $m[3];
			$after_href  = $m[4];

			// Only touch internal links to our own domain.
			if ( 0 !== strpos( $href, $home ) && 0 !== strpos( $href, '/' ) ) {
				return $m[0];
			}

			// Resolve relative to absolute for parsing.
			$absolute = ( 0 === strpos( $href, '/' ) ) ? $home . $href : $href;
			$post_id  = url_to_postid( $absolute );
			if ( ! $post_id ) {
				return $m[0];
			}
			if ( ! in_category( JBK_PRESS_RELEASE_CAT_ID, $post_id ) ) {
				return $m[0];
			}

			// Target IS a PR — add/merge rel="nofollow".
			$full_attrs = $before_href . ' ' . $after_href;
			if ( preg_match( '#\brel=(["\'])([^"\']*)\1#i', $full_attrs, $rel_match ) ) {
				// Existing rel — append nofollow if not already there.
				$existing_rel = $rel_match[2];
				if ( false !== stripos( $existing_rel, 'nofollow' ) ) {
					return $m[0];
				}
				$new_rel  = trim( $existing_rel . ' nofollow' );
				$new_attr = 'rel=' . $rel_match[1] . $new_rel . $rel_match[1];
				$replaced = preg_replace(
					'#\brel=(["\'])([^"\']*)\1#i',
					$new_attr,
					$m[0],
					1
				);
				return $replaced ?: $m[0];
			}
			// No existing rel — insert one.
			return '<a ' . trim( $before_href ) . ' href=' . $quote . $href . $quote
				. ' rel="nofollow"' . $after_href . '>';
		},
		$content
	);

	return $content;
}
add_filter( 'the_content', 'jbk_pr_nofollow_inbound_links', 50 );

/* ────────────────────────────────────────────────────────────
 * 4. Exclude press releases from internal site search
 * ──────────────────────────────────────────────────────────── */

function jbk_pr_exclude_from_search( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_search() ) {
		return;
	}
	$excluded = $query->get( 'category__not_in' );
	if ( ! is_array( $excluded ) ) {
		$excluded = array();
	}
	$excluded[] = JBK_PRESS_RELEASE_CAT_ID;
	$query->set( 'category__not_in', array_unique( $excluded ) );
}
add_action( 'pre_get_posts', 'jbk_pr_exclude_from_search' );

/* ────────────────────────────────────────────────────────────
 * 5. Admin notice so editors remember PR status
 * ──────────────────────────────────────────────────────────── */

function jbk_pr_admin_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}
	global $post;
	if ( ! $post || ! jbk_is_press_release( $post ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Press Release:</strong> this post is automatically <code>noindex, follow</code> and excluded from the sitemap. See <code>inc/press-release-noindex.php</code>.</p></div>';
}
add_action( 'admin_notices', 'jbk_pr_admin_notice' );
