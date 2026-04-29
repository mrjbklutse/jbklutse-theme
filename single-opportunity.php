<?php
/**
 * Single Opportunity template — JBKlutse
 *
 * Renders /opportunities/<type>/<slug>/ with a hero block (org/deadline/value/
 * apply CTA), the structured listing body from PROMPT_J output, and a
 * sidebar with related opportunities of the same type.
 *
 * Auto-expire badge if past deadline. Live JS countdown for upcoming deadlines.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// FSE block theme — load header template part via block API.
// (get_header() doesn't work for block themes; we render parts/header.html directly.)
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php block_template_part( 'header' ); ?>

<style>
.jbk-opp-page{max-width:1100px;margin:30px auto;padding:0 20px;font-family:system-ui,-apple-system,sans-serif;color:#1f2937;}
.jbk-opp-page .breadcrumb{font-size:13px;color:#6b7280;margin-bottom:14px;}
.jbk-opp-page .breadcrumb a{color:#0369a1;text-decoration:none;}
.jbk-opp-grid{display:grid;grid-template-columns:1fr 320px;gap:36px;align-items:start;}
@media(max-width:860px){.jbk-opp-grid{grid-template-columns:1fr;}}

.jbk-opp-hero{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.04);margin-bottom:22px;}
.jbk-opp-tag-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.jbk-opp-tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;}
.jbk-opp-tag.type{background:#0f172a;color:#fbbf24;}
.jbk-opp-tag.featured{background:#fbbf24;color:#0f172a;}
.jbk-opp-tag.expired{background:#9ca3af;color:#fff;}
.jbk-opp-page h1{font-size:30px;line-height:1.25;margin:0 0 6px 0;color:#0f172a;}
.jbk-opp-page .opp-org{font-size:16px;color:#374151;margin:0 0 18px 0;}
.jbk-opp-page .opp-org a{color:inherit;font-weight:600;}
.jbk-opp-meta-row{display:flex;flex-wrap:wrap;gap:10px 26px;margin:14px 0 0 0;color:#374151;font-size:14px;}
.jbk-opp-meta-row strong{color:#0f172a;}
.jbk-opp-meta-row .opp-meta-item{display:flex;flex-direction:column;}
.jbk-opp-meta-row .label{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;font-weight:600;}
.jbk-opp-meta-row .value{font-size:15px;}
.jbk-countdown{color:#dc2626;font-weight:600;}
.jbk-countdown.safe{color:#059669;}

.jbk-apply-row{margin-top:22px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.jbk-apply-btn{display:inline-block;background:#fbbf24;color:#0f172a;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;}
.jbk-apply-btn:hover{background:#f59e0b;}
.jbk-apply-btn.disabled{background:#e5e7eb;color:#9ca3af;cursor:not-allowed;pointer-events:none;}

.jbk-opp-content{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:28px;line-height:1.7;}
.jbk-opp-content h2{font-size:22px;margin:28px 0 12px;color:#0f172a;border-bottom:1px solid #e5e7eb;padding-bottom:8px;}
.jbk-opp-content h2:first-child{margin-top:0;}
.jbk-opp-content p{margin:0 0 14px;}
.jbk-opp-content ul,.jbk-opp-content ol{margin:0 0 14px;padding-left:24px;}
.jbk-opp-content li{margin-bottom:8px;}
.jbk-opp-content .jbk-opp-lead{font-size:17px;color:#0f172a;font-weight:500;background:#fef9e7;border-left:4px solid #fbbf24;padding:14px 18px;margin-bottom:24px;}
.jbk-opp-content figure img,.jbk-opp-content img{max-width:100%;height:auto;border-radius:6px;}
.jbk-opp-content .image-credit{font-size:12px;color:#9ca3af;margin-bottom:18px;}

.jbk-opp-sidebar{position:sticky;top:80px;}
.jbk-sidebar-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px;margin-bottom:18px;}
.jbk-sidebar-card h3{font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;margin:0 0 12px;}
.jbk-related-list{list-style:none;padding:0;margin:0;}
.jbk-related-list li{padding:10px 0;border-bottom:1px solid #f3f4f6;}
.jbk-related-list li:last-child{border-bottom:0;}
.jbk-related-list a{color:#0f172a;text-decoration:none;font-weight:500;font-size:14px;line-height:1.4;}
.jbk-related-list a:hover{color:#0369a1;}
.jbk-related-list .meta{font-size:12px;color:#6b7280;margin-top:3px;}
</style>

<?php while ( have_posts() ) : the_post();
    $post_id      = get_the_ID();
    $expired      = get_post_meta( $post_id, '_expired', true ) === '1';
    $featured     = get_post_meta( $post_id, '_featured', true ) === '1';
    $deadline     = get_post_meta( $post_id, '_deadline', true );
    $deadline_txt = get_post_meta( $post_id, '_deadline_text', true );
    $value_usd    = get_post_meta( $post_id, '_value_usd', true );
    $value_ghs    = get_post_meta( $post_id, '_value_ghs', true );
    $value_text   = get_post_meta( $post_id, '_value_text', true );
    $location     = get_post_meta( $post_id, '_location', true );
    $organization = get_post_meta( $post_id, '_organization', true );
    $apply_url    = get_post_meta( $post_id, '_apply_url', true );
    $source_url   = get_post_meta( $post_id, '_source_url', true );

    $terms     = get_the_terms( $post_id, 'opportunity_type' );
    $type_term = ( ! is_wp_error( $terms ) && $terms ) ? $terms[0] : null;
    $type_label = $type_term ? $type_term->name : 'Opportunity';
    $type_slug  = $type_term ? $type_term->slug : '';
    $type_url   = $type_term ? home_url( '/opportunities/' . $type_slug . '/' ) : home_url( '/opportunities/' );

    // Value display
    $value_display = '';
    if ( $value_usd ) $value_display .= 'USD ' . number_format( (float) $value_usd );
    if ( $value_ghs ) {
        if ( $value_display ) $value_display .= ' (~';
        $value_display .= 'GHS ' . number_format( (float) $value_ghs );
        if ( $value_usd ) $value_display .= ')';
    }
    if ( ! $value_display && $value_text ) $value_display = esc_html( $value_text );

    // Tracked apply URL
    $apply_track_url = $apply_url ? add_query_arg( [
        'utm_source'   => 'jbklutse',
        'utm_medium'   => 'opportunity',
        'utm_campaign' => 'apply',
        'utm_content'  => $post_id,
    ], $apply_url ) : '';
?>

<div class="jbk-opp-page">

    <div class="breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo;
        <a href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">Opportunities</a> &rsaquo;
        <a href="<?php echo esc_url( $type_url ); ?>"><?php echo esc_html( $type_label ); ?></a>
    </div>

    <div class="jbk-opp-grid">
        <main>
            <div class="jbk-opp-hero">
                <div class="jbk-opp-tag-row">
                    <span class="jbk-opp-tag type"><?php echo esc_html( $type_label ); ?></span>
                    <?php if ( $featured ) : ?><span class="jbk-opp-tag featured">Featured</span><?php endif; ?>
                    <?php if ( $expired ) : ?><span class="jbk-opp-tag expired">Expired</span><?php endif; ?>
                </div>

                <h1><?php echo esc_html( get_the_title() ); ?></h1>

                <?php if ( $organization ) : ?>
                    <p class="opp-org">
                        <?php if ( $source_url ) : ?>
                            <a href="<?php echo esc_url( $source_url ); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html( $organization ); ?></a>
                        <?php else : echo esc_html( $organization ); endif; ?>
                    </p>
                <?php endif; ?>

                <div class="jbk-opp-meta-row">
                    <?php if ( $location ) : ?>
                        <div class="opp-meta-item">
                            <span class="label">Location</span>
                            <span class="value"><?php echo esc_html( $location ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $value_display ) : ?>
                        <div class="opp-meta-item">
                            <span class="label">Value</span>
                            <span class="value"><?php echo wp_kses_post( $value_display ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $deadline || $deadline_txt ) : ?>
                        <div class="opp-meta-item">
                            <span class="label">Deadline</span>
                            <span class="value">
                                <?php
                                if ( $deadline ) {
                                    $human = wp_date( 'F j, Y', strtotime( $deadline ) );
                                    echo esc_html( $human );
                                    if ( ! $expired ) {
                                        echo ' <span class="jbk-countdown" data-deadline="' . esc_attr( $deadline ) . '"></span>';
                                    }
                                } else {
                                    echo esc_html( $deadline_txt );
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ( $apply_track_url && ! $expired ) : ?>
                    <div class="jbk-apply-row">
                        <a class="jbk-apply-btn"
                           href="<?php echo esc_url( $apply_track_url ); ?>"
                           target="_blank"
                           rel="noopener nofollow sponsored"
                           onclick="if(navigator.sendBeacon){navigator.sendBeacon('/wp-json/jbklutse/v1/track-apply-click',new Blob([JSON.stringify({post_id:<?php echo (int) $post_id; ?>})],{type:'application/json'}));}">
                            Apply Now &rarr;
                        </a>
                        <small style="color:#6b7280;">Opens the official application page</small>
                    </div>
                <?php elseif ( $expired ) : ?>
                    <div class="jbk-apply-row">
                        <span class="jbk-apply-btn disabled">Closed</span>
                        <small style="color:#6b7280;">This opportunity has expired</small>
                    </div>
                <?php endif; ?>
            </div>

            <article class="jbk-opp-content">
                <?php the_content(); ?>
            </article>
        </main>

        <aside class="jbk-opp-sidebar">
            <?php
            // Related opportunities of same type (5 most recent, exclude current)
            $related = get_posts( [
                'post_type'      => 'opportunity',
                'posts_per_page' => 5,
                'post__not_in'   => [ $post_id ],
                'tax_query'      => $type_term ? [ [
                    'taxonomy' => 'opportunity_type',
                    'field'    => 'term_id',
                    'terms'    => [ $type_term->term_id ],
                ] ] : [],
                'meta_query'     => [
                    'relation' => 'OR',
                    [ 'key' => '_expired', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_expired', 'value' => '1', 'compare' => '!=' ],
                ],
            ] );
            ?>
            <?php if ( $related ) : ?>
                <div class="jbk-sidebar-card">
                    <h3>More <?php echo esc_html( strtolower( $type_label ) ); ?></h3>
                    <ul class="jbk-related-list">
                        <?php foreach ( $related as $rp ) :
                            $rd = get_post_meta( $rp->ID, '_deadline', true );
                            $ro = get_post_meta( $rp->ID, '_organization', true );
                        ?>
                            <li>
                                <a href="<?php echo esc_url( get_permalink( $rp ) ); ?>">
                                    <?php echo esc_html( $rp->post_title ); ?>
                                </a>
                                <div class="meta">
                                    <?php if ( $ro ) echo esc_html( $ro ); ?>
                                    <?php if ( $rd ) echo ' &middot; ' . esc_html( wp_date( 'M j', strtotime( $rd ) ) ); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="jbk-sidebar-card">
                <h3>Browse all</h3>
                <ul class="jbk-related-list">
                    <?php
                    $all_terms = get_terms( [ 'taxonomy' => 'opportunity_type', 'hide_empty' => false ] );
                    foreach ( $all_terms as $t ) :
                    ?>
                        <li>
                            <a href="<?php echo esc_url( home_url( '/opportunities/' . $t->slug . '/' ) ); ?>">
                                <?php echo esc_html( $t->name ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
(function(){
    document.querySelectorAll('.jbk-countdown').forEach(function(el){
        var deadline = el.getAttribute('data-deadline');
        if(!deadline) return;
        var update = function(){
            var ms = new Date(deadline + 'T23:59:59').getTime() - Date.now();
            if (ms <= 0) { el.textContent = ''; return; }
            var days = Math.floor(ms / 86400000);
            var hrs = Math.floor((ms % 86400000) / 3600000);
            if (days > 7) {
                el.textContent = '(' + days + ' days left)';
                el.classList.add('safe');
            } else if (days >= 1) {
                el.textContent = '(' + days + ' days, ' + hrs + ' hrs left)';
                el.classList.remove('safe');
            } else {
                el.textContent = '(' + hrs + ' hours left)';
                el.classList.remove('safe');
            }
        };
        update();
        setInterval(update, 60000);
    });
})();
</script>

<?php endwhile; ?>

<?php block_template_part( 'footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
