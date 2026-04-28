<?php
/**
 * JBKlutse — Manual AdSense Placements
 *
 * Auto-ads alone leaves money on the table. This file injects three ad slots
 * at proven high-CTR positions in every single post:
 *
 *   1. Top — after the first paragraph (before the first H2)
 *   2. Mid — before the H2 closest to the middle of the article
 *   3. Bottom — after the article content
 *
 * AdSense AUTO-ADS picks up these <ins> blocks and fills them with appropriate
 * ad creatives even without explicit data-ad-slot IDs, BUT for guaranteed
 * rendering and best CTR you should create three "In-feed" or "In-article"
 * ad units in the AdSense dashboard and paste the slot IDs into the constants
 * below.
 *
 * To create ad units (10 min):
 *   1. Open https://www.google.com/adsense/
 *   2. Ads -> By ad unit -> Create new ad unit
 *   3. Pick "In-article ads" -> name it "JBK In-Article Top" -> Save
 *      Copy the ad slot ID from the snippet (the digits after data-ad-slot=)
 *   4. Repeat for "JBK In-Article Mid" and "JBK In-Article Bottom"
 *   5. Paste the three slot IDs into the constants below
 *
 * After updating, run on the server:
 *   ssh -p 6543 jbklutse@165.140.158.168 "cd ~/public_html && wp litespeed-purge all"
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'JBK_ADSENSE_CLIENT' ) ) {
    define( 'JBK_ADSENSE_CLIENT', 'ca-pub-6894678502498613' );
}


/**
 * Load the AdSense loader script. Previously came from Site Kit; now loaded
 * directly so AdSense works without the platform plugin (and without the
 * `host=ca-host-pub-...` "hosted content" attribution that Site Kit appends,
 * which suppresses Auto Ads fill rate).
 *
 * Uses wp_enqueue_scripts (not raw wp_head echo) so CDN optimizers respect it.
 */
add_action( 'wp_enqueue_scripts', function () {
    $client = JBK_ADSENSE_CLIENT;
    $src    = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' . $client;
    wp_enqueue_script( 'jbk-adsense-loader', $src, array(), null, false ); // header, async via filter
}, -50 );

add_filter( 'script_loader_tag', function ( $tag, $handle ) {
    if ( $handle === 'jbk-adsense-loader' ) {
        // AdSense requires async + crossorigin
        $tag = str_replace( '<script ', '<script async crossorigin="anonymous" ', $tag );
    }
    return $tag;
}, 10, 2 );

// Replace these with real AdSense ad-unit slot IDs once created.
// Leaving them empty falls back to auto-ads filling the slot.
if ( ! defined( 'JBK_AD_SLOT_TOP' ) )    define( 'JBK_AD_SLOT_TOP', '' );
if ( ! defined( 'JBK_AD_SLOT_MID' ) )    define( 'JBK_AD_SLOT_MID', '' );
if ( ! defined( 'JBK_AD_SLOT_BOTTOM' ) ) define( 'JBK_AD_SLOT_BOTTOM', '' );


/**
 * Build an AdSense <ins> block. If a slot ID is supplied, use a declared
 * in-article unit. Otherwise output an auto-format placeholder that auto-ads
 * may fill.
 */
function jbk_ad_block( string $position, string $slot = '' ): string {
    $client = esc_attr( JBK_ADSENSE_CLIENT );
    $pos    = esc_attr( $position );

    if ( $slot !== '' ) {
        // Declared in-article unit (best CTR + guaranteed fill)
        return <<<HTML
<div class="jbk-ad jbk-ad-{$pos}" style="margin:24px auto;text-align:center;clear:both;max-width:100%;">
  <small style="display:block;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Advertisement</small>
  <ins class="adsbygoogle"
       style="display:block;text-align:center;"
       data-ad-layout="in-article"
       data-ad-format="fluid"
       data-ad-client="{$client}"
       data-ad-slot="{$slot}"></ins>
  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>
HTML;
    }

    // Auto-format placeholder (auto-ads decides whether to fill)
    return <<<HTML
<div class="jbk-ad jbk-ad-{$pos}" style="margin:24px auto;text-align:center;clear:both;max-width:100%;">
  <small style="display:block;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Advertisement</small>
  <ins class="adsbygoogle"
       style="display:block;"
       data-ad-format="auto"
       data-full-width-responsive="true"
       data-ad-client="{$client}"></ins>
  <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
</div>
HTML;
}


/**
 * Inject ads into post content. Hooks at low priority so other content
 * filters (Rank Math etc.) finish first.
 */
add_filter( 'the_content', function ( $content ) {
    // Only on single posts (not pages, archives, search, feeds, AMP)
    if ( ! is_singular( 'post' ) ) return $content;
    if ( ! is_main_query() && ! in_the_loop() ) return $content;
    if ( is_admin() || is_feed() ) return $content;

    // Skip very short posts (< 400 words) — ads on tiny articles look spammy
    $word_count = str_word_count( wp_strip_all_tags( $content ) );
    if ( $word_count < 400 ) return $content;

    $top    = jbk_ad_block( 'top',    JBK_AD_SLOT_TOP );
    $mid    = jbk_ad_block( 'mid',    JBK_AD_SLOT_MID );
    $bottom = jbk_ad_block( 'bottom', JBK_AD_SLOT_BOTTOM );

    // 1. Insert TOP ad after the first closing </p> (before any H2)
    $content = preg_replace( '#(</p>)#', '$1' . $top, $content, 1 );

    // 2. Insert MID ad before the H2 closest to the middle of the article.
    //    Find all <h2> positions, pick the one nearest to content midpoint.
    if ( preg_match_all( '#<h2\b[^>]*>#i', $content, $h2_matches, PREG_OFFSET_CAPTURE ) ) {
        $h2_offsets = array_column( $h2_matches[0], 1 );
        if ( count( $h2_offsets ) >= 2 ) {
            $midpoint = strlen( $content ) / 2;
            $best_offset = null;
            $best_diff   = PHP_INT_MAX;
            // Skip the first H2 (likely too close to top ad). Pick any of the rest.
            foreach ( array_slice( $h2_offsets, 1 ) as $offset ) {
                $diff = abs( $offset - $midpoint );
                if ( $diff < $best_diff ) {
                    $best_diff   = $diff;
                    $best_offset = $offset;
                }
            }
            if ( $best_offset !== null ) {
                $content = substr( $content, 0, $best_offset ) . $mid . substr( $content, $best_offset );
            }
        }
    }

    // 3. Append BOTTOM ad after content
    $content .= $bottom;

    return $content;
}, 99 );
