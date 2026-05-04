<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
$user = getLoggedUser();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>A Minha Garagem</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:         hsl(220,20%,7%);
      --card:       hsl(220,18%,11%);
      --secondary:  hsl(220,15%,16%);
      --border:     hsl(220,15%,18%);
      --fg:         hsl(210,20%,92%);
      --muted:      hsl(215,15%,50%);
      --primary:    hsl(0,85%,55%);
      --destructive:hsl(0,84%,60%);
      --radius:     0.75rem;
      --font-d:     'Orbitron', monospace;
      --font-b:     'Inter', sans-serif;
    }

    body { background: var(--bg); color: var(--fg); font-family: var(--font-b); min-height: 100vh; }

    /* ── Utils ── */
    .font-d  { font-family: var(--font-d); }
    .hidden  { display: none !important; }
    .flex    { display: flex; }
    .items-center { align-items: center; }
    .gap-2   { gap: .5rem; }
    .gap-3   { gap: .75rem; }
    .gap-4   { gap: 1rem; }
    .w-full  { width: 100%; }
    .text-muted { color: var(--muted); }
    .text-primary { color: var(--primary); }
    .text-sm { font-size: .875rem; }
    .text-xs { font-size: .75rem; }
    .uppercase { text-transform: uppercase; }
    .tracking-wide { letter-spacing: .05em; }
    .tracking-wider { letter-spacing: .1em; }
    .tracking-widest { letter-spacing: .2em; }

    /* ── Spinner ── */
    #page-loading {
      display: flex; align-items: center; justify-content: center;
      height: 100vh;
    }
    .spinner {
      width: 36px; height: 36px; border-radius: 50%;
      border: 3px solid var(--border);
      border-top-color: var(--primary);
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Button ── */
    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
      padding: .45rem 1rem; border-radius: calc(var(--radius) - 2px);
      font-family: var(--font-d); font-size: .75rem; font-weight: 600;
      letter-spacing: .1em; text-transform: uppercase; cursor: pointer;
      border: none; transition: opacity .15s, background .15s;
    }
    .btn:disabled { opacity: .5; cursor: not-allowed; }
    .btn-primary  { background: var(--primary); color: #fff; }
    .btn-primary:hover:not(:disabled) { opacity: .85; }
    .btn-ghost    { background: transparent; color: var(--muted); }
    .btn-ghost:hover:not(:disabled) { color: var(--fg); background: var(--secondary); }
    .btn-danger   { background: transparent; color: var(--muted); }
    .btn-danger:hover:not(:disabled) { color: var(--destructive); background: hsl(0 84% 60% / .1); }
    .btn-icon     { padding: .4rem; border-radius: calc(var(--radius) - 2px); }
    .btn-full     { width: 100%; padding: .7rem 1rem; font-size: .85rem; }

    /* ── Input / Label ── */
    .label {
      display: block; font-size: .7rem; font-weight: 500;
      color: var(--muted); text-transform: uppercase; letter-spacing: .1em;
      margin-bottom: .4rem;
    }
    .input {
      width: 100%; background: var(--secondary); border: 1px solid var(--border);
      border-radius: calc(var(--radius) - 2px); color: var(--fg);
      padding: .6rem .85rem; font-size: .9rem; font-family: var(--font-b);
      outline: none; transition: border-color .15s;
    }
    .input:focus { border-color: hsl(0 85% 55% / .5); }
    .input:disabled { opacity: .5; cursor: not-allowed; }
    .input-plate {
      font-family: var(--font-d); font-size: 1.1rem; letter-spacing: .2em;
      text-align: center; text-transform: uppercase;
    }
    .input-hint { font-size: .7rem; color: var(--muted); text-align: right; margin-top: .25rem; }

    /* ── Card ── */
    .card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.5rem;
    }

    /* ── Auth page ── */
    #auth-page {
      min-height: 100vh; display: flex;
    }
    .auth-hero {
      display: none;
      width: 50%; flex-direction: column; justify-content: space-between;
      padding: 3rem; position: relative; border-right: 1px solid var(--border);
      overflow: hidden;
    }
    @media(min-width: 1024px){ .auth-hero { display: flex; } }
    .auth-hero-blob1 {
      position: absolute; top: 5rem; left: 2rem;
      width: 18rem; height: 18rem; border-radius: 50%;
      background: hsl(0 85% 55% / .05); filter: blur(48px); pointer-events: none;
    }
    .auth-hero-blob2 {
      position: absolute; bottom: 5rem; right: 2rem;
      width: 24rem; height: 24rem; border-radius: 50%;
      background: hsl(0 85% 55% / .03); filter: blur(64px); pointer-events: none;
    }
    .auth-logo {
      display: flex; align-items: center; gap: .75rem; position: relative; z-index: 1;
    }
    .auth-logo-icon {
      width: 2.5rem; height: 2.5rem; border-radius: .75rem;
      background: hsl(0 85% 55% / .15); border: 1px solid hsl(0 85% 55% / .2);
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 0 20px hsl(0 85% 55% / .3);
    }
    .auth-hero-body { position: relative; z-index: 1; }
    .auth-hero-title {
      font-family: var(--font-d); font-size: 2.25rem; font-weight: 700;
      line-height: 1.2; letter-spacing: .05em; margin-bottom: 1rem;
    }
    .auth-hero-desc { color: var(--muted); line-height: 1.7; max-width: 24rem; }
    .auth-feature {
      display: flex; align-items: center; gap: .75rem; margin-top: 1rem;
    }
    .auth-feature-icon {
      width: 2rem; height: 2rem; border-radius: .5rem;
      background: hsl(0 85% 55% / .1); border: 1px solid hsl(0 85% 55% / .15);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .auth-form-panel {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 2rem 1.5rem;
    }
    .auth-form-inner { width: 100%; max-width: 22rem; }
    .auth-mobile-logo {
      text-align: center; margin-bottom: 2rem;
    }
    @media(min-width: 1024px){ .auth-mobile-logo { display: none; } }
    .auth-mobile-logo-icon {
      width: 3.5rem; height: 3.5rem; border-radius: 1rem;
      background: hsl(0 85% 55% / .1); border: 1px solid hsl(0 85% 55% / .2);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1rem; box-shadow: 0 0 20px hsl(0 85% 55% / .3);
    }
    .auth-form-title {
      font-family: var(--font-d); font-size: 1.25rem; font-weight: 700;
      letter-spacing: .05em; margin-bottom: .25rem;
    }
    .auth-form-sub { color: var(--muted); font-size: .875rem; margin-bottom: 2rem; }
    .form-group { margin-bottom: 1rem; }
    .auth-toggle {
      text-align: center; padding-top: 1.5rem;
      border-top: 1px solid var(--border); margin-top: 1.5rem;
      font-size: .875rem; color: var(--muted);
    }
    .auth-toggle button {
      background: none; border: none; color: var(--primary);
      font-weight: 500; cursor: pointer; text-decoration: underline;
      text-underline-offset: 3px;
    }
    .auth-err {
      background: hsl(0 84% 60% / .1); border: 1px solid hsl(0 84% 60% / .3);
      border-radius: calc(var(--radius) - 2px); padding: .6rem .9rem;
      font-size: .85rem; color: var(--destructive); margin-bottom: 1rem;
    }
    .forgot-link {
      display: block; text-align: right; font-size: .75rem; color: var(--muted);
      margin-top: .25rem; text-decoration: none;
    }
    .forgot-link:hover { color: var(--primary); }

    /* ── App layout ── */
    #app-page { min-height: 100vh; }
    header.app-header {
      position: sticky; top: 0; z-index: 10;
      background: hsl(220 20% 7% / .9); backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
    }
    .header-inner {
      max-width: 42rem; margin: 0 auto; padding: .75rem 1rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .header-logo-icon {
      width: 2.25rem; height: 2.25rem; border-radius: .5rem;
      background: hsl(0 85% 55% / .1); border: 1px solid hsl(0 85% 55% / .15);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .header-title { font-family: var(--font-d); font-size: .75rem; font-weight: 700; letter-spacing: .2em; }
    .header-sub   { font-size: .7rem; color: var(--muted); margin-top: .1rem; }
    .header-actions { display: flex; align-items: center; gap: .25rem; }
    main.app-main { max-width: 42rem; margin: 0 auto; padding: 1.5rem 1rem; }

    /* ── Profile page ── */
    #profile-page { min-height: 100vh; }
    .profile-header {
      position: sticky; top: 0; z-index: 10;
      background: hsl(220 20% 7% / .9); backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
    }
    .profile-header-inner {
      max-width: 42rem; margin: 0 auto; padding: .75rem 1rem;
      display: flex; align-items: center; gap: .75rem;
    }
    .profile-main { max-width: 42rem; margin: 0 auto; padding: 2rem 1rem; }
    .profile-avatar-card {
      display: flex; align-items: center; gap: 1.25rem;
      padding: 1.25rem; border-radius: var(--radius);
      background: var(--card); border: 1px solid var(--border);
      margin-bottom: 1.5rem;
    }
    .profile-avatar {
      width: 3.5rem; height: 3.5rem; border-radius: 50%;
      background: hsl(0 85% 55% / .1); border: 2px solid hsl(0 85% 55% / .2);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    /* ── Car form ── */
    #car-form-wrapper {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1rem;
      animation: slideIn .2s ease;
    }
    @keyframes slideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
    .form-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1.25rem;
    }
    .form-header h3 {
      font-family: var(--font-d); font-size: .75rem; font-weight: 600;
      letter-spacing: .15em; color: var(--primary); text-transform: uppercase;
    }
    .close-btn {
      background: none; border: none; color: var(--muted); cursor: pointer;
      display: flex; align-items: center; padding: .25rem;
      border-radius: .375rem; transition: color .15s;
    }
    .close-btn:hover { color: var(--fg); }

    /* Color picker */
    .color-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: .5rem; }
    .color-swatch {
      aspect-ratio: 1; border-radius: .5rem; border: 2px solid transparent;
      cursor: pointer; transition: transform .15s, border-color .15s;
    }
    .color-swatch:hover { border-color: hsl(215 15% 50% / .5); }
    .color-swatch.selected {
      border-color: var(--primary); transform: scale(1.12);
      box-shadow: 0 0 0 2px hsl(0 85% 55% / .3);
    }
    .color-name { font-size: .7rem; color: var(--muted); margin-top: .35rem; }

    /* Brand select */
    .brand-select-wrap { position: relative; }
    .brand-combobox {
      width: 100%; display: flex; align-items: center; justify-content: space-between;
      height: 2.5rem; border-radius: calc(var(--radius) - 2px);
      border: 1px solid var(--border); background: var(--secondary);
      padding: 0 .85rem; font-size: .875rem; color: var(--fg);
      cursor: pointer; transition: border-color .15s;
    }
    .brand-combobox:focus { outline: none; border-color: hsl(0 85% 55% / .5); }
    .brand-combobox-left { display: flex; align-items: center; gap: .5rem; }
    .brand-combobox img { width: 1.1rem; height: 1.1rem; object-fit: contain; }
    .brand-dropdown {
      position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 50;
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); overflow: hidden;
      box-shadow: 0 8px 32px rgba(0,0,0,.4);
    }
    .brand-search {
      width: 100%; background: var(--secondary); border: none;
      border-bottom: 1px solid var(--border); color: var(--fg);
      padding: .6rem .85rem; font-size: .85rem; outline: none;
    }
    .brand-list {
      max-height: 14rem; overflow-y: auto;
    }
    .brand-item {
      display: flex; align-items: center; gap: .6rem;
      padding: .5rem .85rem; cursor: pointer; font-size: .875rem;
      transition: background .1s;
    }
    .brand-item:hover, .brand-item.active { background: var(--secondary); }
    .brand-item img { width: 1.1rem; height: 1.1rem; object-fit: contain; }
    .brand-empty { padding: .75rem .85rem; color: var(--muted); font-size: .85rem; }

    /* ── Car card ── */
    .car-card {
      position: relative; border-radius: var(--radius);
      border: 1px solid var(--border); background: var(--card);
      overflow: hidden; transition: border-color .2s;
      margin-bottom: .75rem;
    }
    .car-card:hover { border-color: hsl(0 85% 55% / .25); }
    .car-card-stripe {
      position: absolute; top: 0; left: 0; width: 4px; height: 100%;
      border-radius: var(--radius) 0 0 var(--radius); opacity: .7;
    }
    .car-card-inner {
      padding: 1rem 1rem 1rem 1.25rem;
      display: flex; align-items: center; gap: 1rem;
    }
    .car-brand-logo {
      width: 2.5rem; height: 2.5rem; border-radius: .5rem;
      background: hsl(220 15% 20% / .8); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      overflow: hidden;
    }
    .car-brand-logo img { width: 1.75rem; height: 1.75rem; object-fit: contain; opacity: .85; transition: opacity .2s; }
    .car-card:hover .car-brand-logo img { opacity: 1; }
    .car-info { flex: 1; min-width: 0; display: flex; align-items: center; gap: .75rem; }
    .car-color-dot {
      width: 1rem; height: 1rem; border-radius: 50%;
      border: 1px solid hsl(0 0% 100% / .1); flex-shrink: 0;
    }
    .car-plate { font-family: var(--font-d); font-size: 1rem; letter-spacing: .15em; font-weight: 700; }
    .car-sub { font-size: .8rem; color: var(--muted); display: flex; align-items: center; gap: .4rem; }
    .car-sub-sep { color: var(--border); }
    .car-actions {
      display: flex; gap: .25rem; opacity: 0; transition: opacity .15s; flex-shrink: 0;
    }
    .car-card:hover .car-actions { opacity: 1; }

    /* ── Empty state ── */
    #empty-state { text-align: center; padding: 6rem 1rem; }
    .empty-icon-wrap {
      position: relative; width: 6rem; height: 6rem; margin: 0 auto 1.5rem;
    }
    .empty-icon-bg {
      position: absolute; inset: 0; border-radius: 1rem;
      background: hsl(0 85% 55% / .05); border: 1px solid hsl(0 85% 55% / .1);
      filter: blur(4px);
    }
    .empty-icon-box {
      position: relative; width: 6rem; height: 6rem; border-radius: 1rem;
      background: var(--secondary); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
    }
    #empty-state h2 { font-weight: 600; font-size: 1.1rem; margin-bottom: .5rem; }
    #empty-state p { color: var(--muted); font-size: .875rem; max-width: 20rem; margin: 0 auto 1.5rem; }

    /* ── Skeleton ── */
    .skeleton { background: var(--secondary); border-radius: .5rem; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

    /* ── Toast ── */
    #toast-container {
      position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
      display: flex; flex-direction: column; gap: .5rem;
    }
    .toast {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: .75rem 1.1rem;
      font-size: .875rem; box-shadow: 0 8px 24px rgba(0,0,0,.4);
      animation: toastIn .2s ease; max-width: 22rem;
    }
    .toast.error { border-color: hsl(0 84% 60% / .4); }
    .toast-title { font-weight: 600; margin-bottom: .1rem; }
    .toast-desc  { color: var(--muted); font-size: .8rem; }
    @keyframes toastIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }

    svg { display: inline-block; vertical-align: middle; }
  </style>
