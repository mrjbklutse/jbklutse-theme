<?php
/**
 * JBKlutse — Opportunities Vertical (Phase 3.1 Foundation)
 *
 * Registers a custom post type `opportunity` for the /opportunities/ vertical:
 *   /opportunities/                    -> CPT main archive
 *   /opportunities/jobs/               -> taxonomy archive (filter by type)
 *   /opportunities/jobs/<post-slug>/   -> single opportunity post
 *
 * Categories (taxonomy `opportunity_type`, 10 default terms):
 *   jobs, scholarships, fellowships, grants, deals, events,
 *   webinars, bootcamps, creators, telco-promos
 *
 * Custom meta fields exposed via REST so the publishing pipeline can set:
 *   deadline, value (USD/GHS), location, eligibility, benefits,
 *   apply URL, source URL, featured/expired flags.
 *
 * Auto-expire cron runs daily and flags posts past their deadline with
 * `_expired = 1`. Posts STAY PUBLISHED (good for SEO) — templates show
 * an EXPIRED badge based on the flag.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * 1. Register the custom post type.
 */
add_action( 'init', 'jbk_register_opportunity_cpt', 0 );
function jbk_register_opportunity_cpt() {
    register_post_type( 'opportunity', [
        'labels' => [
            'name'               => 'Opportunities',
            'singular_name'      => 'Opportunity',
            'add_new'            => 'Add New Opportunity',
            'add_new_item'       => 'Add New Opportunity',
            'edit_item'          => 'Edit Opportunity',
            'new_item'           => 'New Opportunity',
            'view_item'          => 'View Opportunity',
            'view_items'         => 'View Opportunities',
            'search_items'       => 'Search Opportunities',
            'not_found'          => 'No opportunities found',
            'not_found_in_trash' => 'No opportunities in trash',
            'all_items'          => 'All Opportunities',
            'menu_name'          => 'Opportunities',
        ],
        'public'              => true,
        'show_in_rest'        => true,
        'rest_base'           => 'opportunities',
        'has_archive'         => 'opportunities',
        'rewrite'             => [
            'slug'       => 'opportunities/%opportunity_type%',
            'with_front' => false,
            'feeds'      => true,
            'pages'      => true,
        ],
        'menu_icon'           => 'dashicons-megaphone',
        'menu_position'       => 6,
        'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'author' ],
        'taxonomies'          => [ 'opportunity_type' ],
        'hierarchical'        => false,
    ] );
}


/**
 * 2. Register the categorisation taxonomy. We set rewrite=false here and
 * add custom rewrite rules below so the taxonomy archive shares the
 * /opportunities/<type>/ URL space with the CPT.
 */
add_action( 'init', 'jbk_register_opportunity_type', 0 );
function jbk_register_opportunity_type() {
    register_taxonomy( 'opportunity_type', [ 'opportunity' ], [
        'labels' => [
            'name'              => 'Opportunity Types',
            'singular_name'     => 'Opportunity Type',
            'all_items'         => 'All Types',
            'edit_item'         => 'Edit Type',
            'view_item'         => 'View Type',
            'add_new_item'      => 'Add New Type',
            'new_item_name'     => 'New Type Name',
            'search_items'      => 'Search Types',
            'menu_name'         => 'Opportunity Types',
        ],
        'public'             => true,
        'show_in_rest'       => true,
        'show_admin_column'  => true,
        'hierarchical'       => false,
        'rewrite'            => false, // custom rewrite rules below
    ] );
}


/**
 * 3. Custom rewrite rules.
 *
 *   /opportunities/jobs/                -> taxonomy archive for jobs
 *   /opportunities/jobs/page/2/         -> paged
 */
add_action( 'init', 'jbk_opportunity_rewrites', 1 );
function jbk_opportunity_rewrites() {
    // Register the placeholder as a real rewrite tag so WordPress
    // substitutes it when generating CPT rules. WITHOUT this, generated
    // rules contain literal "%opportunity_type%" and single posts 404.
    add_rewrite_tag( '%opportunity_type%', '([^/]+)', 'opportunity_type=' );

    // Custom rule: /opportunities/<type>/ -> taxonomy archive
    add_rewrite_rule(
        '^opportunities/([^/]+)/?$',
        'index.php?opportunity_type=$matches[1]',
        'top'
    );
    add_rewrite_rule(
        '^opportunities/([^/]+)/page/([0-9]+)/?$',
        'index.php?opportunity_type=$matches[1]&paged=$matches[2]',
        'top'
    );
}


