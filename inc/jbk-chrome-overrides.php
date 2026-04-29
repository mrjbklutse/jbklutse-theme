<?php
/**
 * JBKlutse — Site chrome (header/footer) brand-color overrides
 *
 * Block theme generates per-block CSS that sometimes overrides inline
 * style attributes set in the .html template parts. This file adds a
 * targeted stylesheet that forces brand-correct colors on the header
 * and footer text + links so contrast is always WCAG-AA compliant.
 *
 * Hooks at wp_head priority 100 so it lands AFTER block-generated CSS.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_head', function () {
    ?>
<style id="jbk-chrome-overrides">
/* ── Header (dark primary background) ───────────────────────────────── */
.has-primary-background-color .wp-block-site-title,
.has-primary-background-color .wp-block-site-title a,
.has-primary-background-color .wp-block-site-title a:hover,
.has-primary-background-color .wp-block-site-title a:focus,
.has-primary-background-color .wp-block-site-title a:visited {
    color: #ffffff !important;
}

.has-primary-background-color .wp-block-navigation,
.has-primary-background-color .wp-block-navigation a,
.has-primary-background-color .wp-block-navigation .wp-block-navigation-item__content,
.has-primary-background-color .wp-block-navigation .wp-block-navigation-link__label {
    color: #ffffff !important;
}
.has-primary-background-color .wp-block-navigation a:hover,
.has-primary-background-color .wp-block-navigation a:focus {
    color: #00b3b3 !important; /* accent teal-light on hover */
}

/* ── Footer (also dark primary background) ──────────────────────────── */
footer.has-primary-background-color,
.wp-block-group.has-primary-background-color {
    color: rgba(255,255,255,0.9);
}

/* Footer wordmark JBKlutse heading */
.has-primary-background-color h2.wp-block-heading,
.has-primary-background-color h3.wp-block-heading {
    /* leave these alone if they have their own explicit color */
}

/* Specifically lock the JBKlutse footer wordmark to white */
.has-primary-background-color h3.wp-block-heading[style*="font-weight:800"],
.has-primary-background-color h3.wp-block-heading[style*="font-weight: 800"] {
    color: #ffffff !important;
}

/* Section headings (Topics/Resources/Company) — accent teal */
.has-primary-background-color h3.wp-block-heading.has-accent-color {
    color: #00b3b3 !important;
}

/* Footer paragraph text + nav links default state */
.has-primary-background-color p.has-text-color {
    /* keep their inline rgba color, just ensure it isn't overridden */
}

.has-primary-background-color .wp-block-navigation a,
.has-primary-background-color .wp-block-navigation .wp-block-navigation-item__content,
.has-primary-background-color .wp-block-navigation .wp-block-navigation-link__label {
    color: rgba(255,255,255,0.85) !important;
}
.has-primary-background-color .wp-block-navigation a:hover,
.has-primary-background-color .wp-block-navigation a:focus {
    color: #00b3b3 !important;
}

/* Social icons on dark bg */
.has-primary-background-color .wp-block-social-link {
    background-color: transparent !important;
}
.has-primary-background-color .wp-block-social-link svg {
    fill: #ffffff !important;
}
.has-primary-background-color .wp-block-social-link:hover svg {
    fill: #00b3b3 !important;
}

/* Separator line */
.has-primary-background-color hr.wp-block-separator {
    background-color: rgba(255,255,255,0.18) !important;
    border-color: rgba(255,255,255,0.18) !important;
}
</style>
    <?php
}, 100 );
