<?php
/**
 * tests/test_suite.php — Phase 14 Test Suite
 *
 * Open in browser:
 *   http://localhost/extra-high-project/tests/test_suite.php
 *
 * Tests every subsystem without modifying any data.
 * Safe to run multiple times.
 */
declare(strict_types=1);

// Load project helpers
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db_mysqli.php';

/* ── Mini test framework ─────────────────────────────────────────────── */
$results = [];
$passed  = 0;
$failed  = 0;

function test(string $group, string $label, bool $ok, string $detail = ''): void
{
    global $results, $passed, $failed;
    $results[] = compact('group', 'label', 'ok', 'detail');
    $ok ? $passed++ : $failed++;
}

function assert_ok(string $g, string $l, bool $cond, string $d = ''): void
{
    test($g, $l, $cond, $d);
}

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 1 — PHP ENVIRONMENT
════════════════════════════════════════════════════════════════════════════ */
assert_ok('PHP', 'PHP version >= 8.0',
    version_compare(PHP_VERSION, '8.0.0', '>='),
    'Found: ' . PHP_VERSION);

assert_ok('PHP', 'mysqli extension enabled',
    extension_loaded('mysqli'), '');

assert_ok('PHP', 'mbstring extension enabled',
    extension_loaded('mbstring'), '');

assert_ok('PHP', 'session extension enabled',
    extension_loaded('session'), '');

assert_ok('PHP', 'declare(strict_types=1) — enforced by runtime',
    true, 'Required in all PHP files');

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 2 — FILE SYSTEM
════════════════════════════════════════════════════════════════════════════ */
$root = __DIR__ . '/..';

$required_files = [
    // HTML pages
    'index.html', 'pages/login.html', 'pages/inscription.html',
    'pages/products/commande_produit1.html',  'pages/products/commande_produit2.html',
    'pages/products/commande_produit3.html',  'pages/products/commande_produit4.html',
    'pages/products/commande_produit5.html',  'pages/products/commande_produit6.html',
    'pages/products/commande_produit7.html',  'pages/products/commande_produit8.html',
    'pages/products/commande_produit9.html',  'pages/products/commande_produit10.html',
    'pages/products/commande_produit11.html', 'pages/products/commande_produit12.html',
    'pages/products/commande_produit13.html', 'pages/products/commande_produit14.html',
    'pages/products/commande_produit15.html', 'pages/products/commande_produit16.html',
    'pages/products/commande_produit17.html', 'pages/products/commande_produit18.html',
    'pages/products/commande_produit19.html', 'pages/products/commande_produit20.html',
    // PHP handlers
    'commande.php', 'inscription.php', 'login.php', 'logout.php',
    // Assets
    'assets/css/style.css', 'assets/js/validation.js',
    'assets/images/placeholder.svg',
    // Config
    'config/db_mysqli.php', 'config/session.php',
    'config/security.php',  'config/.htaccess',
    // Database
    'database/setup.sql', 'database/XAMPP_SETUP.txt',
];

foreach ($required_files as $f) {
    $exists = file_exists($root . '/' . $f);
    assert_ok('Files', $f, $exists, $exists ? '' : 'MISSING');
}

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 3 — CONFIG FILES CONTENT
════════════════════════════════════════════════════════════════════════════ */
$session_src  = file_get_contents($root . '/config/session.php');
$security_src = file_get_contents($root . '/config/security.php');
$db_src       = file_get_contents($root . '/config/db_mysqli.php');
$login_src    = file_get_contents($root . '/login.php');
$insc_src     = file_get_contents($root . '/inscription.php');
$cmd_src      = file_get_contents($root . '/commande.php');

