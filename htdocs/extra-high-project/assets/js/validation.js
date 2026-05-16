/* ============================================================
   E-COOMY — validation.js  (Phase 6)
   validation_login · validation_infos · validation_order
   Password strength · Real-time checks · Error/success states
   Scroll-reveal · Toast · Ripple · Mobile nav · Lazy images
   Theme toggle (light / dark)
   ============================================================ */
'use strict';

/* ── Theme: apply BEFORE DOMContentLoaded to avoid flash ──────────────── */
(function () {
  const saved = localStorage.getItem('ecoomy-theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', theme);
})();

document.addEventListener('DOMContentLoaded', () => {

  // ── 1. Page entrance ──────────────────────────────────────────────────
  requestAnimationFrame(() => document.body.classList.add('page-loaded'));

  // ── 1b. Theme toggle — inject button into navbar ──────────────────────
  (function initThemeToggle() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    // SVG icons — moon (crescent) and sun
    const moonSVG = `<svg class="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
    const sunSVG  = `<svg class="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;

    // Create button
    const btn = document.createElement('button');
    btn.className  = 'theme-toggle';
    btn.type       = 'button';
    btn.setAttribute('aria-label', 'Changer le thème (clair / sombre)');
    btn.innerHTML  = moonSVG + sunSVG;

    // Insert before .nav-actions (so it appears next to the auth buttons)
    const navActions = navbar.querySelector('.nav-actions');
    if (navActions) {
      navbar.insertBefore(btn, navActions);
    } else {
      navbar.appendChild(btn);
    }

    // Toggle handler
    btn.addEventListener('click', () => {
      const html = document.documentElement;
      const current = html.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';

      html.setAttribute('data-theme', next);
      localStorage.setItem('ecoomy-theme', next);

      // Animate icon rotation
      btn.classList.remove('is-animating');
      void btn.offsetWidth; // force reflow
      btn.classList.add('is-animating');
      btn.addEventListener('transitionend', () => {
        btn.classList.remove('is-animating');
      }, { once: true });

      // Fallback: remove animation class after timeout
      setTimeout(() => btn.classList.remove('is-animating'), 600);
    });
  })();

  // ── 2. Smooth anchor scrolling ────────────────────────────────────────
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', e => {
      const id     = anchor.getAttribute('href');
      const target = id.length > 1 ? document.querySelector(id) : null;
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // ── 3. Scroll-reveal (IntersectionObserver) ───────────────────────────
  if ('IntersectionObserver' in window) {
    const revealObs = new IntersectionObserver(
      entries => entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          revealObs.unobserve(entry.target);
        }
      }),
      { threshold: 0.10, rootMargin: '0px 0px -48px 0px' }
    );
    // Add .reveal to non-card elements (product <li> items already have it in HTML)
    document.querySelectorAll(
      '.feature-card, .section-heading, .about-copy, .about-figure'
    ).forEach(el => el.classList.add('reveal'));
    // Observe ALL .reveal elements — including <li class="reveal"> product wrappers
    document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));
    document.querySelectorAll('.card-grid, .features-grid')
      .forEach(g => g.classList.add('stagger'));
  }

  // ── 4. Mobile nav ─────────────────────────────────────────────────────
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks  = document.querySelector('.nav-links');
  if (navToggle && navLinks) {
    const closeNav = () => {
      navToggle.setAttribute('aria-expanded', 'false');
      navLinks.classList.remove('nav-links--open');
    };
    navToggle.addEventListener('click', () => {
      const expanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!expanded));
      navLinks.classList.toggle('nav-links--open', !expanded);
    });
    document.addEventListener('click', e => {
      if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) closeNav();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && navLinks.classList.contains('nav-links--open')) {
        closeNav();
        navToggle.focus();
      }
    });
    navLinks.querySelectorAll('a').forEach(a => a.addEventListener('click', () => closeNav()));
  }

  // ── 5. Password show / hide ───────────────────────────────────────────
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.input-wrapper')?.querySelector('input');
      if (!input) return;
      const hidden = input.type === 'password';
      input.type = hidden ? 'text' : 'password';
      btn.setAttribute('aria-pressed', String(hidden));
      const icon = btn.querySelector('span');
      if (icon) icon.textContent = hidden ? '\uD83D\uDE48' : '\uD83D\uDC41';
    });
  });

  // ── 6. Button ripple ──────────────────────────────────────────────────
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const rect   = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.className  = 'ripple';
      ripple.style.left = `${e.clientX - rect.left  - 5}px`;
      ripple.style.top  = `${e.clientY - rect.top   - 5}px`;
      this.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
    });
  });

  // ── 7. Product card hover labels ──────────────────────────────────────
  document.querySelectorAll('.product-card__figure').forEach(fig => {
    if (fig.querySelector('.product-card__hover-label')) return;
    const label       = document.createElement('span');
    label.className   = 'product-card__hover-label';
    label.textContent = 'Commander \u2192';
    fig.appendChild(label);
  });

  // ── 8. Char counters ──────────────────────────────────────────────────
  document.querySelectorAll('textarea[maxlength]').forEach(ta => {
    const max     = Number(ta.getAttribute('maxlength'));
    const counter = document.createElement('div');
    counter.className   = 'char-counter';
    counter.textContent = `0 / ${max}`;
    ta.insertAdjacentElement('afterend', counter);
    ta.addEventListener('input', () => {
      const len = ta.value.length;
      counter.textContent = `${len} / ${max}`;
      counter.classList.toggle('is-near-limit', len >= max * 0.8 && len < max);
      counter.classList.toggle('is-at-limit',   len >= max);
    });
  });

  // ── 9. Toast region ───────────────────────────────────────────────────
  const toastRegion = document.createElement('div');
  toastRegion.className = 'toast-region';
  toastRegion.setAttribute('role', 'status');
  toastRegion.setAttribute('aria-live', 'polite');
  toastRegion.setAttribute('aria-atomic', 'false');
  document.body.appendChild(toastRegion);

  // ── 10. Lazy image fade-in ────────────────────────────────────────────
  if ('IntersectionObserver' in window) {
    const imgObs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.style.opacity = '1';
        imgObs.unobserve(entry.target);
      });
    }, { rootMargin: '300px' });
    document.querySelectorAll('img').forEach(img => {
      if (img.complete) return;
      img.style.opacity    = '0';
      img.style.transition = 'opacity 400ms ease';
      imgObs.observe(img);
      img.addEventListener('load',  () => { img.style.opacity = '1'; }, { once: true });
      img.addEventListener('error', () => { img.style.opacity = '1'; }, { once: true });
    });
  }

  // ── 11. Active nav link highlight ─────────────────────────────────────
  const currentFile = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    const href = (a.getAttribute('href') || '').split('#')[0];
    if (href && href === currentFile) {
      a.setAttribute('aria-current', 'page');
      a.style.color      = 'var(--c-primary)';
      a.style.background = 'var(--c-primary-pale)';
    }
  });

  // ══════════════════════════════════════════════════════════════════════
  //  FORM WIRING — attach the right validator to whichever form is present
  // ══════════════════════════════════════════════════════════════════════
  const loginForm  = document.querySelector('[data-form-type="login"]');
  const signupForm = document.querySelector('[data-form-type="signup"]');
  const orderForm  = document.querySelector('[data-form-type="order"]');

  if (loginForm)  _wireLogin(loginForm);
  if (signupForm) _wireSignup(signupForm);
  if (orderForm)  _wireOrder(orderForm);

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  A. VALIDATION_LOGIN
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  function _wireLogin(form) {
    const email = form.querySelector("[name='email']");
    const pw    = form.querySelector("[name='password']");

    _onBlurAndLive(email, () => _chk_email(email));
    _onBlurAndLive(pw,    () => _chk_pw_login(pw));

    form.addEventListener('submit', e => {
      const ok = _chk_email(email) & _chk_pw_login(pw); // bitwise: runs both
      if (!ok) {
        e.preventDefault();
        const first = !_chk_email(email) ? email : pw;
        _shakeField(first);
        first.focus();
        showToast('error', 'Connexion impossible', 'V\u00e9rifiez vos identifiants.');
        return;
      }
      _setLoading(form.querySelector('[type="submit"]'), true);
    });
  }

  /** Public: validation_login(form?) — returns true if the login form is valid */
  function validation_login(form) {
    const f = form || loginForm;
    if (!f) return false;
    const email = f.querySelector("[name='email']");
    const pw    = f.querySelector("[name='password']");
    return Boolean(_chk_email(email) & _chk_pw_login(pw));
  }

  function _chk_email(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val)           return _fail(field, 'L\u2019adresse e-mail est obligatoire.');
    if (!_isEmail(val)) return _fail(field, 'Adresse e-mail invalide.');
    return _pass(field);
  }

  function _chk_pw_login(field) {
    if (!field) return 1;
    const val = field.value;
    if (!val)          return _fail(field, 'Le mot de passe est obligatoire.');
    if (val.length < 8) return _fail(field, 'Minimum 8 caract\u00e8res requis.');
    return _pass(field);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  B. VALIDATION_INFOS  (inscription / signup)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  function _wireSignup(form) {
    const F = {
      prenom:           form.querySelector("[name='prenom']"),
      nom:              form.querySelector("[name='nom']"),
      email:            form.querySelector("[name='email']"),
      phone:            form.querySelector("[name='phone']"),
      date_naissance:   form.querySelector("[name='date_naissance']"),
      password:         form.querySelector("[name='password']"),
      password_confirm: form.querySelector("[name='password_confirm']"),
      terms:            form.querySelector("[name='terms']"),
    };

    // Set date max to today so browser date-picker limits to past dates
    if (F.date_naissance) F.date_naissance.max = new Date().toISOString().split('T')[0];

    // Inject password strength meter after the input-wrapper
    if (F.password) {
      const anchor = F.password.closest('.input-wrapper') || F.password;
      const meter  = document.createElement('div');
      meter.className = 'password-strength';
      meter.setAttribute('aria-live', 'polite');
      meter.innerHTML =
        '<div class="password-strength__track">' +
          '<span></span><span></span><span></span><span></span>' +
        '</div>' +
        '<p class="password-strength__label">Tapez un mot de passe</p>';
      anchor.insertAdjacentElement('afterend', meter);

      F.password.addEventListener('input', () => {
        _updateStrengthMeter(F.password.value, meter);
        // Re-validate live if field already has a state
        if (F.password.closest('.form-group')?.classList.contains('is-invalid'))
          _chk_pw_signup(F.password);
        if (F.password_confirm?.value)
          _chk_confirm(F.password, F.password_confirm);
      });
    }

    // Blur + live correction per field
    _onBlurAndLive(F.prenom,          () => _chk_name(F.prenom,  'pr\u00e9nom'));
    _onBlurAndLive(F.nom,             () => _chk_name(F.nom,     'nom'));
    _onBlurAndLive(F.email,           () => _chk_email(F.email));
    _onBlurAndLive(F.phone,           () => _chk_phone(F.phone));
    _onBlurAndLive(F.date_naissance,  () => _chk_age(F.date_naissance));
    _onBlurAndLive(F.password_confirm,() => _chk_confirm(F.password, F.password_confirm));
    F.password?.addEventListener('blur', () => _chk_pw_signup(F.password));

    form.addEventListener('submit', e => {
      // Run all checks (bitwise & so all run, collecting combined result)
      const ok =
        _chk_name(F.prenom, 'pr\u00e9nom') &
        _chk_name(F.nom, 'nom') &
        _chk_email(F.email) &
        _chk_phone(F.phone) &
        _chk_age(F.date_naissance) &
        _chk_pw_signup(F.password) &
        _chk_confirm(F.password, F.password_confirm) &
        _chk_terms(F.terms);

      if (!ok) {
        e.preventDefault();
        const ordered = [F.prenom, F.nom, F.email, F.phone, F.date_naissance,
                         F.password, F.password_confirm, F.terms];
        const first = ordered.find(f =>
          f && (f.closest('.form-group')?.classList.contains('is-invalid') ||
                f.closest('.checkbox-label')?.classList.contains('is-invalid'))
        );
        if (first) { _shakeField(first); first.focus(); }
        showToast('error', 'Inscription incompl\u00e8te', 'Veuillez corriger les champs indiqu\u00e9s.');
        return;
      }
      _setLoading(form.querySelector('[type="submit"]'), true);
    });
  }

  /** Public: validation_infos(form?) — returns true if the signup form is valid */
  function validation_infos(form) {
    const f = form || signupForm;
    if (!f) return false;
    const g = n => f.querySelector(`[name='${n}']`);
    return Boolean(
      _chk_name(g('prenom'), 'pr\u00e9nom') &
      _chk_name(g('nom'), 'nom') &
      _chk_email(g('email')) &
      _chk_phone(g('phone')) &
      _chk_age(g('date_naissance')) &
      _chk_pw_signup(g('password')) &
      _chk_confirm(g('password'), g('password_confirm')) &
      _chk_terms(g('terms'))
    );
  }

  function _chk_name(field, label) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val)         return _fail(field, `Le ${label} est obligatoire.`);
    if (val.length < 2) return _fail(field, `Le ${label} doit comporter au moins 2 caract\u00e8res.`);
    if (!/^[\p{L}\s'\-]+$/u.test(val)) return _fail(field, `Le ${label} contient des caract\u00e8res invalides.`);
    return _pass(field);
  }

  function _chk_phone(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val) return _pass(field); // optional — skip if empty
    if (!_isAlgerianPhone(val))
      return _fail(field, 'Num\u00e9ro invalide. Exemple\u00a0: 05XX\u00a0XXX\u00a0XXX');
    return _pass(field);
  }

  function _chk_age(field) {
    if (!field) return 1;
    const val = field.value;
    if (!val) return _fail(field, 'La date de naissance est obligatoire.');
    const dob   = new Date(val);
    if (isNaN(dob.getTime())) return _fail(field, 'Date invalide.');
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    if (age < 16)  return _fail(field, 'Vous devez avoir au moins 16 ans.');
    if (age > 120) return _fail(field, 'Date de naissance invalide.');
    return _pass(field);
  }

  function _chk_pw_signup(field) {
    if (!field) return 1;
    const val = field.value;
    if (!val)          return _fail(field, 'Le mot de passe est obligatoire.');
    if (val.length < 8) return _fail(field, 'Minimum 8 caract\u00e8res requis.');
    if (!/[A-Z]/.test(val)) return _fail(field, 'Ajoutez au moins une lettre majuscule.');
    if (!/[0-9]/.test(val)) return _fail(field, 'Ajoutez au moins un chiffre.');
    return _pass(field);
  }

  function _chk_confirm(pwField, cnfField) {
    if (!cnfField) return 1;
    const val = cnfField.value;
    if (!val) return _fail(cnfField, 'Veuillez confirmer votre mot de passe.');
    if (pwField?.value !== val)
      return _fail(cnfField, 'Les mots de passe ne correspondent pas.');
    return _pass(cnfField);
  }

  function _chk_terms(field) {
    if (!field) return 1;
    const label = field.closest('.checkbox-label');
    if (field.checked) {
      if (label) label.classList.remove('is-invalid');
      label?.nextElementSibling?.classList.contains('form-group__error') &&
        label.nextElementSibling.remove();
      return 1;
    }
    if (label) {
      label.classList.add('is-invalid');
      let err = label.nextElementSibling;
      if (!err || !err.classList.contains('form-group__error')) {
        err = document.createElement('p');
        err.className = 'form-group__error';
        label.insertAdjacentElement('afterend', err);
      }
      err.textContent = 'Vous devez accepter les conditions d\u2019utilisation.';
    }
    return 0;
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  C. VALIDATION_ORDER
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  function _wireOrder(form) {
    const F = {
      customer_name:   form.querySelector("[name='customer_name']"),
      email:           form.querySelector("[name='email']"),
      phone:           form.querySelector("[name='phone']"),
      address:         form.querySelector("[name='address']"),
      city:            form.querySelector("[name='city']"),
      delivery_method: form.querySelector("[name='delivery_method']"),
      quantity:        form.querySelector("[name='quantity']"),
      size:            form.querySelector("[name='size']"),
      color:           form.querySelector("[name='color']"),
    };

    // Blur + live handlers
    _onBlurAndLive(F.customer_name, () => _chk_order_name(F.customer_name));
    _onBlurAndLive(F.email,         () => _chk_email(F.email));
    _onBlurAndLive(F.phone,         () => _chk_order_phone(F.phone));
    _onBlurAndLive(F.address,       () => _chk_address(F.address));
    _onBlurAndLive(F.city,          () => _chk_city(F.city));
    F.delivery_method?.addEventListener('change', () => _chk_select(F.delivery_method, 'mode de livraison'));
    F.quantity?.addEventListener('input',  () => _chk_qty(F.quantity));
    F.size?.addEventListener('change',     () => _chk_select(F.size, 'taille'));
    F.color?.addEventListener('change',    () => _chk_color(F.color));

    form.addEventListener('submit', e => {
      const ok =
        _chk_order_name(F.customer_name) &
        _chk_email(F.email) &
        _chk_order_phone(F.phone) &
        _chk_address(F.address) &
        _chk_city(F.city) &
        _chk_select(F.delivery_method, 'mode de livraison') &
        _chk_qty(F.quantity) &
        _chk_select(F.size, 'taille') &
        _chk_color(F.color);

      if (!ok) {
        e.preventDefault();
        const ordered = [F.customer_name, F.email, F.phone, F.address, F.city,
                         F.delivery_method, F.quantity, F.size, F.color];
        const first = ordered.find(f => f?.closest('.form-group')?.classList.contains('is-invalid'));
        if (first) { _shakeField(first); first.focus(); }
        showToast('error', 'Commande incompl\u00e8te', 'Veuillez remplir tous les champs obligatoires.');
        return;
      }
      showToast('success', 'Commande en cours\u2026', 'Validation de votre commande.');
      _setLoading(form.querySelector('[type="submit"]'), true);
    });
  }

  /** Public: validation_order(form?) — returns true if the order form is valid */
  function validation_order(form) {
    const f = form || orderForm;
    if (!f) return false;
    const g = n => f.querySelector(`[name='${n}']`);
    return Boolean(
      _chk_order_name(g('customer_name')) &
      _chk_email(g('email')) &
      _chk_order_phone(g('phone')) &
      _chk_address(g('address')) &
      _chk_city(g('city')) &
      _chk_select(g('delivery_method'), 'mode de livraison') &
      _chk_qty(g('quantity')) &
      _chk_select(g('size'), 'taille') &
      _chk_color(g('color'))
    );
  }

  function _chk_order_name(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val)         return _fail(field, 'Le nom complet est obligatoire.');
    if (val.length < 3) return _fail(field, 'Veuillez saisir votre nom complet.');
    return _pass(field);
  }

  function _chk_order_phone(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val)               return _fail(field, 'Le num\u00e9ro de t\u00e9l\u00e9phone est obligatoire.');
    if (!_isAlgerianPhone(val)) return _fail(field, 'Num\u00e9ro invalide. Exemple\u00a0: 05XX\u00a0XXX\u00a0XXX');
    return _pass(field);
  }

  function _chk_address(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val)         return _fail(field, 'L\u2019adresse de livraison est obligatoire.');
    if (val.length < 5) return _fail(field, 'Veuillez saisir une adresse compl\u00e8te.');
    return _pass(field);
  }

  function _chk_city(field) {
    if (!field) return 1;
    const val = field.value.trim();
    if (!val) return _fail(field, 'La wilaya est obligatoire.');
    return _pass(field);
  }

  function _chk_select(field, label) {
    if (!field) return 1;
    if (!field.value) return _fail(field, `Veuillez choisir un ${label}.`);
    return _pass(field);
  }

  function _chk_qty(field) {
    if (!field) return 1;
    const val = Number(field.value);
    const min = Number(field.min || 1);
    const max = Number(field.max || 99);
    if (field.value === '' || isNaN(val)) return _fail(field, 'La quantit\u00e9 est obligatoire.');
    if (!Number.isInteger(val))           return _fail(field, 'La quantit\u00e9 doit \u00eatre un entier.');
    if (val < min) return _fail(field, `Quantit\u00e9 minimum\u00a0: ${min}.`);
    if (val > max) return _fail(field, `Quantit\u00e9 maximum\u00a0: ${max}.`);
    return _pass(field);
  }

  function _chk_color(field) {
    if (!field) return 1;
    if (field.hasAttribute('required') && !field.value)
      return _fail(field, 'Veuillez s\u00e9lectionner une couleur.');
    return _pass(field);
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  PASSWORD STRENGTH METER
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  function _pwScore(val) {
    if (!val) return 0;
    let s = 0;
    if (val.length >= 8)           s++;
    if (val.length >= 12)          s++;
    if (/[A-Z]/.test(val))         s++;
    if (/[a-z]/.test(val))         s++;
    if (/[0-9]/.test(val))         s++;
    if (/[^A-Za-z0-9]/.test(val))  s++;
    return Math.min(s, 6);
  }

  function _updateStrengthMeter(val, meter) {
    const score  = _pwScore(val);
    const level  = !val ? 0 : score <= 2 ? 1 : score <= 3 ? 2 : score <= 5 ? 3 : 4;
    const labels = ['', 'Faible', 'Moyen', 'Bon', 'Fort'];
    meter.dataset.level = level;
    const lbl = meter.querySelector('.password-strength__label');
    if (lbl) lbl.textContent = val ? labels[level] : 'Tapez un mot de passe';
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  PRIVATE UTILITIES
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  function _isEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
  }

  function _isAlgerianPhone(val) {
    const stripped = val.replace(/[\s\-().]/g, '');
    return /^(?:\+213|0)[5-7]\d{8}$/.test(stripped);
  }

  /** Mark a field as valid — returns 1 (truthy integer for bitwise ops) */
  function _pass(field) {
    const group = field.closest('.form-group');
    if (!group) return 1;
    group.classList.remove('is-invalid');
    group.classList.add('is-valid');
    group.querySelector('.form-group__error')?.remove();
    return 1;
  }

  /** Mark a field as invalid — returns 0 (falsy integer for bitwise ops) */
  function _fail(field, message) {
    const group = field.closest('.form-group');
    if (!group) return 0;
    group.classList.remove('is-valid');
    group.classList.add('is-invalid');
    let err = group.querySelector('.form-group__error');
    if (!err) {
      err = document.createElement('p');
      err.className = 'form-group__error';
      const anchor = group.querySelector('.input-wrapper') || field;
      anchor.insertAdjacentElement('afterend', err);
    }
    err.textContent = message;
    return 0;
  }

  /** Attach blur-validate and live-re-validate (input while already invalid) */
  function _onBlurAndLive(field, fn) {
    if (!field) return;
    field.addEventListener('blur', fn);
    field.addEventListener('input', () => {
      if (field.closest('.form-group')?.classList.contains('is-invalid')) fn();
    });
  }

  function _shakeField(field) {
    const target = field.closest('.form-group') || field;
    target.classList.remove('input-shake');
    void target.offsetWidth;
    target.classList.add('input-shake');
    target.addEventListener('animationend', () => target.classList.remove('input-shake'), { once: true });
  }

  function _setLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
      btn.dataset.originalHtml = btn.innerHTML;
      btn.classList.add('is-loading');
      btn.innerHTML = '<span class="spinner"></span><span class="btn-text">Envoi en cours\u2026</span>';
      btn.disabled  = true;
    } else {
      btn.classList.remove('is-loading');
      btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
      btn.disabled  = false;
    }
  }

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  //  PUBLIC API
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  /** validation_login(form?)  — validates the login form, returns boolean */
  window.validation_login = form => validation_login(form);

  /** validation_infos(form?)  — validates the signup form, returns boolean */
  window.validation_infos = form => validation_infos(form);

  /** validation_order(form?)  — validates the order form, returns boolean */
  window.validation_order = form => validation_order(form);

  /**
   * showToast(type, title, message, duration)
   * type: 'success' | 'error' | 'info' | 'warning'
   */
  window.showToast = function (type = 'info', title = '', message = '', duration = 4500) {
    const icons = { success: '\u2705', error: '\u274c', info: '\u2139\ufe0f', warning: '\u26a0\ufe0f' };
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML =
      `<span class="toast__icon">${icons[type] ?? '\u2139\ufe0f'}</span>` +
      '<div class="toast__body">' +
        (title   ? `<p class="toast__title">${title}</p>`   : '') +
        (message ? `<p class="toast__msg">${message}</p>`   : '') +
      '</div>' +
      '<button class="toast__close" aria-label="Fermer">\u2715</button>' +
      `<div class="toast__progress" style="animation-duration:${duration}ms"></div>`;

    const close = () => {
      toast.classList.add('is-leaving');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };
    toast.querySelector('.toast__close').addEventListener('click', close);
    toastRegion.appendChild(toast);
    if (duration > 0) setTimeout(close, duration);
    return toast;
  };

});
