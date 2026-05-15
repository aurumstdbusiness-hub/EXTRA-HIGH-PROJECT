<?php
declare(strict_types=1);
/**
 * config/security.php — HTTP security headers  (Phase 11)
 *
 * require_once this file in every PHP handler, then call
 * apply_security_headers() before any HTML output.
 *
 * Headers applied:
 *   X-Content-Type-Options  — stop MIME sniffing
 *   X-Frame-Options         — block clickjacking (deny framing from other origins)
 *   Referrer-Policy         — limit cross-origin referrer leakage
 *   Cache-Control / Pragma  — prevent caching of authenticated responses
 */

function apply_security_headers(): void
{
    // Prevent MIME-type sniffing (OWASP A05:2021)
    header('X-Content-Type-Options: nosniff');

    // Deny framing from foreign origins (clickjacking protection)
    header('X-Frame-Options: SAMEORIGIN');

    // Only send the origin on cross-origin navigations, not the full URL
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Do not cache authenticated pages in the browser or proxy
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}
