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

define('MYS_HOST',    '127.0.0.1');
define('MYS_USER',    'root');
define('MYS_PASS',    '');
define('MYS_DB',      'Base_Client');
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
        // mysqli_connect(host, user, pass, database)
        $conn = mysqli_connect(MYS_HOST, MYS_USER, MYS_PASS, MYS_DB);

        if (!$conn) {
            error_log('mysqli_connect failed: ' . mysqli_connect_error());
            throw new RuntimeException('Connexion base de données impossible.');
        }

        // Always force utf8mb4 — matches the table collation
        mysqli_set_charset($conn, MYS_CHARSET);
    }

    return $conn;
}
