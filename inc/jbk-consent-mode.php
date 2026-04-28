<?php
/**
 * JBKlutse — Google Consent Mode v2 bridge
 *
 * Properly enqueued via wp_add_inline_script so CDN optimizers (QUIC.cloud
 * etc.) don't strip the script. Bridges Complianz Free which doesn't ship
 * Consent Mode v2.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


function jbk_consent_mode_v2_js() {
    return <<<'JS'
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{'ad_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','analytics_storage':'denied','functionality_storage':'denied','personalization_storage':'denied','security_storage':'granted','wait_for_update':500});
gtag('set','ads_data_redaction',true);
gtag('set','url_passthrough',true);
document.addEventListener('cmplz_status_change',function(e){var c=(e.detail&&e.detail.categories)?e.detail.categories:[];gtag('consent','update',{'ad_storage':c.indexOf('marketing')!==-1?'granted':'denied','ad_user_data':c.indexOf('marketing')!==-1?'granted':'denied','ad_personalization':c.indexOf('marketing')!==-1?'granted':'denied','analytics_storage':c.indexOf('statistics')!==-1?'granted':'denied','functionality_storage':c.indexOf('preferences')!==-1?'granted':'denied','personalization_storage':c.indexOf('preferences')!==-1?'granted':'denied'});});
document.addEventListener('cmplz_accept_marketing',function(){gtag('consent','update',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted'});});
document.addEventListener('cmplz_accept_statistics',function(){gtag('consent','update',{'analytics_storage':'granted'});});
document.addEventListener('cmplz_accept_preferences',function(){gtag('consent','update',{'functionality_storage':'granted','personalization_storage':'granted'});});
try{var s=localStorage.getItem('cmplz_consent_status');if(s&&(s.toLowerCase()==='allow'||s.toLowerCase().indexOf('marketing')!==-1)){gtag('consent','update',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted','analytics_storage':'granted'});}}catch(e){}
JS;
}


/**
 * Register a phantom script handle and attach our consent code as inline
 * "before" data. This makes WordPress emit it as part of the official
 * scripts pipeline, which CDN optimizers respect. The dummy src is the
 * same WP empty-string trick used by core.
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_register_script( 'jbk-consent-mode-v2', '', array(), '1.0', false ); // header
    wp_enqueue_script( 'jbk-consent-mode-v2' );
    wp_add_inline_script( 'jbk-consent-mode-v2', jbk_consent_mode_v2_js(), 'before' );
}, -100 ); // Very early so it lands at the top of <head>


/**
 * Backup path: also emit directly into wp_head with a data-no-optimize
 * attribute so CDN optimizers leave it alone if the enqueue path is
 * ever filtered out.
 */
add_action( 'wp_head', function () {
    echo "<script data-no-optimize=\"1\" data-cfasync=\"false\">\n";
    echo jbk_consent_mode_v2_js();
    echo "\n</script>\n";
}, 1 );