</head>
<body>

<!-- Loading -->
<div id="page-loading"><div class="spinner"></div></div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- AUTH PAGE -->
<div id="auth-page" class="hidden">
  <!-- Hero (desktop only) -->
  <div class="auth-hero">
    <div class="auth-hero-blob1"></div>
    <div class="auth-hero-blob2"></div>
    <div class="auth-logo">
      <div class="auth-logo-icon" style="background:transparent;border:none;box-shadow:none;padding:0">
        <img src="logo.png" style="width:40px;height:40px;object-fit:contain" />
      </div>
      <span class="font-d uppercase tracking-widest text-sm" style="font-weight:700">A Minha Garagem</span>
    </div>
    <div class="auth-hero-body">
      <div class="auth-hero-title">A tua garagem,<br/><span style="color:var(--primary)">sempre contigo.</span></div>
      <p class="auth-hero-desc">Regista, organiza e acompanha todos os teus veículos num só lugar. Simples e seguro.</p>
      <div class="auth-feature">
        <div class="auth-feature-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><bolt d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <span class="text-sm text-muted">Registo rápido de veículos</span>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><rect x="2" y="4" width="20" height="5" rx="1"/><rect x="2" y="12" width="20" height="5" rx="1"/></svg>
        </div>
        <span class="text-sm text-muted">Todos os carros organizados</span>
      </div>
      <div class="auth-feature">
        <div class="auth-feature-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <span class="text-sm text-muted">Os teus dados em segurança</span>
      </div>
    </div>
    <p style="font-size:.7rem;color:hsl(215 15% 50%/.5);text-transform:uppercase;letter-spacing:.1em;position:relative;z-index:1">© 2025 A Minha Garagem</p>
  </div>

  <!-- Form panel -->
  <div class="auth-form-panel">
    <div class="auth-form-inner">
      <div class="auth-mobile-logo">
        <div class="auth-mobile-logo-icon" style="background:transparent;border:none;box-shadow:none">
          <img src="logo.png" style="width:56px;height:56px;object-fit:contain" />
        </div>
        <div class="font-d uppercase tracking-widest" style="font-size:.9rem;font-weight:700">A Minha Garagem</div>
      </div>

      <div id="auth-err" class="auth-err hidden"></div>

      <div id="auth-form-title" class="auth-form-title">Bem-vindo de volta</div>
      <div id="auth-form-sub"   class="auth-form-sub">Introduz as tuas credenciais para entrar</div>

      <!-- Register-only field -->
      <div id="field-name" class="form-group hidden">
        <label class="label">Nome</label>
        <input id="inp-name" class="input" type="text" placeholder="O teu nome" maxlength="100" />
      </div>

      <div class="form-group">
        <label class="label">Email</label>
        <input id="inp-email" class="input" type="email" placeholder="email@exemplo.com" maxlength="255" autocomplete="email" />
      </div>

      <div class="form-group">
        <label class="label">Password</label>
        <input id="inp-password" class="input" type="password" placeholder="••••••••" minlength="6" maxlength="128" />
        <a href="#" id="forgot-link" class="forgot-link">Esqueci a palavra-passe</a>
      </div>

      <button id="auth-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Entrar
      </button>

      <div class="auth-toggle">
        Não tens conta?
        <button id="auth-switch">Criar uma</button>
      </div>
    </div>
  </div>
