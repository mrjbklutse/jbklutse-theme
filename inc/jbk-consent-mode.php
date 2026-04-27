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

add_action( 'wp_head', function () {
    // High priority - must run before AdSense, GA, GTM, etc.
    ?>
<!-- Google Consent Mode v2 (JBKlutse manual implementation, bridges Complianz Free) -->
<script>
window.dataLayer = window.dataLayer || [];
function gtag() { dataLayer.push(arguments); }

// Default state: deny everything until user consents. Wait 500ms for
// Complianz to load its stored state before any tag fires.
gtag('consent', 'default', {
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'denied',
    'personalization_storage': 'denied',
    'security_storage': 'granted',
    'wait_for_update': 500
});
gtag('set', 'ads_data_redaction', true);
gtag('set', 'url_passthrough', true);

// Listen to Complianz consent change events and forward to gtag.
// Complianz fires `cmplz_status_change` on the document with a detail
// object describing which categories were granted.
document.addEventListener('cmplz_status_change', function (e) {
    var consentedCategories = e.detail && e.detail.categories ? e.detail.categories : [];
    var allConsented = (e.detail && e.detail.consentLevel === 'optin' && consentedCategories.length === 0)
        || (consentedCategories.indexOf('marketing') !== -1
            && consentedCategories.indexOf('statistics') !== -1
            && consentedCategories.indexOf('preferences') !== -1);
    var payload = {
        'ad_storage':              consentedCategories.indexOf('marketing')   !== -1 ? 'granted' : 'denied',
        'ad_user_data':            consentedCategories.indexOf('marketing')   !== -1 ? 'granted' : 'denied',
        'ad_personalization':      consentedCategories.indexOf('marketing')   !== -1 ? 'granted' : 'denied',
        'analytics_storage':       consentedCategories.indexOf('statistics')  !== -1 ? 'granted' : 'denied',
        'functionality_storage':   consentedCategories.indexOf('preferences') !== -1 ? 'granted' : 'denied',
        'personalization_storage': consentedCategories.indexOf('preferences') !== -1 ? 'granted' : 'denied'
    };
    gtag('consent', 'update', payload);
});

// Also handle the "accept all" click event which Complianz fires separately
document.addEventListener('cmplz_accept_marketing', function () {
    gtag('consent', 'update', {
        'ad_storage': 'granted',
        'ad_user_data': 'granted',
        'ad_personalization': 'granted'
    });
});
document.addEventListener('cmplz_accept_statistics', function () {
    gtag('consent', 'update', { 'analytics_storage': 'granted' });
});
document.addEventListener('cmplz_accept_preferences', function () {
    gtag('consent', 'update', {
        'functionality_storage': 'granted',
        'personalization_storage': 'granted'
    });
});

// On page load, restore consent state from Complianz's stored status if
// the user has already given consent in a previous session.
(function () {
    if (typeof window.cmplz_check_cookies !== 'undefined') return; // Complianz handles
    try {
        var stored = localStorage.getItem('cmplz_consent_status');
        if (stored) {
            var consentLevel = stored.toLowerCase();
            if (consentLevel === 'allow' || consentLevel.indexOf('marketing') !== -1) {
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'analytics_storage': 'granted'
                });
            }
        }
    } catch (e) { /* localStorage blocked, fall back to default deny */ }
})();
</script>
<!-- end JBK Consent Mode v2 -->
    <?php
}, 1 ); // priority 1 = very early in <head>, before AdSense / Site Kit
