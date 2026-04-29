<?php
/**
 * Opportunity archive — /opportunities/
 *
 * Card grid layout listing all non-expired opportunities. Type tabs at top,
 * featured listings sort first, deadline countdown on each card.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// FSE block theme — render header template part directly.
$current_term = is_tax( 'opportunity_type' ) ? get_queried_object() : null;
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
.jbk-opps-page{
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
.jbk-opps-page{max-width:1200px;margin:30px auto;padding:0 20px;color:var(--jbk-text);}
.jbk-opps-page .breadcrumb{font-size:13px;color:var(--jbk-muted);margin-bottom:12px;}
.jbk-opps-page .breadcrumb a{color:var(--jbk-secondary);text-decoration:none;}
.jbk-opps-page .breadcrumb a:hover{color:var(--jbk-accent);}
.jbk-opps-page .archive-header{margin-bottom:20px;}
.jbk-opps-page .archive-header h1{font-family:var(--jbk-heading-font);font-size:32px;line-height:1.2;margin:0 0 6px;color:var(--jbk-primary);}
.jbk-opps-page .archive-header p{color:var(--jbk-muted);margin:0 0 18px;font-size:16px;}

.jbk-type-tabs{display:flex;flex-wrap:wrap;gap:6px;border-bottom:2px solid var(--jbk-border);margin-bottom:24px;padding-bottom:0;}
.jbk-type-tab{padding:9px 16px;border-radius:6px 6px 0 0;text-decoration:none;color:var(--jbk-text);font-size:14px;font-weight:500;background:transparent;border:1px solid transparent;border-bottom:none;margin-bottom:-2px;font-family:var(--jbk-heading-font);}
.jbk-type-tab:hover{background:var(--jbk-bg);color:var(--jbk-primary);}
.jbk-type-tab.active{background:var(--jbk-secondary);color:#fff;font-weight:600;}
.jbk-type-tab.active:hover{background:var(--jbk-accent);color:#fff;}

.jbk-opp-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;margin-bottom:30px;}

.jbk-card{background:var(--jbk-surface);border:1px solid var(--jbk-border);border-radius:10px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;}
.jbk-card:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(0,128,128,.10);}
.jbk-card.featured{border-color:var(--jbk-secondary);box-shadow:0 0 0 2px var(--jbk-secondary) inset;}
.jbk-card-thumb{aspect-ratio:16/9;background:var(--jbk-bg);background-size:cover;background-position:center;position:relative;display:flex;align-items:center;justify-content:center;}
/* Per-type fallback gradients + emoji-glyph used when a card has no
   featured-image. Each type gets a recognisable colour pair so the page
   doesn't render as a row of identical grey rectangles. */
