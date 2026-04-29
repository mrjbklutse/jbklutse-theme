<?php
/**
 * JBKlutse Tech Theme - Functions
 *
 * @package jbklutse
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup
 */
function jbklutse_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	add_image_size( 'jbklutse-hero', 1200, 600, true );
	add_image_size( 'jbklutse-card', 400, 250, true );
	add_image_size( 'jbklutse-thumbnail', 150, 150, true );

	register_nav_menus( array(
		'primary'   => __( 'Primary Menu', 'jbklutse' ),
		'footer'    => __( 'Footer Menu', 'jbklutse' ),
	) );
}
add_action( 'after_setup_theme', 'jbklutse_setup' );

/**
 * Enqueue styles and scripts
 */
function jbklutse_enqueue_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'jbklutse-custom',
		get_theme_file_uri( 'assets/css/custom.css' ),
		array(),
		$theme_version
	);

	wp_enqueue_script(
		'jbklutse-navigation',
		get_theme_file_uri( 'assets/js/navigation.js' ),
		array(),
		$theme_version,
		true
	);

	// Fix colors on dark backgrounds - must override WP global styles
	wp_add_inline_style( 'jbklutse-custom', '
		.has-primary-background-color .wp-block-navigation a:where(:not(.wp-element-button)) { color: #ffffff !important; }
		.has-primary-background-color .wp-block-navigation a:where(:not(.wp-element-button)):hover { color: #00b3b3 !important; }
		.jbk-hero-post .wp-block-post-title a:where(:not(.wp-element-button)) { color: #ffffff !important; }
		.jbk-hero-post .wp-block-post-title a:where(:not(.wp-element-button)):hover { color: #00b3b3 !important; }
		.jbk-hero-post .wp-block-post-title { color: #ffffff !important; }
	' );
}
add_action( 'wp_enqueue_scripts', 'jbklutse_enqueue_assets' );

/**
 * Register block patterns
 */
function jbklutse_register_patterns() {
	register_block_pattern_category( 'jbklutse', array(
		'label' => __( 'JBKlutse', 'jbklutse' ),
	) );
}
add_action( 'init', 'jbklutse_register_patterns' );

/**
 * Estimated reading time
 */
function jbklutse_reading_time( $post_id = null ) {
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}
	$content    = get_post_field( 'post_content', $post_id );
	$word_count = str_word_count( wp_strip_all_tags( $content ) );
	$minutes    = max( 1, ceil( $word_count / 250 ) );
	return $minutes . ' min read';
}

/**
 * Add reading time to post meta
 */
function jbklutse_post_meta_reading_time() {
	echo '<span class="jbk-reading-time">' . esc_html( jbklutse_reading_time() ) . '</span>';
}

/**
 * Dynamic reading time via render_block filter
 */
function jbklutse_dynamic_reading_time( $block_content, $block ) {
	if ( ! is_singular( 'post' ) ) {
		return $block_content;
	}

	if ( isset( $block['attrs']['className'] ) && strpos( $block['attrs']['className'], 'jbk-reading-time-block' ) !== false ) {
		$reading_time = jbklutse_reading_time();
		$block_content = preg_replace( '/>\d+ min read</', '>' . esc_html( $reading_time ) . '<', $block_content );
	}

	return $block_content;
}
add_filter( 'render_block', 'jbklutse_dynamic_reading_time', 10, 2 );

/**
 * Preload critical assets and add performance hints
 */
function jbklutse_preload_assets() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";
	echo '<link rel="dns-prefetch" href="//www.googletagmanager.com">' . "\n";
}
add_action( 'wp_head', 'jbklutse_preload_assets', 1 );

// Meta description handled by Rank Math — no theme override needed.

/**
 * Add structured data for articles (enhanced for Google News & Discover)
 */
function jbklutse_article_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post    = get_post();
	$author  = get_the_author_meta( 'display_name', $post->post_author );
	$image   = get_the_post_thumbnail_url( $post->ID, 'full' );
	$excerpt = get_the_excerpt( $post );

	// Use NewsArticle for news/press-release posts, Article for how-to/guides
	$categories  = get_the_category( $post->ID );
	$cat_slugs   = wp_list_pluck( $categories, 'slug' );
	$news_cats   = array( 'tech-news', 'press-releases', 'general-news', 'cybersecurity', 'fintech', 'crypto' );
	$is_news     = ! empty( array_intersect( $cat_slugs, $news_cats ) );
	$schema_type = $is_news ? 'NewsArticle' : 'Article';

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => $schema_type,
		'headline'         => get_the_title( $post ),
		'description'      => $excerpt,
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author,
			'url'   => get_author_posts_url( $post->post_author ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => 'JBKlutse',
			'url'   => home_url(),
			'logo'  => array(
				'@type' => 'ImageObject',
				'url'   => home_url( '/wp-content/uploads/jbklutse-logo.png' ),
			),
		),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post ),
		),
		'wordCount'        => str_word_count( wp_strip_all_tags( $post->post_content ) ),
		'inLanguage'       => 'en-GH',
	);

	if ( $image ) {
		$schema['image'] = array(
			'@type'  => 'ImageObject',
			'url'    => $image,
			'width'  => 1200,
			'height' => 675,
		);
	}

	if ( ! empty( $categories ) ) {
		$schema['articleSection'] = $categories[0]->name;
	}

	if ( $is_news ) {
		$schema['dateline'] = 'Accra, Ghana';
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'jbklutse_article_schema' );

