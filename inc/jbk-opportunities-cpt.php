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
 * 7b. Register Rank Math's own meta keys for the `opportunity` post type.
 *
 * WordPress REST silently drops any meta key that isn't registered with
 * `show_in_rest=true` for a given post type. Rank Math auto-registers its
 * meta on `post` and `page`, but NOT on custom post types. Without this
 * block, the publishing pipeline POSTs `rank_math_focus_keyword`, `_title`,
 * `_description`, `_facebook_image` etc. and they vanish into the void —
 * the Rank Math admin column then shows "Keyword: Not Set / Schema: Off".
 *
 * Registering them here makes them REST-writable so the existing drafter
 * code (which already sends them) starts saving correctly. Use the
 * companion backfill script `scripts/backfill_opportunity_seo.py` to fill
 * the 22 already-published opportunity posts.
 */
add_action( 'init', 'jbk_opportunity_register_rank_math_meta', 11 );
function jbk_opportunity_register_rank_math_meta() {
    $rm_string_keys = [
        // Core SEO meta (the four big rank levers)
        'rank_math_focus_keyword',
        'rank_math_title',
        'rank_math_description',
        'rank_math_canonical_url',
        // Cornerstone / pillar flag (clusters set "", pillars set "on")
        'rank_math_pillar_content',
        // OG / social
        'rank_math_facebook_image',
        'rank_math_facebook_title',
        'rank_math_facebook_description',
        'rank_math_twitter_image',
        'rank_math_twitter_title',
        'rank_math_twitter_description',
        // News + Sitemap controls
        'rank_math_news_sitemap_robots',
        // Schema (set to "article", "jobposting", "course", "event", etc.)
        'rank_math_rich_snippet',
        // Common JobPosting schema fields (used when rank_math_rich_snippet=jobposting)
        'rank_math_snippet_jobposting_salary',
        'rank_math_snippet_jobposting_employment_type',
        'rank_math_snippet_jobposting_organization',
        // Common Event schema fields
        'rank_math_snippet_event_status',
        'rank_math_snippet_event_attendance_mode',
        'rank_math_snippet_event_location',
        // Course schema fields (scholarships / bootcamps)
        'rank_math_snippet_course_provider',
        'rank_math_snippet_course_provider_url',
    ];
    foreach ( $rm_string_keys as $key ) {
        register_post_meta( 'opportunity', $key, [
            'type'          => 'string',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ] );
    }

    // Robots is an array of directive strings (e.g. ["index","follow",
    // "max-snippet:-1","max-image-preview:large","max-video-preview:-1"]).
    // REST needs the schema spec to accept JSON array writes.
    register_post_meta( 'opportunity', 'rank_math_robots', [
        'type'          => 'array',
        'single'        => true,
        'show_in_rest'  => [
            'schema' => [
                'type'  => 'array',
                'items' => [ 'type' => 'string' ],
            ],
        ],
        'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
    ] );

    // Schema Builder meta keys (Rank Math 1.0.268+). Each schema TYPE is
    // stored under its own meta key (rank_math_schema_JobPosting,
    // rank_math_schema_Course, rank_math_schema_Event, rank_math_schema_Article)
    // as an associative array with type-specific fields + a `metadata`
    // sub-array that drives the admin "Schema:" column display. Registered
    // with type=object so REST accepts the structured JSON the drafter
    // sends; WP auto-serializes to PHP-serialized format on write.
    $schema_builder_keys = [
        'rank_math_schema_JobPosting',
        'rank_math_schema_Course',
        'rank_math_schema_Event',
        'rank_math_schema_Article',
    ];
    foreach ( $schema_builder_keys as $key ) {
        register_post_meta( 'opportunity', $key, [
            'type'          => 'object',
            'single'        => true,
            'show_in_rest'  => [
                'schema' => [
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ],
            ],
            'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
        ] );
    }
}


