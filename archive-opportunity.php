<?php
/**
 * Opportunity archive — /opportunities/
 *
 * Card grid layout listing all non-expired opportunities. Type tabs at top,
 * featured listings sort first, deadline countdown on each card.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// Determine current taxonomy term if any (used by taxonomy-opportunity_type.php
// which includes this same template via locate_template fallback).
$current_term = is_tax( 'opportunity_type' ) ? get_queried_object() : null;
?>

<style>
.jbk-opps-page{max-width:1200px;margin:30px auto;padding:0 20px;font-family:system-ui,-apple-system,sans-serif;color:#1f2937;}
.jbk-opps-page .breadcrumb{font-size:13px;color:#6b7280;margin-bottom:12px;}
.jbk-opps-page .breadcrumb a{color:#0369a1;text-decoration:none;}
.jbk-opps-page .archive-header{margin-bottom:20px;}
.jbk-opps-page .archive-header h1{font-size:32px;line-height:1.2;margin:0 0 6px;color:#0f172a;}
.jbk-opps-page .archive-header p{color:#6b7280;margin:0 0 18px;font-size:16px;}

.jbk-type-tabs{display:flex;flex-wrap:wrap;gap:6px;border-bottom:2px solid #e5e7eb;margin-bottom:24px;padding-bottom:0;}
.jbk-type-tab{padding:9px 16px;border-radius:6px 6px 0 0;text-decoration:none;color:#374151;font-size:14px;font-weight:500;background:transparent;border:1px solid transparent;border-bottom:none;margin-bottom:-2px;}
.jbk-type-tab:hover{background:#f3f4f6;color:#0f172a;}
.jbk-type-tab.active{background:#0f172a;color:#fbbf24;font-weight:600;}
.jbk-type-tab.active:hover{background:#0f172a;color:#fbbf24;}

.jbk-opp-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;margin-bottom:30px;}

.jbk-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s;}
.jbk-card:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(0,0,0,.08);}
.jbk-card.featured{border-color:#fbbf24;box-shadow:0 0 0 2px #fbbf24 inset;}
.jbk-card-thumb{aspect-ratio:16/9;background:#f3f4f6;background-size:cover;background-position:center;position:relative;}
.jbk-card-badge{position:absolute;top:10px;left:10px;background:#0f172a;color:#fbbf24;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;}
.jbk-card-badge.featured{background:#fbbf24;color:#0f172a;}
.jbk-card-body{padding:16px;flex:1;display:flex;flex-direction:column;}
.jbk-card-title{font-size:16px;line-height:1.35;font-weight:600;margin:0 0 6px;color:#0f172a;}
.jbk-card-title a{color:inherit;text-decoration:none;}
.jbk-card-title a:hover{color:#0369a1;}
.jbk-card-org{font-size:13px;color:#6b7280;margin:0 0 10px;}
.jbk-card-meta{margin-top:auto;padding-top:12px;border-top:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#374151;}
.jbk-card-meta .deadline{font-weight:600;}
.jbk-card-meta .deadline.urgent{color:#dc2626;}
.jbk-card-meta .deadline.expired{color:#9ca3af;text-decoration:line-through;}
.jbk-card-meta .value{color:#059669;font-weight:600;}

.jbk-pagination{margin:30px 0;text-align:center;}
.jbk-pagination .page-numbers{display:inline-block;padding:8px 14px;margin:0 3px;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:#374151;}
.jbk-pagination .page-numbers.current{background:#0f172a;color:#fbbf24;border-color:#0f172a;}

.jbk-no-results{background:#fef9e7;border:1px solid #fbbf24;border-radius:8px;padding:24px;text-align:center;color:#78350f;}

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
                    <div class="jbk-card-thumb" <?php if ( $thumb_url ) echo 'style="background-image:url(' . esc_url( $thumb_url ) . ')"'; ?>>
                        <?php if ( $featured ) : ?>
                            <span class="jbk-card-badge featured">Featured</span>
                        <?php else : ?>
                            <span class="jbk-card-badge"><?php echo esc_html( $type_label ); ?></span>
                        <?php endif; ?>
                    </div>
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

<?php get_footer(); ?>