/**
 * 4. Replace the %opportunity_type% placeholder in single-post permalinks
 * with the actual taxonomy term slug. Without this, single posts would
 * have literal "%opportunity_type%" in their URL.
 */
add_filter( 'post_type_link', 'jbk_opportunity_permalink', 10, 2 );
function jbk_opportunity_permalink( $url, $post ) {
    if ( $post->post_type !== 'opportunity' ) {
        return $url;
    }
    $terms = wp_get_object_terms( $post->ID, 'opportunity_type' );
    $slug  = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->slug : 'general';
    return str_replace( '%opportunity_type%', $slug, $url );
}


/**
 * 5. Insert the 10 default taxonomy terms once.
 */
add_action( 'init', 'jbk_opportunity_seed_terms', 5 );
function jbk_opportunity_seed_terms() {
    if ( get_option( 'jbk_opportunity_terms_seeded' ) ) {
        return;
    }
    $defaults = [
        'jobs'         => 'Jobs',
        'scholarships' => 'Scholarships',
        'fellowships'  => 'Fellowships',
        'grants'       => 'Grants',
        'deals'        => 'Deals',
        'events'       => 'Events',
        'webinars'     => 'Webinars',
        'bootcamps'    => 'Bootcamps',
        'creators'     => 'Creators',
        'telco-promos' => 'Telco Promos',
    ];
    foreach ( $defaults as $slug => $name ) {
        if ( ! term_exists( $slug, 'opportunity_type' ) ) {
            wp_insert_term( $name, 'opportunity_type', [ 'slug' => $slug ] );
        }
    }
    update_option( 'jbk_opportunity_terms_seeded', '1' );
}


/**
 * 6. Flush rewrite rules once after deploy. Bump $version when the rules
 * change to trigger another flush.
 */
add_action( 'init', 'jbk_opportunity_maybe_flush_rules', 99 );
function jbk_opportunity_maybe_flush_rules() {
    $version = '1.1'; // bumped after add_rewrite_tag fix
    if ( get_option( 'jbk_opportunity_rules_version' ) !== $version ) {
        flush_rewrite_rules( false );
        update_option( 'jbk_opportunity_rules_version', $version );
    }
}


/**
 * 7. Register custom post-meta fields. All exposed in REST so the future
 * publishing pipeline can set them via /wp/v2/opportunities REST endpoint.
 */
add_action( 'init', 'jbk_opportunity_register_meta', 10 );
function jbk_opportunity_register_meta() {
    $fields = [
        // Deadline
        '_deadline'        => [ 'string',  'Application deadline (YYYY-MM-DD)' ],
        '_deadline_text'   => [ 'string',  'Free-form deadline text (e.g. "Rolling")' ],
        // Value
        '_value_usd'       => [ 'number',  'Award value in USD' ],
        '_value_ghs'       => [ 'number',  'Award value in GHS' ],
        '_value_text'      => [ 'string',  'Free-form value description' ],
        // Logistics
        '_location'        => [ 'string',  'Location' ],
        '_remote_ok'       => [ 'boolean', 'Remote work allowed' ],
        '_organization'    => [ 'string',  'Sponsoring organisation' ],
        // Detail
        '_eligibility'     => [ 'string',  'Eligibility criteria' ],
        '_benefits'        => [ 'string',  'Benefits offered' ],
        // CTA
        '_apply_url'       => [ 'string',  'Direct application URL' ],
        '_source_url'      => [ 'string',  'Original discovery source' ],
        // Lifecycle
        '_expired'         => [ 'boolean', 'Deadline has passed' ],
        '_featured'        => [ 'boolean', 'Sponsor-paid featured listing' ],
        '_featured_until'  => [ 'string',  'Featured until (YYYY-MM-DD)' ],
        // Tracking
        '_apply_clicks'    => [ 'integer', 'Number of Apply Now clicks' ],
    ];
    foreach ( $fields as $key => $def ) {
        register_post_meta( 'opportunity', $key, [
            'type'              => $def[0],
            'description'       => $def[1],
            'single'            => true,
            'show_in_rest'      => true,
            'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
        ] );
    }
}