</div>

<!-- APP PAGE -->
<div id="app-page" class="hidden">
  <header class="app-header">
    <div class="header-inner">
      <div class="flex items-center gap-3">
        <div class="header-logo-icon" style="background:transparent;border:none;padding:0">
          <img src="logo.png" style="width:36px;height:36px;object-fit:contain" />
        </div>
        <div>
          <div class="header-title">A Minha Garagem</div>
          <div id="car-count-label" class="header-sub">A carregar...</div>
        </div>
      </div>
      <div class="header-actions">
        <button id="btn-add-car" class="btn btn-primary" style="height:2rem;font-size:.7rem;padding:0 .75rem">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Adicionar
        </button>
        <button id="btn-profile" class="btn btn-ghost btn-icon" title="Perfil">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <button id="btn-logout" class="btn btn-danger btn-icon" title="Sair">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </div>
  </header>
  <main class="app-main">
    <div id="car-form-wrapper" class="hidden">
      <div class="form-header">
        <h3 id="form-title">Novo Carro</h3>
        <button class="close-btn" id="btn-close-form">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Plate -->
      <div class="form-group">
        <label class="label">Matrícula</label>
        <input id="inp-plate" class="input input-plate" type="text" placeholder="AA-00-AA" maxlength="8" />
        <div class="input-hint"><span id="plate-count">0</span>/8</div>
      </div>

      <!-- Brand -->
      <div class="form-group">
        <label class="label">Marca</label>
        <div class="brand-select-wrap">
          <button type="button" id="brand-combobox" class="brand-combobox">
            <span class="brand-combobox-left" id="brand-combobox-label">
              <span style="color:var(--muted)">Pesquisar marca...</span>
            </span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div id="brand-dropdown" class="brand-dropdown hidden">
            <input id="brand-search" class="brand-search" type="text" placeholder="Pesquisar marca..." />
            <div id="brand-list" class="brand-list"></div>
          </div>
        </div>
      </div>

      <!-- Color -->
      <div class="form-group">
        <label class="label">Cor</label>
        <div id="color-grid" class="color-grid"></div>
        <div id="color-name" class="color-name"></div>
      </div>

      <button id="btn-form-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Adicionar
      </button>
    </div>

    <div id="cars-loading" class="hidden">
      <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:.75rem">
        <div class="skeleton" style="height:2rem;width:8rem;margin-bottom:1rem;border-radius:.5rem"></div>
        <div style="display:flex;align-items:center;gap:.75rem">
          <div class="skeleton" style="width:1.25rem;height:1.25rem;border-radius:50%"></div>
          <div>
            <div class="skeleton" style="height:1rem;width:6rem;border-radius:.375rem;margin-bottom:.375rem"></div>
            <div class="skeleton" style="height:.75rem;width:4rem;border-radius:.375rem"></div>
          </div>
        </div>
      </div>
    </div>

    <div id="empty-state" class="hidden">
      <div class="empty-icon-wrap">
        <div class="empty-icon-bg"></div>
        <div class="empty-icon-box">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)">
            <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
          </svg>
        </div>
      </div>
      <h2>Garagem vazia</h2>
      <p>Ainda não tens nenhum veículo registado. Adiciona o primeiro agora.</p>
      <button class="btn btn-primary" id="btn-add-car-empty">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Adicionar Carro
      </button>
    </div>

    <div id="cars-list"></div>
  </main>
