<?php
declare(strict_types=1);
/**
 * config/session.php — Session bootstrap
 *
 * require_once this file on every page that reads or writes $_SESSION.
 * It configures secure cookie settings then starts the session.
 */

// ── Harden the session cookie ─────────────────────────────────────────────────
// httponly   — JavaScript cannot read the cookie (mitigates XSS cookie theft)
// samesite   — Lax prevents CSRF on cross-site navigations
// secure     — Set to 1 on HTTPS; keep 0 for local XAMPP development
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure',   '0');   // change to '1' on production HTTPS
ini_set('session.use_strict_mode', '1');   // reject unrecognised session IDs
ini_set('session.gc_maxlifetime',  '3600'); // 1-hour idle expiry

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Helper : is the visitor currently logged in? ────────────────────────────
   Returns true if a valid session contains id_client.
   Use from any page: require_once 'config/session.php'; if (is_logged_in()) …
*/
function is_logged_in(): bool
{
    return isset($_SESSION['id_client']) && is_int($_SESSION['id_client']);
}

/* ── Helper : destroy session and clear the cookie ───────────────────────────
   Call logout() to sign out the user from any page.
*/
function logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }

    session_destroy();
}