/**
 * 8. Daily auto-expire sweep. Past-deadline posts get `_expired = 1`.
 * They stay published (SEO juice preserved) — templates show an
 * EXPIRED badge based on this flag.
 */
add_action( 'init', 'jbk_opportunity_schedule_expire_sweep' );
function jbk_opportunity_schedule_expire_sweep() {
    if ( ! wp_next_scheduled( 'jbk_opportunity_expire_sweep' ) ) {
        wp_schedule_event( time() + 60, 'daily', 'jbk_opportunity_expire_sweep' );
    }
}

add_action( 'jbk_opportunity_expire_sweep', 'jbk_opportunity_run_expire_sweep' );
function jbk_opportunity_run_expire_sweep() {
    $today = wp_date( 'Y-m-d' );
    $ids   = get_posts( [
        'post_type'      => 'opportunity',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'     => '_deadline',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ],
            [
                'relation' => 'OR',
                [ 'key' => '_expired', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_expired', 'value' => '1', 'compare' => '!=' ],
            ],
        ],
    ] );
    foreach ( $ids as $id ) {
        update_post_meta( $id, '_expired', '1' );
    }
    if ( $ids ) {
        error_log( sprintf( '[jbk_opportunity_expire_sweep] flagged %d posts as expired', count( $ids ) ) );
    }
}


/**
 * 9. EXPIRED badge on titles when shown in the loop on opportunity posts.
 */
add_filter( 'the_title', 'jbk_opportunity_expired_title', 10, 2 );
function jbk_opportunity_expired_title( $title, $post_id = 0 ) {
    if ( ! $post_id || ! in_the_loop() ) return $title;
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'opportunity' ) return $title;
    if ( get_post_meta( $post_id, '_expired', true ) === '1' ) {
        return '<span class="jbk-expired-badge" style="background:#9ca3af;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.7em;vertical-align:middle;margin-right:8px;font-weight:bold;">EXPIRED</span>' . $title;
    }
    return $title;
}


/**
 * 10. Append a basic Apply Now box + opportunity meta to the_content on
 * single opportunity posts.
 *
 * DEPRECATED in Phase 3.5: single-opportunity.php now renders the hero +
 * apply box natively. We disable this content-filter injection if the
 * custom single template is being used (avoids duplicate Apply boxes).
 */
