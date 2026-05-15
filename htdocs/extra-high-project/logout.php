<?php
/**
 * logout.php — Session destruction & redirect
 *
 * Link to this page from any "Déconnexion" button.
 * Clears $_SESSION, destroys the session file, expires the cookie,
 * then redirects to the homepage.
 */
declare(strict_types=1);
require_once __DIR__ . '/config/session.php';

logout();   // defined in config/session.php

header('Location: index.html');
exit;
