E-COOMY — Boutique E-commerce Algérienne
=====================================================
Projet final — Développement Web  |  Phases 1-15
Auteur : [Votre nom]   |   Date : Mai 2026
Technologie : HTML5 / CSS3 / JavaScript / PHP 8 / MySQL (XAMPP)


═══════════════════════════════════════════════════════════
 APERÇU DU PROJET
═══════════════════════════════════════════════════════════

E-coomy est une boutique e-commerce complète de vêtements
tendance destinée au marché algérien.

Fonctionnalités :
  ✓ Catalogue de 20 produits avec descriptions complètes
  ✓ Inscription sécurisée (bcrypt, validation temps réel)
  ✓ Connexion avec sessions PHP sécurisées
  ✓ Passage de commande (réservé aux utilisateurs connectés)
  ✓ Base de données MySQL : Client + Commande_produit
  ✓ Design responsive (7 breakpoints, dark mode, a11y)
  ✓ Animations et micro-interactions premium


═══════════════════════════════════════════════════════════
 STRUCTURE DES FICHIERS
═══════════════════════════════════════════════════════════

extra-high-project/
│
├── index.html                   Page d'accueil + catalogue
├── login.html                   Formulaire de connexion (frontend)
├── inscription.html             Formulaire d'inscription (frontend)
│
├── login.php                    Authentification (Phase 9)
├── inscription.php              Inscription (Phase 8)
├── commande.php                 Commandes (Phase 10)
├── logout.php                   Déconnexion (Phase 9)
│
├── commande_produit1-20.html    20 fiches produit (Phase 12)
│
├── style.css                    Feuille de style unique (2092 lignes)
├── validation.js                Logique JS + animations (652 lignes)
│
├── config/
│   ├── db_mysqli.php            Connexion MySQL (singleton)
│   ├── session.php              Bootstrap session + helpers
│   ├── security.php             En-têtes HTTP de sécurité
│   ├── db.php                   Connexion PDO (Phase 7, référence)
│   └── .htaccess                Interdit l'accès HTTP direct à config/
│
├── database/
│   ├── setup.sql                Schéma complet + 2 tables
│   └── XAMPP_SETUP.txt          Guide d'installation pas-à-pas
│
├── assets/
│   ├── images/                  placeholder.svg + produit1-20.jpg
│   ├── icons/
│   ├── css/
│   └── js/
│
└── tests/
    ├── test_suite.php           Suite de tests PHP (Phase 14)
    └── test_validation.html     Tests JS offline (Phase 14)


═══════════════════════════════════════════════════════════
 INSTALLATION RAPIDE (5 minutes)
═══════════════════════════════════════════════════════════

1. Installer XAMPP (Apache + MySQL + PHP 8)
     https://www.apachefriends.org/download.html

2. Copier ce dossier dans :
     C:\xampp\htdocs\extra-high-project\

3. Démarrer Apache + MySQL dans XAMPP Control Panel

4. Créer la base de données :
     http://localhost/phpmyadmin
     → Importer → database/setup.sql → Exécuter

5. Ouvrir le site :
     http://localhost/extra-high-project/

   Guide détaillé : database/XAMPP_SETUP.txt


═══════════════════════════════════════════════════════════
 FLUX UTILISATEUR
═══════════════════════════════════════════════════════════

INSCRIPTION :
  /inscription.html → POST → inscription.php
  → Validation serveur → INSERT Client → session auto-login → /index.html

CONNEXION :
  /login.html → POST → login.php
  → SELECT Client → password_verify() → $_SESSION → /index.html

COMMANDE :
  /commande_produitN.html → POST → commande.php
  → Auth guard (is_logged_in) → Validation → INSERT Commande_produit → confirmation

DÉCONNEXION :
  /logout.php → session_destroy() → /index.html


═══════════════════════════════════════════════════════════
 SÉCURITÉ IMPLÉMENTÉE  (Phase 11)
