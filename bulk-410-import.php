<?php
/**
 * Bulk-import 410 Gone rules into Rank Math redirections from a CSV.
 *
 * CSV columns (in order): url, action, target, gsc_clicks, gsc_impressions, reason
 * Only rows with action == '410_gone' are processed.
 *
 * Defensive: any URL whose path resolves to an existing live post/page is
 * SKIPPED with a warning - we never 410 a URL that's still serving content.
 *
 * Idempotent: existing 410 rule for the same source path is left in place.
 *
 * Run (dry-run): wp eval-file bulk-410-import.php
 * Run (apply):   APPLY=1 wp eval-file bulk-410-import.php
 */

global $wpdb;
$apply = getenv( 'APPLY' ) === '1';
$csv   = __DIR__ . '/seo-analysis-output/decisions/review-608-classified.csv';
$table = $wpdb->prefix . 'rank_math_redirections';

if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
	WP_CLI::error( "Rank Math redirections table not found: $table" );
}
if ( ! file_exists( $csv ) ) {
	WP_CLI::error( "CSV not found: $csv" );
}

$rows    = array_map( 'str_getcsv', file( $csv ) );
$headers = array_shift( $rows );

$now      = current_time( 'mysql' );
$inserted = 0;
$existing_count = 0;
$skipped_live   = 0;
$skipped_other  = 0;

foreach ( $rows as $row ) {
	if ( count( $row ) < 2 ) {
		continue;
	}
	$url    = trim( $row[0] );
	$action = trim( $row[1] );
	if ( $action !== '410_gone' ) {
		$skipped_other++;
		continue;
	}

	$path = ltrim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
	if ( $path === '' ) {
		continue;
	}

	// If a URL resolves to a live post under a different (category-prefix) path,
	// emit a 301 to the canonical permalink instead of a 410.
	$slug = basename( rtrim( $path, '/' ) );
	$live = get_page_by_path( $slug, OBJECT, array( 'post', 'page' ) );

	$sources    = array(
		array(
			'pattern'    => $path,
			'comparison' => 'exact',
			'ignore'     => '',
		),
	);
	$serialized = serialize( $sources );

	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $table WHERE sources = %s LIMIT 1",
		$serialized
	) );
	if ( $existing ) {
		$existing_count++;
		continue;
	}

	if ( $live && $live->post_status === 'publish' ) {
		$canonical = get_permalink( $live );
		WP_CLI::log( sprintf( '[301] %s -> %s', $url, $canonical ) );
		if ( $apply ) {
			$wpdb->insert(
				$table,
				array(
					'sources'     => $serialized,
					'url_to'      => $canonical,
					'header_code' => 301,
					'hits'        => 0,
					'status'      => 'active',
					'created'     => $now,
					'updated'     => $now,
				),
				array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
			);
		}
		$skipped_live++;
		continue;
	}

	if ( $apply ) {
		$wpdb->insert(
			$table,
			array(
				'sources'     => $serialized,
				'url_to'      => '',
				'header_code' => 410,
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

WP_CLI::success( sprintf(
	'%s: %d 410 inserted, %d 301 redirected to live canonical, %d already existed, %d skipped (other action).',
	$apply ? 'Applied' : 'Dry run',
	$inserted,
	$skipped_live,
	$existing_count,
	$skipped_other
) );
