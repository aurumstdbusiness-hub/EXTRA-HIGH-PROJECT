<?php
declare(strict_types=1);
/**
 * Database connection — mysqli
 *
 * Used by : inscription.php  (Phase 8)
 *           login.php       (Phase 9)
 *           commande.php    (Phase 10)
 *
 * Credentials match XAMPP defaults.
 * Change only the four constants below when deploying.
 */

// ── Resolve DB credentials ────────────────────────────────────────────────────
// Priority 1 : MYSQL_URL (Railway format)  mysql://user:pass@host:port/dbname
// Priority 2 : individual DB_* env vars    (Render / Docker)
// Priority 3 : XAMPP localhost defaults    (local development)

$_ehp_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';

if ($_ehp_url !== '') {
    $_ehp_parsed = parse_url($_ehp_url);
    define('MYS_HOST', (string) ($_ehp_parsed['host'] ?? '127.0.0.1'));
    define('MYS_USER', urldecode((string) ($_ehp_parsed['user'] ?? 'root')));
    define('MYS_PASS', urldecode((string) ($_ehp_parsed['pass'] ?? '')));
    define('MYS_DB',   ltrim((string) ($_ehp_parsed['path'] ?? '/Base_Client'), '/'));
    define('MYS_PORT', (int)    ($_ehp_parsed['port'] ?? 3306));
    define('MYS_SSL',  true);   // Railway requires SSL
    unset($_ehp_parsed);
} else {
    define('MYS_HOST', (string)(getenv('DB_HOST') ?: '127.0.0.1'));
    define('MYS_USER', (string)(getenv('DB_USER') ?: 'root'));
    define('MYS_PASS', (string)(getenv('DB_PASS') ?: ''));
    define('MYS_DB',   (string)(getenv('DB_NAME') ?: 'Base_Client'));
    define('MYS_PORT', 3306);
    define('MYS_SSL',  false);
}
unset($_ehp_url);

define('MYS_CHARSET', 'utf8mb4');

/**
 * mysqli_db() — returns a reused mysqli connection (singleton).
 *
 * Throws RuntimeException on failure so callers can catch it
 * without leaking credentials in error messages.
 */
function mysqli_db(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        $conn = mysqli_init();
        if (!$conn) {
            throw new RuntimeException('Connexion base de données impossible.');
        }

        // Skip SSL certificate verification for Railway / self-signed certs
        if (MYS_SSL) {
            mysqli_ssl_set($conn, null, null, null, null, null);
        }

        $flags = MYS_SSL ? MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0;

        $ok = mysqli_real_connect(
            $conn, MYS_HOST, MYS_USER, MYS_PASS, MYS_DB, MYS_PORT,
            null, $flags
        );

        if (!$ok) {
            error_log('mysqli_connect failed: ' . mysqli_connect_error());
            throw new RuntimeException('Connexion base de données impossible.');
        }

        // Always force utf8mb4 — matches the table collation
        mysqli_set_charset($conn, MYS_CHARSET);
    }

    return $conn;
}