</div>

<!-- PROFILE PAGE -->
<div id="profile-page" class="hidden">
  <div class="profile-header">
    <div class="profile-header-inner">
      <button id="btn-back-profile" class="btn btn-ghost btn-icon">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <div>
        <div class="header-title">Perfil</div>
        <div class="header-sub">Gerir conta</div>
      </div>
    </div>
  </div>
  <div class="profile-main">
    <div class="profile-avatar-card">
      <div class="profile-avatar">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div id="profile-name-display" style="font-weight:600"></div>
        <div id="profile-email-display" style="font-size:.8rem;color:var(--muted);margin-top:.2rem"></div>
      </div>
    </div>
    <div class="card">
      <div style="font-family:var(--font-d);font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--muted);text-transform:uppercase;margin-bottom:1.25rem">Informações</div>
      <div class="form-group">
        <label class="label">Nome</label>
        <input id="profile-name-inp" class="input" type="text" placeholder="O teu nome" maxlength="100" />
      </div>
      <div class="form-group">
        <label class="label">Email</label>
        <input id="profile-email-inp" class="input" type="email" disabled style="opacity:.5;cursor:not-allowed" />
        <div class="input-hint" style="text-align:left;margin-top:.35rem">O email não pode ser alterado</div>
      </div>
      <button id="btn-save-profile" class="btn btn-primary btn-full" style="margin-top:.25rem">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Guardar Alterações
      </button>
    </div>
  </div>
