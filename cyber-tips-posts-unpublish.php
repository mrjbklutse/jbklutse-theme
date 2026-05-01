<?php
/**
 * Move the 16 Cyber Tips daily posts to draft after the pillar + redirects ship.
 *
 * Reads seo-analysis-output/decisions/cyber-tips-redirects.csv (column 1)
 * and flips each matching post from publish to draft. Idempotent: posts
 * already in draft/trash/anything-but-publish are skipped.
 *
 * Run AFTER cyber-tips-redirects-import.php, otherwise visitors hitting
 * the daily URLs get a bare 404 instead of the 301 to the pillar.
 *
 * Usage (dry run):
 *   wp eval-file cyber-tips-posts-unpublish.php
 *
 * Usage (commit):
 *   wp eval-file cyber-tips-posts-unpublish.php apply
 */

$apply = getenv( 'APPLY' ) === '1';
$csv   = __DIR__ . '/seo-analysis-output/decisions/cyber-tips-redirects.csv';

if ( ! file_exists( $csv ) ) {
	WP_CLI::error( "Redirect CSV not found: {$csv}" );
}

$rows    = array_map( 'str_getcsv', file( $csv ) );
array_shift( $rows );

$drafted   = 0;
$missing   = 0;
$unchanged = 0;

foreach ( $rows as $row ) {
	if ( empty( $row[0] ) ) {
		continue;
	}
	$source_url = trim( $row[0] );
	$path       = trim( wp_parse_url( $source_url, PHP_URL_PATH ), '/' );
	$slug       = basename( $path );

	$post = get_page_by_path( $slug, OBJECT, 'post' );
	if ( ! $post ) {
		WP_CLI::warning( "[missing] no post matched slug '{$slug}'" );
		$missing++;
		continue;
	}

	if ( $post->post_status !== 'publish' ) {
		WP_CLI::log( sprintf( '[skip] %d "%s" already in status: %s', $post->ID, $post->post_title, $post->post_status ) );
		$unchanged++;
		continue;
	}

	WP_CLI::log( sprintf( '[draft] %d "%s"', $post->ID, $post->post_title ) );
	if ( $apply ) {
		$result = wp_update_post(
			array(
				'ID'          => $post->ID,
				'post_status' => 'draft',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			WP_CLI::warning( "[error] {$post->ID}: " . $result->get_error_message() );
			continue;
		}
	}
	$drafted++;
}

WP_CLI::success( sprintf(
	'%s: %d drafted, %d already non-publish, %d missing.',
	$apply ? 'Applied' : 'Dry run',
	$drafted,
	$unchanged,
	$missing
) );
