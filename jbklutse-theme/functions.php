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
 * Preload critical assets
 */
function jbklutse_preload_assets() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
	echo '<link rel="dns-prefetch" href="//www.google-analytics.com">' . "\n";
}
add_action( 'wp_head', 'jbklutse_preload_assets', 1 );

/**
 * Add structured data for articles
 */
function jbklutse_article_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post    = get_post();
	$author  = get_the_author_meta( 'display_name', $post->post_author );
	$image   = get_the_post_thumbnail_url( $post->ID, 'full' );
	$excerpt = get_the_excerpt( $post );

	$schema = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'Article',
		'headline'         => get_the_title( $post ),
		'description'      => $excerpt,
		'datePublished'    => get_the_date( 'c', $post ),
		'dateModified'     => get_the_modified_date( 'c', $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author,
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => 'JBKlutse',
			'url'   => home_url(),
		),
		'mainEntityOfPage' => get_permalink( $post ),
	);

	if ( $image ) {
		$schema['image'] = $image;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'jbklutse_article_schema' );