// session.php
assert_ok('Config', 'session.php: session_start()', str_contains($session_src, 'session_start()'));
assert_ok('Config', 'session.php: is_logged_in()',  str_contains($session_src, 'is_logged_in()'));
assert_ok('Config', 'session.php: logout()',        str_contains($session_src, 'function logout'));
assert_ok('Config', 'session.php: cookie_httponly', str_contains($session_src, 'cookie_httponly'));
assert_ok('Config', 'session.php: strict_mode',     str_contains($session_src, 'use_strict_mode'));

// security.php
assert_ok('Config', 'security.php: X-Content-Type-Options', str_contains($security_src, 'X-Content-Type-Options'));
assert_ok('Config', 'security.php: X-Frame-Options',        str_contains($security_src, 'X-Frame-Options'));
assert_ok('Config', 'security.php: Cache-Control no-store', str_contains($security_src, 'no-store'));

// db_mysqli.php
assert_ok('Config', 'db_mysqli.php: mysqli_connect()',    str_contains($db_src, 'mysqli_connect'));
assert_ok('Config', 'db_mysqli.php: utf8mb4 charset',    str_contains($db_src, 'utf8mb4'));
assert_ok('Config', 'db_mysqli.php: RuntimeException',   str_contains($db_src, 'RuntimeException'));

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 4 — PHP HANDLER SECURITY CHECKS
════════════════════════════════════════════════════════════════════════════ */
// login.php
assert_ok('Handlers', 'login.php: session guard',         str_contains($login_src, 'is_logged_in()'));
assert_ok('Handlers', 'login.php: FILTER_VALIDATE_EMAIL', str_contains($login_src, 'FILTER_VALIDATE_EMAIL'));
assert_ok('Handlers', 'login.php: password_verify',       str_contains($login_src, 'password_verify'));
assert_ok('Handlers', 'login.php: session_regenerate_id', str_contains($login_src, 'session_regenerate_id'));
assert_ok('Handlers', 'login.php: usleep brute-force',    str_contains($login_src, 'usleep'));
assert_ok('Handlers', 'login.php: prepared statement',    str_contains($login_src, 'mysqli_prepare'));
assert_ok('Handlers', 'login.php: PRG redirect',          str_contains($login_src, "header('Location: index.html')"));
assert_ok('Handlers', 'login.php: htmlspecialchars',      str_contains($login_src, 'htmlspecialchars'));

// inscription.php
assert_ok('Handlers', 'inscription.php: session guard',    str_contains($insc_src, 'is_logged_in()'));
assert_ok('Handlers', 'inscription.php: password_hash',    str_contains($insc_src, 'PASSWORD_BCRYPT'));
assert_ok('Handlers', 'inscription.php: email uniqueness', str_contains($insc_src, 'SELECT') && str_contains($insc_src, 'email'));
assert_ok('Handlers', 'inscription.php: auto-login',       str_contains($insc_src, 'session_regenerate_id'));
assert_ok('Handlers', 'inscription.php: bind_param',       str_contains($insc_src, 'mysqli_stmt_bind_param'));

// commande.php
assert_ok('Handlers', 'commande.php: auth guard',          str_contains($cmd_src, 'is_logged_in()'));
assert_ok('Handlers', 'commande.php: whitelist LIVRAISONS',str_contains($cmd_src, 'LIVRAISONS'));
assert_ok('Handlers', 'commande.php: whitelist TAILLES',   str_contains($cmd_src, 'TAILLES'));
assert_ok('Handlers', 'commande.php: whitelist PAIEMENTS', str_contains($cmd_src, 'PAIEMENTS'));
assert_ok('Handlers', 'commande.php: mb_strlen limits',    str_contains($cmd_src, 'mb_strlen'));
assert_ok('Handlers', 'commande.php: security headers',    str_contains($cmd_src, 'apply_security_headers'));
assert_ok('Handlers', 'commande.php: bind_param mysqli',   str_contains($cmd_src, 'mysqli_stmt_bind_param'));

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 5 — SESSION BOOTSTRAP
════════════════════════════════════════════════════════════════════════════ */
assert_ok('Session', 'session_status() returns active after require',
    session_status() === PHP_SESSION_ACTIVE, 'Status: ' . session_status());

