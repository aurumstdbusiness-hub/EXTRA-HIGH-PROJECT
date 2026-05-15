<?php
// Temporary diagnostic — DELETE after fixing
$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';

echo '<pre>';
echo 'MYSQL_URL set: ' . ($url !== '' ? 'YES' : 'NO') . "\n";

if ($url !== '') {
    $p = parse_url($url);
    echo 'Host : ' . ($p['host'] ?? 'MISSING') . "\n";
    echo 'Port : ' . ($p['port'] ?? 'MISSING') . "\n";
    echo 'User : ' . ($p['user'] ?? 'MISSING') . "\n";
    echo 'Pass : ' . (isset($p['pass']) ? str_repeat('*', strlen($p['pass'])) : 'MISSING') . "\n";
    echo 'DB   : ' . ltrim($p['path'] ?? '', '/') . "\n\n";

    $conn = mysqli_init();
    mysqli_ssl_set($conn, null, null, null, null, null);
    $ok = mysqli_real_connect(
        $conn,
        $p['host'], urldecode($p['user'] ?? ''), urldecode($p['pass'] ?? ''),
        ltrim($p['path'] ?? '', '/'), (int)($p['port'] ?? 3306),
        null, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
    );
    echo $ok ? "CONNECTION: OK\n" : "CONNECTION FAILED: " . mysqli_connect_error() . "\n";
} else {
    echo "No MYSQL_URL found - check Render environment variables\n";
}
echo '</pre>';
