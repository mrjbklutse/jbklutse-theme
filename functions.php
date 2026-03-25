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