</div>

<script>
// ══════════════════════════════════════════════
//  DATA
// ══════════════════════════════════════════════
const CAR_COLORS = [
  {name:"Preto",value:"#111111"},{name:"Preto Mate",value:"#2a2a2a"},
  {name:"Branco",value:"#f5f5f5"},{name:"Branco Pérola",value:"#f0ece4"},
  {name:"Cinzento",value:"#808080"},{name:"Cinzento Escuro",value:"#4a4a4a"},
  {name:"Prata",value:"#c0c0c0"},{name:"Champanhe",value:"#c9a96e"},
  {name:"Dourado",value:"#d4a017"},{name:"Vermelho",value:"#dc2626"},
  {name:"Vermelho Escuro",value:"#8b0000"},{name:"Bordeaux",value:"#722f37"},
  {name:"Laranja",value:"#ea580c"},{name:"Amarelo",value:"#eab308"},
  {name:"Verde",value:"#16a34a"},{name:"Verde Escuro",value:"#14532d"},
  {name:"Verde Militar",value:"#4b5320"},{name:"Turquesa",value:"#0d9488"},
  {name:"Azul Claro",value:"#38bdf8"},{name:"Azul",value:"#2563eb"},
  {name:"Azul Escuro",value:"#1e3a5f"},{name:"Azul Marinho",value:"#172554"},
  {name:"Roxo",value:"#7c3aed"},{name:"Rosa",value:"#db2777"},
  {name:"Castanho",value:"#92400e"},{name:"Bege",value:"#d4c5a9"},
];

const CAR_BRANDS = ["Abarth","AC Cars","Acura","Alfa Romeo","Alpine","Alpina","Aston Martin","Audi","Austin","Bentley","BMW","Bugatti","Buick","BYD","Cadillac","Caterham","Chery","Chevrolet","Chrysler","Citroën","Cupra","Dacia","Daewoo","Daihatsu","Datsun","De Tomaso","DeLorean","Dodge","DS Automobiles","Ferrari","Fiat","Fisker","Ford","Genesis","GMC","Honda","Hummer","Hyundai","Infiniti","Jaguar","Jeep","Kia","Koenigsegg","Lada","Lamborghini","Lancia","Land Rover","Lexus","Lincoln","Lotus","Lucid","Mahindra","Maserati","Maybach","Mazda","McLaren","Mercedes-Benz","MG","Mini","Mitsubishi","Morgan","NIO","Nissan","Oldsmobile","Opel","Pagani","Peugeot","Polestar","Pontiac","Porsche","RAM","Renault","Rimac","Rivian","Rolls-Royce","Rover","Saab","SEAT","Škoda","Smart","Subaru","Suzuki","Tata","Tesla","Toyota","Triumph","Volkswagen","Volvo","Wiesmann","XPeng"].sort((a,b)=>a.localeCompare(b));

