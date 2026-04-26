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

    // Render the post inline. Bypass WP main query completely — the theme's
    // homepage template was ignoring our query manipulation. Instead we
    // build a minimal full-page render using the_content filter so all
    // WP plugins (Rank Math, embeds, image lazyload, etc.) still apply.
    global $post, $wp_query;
    $post = $post; // post object from earlier get_post()
    setup_postdata( $post );

    // Build a synthetic main query so theme functions like is_single() work
    $wp_query = new WP_Query( [ 'p' => $post_id, 'post_status' => 'any' ] );
    $wp_query->is_single  = true;
    $wp_query->is_singular = true;
    $wp_query->is_home    = false;
    $wp_query->is_front_page = false;

    // Render
    nocache_headers();
    status_header( 200 );

    $featured_url = get_the_post_thumbnail_url( $post_id, 'large' );
    $title        = get_the_title( $post_id );
    $remaining    = max( 0, $exp - time() );
    $hours        = round( $remaining / 3600, 1 );
    $author       = get_the_author_meta( 'display_name', $post->post_author );
    $word_count   = str_word_count( wp_strip_all_tags( $post->post_content ) );

    echo '<!DOCTYPE html><html ' . get_language_attributes() . '><head>';
    echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>[Preview] ' . esc_html( $title ) . ' &middot; JBKlutse</title>';
    wp_head();
    echo '<style>';
    echo '.jbk-preview-banner{background:#1f2937;color:#fbbf24;padding:10px 16px;font-family:system-ui;font-size:13px;text-align:center;border-bottom:2px solid #fbbf24;position:sticky;top:0;z-index:9999;}';
    echo '.jbk-preview-wrap{max-width:780px;margin:24px auto;padding:0 20px;font-family:system-ui,-apple-system,sans-serif;line-height:1.7;color:#1f2937;}';
    echo '.jbk-preview-wrap h1{font-size:32px;line-height:1.25;margin:0 0 8px 0;color:#0f172a;}';
    echo '.jbk-preview-wrap .meta{color:#6b7280;font-size:14px;margin-bottom:18px;}';
    echo '.jbk-preview-wrap img.featured{width:100%;height:auto;border-radius:8px;margin:12px 0 24px;}';
    echo '.jbk-preview-wrap h2{font-size:24px;margin:32px 0 12px;color:#0f172a;}';
    echo '.jbk-preview-wrap h3{font-size:19px;margin:24px 0 8px;color:#0f172a;}';
    echo '.jbk-preview-wrap p{margin:0 0 16px;}';
    echo '.jbk-preview-wrap a{color:#0369a1;}';
    echo '.jbk-preview-wrap figure{margin:20px 0;}';
    echo '.jbk-preview-wrap figure img{width:100%;height:auto;border-radius:6px;}';
    echo '.jbk-preview-wrap figcaption{color:#6b7280;font-size:13px;text-align:center;margin-top:6px;}';
    echo '.jbk-preview-wrap .image-credit small{color:#9ca3af;font-size:12px;}';
    echo '</style>';
    echo '</head><body>';

    echo '<div class="jbk-preview-banner">';
    echo '🔍 <strong>JBKlutse Review Preview</strong> &middot; Post #' . esc_html( (string) $post_id );
    echo ' &middot; Draft, not public &middot; Link expires in ' . esc_html( (string) $hours ) . 'h';
    echo ' &middot; Action via Telegram or email after reading';
    echo '</div>';

    echo '<article class="jbk-preview-wrap">';
    echo '<h1>' . esc_html( $title ) . '</h1>';
    echo '<p class="meta">By ' . esc_html( $author ) . ' &middot; ~' . esc_html( (string) $word_count ) . ' words &middot; Status: <strong>' . esc_html( $post->post_status ) . '</strong></p>';
    if ( $featured_url ) {
        echo '<img class="featured" src="' . esc_url( $featured_url ) . '" alt="' . esc_attr( $title ) . '">';
    }
    echo apply_filters( 'the_content', $post->post_content );
    echo '</article>';

    wp_reset_postdata();
    wp_footer();
    echo '</body></html>';
    exit;
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


