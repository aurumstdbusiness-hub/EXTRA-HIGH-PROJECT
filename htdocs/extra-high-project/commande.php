<?php
/**
 * commande.php — Order handler  (Phase 10 + Phase 11)
 *
 * Flow:
 *   1.  Start session — redirect unauthenticated visitors to login
 *   2.  Apply HTTP security headers
 *   3.  Retrieve order fields from $_POST
 *   4.  Server-side validation :
 *         - Required fields
 *         - Whitelist checks  (livraison / taille / paiement)
 *         - Range check       (quantite 1-99)
 *         - Length limits     (prevent DB-truncation and DoS)
 *   5.  INSERT into Commande_produit via prepared statement
 *         (SQL-injection protection — no raw user data in the query)
 *   6.  Show order confirmation (or error list)
 */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/db_mysqli.php';

apply_security_headers();

/* ── STEP 1 — Auth guard ─────────────────────────────────────────────────────
   Only logged-in users may place orders.
   Visitors are redirected to the login page — no order data is processed.
────────────────────────────────────────────────────────────────────────────── */
if (!is_logged_in()) {
    header('Location: pages/login.html');
    exit;
}

$id_client = (int) $_SESSION['id_client'];   // trusted — comes from validated session

/* ── helper : HTML-safe output ───────────────────────────────────────────── */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* ── Allowed values (whitelist — Phase 11) ───────────────────────────────── */
const LIVRAISONS = ['standard', 'express', 'pickup'];
const TAILLES    = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
const PAIEMENTS  = ['cod', 'ccp', 'baridimob'];

/* ── initial state ───────────────────────────────────────────────────────── */
$method        = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$errors        = [];
$success       = false;
$id_commande   = null;
$product_name  = '';
$customer_name = '';