const CDN = "https://cdn.jsdelivr.net/gh/filippofilip95/car-logos-ds@latest/logos/optimized";
const BRAND_LOGOS = {"Abarth":"abarth","Alfa Romeo":"alfa-romeo","Alpine":"alpine","Aston Martin":"aston-martin","Audi":"audi","Bentley":"bentley","BMW":"bmw","Bugatti":"bugatti","Cadillac":"cadillac","Chevrolet":"chevrolet","Chrysler":"chrysler","Citroën":"citroen","Cupra":"cupra","Dacia":"dacia","Dodge":"dodge","DS Automobiles":"ds","Ferrari":"ferrari","Fiat":"fiat","Ford":"ford","Genesis":"genesis","Honda":"honda","Hyundai":"hyundai","Infiniti":"infiniti","Jaguar":"jaguar","Jeep":"jeep","Kia":"kia","Lamborghini":"lamborghini","Land Rover":"land-rover","Lexus":"lexus","Maserati":"maserati","Mazda":"mazda","McLaren":"mclaren","Mercedes-Benz":"mercedes","Mini":"mini","Mitsubishi":"mitsubishi","Nissan":"nissan","Opel":"opel","Pagani":"pagani","Peugeot":"peugeot","Polestar":"polestar","Porsche":"porsche","RAM":"ram","Renault":"renault","Rolls-Royce":"rolls-royce","Saab":"saab","SEAT":"seat","Škoda":"skoda","Smart":"smart","Subaru":"subaru","Suzuki":"suzuki","Tesla":"tesla","Toyota":"toyota","Volkswagen":"volkswagen","Volvo":"volvo"};
const getBrandLogo = b => BRAND_LOGOS[b] ? `${CDN}/${BRAND_LOGOS[b]}.svg` : null;

// ══════════════════════════════════════════════
//  STATE
// ══════════════════════════════════════════════
let currentUser = null;
let cars = [];
let editingCarId = null;
let selectedBrand = '';
let selectedColor = CAR_COLORS[0].value;

// ══════════════════════════════════════════════
//  API
// ══════════════════════════════════════════════
async function api(method, url, body) {
  const opts = { method, headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const res = await fetch(url, opts);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Erro desconhecido');
  return data;
}

// ══════════════════════════════════════════════
//  TOAST
// ══════════════════════════════════════════════
function toast(title, desc, isError) {
  const el = document.createElement('div');
  el.className = 'toast' + (isError ? ' error' : '');
  el.innerHTML = `<div class="toast-title">${title}</div>${desc ? `<div class="toast-desc">${desc}</div>` : ''}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ══════════════════════════════════════════════
//  PAGES
// ══════════════════════════════════════════════
function showPage(name) {
  document.getElementById('page-loading').classList.add('hidden');
  ['auth-page','app-page','profile-page'].forEach(id =>
    document.getElementById(id).classList.toggle('hidden', id !== name + '-page')
  );
}

// ══════════════════════════════════════════════
//  AUTH
// ══════════════════════════════════════════════
let isLoginMode = true;

function setAuthMode(login) {
  isLoginMode = login;
  document.getElementById('field-name').classList.toggle('hidden', login);
  document.getElementById('forgot-link').classList.toggle('hidden', !login);
  document.getElementById('auth-form-title').textContent = login ? 'Bem-vindo de volta' : 'Criar conta';
  document.getElementById('auth-form-sub').textContent   = login ? 'Introduz as tuas credenciais para entrar' : 'Preenche os dados para criar a tua conta';
  document.getElementById('auth-submit').innerHTML = login
    ? `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Entrar`
    : `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg> Criar Conta`;
  document.getElementById('auth-switch').textContent = login ? 'Criar uma' : 'Entrar';
  document.querySelector('.auth-toggle').firstChild.textContent = login ? 'Não tens conta? ' : 'Já tens conta? ';
  document.getElementById('auth-err').classList.add('hidden');
}

document.getElementById('auth-switch').onclick = () => setAuthMode(!isLoginMode);
document.getElementById('forgot-link').onclick = e => {
  e.preventDefault();
  toast('Reset de password', 'Funcionalidade disponível em breve.');
};

document.getElementById('auth-submit').onclick = async () => {
  const email    = document.getElementById('inp-email').value.trim();
  const password = document.getElementById('inp-password').value;
  const name     = document.getElementById('inp-name').value.trim();
  const errEl    = document.getElementById('auth-err');
  const btn      = document.getElementById('auth-submit');

  errEl.classList.add('hidden');
  btn.disabled = true; btn.textContent = 'A processar...';

  try {
    let user;
    if (isLoginMode) {
      user = await api('POST', 'api/auth.php?action=login', { email, password });
    } else {
      user = await api('POST', 'api/auth.php?action=register', { email, password, displayName: name || undefined });
    }
    currentUser = user;
    await loadCars();
    showPage('app');
  } catch (e) {
    errEl.textContent = e.message;
    errEl.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    setAuthMode(isLoginMode);
  }
};

// allow Enter key
['inp-email','inp-password','inp-name'].forEach(id => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('auth-submit').click();
  });
});

// ══════════════════════════════════════════════
//  LOGOUT
// ══════════════════════════════════════════════
document.getElementById('btn-logout').onclick = async () => {
  await api('POST', 'api/auth.php?action=logout').catch(() => {});
  currentUser = null; cars = [];
  showPage('auth');
};

// ══════════════════════════════════════════════
//  PROFILE
// ══════════════════════════════════════════════
document.getElementById('btn-profile').onclick = () => {
  document.getElementById('profile-name-display').textContent = currentUser.displayName || 'Sem nome';
  document.getElementById('profile-email-display').textContent = currentUser.email;
  document.getElementById('profile-name-inp').value  = currentUser.displayName || '';
  document.getElementById('profile-email-inp').value = currentUser.email;
  showPage('profile');
};
document.getElementById('btn-back-profile').onclick = () => showPage('app');
document.getElementById('btn-save-profile').onclick = async () => {
  const name = document.getElementById('profile-name-inp').value.trim();
  const btn  = document.getElementById('btn-save-profile');
  btn.disabled = true; btn.textContent = 'A guardar...';
  try {
    await api('PUT', 'api/auth.php?action=profile', { displayName: name });
    currentUser.displayName = name;
    toast('Perfil atualizado!');
    showPage('app');
  } catch (e) { toast('Erro', e.message, true); }
  finally { btn.disabled = false; btn.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar Alterações`; }
};