// WebSite schema handled by Rank Math — no theme override needed.

/**
 * Defer non-critical scripts for performance
 */
function jbklutse_defer_scripts( $tag, $handle, $src ) {
	// Don't defer admin scripts or critical scripts
	if ( is_admin() ) {
		return $tag;
	}

	$no_defer = array( 'jquery-core', 'jquery-migrate', 'wp-hooks' );
	if ( in_array( $handle, $no_defer, true ) ) {
		return $tag;
	}

	// Defer theme scripts and non-critical plugin scripts
	$defer_handles = array( 'jbklutse-navigation', 'comment-reply' );
	if ( in_array( $handle, $defer_handles, true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'jbklutse_defer_scripts', 10, 3 );

/**
 * Add fetchpriority="high" to hero/featured images for LCP optimization
 */
function jbklutse_optimize_featured_images( $attr, $attachment, $size ) {
	if ( is_singular() && in_the_loop() && is_main_query() ) {
		$attr['fetchpriority'] = 'high';
		$attr['decoding']      = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'jbklutse_optimize_featured_images', 10, 3 );

/**
 * Ensure all images have alt text fallback
 */
function jbklutse_image_alt_fallback( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) ) {
		$attr['alt'] = get_the_title( $attachment->ID );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'jbklutse_image_alt_fallback', 20, 2 );

/**
 * Remove unnecessary WordPress head clutter for performance
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

/**
 * Disable emoji scripts and styles (saves ~15KB)
 */
function jbklutse_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'jbklutse_disable_emojis' );

/**
 * Remove "Category:" prefix from archive titles
 */
function jbklutse_archive_title( $title ) {
	if ( is_category() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() ) {
		$title = single_tag_title( '', false );
	} elseif ( is_author() ) {
		$title = get_the_author();
	}
	return $title;
}
add_filter( 'get_the_archive_title', 'jbklutse_archive_title' );

/**
 * Add excerpt support and set default length
 */
function jbklutse_excerpt_length( $length ) {
	return 25;
}
add_filter( 'excerpt_length', 'jbklutse_excerpt_length' );

function jbklutse_excerpt_more( $more ) {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'jbklutse_excerpt_more' );

/**
 * Exclude press releases (cat 1850) from homepage hero and sidebar queries
 */
function jbklutse_exclude_press_releases_from_homepage( $query_args, $block, $page ) {
	// Only on the front page / homepage
	if ( ! is_front_page() && ! is_home() ) {
		return $query_args;
	}

	// The queryId is passed via block context from the parent core/query block
	$query_id = 0;
	if ( is_object( $block ) && isset( $block->context['queryId'] ) ) {
		$query_id = (int) $block->context['queryId'];
	}

	// Exclude press releases (cat 1850) from hero (10) and sidebar (11) queries
	if ( in_array( $query_id, array( 10, 11 ), true ) ) {
		$query_args['category__not_in'] = array( 1850 );
	}

	return $query_args;
}
add_filter( 'query_loop_block_query_vars', 'jbklutse_exclude_press_releases_from_homepage', 10, 3 );

/**
 * Add disclosure label to press release posts
 */
function jbklutse_press_release_disclosure( $content ) {
	if ( ! is_singular( 'post' ) ) {
		return $content;
	}

	$categories = get_the_category();
	$is_press_release = false;
	foreach ( $categories as $cat ) {
		if ( $cat->slug === 'press-releases' ) {
			$is_press_release = true;
			break;
		}
	}

	if ( $is_press_release ) {
		$disclosure = '<div class="jbk-press-release-notice" style="background:#f0f7f7;border-left:4px solid #00b3b3;padding:12px 16px;margin-bottom:24px;font-size:0.875rem;color:#1a2332;border-radius:0 6px 6px 0;">';
		$disclosure .= '<strong>Press Release</strong> — This content was provided by a third party. JBKlutse publishes press releases for informational purposes. Views expressed are those of the issuing organization.';
		$disclosure .= '</div>';
		$content = $disclosure . $content;
	}

	return $content;
}
add_filter( 'the_content', 'jbklutse_press_release_disclosure' );

/**
 * Auto-generate Table of Contents for posts with 3+ headings
 * Replaces Easy Table of Contents plugin
 */
function jbklutse_auto_toc( $content ) {
	if ( ! is_singular( 'post' ) || is_admin() ) {
		return $content;
	}

	// Only run in the main post content loop, not in queries or related posts
	$post_id = get_the_ID();
	$queried = get_queried_object_id();
	if ( ! $post_id || $post_id !== $queried ) {
		return $content;
	}

	// Prevent running twice per post per request
	global $jbklutse_toc_rendered;
	if ( ! empty( $jbklutse_toc_rendered[ $post_id ] ) ) {
		return $content;
	}
	$jbklutse_toc_rendered[ $post_id ] = true;

	// Find all h2 and h3 headings
	preg_match_all( '/<h([2-3])[^>]*>(.*?)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER );

	if ( count( $matches ) < 3 ) {
		return $content;
	}

	// Build TOC
	$toc  = '<div class="jbk-toc">';
	$toc .= '<details open>';
	$toc .= '<summary><strong>Table of Contents</strong></summary>';
	$toc .= '<ul>';

	$counter = 0;
	foreach ( $matches as $match ) {
		$level   = (int) $match[1];
		$text    = wp_strip_all_tags( $match[2] );
		$slug    = 'toc-' . sanitize_title( $text );
		$indent  = $level === 3 ? ' class="jbk-toc-sub"' : '';
		$toc    .= '<li' . $indent . '><a href="#' . esc_attr( $slug ) . '">' . esc_html( $text ) . '</a></li>';

		// Add id attribute to the heading in content
		$old_heading = $match[0];
		$new_heading = preg_replace( '/(<h[2-3])([^>]*>)/i', '$1 id="' . esc_attr( $slug ) . '"$2', $old_heading, 1 );
		$content     = str_replace( $old_heading, $new_heading, $content );

		$counter++;
	}

	$toc .= '</ul>';
	$toc .= '</details>';
	$toc .= '</div>';

	// Insert TOC after the first paragraph
	$first_p_pos = strpos( $content, '</p>' );
	if ( false !== $first_p_pos ) {
		$content = substr_replace( $content, '</p>' . $toc, $first_p_pos, 4 );
	} else {
		$content = $toc . $content;
	}

	return $content;
}
add_filter( 'the_content', 'jbklutse_auto_toc', 5 );

/* ───────────────────────────────────────────────────
 *  Related Posts: filter by current post's categories
 * ─────────────────────────────────────────────────── */
function jbklutse_related_posts_query( $query, $block ) {
	if ( ! is_singular( 'post' ) ) {
		return $query;
	}
	// Target the Related Posts query (queryId 2) on single posts
	if ( isset( $block->context['queryId'] ) && 2 === $block->context['queryId'] ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$cat_ids = wp_list_pluck( $cats, 'term_id' );
			$query['category__in'] = $cat_ids;
		}
		$query['post__not_in'] = array( get_the_ID() );
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'jbklutse_related_posts_query', 10, 2 );

/* ───────────────────────────────────────────────────
 *  Anti-Spam Comment Filter
 *  Honeypot field + keyword blacklist + time-trap
 * ─────────────────────────────────────────────────── */

// 1. Add a hidden honeypot field to the comment form
function jbklutse_spam_honeypot_field( $fields ) {
	$fields['jbk_hp'] = '<p class="jbk-hp-wrap" style="display:none !important;visibility:hidden;position:absolute;left:-9999px;">'
		. '<label for="jbk_hp_field">Leave this empty</label>'
		. '<input type="text" name="jbk_hp_field" id="jbk_hp_field" value="" tabindex="-1" autocomplete="off" />'
		. '</p>';
	$fields['jbk_ts'] = '<input type="hidden" name="jbk_ts" value="' . time() . '" />';
	return $fields;
}
add_filter( 'comment_form_default_fields', 'jbklutse_spam_honeypot_field' );

// 2. Validate comments before they're saved
function jbklutse_block_spam_comment( $commentdata ) {

	// Skip logged-in users / admins
	if ( is_user_logged_in() ) {
		return $commentdata;
	}

	// --- Honeypot check: bots fill hidden fields ---
	if ( ! empty( $_POST['jbk_hp_field'] ) ) {
		wp_die( 'Spam detected.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	// --- Require honeypot field exists (blocks direct POST bots) ---
	if ( ! isset( $_POST['jbk_ts'] ) ) {
		wp_die( 'Invalid comment submission.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	// --- Time-trap: reject if submitted in under 5 seconds ---
	$elapsed = time() - absint( $_POST['jbk_ts'] );
	if ( $elapsed < 5 ) {
		wp_die( 'You submitted too fast. Please go back and try again.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	$author  = strtolower( $commentdata['comment_author'] ?? '' );
	$email   = strtolower( $commentdata['comment_author_email'] ?? '' );
	$content = strtolower( $commentdata['comment_content'] ?? '' );
	$url     = strtolower( $commentdata['comment_author_url'] ?? '' );

	// --- Keyword blacklist (author + content) ---
	$spam_keywords = array(
		'tank welding', 'tank fabrication', 'tank erection', 'tank jacking',
		'tank lifting', 'tank construction', 'hydraulic lifting',
		'welding tractor', 'welding equipment', 'welding system',
		'lng tank', 'storage tank', 'bulk storage',
		'cloud hosting', 'home insurance', 'payday loan', 'cbd oil',
		'casino online', 'sports betting', 'crypto trading',
		'buy cheap', 'order now', 'click here', 'free trial',
		'viagra', 'cialis', 'pharmacy online',
		'life insurance', 'تنظيف', 'شركة', 'severek takip',
		'teşekkür', 'yararlı', 'i came across a', 'helpful platform',
		'give it a visit', 'packed with a lot',
	);

	$check_text = $author . ' ' . $content;
	foreach ( $spam_keywords as $keyword ) {
		if ( false !== strpos( $check_text, $keyword ) ) {
			wp_die( 'Your comment was flagged as spam.', 'Comment Blocked', array( 'back_link' => true ) );
		}
	}

	// --- Block disposable / suspicious email patterns ---
	$spam_email_patterns = array(
		'@example.com',
		'@gcomadescertj',
		'forum.fun',
		'@kirisbyforum',
	);
	foreach ( $spam_email_patterns as $pattern ) {
		if ( false !== strpos( $email, $pattern ) ) {
			wp_die( 'Your comment was flagged as spam.', 'Comment Blocked', array( 'back_link' => true ) );
		}
	}

	// --- Block comments that are just a URL ---
	if ( filter_var( trim( $commentdata['comment_content'] ), FILTER_VALIDATE_URL ) ) {
		wp_die( 'Comments that are only a link are not allowed.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	// --- Block comments with 2+ links (common spam pattern) ---
	$link_count = preg_match_all( '/<a\s|https?:\/\//i', $commentdata['comment_content'] );
	if ( $link_count >= 2 ) {
		wp_die( 'Too many links in your comment.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	// --- Block very short generic comments (under 20 chars, no substance) ---
	$stripped = trim( strip_tags( $commentdata['comment_content'] ) );
	if ( strlen( $stripped ) < 20 ) {
		wp_die( 'Please write a more detailed comment.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	// --- Block non-Latin scripts in author name (Arabic, Cyrillic spam) ---
	if ( preg_match( '/[\x{0600}-\x{06FF}\x{0400}-\x{04FF}]/u', $author ) ) {
		wp_die( 'Your comment was flagged as spam.', 'Comment Blocked', array( 'back_link' => true ) );
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'jbklutse_block_spam_comment' );

/* ────────────────────────────────────────────────────────────
 * Modular includes
 * ──────────────────────────────────────────────────────────── */

// Noindex + sitemap exclusion + link isolation for press releases (category 1850).
// This is the single highest-leverage SEO change on the site — insulates our
// ~160 original posts from the ~2,068 paid press releases in terms of domain signal.
require_once get_theme_file_path( 'inc/press-release-noindex.php' );

// Auto-tag original (non-PR) content with "newstex" (tag ID 1846) on publish.
// Distinguishes our original journalism from paid press releases at the data layer.
require_once get_theme_file_path( 'inc/auto-tag-original-content.php' );

// Force index, follow on /topics/ archives (except press-releases). Rank Math
// defaults categories to noindex, which would kill the topical-authority hubs.
require_once get_theme_file_path( 'inc/topic-archives-index.php' );

// Expose Rank Math per-post meta to REST so the publishing pipeline can set
// focus keyword, meta description, Pillar Content flag, social images, etc.
require_once get_theme_file_path( 'inc/rank-math-rest.php' );

// Trending news review pipeline: tokenized public preview URL +
// /jbklutse/v1/send-review-email REST endpoint (uses wp_mail via wp-mail-smtp).
require_once get_theme_file_path( 'inc/jbk-trending-review.php' );

// AdSense manual placements (top, mid, bottom of every single post).
// Picks up auto-ads inventory by default; for guaranteed fill paste explicit
// ad-unit slot IDs into the JBK_AD_SLOT_* constants in the file.
require_once get_theme_file_path( 'inc/jbk-ad-placements.php' );

// Google Consent Mode v2 — emits gtag('consent','default'/'update') signals
// so AdSense + GA can switch between personalized/non-personalized ads.
// Bridges Complianz Free (which doesn't ship Consent Mode v2 itself).
require_once get_theme_file_path( 'inc/jbk-consent-mode.php' );

// Opportunities Vertical (Phase 3.1 foundation): CPT, taxonomy, custom URL
// routing, meta fields, daily auto-expire sweep, basic apply-button injection.
require_once get_theme_file_path( 'inc/jbk-opportunities-cpt.php' );

// Site chrome (header/footer) brand-color overrides — forces white/teal text
// on the dark primary background regardless of block-generated CSS.
require_once get_theme_file_path( 'inc/jbk-chrome-overrides.php' );
