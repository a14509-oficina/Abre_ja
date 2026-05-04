<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Abre Já</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:          hsl(220,20%,7%);
      --card:        hsl(220,18%,11%);
      --secondary:   hsl(220,15%,16%);
      --border:      hsl(220,15%,18%);
      --fg:          hsl(210,20%,92%);
      --muted:       hsl(215,15%,50%);
      --primary:     hsl(0,85%,55%);
      --destructive: hsl(0,84%,60%);
      --success:     hsl(142,70%,45%);
      --warning:     hsl(38,92%,50%);
      --radius:      0.75rem;
      --font-d:      'Orbitron', monospace;
      --font-b:      'Inter', sans-serif;
    }
    body { background: var(--bg); color: var(--fg); font-family: var(--font-b); min-height: 100vh; }
    .hidden { display: none !important; }
    .flex { display: flex; }
    .items-center { align-items: center; }
    .gap-2 { gap: .5rem; }
    .gap-3 { gap: .75rem; }
    .w-full { width: 100%; }
    .text-muted { color: var(--muted); }
    .text-primary { color: var(--primary); }
    .text-sm { font-size: .875rem; }
    .text-xs { font-size: .75rem; }

    /* Spinner */
    #page-loading { display:flex; align-items:center; justify-content:center; height:100vh; }
    .spinner { width:36px; height:36px; border-radius:50%; border:3px solid var(--border); border-top-color:var(--primary); animation:spin .7s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }

    /* Buttons */
    .btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; padding:.45rem 1rem; border-radius:calc(var(--radius) - 2px); font-family:var(--font-d); font-size:.75rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; cursor:pointer; border:none; transition:opacity .15s, background .15s; }
    .btn:disabled { opacity:.5; cursor:not-allowed; }
    .btn-primary  { background:var(--primary); color:#fff; }
    .btn-primary:hover:not(:disabled) { opacity:.85; }
    .btn-ghost    { background:transparent; color:var(--muted); }
    .btn-ghost:hover:not(:disabled) { color:var(--fg); background:var(--secondary); }
    .btn-danger   { background:transparent; color:var(--muted); }
    .btn-danger:hover:not(:disabled) { color:var(--destructive); background:hsl(0 84% 60%/.1); }
    .btn-success  { background:hsl(142 70% 45%/.15); color:var(--success); border:1px solid hsl(142 70% 45%/.3); }
    .btn-success:hover:not(:disabled) { background:hsl(142 70% 45%/.25); }
    .btn-warning  { background:hsl(38 92% 50%/.15); color:var(--warning); border:1px solid hsl(38 92% 50%/.3); }
    .btn-icon { padding:.4rem; }
    .btn-full { width:100%; padding:.7rem 1rem; font-size:.85rem; }

    /* Inputs */
    .label { display:block; font-size:.7rem; font-weight:500; color:var(--muted); text-transform:uppercase; letter-spacing:.1em; margin-bottom:.4rem; }
    .input { width:100%; background:var(--secondary); border:1px solid var(--border); border-radius:calc(var(--radius) - 2px); color:var(--fg); padding:.6rem .85rem; font-size:.9rem; font-family:var(--font-b); outline:none; transition:border-color .15s; }
    .input:focus { border-color:hsl(0 85% 55%/.5); }
    .input:disabled { opacity:.5; cursor:not-allowed; }
    .input-plate { font-family:var(--font-d); font-size:1.1rem; letter-spacing:.2em; text-align:center; text-transform:uppercase; }
    .input-hint { font-size:.7rem; color:var(--muted); text-align:right; margin-top:.25rem; }
    .form-group { margin-bottom:1rem; }

    /* Card */
    .card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; }

    /* Auth */
    #auth-page { min-height:100vh; display:flex; }
    .auth-hero { display:none; width:50%; flex-direction:column; justify-content:space-between; padding:3rem; position:relative; border-right:1px solid var(--border); overflow:hidden; }
    @media(min-width:1024px){ .auth-hero { display:flex; } }
    .auth-hero-blob1 { position:absolute; top:5rem; left:2rem; width:18rem; height:18rem; border-radius:50%; background:hsl(0 85% 55%/.05); filter:blur(48px); pointer-events:none; }
    .auth-hero-blob2 { position:absolute; bottom:5rem; right:2rem; width:24rem; height:24rem; border-radius:50%; background:hsl(0 85% 55%/.03); filter:blur(64px); pointer-events:none; }
    .auth-logo { display:flex; align-items:center; gap:.75rem; position:relative; z-index:1; }
    .auth-logo-icon { width:2.5rem; height:2.5rem; border-radius:.75rem; background:hsl(0 85% 55%/.15); border:1px solid hsl(0 85% 55%/.2); display:flex; align-items:center; justify-content:center; box-shadow:0 0 20px hsl(0 85% 55%/.3); }
    .auth-hero-body { position:relative; z-index:1; }
    .auth-hero-title { font-family:var(--font-d); font-size:2.25rem; font-weight:700; line-height:1.2; letter-spacing:.05em; margin-bottom:1rem; }
    .auth-hero-desc { color:var(--muted); line-height:1.7; max-width:24rem; }
    .auth-form-panel { flex:1; display:flex; align-items:center; justify-content:center; padding:2rem 1.5rem; }
    .auth-form-inner { width:100%; max-width:22rem; }
    .auth-mobile-logo { text-align:center; margin-bottom:2rem; }
    @media(min-width:1024px){ .auth-mobile-logo { display:none; } }
    .auth-mobile-logo-icon { width:3.5rem; height:3.5rem; border-radius:1rem; background:hsl(0 85% 55%/.1); border:1px solid hsl(0 85% 55%/.2); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; box-shadow:0 0 20px hsl(0 85% 55%/.3); }
    .auth-form-title { font-family:var(--font-d); font-size:1.25rem; font-weight:700; letter-spacing:.05em; margin-bottom:.25rem; }
    .auth-form-sub { color:var(--muted); font-size:.875rem; margin-bottom:2rem; }
    .auth-toggle { text-align:center; padding-top:1.5rem; border-top:1px solid var(--border); margin-top:1.5rem; font-size:.875rem; color:var(--muted); }
    .auth-toggle button { background:none; border:none; color:var(--primary); font-weight:500; cursor:pointer; text-decoration:underline; text-underline-offset:3px; }
    .auth-err { background:hsl(0 84% 60%/.1); border:1px solid hsl(0 84% 60%/.3); border-radius:calc(var(--radius) - 2px); padding:.6rem .9rem; font-size:.85rem; color:var(--destructive); margin-bottom:1rem; }
    .forgot-link { display:block; text-align:right; font-size:.75rem; color:var(--muted); margin-top:.25rem; text-decoration:none; cursor:pointer; }
    .forgot-link:hover { color:var(--primary); }

    /* App header */
    #app-page { min-height:100vh; }
    header.app-header { position:sticky; top:0; z-index:10; background:hsl(220 20% 7%/.9); backdrop-filter:blur(16px); border-bottom:1px solid var(--border); }
    .header-inner { max-width:48rem; margin:0 auto; padding:.75rem 1rem; display:flex; align-items:center; justify-content:space-between; }
    .header-title { font-family:var(--font-d); font-size:.85rem; font-weight:700; letter-spacing:.1em; }
    .header-sub { font-size:.7rem; color:var(--muted); margin-top:.1rem; }
    .header-logo-icon { width:2rem; height:2rem; border-radius:.5rem; background:hsl(0 85% 55%/.1); border:1px solid hsl(0 85% 55%/.2); display:flex; align-items:center; justify-content:center; }
    .header-actions { display:flex; align-items:center; gap:.4rem; }
    .app-main { max-width:48rem; margin:0 auto; padding:1.5rem 1rem; }

    /* Nav tabs */
    .nav-tabs { display:flex; gap:.25rem; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:.3rem; margin-bottom:1.5rem; }
    .nav-tab { flex:1; padding:.45rem .5rem; border-radius:calc(var(--radius) - 4px); border:none; background:transparent; color:var(--muted); font-family:var(--font-d); font-size:.65rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer; transition:background .15s, color .15s; }
    .nav-tab.active { background:var(--secondary); color:var(--fg); }
    .nav-tab:hover:not(.active) { color:var(--fg); }

    /* Section header */
    .section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
    .section-title-text { font-family:var(--font-d); font-size:.7rem; font-weight:700; letter-spacing:.15em; color:var(--muted); text-transform:uppercase; }

    /* Car form */
    .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1rem; animation:slideIn .2s ease; }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
    .form-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
    .form-header h3 { font-family:var(--font-d); font-size:.75rem; font-weight:600; letter-spacing:.15em; color:var(--primary); text-transform:uppercase; }
    .close-btn { background:none; border:none; color:var(--muted); cursor:pointer; display:flex; align-items:center; padding:.25rem; border-radius:.375rem; transition:color .15s; }
    .close-btn:hover { color:var(--fg); }

    /* Color picker */
    .color-grid { display:grid; grid-template-columns:repeat(8,1fr); gap:.5rem; }
    .color-swatch { aspect-ratio:1; border-radius:.5rem; border:2px solid transparent; cursor:pointer; transition:transform .15s, border-color .15s; }
    .color-swatch:hover { border-color:hsl(215 15% 50%/.5); }
    .color-swatch.selected { border-color:var(--primary); transform:scale(1.12); box-shadow:0 0 0 2px hsl(0 85% 55%/.3); }
    .color-name { font-size:.7rem; color:var(--muted); margin-top:.35rem; }

    /* Brand select */
    .brand-select-wrap { position:relative; }
    .brand-combobox { width:100%; display:flex; align-items:center; justify-content:space-between; height:2.5rem; border-radius:calc(var(--radius) - 2px); border:1px solid var(--border); background:var(--secondary); padding:0 .85rem; font-size:.875rem; color:var(--fg); cursor:pointer; transition:border-color .15s; }
    .brand-combobox:focus { outline:none; border-color:hsl(0 85% 55%/.5); }
    .brand-combobox-left { display:flex; align-items:center; gap:.5rem; }
    .brand-combobox img { width:1.1rem; height:1.1rem; object-fit:contain; }
    .brand-dropdown { position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:50; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,.4); }
    .brand-search { width:100%; background:var(--secondary); border:none; border-bottom:1px solid var(--border); color:var(--fg); padding:.6rem .85rem; font-size:.85rem; outline:none; }
    .brand-list { max-height:14rem; overflow-y:auto; }
    .brand-item { display:flex; align-items:center; gap:.6rem; padding:.5rem .85rem; cursor:pointer; font-size:.875rem; transition:background .1s; }
    .brand-item:hover,.brand-item.active { background:var(--secondary); }
    .brand-item img { width:1.1rem; height:1.1rem; object-fit:contain; }
    .brand-empty { padding:.75rem .85rem; color:var(--muted); font-size:.85rem; }

    /* Car card */
    .car-card { position:relative; border-radius:var(--radius); border:1px solid var(--border); background:var(--card); overflow:hidden; transition:border-color .2s; margin-bottom:.75rem; }
    .car-card:hover { border-color:hsl(0 85% 55%/.25); }
    .car-card-stripe { position:absolute; top:0; left:0; width:4px; height:100%; border-radius:var(--radius) 0 0 var(--radius); opacity:.7; }
    .car-card-inner { padding:1rem 1rem 1rem 1.25rem; display:flex; align-items:center; gap:1rem; }
    .car-brand-logo { width:2.5rem; height:2.5rem; border-radius:.5rem; background:hsl(220 15% 20%/.8); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; overflow:hidden; }
    .car-brand-logo img { width:1.75rem; height:1.75rem; object-fit:contain; opacity:.85; }
    .car-info { flex:1; min-width:0; display:flex; align-items:center; gap:.75rem; }
    .car-color-dot { width:1rem; height:1rem; border-radius:50%; border:1px solid hsl(0 0% 100%/.1); flex-shrink:0; }
    .car-plate { font-family:var(--font-d); font-size:1rem; letter-spacing:.15em; font-weight:700; }
    .car-sub { font-size:.8rem; color:var(--muted); display:flex; align-items:center; gap:.4rem; }
    .car-sub-sep { color:var(--border); }
    .card-actions { display:flex; gap:.25rem; opacity:0; transition:opacity .15s; flex-shrink:0; }
    .car-card:hover .card-actions { opacity:1; }

    /* Gate card */
    .gate-card { position:relative; border-radius:var(--radius); border:1px solid var(--border); background:var(--card); overflow:hidden; transition:border-color .2s; margin-bottom:.75rem; }
    .gate-card:hover { border-color:hsl(0 85% 55%/.25); }
    .gate-card-inner { padding:1rem 1rem 1rem 1.25rem; display:flex; align-items:center; gap:1rem; }
    .gate-icon-box { width:2.75rem; height:2.75rem; border-radius:.6rem; background:hsl(0 85% 55%/.08); border:1px solid hsl(0 85% 55%/.15); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.4rem; }
    .gate-info { flex:1; min-width:0; }
    .gate-name { font-family:var(--font-d); font-size:.95rem; font-weight:700; letter-spacing:.05em; }
    .gate-relay { font-size:.72rem; color:var(--muted); margin-top:.15rem; font-family:monospace; }
    .gate-card:hover .card-actions { opacity:1; }
    .btn-open-gate { background:hsl(142 70% 45%/.12); color:var(--success); border:1px solid hsl(142 70% 45%/.25); font-size:.65rem; padding:.3rem .6rem; }
    .btn-open-gate:hover { background:hsl(142 70% 45%/.22); }

    /* Icon picker */
    .icon-grid { display:flex; flex-wrap:wrap; gap:.4rem; }
    .icon-btn { width:2.4rem; height:2.4rem; border-radius:.5rem; border:2px solid transparent; cursor:pointer; font-size:1.3rem; background:var(--secondary); display:flex; align-items:center; justify-content:center; transition:border-color .15s, transform .1s; }
    .icon-btn:hover { border-color:hsl(215 15% 50%/.4); }
    .icon-btn.selected { border-color:var(--primary); transform:scale(1.1); }

    /* Access log */
    .log-item { display:flex; align-items:center; gap:.75rem; padding:.6rem .85rem; border-bottom:1px solid var(--border); font-size:.82rem; }
    .log-item:last-child { border-bottom:none; }
    .log-icon { width:1.75rem; height:1.75rem; border-radius:.4rem; background:hsl(0 85% 55%/.08); display:flex; align-items:center; justify-content:center; font-size:.9rem; flex-shrink:0; }
    .log-info { flex:1; min-width:0; }
    .log-time { font-size:.7rem; color:var(--muted); }

    /* Profile */
    #profile-page { min-height:100vh; }
    .profile-header { position:sticky; top:0; z-index:10; background:hsl(220 20% 7%/.9); backdrop-filter:blur(16px); border-bottom:1px solid var(--border); }
    .profile-header-inner { max-width:42rem; margin:0 auto; padding:.75rem 1rem; display:flex; align-items:center; gap:.75rem; }
    .profile-main { max-width:42rem; margin:0 auto; padding:2rem 1rem; }
    .profile-avatar { width:3.5rem; height:3.5rem; border-radius:50%; background:hsl(0 85% 55%/.1); border:2px solid hsl(0 85% 55%/.2); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .section-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:1.5rem; }
    .section-card-title { font-family:var(--font-d); font-size:.7rem; font-weight:700; letter-spacing:.15em; color:var(--muted); text-transform:uppercase; margin-bottom:1.25rem; }

    /* Admin */
    #admin-page { min-height:100vh; }
    .admin-header { position:sticky; top:0; z-index:10; background:hsl(220 20% 7%/.9); backdrop-filter:blur(16px); border-bottom:1px solid var(--border); }
    .admin-main { max-width:60rem; margin:0 auto; padding:1.5rem 1rem; }
    .stat-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.75rem; margin-bottom:1.5rem; }
    @media(min-width:640px){ .stat-grid { grid-template-columns:repeat(4,1fr); } }
    .stat-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1rem; }
    .stat-value { font-family:var(--font-d); font-size:1.75rem; font-weight:700; color:var(--primary); }
    .stat-label { font-size:.75rem; color:var(--muted); margin-top:.2rem; }
    .user-row { display:flex; align-items:center; gap:.75rem; padding:.75rem; border-radius:calc(var(--radius) - 2px); transition:background .15s; cursor:pointer; }
    .user-row:hover { background:var(--secondary); }
    .user-avatar { width:2rem; height:2rem; border-radius:50%; background:hsl(0 85% 55%/.1); border:1px solid hsl(0 85% 55%/.2); display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:600; flex-shrink:0; color:var(--primary); font-family:var(--font-d); }
    .user-info { flex:1; min-width:0; }
    .user-name { font-size:.875rem; font-weight:500; }
    .user-email { font-size:.75rem; color:var(--muted); }
    .user-badges { display:flex; gap:.3rem; flex-wrap:wrap; }
    .badge { font-size:.6rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; padding:.15rem .45rem; border-radius:.3rem; }
    .badge-admin { background:hsl(0 85% 55%/.15); color:var(--primary); border:1px solid hsl(0 85% 55%/.3); }
    .badge-blocked { background:hsl(0 0% 50%/.15); color:var(--muted); border:1px solid hsl(0 0% 50%/.2); }
    .admin-tabs { display:flex; gap:.25rem; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:.3rem; margin-bottom:1.5rem; }
    .admin-tab { flex:1; padding:.45rem .5rem; border-radius:calc(var(--radius) - 4px); border:none; background:transparent; color:var(--muted); font-family:var(--font-d); font-size:.6rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; cursor:pointer; transition:background .15s, color .15s; }
    .admin-tab.active { background:var(--secondary); color:var(--fg); }

    /* Modal */
    .modal-overlay { position:fixed; inset:0; background:hsl(220 20% 4%/.8); backdrop-filter:blur(4px); z-index:100; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .modal { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; width:100%; max-width:28rem; max-height:85vh; overflow-y:auto; animation:slideIn .2s ease; }
    .modal-title { font-family:var(--font-d); font-size:.85rem; font-weight:700; letter-spacing:.1em; margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between; }

    /* Empty state */
    .empty-state { text-align:center; padding:5rem 1rem; }
    .empty-icon-wrap { position:relative; width:6rem; height:6rem; margin:0 auto 1.5rem; }
    .empty-icon-bg { position:absolute; inset:0; border-radius:1rem; background:hsl(0 85% 55%/.05); border:1px solid hsl(0 85% 55%/.1); filter:blur(4px); }
    .empty-icon-box { position:relative; width:6rem; height:6rem; border-radius:1rem; background:var(--secondary); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; }
    .empty-state h2 { font-weight:600; font-size:1.1rem; margin-bottom:.5rem; }
    .empty-state p { color:var(--muted); font-size:.875rem; max-width:20rem; margin:0 auto 1.5rem; }

    /* Skeleton */
    .skeleton { background:var(--secondary); border-radius:.5rem; animation:pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

    /* Toast */
    #toast-container { position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999; display:flex; flex-direction:column; gap:.5rem; }
    .toast { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:.75rem 1.1rem; font-size:.875rem; box-shadow:0 8px 24px rgba(0,0,0,.4); animation:toastIn .2s ease; max-width:22rem; }
    .toast.error { border-color:hsl(0 84% 60%/.4); }
    .toast.success { border-color:hsl(142 70% 45%/.4); }
    .toast-title { font-weight:600; margin-bottom:.1rem; }
    .toast-desc  { color:var(--muted); font-size:.8rem; }
    @keyframes toastIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

    svg { display:inline-block; vertical-align:middle; }
  </style>
</head>
<body>

<div id="page-loading"><div class="spinner"></div></div>
<div id="toast-container"></div>

<!-- ═══════════════════════════════════════ AUTH PAGE -->
<div id="auth-page" class="hidden">
  <div class="auth-hero">
    <div class="auth-hero-blob1"></div>
    <div class="auth-hero-blob2"></div>
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <span style="font-family:var(--font-d);font-weight:700;font-size:.9rem;letter-spacing:.1em">ABRE JÁ</span>
    </div>
    <div class="auth-hero-body">
      <div class="auth-hero-title">Os teus portões,<br/><span style="color:var(--primary)">sempre contigo.</span></div>
      <div class="auth-hero-desc">Gere os teus portões e carros num só lugar. Acesso rápido e seguro a qualquer momento.</div>
    </div>
    <div style="font-size:.7rem;color:var(--muted)">© 2025 Abre Já</div>
  </div>
  <div class="auth-form-panel">
    <div class="auth-form-inner">
      <div class="auth-mobile-logo">
        <div class="auth-mobile-logo-icon">
          <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <div style="font-family:var(--font-d);font-weight:700;font-size:1rem;letter-spacing:.1em">ABRE JÁ</div>
      </div>

      <!-- Login form -->
      <div id="login-form">
        <div class="auth-form-title">Entrar</div>
        <div class="auth-form-sub">Acede à tua conta</div>
        <div id="login-err" class="auth-err hidden"></div>
        <div class="form-group">
          <label class="label">Email</label>
          <input id="inp-email" class="input" type="email" placeholder="email@exemplo.com" />
        </div>
        <div class="form-group">
          <label class="label">Password</label>
          <input id="inp-password" class="input" type="password" placeholder="••••••••" />
          <span class="forgot-link" id="btn-show-forgot">Esqueceste a password?</span>
        </div>
        <button id="auth-submit" class="btn btn-primary btn-full">Entrar</button>
        <div class="auth-toggle">Não tens conta? <button id="btn-toggle-auth">Registar</button></div>
      </div>

      <!-- Register form -->
      <div id="register-form" class="hidden">
        <div class="auth-form-title">Criar Conta</div>
        <div class="auth-form-sub">Regista-te gratuitamente</div>
        <div id="register-err" class="auth-err hidden"></div>
        <div class="form-group">
          <label class="label">Nome (opcional)</label>
          <input id="inp-name" class="input" type="text" placeholder="O teu nome" maxlength="100" />
        </div>
        <div class="form-group">
          <label class="label">Email</label>
          <input id="inp-reg-email" class="input" type="email" placeholder="email@exemplo.com" />
        </div>
        <div class="form-group">
          <label class="label">Password</label>
          <input id="inp-reg-password" class="input" type="password" placeholder="Mínimo 6 caracteres" />
        </div>
        <button id="register-submit" class="btn btn-primary btn-full">Criar Conta</button>
        <div class="auth-toggle">Já tens conta? <button id="btn-toggle-login">Entrar</button></div>
      </div>

      <!-- Forgot password form -->
      <div id="forgot-form" class="hidden">
        <div class="auth-form-title">Recuperar Password</div>
        <div class="auth-form-sub">Insere o teu email para receberes um link</div>
        <div id="forgot-err" class="auth-err hidden"></div>
        <div id="forgot-ok" class="hidden" style="background:hsl(142 70% 45%/.1);border:1px solid hsl(142 70% 45%/.3);border-radius:calc(var(--radius) - 2px);padding:.6rem .9rem;font-size:.85rem;color:var(--success);margin-bottom:1rem"></div>
        <div class="form-group">
          <label class="label">Email</label>
          <input id="inp-forgot-email" class="input" type="email" placeholder="email@exemplo.com" />
        </div>
        <button id="forgot-submit" class="btn btn-primary btn-full">Enviar Link</button>
        <div class="auth-toggle"><button id="btn-back-login">← Voltar ao login</button></div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════ APP PAGE -->
<div id="app-page" class="hidden">
  <header class="app-header">
    <div class="header-inner">
      <div class="flex items-center gap-2">
        <div class="header-logo-icon">
          <img src="logo.png" style="width:22px;height:22px;object-fit:contain" />
        </div>
        <div>
          <div class="header-title">ABRE JÁ</div>
          <div id="header-sub-label" class="header-sub">—</div>
        </div>
      </div>
      <div class="header-actions">
        <button id="btn-admin-panel" class="btn btn-warning hidden" style="height:2rem;font-size:.65rem;padding:0 .6rem">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14l-5-4.87 6.91-1.01z"/></svg>
          Admin
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
    <nav class="nav-tabs">
      <button class="nav-tab active" data-tab="cars">🚗 Carros</button>
      <button class="nav-tab" data-tab="gates">🚪 Portões</button>
    </nav>

    <!-- CARS TAB -->
    <div id="tab-cars">
      <div class="section-header">
        <span id="car-count-label" class="section-title-text">—</span>
        <button id="btn-add-car" class="btn btn-primary" style="height:2rem;font-size:.7rem;padding:0 .75rem">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Adicionar
        </button>
      </div>

      <div id="car-form-wrapper" class="form-card hidden">
        <div class="form-header">
          <h3 id="form-title">Novo Carro</h3>
          <button class="close-btn" id="btn-close-form">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="form-group">
          <label class="label">Matrícula</label>
          <input id="inp-plate" class="input input-plate" type="text" placeholder="AA-00-AA" maxlength="8" />
          <div class="input-hint"><span id="plate-count">0</span>/8</div>
        </div>
        <div class="form-group">
          <label class="label">Marca</label>
          <div class="brand-select-wrap">
            <button type="button" id="brand-combobox" class="brand-combobox">
              <span class="brand-combobox-left" id="brand-combobox-label"><span style="color:var(--muted)">Pesquisar marca...</span></span>
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div id="brand-dropdown" class="brand-dropdown hidden">
              <input id="brand-search" class="brand-search" type="text" placeholder="Pesquisar marca..." />
              <div id="brand-list" class="brand-list"></div>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="label">Cor</label>
          <div id="color-grid" class="color-grid"></div>
          <div id="color-name" class="color-name"></div>
        </div>
        <button id="btn-form-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">Adicionar</button>
      </div>

      <div id="cars-loading" class="hidden">
        <div class="skeleton" style="height:4rem;border-radius:var(--radius);margin-bottom:.75rem"></div>
        <div class="skeleton" style="height:4rem;border-radius:var(--radius);margin-bottom:.75rem"></div>
      </div>
      <div id="cars-empty" class="empty-state hidden">
        <div class="empty-icon-wrap"><div class="empty-icon-bg"></div><div class="empty-icon-box">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5"/><circle cx="16" cy="19" r="2"/><circle cx="7" cy="19" r="2"/><path d="M3 15h12v2"/></svg>
        </div></div>
        <h2>Sem carros</h2>
        <p>Adiciona o teu primeiro veículo.</p>
        <button class="btn btn-primary" id="btn-add-car-empty">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Adicionar Carro
        </button>
      </div>
      <div id="cars-list"></div>
    </div>

    <!-- GATES TAB -->
    <div id="tab-gates" class="hidden">
      <div class="section-header">
        <span id="gate-count-label" class="section-title-text">—</span>
        <button id="btn-add-gate" class="btn btn-primary" style="height:2rem;font-size:.7rem;padding:0 .75rem">
          <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Portão
        </button>
      </div>

      <div id="gate-form-wrapper" class="form-card hidden">
        <div class="form-header">
          <h3 id="gate-form-title">Novo Portão</h3>
          <button class="close-btn" id="btn-close-gate-form">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="form-group">
          <label class="label">Nome do Portão</label>
          <input id="inp-gate-name" class="input" type="text" placeholder="Ex: Portão Principal" maxlength="60" />
          <div class="input-hint"><span id="gate-name-count">0</span>/60</div>
        </div>
        <div class="form-group">
          <label class="label">ID do Relé</label>
          <input id="inp-gate-relay" class="input" style="font-family:monospace" type="text" placeholder="Ex: relay_01 ou 192.168.1.100" maxlength="100" />
          <div class="input-hint" style="text-align:left">Identificador único do dispositivo físico</div>
        </div>
        <div class="form-group">
          <label class="label">Ícone</label>
          <div id="gate-icon-grid" class="icon-grid"></div>
        </div>
        <button id="btn-gate-form-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">Adicionar</button>
      </div>

      <div id="gates-loading" class="hidden">
        <div class="skeleton" style="height:4rem;border-radius:var(--radius);margin-bottom:.75rem"></div>
      </div>
      <div id="gates-empty" class="empty-state hidden">
        <div class="empty-icon-wrap"><div class="empty-icon-bg"></div><div class="empty-icon-box">
          <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
        </div></div>
        <h2>Sem portões</h2>
        <p>Adiciona o teu primeiro portão.</p>
        <button class="btn btn-primary" id="btn-add-gate-empty">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Novo Portão
        </button>
      </div>
      <div id="gates-list"></div>
    </div>
  </main>
</div>

<!-- ═══════════════════════════════════════ PROFILE PAGE -->
<div id="profile-page" class="hidden">
  <div class="profile-header">
    <div class="profile-header-inner">
      <button id="btn-back-profile" class="btn btn-ghost btn-icon">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <div><div class="header-title">Perfil</div><div class="header-sub">Gerir conta</div></div>
    </div>
  </div>
  <div class="profile-main">
    <!-- Avatar + info -->
    <div style="display:flex;align-items:center;gap:1.25rem;padding:1.25rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.5rem">
      <div class="profile-avatar">
        <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--primary)"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <div>
        <div id="profile-name-display" style="font-weight:600"></div>
        <div id="profile-email-display" style="font-size:.8rem;color:var(--muted);margin-top:.2rem"></div>
      </div>
    </div>

    <!-- Informações -->
    <div class="section-card">
      <div class="section-card-title">Informações</div>
      <div class="form-group">
        <label class="label">Nome</label>
        <input id="profile-name-inp" class="input" type="text" placeholder="O teu nome" maxlength="100" />
      </div>
      <div class="form-group">
        <label class="label">Email</label>
        <input id="profile-email-inp" class="input" type="email" disabled style="opacity:.5;cursor:not-allowed" />
        <div class="input-hint" style="text-align:left;margin-top:.35rem">O email não pode ser alterado</div>
      </div>
      <button id="btn-save-profile" class="btn btn-primary btn-full">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Guardar Alterações
      </button>
    </div>

    <!-- Alterar password -->
    <div class="section-card">
      <div class="section-card-title">Alterar Password</div>
      <div class="form-group">
        <label class="label">Password Atual</label>
        <input id="inp-pw-current" class="input" type="password" placeholder="••••••••" />
      </div>
      <div class="form-group">
        <label class="label">Nova Password</label>
        <input id="inp-pw-new" class="input" type="password" placeholder="Mínimo 6 caracteres" />
      </div>
      <div class="form-group">
        <label class="label">Confirmar Nova Password</label>
        <input id="inp-pw-confirm" class="input" type="password" placeholder="Repete a nova password" />
      </div>
      <button id="btn-change-password" class="btn btn-primary btn-full">Alterar Password</button>
    </div>

    <!-- Zona de perigo -->
    <div class="section-card" style="border-color:hsl(0 84% 60%/.2)">
      <div class="section-card-title" style="color:var(--destructive)">Zona de Perigo</div>
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem">Terminar sessão em todos os dispositivos.</p>
      <button id="btn-logout-profile" class="btn btn-full" style="background:hsl(0 84% 60%/.1);color:var(--destructive);border:1px solid hsl(0 84% 60%/.3)">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Terminar Sessão
      </button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════ ADMIN PAGE -->
<div id="admin-page" class="hidden">
  <div class="admin-header">
    <div class="header-inner" style="max-width:60rem">
      <div class="flex items-center gap-2">
        <button id="btn-back-admin" class="btn btn-ghost btn-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div>
          <div class="header-title" style="color:var(--warning)">⭐ Painel Admin</div>
          <div class="header-sub">Gestão do sistema</div>
        </div>
      </div>
    </div>
  </div>
  <div class="admin-main">
    <div id="admin-stats" class="stat-grid">
      <div class="stat-card"><div class="stat-value" id="stat-users">—</div><div class="stat-label">Utilizadores</div></div>
      <div class="stat-card"><div class="stat-value" id="stat-cars">—</div><div class="stat-label">Carros</div></div>
      <div class="stat-card"><div class="stat-value" id="stat-gates">—</div><div class="stat-label">Portões</div></div>
      <div class="stat-card"><div class="stat-value" id="stat-blocked">—</div><div class="stat-label">Bloqueados</div></div>
    </div>

    <nav class="admin-tabs">
      <button class="admin-tab active" data-atab="users">👥 Utilizadores</button>
      <button class="admin-tab" data-atab="log">📋 Histórico</button>
    </nav>

    <!-- Users list -->
    <div id="atab-users">
      <div id="admin-users-loading" class="skeleton" style="height:8rem;border-radius:var(--radius)"></div>
      <div id="admin-users-list"></div>
    </div>

    <!-- Access log -->
    <div id="atab-log" class="hidden">
      <div id="admin-log-loading" class="skeleton" style="height:8rem;border-radius:var(--radius)"></div>
      <div class="section-card" style="padding:0;overflow:hidden">
        <div id="admin-log-list"></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: user detail (admin) -->
<div id="modal-user-detail" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-title">
      <span id="modal-user-title">Utilizador</span>
      <button class="close-btn" id="modal-user-close">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="modal-user-body"></div>
    <div id="modal-user-actions" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.25rem"></div>
  </div>
</div>

<!-- Modal: gate access log -->
<div id="modal-gate-log" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-title">
      <span id="modal-gate-log-title">Histórico</span>
      <button class="close-btn" id="modal-gate-log-close">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="modal-gate-log-body"></div>
  </div>
</div>

<script>
// ═══════════════════════════════════════════
//  DATA
// ═══════════════════════════════════════════
const CAR_COLORS = [
  {name:"Preto",value:"#111111"},{name:"Preto Mate",value:"#2a2a2a"},
  {name:"Branco",value:"#f5f5f5"},{name:"Branco Pérola",value:"#f0ece4"},
  {name:"Cinzento",value:"#808080"},{name:"Cinzento Escuro",value:"#4a4a4a"},
  {name:"Prata",value:"#c0c0c0"},{name:"Champanhe",value:"#c9a96e"},
  {name:"Dourado",value:"#d4a017"},{name:"Vermelho",value:"#dc2626"},
  {name:"Vermelho Escuro",value:"#8b0000"},{name:"Bordeaux",value:"#722f37"},
  {name:"Laranja",value:"#ea580c"},{name:"Amarelo",value:"#eab308"},
  {name:"Verde",value:"#16a34a"},{name:"Verde Escuro",value:"#14532d"},
  {name:"Turquesa",value:"#0d9488"},{name:"Azul Claro",value:"#38bdf8"},
  {name:"Azul",value:"#2563eb"},{name:"Azul Escuro",value:"#1e3a5f"},
  {name:"Roxo",value:"#7c3aed"},{name:"Rosa",value:"#db2777"},
  {name:"Castanho",value:"#92400e"},{name:"Bege",value:"#d4c5a9"},
];
const CAR_BRANDS = ["Abarth","Alfa Romeo","Alpine","Aston Martin","Audi","Bentley","BMW","Bugatti","Cadillac","Chevrolet","Chrysler","Citroën","Cupra","Dacia","Dodge","DS Automobiles","Ferrari","Fiat","Ford","Genesis","Honda","Hyundai","Infiniti","Jaguar","Jeep","Kia","Lamborghini","Land Rover","Lexus","Maserati","Mazda","McLaren","Mercedes-Benz","Mini","Mitsubishi","Nissan","Opel","Pagani","Peugeot","Polestar","Porsche","RAM","Renault","Rolls-Royce","Saab","SEAT","Škoda","Smart","Subaru","Suzuki","Tesla","Toyota","Volkswagen","Volvo"].sort((a,b)=>a.localeCompare(b));
const CDN = "https://cdn.jsdelivr.net/gh/filippofilip95/car-logos-ds@latest/logos/optimized";
const BRAND_LOGOS = {"Alfa Romeo":"alfa-romeo","Aston Martin":"aston-martin","Audi":"audi","Bentley":"bentley","BMW":"bmw","Bugatti":"bugatti","Cadillac":"cadillac","Chevrolet":"chevrolet","Chrysler":"chrysler","Citroën":"citroen","Cupra":"cupra","Dacia":"dacia","Dodge":"dodge","Ferrari":"ferrari","Fiat":"fiat","Ford":"ford","Genesis":"genesis","Honda":"honda","Hyundai":"hyundai","Infiniti":"infiniti","Jaguar":"jaguar","Jeep":"jeep","Kia":"kia","Lamborghini":"lamborghini","Land Rover":"land-rover","Lexus":"lexus","Maserati":"maserati","Mazda":"mazda","McLaren":"mclaren","Mercedes-Benz":"mercedes","Mini":"mini","Mitsubishi":"mitsubishi","Nissan":"nissan","Opel":"opel","Peugeot":"peugeot","Polestar":"polestar","Porsche":"porsche","Renault":"renault","Rolls-Royce":"rolls-royce","SEAT":"seat","Škoda":"skoda","Smart":"smart","Subaru":"subaru","Suzuki":"suzuki","Tesla":"tesla","Toyota":"toyota","Volkswagen":"volkswagen","Volvo":"volvo"};
const getBrandLogo = b => BRAND_LOGOS[b] ? `${CDN}/${BRAND_LOGOS[b]}.svg` : null;
const GATE_ICONS = ['🏠','🏢','🏭','🏪','🏫','🚗','🚪','🔑','🔒','🛡️','📡','⚙️','🔧','💡','🌿','🏗️'];

// ═══════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════
let currentUser = null;
let cars = [], gates = [];
let editingCarId = null, editingGateId = null;
let selectedBrand = '', selectedColor = CAR_COLORS[0].value;
let selectedGateIcon = '🏠';
let brandDropdownOpen = false;

// ═══════════════════════════════════════════
//  API
// ═══════════════════════════════════════════
async function api(method, url, body) {
  const opts = { method, headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const res  = await fetch(url, opts);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Erro desconhecido');
  return data;
}

// ═══════════════════════════════════════════
//  TOAST
// ═══════════════════════════════════════════
function toast(title, desc = '', type = '') {
  const el = document.createElement('div');
  el.className = 'toast' + (type ? ' ' + type : '');
  el.innerHTML = `<div class="toast-title">${title}</div>${desc ? `<div class="toast-desc">${desc}</div>` : ''}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ═══════════════════════════════════════════
//  PAGES
// ═══════════════════════════════════════════
function showPage(name) {
  document.getElementById('page-loading').classList.add('hidden');
  ['auth-page','app-page','profile-page','admin-page'].forEach(id =>
    document.getElementById(id).classList.toggle('hidden', id !== name + '-page')
  );
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('pt-PT', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

// ═══════════════════════════════════════════
//  AUTH
// ═══════════════════════════════════════════
function showAuthForm(form) {
  ['login-form','register-form','forgot-form'].forEach(id =>
    document.getElementById(id).classList.toggle('hidden', id !== form)
  );
}

document.getElementById('btn-toggle-auth').onclick  = () => showAuthForm('register-form');
document.getElementById('btn-toggle-login').onclick = () => showAuthForm('login-form');
document.getElementById('btn-show-forgot').onclick  = () => showAuthForm('forgot-form');
document.getElementById('btn-back-login').onclick   = () => showAuthForm('login-form');

document.getElementById('auth-submit').onclick = async () => {
  const email    = document.getElementById('inp-email').value.trim();
  const password = document.getElementById('inp-password').value;
  const err      = document.getElementById('login-err');
  err.classList.add('hidden');
  const btn = document.getElementById('auth-submit');
  btn.disabled = true; btn.textContent = 'A entrar...';
  try {
    currentUser = await api('POST', 'api/auth.php?action=login', { email, password });
    await loadAll();
    showPage('app');
  } catch (e) {
    err.textContent = e.message;
    err.classList.remove('hidden');
  } finally { btn.disabled = false; btn.textContent = 'Entrar'; }
};

document.getElementById('register-submit').onclick = async () => {
  const displayName = document.getElementById('inp-name').value.trim();
  const email       = document.getElementById('inp-reg-email').value.trim();
  const password    = document.getElementById('inp-reg-password').value;
  const err         = document.getElementById('register-err');
  err.classList.add('hidden');
  const btn = document.getElementById('register-submit');
  btn.disabled = true; btn.textContent = 'A criar...';
  try {
    currentUser = await api('POST', 'api/auth.php?action=register', { email, password, displayName });
    await loadAll();
    showPage('app');
  } catch (e) {
    err.textContent = e.message;
    err.classList.remove('hidden');
  } finally { btn.disabled = false; btn.textContent = 'Criar Conta'; }
};

document.getElementById('forgot-submit').onclick = async () => {
  const email = document.getElementById('inp-forgot-email').value.trim();
  const err   = document.getElementById('forgot-err');
  const ok    = document.getElementById('forgot-ok');
  err.classList.add('hidden'); ok.classList.add('hidden');
  const btn = document.getElementById('forgot-submit');
  btn.disabled = true; btn.textContent = 'A enviar...';
  try {
    await api('POST', 'api/auth.php?action=forgot', { email });
    ok.textContent = 'Se o email existir, receberás um link em breve.';
    ok.classList.remove('hidden');
  } catch (e) {
    err.textContent = e.message;
    err.classList.remove('hidden');
  } finally { btn.disabled = false; btn.textContent = 'Enviar Link'; }
};

['inp-email','inp-password'].forEach(id =>
  document.getElementById(id).addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('auth-submit').click(); })
);

// ═══════════════════════════════════════════
//  LOGOUT
// ═══════════════════════════════════════════
async function doLogout() {
  await api('POST', 'api/auth.php?action=logout').catch(() => {});
  currentUser = null; cars = []; gates = [];
  showPage('auth');
}
document.getElementById('btn-logout').onclick         = doLogout;
document.getElementById('btn-logout-profile').onclick = doLogout;

// ═══════════════════════════════════════════
//  TABS (app)
// ═══════════════════════════════════════════
document.querySelectorAll('.nav-tab').forEach(tab => {
  tab.onclick = () => {
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const name = tab.dataset.tab;
    document.getElementById('tab-cars').classList.toggle('hidden', name !== 'cars');
    document.getElementById('tab-gates').classList.toggle('hidden', name !== 'gates');
    document.getElementById('header-sub-label').textContent = name === 'cars'
      ? `${cars.length} ${cars.length===1?'veículo':'veículos'}`
      : `${gates.length} ${gates.length===1?'portão':'portões'}`;
  };
});

// ═══════════════════════════════════════════
//  PROFILE
// ═══════════════════════════════════════════
document.getElementById('btn-profile').onclick = () => {
  document.getElementById('profile-name-display').textContent  = currentUser.displayName || 'Sem nome';
  document.getElementById('profile-email-display').textContent = currentUser.email;
  document.getElementById('profile-name-inp').value   = currentUser.displayName || '';
  document.getElementById('profile-email-inp').value  = currentUser.email;
  document.getElementById('inp-pw-current').value = '';
  document.getElementById('inp-pw-new').value     = '';
  document.getElementById('inp-pw-confirm').value = '';
  showPage('profile');
};
document.getElementById('btn-back-profile').onclick = () => showPage('app');

document.getElementById('btn-save-profile').onclick = async () => {
  const name = document.getElementById('profile-name-inp').value.trim();
  const btn  = document.getElementById('btn-save-profile');
  btn.disabled = true;
  try {
    await api('PUT', 'api/auth.php?action=profile', { displayName: name });
    currentUser.displayName = name;
    document.getElementById('profile-name-display').textContent = name || 'Sem nome';
    toast('Perfil atualizado!', '', 'success');
  } catch (e) { toast('Erro', e.message, 'error'); }
  finally { btn.disabled = false; }
};

document.getElementById('btn-change-password').onclick = async () => {
  const current = document.getElementById('inp-pw-current').value;
  const nw      = document.getElementById('inp-pw-new').value;
  const confirm = document.getElementById('inp-pw-confirm').value;
  if (nw !== confirm) { toast('As passwords não coincidem', '', 'error'); return; }
  const btn = document.getElementById('btn-change-password');
  btn.disabled = true;
  try {
    await api('PUT', 'api/auth.php?action=password', { current, new: nw });
    document.getElementById('inp-pw-current').value = '';
    document.getElementById('inp-pw-new').value     = '';
    document.getElementById('inp-pw-confirm').value = '';
    toast('Password alterada!', '', 'success');
  } catch (e) { toast('Erro', e.message, 'error'); }
  finally { btn.disabled = false; }
};

// ═══════════════════════════════════════════
//  COLOR PICKER
// ═══════════════════════════════════════════
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
      document.querySelectorAll('#color-grid .color-swatch').forEach(s => s.classList.remove('selected'));
      btn.classList.add('selected');
      document.getElementById('color-name').textContent = c.name;
    };
    grid.appendChild(btn);
  });
  document.getElementById('color-name').textContent = CAR_COLORS.find(c=>c.value===selectedColor)?.name || '';
}

// ═══════════════════════════════════════════
//  BRAND COMBOBOX
// ═══════════════════════════════════════════
function setBrand(brand) {
  selectedBrand = brand;
  const logo  = getBrandLogo(brand);
  const label = document.getElementById('brand-combobox-label');
  label.innerHTML = brand
    ? `${logo ? `<img src="${logo}" alt="${brand}" onerror="this.style.display='none'"/>` : ''}<span>${brand}</span>`
    : `<span style="color:var(--muted)">Pesquisar marca...</span>`;
}
function renderBrandList(filter) {
  const list  = document.getElementById('brand-list');
  const items = CAR_BRANDS.filter(b => b.toLowerCase().includes(filter.toLowerCase()));
  if (!items.length) { list.innerHTML = `<div class="brand-empty">Marca não encontrada.</div>`; return; }
  list.innerHTML = items.map(b => {
    const logo = getBrandLogo(b);
    return `<div class="brand-item${b===selectedBrand?' active':''}" data-brand="${b}">${logo ? `<img src="${logo}" alt="${b}" onerror="this.style.display='none'"/>` : ''}<span>${b}</span></div>`;
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
document.getElementById('brand-search').oninput   = e => renderBrandList(e.target.value);
document.addEventListener('click', e => {
  if (brandDropdownOpen && !document.querySelector('.brand-select-wrap').contains(e.target)) closeBrandDropdown();
});

// ═══════════════════════════════════════════
//  CAR FORM
// ═══════════════════════════════════════════
function openCarForm(car) {
  editingCarId  = car ? car.id : null;
  selectedBrand = car ? car.brand : '';
  selectedColor = car ? car.color : CAR_COLORS[0].value;
  document.getElementById('form-title').textContent      = car ? 'Editar Carro' : 'Novo Carro';
  document.getElementById('inp-plate').value             = car ? car.plate : '';
  document.getElementById('plate-count').textContent     = car ? car.plate.length : 0;
  document.getElementById('btn-form-submit').textContent = car ? 'Guardar' : 'Adicionar';
  setBrand(selectedBrand);
  buildColorGrid();
  document.getElementById('car-form-wrapper').classList.remove('hidden');
  document.getElementById('btn-add-car').classList.add('hidden');
  document.getElementById('cars-empty').classList.add('hidden');
  document.getElementById('inp-plate').focus();
}
function closeCarForm() {
  editingCarId = null;
  document.getElementById('car-form-wrapper').classList.add('hidden');
  document.getElementById('btn-add-car').classList.remove('hidden');
  renderCars();
}
document.getElementById('inp-plate').oninput = e => {
  e.target.value = e.target.value.replace(/[^a-zA-Z0-9-]/g,'').toUpperCase();
  document.getElementById('plate-count').textContent = e.target.value.length;
};
document.getElementById('btn-add-car').onclick       = () => openCarForm(null);
document.getElementById('btn-add-car-empty').onclick = () => openCarForm(null);
document.getElementById('btn-close-form').onclick    = closeCarForm;

document.getElementById('btn-form-submit').onclick = async () => {
  const plate = document.getElementById('inp-plate').value.trim().toUpperCase();
  if (!plate || plate.length > 8) { toast('Matrícula inválida','Máx. 8 caracteres','error'); return; }
  if (!selectedBrand)             { toast('Marca obrigatória','','error'); return; }
  const btn = document.getElementById('btn-form-submit');
  btn.disabled = true; btn.textContent = 'A guardar...';
  try {
    const body = { plate, brand: selectedBrand, color: selectedColor };
    if (editingCarId) { await api('PUT', `api/cars.php?id=${editingCarId}`, body); toast('Carro atualizado!','','success'); }
    else              { await api('POST', 'api/cars.php', body); toast('Carro adicionado!','','success'); }
    await loadCars(); closeCarForm();
  } catch (e) { toast('Erro', e.message, 'error'); }
  finally { btn.disabled = false; }
};

function renderCars() {
  const list  = document.getElementById('cars-list');
  const empty = document.getElementById('cars-empty');
  const count = document.getElementById('car-count-label');
  count.textContent = `${cars.length} ${cars.length===1?'veículo':'veículos'}`;
  document.getElementById('header-sub-label').textContent = count.textContent;
  if (!cars.length) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
  empty.classList.add('hidden');
  list.innerHTML = cars.map(car => {
    const logo      = getBrandLogo(car.brand);
    const colorName = CAR_COLORS.find(c => c.value === car.color)?.name ?? 'Personalizada';
    return `<div class="car-card">
      <div class="car-card-stripe" style="background:${car.color}"></div>
      <div class="car-card-inner">
        <div class="car-brand-logo">${logo ? `<img src="${logo}" alt="${car.brand}" onerror="this.style.display='none'"/>` : `<svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5"/><circle cx="16" cy="19" r="2"/><circle cx="7" cy="19" r="2"/></svg>`}</div>
        <div class="car-info">
          <div class="car-color-dot" style="background:${car.color}"></div>
          <div style="min-width:0">
            <div class="car-plate">${car.plate}</div>
            <div class="car-sub"><span style="color:hsl(210 20% 85%/.8);font-weight:500">${car.brand}</span><span class="car-sub-sep">·</span><span>${colorName}</span></div>
          </div>
        </div>
        <div class="card-actions">
          <button class="btn btn-ghost btn-icon btn-edit-car" data-id="${car.id}" title="Editar"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="btn btn-danger btn-icon btn-delete-car" data-id="${car.id}" title="Remover"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></button>
        </div>
      </div>
    </div>`;
  }).join('');
  list.querySelectorAll('.btn-edit-car').forEach(btn => {
    btn.onclick = () => { const c = cars.find(c => c.id == btn.dataset.id); if (c) openCarForm(c); };
  });
  list.querySelectorAll('.btn-delete-car').forEach(btn => {
    btn.onclick = async () => {
      if (!confirm('Remover este carro?')) return;
      try { await api('DELETE', `api/cars.php?id=${btn.dataset.id}`); await loadCars(); toast('Carro removido.'); }
      catch (e) { toast('Erro', e.message, 'error'); }
    };
  });
}
async function loadCars() {
  document.getElementById('cars-loading').classList.remove('hidden');
  try { cars = await api('GET', 'api/cars.php'); } catch { cars = []; }
  document.getElementById('cars-loading').classList.add('hidden');
  renderCars();
}

// ═══════════════════════════════════════════
//  GATE ICON PICKER
// ═══════════════════════════════════════════
function buildGateIconGrid() {
  const grid = document.getElementById('gate-icon-grid');
  grid.innerHTML = '';
  GATE_ICONS.forEach(icon => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'icon-btn' + (icon === selectedGateIcon ? ' selected' : '');
    btn.textContent = icon;
    btn.onclick = () => {
      selectedGateIcon = icon;
      document.querySelectorAll('.icon-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
    };
    grid.appendChild(btn);
  });
}

// ═══════════════════════════════════════════
//  GATE FORM
// ═══════════════════════════════════════════
function openGateForm(gate) {
  editingGateId   = gate ? gate.id : null;
  selectedGateIcon = gate ? gate.icon : '🏠';
  document.getElementById('gate-form-title').textContent       = gate ? 'Editar Portão' : 'Novo Portão';
  document.getElementById('inp-gate-name').value               = gate ? gate.name     : '';
  document.getElementById('inp-gate-relay').value              = gate ? gate.relay_id : '';
  document.getElementById('gate-name-count').textContent       = gate ? gate.name.length : 0;
  document.getElementById('btn-gate-form-submit').textContent  = gate ? 'Guardar' : 'Adicionar';
  buildGateIconGrid();
  document.getElementById('gate-form-wrapper').classList.remove('hidden');
  document.getElementById('btn-add-gate').classList.add('hidden');
  document.getElementById('gates-empty').classList.add('hidden');
  document.getElementById('inp-gate-name').focus();
}
function closeGateForm() {
  editingGateId = null;
  document.getElementById('gate-form-wrapper').classList.add('hidden');
  document.getElementById('btn-add-gate').classList.remove('hidden');
  renderGates();
}
document.getElementById('inp-gate-name').oninput   = e => { document.getElementById('gate-name-count').textContent = e.target.value.length; };
document.getElementById('btn-add-gate').onclick    = () => openGateForm(null);
document.getElementById('btn-add-gate-empty').onclick = () => openGateForm(null);
document.getElementById('btn-close-gate-form').onclick = closeGateForm;

document.getElementById('btn-gate-form-submit').onclick = async () => {
  const name    = document.getElementById('inp-gate-name').value.trim();
  const relayId = document.getElementById('inp-gate-relay').value.trim();
  if (!name)    { toast('Nome obrigatório','','error'); return; }
  if (!relayId) { toast('ID do relé obrigatório','','error'); return; }
  const btn = document.getElementById('btn-gate-form-submit');
  btn.disabled = true; btn.textContent = 'A guardar...';
  try {
    const body = { name, relayId, icon: selectedGateIcon };
    if (editingGateId) { await api('PUT', `api/gates.php?id=${editingGateId}`, body); toast('Portão atualizado!','','success'); }
    else               { await api('POST', 'api/gates.php', body); toast('Portão adicionado!','','success'); }
    await loadGates(); closeGateForm();
  } catch (e) { toast('Erro', e.message, 'error'); }
  finally { btn.disabled = false; }
};

async function openGate(gateId, gateName) {
  try {
    await api('POST', `api/gates.php?id=${gateId}&action=open`);
    toast(`${gateName}`, 'Sinal enviado!', 'success');
  } catch (e) { toast('Erro', e.message, 'error'); }
}

async function showGateLog(gate) {
  document.getElementById('modal-gate-log-title').textContent = `${gate.icon} ${gate.name} — Histórico`;
  document.getElementById('modal-gate-log-body').innerHTML = '<div class="skeleton" style="height:4rem;border-radius:.5rem"></div>';
  document.getElementById('modal-gate-log').classList.remove('hidden');
  try {
    const rows = await api('GET', `api/gates.php?id=${gate.id}&action=log`);
    if (!rows.length) {
      document.getElementById('modal-gate-log-body').innerHTML = '<p style="color:var(--muted);font-size:.85rem;padding:.5rem 0">Sem registos de acesso ainda.</p>';
      return;
    }
    document.getElementById('modal-gate-log-body').innerHTML = `
      <div style="background:var(--secondary);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
        ${rows.map(r => `<div class="log-item">
          <div class="log-icon">🔓</div>
          <div class="log-info">
            <div>${r.users?.display_name || r.users?.email || 'Desconhecido'}</div>
            <div class="log-time">${formatDate(r.opened_at)}${r.ip_address ? ` · ${r.ip_address}` : ''}</div>
          </div>
        </div>`).join('')}
      </div>`;
  } catch (e) { document.getElementById('modal-gate-log-body').innerHTML = `<p style="color:var(--destructive)">${e.message}</p>`; }
}

document.getElementById('modal-gate-log-close').onclick = () => document.getElementById('modal-gate-log').classList.add('hidden');

function renderGates() {
  const list  = document.getElementById('gates-list');
  const empty = document.getElementById('gates-empty');
  const count = document.getElementById('gate-count-label');
  count.textContent = `${gates.length} ${gates.length===1?'portão':'portões'}`;
  if (!gates.length) { list.innerHTML = ''; empty.classList.remove('hidden'); return; }
  empty.classList.add('hidden');
  list.innerHTML = gates.map(gate => `
    <div class="gate-card">
      <div class="gate-card-inner">
        <div class="gate-icon-box">${gate.icon}</div>
        <div class="gate-info">
          <div class="gate-name">${gate.name}</div>
          <div class="gate-relay">relé: ${gate.relay_id}</div>
        </div>
        <div class="card-actions" style="opacity:1;gap:.3rem">
          <button class="btn btn-open-gate btn-icon" data-id="${gate.id}" data-name="${gate.name}" title="Abrir portão">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
            Abrir
          </button>
          <button class="btn btn-ghost btn-icon btn-log-gate" data-id="${gate.id}" title="Histórico">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </button>
          <button class="btn btn-ghost btn-icon btn-edit-gate" data-id="${gate.id}" title="Editar">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn btn-danger btn-icon btn-delete-gate" data-id="${gate.id}" title="Remover">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
          </button>
        </div>
      </div>
    </div>`).join('');

  list.querySelectorAll('.btn-open-gate').forEach(btn => {
    btn.onclick = () => openGate(btn.dataset.id, btn.dataset.name);
  });
  list.querySelectorAll('.btn-log-gate').forEach(btn => {
    btn.onclick = () => { const g = gates.find(g => g.id == btn.dataset.id); if (g) showGateLog(g); };
  });
  list.querySelectorAll('.btn-edit-gate').forEach(btn => {
    btn.onclick = () => { const g = gates.find(g => g.id == btn.dataset.id); if (g) openGateForm(g); };
  });
  list.querySelectorAll('.btn-delete-gate').forEach(btn => {
    btn.onclick = async () => {
      if (!confirm('Remover este portão?')) return;
      try { await api('DELETE', `api/gates.php?id=${btn.dataset.id}`); await loadGates(); toast('Portão removido.'); }
      catch (e) { toast('Erro', e.message, 'error'); }
    };
  });
}
async function loadGates() {
  document.getElementById('gates-loading').classList.remove('hidden');
  try { gates = await api('GET', 'api/gates.php'); } catch { gates = []; }
  document.getElementById('gates-loading').classList.add('hidden');
  renderGates();
}

// ═══════════════════════════════════════════
//  ADMIN
// ═══════════════════════════════════════════
document.getElementById('btn-admin-panel').onclick = async () => {
  showPage('admin');
  await loadAdminStats();
  await loadAdminUsers();
};
document.getElementById('btn-back-admin').onclick = () => showPage('app');

document.querySelectorAll('.admin-tab').forEach(tab => {
  tab.onclick = async () => {
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const name = tab.dataset.atab;
    document.getElementById('atab-users').classList.toggle('hidden', name !== 'users');
    document.getElementById('atab-log').classList.toggle('hidden', name !== 'log');
    if (name === 'log') await loadAdminLog();
  };
});

async function loadAdminStats() {
  try {
    const s = await api('GET', 'api/admin.php?action=stats');
    document.getElementById('stat-users').textContent   = s.totalUsers;
    document.getElementById('stat-cars').textContent    = s.totalCars;
    document.getElementById('stat-gates').textContent   = s.totalGates;
    document.getElementById('stat-blocked').textContent = s.totalBlocked;
  } catch {}
}

async function loadAdminUsers() {
  document.getElementById('admin-users-loading').classList.remove('hidden');
  document.getElementById('admin-users-list').innerHTML = '';
  try {
    const users = await api('GET', 'api/admin.php?action=users');
    document.getElementById('admin-users-loading').classList.add('hidden');
    if (!users.length) {
      document.getElementById('admin-users-list').innerHTML = '<p style="color:var(--muted);padding:1rem">Sem utilizadores.</p>';
      return;
    }
    document.getElementById('admin-users-list').innerHTML = `
      <div class="section-card" style="padding:.5rem">
        ${users.map(u => `
        <div class="user-row" data-uid="${u.id}">
          <div class="user-avatar">${(u.displayName || u.email)[0].toUpperCase()}</div>
          <div class="user-info">
            <div class="user-name">${u.displayName || '—'}</div>
            <div class="user-email">${u.email}</div>
          </div>
          <div class="user-badges">
            ${u.isAdmin   ? '<span class="badge badge-admin">Admin</span>' : ''}
            ${u.isBlocked ? '<span class="badge badge-blocked">Bloqueado</span>' : ''}
          </div>
        </div>`).join('')}
      </div>`;

    document.querySelectorAll('.user-row').forEach(row => {
      row.onclick = () => showUserDetail(parseInt(row.dataset.uid), users);
    });
  } catch (e) {
    document.getElementById('admin-users-loading').classList.add('hidden');
    toast('Erro a carregar utilizadores', e.message, 'error');
  }
}

async function showUserDetail(uid, users) {
  const u = users.find(u => u.id === uid);
  if (!u) return;
  document.getElementById('modal-user-title').textContent = u.displayName || u.email;
  document.getElementById('modal-user-body').innerHTML = '<div class="skeleton" style="height:4rem;border-radius:.5rem"></div>';
  document.getElementById('modal-user-actions').innerHTML = '';
  document.getElementById('modal-user-detail').classList.remove('hidden');

  try {
    const detail = await api('GET', `api/admin.php?action=user-detail&id=${uid}`);
    document.getElementById('modal-user-body').innerHTML = `
      <div style="font-size:.82rem;color:var(--muted);margin-bottom:1rem">
        <div>📧 ${u.email}</div>
        <div style="margin-top:.25rem">📅 Registado em ${formatDate(detail.user.created_at)}</div>
      </div>
      <div style="display:flex;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap">
        <div style="background:var(--secondary);border:1px solid var(--border);border-radius:.5rem;padding:.6rem .9rem;font-size:.8rem">
          🚗 <strong>${detail.cars.length}</strong> ${detail.cars.length===1?'carro':'carros'}
        </div>
        <div style="background:var(--secondary);border:1px solid var(--border);border-radius:.5rem;padding:.6rem .9rem;font-size:.8rem">
          🚪 <strong>${detail.gates.length}</strong> ${detail.gates.length===1?'portão':'portões'}
        </div>
      </div>
      ${detail.cars.length ? `<div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.4rem">Carros</div>
      <div style="margin-bottom:.75rem">${detail.cars.map(c=>`<div style="font-size:.8rem;padding:.3rem 0;border-bottom:1px solid var(--border)">${c.plate} · ${c.brand}</div>`).join('')}</div>` : ''}
      ${detail.gates.length ? `<div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.4rem">Portões</div>
      <div>${detail.gates.map(g=>`<div style="font-size:.8rem;padding:.3rem 0;border-bottom:1px solid var(--border)">${g.icon} ${g.name} · <span style="font-family:monospace">${g.relay_id}</span></div>`).join('')}</div>` : ''}
    `;

    const actions = document.getElementById('modal-user-actions');
    if (u.id !== currentUser.id) {
      // Block / Unblock
      if (u.isBlocked) {
        const btnUnblock = document.createElement('button');
        btnUnblock.className = 'btn btn-success';
        btnUnblock.innerHTML = '✓ Desbloquear';
        btnUnblock.onclick = async () => {
          try { await api('DELETE', `api/admin.php?action=block&id=${uid}`); toast('Utilizador desbloqueado','','success'); document.getElementById('modal-user-detail').classList.add('hidden'); await loadAdminUsers(); await loadAdminStats(); }
          catch (e) { toast('Erro', e.message, 'error'); }
        };
        actions.appendChild(btnUnblock);
      } else {
        const btnBlock = document.createElement('button');
        btnBlock.className = 'btn btn-warning';
        btnBlock.innerHTML = '🚫 Bloquear';
        btnBlock.onclick = async () => {
          const reason = prompt('Motivo do bloqueio (opcional):');
          try { await api('POST', `api/admin.php?action=block&id=${uid}`, { reason }); toast('Utilizador bloqueado','','success'); document.getElementById('modal-user-detail').classList.add('hidden'); await loadAdminUsers(); await loadAdminStats(); }
          catch (e) { toast('Erro', e.message, 'error'); }
        };
        actions.appendChild(btnBlock);
      }

      // Toggle admin
      const btnAdmin = document.createElement('button');
      btnAdmin.className = 'btn btn-ghost';
      btnAdmin.innerHTML = u.isAdmin ? '⬇️ Remover Admin' : '⭐ Tornar Admin';
      btnAdmin.onclick = async () => {
        try { await api('PATCH', `api/admin.php?action=toggle-admin&id=${uid}`); toast('Papel alterado','','success'); document.getElementById('modal-user-detail').classList.add('hidden'); await loadAdminUsers(); }
        catch (e) { toast('Erro', e.message, 'error'); }
      };
      actions.appendChild(btnAdmin);

      // Delete
      const btnDelete = document.createElement('button');
      btnDelete.className = 'btn btn-danger';
      btnDelete.innerHTML = '🗑️ Apagar Conta';
      btnDelete.onclick = async () => {
        if (!confirm(`Apagar a conta de ${u.email}? Esta ação é irreversível.`)) return;
        try { await api('DELETE', `api/admin.php?action=user&id=${uid}`); toast('Conta apagada','','success'); document.getElementById('modal-user-detail').classList.add('hidden'); await loadAdminUsers(); await loadAdminStats(); }
        catch (e) { toast('Erro', e.message, 'error'); }
      };
      actions.appendChild(btnDelete);
    }
  } catch (e) {
    document.getElementById('modal-user-body').innerHTML = `<p style="color:var(--destructive)">${e.message}</p>`;
  }
}

document.getElementById('modal-user-close').onclick = () => document.getElementById('modal-user-detail').classList.add('hidden');

async function loadAdminLog() {
  document.getElementById('admin-log-loading').classList.remove('hidden');
  document.getElementById('admin-log-list').innerHTML = '';
  try {
    const rows = await api('GET', 'api/admin.php?action=access-log');
    document.getElementById('admin-log-loading').classList.add('hidden');
    if (!rows.length) {
      document.getElementById('admin-log-list').innerHTML = '<p style="color:var(--muted);padding:1rem;font-size:.85rem">Sem registos ainda.</p>';
      return;
    }
    document.getElementById('admin-log-list').innerHTML = rows.map(r => `
      <div class="log-item">
        <div class="log-icon">🔓</div>
        <div class="log-info">
          <div style="font-size:.82rem"><strong>${r.gates?.name || '—'}</strong> · ${r.users?.display_name || r.users?.email || 'Desconhecido'}</div>
          <div class="log-time">${formatDate(r.opened_at)}${r.ip_address ? ` · ${r.ip_address}` : ''}</div>
        </div>
      </div>`).join('');
  } catch (e) {
    document.getElementById('admin-log-loading').classList.add('hidden');
    toast('Erro', e.message, 'error');
  }
}

// ═══════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════
async function loadAll() {
  await Promise.all([loadCars(), loadGates()]);
  if (currentUser?.isAdmin) {
    document.getElementById('btn-admin-panel').classList.remove('hidden');
  }
  document.getElementById('header-sub-label').textContent =
    `${cars.length} ${cars.length===1?'veículo':'veículos'}`;
}

(async () => {
  try {
    currentUser = await api('GET', 'api/auth.php?action=user');
    await loadAll();
    showPage('app');
  } catch {
    showPage('auth');
  }
})();
</script>
</body>
</html>