// ══════════════════════════════════════════════
//  COLOR PICKER
// ══════════════════════════════════════════════
function buildColorGrid() {
  const grid = document.getElementById('color-grid');
  grid.innerHTML = '';
  CAR_COLORS.forEach(c => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'color-swatch' + (c.value === selectedColor ? ' selected' : '');
    btn.style.backgroundColor = c.value;
    btn.title = c.name;
    btn.onclick = () => {
      selectedColor = c.value;
      document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('selected'));
      btn.classList.add('selected');
      document.getElementById('color-name').textContent = c.name;
    };
    grid.appendChild(btn);
  });
  document.getElementById('color-name').textContent = CAR_COLORS.find(c=>c.value===selectedColor)?.name || '';
}

// ══════════════════════════════════════════════
//  BRAND COMBOBOX
// ══════════════════════════════════════════════
let brandDropdownOpen = false;

function setBrand(brand) {
  selectedBrand = brand;
  const logo = getBrandLogo(brand);
  const label = document.getElementById('brand-combobox-label');
  label.innerHTML = brand
    ? `${logo ? `<img src="${logo}" alt="${brand}" onerror="this.style.display='none'"/>` : ''}<span>${brand}</span>`
    : `<span style="color:var(--muted)">Pesquisar marca...</span>`;
}

function renderBrandList(filter) {
  const list = document.getElementById('brand-list');
  const items = CAR_BRANDS.filter(b => b.toLowerCase().includes(filter.toLowerCase()));
  if (!items.length) { list.innerHTML = `<div class="brand-empty">Marca não encontrada.</div>`; return; }
  list.innerHTML = items.map(b => {
    const logo = getBrandLogo(b);
    return `<div class="brand-item${b===selectedBrand?' active':''}" data-brand="${b}">
      ${logo ? `<img src="${logo}" alt="${b}" onerror="this.style.display='none'"/>` : ''}
      <span>${b}</span>
    </div>`;
  }).join('');
  list.querySelectorAll('.brand-item').forEach(el => {
    el.onclick = () => { setBrand(el.dataset.brand); closeBrandDropdown(); };
  });
}

function openBrandDropdown() {
  brandDropdownOpen = true;
  document.getElementById('brand-dropdown').classList.remove('hidden');
  document.getElementById('brand-search').value = '';
  renderBrandList('');
  setTimeout(() => document.getElementById('brand-search').focus(), 50);
}
function closeBrandDropdown() {
  brandDropdownOpen = false;
  document.getElementById('brand-dropdown').classList.add('hidden');
}

document.getElementById('brand-combobox').onclick = () => brandDropdownOpen ? closeBrandDropdown() : openBrandDropdown();
document.getElementById('brand-search').oninput = e => renderBrandList(e.target.value);
document.addEventListener('click', e => {
  if (brandDropdownOpen && !document.querySelector('.brand-select-wrap').contains(e.target)) closeBrandDropdown();
});