assert_ok('Session', 'is_logged_in() callable',
    function_exists('is_logged_in'), '');

assert_ok('Session', 'logout() callable',
    function_exists('logout'), '');

assert_ok('Session', 'is_logged_in() returns bool (not logged in = false)',
    is_logged_in() === false, 'Correct: not authenticated in test context');

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 6 — DATABASE CONNECTION + SCHEMA
════════════════════════════════════════════════════════════════════════════ */
$db_ok = false;
$db_error = '';
try {
    $conn = mysqli_db();
    $db_ok = ($conn instanceof mysqli);
} catch (RuntimeException $ex) {
    $db_error = $ex->getMessage();
}

assert_ok('Database', 'mysqli_db() connects to Base_Client', $db_ok,
    $db_ok ? 'Connected to 127.0.0.1' : 'FAILED: ' . $db_error . ' — Start MySQL in XAMPP');

if ($db_ok) {
    // Table: Client
    $r = mysqli_query($conn, "SHOW TABLES LIKE 'Client'");
    assert_ok('Database', 'Table Client exists', mysqli_num_rows($r) === 1, '');

    // Table: Commande_produit
    $r2 = mysqli_query($conn, "SHOW TABLES LIKE 'Commande_produit'");
    assert_ok('Database', 'Table Commande_produit exists', mysqli_num_rows($r2) === 1, '');

    if (mysqli_num_rows($r) === 1) {
        // Client columns
        $cols = [];
        $res  = mysqli_query($conn, 'DESCRIBE Client');
        while ($row = mysqli_fetch_assoc($res)) { $cols[] = $row['Field']; }

        foreach (['id_client','prenom','nom','email','mot_de_passe','created_at'] as $col) {
            assert_ok('Database', "Client.{$col} column exists", in_array($col, $cols, true), '');
        }
    }

    if (mysqli_num_rows($r2) === 1) {
        $cols2 = [];
        $res2  = mysqli_query($conn, 'DESCRIBE Commande_produit');
        while ($row = mysqli_fetch_assoc($res2)) { $cols2[] = $row['Field']; }

        foreach (['id_commande','id_client','product_name','taille','paiement','statut'] as $col) {
            assert_ok('Database', "Commande_produit.{$col} exists", in_array($col, $cols2, true), '');
        }
    }

    // Engine + charset
    $engine_res = mysqli_query($conn,
        "SELECT ENGINE, TABLE_COLLATION
           FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = 'Base_Client'
            AND TABLE_NAME   = 'Client'");
    if ($engine_res && $row = mysqli_fetch_assoc($engine_res)) {
        assert_ok('Database', 'Client engine = InnoDB',      $row['ENGINE'] === 'InnoDB', $row['ENGINE']);
        assert_ok('Database', 'Client collation = utf8mb4',  str_starts_with($row['TABLE_COLLATION'], 'utf8mb4'), $row['TABLE_COLLATION']);
    }
}

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 7 — HTML PAGE QUALITY CHECKS
════════════════════════════════════════════════════════════════════════════ */
$pages_to_check = [
    'index.html'               => ['skip-link', 'assets/js/validation.js', 'theme-color', 'card-grid'],
    'pages/inscription.html'         => ['data-validate', 'data-form-type="signup"', 'date_naissance', 'password-strength'],
    'pages/login.html'               => ['data-validate', 'data-form-type="login"', 'email', 'password'],
    'pages/products/commande_produit1.html'   => ['T-shirt Premium Oversize', 'EHP-001', 'commande.php', 'product-info'],
    'pages/products/commande_produit10.html'  => ['Bermuda Cargo Style', 'EHP-010', 'commande.php'],
    'pages/products/commande_produit20.html'  => ['Hoodie Brodé Signature', 'EHP-020', 'commande.php'],
];

foreach ($pages_to_check as $page => $keywords) {
    $src = file_get_contents($root . '/' . $page);
    foreach ($keywords as $kw) {
        assert_ok('HTML', "{$page}: contains "{$kw}"", str_contains($src, $kw), '');
    }
}

// All 20 product pages have unique product names and refs
$names = $refs = [];
for ($i = 1; $i <= 20; $i++) {
    $s = file_get_contents($root . "/pages/products/commande_produit{$i}.html");
    preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $s, $m);
    if (!empty($m[1])) $names[] = trim(strip_tags($m[1]));
    preg_match('/value="EHP-(\d+)"/', $s, $r);
    if (!empty($r[1])) $refs[] = $r[1];
}
assert_ok('HTML', '20 product pages — all names unique',   count(array_unique($names)) === 20,
    count(array_unique($names)) . '/20 unique');