═══════════════════════════════════════════════════════════

  ✓ Prepared statements + bind_param → protection SQL injection
  ✓ password_hash(PASSWORD_BCRYPT) / password_verify()
  ✓ htmlspecialchars() sur toutes les sorties → protection XSS
  ✓ filter_var(FILTER_VALIDATE_EMAIL) sur tous les emails
  ✓ Listes blanches : LIVRAISONS / TAILLES / PAIEMENTS
  ✓ Limites mb_strlen() → correspondance avec VARCHAR du schéma
  ✓ session_regenerate_id(true) → prévention fixation de session
  ✓ Cookie httponly + samesite=Lax + strict_mode
  ✓ Message d'erreur générique + usleep(300ms) → anti-énumération
  ✓ En-têtes HTTP : X-Content-Type-Options, X-Frame-Options,
                    Referrer-Policy, Cache-Control: no-store
  ✓ config/.htaccess → accès HTTP direct aux fichiers de config bloqué


═══════════════════════════════════════════════════════════
 BASE DE DONNÉES
═══════════════════════════════════════════════════════════

  Base : Base_Client  |  Moteur : InnoDB  |  Encodage : utf8mb4_unicode_ci

  Table Client :
    id_client, prenom, nom, email (UNIQUE), phone, date_naissance,
    adresse, wilaya, code_postal, mot_de_passe (bcrypt), created_at

  Table Commande_produit :
    id_commande, id_client (FK nullable), product_name, product_ref,
    customer_name, email, phone, adresse, wilaya, code_postal,
    livraison ENUM, quantite TINYINT, taille ENUM, couleur,
    paiement ENUM, notes, statut ENUM, created_at


═══════════════════════════════════════════════════════════
 CATALOGUE DES 20 PRODUITS
═══════════════════════════════════════════════════════════

  EHP-001  T-shirt Premium Oversize       T-shirts    2 200 DA
  EHP-002  Jean Slim Fit Stretch          Pantalons   4 800 DA
  EHP-003  Hoodie Urbain Premium          Sweats      3 500 DA
  EHP-004  Veste Bomber Street            Vestes      6 500 DA
  EHP-005  Short Jogger Coton             Shorts      2 800 DA
  EHP-006  Polo Classique Piqué           Polos       3 200 DA
  EHP-007  Sweat Capuche Zippé            Sweats      4 200 DA
  EHP-008  Ensemble Jogging Set           Ensembles   5 500 DA
  EHP-009  Chemise Lin Estivale           Chemises    3 800 DA
  EHP-010  Bermuda Cargo Style            Shorts      3 100 DA
  EHP-011  T-shirt Graphique Print        T-shirts    2 500 DA
  EHP-012  Manteau Long Premium           Manteaux    8 900 DA
  EHP-013  Pantalon Chino Slim            Pantalons   4 300 DA
  EHP-014  Débardeur Sport Dry-Fit        T-shirts    1 800 DA
  EHP-015  Veste Windbreaker Légère       Vestes      5 800 DA
  EHP-016  Pull Tricot Oversize           Pulls       4 600 DA
  EHP-017  Set Sport Matching             Ensembles   6 200 DA
  EHP-018  Chemise Flanelle Carreaux      Chemises    3 900 DA
  EHP-019  Jean Wide Leg Baggy            Pantalons   5 100 DA
  EHP-020  Hoodie Brodé Signature         Sweats      4 900 DA


═══════════════════════════════════════════════════════════
 TESTS (Phase 14)
═══════════════════════════════════════════════════════════

  PHP / DB test suite :
    http://localhost/extra-high-project/tests/test_suite.php

  JS validation tests (offline) :
    Ouvrir directement : tests/test_validation.html


═══════════════════════════════════════════════════════════
 PHASES DU PROJET
═══════════════════════════════════════════════════════════

  Phase  1  Structure de base, XAMPP setup
  Phase  2  23 pages HTML
  Phase  3  Design system CSS (variables, typographie, composants)
  Phase  4  Responsive 7 breakpoints
  Phase  5  Animations et micro-interactions
  Phase  6  Validation JS côté client
  Phase  7  Base de données MySQL, setup.sql, PDO
  Phase  8  Inscription PHP (mysqli)
  Phase  9  Authentification PHP + sessions
  Phase 10  Système de commandes (auth requis)
  Phase 11  Sécurité avancée (headers, sanitization, limits)
  Phase 12  Contenu réaliste — 20 produits
  Phase 13  Finitions premium (dark mode, a11y, typographie)
  Phase 14  Suite de tests automatisés
  Phase 15  Déploiement et présentation
