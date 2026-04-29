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
/* JBKlutse brand variables (fall back if WP preset vars aren't loaded) */
.jbk-opp-page{
    --jbk-primary:   var(--wp--preset--color--primary,    #141818);
    --jbk-secondary: var(--wp--preset--color--secondary,  #008080);
    --jbk-accent:    var(--wp--preset--color--accent,     #00b3b3);
    --jbk-bg:        var(--wp--preset--color--background, #f8f9fa);
    --jbk-surface:   var(--wp--preset--color--surface,    #ffffff);
    --jbk-text:      var(--wp--preset--color--text,       #333333);
    --jbk-muted:     var(--wp--preset--color--muted,      #6c757d);
    --jbk-border:    var(--wp--preset--color--border,     #e0e0e0);
    --jbk-heading-font: var(--wp--preset--font-family--heading, 'Mont','Montserrat',system-ui,sans-serif);
}

.jbk-opp-page{max-width:1100px;margin:30px auto;padding:0 20px;color:var(--jbk-text);}
.jbk-opp-page .breadcrumb{font-size:13px;color:var(--jbk-muted);margin-bottom:14px;}
.jbk-opp-page .breadcrumb a{color:var(--jbk-secondary);text-decoration:none;}
.jbk-opp-page .breadcrumb a:hover{color:var(--jbk-accent);}
.jbk-opp-grid{display:grid;grid-template-columns:1fr 320px;gap:36px;align-items:start;}
@media(max-width:860px){.jbk-opp-grid{grid-template-columns:1fr;}}

.jbk-opp-hero{background:var(--jbk-surface);border:1px solid var(--jbk-border);border-radius:10px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.04);margin-bottom:22px;}
.jbk-opp-hero-image{margin:-24px -24px 22px -24px;border-radius:10px 10px 0 0;overflow:hidden;aspect-ratio:21/9;background:var(--jbk-bg) center/cover;display:flex;align-items:center;justify-content:center;}
.jbk-opp-hero-image img{width:100%;height:100%;object-fit:cover;display:block;}
.jbk-opp-hero-image.no-thumb{color:#fff;font-family:var(--jbk-heading-font);text-shadow:0 2px 8px rgba(0,0,0,.18);}
.jbk-opp-hero-image.no-thumb .jbk-hero-glyph{font-size:88px;line-height:1;}
.jbk-opp-hero-image.no-thumb .jbk-hero-label{font-size:16px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin-top:8px;opacity:.92;}
.jbk-opp-hero-image.no-thumb .jbk-hero-stack{display:flex;flex-direction:column;align-items:center;text-align:center;}
.jbk-opp-hero-image.type-jobs        {background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 100%);}
.jbk-opp-hero-image.type-scholarships{background:linear-gradient(135deg,#065f46 0%,#10b981 100%);}
.jbk-opp-hero-image.type-fellowships {background:linear-gradient(135deg,#7c2d12 0%,#ea580c 100%);}
.jbk-opp-hero-image.type-grants      {background:linear-gradient(135deg,#581c87 0%,#a855f7 100%);}
.jbk-opp-hero-image.type-deals       {background:linear-gradient(135deg,#9a3412 0%,#fb923c 100%);}
.jbk-opp-hero-image.type-events      {background:linear-gradient(135deg,#0e7490 0%,#06b6d4 100%);}
.jbk-opp-hero-image.type-webinars    {background:linear-gradient(135deg,#1e40af 0%,#60a5fa 100%);}
.jbk-opp-hero-image.type-bootcamps   {background:linear-gradient(135deg,#9f1239 0%,#fb7185 100%);}
.jbk-opp-hero-image.type-creators    {background:linear-gradient(135deg,#4338ca 0%,#a78bfa 100%);}
.jbk-opp-hero-image.type-telco-promos{background:linear-gradient(135deg,#15803d 0%,#84cc16 100%);}
.jbk-opp-hero-image.type-default     {background:linear-gradient(135deg,#0f172a 0%,#475569 100%);}
.jbk-opp-tag-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.jbk-opp-tag{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;font-family:var(--jbk-heading-font);}
.jbk-opp-tag.type{background:var(--jbk-primary);color:var(--jbk-accent);}
.jbk-opp-tag.featured{background:var(--jbk-accent);color:var(--jbk-primary);}
.jbk-opp-tag.expired{background:var(--jbk-muted);color:#fff;}
.jbk-opp-page h1{font-family:var(--jbk-heading-font);font-size:30px;line-height:1.25;margin:0 0 6px 0;color:var(--jbk-primary);}
.jbk-opp-page .opp-org{font-size:16px;color:var(--jbk-text);margin:0 0 18px 0;}
.jbk-opp-page .opp-org a{color:var(--jbk-secondary);font-weight:600;text-decoration:none;}
.jbk-opp-page .opp-org a:hover{color:var(--jbk-accent);}
.jbk-opp-meta-row{display:flex;flex-wrap:wrap;gap:10px 26px;margin:14px 0 0 0;color:var(--jbk-text);font-size:14px;}
.jbk-opp-meta-row .opp-meta-item{display:flex;flex-direction:column;}
.jbk-opp-meta-row .label{font-size:11px;color:var(--jbk-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600;font-family:var(--jbk-heading-font);}
.jbk-opp-meta-row .value{font-size:15px;color:var(--jbk-primary);font-weight:500;}
.jbk-countdown{color:#c0392b;font-weight:600;}
.jbk-countdown.safe{color:var(--jbk-secondary);}

.jbk-apply-row{margin-top:22px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.jbk-apply-btn{display:inline-block;background:var(--jbk-secondary);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;font-family:var(--jbk-heading-font);transition:background .15s;}
.jbk-apply-btn:hover{background:var(--jbk-accent);color:#fff;}
.jbk-apply-btn.disabled{background:var(--jbk-border);color:var(--jbk-muted);cursor:not-allowed;pointer-events:none;}

.jbk-opp-content{background:var(--jbk-surface);border:1px solid var(--jbk-border);border-radius:10px;padding:28px;line-height:1.7;}
.jbk-opp-content h2{font-family:var(--jbk-heading-font);font-size:22px;margin:28px 0 12px;color:var(--jbk-primary);border-bottom:1px solid var(--jbk-border);padding-bottom:8px;}
.jbk-opp-content h2:first-child{margin-top:0;}
.jbk-opp-content p{margin:0 0 14px;}
.jbk-opp-content ul,.jbk-opp-content ol{margin:0 0 14px;padding-left:24px;}
.jbk-opp-content li{margin-bottom:8px;}
.jbk-opp-content a{color:var(--jbk-secondary);}
.jbk-opp-content a:hover{color:var(--jbk-accent);}
.jbk-opp-content .jbk-opp-lead{font-size:17px;color:var(--jbk-primary);font-weight:500;background:rgba(0,128,128,0.06);border-left:4px solid var(--jbk-secondary);padding:14px 18px;margin-bottom:24px;}
.jbk-opp-content figure img,.jbk-opp-content img{max-width:100%;height:auto;border-radius:6px;}
.jbk-opp-content .image-credit{font-size:12px;color:var(--jbk-muted);margin-bottom:18px;}

.jbk-opp-cta-bottom{margin-top:22px;background:var(--jbk-primary);color:#fff;border-radius:10px;padding:28px;text-align:center;}
.jbk-opp-cta-bottom h3{font-family:var(--jbk-heading-font);margin:0 0 8px;color:var(--jbk-accent);font-size:22px;line-height:1.3;}
.jbk-opp-cta-bottom p{margin:0 0 18px;color:rgba(255,255,255,0.85);font-size:15px;}
.jbk-opp-cta-bottom .jbk-apply-btn{font-size:17px;padding:16px 36px;background:var(--jbk-secondary);}
.jbk-opp-cta-bottom .jbk-apply-btn:hover{background:var(--jbk-accent);}
.jbk-opp-cta-bottom .deadline-reminder{font-size:13px;color:rgba(255,255,255,0.7);margin-top:14px;}
.jbk-opp-cta-bottom .deadline-reminder strong{color:var(--jbk-accent);}
.jbk-opp-cta-bottom.expired{background:var(--jbk-bg);color:var(--jbk-text);}
.jbk-opp-cta-bottom.expired h3{color:var(--jbk-text);}
.jbk-opp-cta-bottom.expired p{color:var(--jbk-muted);}
.jbk-opp-cta-bottom.expired a{color:var(--jbk-secondary);}

.jbk-opp-sidebar{position:sticky;top:80px;}
.jbk-sidebar-card{background:var(--jbk-surface);border:1px solid var(--jbk-border);border-radius:10px;padding:18px;margin-bottom:18px;}
.jbk-sidebar-card h3{font-family:var(--jbk-heading-font);font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:var(--jbk-muted);margin:0 0 12px;}
.jbk-related-list{list-style:none;padding:0;margin:0;}
.jbk-related-list li{padding:10px 0;border-bottom:1px solid var(--jbk-border);}
.jbk-related-list li:last-child{border-bottom:0;}
.jbk-related-list a{color:var(--jbk-primary);text-decoration:none;font-weight:500;font-size:14px;line-height:1.4;}
.jbk-related-list a:hover{color:var(--jbk-secondary);}
.jbk-related-list .meta{font-size:12px;color:var(--jbk-muted);margin-top:3px;}
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
                <?php
                // Hero image. Falls back to a per-type colour gradient + glyph
                // if the post has no featured-media — keeps the visual rhythm
                // intact instead of jumping straight from breadcrumb to title.
                $hero_url = get_the_post_thumbnail_url( $post_id, 'large' );
                $hero_glyphs = [
                    'jobs'         => '💼',
                    'scholarships' => '🎓',
                    'fellowships'  => '🌟',
                    'grants'       => '💰',
                    'deals'        => '🏷️',
                    'events'       => '📅',
                    'webinars'     => '📺',
                    'bootcamps'    => '🚀',
                    'creators'     => '🎥',
                    'telco-promos' => '📱',
                ];
                $hero_glyph = isset( $hero_glyphs[ $type_slug ] ) ? $hero_glyphs[ $type_slug ] : '✨';
                ?>
                <?php if ( $hero_url ) : ?>
                    <div class="jbk-opp-hero-image">
                        <img src="<?php echo esc_url( $hero_url ); ?>"
                             alt="<?php echo esc_attr( get_the_title() ); ?>"
                             loading="eager" decoding="async" />
                    </div>
                <?php else : ?>
                    <div class="jbk-opp-hero-image no-thumb type-<?php echo esc_attr( $type_slug ?: 'default' ); ?>">
                        <div class="jbk-hero-stack">
                            <span class="jbk-hero-glyph"><?php echo $hero_glyph; ?></span>
                            <span class="jbk-hero-label"><?php echo esc_html( $type_label ); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

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

                <?php
                // Build meta-row items, skipping any with empty or whitespace-only values.
                $meta_items = [];
                if ( trim( (string) $location ) !== '' ) {
                    $meta_items[] = [ 'label' => 'Location', 'html' => esc_html( $location ) ];
                }
                if ( trim( (string) $value_display ) !== '' ) {
                    $meta_items[] = [ 'label' => 'Value', 'html' => wp_kses_post( $value_display ) ];
                }
                if ( trim( (string) $deadline ) !== '' ) {
                    $human = wp_date( 'F j, Y', strtotime( $deadline ) );
                    $extra = ( ! $expired ) ? ' <span class="jbk-countdown" data-deadline="' . esc_attr( $deadline ) . '"></span>' : '';
                    $meta_items[] = [ 'label' => 'Deadline', 'html' => esc_html( $human ) . $extra ];
                } elseif ( trim( (string) $deadline_txt ) !== '' ) {
                    $meta_items[] = [ 'label' => 'Deadline', 'html' => esc_html( $deadline_txt ) ];
                }
                ?>
                <?php if ( $meta_items ) : ?>
                    <div class="jbk-opp-meta-row">
                        <?php foreach ( $meta_items as $mi ) : ?>
                            <div class="opp-meta-item">
                                <span class="label"><?php echo esc_html( $mi['label'] ); ?></span>
                                <span class="value"><?php echo $mi['html']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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

            <?php if ( $apply_track_url && ! $expired ) : ?>
                <section class="jbk-opp-cta-bottom">
                    <h3>Ready to apply?</h3>
                    <p>You've read the details. Now is when most applications get abandoned. Don't be that person.</p>
                    <a class="jbk-apply-btn"
                       href="<?php echo esc_url( $apply_track_url ); ?>"
                       target="_blank"
                       rel="noopener nofollow sponsored"
                       onclick="if(navigator.sendBeacon){navigator.sendBeacon('/wp-json/jbklutse/v1/track-apply-click',new Blob([JSON.stringify({post_id:<?php echo (int) $post_id; ?>,position:'bottom'})],{type:'application/json'}));}">
                        Apply on <?php echo esc_html( $organization ?: 'official site' ); ?> &rarr;
                    </a>
                    <?php if ( $deadline ) : ?>
                        <p class="deadline-reminder">
                            Closes <strong><?php echo esc_html( wp_date( 'F j, Y', strtotime( $deadline ) ) ); ?></strong>
                            <span class="jbk-countdown" data-deadline="<?php echo esc_attr( $deadline ); ?>"></span>
                        </p>
                    <?php elseif ( $deadline_txt ) : ?>
                        <p class="deadline-reminder">Deadline: <strong><?php echo esc_html( $deadline_txt ); ?></strong></p>
                    <?php endif; ?>
                </section>
            <?php elseif ( $expired ) : ?>
                <section class="jbk-opp-cta-bottom expired">
                    <h3>This opportunity has closed</h3>
                    <p>Browse current <a href="<?php echo esc_url( $type_url ); ?>"><?php echo esc_html( strtolower( $type_label ) ); ?></a> or all <a href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">opportunities</a> for similar listings still accepting applications.</p>
                </section>
            <?php endif; ?>
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
