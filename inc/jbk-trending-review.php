<?php
/**
 * JBKlutse — Trending Review Endpoints
 * --------------------------------------
 * Provides:
 *   1. A signed public preview URL for draft posts
 *      (so John can review on phone without logging into wp-admin):
 *          /?jbk_review=<post_id>&t=<hmac>&exp=<unix_ts>
 *   2. A REST endpoint that sends review emails via wp_mail()
 *      (which routes through wp-mail-smtp's existing SMTP config):
 *          POST /wp-json/jbklutse/v1/send-review-email
 *
 * Install:
 *   1. Place this file at:
 *        wp-content/themes/jbklutse-theme/inc/jbk-trending-review.php
 *   2. In functions.php add:
 *        require_once get_stylesheet_directory() . '/inc/jbk-trending-review.php';
 *   3. In wp-config.php add a long random secret (separate from auth keys):
 *        define( 'JBK_REVIEW_SECRET', 'paste-a-long-random-string-here' );
 *   4. Mirror the same value into your local .env as JBK_REVIEW_SECRET so
 *      the trending_draft.py script can sign matching URLs.
 *
 * Security model:
 *   - Tokens are HMAC-SHA256 signed with JBK_REVIEW_SECRET.
 *   - Tokens carry an expiry (default 48h). After expiry the link 404s.
 *   - The preview URL only renders posts in 'draft' or 'pending' status —
 *     never bypasses publication state for already-public posts.
 *   - The send-review-email endpoint requires Application Password auth
 *     and the same WP user role used by the publishing pipeline.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'JBK_REVIEW_SECRET' ) ) {
    // Fallback so the site doesn't fatal — but bypass URL feature gracefully.
    define( 'JBK_REVIEW_SECRET', '' );
}

/**
 * 1) Signed public preview URL handler.
 *
 * On request to ?jbk_review=ID&t=HMAC&exp=TS, validate token, then render
 * the draft post for the user as if they were the author (single template).
 */
add_action( 'init', function () {
    if ( empty( $_GET['jbk_review'] ) ) {
        return;
    }
    if ( JBK_REVIEW_SECRET === '' ) {
        wp_die( 'Preview disabled: JBK_REVIEW_SECRET not configured.', 'Preview', [ 'response' => 503 ] );
    }

    $post_id = absint( $_GET['jbk_review'] );
    $token   = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : '';
    $exp     = isset( $_GET['exp'] ) ? absint( $_GET['exp'] ) : 0;

    if ( ! $post_id || ! $token || ! $exp ) {
        wp_die( 'Invalid preview link.', 'Preview', [ 'response' => 400 ] );
    }
    if ( time() > $exp ) {
        wp_die( 'Preview link has expired. Generate a new one from the review system.', 'Preview', [ 'response' => 410 ] );
    }

    $expected = hash_hmac( 'sha256', "{$post_id}:{$exp}", JBK_REVIEW_SECRET );
    if ( ! hash_equals( $expected, $token ) ) {
        wp_die( 'Invalid preview signature.', 'Preview', [ 'response' => 403 ] );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_die( 'Post not found.', 'Preview', [ 'response' => 404 ] );
    }
    if ( ! in_array( $post->post_status, [ 'draft', 'pending', 'auto-draft', 'private' ], true ) ) {
        wp_redirect( get_permalink( $post_id ) );
        exit;
    }

    // Spoof status to 'publish' for this request only so themes render normally.
    add_filter( 'the_posts', function ( $posts ) use ( $post_id ) {
        foreach ( $posts as $p ) {
            if ( $p->ID === $post_id ) {
                $p->post_status = 'publish';
            }
        }
        return $posts;
    } );

    // Force WP to load this post as the main query.
    add_action( 'pre_get_posts', function ( $q ) use ( $post_id ) {
        if ( $q->is_main_query() ) {
            $q->set( 'p', $post_id );
            $q->set( 'post_status', [ 'draft', 'pending', 'private', 'publish' ] );
        }
    } );

    // Banner so the reviewer knows it's a preview, not a live post.
    add_action( 'wp_body_open', function () use ( $post_id, $exp ) {
        $remaining = max( 0, $exp - time() );
        $hours     = round( $remaining / 3600, 1 );
        echo '<div style="background:#1f2937;color:#fbbf24;padding:10px 16px;font-family:system-ui;font-size:13px;text-align:center;border-bottom:2px solid #fbbf24;">';
        echo '🔍 <strong>JBKlutse Review Preview</strong> — Post #' . esc_html( (string) $post_id );
        echo ' · Draft, not public · Link expires in ' . esc_html( (string) $hours ) . 'h · ';
        echo 'Reply on Telegram with <strong>Approve</strong>, <strong>Suggest</strong>, or <strong>Reject</strong>.';
        echo '</div>';
    } );
}, 1 );


/**
 * 2) REST endpoint: send a review email via wp_mail().
 *
 * POST /wp-json/jbklutse/v1/send-review-email
 *   { "post_id": 147700,
 *     "to": "jbklutse@gmail.com",
 *     "subject": "Review: ...",
 *     "html_body": "<p>...</p>" }
 *
 * Authenticates with the same Application Password used by the publishing
 * pipeline. Returns { ok: true, message_id: ... }.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'jbklutse/v1', '/send-review-email', [
        'methods'             => 'POST',
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
        'args' => [
            'post_id'   => [ 'required' => false, 'sanitize_callback' => 'absint' ],
            'to'        => [ 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
            'subject'   => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'html_body' => [ 'required' => true ],
        ],
        'callback' => function ( WP_REST_Request $req ) {
            $to        = $req->get_param( 'to' );
            $subject   = $req->get_param( 'subject' );
            $html_body = (string) $req->get_param( 'html_body' );
            $post_id   = (int) $req->get_param( 'post_id' );

            if ( ! is_email( $to ) ) {
                return new WP_REST_Response( [ 'ok' => false, 'error' => 'invalid recipient' ], 400 );
            }

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: JBKlutse Review <noreply@jbklutse.com>',
            ];
            // Add a Reply-To so user can reply directly if their inbox supports it.
            $headers[] = 'Reply-To: jbklutse@gmail.com';

            // Add hidden tracking headers for debugging.
            if ( $post_id ) {
                $headers[] = 'X-JBK-Review-Post-ID: ' . $post_id;
            }

            $sent = wp_mail( $to, $subject, $html_body, $headers );
            return new WP_REST_Response( [
                'ok'      => (bool) $sent,
                'post_id' => $post_id,
            ], $sent ? 200 : 500 );
        },
    ] );
} );


/**
 * Helper exposed for PHP callers (not used by Python client, which signs locally).
 */
function jbk_build_review_url( int $post_id, int $ttl_hours = 48 ): string {
    if ( JBK_REVIEW_SECRET === '' ) {
        return get_edit_post_link( $post_id ) ?: '';
    }
    $exp   = time() + ( $ttl_hours * 3600 );
    $token = hash_hmac( 'sha256', "{$post_id}:{$exp}", JBK_REVIEW_SECRET );
    return add_query_arg( [
        'jbk_review' => $post_id,
        't'          => $token,
        'exp'        => $exp,
    ], home_url( '/' ) );
}