// ══════════════════════════════════════════════
//  CAR FORM
// ══════════════════════════════════════════════
function openForm(car) {
  editingCarId = car ? car.id : null;
  selectedBrand = car ? car.brand : '';
  selectedColor = car ? car.color : CAR_COLORS[0].value;

  document.getElementById('form-title').textContent = car ? 'Editar Carro' : 'Novo Carro';
  document.getElementById('inp-plate').value = car ? car.plate : '';
  document.getElementById('plate-count').textContent = (car ? car.plate.length : 0);
  document.getElementById('btn-form-submit').innerHTML = car
    ? `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar`
    : `<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Adicionar`;

  setBrand(selectedBrand);
  buildColorGrid();

  document.getElementById('car-form-wrapper').classList.remove('hidden');
  document.getElementById('btn-add-car').classList.add('hidden');
  document.getElementById('empty-state').classList.add('hidden');
  document.getElementById('inp-plate').focus();
}

function closeForm() {
  editingCarId = null;
  document.getElementById('car-form-wrapper').classList.add('hidden');
  document.getElementById('btn-add-car').classList.remove('hidden');
  renderCars();
}

document.getElementById('inp-plate').oninput = e => {
  e.target.value = e.target.value.replace(/[^a-zA-Z0-9-]/g,'').toUpperCase();
  document.getElementById('plate-count').textContent = e.target.value.length;
};

document.getElementById('btn-add-car').onclick       = () => openForm(null);
document.getElementById('btn-add-car-empty').onclick  = () => openForm(null);
document.getElementById('btn-close-form').onclick     = closeForm;

document.getElementById('btn-form-submit').onclick = async () => {
  const plate = document.getElementById('inp-plate').value.trim().toUpperCase();
  if (!plate || plate.length > 8) { toast('Matrícula inválida','Máx. 8 caracteres',true); return; }
  if (!selectedBrand)             { toast('Marca obrigatória','',true); return; }

  const btn = document.getElementById('btn-form-submit');
  btn.disabled = true; btn.textContent = 'A guardar...';

  try {
    const body = { plate, brand: selectedBrand, color: selectedColor };
    if (editingCarId) {
      await api('PUT', `api/cars.php?id=${editingCarId}`, body);
      toast('Carro atualizado!');
    } else {
      await api('POST', 'api/cars.php', body);
      toast('Carro adicionado!');
    }
    await loadCars();
    closeForm();
  } catch (e) { toast('Erro', e.message, true); }
  finally { btn.disabled = false; }
};

// ══════════════════════════════════════════════
//  CARS LIST
// ══════════════════════════════════════════════
function renderCars() {
  const list  = document.getElementById('cars-list');
  const empty = document.getElementById('empty-state');
  const count = document.getElementById('car-count-label');

  count.textContent = `${cars.length} ${cars.length === 1 ? 'veículo' : 'veículos'}`;

  if (!cars.length) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
  empty.classList.add('hidden');

  list.innerHTML = cars.map(car => {
    const logo      = getBrandLogo(car.brand);
    const colorName = CAR_COLORS.find(c => c.value === car.color)?.name ?? 'Personalizada';
    return `
    <div class="car-card" data-id="${car.id}">
      <div class="car-card-stripe" style="background:${car.color}"></div>
      <div class="car-card-inner">
        <div class="car-brand-logo">
          ${logo ? `<img src="${logo}" alt="${car.brand}" onerror="this.style.display='none'"/>` : `<img src="logo.png" style="width:1.75rem;height:1.75rem;object-fit:contain;opacity:.5" alt="carro"/>`}
        </div>
        <div class="car-info">
          <div class="car-color-dot" style="background:${car.color}"></div>
          <div style="min-width:0">
            <div class="car-plate">${car.plate}</div>
            <div class="car-sub">
              <span style="color:hsl(210 20% 85%/.8);font-weight:500">${car.brand}</span>
              <span class="car-sub-sep">·</span>
              <span>${colorName}</span>
            </div>
          </div>
        </div>
        <div class="car-actions">
          <button class="btn btn-ghost btn-icon btn-edit" data-id="${car.id}" title="Editar">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn btn-danger btn-icon btn-delete" data-id="${car.id}" title="Remover">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
          </button>
        </div>
      </div>
    </div>`;
  }).join('');

  list.querySelectorAll('.btn-edit').forEach(btn => {
    btn.onclick = () => { const car = cars.find(c => c.id == btn.dataset.id); if (car) openForm(car); };
  });
  list.querySelectorAll('.btn-delete').forEach(btn => {
    btn.onclick = async () => {
      if (!confirm('Tens a certeza que queres remover este carro?')) return;
      try {
        await api('DELETE', `api/cars.php?id=${btn.dataset.id}`);
        await loadCars(); renderCars();
        toast('Carro removido.');
      } catch (e) { toast('Erro', e.message, true); }
    };
  });
}

async function loadCars() {
  document.getElementById('cars-loading').classList.remove('hidden');
  try {
    cars = await api('GET', 'api/cars.php');
  } catch (e) { cars = []; }
  document.getElementById('cars-loading').classList.add('hidden');
  renderCars();
}

// ══════════════════════════════════════════════
//  INIT
// ══════════════════════════════════════════════
(async () => {
  try {
    currentUser = await api('GET', 'api/auth.php?action=user');
    await loadCars();
    showPage('app');
  } catch {
    showPage('auth');
  }
})();
</script>
</body>
</html>