if ($method === 'POST') {

    /* ══════════════════════════════════════════════════════════════════════
       STEP 3 — Retrieve POST data
       trim() removes accidental whitespace; cast ensures correct types.
    ════════════════════════════════════════════════════════════════════════ */
    $product_name  = trim((string) ($_POST['product_name']    ?? ''));
    $product_ref   = trim((string) ($_POST['product_ref']     ?? ''));
    $customer_name = trim((string) ($_POST['customer_name']   ?? ''));
    $email         = trim((string) ($_POST['email']           ?? ''));
    $phone         = trim((string) ($_POST['phone']           ?? ''));
    $adresse       = trim((string) ($_POST['address']         ?? ''));
    $wilaya        = trim((string) ($_POST['city']            ?? ''));
    $code_postal   = trim((string) ($_POST['zip']             ?? ''));
    $livraison     = trim((string) ($_POST['delivery_method'] ?? ''));
    $quantite      = (int) ($_POST['quantity'] ?? 0);
    $taille        = trim((string) ($_POST['size']            ?? ''));
    $couleur       = trim((string) ($_POST['color']           ?? ''));
    $paiement      = trim((string) ($_POST['payment']         ?? 'cod'));
    $notes         = trim((string) ($_POST['notes']           ?? ''));

    /* ══════════════════════════════════════════════════════════════════════
       STEP 4 — Validation
       a) Required fields
       b) Whitelist checks  (no arbitrary enum values reach the DB)
       c) Length limits     (Phase 11 — match VARCHAR sizes in setup.sql)
    ════════════════════════════════════════════════════════════════════════ */

    // a) Required fields
    if ($product_name  === '')                      $errors[] = 'Produit manquant.';
    if ($customer_name === '')                      $errors[] = 'Le nom complet est obligatoire.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse e-mail invalide.';
    if ($phone   === '')                            $errors[] = 'Le numéro de téléphone est obligatoire.';
    if ($adresse === '')                            $errors[] = "L'adresse de livraison est obligatoire.";
    if ($wilaya  === '')                            $errors[] = 'La wilaya est obligatoire.';

    // b) Whitelist checks
    if (!in_array($livraison, LIVRAISONS, true))    $errors[] = 'Mode de livraison invalide.';
    if ($quantite < 1 || $quantite > 99)            $errors[] = 'Quantité invalide (1–99).';
    if (!in_array($taille, TAILLES, true))          $errors[] = 'Taille invalide.';
    if (!in_array($paiement, PAIEMENTS, true))      $errors[] = 'Mode de paiement invalide.';

    // c) Length limits (Phase 11) — match VARCHAR sizes defined in setup.sql
    if (mb_strlen($product_name)  > 120) $errors[] = 'Nom du produit trop long (max 120 car.).';
    if (mb_strlen($customer_name) > 160) $errors[] = 'Nom trop long (max 160 car.).';
    if (mb_strlen($email)         > 180) $errors[] = 'E-mail trop long (max 180 car.).';
    if (mb_strlen($phone)         >  20) $errors[] = 'Numéro de téléphone trop long (max 20 car.).';
    if (mb_strlen($adresse)       > 255) $errors[] = 'Adresse trop longue (max 255 car.).';
    if (mb_strlen($wilaya)        >  80) $errors[] = 'Wilaya trop longue (max 80 car.).';

    /* ══════════════════════════════════════════════════════════════════════
       STEP 5 — Database INSERT
       Prepared statement with bind_param — user data is NEVER interpolated
       into the query string (SQL injection protection).
       Nullable columns receive null (not an empty string) when empty.
    ════════════════════════════════════════════════════════════════════════ */
    if (empty($errors)) {
        try {
            $conn = mysqli_db();

            // Map nullable columns: store NULL instead of empty string
            $code_postal_db = $code_postal !== '' ? $code_postal : null;
            $couleur_db     = $couleur     !== '' ? $couleur     : null;
            $notes_db       = $notes       !== '' ? $notes       : null;

            // 15 parameters — type string: i=INT, s=STRING
            //  1  id_client      i
            //  2  product_name   s
            //  3  product_ref    s
            //  4  customer_name  s
            //  5  email          s
            //  6  phone          s
            //  7  adresse        s
            //  8  wilaya         s
            //  9  code_postal    s  (nullable)
            // 10  livraison      s
            // 11  quantite       i
            // 12  taille         s
            // 13  couleur        s  (nullable)
            // 14  paiement       s
            // 15  notes          s  (nullable)
            $stmt = mysqli_prepare($conn,
                'INSERT INTO Commande_produit
                   (id_client, product_name, product_ref, customer_name, email,
                    phone, adresse, wilaya, code_postal, livraison, quantite,
                    taille, couleur, paiement, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if (!$stmt) {
                throw new RuntimeException(mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'isssssssssissss',
                $id_client,
                $product_name, $product_ref, $customer_name, $email,
                $phone, $adresse, $wilaya, $code_postal_db,
                $livraison, $quantite,
                $taille, $couleur_db, $paiement, $notes_db
            );

            mysqli_stmt_execute($stmt);
            $id_commande = (int) mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            $success = true;

        } catch (RuntimeException $ex) {
            $errors[] = 'Erreur base de données. Veuillez réessayer.';
            error_log('commande.php mysqli: ' . $ex->getMessage());
        }
    }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-coomy | Confirmation commande</title>
<!-- fonts-preconnect -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <main class="auth-shell">
    <section class="auth-card auth-card--wide">
      <header class="auth-card__header">
        <a class="brand" href="index.html">E-coomy</a>
        <h1>Confirmation de commande</h1>
      </header>

      <?php if ($method !== 'POST'): ?>
        <p class="muted">Accédez à une fiche produit pour passer commande.</p>

      <?php elseif (!empty($errors)): ?>
        <div class="status status-error" role="alert">
          <?php foreach ($errors as $err): ?>
            <p><?php echo e($err); ?></p>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <!-- STEP 6 — Order confirmation -->
        <div class="status status-success" role="status">
          <p><strong>Commande #<?php echo e((string) $id_commande); ?> enregistrée&nbsp;!</strong></p>
          <p>Produit&nbsp;: <?php echo e($product_name); ?></p>
          <p>Client&nbsp;&nbsp;: <?php echo e($customer_name); ?></p>
          <p>Nous vous contacterons bientôt pour confirmer la livraison.</p>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
        <p class="auth-link">
          <a href="javascript:history.back()">← Retour au formulaire</a>
        </p>
      <?php else: ?>
        <p class="auth-link"><a href="index.html">← Continuer mes achats</a></p>
      <?php endif; ?>

    </section>
  </main>
</body>
</html>