.jbk-card-thumb.no-thumb{color:#fff;font-family:var(--jbk-heading-font);font-size:48px;letter-spacing:.04em;text-shadow:0 2px 8px rgba(0,0,0,.18);}
.jbk-card-thumb.no-thumb .jbk-thumb-glyph{font-size:54px;line-height:1;}
.jbk-card-thumb.no-thumb .jbk-thumb-label{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:6px;opacity:.92;}
.jbk-card-thumb.no-thumb .jbk-thumb-stack{display:flex;flex-direction:column;align-items:center;text-align:center;}
.jbk-card-thumb.type-jobs        {background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 100%);}
.jbk-card-thumb.type-scholarships{background:linear-gradient(135deg,#065f46 0%,#10b981 100%);}
.jbk-card-thumb.type-fellowships {background:linear-gradient(135deg,#7c2d12 0%,#ea580c 100%);}
.jbk-card-thumb.type-grants      {background:linear-gradient(135deg,#581c87 0%,#a855f7 100%);}
.jbk-card-thumb.type-deals       {background:linear-gradient(135deg,#9a3412 0%,#fb923c 100%);}
.jbk-card-thumb.type-events      {background:linear-gradient(135deg,#0e7490 0%,#06b6d4 100%);}
.jbk-card-thumb.type-webinars    {background:linear-gradient(135deg,#1e40af 0%,#60a5fa 100%);}
.jbk-card-thumb.type-bootcamps   {background:linear-gradient(135deg,#9f1239 0%,#fb7185 100%);}
.jbk-card-thumb.type-creators    {background:linear-gradient(135deg,#4338ca 0%,#a78bfa 100%);}
.jbk-card-thumb.type-telco-promos{background:linear-gradient(135deg,#15803d 0%,#84cc16 100%);}
.jbk-card-thumb.type-default     {background:linear-gradient(135deg,#0f172a 0%,#475569 100%);}
.jbk-card-badge{position:absolute;top:10px;left:10px;background:var(--jbk-primary);color:var(--jbk-accent);padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;font-family:var(--jbk-heading-font);}
.jbk-card-badge.featured{background:var(--jbk-secondary);color:#fff;}
.jbk-card-body{padding:16px;flex:1;display:flex;flex-direction:column;}
.jbk-card-title{font-family:var(--jbk-heading-font);font-size:16px;line-height:1.35;font-weight:600;margin:0 0 6px;color:var(--jbk-primary);}
.jbk-card-title a{color:inherit;text-decoration:none;}
.jbk-card-title a:hover{color:var(--jbk-secondary);}
.jbk-card-org{font-size:13px;color:var(--jbk-muted);margin:0 0 10px;}
.jbk-card-meta{margin-top:auto;padding-top:12px;border-top:1px solid var(--jbk-border);display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--jbk-text);}
.jbk-card-meta .deadline{font-weight:600;}
.jbk-card-meta .deadline.urgent{color:#c0392b;}
.jbk-card-meta .deadline.expired{color:var(--jbk-muted);text-decoration:line-through;}
.jbk-card-meta .value{color:var(--jbk-secondary);font-weight:600;}

.jbk-pagination{margin:30px 0;text-align:center;}
.jbk-pagination .page-numbers{display:inline-block;padding:8px 14px;margin:0 3px;border:1px solid var(--jbk-border);border-radius:6px;text-decoration:none;color:var(--jbk-text);font-family:var(--jbk-heading-font);}
.jbk-pagination .page-numbers.current{background:var(--jbk-secondary);color:#fff;border-color:var(--jbk-secondary);}

.jbk-no-results{background:rgba(0,128,128,0.08);border:1px solid var(--jbk-secondary);border-radius:8px;padding:24px;text-align:center;color:var(--jbk-primary);}
.jbk-no-results a{color:var(--jbk-secondary);}

@media(max-width:600px){
    .jbk-opps-page .archive-header h1{font-size:24px;}
    .jbk-type-tabs{overflow-x:auto;flex-wrap:nowrap;}
    .jbk-type-tab{flex-shrink:0;}
}
</style>

<div class="jbk-opps-page">
    <div class="breadcrumb">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &rsaquo;
        <a href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">Opportunities</a>
        <?php if ( $current_term ) : ?>
            &rsaquo; <?php echo esc_html( $current_term->name ); ?>
        <?php endif; ?>
    </div>

    <header class="archive-header">
        <?php if ( $current_term ) : ?>
            <h1><?php echo esc_html( $current_term->name ); ?> in Ghana &amp; Africa</h1>
            <p>Curated <?php echo esc_html( strtolower( $current_term->name ) ); ?> for Ghanaian and West African tech audiences. Deadlines verified, expired items hidden by default.</p>
        <?php else : ?>
            <h1>Tech Opportunities for Ghana &amp; Africa</h1>
            <p>Jobs, scholarships, fellowships, grants, deals, events. Curated for Ghanaian + West African tech audiences. Apply Now buttons link directly to the official source.</p>
        <?php endif; ?>
    </header>

    <nav class="jbk-type-tabs">
        <a class="jbk-type-tab <?php echo $current_term ? '' : 'active'; ?>" href="<?php echo esc_url( home_url( '/opportunities/' ) ); ?>">All</a>
        <?php
        $type_order = [ 'jobs', 'scholarships', 'fellowships', 'grants', 'deals', 'events', 'webinars', 'bootcamps', 'creators', 'telco-promos' ];
        foreach ( $type_order as $slug ) {
            $t = get_term_by( 'slug', $slug, 'opportunity_type' );
            if ( ! $t || is_wp_error( $t ) ) continue;
            $is_active = $current_term && $current_term->slug === $slug;
            ?>
            <a class="jbk-type-tab <?php echo $is_active ? 'active' : ''; ?>" href="<?php echo esc_url( home_url( '/opportunities/' . $slug . '/' ) ); ?>">
                <?php echo esc_html( $t->name ); ?>
            </a>
            <?php
        }
        ?>
    </nav>

    <?php if ( have_posts() ) : ?>
        <div class="jbk-opp-cards">
            <?php while ( have_posts() ) : the_post();
                $pid          = get_the_ID();
                $deadline     = get_post_meta( $pid, '_deadline', true );
                $deadline_txt = get_post_meta( $pid, '_deadline_text', true );
                $expired      = get_post_meta( $pid, '_expired', true ) === '1';
                $featured     = get_post_meta( $pid, '_featured', true ) === '1';
                $organization = get_post_meta( $pid, '_organization', true );
                $value_usd    = get_post_meta( $pid, '_value_usd', true );
                $value_ghs    = get_post_meta( $pid, '_value_ghs', true );
                $value_text   = get_post_meta( $pid, '_value_text', true );

                $thumb_url    = get_the_post_thumbnail_url( $pid, 'medium_large' );
                $terms        = get_the_terms( $pid, 'opportunity_type' );
                $type_label   = ( ! is_wp_error( $terms ) && $terms ) ? $terms[0]->name : 'Opportunity';
                $type_slug    = ( ! is_wp_error( $terms ) && $terms ) ? $terms[0]->slug : 'default';

                // Per-type emoji glyph used as the no-thumb fallback. Mirrors
                // the type slugs registered in jbk-opportunities-cpt.php.
                $type_glyphs = [
                    'jobs'         => '\u{1F4BC}', // briefcase
                    'scholarships' => '\u{1F393}', // graduation cap
                    'fellowships'  => '\u{1F31F}', // glowing star
                    'grants'       => '\u{1F4B0}', // money bag
                    'deals'        => '\u{1F3F7}', // tag
                    'events'       => '\u{1F4C5}', // calendar
                    'webinars'     => '\u{1F4FA}', // television
                    'bootcamps'    => '\u{1F680}', // rocket
                    'creators'     => '\u{1F3A5}', // movie camera
                    'telco-promos' => '\u{1F4F1}', // phone
                ];
                // PHP doesn't support \u{} escape inside single-quoted strings prior to 7.0
                // and we want this to work everywhere — switch to the literal symbols.
                $type_glyphs = [
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
                $glyph = isset( $type_glyphs[ $type_slug ] ) ? $type_glyphs[ $type_slug ] : '✨';

                // Compute deadline state
                $deadline_class = '';
                $deadline_display = '';
                if ( $expired ) {
                    $deadline_class = 'expired';
                    $deadline_display = 'Expired';
                } elseif ( $deadline ) {
                    $days_left = max( 0, floor( ( strtotime( $deadline ) - time() ) / 86400 ) );
                    if ( $days_left <= 7 ) $deadline_class = 'urgent';
                    if ( $days_left == 0 ) {
                        $deadline_display = 'Closes today';
                    } else {
                        $deadline_display = $days_left . 'd left';
                    }
                } elseif ( $deadline_txt ) {
                    $deadline_display = esc_html( $deadline_txt );
                } else {
                    $deadline_display = 'Rolling';
                }

                // Compact value
                $value_short = '';
                if ( $value_usd ) $value_short = '$' . ( $value_usd >= 1000 ? round( $value_usd / 1000 ) . 'k' : $value_usd );
                elseif ( $value_ghs ) $value_short = 'GHS ' . ( $value_ghs >= 1000 ? round( $value_ghs / 1000 ) . 'k' : $value_ghs );
                elseif ( $value_text ) $value_short = mb_strimwidth( $value_text, 0, 22, '…' );
            ?>
                <article class="jbk-card <?php echo $featured ? 'featured' : ''; ?>">
                    <?php if ( $thumb_url ) : ?>
                        <div class="jbk-card-thumb" style="background-image:url(<?php echo esc_url( $thumb_url ); ?>)">
                            <?php if ( $featured ) : ?>
                                <span class="jbk-card-badge featured">Featured</span>
                            <?php else : ?>
                                <span class="jbk-card-badge"><?php echo esc_html( $type_label ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <div class="jbk-card-thumb no-thumb type-<?php echo esc_attr( $type_slug ); ?>">
                            <?php if ( $featured ) : ?>
                                <span class="jbk-card-badge featured">Featured</span>
                            <?php else : ?>
                                <span class="jbk-card-badge"><?php echo esc_html( $type_label ); ?></span>
                            <?php endif; ?>
                            <div class="jbk-thumb-stack">
                                <span class="jbk-thumb-glyph"><?php echo $glyph; ?></span>
                                <span class="jbk-thumb-label"><?php echo esc_html( $type_label ); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="jbk-card-body">
                        <h2 class="jbk-card-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h2>
                        <?php if ( $organization ) : ?>
                            <p class="jbk-card-org"><?php echo esc_html( $organization ); ?></p>
                        <?php endif; ?>
                        <div class="jbk-card-meta">
                            <span class="deadline <?php echo esc_attr( $deadline_class ); ?>"><?php echo esc_html( $deadline_display ); ?></span>
                            <?php if ( $value_short ) : ?>
                                <span class="value"><?php echo esc_html( $value_short ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <div class="jbk-pagination">
            <?php
            echo paginate_links( [
                'prev_text' => '&laquo; Prev',
                'next_text' => 'Next &raquo;',
            ] );
            ?>
        </div>
    <?php else : ?>
        <div class="jbk-no-results">
            <p><strong>No <?php echo $current_term ? esc_html( strtolower( $current_term->name ) ) : 'opportunities'; ?> live right now.</strong></p>
            <p>Check back tomorrow — our scanner pulls fresh listings every morning at 4am Accra time.</p>
        </div>
    <?php endif; ?>
</div>

<?php block_template_part( 'footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
