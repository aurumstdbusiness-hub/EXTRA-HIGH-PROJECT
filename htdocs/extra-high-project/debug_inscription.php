<?php
/**
 * debug_inscription.php — Temporary debug endpoint
 * DELETE THIS FILE after debugging!
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Inscription Debug ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP: " . PHP_VERSION . "\n\n";

// 1. Check session
echo "--- Session ---\n";
require_once __DIR__ . '/config/session.php';
echo "session_status: " . session_status() . " (2=active)\n";
echo "session_id: " . session_id() . "\n";
echo "save_path: " . session_save_path() . "\n";
$savePath = session_save_path() ?: sys_get_temp_dir();
echo "save_path writable: " . (is_writable($savePath) ? 'YES' : 'NO') . "\n\n";

// 2. Check security
echo "--- Security ---\n";
require_once __DIR__ . '/config/security.php';
echo "apply_security_headers exists: " . (function_exists('apply_security_headers') ? 'YES' : 'NO') . "\n\n";

// 3. Check DB connection
echo "--- DB Connection ---\n";
require_once __DIR__ . '/config/db_mysqli.php';
try {
    $conn = mysqli_db();
    echo "Connection: OK\n";
    echo "Server: " . mysqli_get_server_info($conn) . "\n";
} catch (\Throwable $e) {
    echo "Connection FAILED: [" . get_class($e) . "] " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit;
}

// 4. Test exact INSERT that inscription.php does
echo "\n--- Test INSERT (same as inscription.php) ---\n";
$prenom = 'DebugTest';
$nom = 'User';
$email = 'debug_test_' . time() . '@test.com';
$phone_val = '';
$date_naissance = '2000-01-01';
$adresse_val = '';
$wilaya_val = '';
$zip_val = '';
$hash = password_hash('TestPass123', PASSWORD_BCRYPT);

try {
    // Step A: email uniqueness check
    $chk = mysqli_prepare($conn,
        'SELECT id_client FROM Client WHERE email = ? LIMIT 1'
    );
    if (!$chk) throw new RuntimeException('PREPARE chk: ' . mysqli_error($conn));

    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    mysqli_stmt_store_result($chk);
    $numRows = mysqli_stmt_num_rows($chk);
    echo "Email check: $numRows existing rows\n";
    mysqli_stmt_close($chk);

    // Step B: INSERT
    $ins = mysqli_prepare($conn, '
        INSERT INTO Client
          (prenom, nom, email, phone, date_naissance,
           adresse, wilaya, code_postal, mot_de_passe)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    if (!$ins) throw new RuntimeException('PREPARE ins: ' . mysqli_error($conn));

    echo "Bind types: sssssssss\n";
    echo "Values: prenom=$prenom, nom=$nom, email=$email\n";
    echo "  phone='' dob=$date_naissance addr='' wil='' zip=''\n";
    echo "  hash=" . substr($hash, 0, 20) . "...\n";

    mysqli_stmt_bind_param($ins, 'sssssssss',
        $prenom, $nom, $email, $phone_val, $date_naissance,
        $adresse_val, $wilaya_val, $zip_val, $hash
    );

    $execOk = mysqli_stmt_execute($ins);
    if (!$execOk) {
        echo "!! EXECUTE FAILED\n";
        echo "   Error: " . mysqli_stmt_error($ins) . "\n";
        echo "   Errno: " . mysqli_stmt_errno($ins) . "\n";
    } else {
        $newId = (int) mysqli_insert_id($conn);
        echo "INSERT OK: id_client = $newId\n";

        // Step C: Test session_regenerate_id (this might be the culprit!)
        echo "\n--- Test session_regenerate_id ---\n";
        try {
            session_regenerate_id(true);
            echo "session_regenerate_id: OK\n";
        } catch (\Throwable $e) {
            echo "session_regenerate_id FAILED: " . $e->getMessage() . "\n";
        }

        // Cleanup
        $del = mysqli_prepare($conn, 'DELETE FROM Client WHERE id_client = ?');
        if ($del) {
            mysqli_stmt_bind_param($del, 'i', $newId);
            mysqli_stmt_execute($del);
            echo "Cleanup: deleted\n";
            mysqli_stmt_close($del);
        }
    }

    mysqli_stmt_close($ins);

} catch (\Throwable $e) {
    echo "\n!! EXCEPTION: [" . get_class($e) . "]\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
mysqli_close($conn);
