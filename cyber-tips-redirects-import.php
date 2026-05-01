<?php
/**
 * Import Cyber Tips -> Cybersecurity Awareness Month pillar redirects.
 *
 * Reads seo-analysis-output/decisions/cyber-tips-redirects.csv and inserts
 * matching rows into the Rank Math redirections table (idempotent: existing
 * source patterns are updated in place rather than duplicated).
 *
 * Usage (dry run):
 *   wp eval-file cyber-tips-redirects-import.php
 *
 * Usage (commit):
 *   wp eval-file cyber-tips-redirects-import.php apply
 *
 * Order of operations:
 *   1. Publish the pillar at /cybersecurity-awareness-month-2025/ FIRST.
 *   2. Run this script with `apply`.
 *   3. Run cyber-tips-posts-unpublish.php with `apply`.
 */

global $wpdb;

$apply = getenv( 'APPLY' ) === '1';
$csv   = __DIR__ . '/seo-analysis-output/decisions/cyber-tips-redirects.csv';
$table = $wpdb->prefix . 'rank_math_redirections';

if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
	WP_CLI::error( "Rank Math redirections table not found: {$table}" );
}

if ( ! file_exists( $csv ) ) {
	WP_CLI::error( "Redirect CSV not found: {$csv}" );
}

$pillar_slug = 'cybersecurity-awareness-month-2025';
$pillar_post = get_page_by_path( $pillar_slug, OBJECT, 'post' );
if ( ! $pillar_post || $pillar_post->post_status !== 'publish' ) {
	WP_CLI::warning( "Pillar /{$pillar_slug}/ is not published. Publish it before applying, or redirects will land on a 404." );
	if ( $apply ) {
		WP_CLI::error( 'Refusing to apply redirects without a published pillar.' );
	}
}

$rows    = array_map( 'str_getcsv', file( $csv ) );
$headers = array_shift( $rows );

$now      = current_time( 'mysql' );
$inserted = 0;
$updated  = 0;
$skipped  = 0;

foreach ( $rows as $row ) {
	if ( count( $row ) < 3 ) {
		continue;
	}
	list( $source_url, $target_url, $type ) = $row;
	$source_url = trim( $source_url );
	$target_url = trim( $target_url );
	if ( $source_url === '' || $target_url === '' ) {
		continue;
	}

	$path     = ltrim( wp_parse_url( $source_url, PHP_URL_PATH ), '/' );
	$sources  = array(
		array(
			'pattern'    => $path,
			'comparison' => 'exact',
			'ignore'     => '',
		),
	);
	$serialized = serialize( $sources );

	$existing = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, url_to, header_code, status FROM {$table} WHERE sources = %s LIMIT 1",
		$serialized
	) );

	if ( $existing ) {
		if (
			$existing->url_to === $target_url
			&& (int) $existing->header_code === (int) $type
			&& $existing->status === 'active'
		) {
			$skipped++;
			continue;
		}
		WP_CLI::log( sprintf( '[update] %s -> %s (%s)', $source_url, $target_url, $type ) );
		if ( $apply ) {
			$wpdb->update(
				$table,
				array(
					'url_to'      => $target_url,
					'header_code' => (int) $type,
					'status'      => 'active',
					'updated'     => $now,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);
		}
		$updated++;
	} else {
		WP_CLI::log( sprintf( '[insert] %s -> %s (%s)', $source_url, $target_url, $type ) );
		if ( $apply ) {
			$wpdb->insert(
				$table,
				array(
					'sources'     => $serialized,
					'url_to'      => $target_url,
					'header_code' => (int) $type,
					'hits'        => 0,
					'status'      => 'active',
					'created'     => $now,
					'updated'     => $now,
				),
				array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
			);
		}
		$inserted++;
	}
}

WP_CLI::success( sprintf(
	'%s: %d insert, %d update, %d unchanged.',
	$apply ? 'Applied' : 'Dry run',
	$inserted,
	$updated,
	$skipped
) );
