<?php
/**
 * JBKlutse — Google Consent Mode v2 bridge
 *
 * Complianz Free does not emit Google Consent Mode v2 signals. AdSense
 * (and Google Analytics) needs gtag('consent', 'default'/'update', {...})
 * to switch between personalized and non-personalized ads. Without it,
 * AdSense serves only non-personalized ads to EU visitors → 30-50% lower
 * CPM.
 *
 * This file:
 *   1. Emits the default consent state as denied (must run BEFORE AdSense)
 *   2. Listens for Complianz's `cmplz_status_change` JS event
 *   3. Maps Complianz consent categories to Consent Mode v2 fields:
 *        marketing  -> ad_storage, ad_user_data, ad_personalization
 *        statistics -> analytics_storage
 *        preferences-> functionality_storage, personalization_storage
 *   4. Calls gtag('consent', 'update', ...) on each change
 *
 * AdSense reads dataLayer for consent state and adapts its ad calls.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function jbk_emit_consent_mode_v2() {
    echo "<!-- Google Consent Mode v2 (JBKlutse manual implementation) -->\n";
    echo "<script>\n";
    echo "window.dataLayer = window.dataLayer || [];\n";
    echo "function gtag(){dataLayer.push(arguments);}\n";
    echo "gtag('consent','default',{'ad_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','analytics_storage':'denied','functionality_storage':'denied','personalization_storage':'denied','security_storage':'granted','wait_for_update':500});\n";
    echo "gtag('set','ads_data_redaction',true);\n";
    echo "gtag('set','url_passthrough',true);\n";
    echo "document.addEventListener('cmplz_status_change',function(e){var c=(e.detail&&e.detail.categories)?e.detail.categories:[];gtag('consent','update',{'ad_storage':c.indexOf('marketing')!==-1?'granted':'denied','ad_user_data':c.indexOf('marketing')!==-1?'granted':'denied','ad_personalization':c.indexOf('marketing')!==-1?'granted':'denied','analytics_storage':c.indexOf('statistics')!==-1?'granted':'denied','functionality_storage':c.indexOf('preferences')!==-1?'granted':'denied','personalization_storage':c.indexOf('preferences')!==-1?'granted':'denied'});});\n";
    echo "document.addEventListener('cmplz_accept_marketing',function(){gtag('consent','update',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted'});});\n";
    echo "document.addEventListener('cmplz_accept_statistics',function(){gtag('consent','update',{'analytics_storage':'granted'});});\n";
    echo "document.addEventListener('cmplz_accept_preferences',function(){gtag('consent','update',{'functionality_storage':'granted','personalization_storage':'granted'});});\n";
    echo "try{var s=localStorage.getItem('cmplz_consent_status');if(s&&(s.toLowerCase()==='allow'||s.toLowerCase().indexOf('marketing')!==-1)){gtag('consent','update',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted','analytics_storage':'granted'});}}catch(e){}\n";
    echo "</script>\n";
    echo "<!-- end JBK Consent Mode v2 -->\n";
}
add_action( 'wp_head', 'jbk_emit_consent_mode_v2', 1 );