add_filter( 'the_content', 'jbk_opportunity_inject_meta_box', 5 );
function jbk_opportunity_inject_meta_box( $content ) {
    if ( ! is_singular( 'opportunity' ) || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    // If the custom single-opportunity.php template is being used, it
    // already renders the hero + apply box. Don't duplicate.
    if ( locate_template( 'single-opportunity.php' ) ) {
        return $content;
    }
    $post_id = get_the_ID();
    $expired = get_post_meta( $post_id, '_expired', true ) === '1';

    $deadline       = esc_html( get_post_meta( $post_id, '_deadline', true ) );
    $deadline_text  = esc_html( get_post_meta( $post_id, '_deadline_text', true ) );
    $value_usd      = get_post_meta( $post_id, '_value_usd', true );
    $value_ghs      = get_post_meta( $post_id, '_value_ghs', true );
    $value_text     = esc_html( get_post_meta( $post_id, '_value_text', true ) );
    $location       = esc_html( get_post_meta( $post_id, '_location', true ) );
    $organization   = esc_html( get_post_meta( $post_id, '_organization', true ) );
    $eligibility    = esc_html( get_post_meta( $post_id, '_eligibility', true ) );
    $benefits       = esc_html( get_post_meta( $post_id, '_benefits', true ) );
    $apply_url      = esc_url( get_post_meta( $post_id, '_apply_url', true ) );

    $facts = [];
    if ( $organization ) $facts[] = '<strong>Organisation:</strong> ' . $organization;
    if ( $location )     $facts[] = '<strong>Location:</strong> '     . $location;
    if ( $deadline || $deadline_text ) {
        $facts[] = '<strong>Deadline:</strong> ' . ( $deadline ? $deadline : $deadline_text );
    }
    if ( $value_usd || $value_ghs || $value_text ) {
        $vparts = [];
        if ( $value_usd ) $vparts[] = 'USD ' . number_format( (float) $value_usd );
        if ( $value_ghs ) $vparts[] = 'GHS ' . number_format( (float) $value_ghs );
        if ( $value_text ) $vparts[] = $value_text;
        $facts[] = '<strong>Value:</strong> ' . implode( ' (', $vparts ) . ( count( $vparts ) > 1 ? ')' : '' );
    }

    ob_start();
    ?>
    <div class="jbk-opp-box" style="border:1px solid #e5e7eb;border-radius:8px;padding:18px 22px;margin:0 0 22px 0;background:#f9fafb;">
        <?php if ( $expired ) : ?>
            <div style="background:#9ca3af;color:#fff;padding:6px 12px;border-radius:4px;display:inline-block;font-weight:bold;font-size:13px;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:12px;">Expired</div>
        <?php endif; ?>
        <?php if ( $facts ) : ?>
            <ul style="margin:0;padding:0;list-style:none;">
                <?php foreach ( $facts as $f ) : ?>
                    <li style="padding:4px 0;"><?php echo wp_kses_post( $f ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ( $eligibility ) : ?>
            <p style="margin:14px 0 6px 0;"><strong>Eligibility:</strong> <?php echo wp_kses_post( $eligibility ); ?></p>
        <?php endif; ?>
        <?php if ( $benefits ) : ?>
            <p style="margin:6px 0;"><strong>Benefits:</strong> <?php echo wp_kses_post( $benefits ); ?></p>
        <?php endif; ?>
        <?php if ( $apply_url && ! $expired ) :
            $tracked_url = add_query_arg( [
                'utm_source'   => 'jbklutse',
                'utm_medium'   => 'opportunity',
                'utm_campaign' => 'apply',
                'utm_content'  => $post_id,
            ], $apply_url );
        ?>
            <a class="jbk-apply-btn"
               href="<?php echo esc_url( $tracked_url ); ?>"
               target="_blank" rel="noopener nofollow sponsored"
               onclick="if(navigator.sendBeacon){navigator.sendBeacon('/wp-json/jbklutse/v1/track-apply-click',new Blob([JSON.stringify({post_id:<?php echo (int) $post_id; ?>})],{type:'application/json'}));}"
               style="display:inline-block;background:#fbbf24;color:#0f172a;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:16px;margin-top:14px;">
                Apply Now &rarr;
            </a>
        <?php endif; ?>
    </div>
    <?php
    $box = ob_get_clean();
    return $box . $content;
}


/**
 * 11. Apply Now click tracking endpoint (increments _apply_clicks).
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'jbklutse/v1', '/track-apply-click', [
        'methods'             => 'POST',
        'permission_callback' => '__return_true',
        'args'                => [ 'post_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ] ],
        'callback'            => function ( WP_REST_Request $req ) {
            $pid = (int) $req->get_param( 'post_id' );
            if ( ! $pid || get_post_type( $pid ) !== 'opportunity' ) {
                return new WP_REST_Response( [ 'ok' => false ], 400 );
            }
            $current = (int) get_post_meta( $pid, '_apply_clicks', true );
            update_post_meta( $pid, '_apply_clicks', $current + 1 );
            return new WP_REST_Response( [ 'ok' => true, 'clicks' => $current + 1 ], 200 );
        },
    ] );
} );


/**
 * 12. Override main query for /opportunities/ archive: only show non-expired
 * by default (expired ones still accessible via direct URL for SEO).
 * Featured listings sort to top.
 */
add_action( 'pre_get_posts', 'jbk_opportunity_archive_query' );
function jbk_opportunity_archive_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( ! $query->is_post_type_archive( 'opportunity' )
        && ! $query->is_tax( 'opportunity_type' ) ) return;

    // Hide expired by default
    $meta_query = $query->get( 'meta_query' ) ?: [];
    $meta_query[] = [
        'relation' => 'OR',
        [ 'key' => '_expired', 'compare' => 'NOT EXISTS' ],
        [ 'key' => '_expired', 'value' => '1', 'compare' => '!=' ],
    ];
    $query->set( 'meta_query', $meta_query );

    // Sort: featured first, then deadline ascending
    $query->set( 'orderby', [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ] );
    $query->set( 'meta_key', '_featured' );
    $query->set( 'posts_per_page', 20 );
}