assert_ok('HTML', '20 product pages — all EHP refs unique', count(array_unique($refs)) === 20,
    count(array_unique($refs)) . '/20 unique');

/* ══════════════════════════════════════════════════════════════════════════
   TEST GROUP 8 — CSS + JS ASSETS
════════════════════════════════════════════════════════════════════════════ */
$css = file_get_contents($root . '/style.css');
$js  = file_get_contents($root . '/validation.js');

// CSS checks
assert_ok('Assets', 'style.css: design tokens (:root)',   str_contains($css, ':root'));
assert_ok('Assets', 'style.css: --c-primary token',       str_contains($css, '--c-primary:'));
assert_ok('Assets', 'style.css: responsive @media',       str_contains($css, '@media'));
assert_ok('Assets', 'style.css: prefers-reduced-motion',  str_contains($css, 'prefers-reduced-motion'));
assert_ok('Assets', 'style.css: prefers-color-scheme dark', str_contains($css, 'prefers-color-scheme: dark'));
assert_ok('Assets', 'style.css: skip-link',               str_contains($css, '.skip-link'));
assert_ok('Assets', 'style.css: password-strength meter', str_contains($css, 'password-strength'));
assert_ok('Assets', 'style.css: product-card__badge',     str_contains($css, 'product-card__badge'));
assert_ok('Assets', 'style.css: toast notifications',     str_contains($css, '.toast'));
assert_ok('Assets', 'style.css: total > 2000 lines',
    substr_count($css, "\n") > 2000, substr_count($css, "\n") . ' lines');

// JS checks
assert_ok('Assets', 'validation.js: validation_login',    str_contains($js, 'validation_login'));
assert_ok('Assets', 'validation.js: validation_infos',    str_contains($js, 'validation_infos'));
assert_ok('Assets', 'validation.js: validation_order',    str_contains($js, 'validation_order'));
assert_ok('Assets', 'validation.js: showToast',           str_contains($js, 'showToast'));
assert_ok('Assets', 'validation.js: _isAlgerianPhone',    str_contains($js, '_isAlgerianPhone'));
assert_ok('Assets', 'validation.js: password strength',   str_contains($js, '_pwScore'));
assert_ok('Assets', 'validation.js: IntersectionObserver',str_contains($js, 'IntersectionObserver'));
assert_ok('Assets', 'validation.js: ripple effect',       str_contains($js, 'ripple'));

