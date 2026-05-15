<?php
/**
 * inscription.php — Registration handler  (Phase 8)
 *
 * Flow:
 *   1. Only process POST requests
 *   2. Retrieve and sanitise $_POST data
 *   3. Server-side validation
 *   4. Connect to MySQL with mysqli_connect()
 *   5. Check email uniqueness (prepared SELECT)
 *   6. Hash the password with password_hash()
 *   7. INSERT new client row (prepared INSERT)
 *   8. Render success or error feedback
 */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db_mysqli.php';

apply_security_headers();

// Already logged in — skip registration page
if (is_logged_in()) {
    header('Location: index.html');
    exit;
}

/* ── helper : HTML-safe output ───────────────────────────────────────────── */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* ── initial state ───────────────────────────────────────────────────────── */
$errors       = [];
$success      = false;
$id_client    = null;
$prenom       = '';

/* ── only run logic on POST ──────────────────────────────────────────────── */
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {

    /* ══════════════════════════════════════════════════════════════════════
       STEP 1 — Retrieve POST data
       Each value is trimmed; password fields are NOT trimmed (spaces matter)
    ════════════════════════════════════════════════════════════════════════ */
    $prenom           = trim((string) ($_POST['prenom']           ?? ''));
    $nom              = trim((string) ($_POST['nom']              ?? ''));
    $email            = trim((string) ($_POST['email']            ?? ''));
    $phone            = trim((string) ($_POST['phone']            ?? ''));
    $date_naissance   = trim((string) ($_POST['date_naissance']   ?? ''));
    $adresse          = trim((string) ($_POST['address']          ?? ''));
    $wilaya           = trim((string) ($_POST['city']             ?? ''));
    $code_postal      = trim((string) ($_POST['zip']              ?? ''));
    $password         = (string)      ($_POST['password']         ?? '');
    $password_confirm = (string)      ($_POST['password_confirm'] ?? '');

    /* ══════════════════════════════════════════════════════════════════════
       STEP 2 — Server-side validation
       Never rely on client-side validation alone.
    ════════════════════════════════════════════════════════════════════════ */

    // Required text fields
    if ($prenom === '') $errors[] = 'Le prénom est obligatoire.';
    if ($nom    === '') $errors[] = 'Le nom est obligatoire.';

    // Email format
    if ($email === '') {
        $errors[] = "L'adresse e-mail est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }

    // Phone — optional but must match Algerian pattern when provided
    if ($phone !== '' && !preg_match('/^(\+213|0)[5-7]\d{8}$/', preg_replace('/[\s\-().]/', '', $phone))) {
        $errors[] = 'Numéro de téléphone invalide. Exemple : 05XX XXX XXX';
    }

    // Date of birth & age gate (≥ 16 years)
    if ($date_naissance === '') {
        $errors[] = 'La date de naissance est obligatoire.';
    } else {
        $dob = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if ($dob === false) {
            $errors[] = 'Date de naissance invalide.';
        } else {
            $age = (int) (new DateTime())->diff($dob)->y;
            if ($age < 16)  $errors[] = 'Vous devez avoir au moins 16 ans pour vous inscrire.';
            if ($age > 120) $errors[] = 'Date de naissance invalide.';
        }
    }

    // Password strength
    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit comporter au moins 8 caractères.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
    }

    // Password confirmation
    if ($password !== '' && $password_confirm === '') {
        $errors[] = 'Veuillez confirmer votre mot de passe.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    /* ══════════════════════════════════════════════════════════════════════
       STEP 3 — Database operations (only when no validation errors)
    ════════════════════════════════════════════════════════════════════════ */
    if (empty($errors)) {
        try {
            // ── Connect with mysqli_connect() ─────────────────────────────
            $conn = mysqli_db();   // calls mysqli_connect() internally

            /* ── Check email uniqueness (prepared SELECT) ─────────────────
               Using a prepared statement prevents SQL injection.
               bind_param types:  s = string
            ─────────────────────────────────────────────────────────────── */
            $chk = mysqli_prepare($conn,
                'SELECT id_client FROM Client WHERE email = ? LIMIT 1'
            );
            if (!$chk) throw new RuntimeException(mysqli_error($conn));

            mysqli_stmt_bind_param($chk, 's', $email);
            mysqli_stmt_execute($chk);
            mysqli_stmt_store_result($chk);

            if (mysqli_stmt_num_rows($chk) > 0) {
                $errors[] = 'Cette adresse e-mail est déjà utilisée.';
            } else {

                /* ── Hash the password with bcrypt ────────────────────────
                   password_hash() uses a random salt automatically.
                   NEVER store plain-text passwords.
                ─────────────────────────────────────────────────────────── */
                $hash = password_hash($password, PASSWORD_BCRYPT);

                // Nullable optional fields — store NULL when empty
                $phone_val   = $phone       !== '' ? $phone       : null;
                $adresse_val = $adresse     !== '' ? $adresse     : null;
                $wilaya_val  = $wilaya      !== '' ? $wilaya      : null;
                $zip_val     = $code_postal !== '' ? $code_postal : null;

                /* ── INSERT new client row (prepared statement) ────────────
                   bind_param types:
                     s = string
                   Nine placeholders = nine bind variables.
                ─────────────────────────────────────────────────────────── */
                $ins = mysqli_prepare($conn, '
                    INSERT INTO Client
                      (prenom, nom, email, phone, date_naissance,
                       adresse, wilaya, code_postal, mot_de_passe)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                if (!$ins) throw new RuntimeException(mysqli_error($conn));

                mysqli_stmt_bind_param(
                    $ins, 'sssssssss',
                    $prenom,
                    $nom,
                    $email,
                    $phone_val,
                    $date_naissance,
                    $adresse_val,
                    $wilaya_val,
                    $zip_val,
                    $hash
                );

                if (!mysqli_stmt_execute($ins)) {
                    throw new RuntimeException(mysqli_stmt_error($ins));
                }

                // Retrieve the auto-incremented primary key
                $id_client = (int) mysqli_insert_id($conn);
                $success   = true;

                // Auto-login: regenerate session then store identity
                session_regenerate_id(true);
                $_SESSION['id_client']    = $id_client;
                $_SESSION['prenom']       = $prenom;
                $_SESSION['nom']          = $nom;
                $_SESSION['email']        = $email;
                $_SESSION['logged_in_at'] = time();

                mysqli_stmt_close($ins);
            }

            mysqli_stmt_close($chk);

        } catch (RuntimeException $ex) {
            $errors[] = 'Erreur base de données. Veuillez réessayer.';
            error_log('inscription.php mysqli: ' . $ex->getMessage());
        }
    }
}
// End of PHP logic — HTML output follows
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-coomy | Inscription</title>
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
        <h1>Inscription</h1>
      </header>

      <?php if ($method !== 'POST'): ?>
        <!-- No form submission yet -->
        <p class="muted">Utilisez le formulaire d'inscription pour créer votre compte.</p>

      <?php elseif (!empty($errors)): ?>
        <!-- Validation or database errors -->
        <div class="status status-error" role="alert" aria-live="assertive">
          <strong>Inscription impossible :</strong>
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

      <?php else: ?>
        <!-- Success -->
        <div class="status status-success" role="status" aria-live="polite">
          <p>
            <strong>Compte créé avec succès&nbsp;!</strong>
          </p>
          <p>
            Bienvenue,&nbsp;<strong><?php echo e($prenom); ?></strong>&nbsp;!<br>
            Votre compte #<?php echo e((string) $id_client); ?> est actif.
          </p>
          <p>Vous pouvez maintenant vous connecter.</p>
        </div>

      <?php endif; ?>

      <?php if (!$success): ?>
        <p class="auth-link">
          <a href="pages/inscription.html">← Retour au formulaire</a>
        </p>
      <?php else: ?>
        <p class="auth-link">
          <a href="pages/login.html" class="btn btn-primary">Se connecter →</a>
        </p>
      <?php endif; ?>

    </section>
  </main>
</body>
</html>