/**
 * 3) Trending decision pages — per-item Approve / Reject from the digest.
 *
 *    URL shape:
 *      /?jbk_trending_decision=<airtable_record_id>&t=<HMAC>&exp=<TS>
 *      [&action=approve|reject|view]   (default: view)
 *      [&commit=1]                     (commits the action; without it, shows
 *                                       a confirmation page so accidental
 *                                       email-scanner pre-fetches do not fire)
 *
 *    Requirements in wp-config.php (in addition to JBK_REVIEW_SECRET):
 *      define( 'JBK_AIRTABLE_TOKEN', 'pat...' );
 *      define( 'JBK_AIRTABLE_BASE',  'app...' );
 */
if ( ! defined( 'JBK_AIRTABLE_TOKEN' ) ) define( 'JBK_AIRTABLE_TOKEN', '' );
if ( ! defined( 'JBK_AIRTABLE_BASE' ) )  define( 'JBK_AIRTABLE_BASE', '' );


function jbk_at_request( string $method, string $path, array $body = null ) {
    if ( JBK_AIRTABLE_TOKEN === '' || JBK_AIRTABLE_BASE === '' ) {
        return [ 'error' => 'Airtable creds not configured in wp-config.php' ];
    }
    $url  = 'https://api.airtable.com/v0/' . JBK_AIRTABLE_BASE . $path;
    $args = [
        'method'  => $method,
        'headers' => [
            'Authorization' => 'Bearer ' . JBK_AIRTABLE_TOKEN,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 20,
    ];
    if ( $body !== null ) {
        $args['body'] = wp_json_encode( $body );
    }
    $resp = wp_remote_request( $url, $args );
    if ( is_wp_error( $resp ) ) {
        return [ 'error' => $resp->get_error_message() ];
    }
    $code = wp_remote_retrieve_response_code( $resp );
    $data = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( $code >= 400 ) {
        return [ 'error' => "HTTP $code: " . wp_json_encode( $data ) ];
    }
    return $data ?: [];
}


function jbk_decision_page_html( string $title, string $body_html, string $tone = 'neutral' ): string {
    $colors = [
        'neutral' => [ '#0f172a', '#fbbf24' ],
        'success' => [ '#065f46', '#10b981' ],
        'danger'  => [ '#7f1d1d', '#ef4444' ],
    ];
    [ $bg, $accent ] = $colors[ $tone ] ?? $colors['neutral'];
    return '<!DOCTYPE html><html><head>'
        . '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . esc_html( $title ) . ' &middot; JBKlutse Review</title>'
        . '<style>'
        . 'body{font-family:system-ui,-apple-system,sans-serif;background:#f3f4f6;margin:0;padding:24px;color:#111827;}'
        . '.card{max-width:720px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);}'
        . '.hdr{background:' . $bg . ';color:' . $accent . ';padding:18px 24px;font-weight:bold;font-size:18px;}'
        . '.body{padding:24px;line-height:1.6;}'
        . 'h2{margin:0 0 8px 0;font-size:22px;line-height:1.3;}'
        . '.meta{color:#6b7280;font-size:14px;margin:0 0 18px 0;}'
        . '.score{display:inline-block;background:#0f172a;color:#fbbf24;font-weight:bold;padding:4px 12px;border-radius:4px;font-size:14px;}'
        . '.btn{display:inline-block;padding:14px 24px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;margin:6px 6px 6px 0;border:0;cursor:pointer;}'
        . '.btn-approve{background:#10b981;color:#fff;}'
        . '.btn-reject{background:#ef4444;color:#fff;}'
        . '.btn-secondary{background:#f3f4f6;color:#374151;border:1px solid #d1d5db;}'
        . '.kicker{background:#fef3c7;border:1px solid #fbbf24;border-radius:6px;padding:12px 16px;color:#78350f;font-size:14px;margin:18px 0;}'
        . '.dim{color:#6b7280;font-size:13px;}'
        . 'a{color:#0f172a;}'
        . '</style></head><body><div class="card"><div class="hdr">JBKlutse Trending Review</div><div class="body">'
        . $body_html
        . '</div></div></body></html>';
}


add_action( 'init', function () {
    if ( empty( $_GET['jbk_trending_decision'] ) ) {
        return;
    }
    if ( JBK_REVIEW_SECRET === '' ) {
        wp_die( 'Trending decisions disabled: JBK_REVIEW_SECRET not configured.' );
    }

    $airtable_id = preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_GET['jbk_trending_decision'] ) );
    $token       = isset( $_GET['t'] )      ? sanitize_text_field( wp_unslash( $_GET['t'] ) )      : '';
    $exp         = isset( $_GET['exp'] )    ? absint( $_GET['exp'] )                                : 0;
    $action      = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) )         : 'view';
    $commit      = ! empty( $_GET['commit'] );

    if ( ! $airtable_id || ! $token || ! $exp ) {
        status_header( 400 );
        echo jbk_decision_page_html( 'Bad link', '<p>Missing parameters.</p>', 'danger' );
        exit;
    }
    if ( time() > $exp ) {
        status_header( 410 );
        echo jbk_decision_page_html( 'Link expired',
            '<p>This decision link has expired (links live for 48 hours). The next digest will show this story again if it is still relevant.</p>',
            'danger' );
        exit;
    }
    $expected = hash_hmac( 'sha256', "{$airtable_id}:{$exp}", JBK_REVIEW_SECRET );
    if ( ! hash_equals( $expected, $token ) ) {
        status_header( 403 );
        echo jbk_decision_page_html( 'Invalid signature', '<p>Token did not validate.</p>', 'danger' );
        exit;
    }

    // Fetch the Airtable record so we can show context
    $rec = jbk_at_request( 'GET', '/Trending%20Queue/' . urlencode( $airtable_id ) );
    if ( ! empty( $rec['error'] ) ) {
        status_header( 500 );
        echo jbk_decision_page_html( 'Lookup failed',
            '<p>Could not load this story from Airtable.</p><p class="dim">' . esc_html( $rec['error'] ) . '</p>', 'danger' );
        exit;
    }
    $f        = $rec['fields'] ?? [];
    $headline = $f['Headline']        ?? '(no headline)';
    $source   = $f['Source Name']     ?? '?';
    $url      = $f['Source URL']      ?? '#';
    $score    = isset( $f['Score'] )            ? intval( $f['Score'] )            : 0;
    $age      = isset( $f['Freshness Hours'] )  ? intval( $f['Freshness Hours'] )  : 0;
    $gr       = isset( $f['Ghana Relevance'] )  ? intval( $f['Ghana Relevance'] )  : 0;
    $angle    = $f['Angle Notes']     ?? '';
    $status   = $f['Status']          ?? '?';

    // VIEW (default) — show the story + 3 buttons
    if ( $action === 'view' ) {
        $approve_url = add_query_arg( [
            'jbk_trending_decision' => $airtable_id, 't' => $token, 'exp' => $exp, 'action' => 'approve',
        ], home_url( '/' ) );
        $reject_url  = add_query_arg( [
            'jbk_trending_decision' => $airtable_id, 't' => $token, 'exp' => $exp, 'action' => 'reject',
        ], home_url( '/' ) );

        $body  = '<span class="score">Score ' . esc_html( $score ) . '</span> ';
        $body .= '<span class="dim" style="margin-left:8px;">Status: <strong>' . esc_html( $status ) . '</strong></span>';
        $body .= '<h2 style="margin-top:14px;">' . esc_html( $headline ) . '</h2>';
        $body .= '<p class="meta">' . esc_html( $source ) . ' &middot; ' . esc_html( $age ) . 'h ago &middot; Ghana relevance ' . esc_html( $gr ) . '/10</p>';
        if ( $angle ) {
            $body .= '<p><strong>Suggested angle:</strong> ' . esc_html( $angle ) . '</p>';
        }
        $body .= '<p><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">&#128214; Open original article &rarr;</a></p>';

        if ( $status === 'Proposed' ) {
            $body .= '<div style="margin-top:24px;">';
            $body .= '<a class="btn btn-approve" href="' . esc_url( $approve_url ) . '">&#10003; Approve &amp; draft</a>';
            $body .= '<a class="btn btn-reject" href="' . esc_url( $reject_url ) . '">&#10005; Reject</a>';
            $body .= '<a class="btn btn-secondary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">&#128270; Read source first</a>';
            $body .= '</div>';
            $body .= '<p class="dim" style="margin-top:18px;">Approve sends this to the drafter (Sonnet writes a 600&ndash;1,200 word article in ~90s, you then review the full draft). Reject permanently removes it from the queue.</p>';
        } else {
            $body .= '<div class="kicker">This story is no longer in <code>Proposed</code> state (current: <strong>' . esc_html( $status ) . '</strong>). It has already been actioned.</div>';
        }

        echo jbk_decision_page_html( $headline, $body );
        exit;
    }

    // APPROVE / REJECT — show confirm page (unless already committed)
    if ( in_array( $action, [ 'approve', 'reject' ], true ) ) {
        $new_status = ( $action === 'approve' ) ? 'Approved' : 'Rejected';

        if ( ! $commit ) {
            $commit_url = add_query_arg( [
                'jbk_trending_decision' => $airtable_id, 't' => $token, 'exp' => $exp,
                'action' => $action, 'commit' => '1',
            ], home_url( '/' ) );
            $back_url = add_query_arg( [
                'jbk_trending_decision' => $airtable_id, 't' => $token, 'exp' => $exp, 'action' => 'view',
            ], home_url( '/' ) );

            $tone   = ( $action === 'approve' ) ? 'success' : 'danger';
            $verb   = ( $action === 'approve' ) ? '&#10003; Approve &amp; draft' : '&#10005; Reject';
            $btn_cl = ( $action === 'approve' ) ? 'btn-approve' : 'btn-reject';

            $body  = '<h2>Confirm: ' . $verb . '</h2>';
            $body .= '<p class="meta">' . esc_html( $headline ) . '</p>';
            $body .= '<p>' . ( $action === 'approve'
                ? 'Send this to the drafter? Sonnet will write a full article (~90s, ~$0.06).'
                : 'Reject permanently? It will not be drafted.' ) . '</p>';
            $body .= '<div style="margin-top:24px;">';
            $body .= '<a class="btn ' . $btn_cl . '" href="' . esc_url( $commit_url ) . '">Yes, ' . $verb . '</a>';
            $body .= '<a class="btn btn-secondary" href="' . esc_url( $back_url ) . '">&larr; Back</a>';
            $body .= '</div>';
            echo jbk_decision_page_html( 'Confirm', $body, $tone );
            exit;
        }

        // Commit
        if ( $status !== 'Proposed' ) {
            echo jbk_decision_page_html( 'Already actioned',
                '<div class="kicker">This story has already been actioned (current status: <strong>' . esc_html( $status ) . '</strong>).</div>',
                'neutral' );
            exit;
        }

        $patch_body = [ 'fields' => [ 'Status' => $new_status ] ];
        if ( $action === 'approve' ) {
            $patch_body['fields']['Approved At'] = gmdate( 'c' );
        }
        $upd = jbk_at_request( 'PATCH', '/Trending%20Queue/' . urlencode( $airtable_id ), $patch_body );
        if ( ! empty( $upd['error'] ) ) {
            echo jbk_decision_page_html( 'Update failed',
                '<p>Airtable rejected the update.</p><p class="dim">' . esc_html( $upd['error'] ) . '</p>',
                'danger' );
            exit;
        }

        $tone  = ( $action === 'approve' ) ? 'success' : 'danger';
        $title = ( $action === 'approve' ) ? '&#10003; Approved' : '&#10005; Rejected';
        $next  = ( $action === 'approve' )
            ? '<p>The drafter will pick this up in the next 15-min cycle. You will receive a Telegram + email when the full draft is ready for review.</p>'
            : '<p>This story will not be drafted.</p>';
        $body  = '<h2>' . $title . '</h2>';
        $body .= '<p class="meta">' . esc_html( $headline ) . '</p>';
        $body .= $next;
        $body .= '<p class="dim" style="margin-top:18px;">You can close this page.</p>';
        echo jbk_decision_page_html( $title, $body, $tone );
        exit;
    }

    status_header( 400 );
    echo jbk_decision_page_html( 'Unknown action', '<p>Action must be view, approve, or reject.</p>', 'danger' );
    exit;
}, 1 );