/**
 * 7c. Set sensible Rank Math defaults for the `opportunity` post type the
 * first time we see this version of the file. Two changes:
 *
 *   - Default schema → "article" so opportunity posts produce Article
 *     structured data out of the box. Per-type schemas (JobPosting, Event,
 *     Course) can be set per-post via rank_math_rich_snippet.
 *   - Include opportunity posts in the XML sitemap.
 *
 * Versioned via an option flag so we don't fight admin edits — the user
 * can override these defaults in Rank Math UI and we won't clobber them
 * on later runs.
 */
add_action( 'init', 'jbk_opportunity_set_rank_math_defaults', 99 );
function jbk_opportunity_set_rank_math_defaults() {
    if ( get_option( 'jbk_opp_rank_math_defaults_v' ) === '1' ) {
        return;
    }

    $titles = (array) get_option( 'rank-math-options-titles', [] );
    $changed = false;

    if ( empty( $titles['pt_opportunity_default_rich_snippet'] ) ) {
        $titles['pt_opportunity_default_rich_snippet'] = 'article';
        $changed = true;
    }
    if ( empty( $titles['pt_opportunity_default_article_type'] ) ) {
        $titles['pt_opportunity_default_article_type'] = 'Article';
        $changed = true;
    }
    if ( ! isset( $titles['pt_opportunity_sitemap'] ) ) {
        $titles['pt_opportunity_sitemap'] = 'on';
        $changed = true;
    }
    if ( empty( $titles['pt_opportunity_robots'] ) ) {
        $titles['pt_opportunity_robots'] = [ 'index' ];
        $changed = true;
    }
    if ( empty( $titles['pt_opportunity_advanced_robots'] ) ) {
        $titles['pt_opportunity_advanced_robots'] = [
            'max-snippet'        => '-1',
            'max-video-preview'  => '-1',
            'max-image-preview'  => 'large',
        ];
        $changed = true;
    }

    if ( $changed ) {
        update_option( 'rank-math-options-titles', $titles );
    }
    update_option( 'jbk_opp_rank_math_defaults_v', '1' );
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

    // Pass 1 — concrete deadline in the past
    $dated_expired = get_posts( [
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

    // Pass 2 — undated posts older than 60 days from publish.
    //
    // Without this rule, an opportunity ingested with no extractable
    // deadline (and no `Rolling` text fallback in the legacy queue) would
    // sit on /opportunities/ forever. 60 days is conservative — long
    // enough to keep genuinely-rolling postings live, short enough to
    // stop "while-stocks-last"-type promos drifting indefinitely.
    $undated_old = get_posts( [
        'post_type'      => 'opportunity',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'date_query'     => [
            [ 'before' => '-60 days' ],
        ],
        'meta_query'     => [
            'relation' => 'AND',
            [
                'relation' => 'OR',
                [ 'key' => '_deadline',      'compare' => 'NOT EXISTS' ],
                [ 'key' => '_deadline',      'value' => '', 'compare' => '=' ],
            ],
            [
                'relation' => 'OR',
                [ 'key' => '_deadline_text', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_deadline_text', 'value' => '', 'compare' => '=' ],
            ],
            [
                'relation' => 'OR',
                [ 'key' => '_expired', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_expired', 'value' => '1', 'compare' => '!=' ],
            ],
        ],
    ] );

    $ids = array_unique( array_merge( $dated_expired, $undated_old ) );
    foreach ( $ids as $id ) {
        update_post_meta( $id, '_expired', '1' );
    }
    if ( $ids ) {
        error_log( sprintf(
            '[jbk_opportunity_expire_sweep] flagged %d posts as expired (dated: %d, undated>60d: %d)',
            count( $ids ), count( $dated_expired ), count( $undated_old )
        ) );
    }
}


/**
 * 8c. Soft-retire expired opportunities — apply `noindex` via Rank Math's
 * frontend robots filter so Google de-indexes them, while keeping the post
 * accessible at its URL (preserves any inbound backlinks and direct-link
 * referrals; templates still show the EXPIRED badge).
 *
 * Logic:
 *   - On a single opportunity post with _expired=1 → robots = noindex,follow
 *   - Everywhere else → leave the existing robots untouched.
 *
 * This sits AFTER our existing `jbk_pr_rank_math_robots` filter (priority
 * 10) so press-releases keep their own noindex rules and opportunity
 * filters compose cleanly.
 */
add_filter( 'rank_math/frontend/robots', 'jbk_opportunity_robots_noindex_when_expired', 11, 1 );
function jbk_opportunity_robots_noindex_when_expired( $robots ) {
    if ( ! is_singular( 'opportunity' ) ) {
        return $robots;
    }
    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return $robots;
    }
    if ( get_post_meta( $post_id, '_expired', true ) !== '1' ) {
        return $robots;
    }

    // Force noindex,follow. Drop any conflicting "index" directive Rank Math
    // may have already added so the resulting tag doesn't say "index, noindex"
    // (which Google ignores in favour of the more permissive directive).
    if ( ! is_array( $robots ) ) {
        $robots = [];
    }
    $robots['index']   = 'noindex';
    $robots['follow']  = 'follow';
    return $robots;
}


/**
 * 8d. Keep expired opportunities out of XML sitemaps. Rank Math's
 * `posts_to_exclude` filter accepts an array of post IDs to drop from
 * sitemap generation.
 */
add_filter( 'rank_math/sitemap/posts_to_exclude', 'jbk_opportunity_sitemap_exclude_expired', 10, 1 );
function jbk_opportunity_sitemap_exclude_expired( $excluded ) {
    $expired_ids = get_posts( [
        'post_type'              => 'opportunity',
        'post_status'            => 'publish',
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'meta_query'             => [
            [ 'key' => '_expired', 'value' => '1', 'compare' => '=' ],
        ],
    ] );
    return array_values( array_unique( array_map(
        'absint',
        array_merge( (array) $excluded, $expired_ids )
    ) ) );
}


/**
 * 8b. Force our PHP templates to win over the block theme's template
 * resolution. Block themes (FSE) prefer templates/*.html over PHP templates
 * at the same hierarchy level, so our brand-styled single/archive/taxonomy
 * templates get bypassed. This filter runs LATE and asserts the PHP file
 * for opportunity-related routes.
 */
add_filter( 'template_include', function ( $template ) {
    $candidate = '';
    if ( is_singular( 'opportunity' ) ) {
        $candidate = get_theme_file_path( 'single-opportunity.php' );
    } elseif ( is_tax( 'opportunity_type' ) ) {
        $candidate = get_theme_file_path( 'taxonomy-opportunity_type.php' );
    } elseif ( is_post_type_archive( 'opportunity' ) ) {
        $candidate = get_theme_file_path( 'archive-opportunity.php' );
    }
    if ( $candidate && file_exists( $candidate ) ) {
        return $candidate;
    }
    return $template;
}, 99 );


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

    // Featured-first ordering. We use a NAMED meta_query clause + an
    // EXISTS / NOT EXISTS pair so WP issues a LEFT JOIN — posts WITHOUT a
    // `_featured` meta row still appear in the result set. Using the older
    // `meta_key` shortcut would force an INNER JOIN and silently drop
    // every post that hasn't been explicitly marked featured (i.e. all of
    // them, since the drafter doesn't set this field by default). That
    // bug caused /opportunities/ to render empty for every published post.
    $meta_query['featured_clause'] = [
        'relation' => 'OR',
        [ 'key' => '_featured', 'value' => '1', 'compare' => '=' ],
        [ 'key' => '_featured', 'compare' => 'NOT EXISTS' ],
    ];
    $query->set( 'meta_query', $meta_query );
    $query->set( 'orderby', [ 'featured_clause' => 'DESC', 'date' => 'DESC' ] );
    $query->set( 'posts_per_page', 20 );
}


/**
 * Homepage Opportunities query loop (queryId=17 in templates/home.html).
 *
 * The block UI can't express a meta_query, so we inject one here:
 * hide expired posts the same way the archive does. We also force
 * featured-first ordering so curated picks land at the top of the
 * homepage section.
 */
add_filter( 'query_loop_block_query_vars', function ( $query, $block ) {
    if ( ! isset( $block->context['queryId'] ) ) return $query;
    if ( (int) $block->context['queryId'] !== 17 ) return $query;

    $meta_query = isset( $query['meta_query'] ) && is_array( $query['meta_query'] )
        ? $query['meta_query']
        : [];

    $meta_query[] = [
        'relation' => 'OR',
        [ 'key' => '_expired', 'compare' => 'NOT EXISTS' ],
        [ 'key' => '_expired', 'value' => '1', 'compare' => '!=' ],
    ];
    $meta_query['featured_clause'] = [
        'relation' => 'OR',
        [ 'key' => '_featured', 'value' => '1', 'compare' => '=' ],
        [ 'key' => '_featured', 'compare' => 'NOT EXISTS' ],
    ];

    $query['meta_query'] = $meta_query;
    $query['orderby']    = [ 'featured_clause' => 'DESC', 'date' => 'DESC' ];

    return $query;
}, 10, 2 );


/**
 * 13. Type-aware schema injection on single opportunity pages.
 *
 * Why: Rank Math's per-post `rank_math_rich_snippet` selector (we set this
 * to "jobposting", "course", "event", "article" during the SEO backfill)
 * tells Rank Math which schema TYPE to generate, but it ALSO needs the
 * structured fields populated (hiringOrganization, jobLocation,
 * validThrough, employmentType, etc.) — and those live in Rank Math's
 * own `rank_math_snippet_jobposting_*` fields, which we don't fill. If
 * those fields are empty, Rank Math silently skips JSON-LD generation
 * for that schema type, and the opp page ends up shipping only a
 * BreadcrumbList — useless for Google's Job/Course/Event rich results.
 *
 * Fix: we already collect the data needed (_organization, _location,
 * _deadline, _apply_url, _value_usd, post_content excerpt). Build the
 * JSON-LD ourselves from that meta and inject it into Rank Math's @graph
 * via the `rank_math/json_ld` filter. One filter, all opp types.
 *
 * Schema map:
 *   jobs                         → JobPosting
 *   scholarships, fellowships,
 *   grants, bootcamps            → Course
 *   events, webinars             → Event (Online if webinar)
 *   deals, telco-promos,
 *   software, creators           → Article (Rank Math default — no override)
 */
add_filter( 'rank_math/json_ld', 'jbk_opportunity_inject_schema', 99, 2 );
function jbk_opportunity_inject_schema( $data, $jsonld ) {
    if ( ! is_singular( 'opportunity' ) ) {
        return $data;
    }

    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return $data;
    }

    // Determine opp type from taxonomy
    $terms = get_the_terms( $post_id, 'opportunity_type' );
    $type_slug = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
    if ( ! $type_slug ) {
        return $data;
    }

    // Collect post + meta we'll use across schema types
    $title         = wp_strip_all_tags( get_the_title( $post_id ) );
    $url           = get_permalink( $post_id );
    $thumb_url     = get_the_post_thumbnail_url( $post_id, 'large' );
    $description   = get_post_meta( $post_id, 'rank_math_description', true );
    if ( ! $description ) {
        $description = wp_strip_all_tags( get_the_excerpt( $post_id ) );
    }
    $description   = mb_substr( $description, 0, 500 );
    $date_posted   = get_the_date( 'c', $post_id );
    $organization  = get_post_meta( $post_id, '_organization', true );
    $location      = get_post_meta( $post_id, '_location', true );
    $deadline      = get_post_meta( $post_id, '_deadline', true );
    $apply_url     = get_post_meta( $post_id, '_apply_url', true );
    $source_url    = get_post_meta( $post_id, '_source_url', true );
    $value_usd     = get_post_meta( $post_id, '_value_usd', true );
    $value_ghs     = get_post_meta( $post_id, '_value_ghs', true );
    $expired       = get_post_meta( $post_id, '_expired', true ) === '1';

    $body_text = wp_strip_all_tags( get_post_field( 'post_content', $post_id ) );
    $haystack  = strtolower( $body_text . ' ' . $title . ' ' . $location );

    $schema = null;

    // ── JobPosting ──
    if ( $type_slug === 'jobs' ) {
        $schema = [
            '@type'              => 'JobPosting',
            'title'              => $title,
            'description'        => $description,
            'datePosted'         => $date_posted,
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name'  => $organization ?: 'JBKlutse',
            ],
        ];
        if ( $apply_url ) {
            $schema['url'] = $apply_url;
        }
        if ( $deadline ) {
            $schema['validThrough'] = date( 'c', strtotime( $deadline ) );
        }

        // Employment type (best-effort from title + body)
        $emp = 'OTHER';
        if ( preg_match( '/\b(full[- ]?time|permanent|salary)\b/i', $haystack ) )      { $emp = 'FULL_TIME'; }
        elseif ( preg_match( '/\bpart[- ]?time\b/i', $haystack ) )                     { $emp = 'PART_TIME'; }
        elseif ( preg_match( '/\b(contract(or)?|freelance|consultan(t|cy))\b/i', $haystack ) ) { $emp = 'CONTRACTOR'; }
        elseif ( preg_match( '/\bintern(ship)?\b/i', $haystack ) )                     { $emp = 'INTERN'; }
        elseif ( preg_match( '/\btempor(ary|ar)\b/i', $haystack ) )                    { $emp = 'TEMPORARY'; }
        $schema['employmentType'] = $emp;

        // Remote vs onsite
        $is_remote = (bool) preg_match( '/\bremote\b|\btelecommute\b|\bwork from home\b/i', $haystack );
        if ( $is_remote ) {
            $schema['jobLocationType']            = 'TELECOMMUTE';
            $schema['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name'  => 'GH',
            ];
        } elseif ( $location ) {
            $schema['jobLocation'] = [
                '@type'   => 'Place',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $location,
                    'addressCountry'  => 'GH',
                ],
            ];
        } else {
            // Fall back to country-only so Google doesn't reject the listing
            $schema['jobLocation'] = [
                '@type'   => 'Place',
                'address' => [
                    '@type'          => 'PostalAddress',
                    'addressCountry' => 'GH',
                ],
            ];
        }

        // Salary (only when we know it concretely)
        if ( $value_usd && (float) $value_usd > 0 ) {
            $schema['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => 'USD',
                'value'    => [
                    '@type'    => 'QuantitativeValue',
                    'value'    => (float) $value_usd,
                    'unitText' => 'YEAR',
                ],
            ];
        } elseif ( $value_ghs && (float) $value_ghs > 0 ) {
            $schema['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => 'GHS',
                'value'    => [
                    '@type'    => 'QuantitativeValue',
                    'value'    => (float) $value_ghs,
                    'unitText' => 'YEAR',
                ],
            ];
        }

        // directApply: false signals applicants leave the site to apply
        if ( $apply_url ) {
            $schema['directApply'] = false;
        }
    }

    // ── Course (scholarships, fellowships, grants, bootcamps) ──
    elseif ( in_array( $type_slug, [ 'scholarships', 'fellowships', 'grants', 'bootcamps' ], true ) ) {
        $schema = [
            '@type'       => 'Course',
            'name'        => $title,
            'description' => $description,
            'provider'    => [
                '@type' => 'Organization',
                'name'  => $organization ?: 'JBKlutse',
                'sameAs' => $source_url ?: home_url(),
            ],
            'url'         => $url,
        ];

        // Course instance — required-recommended for Google's Course rich results
        $instance = [
            '@type'       => 'CourseInstance',
            'courseMode'  => 'online',
        ];
        if ( $deadline ) {
            // Treat deadline as the application deadline; instance starts after
            $instance['startDate'] = date( 'c', strtotime( $deadline ) );
        }
        $schema['hasCourseInstance'] = $instance;

        // Offer (free or value-bearing)
        if ( $value_usd && (float) $value_usd > 0 ) {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'category'      => 'Funding',
                'price'         => 0,
                'priceCurrency' => 'USD',
                'description'   => 'Award value: USD ' . number_format( (float) $value_usd ),
            ];
        } else {
            $schema['offers'] = [
                '@type'         => 'Offer',
                'category'      => 'Free',
                'price'         => 0,
                'priceCurrency' => 'USD',
            ];
        }
    }

    // ── Event (events, webinars) ──
    elseif ( in_array( $type_slug, [ 'events', 'webinars' ], true ) ) {
        $schema = [
            '@type'           => 'Event',
            'name'            => $title,
            'description'     => $description,
            'eventStatus'     => 'https://schema.org/EventScheduled',
            'organizer'       => [
                '@type' => 'Organization',
                'name'  => $organization ?: 'JBKlutse',
            ],
            'url'             => $url,
        ];
        if ( $deadline ) {
            $schema['startDate'] = date( 'c', strtotime( $deadline ) );
            $schema['endDate']   = date( 'c', strtotime( $deadline . ' +1 day' ) );
        }
        $is_webinar_or_remote = ( $type_slug === 'webinars' ) || (bool) preg_match( '/\bonline\b|\bvirtual\b|\bwebinar\b/i', $haystack );
        if ( $is_webinar_or_remote ) {
            $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            $schema['location'] = [
                '@type' => 'VirtualLocation',
                'url'   => $apply_url ?: $url,
            ];
        } else {
            $schema['eventAttendanceMode'] = 'https://schema.org/MixedEventAttendanceMode';
            $schema['location'] = [
                '@type'   => 'Place',
                'name'    => $location ?: 'Ghana',
                'address' => [
                    '@type'          => 'PostalAddress',
                    'addressLocality' => $location ?: 'Accra',
                    'addressCountry' => 'GH',
                ],
            ];
        }
        // Free events are explicitly marked as such for SERP filtering
        $schema['offers'] = [
            '@type'         => 'Offer',
            'price'         => 0,
            'priceCurrency' => 'USD',
            'availability'  => $expired ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
            'url'           => $apply_url ?: $url,
            'validFrom'     => $date_posted,
        ];
    }

    if ( ! $schema ) {
        return $data;
    }

    // Common image attachment
    if ( $thumb_url ) {
        $schema['image'] = $thumb_url;
    }

    // Dedup: when a Schema Builder meta entry (rank_math_schema_<Type>)
    // also exists on this post, Rank Math will already have added a node
    // of the same @type to $data. Remove any such pre-existing nodes so
    // there's exactly one @type=JobPosting/Course/Event in the @graph and
    // it's our richer version (with employmentType detection, jobLocation
    // fallbacks, applicantLocationRequirements for remote, salary, etc.).
    $expected_type = $schema['@type'];
    foreach ( $data as $existing_key => $existing_node ) {
        if ( ! is_array( $existing_node ) ) {
            continue;
        }
        $node_type = $existing_node['@type'] ?? '';
        if ( is_array( $node_type ) ) {
            $node_type = reset( $node_type );
        }
        if ( $node_type === $expected_type ) {
            unset( $data[ $existing_key ] );
        }
    }

    // Use a stable key so we replace ourselves cleanly on later filter
    // passes.
    $key = 'jbk_opportunity_' . $type_slug;
    $data[ $key ] = $schema;

    return $data;
}
