<?php
/**
 * test_db.php — Full diagnostic for Railway MySQL connection
 * DELETE THIS FILE after debugging!
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== E-coomy Database Diagnostic ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ── Step 1: Check environment variable ──────────────────────────────────────
$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
echo "--- STEP 1: Environment Variables ---\n";
echo "MYSQL_URL set: " . ($url !== '' ? 'YES' : 'NO') . "\n";

if ($url === '') {
    // Check individual vars
    $h = getenv('DB_HOST') ?: '(not set)';
    $u = getenv('DB_USER') ?: '(not set)';
    $p = getenv('DB_PASS') ? '***SET***' : '(not set)';
    $d = getenv('DB_NAME') ?: '(not set)';
    echo "DB_HOST: $h\n";
    echo "DB_USER: $u\n";
    echo "DB_PASS: $p\n";
    echo "DB_NAME: $d\n";
    echo "\n!! PROBLEM: No MYSQL_URL found. Falling back to individual vars.\n";
    echo "   If DB_HOST is 127.0.0.1, connection WILL FAIL on Render.\n\n";
}

// ── Step 2: Parse URL ───────────────────────────────────────────────────────
if ($url !== '') {
    echo "\n--- STEP 2: URL Parsing ---\n";
    $p = parse_url($url);
    if ($p === false) {
        echo "!! FATAL: parse_url() FAILED — the MYSQL_URL is malformed.\n";
        echo "   Raw URL (first 20 chars): " . substr($url, 0, 20) . "...\n";
        exit;
    }
    $host = $p['host'] ?? 'MISSING';
    $port = (int)($p['port'] ?? 3306);
    $user = urldecode($p['user'] ?? '');
    $pass = urldecode($p['pass'] ?? '');
    $db   = ltrim($p['path'] ?? '', '/');

    echo "Host : $host\n";
    echo "Port : $port\n";
    echo "User : $user\n";
    echo "Pass : " . ($pass !== '' ? str_repeat('*', strlen($pass)) : 'EMPTY') . "\n";
    echo "DB   : $db\n";
} else {
    $host = (string)(getenv('DB_HOST') ?: '127.0.0.1');
    $user = (string)(getenv('DB_USER') ?: 'root');
    $pass = (string)(getenv('DB_PASS') ?: '');
    $db   = (string)(getenv('DB_NAME') ?: 'Base_Client');
    $port = 3306;
}

// ── Step 3: Test connection ─────────────────────────────────────────────────
echo "\n--- STEP 3: Connection Test ---\n";

$conn = mysqli_init();
if (!$conn) {
    echo "!! FATAL: mysqli_init() failed.\n";
    exit;
}

$useSSL = ($url !== '');
if ($useSSL) {
    mysqli_ssl_set($conn, null, null, null, null, null);
    echo "SSL: Enabled (Railway)\n";
}

$flags = $useSSL ? MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT : 0;

$ok = @mysqli_real_connect($conn, $host, $user, $pass, $db, $port, null, $flags);

if (!$ok) {
    echo "!! CONNECTION FAILED\n";
    echo "   Error: " . mysqli_connect_error() . "\n";
    echo "   Errno: " . mysqli_connect_errno() . "\n";
    echo "\n   Common fixes:\n";
    echo "   - Check MYSQL_URL is correctly set in Render Environment\n";
    echo "   - Check Railway MySQL service is running\n";
    echo "   - Check the password has no unescaped special characters\n";
    exit;
}

echo "CONNECTION: OK ✓\n";
echo "Server: " . mysqli_get_server_info($conn) . "\n";
echo "Charset: " . mysqli_character_set_name($conn) . "\n";

mysqli_set_charset($conn, 'utf8mb4');

// ── Step 4: Check tables exist ──────────────────────────────────────────────
echo "\n--- STEP 4: Table Check ---\n";
$tables = mysqli_query($conn, "SHOW TABLES");
if ($tables) {
    $found = [];
    while ($row = mysqli_fetch_row($tables)) {
        $found[] = $row[0];
        echo "  Table: " . $row[0] . "\n";
    }
    if (empty($found)) {
        echo "!! NO TABLES FOUND — you need to run setup_production.sql\n";
        exit;
    }
    if (!in_array('Client', $found)) {
        echo "!! MISSING: 'Client' table not found!\n";
    }
} else {
    echo "!! SHOW TABLES failed: " . mysqli_error($conn) . "\n";
}

// ── Step 5: Check Client table structure ────────────────────────────────────
echo "\n--- STEP 5: Client Table Structure ---\n";
$desc = mysqli_query($conn, "DESCRIBE Client");
if ($desc) {
    echo sprintf("  %-20s %-30s %-6s %-10s\n", 'Field', 'Type', 'Null', 'Key');
    echo "  " . str_repeat('-', 70) . "\n";
    while ($row = mysqli_fetch_assoc($desc)) {
        echo sprintf("  %-20s %-30s %-6s %-10s\n",
            $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
} else {
    echo "!! DESCRIBE Client failed: " . mysqli_error($conn) . "\n";
}

// ── Step 6: Test INSERT (then DELETE) ───────────────────────────────────────
echo "\n--- STEP 6: Test INSERT ---\n";
$testEmail = 'test_diagnostic_' . time() . '@test.com';
$testHash  = password_hash('TestPass123', PASSWORD_BCRYPT);

$ins = mysqli_prepare($conn, '
    INSERT INTO Client
      (prenom, nom, email, phone, date_naissance,
       adresse, wilaya, code_postal, mot_de_passe)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
');

if (!$ins) {
    echo "!! PREPARE failed: " . mysqli_error($conn) . "\n";
    echo "   This means the INSERT SQL syntax doesn't match the table.\n";
    exit;
}

$prenom = 'Test';
$nom    = 'Diagnostic';
$phone  = null;
$dob    = '2000-01-01';
$addr   = null;
$wil    = null;
$zip    = null;

mysqli_stmt_bind_param($ins, 'sssssssss',
    $prenom, $nom, $testEmail, $phone, $dob, $addr, $wil, $zip, $testHash
);

$execOk = mysqli_stmt_execute($ins);
if (!$execOk) {
    echo "!! INSERT FAILED\n";
    echo "   Error : " . mysqli_stmt_error($ins) . "\n";
    echo "   Errno : " . mysqli_stmt_errno($ins) . "\n";
    echo "\n   This is the EXACT error your inscription page is hitting.\n";
} else {
    $newId = (int) mysqli_insert_id($conn);
    echo "INSERT: OK ✓  (id_client = $newId)\n";

    // Clean up test row
    $del = mysqli_prepare($conn, 'DELETE FROM Client WHERE id_client = ?');
    if ($del) {
        mysqli_stmt_bind_param($del, 'i', $newId);
        mysqli_stmt_execute($del);
        echo "CLEANUP: Test row deleted ✓\n";
        mysqli_stmt_close($del);
    }
}

mysqli_stmt_close($ins);

// ── Step 7: Check row count ─────────────────────────────────────────────────
echo "\n--- STEP 7: Current Row Count ---\n";
$cnt = mysqli_query($conn, "SELECT COUNT(*) as c FROM Client");
if ($cnt) {
    $row = mysqli_fetch_assoc($cnt);
    echo "Client rows: " . $row['c'] . "\n";
}
$cnt2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM Commande_produit");
if ($cnt2) {
    $row2 = mysqli_fetch_assoc($cnt2);
    echo "Commande_produit rows: " . $row2['c'] . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
mysqli_close($conn);
