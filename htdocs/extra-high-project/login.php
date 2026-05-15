<?php
/**
 * login.php — Authentication handler  (Phase 9)
 *
 * Flow:
 *   1. Start / resume session
 *   2. Redirect immediately if already logged in
 *   3. Only process POST requests
 *   4. Retrieve $_POST data
 *   5. Server-side input validation
 *   6. Connect to MySQL with mysqli_connect()
 *   7. SELECT client row by email (prepared statement)
 *   8. Verify password with password_verify()
 *   9. Regenerate session ID (prevents session fixation)
 *  10. Store user data in $_SESSION
 *  11. Redirect authenticated user to homepage
 *  12. Render error feedback on failure
 */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db_mysqli.php';

apply_security_headers();

/* ── helper : HTML-safe output ───────────────────────────────────────────── */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* ── STEP 1-2 : Session already active → skip login page ────────────────── */
if (is_logged_in()) {
    header('Location: index.html');
    exit;
}

/* ── initial state ───────────────────────────────────────────────────────── */
$errors = [];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {

    /* ══════════════════════════════════════════════════════════════════════
       STEP 3-4 — Retrieve POST data
    ════════════════════════════════════════════════════════════════════════ */
    $email    = trim((string) ($_POST['email']    ?? ''));
    $password =      (string) ($_POST['password'] ?? '');

    /* ══════════════════════════════════════════════════════════════════════
       STEP 5 — Server-side validation
       Keep messages generic to avoid revealing which field was wrong
       (user-enumeration defence).
    ════════════════════════════════════════════════════════════════════════ */
    // Phase 11 — length limits prevent oversized input reaching bcrypt/DB
    if (mb_strlen($email)    > 180)  $errors[] = 'E-mail trop long.';
    if (mb_strlen($password) > 1000) $errors[] = 'Mot de passe trop long.';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }
    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    }

    if (empty($errors)) {

        try {
            /* ── STEP 6 — Connect with mysqli_connect() ───────────────── */
            $conn = mysqli_db();   // internally: mysqli_connect(host,user,pass,db)

            /* ── STEP 7 — SELECT row by email (prepared statement) ────────
               Prepared statements prevent SQL injection.
               Binding type: s = string
            ─────────────────────────────────────────────────────────────── */
            $stmt = mysqli_prepare($conn,
                'SELECT id_client, prenom, nom, mot_de_passe
                   FROM Client
                  WHERE email = ?
                  LIMIT 1'
            );

            if (!$stmt) {
                throw new RuntimeException(mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);

            // Bind result columns to variables
            mysqli_stmt_bind_result($stmt, $id_client, $prenom, $nom, $hash);
            $found = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            /* ── STEP 8 — Verify password ─────────────────────────────────
               password_verify() compares plain-text input against the
               bcrypt hash stored in the database.
               The same generic error covers both "email not found" and
               "wrong password" — prevents user-enumeration.
            ─────────────────────────────────────────────────────────────── */
            if (!$found || !password_verify($password, (string) $hash)) {
                // Artificial tiny delay reduces brute-force effectiveness
                usleep(300_000); // 300 ms
                $errors[] = 'E-mail ou mot de passe incorrect.';

            } else {

                /* ── STEP 9 — Regenerate session ID ──────────────────────
                   session_regenerate_id(true) assigns a new session ID and
                   deletes the old session file — prevents session fixation.
                ─────────────────────────────────────────────────────────── */
                session_regenerate_id(true);

                /* ── STEP 10 — Store user data in $_SESSION ───────────────
                   Only store what is needed — never store the password hash.
                ─────────────────────────────────────────────────────────── */
                $_SESSION['id_client']    = (int)    $id_client;
                $_SESSION['prenom']       = (string) $prenom;
                $_SESSION['nom']          = (string) $nom;
                $_SESSION['email']        = $email;
                $_SESSION['logged_in_at'] = time();

                /* ── STEP 11 — Redirect authenticated user ────────────────
                   header() + exit stops any further HTML output.
                   PRG pattern: redirect after POST prevents double-submit.
                ─────────────────────────────────────────────────────────── */
                header('Location: index.html');
                exit;
            }

        } catch (RuntimeException $ex) {
            $errors[] = 'Erreur base de données. Veuillez réessayer.';
            error_log('login.php mysqli: ' . $ex->getMessage());
        }
    }
}
/* ════════════════════════════════════════════════════════════════════════════
   STEP 12 — Render HTML (only reached on GET or failed POST)
════════════════════════════════════════════════════════════════════════════ */
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-coomy | Connexion</title>
<!-- fonts-preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="auth-shell">
    <section class="auth-card">

      <header class="auth-card__header">
        <a class="brand" href="index.html">E-coomy</a>
        <h1>Connexion</h1>
      </header>

      <?php if (!empty($errors)): ?>
        <!-- STEP 12 : invalid credentials or db error -->
        <div class="status status-error" role="alert" aria-live="assertive">
          <?php foreach ($errors as $err): ?>
            <p><?php echo e($err); ?></p>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted">
          Connectez-vous pour accéder à votre compte.
        </p>
      <?php endif; ?>

      <p class="auth-link">
        <a href="pages/login.html">← Retour au formulaire</a>
      </p>

      <p class="auth-link">
        Pas encore de compte&nbsp;?
        <a href="pages/inscription.html">Créer un compte</a>
      </p>

    </section>
  </main>
</body>
</html>