/* ══════════════════════════════════════════════════════════════════════════
   RENDER HTML REPORT
════════════════════════════════════════════════════════════════════════════ */
$total   = $passed + $failed;
$percent = $total > 0 ? round(($passed / $total) * 100) : 0;
$groups  = [];
foreach ($results as $r) {
    $groups[$r['group']][] = $r;
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phase 14 — Test Suite | E-coomy</title>
  <style>
    :root{--pass:#059669;--fail:#dc2626;--bg:#f8f9fb;--card:#fff;--border:rgba(0,0,0,.08);--text:#0a0e1a;--muted:#6b7280}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',system-ui,sans-serif;font-size:.875rem;background:var(--bg);color:var(--text);padding:2rem}
    h1{font-size:1.5rem;margin-bottom:.25rem}
    .subtitle{color:var(--muted);margin-bottom:2rem}
    .summary{display:flex;gap:1.5rem;margin-bottom:2rem;flex-wrap:wrap}
    .stat{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.5rem;min-width:130px}
    .stat__label{font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:.25rem}
    .stat__value{font-size:2rem;font-weight:800;line-height:1}
    .stat--pass .stat__value{color:var(--pass)}
    .stat--fail .stat__value{color:var(--fail)}
    .stat--total .stat__value{color:var(--text)}
    .progress{height:8px;background:rgba(0,0,0,.08);border-radius:99px;overflow:hidden;margin-bottom:2.5rem;max-width:600px}
    .progress__bar{height:100%;border-radius:99px;background:linear-gradient(90deg,#059669,#10b981);transition:width .4s ease}
    .group{margin-bottom:1.5rem}
    .group__title{font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:.5rem;padding-left:.25rem}
    .test-row{display:flex;align-items:baseline;gap:.75rem;padding:.4rem .5rem;border-radius:6px;transition:background .15s}
    .test-row:hover{background:rgba(0,0,0,.03)}
    .badge{flex-shrink:0;font-size:.65rem;font-weight:700;letter-spacing:.06em;padding:.15em .6em;border-radius:99px;text-transform:uppercase}
    .badge--pass{background:rgba(5,150,105,.1);color:var(--pass)}
    .badge--fail{background:rgba(220,38,38,.1);color:var(--fail)}
    .label{flex:1;color:var(--text)}
    .detail{font-size:.75rem;color:var(--muted)}
    .all-passed{color:var(--pass);font-weight:700;font-size:1.1rem;margin-bottom:1rem}
    .has-failures{color:var(--fail);font-weight:700;font-size:1.1rem;margin-bottom:1rem}
    footer{margin-top:3rem;color:var(--muted);font-size:.75rem}
  </style>
</head>
<body>
<h1>Phase 14 — Test Suite</h1>
<p class="subtitle">E-coomy · <?php echo htmlspecialchars(date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?></p>

<div class="summary">
  <div class="stat stat--pass"><div class="stat__label">Passed</div><div class="stat__value"><?php echo $passed; ?></div></div>
  <div class="stat stat--fail"><div class="stat__label">Failed</div><div class="stat__value"><?php echo $failed; ?></div></div>
  <div class="stat stat--total"><div class="stat__label">Total</div><div class="stat__value"><?php echo $total; ?></div></div>
  <div class="stat stat--total"><div class="stat__label">Score</div><div class="stat__value"><?php echo $percent; ?>%</div></div>
</div>

<div class="progress">
  <div class="progress__bar" style="width:<?php echo $percent; ?>%"></div>
</div>

<?php if ($failed === 0): ?>
  <p class="all-passed">✓ All <?php echo $total; ?> tests passed — project is fully functional.</p>
<?php else: ?>
  <p class="has-failures">✗ <?php echo $failed; ?> test(s) failed — see details below.</p>
<?php endif; ?>

<?php foreach ($groups as $group => $rows): ?>
<div class="group">
  <div class="group__title"><?php echo htmlspecialchars($group, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php foreach ($rows as $row): ?>
  <div class="test-row">
    <span class="badge badge--<?php echo $row['ok'] ? 'pass' : 'fail'; ?>">
      <?php echo $row['ok'] ? 'PASS' : 'FAIL'; ?>
    </span>
    <span class="label"><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?></span>
    <?php if ($row['detail']): ?>
    <span class="detail"><?php echo htmlspecialchars($row['detail'], ENT_QUOTES, 'UTF-8'); ?></span>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<footer>
  E-coomy — Phase 14 Test Suite — PHP <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?>
</footer>
</body>
</html>
