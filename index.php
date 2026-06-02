<?php
require_once __DIR__ . '/includes/auth.php';
startSession();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta name="theme-color" content="#0d0f14"/>
  <title>Abre Já</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:hsl(220,20%,7%);--card:hsl(220,18%,11%);--secondary:hsl(220,15%,16%);
      --border:hsl(220,15%,18%);--fg:hsl(210,20%,92%);--muted:hsl(215,15%,50%); 
      --primary:hsl(0,85%,55%);--destructive:hsl(0,84%,60%);
      --success:hsl(142,70%,45%);--warning:hsl(38,92%,50%);
      --radius:.75rem;--font-d:'Orbitron',monospace;--font-b:'Inter',sans-serif;
    } 
    [data-theme="light"]{
      --bg:hsl(210,20%,96%);--card:hsl(0,0%,100%);--secondary:hsl(210,15%,90%);
      --border:hsl(210,15%,82%);--fg:hsl(220,20%,12%);--muted:hsl(215,10%,45%);
      --primary:hsl(0,75%,50%);--success:hsl(142,60%,35%);--warning:hsl(38,85%,40%);
    }
    body{background:var(--bg);color:var(--fg);font-family:var(--font-b);min-height:100vh;transition:background .2s,color .2s}
    .hidden{display:none!important}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.45rem 1rem;border-radius:calc(var(--radius) - 2px);font-family:var(--font-d);font-size:.75rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;border:none;transition:all .15s}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .btn-primary{background:var(--primary);color:#fff}.btn-primary:hover:not(:disabled){opacity:.85}
    .btn-ghost{background:transparent;color:var(--muted)}.btn-ghost:hover:not(:disabled){color:var(--fg);background:var(--secondary)}
    .btn-danger{background:transparent;color:var(--muted)}.btn-danger:hover:not(:disabled){color:var(--destructive);background:hsl(0 84% 60%/.1)}
    .btn-success{background:hsl(142 70% 45%/.15);color:var(--success);border:1px solid hsl(142 70% 45%/.3)}
    .btn-warning{background:hsl(38 92% 50%/.15);color:var(--warning);border:1px solid hsl(38 92% 50%/.3)}
    .btn-icon{padding:.4rem}.btn-full{width:100%;padding:.7rem 1rem;font-size:.8rem}
    .btn-sm{padding:.3rem .65rem;font-size:.65rem}
    .btn-open{background:hsl(142 70% 45%/.12);color:var(--success);border:1px solid hsl(142 70% 45%/.25);font-size:.65rem;padding:.3rem .6rem}
    .label{display:block;font-size:.7rem;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.4rem}
    .input{width:100%;background:var(--secondary);border:1px solid var(--border);border-radius:calc(var(--radius) - 2px);color:var(--fg);padding:.6rem .85rem;font-size:.9rem;font-family:var(--font-b);outline:none;transition:border-color .15s}
    .input:focus{border-color:hsl(0 85% 55%/.5)}.input:disabled{opacity:.5;cursor:not-allowed}
    select.input{cursor:pointer}
    .input-plate{font-family:var(--font-d);font-size:1.1rem;letter-spacing:.2em;text-align:center;text-transform:uppercase}
    .input-hint{font-size:.7rem;color:var(--muted);margin-top:.25rem}
    .form-group{margin-bottom:1rem}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
    .form-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1rem;animation:slideIn .2s ease}
    @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .form-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
    .form-header h3{font-family:var(--font-d);font-size:.75rem;font-weight:600;letter-spacing:.15em;color:var(--primary);text-transform:uppercase}
    .close-btn{background:none;border:none;color:var(--muted);cursor:pointer;display:flex;align-items:center;padding:.25rem;border-radius:.375rem;transition:color .15s}
    .close-btn:hover{color:var(--fg)}
    .section-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem}
    .section-card-title{font-family:var(--font-d);font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--muted);text-transform:uppercase;margin-bottom:1.25rem}
    /* Theme toggle */
    .theme-toggle{background:var(--secondary);border:1px solid var(--border);border-radius:2rem;width:2.5rem;height:1.4rem;cursor:pointer;position:relative;flex-shrink:0}
    .theme-toggle::after{content:'';position:absolute;top:.15rem;left:.15rem;width:1.1rem;height:1.1rem;border-radius:50%;background:var(--primary);transition:transform .2s}
    [data-theme="light"] .theme-toggle::after{transform:translateX(1.1rem)}
    /* Spinner */
    #page-loading{display:flex;align-items:center;justify-content:center;height:100vh;flex-direction:column;gap:1rem}
    .spinner{width:36px;height:36px;border-radius:50%;border:3px solid var(--border);border-top-color:var(--primary);animation:spin .7s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
    /* Auth */
    #auth-page{min-height:100vh;display:flex}
    .auth-hero{display:none;width:50%;flex-direction:column;justify-content:space-between;padding:3rem;position:relative;border-right:1px solid var(--border);overflow:hidden;background:var(--card)}
    @media(min-width:1024px){.auth-hero{display:flex}}
    .auth-blob{position:absolute;border-radius:50%;filter:blur(48px);pointer-events:none}
    .auth-form-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1.5rem}
    .auth-form-inner{width:100%;max-width:22rem}
    .auth-form-title{font-family:var(--font-d);font-size:1.25rem;font-weight:700;letter-spacing:.05em;margin-bottom:.25rem}
    .auth-form-sub{color:var(--muted);font-size:.875rem;margin-bottom:2rem}
    .auth-toggle{text-align:center;padding-top:1.5rem;border-top:1px solid var(--border);margin-top:1.5rem;font-size:.875rem;color:var(--muted)}
    .auth-toggle button{background:none;border:none;color:var(--primary);font-weight:500;cursor:pointer;text-decoration:underline;text-underline-offset:3px}
    .auth-err{background:hsl(0 84% 60%/.1);border:1px solid hsl(0 84% 60%/.3);border-radius:calc(var(--radius) - 2px);padding:.6rem .9rem;font-size:.85rem;color:var(--destructive);margin-bottom:1rem}
    .auth-ok{background:hsl(142 70% 45%/.1);border:1px solid hsl(142 70% 45%/.3);border-radius:calc(var(--radius) - 2px);padding:.6rem .9rem;font-size:.85rem;color:var(--success);margin-bottom:1rem}
    .forgot-link{display:block;text-align:right;font-size:.75rem;color:var(--muted);margin-top:.25rem;cursor:pointer}
    .forgot-link:hover{color:var(--primary)}
    /* App header */
    #app-page{min-height:100vh}
    header.app-header{position:sticky;top:0;z-index:10;background:hsl(220 20% 7%/.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border)}
    [data-theme="light"] header.app-header{background:hsl(210 20% 96%/.92)}
    .header-inner{max-width:48rem;margin:0 auto;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between}
    .header-title{font-family:var(--font-d);font-size:.85rem;font-weight:700;letter-spacing:.1em}
    .header-sub{font-size:.7rem;color:var(--muted);margin-top:.1rem}
    .header-logo-icon{width:2rem;height:2rem;border-radius:.5rem;background:hsl(0 85% 55%/.1);border:1px solid hsl(0 85% 55%/.2);display:flex;align-items:center;justify-content:center;overflow:hidden}
    .header-actions{display:flex;align-items:center;gap:.35rem}
    .app-main{max-width:48rem;margin:0 auto;padding:1.5rem 1rem}
    /* Nav tabs */
    .nav-tabs{display:flex;gap:.25rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.3rem;margin-bottom:1.5rem}
    .nav-tab{flex:1;padding:.45rem .5rem;border-radius:calc(var(--radius) - 4px);border:none;background:transparent;color:var(--muted);font-family:var(--font-d);font-size:.6rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:all .15s}
    .nav-tab.active{background:var(--secondary);color:var(--fg)}
    .section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
    .section-title-text{font-family:var(--font-d);font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--muted);text-transform:uppercase}
    /* Color picker */
    .color-grid{display:grid;grid-template-columns:repeat(8,1fr);gap:.5rem}
    .color-swatch{aspect-ratio:1;border-radius:.5rem;border:2px solid transparent;cursor:pointer;transition:transform .15s,border-color .15s}
    .color-swatch:hover{border-color:hsl(215 15% 50%/.5)}
    .color-swatch.selected{border-color:var(--primary);transform:scale(1.12);box-shadow:0 0 0 2px hsl(0 85% 55%/.3)}
    .color-name{font-size:.7rem;color:var(--muted);margin-top:.35rem}
    .avatar-colors{display:flex;flex-wrap:wrap;gap:.5rem}
    .avatar-color-btn{width:2rem;height:2rem;border-radius:50%;border:2px solid transparent;cursor:pointer;transition:all .15s}
    .avatar-color-btn.selected{border-color:white;transform:scale(1.15)}
    /* Brand select */
    .brand-select-wrap{position:relative}
    .brand-combobox{width:100%;display:flex;align-items:center;justify-content:space-between;height:2.5rem;border-radius:calc(var(--radius) - 2px);border:1px solid var(--border);background:var(--secondary);padding:0 .85rem;font-size:.875rem;color:var(--fg);cursor:pointer;transition:border-color .15s}
    .brand-combobox:focus{outline:none;border-color:hsl(0 85% 55%/.5)}
    .brand-combobox-left{display:flex;align-items:center;gap:.5rem}
    .brand-combobox img{width:1.1rem;height:1.1rem;object-fit:contain}
    .brand-dropdown{position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:50;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.4)}
    .brand-search{width:100%;background:var(--secondary);border:none;border-bottom:1px solid var(--border);color:var(--fg);padding:.6rem .85rem;font-size:.85rem;outline:none}
    .brand-list{max-height:14rem;overflow-y:auto}
    .brand-item{display:flex;align-items:center;gap:.6rem;padding:.5rem .85rem;cursor:pointer;font-size:.875rem;transition:background .1s}
    .brand-item:hover,.brand-item.active{background:var(--secondary)}
    .brand-item img{width:1.1rem;height:1.1rem;object-fit:contain}
    .brand-empty{padding:.75rem .85rem;color:var(--muted);font-size:.85rem}
    /* Car card */
    .car-card{position:relative;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);overflow:hidden;transition:border-color .2s;margin-bottom:.75rem}
    .car-card:hover{border-color:hsl(0 85% 55%/.25)}
    .car-stripe{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:var(--radius) 0 0 var(--radius);opacity:.7}
    .car-inner{padding:1rem 1rem 1rem 1.25rem;display:flex;align-items:center;gap:1rem}
    .car-brand-logo{width:2.5rem;height:2.5rem;border-radius:.5rem;background:hsl(220 15% 20%/.8);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
    .car-brand-logo img{width:1.75rem;height:1.75rem;object-fit:contain;opacity:.85}
    .car-info{flex:1;min-width:0;display:flex;align-items:center;gap:.75rem}
    .car-dot{width:1rem;height:1rem;border-radius:50%;border:1px solid hsl(0 0% 100%/.1);flex-shrink:0}
    .car-plate{font-family:var(--font-d);font-size:1rem;letter-spacing:.15em;font-weight:700}
    .car-sub{font-size:.8rem;color:var(--muted)}
    .card-actions{display:flex;gap:.25rem;opacity:0;transition:opacity .15s;flex-shrink:0}
    .car-card:hover .card-actions,.gate-card:hover .card-actions{opacity:1}
    /* Gate card */
    .gate-card{position:relative;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);overflow:hidden;transition:border-color .2s;margin-bottom:.75rem}
    .gate-card:hover{border-color:hsl(0 85% 55%/.25)}
    .gate-inner{padding:1rem 1rem 1rem 1.25rem;display:flex;align-items:center;gap:1rem}
    .gate-icon-box{width:2.75rem;height:2.75rem;border-radius:.6rem;background:hsl(0 85% 55%/.08);border:1px solid hsl(0 85% 55%/.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem}
    .gate-info{flex:1;min-width:0}
    .gate-name{font-family:var(--font-d);font-size:.95rem;font-weight:700;letter-spacing:.05em}
    .gate-relay{font-size:.72rem;color:var(--muted);margin-top:.15rem;font-family:monospace}
    .gate-actions{display:flex;gap:.3rem;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end}
    /* Icon picker */
    .icon-grid{display:flex;flex-wrap:wrap;gap:.4rem}
    .icon-btn{width:2.4rem;height:2.4rem;border-radius:.5rem;border:2px solid transparent;cursor:pointer;font-size:1.3rem;background:var(--secondary);display:flex;align-items:center;justify-content:center;transition:all .15s}
    .icon-btn:hover{border-color:hsl(215 15% 50%/.4)}
    .icon-btn.selected{border-color:var(--primary);transform:scale(1.1)}
    /* Log */
    .log-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-bottom:1px solid var(--border);font-size:.82rem}
    .log-item:last-child{border-bottom:none}
    .log-icon{width:1.75rem;height:1.75rem;border-radius:.4rem;background:hsl(0 85% 55%/.08);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
    .log-time{font-size:.7rem;color:var(--muted);margin-top:.1rem}
    /* Modal tabs */
    .modal-tabs{display:flex;gap:.25rem;background:var(--secondary);border-radius:calc(var(--radius) - 2px);padding:.25rem;margin-bottom:1rem}
    .modal-tab{flex:1;padding:.4rem .5rem;border-radius:calc(var(--radius) - 4px);border:none;background:transparent;color:var(--muted);font-family:var(--font-d);font-size:.58rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:all .15s}
    .modal-tab.active{background:var(--card);color:var(--fg)}
    /* Day picker */
    .days-row{display:flex;gap:.4rem}
    .day-btn{width:1.9rem;height:1.9rem;border-radius:50%;border:1px solid var(--border);background:var(--secondary);color:var(--muted);font-size:.65rem;font-weight:600;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center}
    .day-btn.active{background:var(--primary);border-color:var(--primary);color:#fff}
    /* Share item */
    .share-item{display:flex;align-items:center;justify-content:space-between;padding:.65rem .85rem;border-bottom:1px solid var(--border);font-size:.82rem}
    .share-item:last-child{border-bottom:none}
    /* Profile */
    #profile-page{min-height:100vh}
    .page-header{position:sticky;top:0;z-index:10;background:hsl(220 20% 7%/.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border)}
    [data-theme="light"] .page-header{background:hsl(210 20% 96%/.92)}
    .page-header-inner{max-width:42rem;margin:0 auto;padding:.75rem 1rem;display:flex;align-items:center;gap:.75rem}
    .page-main{max-width:42rem;margin:0 auto;padding:2rem 1rem}
    .profile-avatar{width:3.5rem;height:3.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--font-d);font-size:1.2rem;font-weight:700;border:2px solid hsl(0 0% 100%/.1)}
    /* Empty */
    .empty-state{text-align:center;padding:5rem 1rem}
    .empty-icon-wrap{position:relative;width:6rem;height:6rem;margin:0 auto 1.5rem}
    .empty-icon-bg{position:absolute;inset:0;border-radius:1rem;background:hsl(0 85% 55%/.05);border:1px solid hsl(0 85% 55%/.1);filter:blur(4px)}
    .empty-icon-box{position:relative;width:6rem;height:6rem;border-radius:1rem;background:var(--secondary);border:1px solid var(--border);display:flex;align-items:center;justify-content:center}
    .empty-state h2{font-weight:600;font-size:1.1rem;margin-bottom:.5rem}
    .empty-state p{color:var(--muted);font-size:.875rem;max-width:20rem;margin:0 auto 1.5rem}
    /* Skeleton */
    .skeleton{background:var(--secondary);border-radius:.5rem;animation:pulse 1.5s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
    /* Toast */
    #toast-container{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none}
    .toast{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.75rem 1.1rem;font-size:.875rem;box-shadow:0 8px 24px rgba(0,0,0,.4);animation:toastIn .2s ease;max-width:22rem;pointer-events:auto}
    .toast.error{border-color:hsl(0 84% 60%/.4)}.toast.success{border-color:hsl(142 70% 45%/.4)}
    @keyframes toastIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
    /* Modal */
    .modal-overlay{position:fixed;inset:0;background:hsl(220 20% 4%/.85);backdrop-filter:blur(4px);z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem}
    .modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;width:100%;max-width:32rem;max-height:88vh;overflow-y:auto;animation:mIn .2s ease}
    @keyframes mIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .modal-title{font-family:var(--font-d);font-size:.85rem;font-weight:700;letter-spacing:.1em;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between}
    .badge{font-size:.6rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:.15rem .45rem;border-radius:.3rem}
    .badge-shared{background:hsl(215 80% 55%/.15);color:hsl(215 80% 70%);border:1px solid hsl(215 80% 55%/.3)}
    svg{display:inline-block;vertical-align:middle}
  </style>
</head>
<body>
<div id="page-loading"><div class="spinner"></div><div style="font-family:var(--font-d);font-size:.7rem;letter-spacing:.15em;color:var(--muted)">ABRE JÁ</div></div>
<div id="toast-container"></div>

<!-- AUTH -->
<div id="auth-page" class="hidden">
  <div class="auth-hero">
    <div class="auth-blob" style="top:5rem;left:2rem;width:18rem;height:18rem;background:hsl(0 85% 55%/.05)"></div>
    <div style="position:relative;z-index:1">
      <div style="font-family:var(--font-d);font-weight:700;font-size:.9rem;letter-spacing:.1em">ABRE JÁ</div>
    </div>
    <div style="position:relative;z-index:1">
      <div style="font-family:var(--font-d);font-size:2rem;font-weight:700;line-height:1.2;margin-bottom:1rem">Os teus portões,<br/><span style="color:var(--primary)">sempre contigo.</span></div>
      <div style="color:var(--muted);line-height:1.7">Gere portões e carros num só lugar.</div>
    </div>
    <div style="font-size:.7rem;color:var(--muted)">© 2025 Abre Já</div>
  </div>
  <div class="auth-form-panel">
    <div class="auth-form-inner">
      <!-- Login -->
      <div id="login-form">
        <div class="auth-form-title">Entrar</div>
        <div class="auth-form-sub">Acede à tua conta</div>
        <div id="login-err" class="auth-err hidden"></div>
        <div class="form-group"><label class="label">Email</label><input id="inp-email" class="input" type="email" placeholder="email@exemplo.com"/></div>
        <div class="form-group"><label class="label">Password</label><input id="inp-password" class="input" type="password" placeholder="••••••••"/><span class="forgot-link" id="btn-show-forgot">Esqueceste a password?</span></div>
        <button id="auth-submit" class="btn btn-primary btn-full">Entrar</button>
        <div class="auth-toggle">Não tens conta? <button id="btn-toggle-auth">Registar</button></div>
      </div>
      <!-- Register -->
      <div id="register-form" class="hidden">
        <div class="auth-form-title">Criar Conta</div>
        <div class="auth-form-sub">Regista-te gratuitamente</div>
        <div id="register-err" class="auth-err hidden"></div>
        <div class="form-group"><label class="label">Nome (opcional)</label><input id="inp-name" class="input" type="text" placeholder="O teu nome" maxlength="100"/></div>
        <div class="form-group"><label class="label">Email</label><input id="inp-reg-email" class="input" type="email" placeholder="email@exemplo.com"/></div>
        <div class="form-group"><label class="label">Password</label><input id="inp-reg-password" class="input" type="password" placeholder="Mínimo 6 caracteres"/></div>
        <button id="register-submit" class="btn btn-primary btn-full">Criar Conta</button>
        <div class="auth-toggle">Já tens conta? <button id="btn-toggle-login">Entrar</button></div>
      </div>
      <!-- Forgot -->
      <div id="forgot-form" class="hidden">
        <div class="auth-form-title">Recuperar Password</div>
        <div class="auth-form-sub">Insere o teu email</div>
        <div id="forgot-err" class="auth-err hidden"></div>
        <div id="forgot-ok" class="auth-ok hidden"></div>
        <div class="form-group"><label class="label">Email</label><input id="inp-forgot-email" class="input" type="email" placeholder="email@exemplo.com"/></div>
        <button id="forgot-submit" class="btn btn-primary btn-full">Enviar Link</button>
        <div class="auth-toggle"><button id="btn-back-login">← Voltar ao login</button></div>
      </div>
    </div>
  </div>
</div>

<!-- APP -->
<div id="app-page" class="hidden">
  <header class="app-header">
    <div class="header-inner">
      <div style="display:flex;align-items:center;gap:.5rem">
        <div class="header-logo-icon"><img src="logo.png" style="width:22px;height:22px;object-fit:contain"/></div>
        <div><div class="header-title">ABRE JÁ</div><div id="header-sub" class="header-sub">—</div></div>
      </div>
      <div class="header-actions">
        <button id="btn-theme" class="theme-toggle" title="Tema"></button>
        <button id="btn-profile" class="btn btn-ghost btn-icon" title="Perfil">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </button>
        <button id="btn-logout" class="btn btn-danger btn-icon" title="Sair">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </div>
  </header>
  <main class="app-main">
    <nav class="nav-tabs">
      <button class="nav-tab active" data-tab="cars">🚗 Carros</button>
      <button class="nav-tab" data-tab="gates">🚪 Portões</button>
    </nav>

    <!-- CARS -->
    <div id="tab-cars">
      <div class="section-header">
        <span id="car-count-lbl" class="section-title-text">—</span>
        <button id="btn-add-car" class="btn btn-primary btn-sm">+ Adicionar</button>
      </div>
      <div id="car-form-wrapper" class="form-card hidden">
        <div class="form-header"><h3 id="car-form-title">Novo Carro</h3><button class="close-btn" id="btn-close-car-form"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="form-group"><label class="label">Matrícula</label><input id="inp-plate" class="input input-plate" type="text" placeholder="AA-00-AA" maxlength="8"/><div class="input-hint"><span id="plate-count">0</span>/8</div></div>
        <div class="form-group"><label class="label">Marca</label>
          <div class="brand-select-wrap">
            <button type="button" id="brand-combobox" class="brand-combobox"><span class="brand-combobox-left" id="brand-combobox-label"><span style="color:var(--muted)">Pesquisar marca...</span></span><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg></button>
            <div id="brand-dropdown" class="brand-dropdown hidden"><input id="brand-search" class="brand-search" type="text" placeholder="Pesquisar..."/><div id="brand-list" class="brand-list"></div></div>
          </div>
        </div>
        <div class="form-group"><label class="label">Cor</label><div id="color-grid" class="color-grid"></div><div id="color-name" class="color-name"></div></div>
        <button id="btn-car-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">Adicionar</button>
      </div>
      <div id="cars-loading" class="hidden"><div class="skeleton" style="height:4rem;border-radius:var(--radius);margin-bottom:.75rem"></div></div>
      <div id="cars-empty" class="empty-state hidden">
        <div class="empty-icon-wrap"><div class="empty-icon-bg"></div><div class="empty-icon-box"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5"/><circle cx="16" cy="19" r="2"/><circle cx="7" cy="19" r="2"/></svg></div></div>
        <h2>Sem carros</h2><p>Adiciona o teu primeiro veículo.</p>
        <button class="btn btn-primary" id="btn-add-car-empty">+ Adicionar Carro</button>
      </div>
      <div id="cars-list"></div>
    </div>

    <!-- GATES -->
    <div id="tab-gates" class="hidden">
      <div class="section-header">
        <span id="gate-count-lbl" class="section-title-text">—</span>
        <button id="btn-add-gate" class="btn btn-primary btn-sm">+ Novo Portão</button>
      </div>
      <div id="gate-form-wrapper" class="form-card hidden">
        <div class="form-header"><h3 id="gate-form-title">Novo Portão</h3><button class="close-btn" id="btn-close-gate-form"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
        <div class="form-group"><label class="label">Nome</label><input id="inp-gate-name" class="input" type="text" placeholder="Ex: Portão Principal" maxlength="60"/><div class="input-hint"><span id="gate-name-count">0</span>/60</div></div>
        <div class="form-group"><label class="label">ID do Relé</label><input id="inp-gate-relay" class="input" style="font-family:monospace" type="text" placeholder="relay_01 ou 192.168.1.100" maxlength="100"/></div>
        <div class="form-group"><label class="label">Ícone</label><div id="gate-icon-grid" class="icon-grid"></div></div>
        <button id="btn-gate-submit" class="btn btn-primary btn-full" style="margin-top:.5rem">Adicionar</button>
      </div>
      <div id="gates-loading" class="hidden"><div class="skeleton" style="height:4rem;border-radius:var(--radius);margin-bottom:.75rem"></div></div>
      <div id="gates-empty" class="empty-state hidden">
        <div class="empty-icon-wrap"><div class="empty-icon-bg"></div><div class="empty-icon-box"><svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="3" x2="12" y2="21"/></svg></div></div>
        <h2>Sem portões</h2><p>Adiciona o teu primeiro portão.</p>
        <button class="btn btn-primary" id="btn-add-gate-empty">+ Novo Portão</button>
      </div>
      <div id="gates-list"></div>
    </div>
  </main>
</div>

<!-- GATE DETAIL MODAL -->
<div id="modal-gate" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-title">
      <span id="modal-gate-title">Portão</span>
      <button class="close-btn" id="btn-close-gate-modal"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <div class="modal-tabs">
      <button class="modal-tab active" data-mtab="log">📋 Histórico</button>
      <button class="modal-tab" data-mtab="cars">🚗 Carros</button>
      <button class="modal-tab" data-mtab="shares">🤝 Acesso</button>
      <button class="modal-tab" data-mtab="schedules">⏰ Agenda</button>
    </div>
    <div id="mtab-log"></div>
    <div id="mtab-cars" class="hidden"></div>
    <div id="mtab-shares" class="hidden"></div>
    <div id="mtab-schedules" class="hidden"></div>
  </div>
</div>

<!-- PROFILE -->
<div id="profile-page" class="hidden">
  <div class="page-header"><div class="page-header-inner">
    <button id="btn-back-profile" class="btn btn-ghost btn-icon"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg></button>
    <div><div class="header-title">Perfil</div><div class="header-sub">Gerir conta</div></div>
  </div></div>
  <div class="page-main">
    <div style="display:flex;align-items:center;gap:1.25rem;padding:1.25rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:1.5rem">
      <div id="profile-avatar" class="profile-avatar"></div>
      <div><div id="profile-name-display" style="font-weight:600"></div><div id="profile-email-display" style="font-size:.8rem;color:var(--muted);margin-top:.2rem"></div></div>
    </div>
    <div class="section-card">
      <div class="section-card-title">Informações</div>
      <div class="form-group"><label class="label">Nome</label><input id="profile-name-inp" class="input" type="text" maxlength="100"/></div>
      <div class="form-group"><label class="label">Email</label><input id="profile-email-inp" class="input" disabled/></div>
      <div class="form-group"><label class="label">Cor do Avatar</label><div class="avatar-colors" id="avatar-colors"></div></div>
      <button id="btn-save-profile" class="btn btn-primary btn-full">Guardar Alterações</button>
    </div>
    <div class="section-card">
      <div class="section-card-title">Alterar Password</div>
      <div class="form-group"><label class="label">Password Atual</label><input id="inp-pw-current" class="input" type="password" placeholder="••••••••"/></div>
      <div class="form-row">
        <div class="form-group"><label class="label">Nova Password</label><input id="inp-pw-new" class="input" type="password" placeholder="Mínimo 6 chars"/></div>
        <div class="form-group"><label class="label">Confirmar</label><input id="inp-pw-confirm" class="input" type="password" placeholder="Repete"/></div>
      </div>
      <button id="btn-change-pw" class="btn btn-primary btn-full">Alterar Password</button>
    </div>
    <div id="admin-link-card" class="section-card hidden" style="border-color:hsl(38 92% 50%/.2)">
      <div class="section-card-title" style="color:var(--warning)">Administração</div>
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem">Aceder ao painel de administração do sistema.</p>
      <a href="admin_panel.php" target="_blank" class="btn btn-full" style="background:hsl(38 92% 50%/.1);color:var(--warning);border:1px solid hsl(38 92% 50%/.3);text-decoration:none">⭐ Abrir Painel Admin</a>
    </div>
    <div class="section-card" style="border-color:hsl(0 84% 60%/.2)">
      <div class="section-card-title" style="color:var(--destructive)">Zona de Perigo</div>
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem">Terminar sessão em todos os dispositivos.</p>
      <button id="btn-logout-profile" class="btn btn-full" style="background:hsl(0 84% 60%/.1);color:var(--destructive);border:1px solid hsl(0 84% 60%/.3)">Terminar Sessão</button>
    </div>
  </div>
</div>

<script>
// ════ DATA ════
const CAR_COLORS=[{name:"Preto",value:"#111111"},{name:"Preto Mate",value:"#2a2a2a"},{name:"Branco",value:"#f5f5f5"},{name:"Branco Pérola",value:"#f0ece4"},{name:"Cinzento",value:"#808080"},{name:"Cinzento Escuro",value:"#4a4a4a"},{name:"Prata",value:"#c0c0c0"},{name:"Champanhe",value:"#c9a96e"},{name:"Dourado",value:"#d4a017"},{name:"Vermelho",value:"#dc2626"},{name:"Vermelho Escuro",value:"#8b0000"},{name:"Bordeaux",value:"#722f37"},{name:"Laranja",value:"#ea580c"},{name:"Amarelo",value:"#eab308"},{name:"Verde",value:"#16a34a"},{name:"Verde Escuro",value:"#14532d"},{name:"Turquesa",value:"#0d9488"},{name:"Azul Claro",value:"#38bdf8"},{name:"Azul",value:"#2563eb"},{name:"Azul Escuro",value:"#1e3a5f"},{name:"Roxo",value:"#7c3aed"},{name:"Rosa",value:"#db2777"},{name:"Castanho",value:"#92400e"},{name:"Bege",value:"#d4c5a9"}];
const CAR_BRANDS=["Abarth","Alfa Romeo","Alpine","Aston Martin","Audi","Bentley","BMW","Bugatti","BYD","Cadillac","Chevrolet","Chrysler","Citroën","Cupra","Dacia","Dodge","DS Automobiles","Ferrari","Fiat","Ford","Ford Mustang","Genesis","Honda","Hyundai","Infiniti","Jaguar","Jeep","Kia","Lamborghini","Land Rover","Lexus","Lucid","Lynk & Co","Maserati","Mazda","McLaren","Mercedes-Benz","MG","Mini","Mitsubishi","Nissan","Opel","Pagani","Peugeot","Polestar","Porsche","RAM","Renault","Rivian","Rolls-Royce","Saab","SEAT","Škoda","Smart","Subaru","Suzuki","Tesla","Toyota","Volkswagen","Volvo"].sort((a,b)=>a.localeCompare(b));
const CDN="https://cdn.jsdelivr.net/gh/filippofilip95/car-logos-ds@latest/logos/optimized";
const BRAND_LOGOS={"Alfa Romeo":"alfa-romeo","Aston Martin":"aston-martin","Audi":"audi","Bentley":"bentley","BMW":"bmw","Bugatti":"bugatti","Cadillac":"cadillac","Chevrolet":"chevrolet","Chrysler":"chrysler","Citroën":"citroen","Cupra":"cupra","Dacia":"dacia","Dodge":"dodge","Ferrari":"ferrari","Fiat":"fiat","Ford":"ford","Ford Mustang":"ford","Genesis":"genesis","Honda":"honda","Hyundai":"hyundai","Infiniti":"infiniti","Jaguar":"jaguar","Jeep":"jeep","Kia":"kia","Lamborghini":"lamborghini","Land Rover":"land-rover","Lexus":"lexus","Maserati":"maserati","Mazda":"mazda","McLaren":"mclaren","Mercedes-Benz":"mercedes","Mini":"mini","Mitsubishi":"mitsubishi","Nissan":"nissan","Opel":"opel","Peugeot":"peugeot","Polestar":"polestar","Porsche":"porsche","Renault":"renault","Rolls-Royce":"rolls-royce","SEAT":"seat","Škoda":"skoda","Smart":"smart","Subaru":"subaru","Suzuki":"suzuki","Tesla":"tesla","Toyota":"toyota","Volkswagen":"volkswagen","Volvo":"volvo","MG":"mg"};
const getBrandLogo=b=>BRAND_LOGOS[b]?`${CDN}/${BRAND_LOGOS[b]}.svg`:null;
const GATE_ICONS=['🏠','🏢','🏭','🏪','🏫','🚗','🚪','🔑','🔒','🛡️','📡','⚙️','🔧','💡','🌿','🏗️','🅿️','🏊','🌳','🔐'];
const AVATAR_COLORS=['#e53935','#e91e63','#9c27b0','#3f51b5','#2196f3','#009688','#4caf50','#ff9800','#795548','#607d8b'];
const DAY_LABELS=['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

// ════ STATE ════
let currentUser=null, cars=[], gates=[];
let editingCarId=null, editingGateId=null, currentGate=null;
let selectedBrand='', selectedColor=CAR_COLORS[0].value;
let selectedGateIcon='🏠', selectedAvatarColor='#e53935';
let selectedDays=new Set();
let brandOpen=false;

// ════ API ════
async function api(method,url,body){
  const o={method,headers:{}};
  if(body){o.headers['Content-Type']='application/json';o.body=JSON.stringify(body);}
  const r=await fetch(url,o);
  const d=await r.json().catch(()=>({}));
  if(!r.ok)throw new Error(d.error||'Erro desconhecido');
  return d;
}

// ════ TOAST ════
function toast(title,desc='',type=''){
  const el=document.createElement('div');
  el.className='toast'+(type?' '+type:'');
  el.innerHTML=`<div style="font-weight:600">${title}</div>${desc?`<div style="color:var(--muted);font-size:.8rem">${desc}</div>`:''}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(()=>el.remove(),3500);
}

// ════ PAGES ════
function showPage(name){
  document.getElementById('page-loading').classList.add('hidden');
  ['auth-page','app-page','profile-page'].forEach(id=>
    document.getElementById(id).classList.toggle('hidden',id!==name+'-page')
  );
}
function fmt(iso){
  if(!iso)return'—';
  return new Date(iso).toLocaleString('pt-PT',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

// ════ THEME ════
function applyTheme(t){
  document.documentElement.setAttribute('data-theme',t);
  localStorage.setItem('theme',t);
}
applyTheme(localStorage.getItem('theme')||'dark');
document.getElementById('btn-theme').onclick=()=>{
  applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');
};

// ════ AUTH ════
function showAuthForm(f){
  ['login-form','register-form','forgot-form'].forEach(id=>
    document.getElementById(id).classList.toggle('hidden',id!==f)
  );
}
document.getElementById('btn-toggle-auth').onclick=()=>showAuthForm('register-form');
document.getElementById('btn-toggle-login').onclick=()=>showAuthForm('login-form');
document.getElementById('btn-show-forgot').onclick=()=>showAuthForm('forgot-form');
document.getElementById('btn-back-login').onclick=()=>showAuthForm('login-form');

document.getElementById('auth-submit').onclick=async()=>{
  const email=document.getElementById('inp-email').value.trim();
  const pw=document.getElementById('inp-password').value;
  const err=document.getElementById('login-err'); err.classList.add('hidden');
  const btn=document.getElementById('auth-submit'); btn.disabled=true; btn.textContent='A entrar...';
  try{
    currentUser=await api('POST','api/auth.php?action=login',{email,password:pw});
    await loadAll(); showPage('app');
  }catch(e){err.textContent=e.message;err.classList.remove('hidden');}
  finally{btn.disabled=false;btn.textContent='Entrar';}
};
document.getElementById('register-submit').onclick=async()=>{
  const dn=document.getElementById('inp-name').value.trim();
  const email=document.getElementById('inp-reg-email').value.trim();
  const pw=document.getElementById('inp-reg-password').value;
  const err=document.getElementById('register-err'); err.classList.add('hidden');
  const btn=document.getElementById('register-submit'); btn.disabled=true;
  try{
    currentUser=await api('POST','api/auth.php?action=register',{email,password:pw,displayName:dn});
    await loadAll(); showPage('app');
  }catch(e){err.textContent=e.message;err.classList.remove('hidden');}
  finally{btn.disabled=false;btn.textContent='Criar Conta';}
};
document.getElementById('forgot-submit').onclick=async()=>{
  const email=document.getElementById('inp-forgot-email').value.trim();
  const err=document.getElementById('forgot-err'); const ok=document.getElementById('forgot-ok');
  err.classList.add('hidden'); ok.classList.add('hidden');
  const btn=document.getElementById('forgot-submit'); btn.disabled=true;
  try{
    await api('POST','api/auth.php?action=forgot',{email});
    ok.textContent='Se o email existir, receberás instruções em breve.'; ok.classList.remove('hidden');
  }catch(e){err.textContent=e.message;err.classList.remove('hidden');}
  finally{btn.disabled=false;btn.textContent='Enviar Link';}
};
['inp-email','inp-password'].forEach(id=>
  document.getElementById(id).addEventListener('keydown',e=>{if(e.key==='Enter')document.getElementById('auth-submit').click();})
);

// ════ LOGOUT ════
async function doLogout(){
  await api('POST','api/auth.php?action=logout').catch(()=>{});
  currentUser=null; cars=[]; gates=[];
  document.getElementById('admin-link-card').classList.add('hidden');
  showPage('auth');
}
document.getElementById('btn-logout').onclick=doLogout;
document.getElementById('btn-logout-profile').onclick=doLogout;

// ════ TABS ════
document.querySelectorAll('.nav-tab').forEach(tab=>{
  tab.onclick=()=>{
    document.querySelectorAll('.nav-tab').forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    const n=tab.dataset.tab;
    document.getElementById('tab-cars').classList.toggle('hidden',n!=='cars');
    document.getElementById('tab-gates').classList.toggle('hidden',n!=='gates');
    updateSub();
  };
});
function updateSub(){
  const active=document.querySelector('.nav-tab.active')?.dataset.tab||'cars';
  document.getElementById('header-sub').textContent=active==='cars'
    ?`${cars.length} ${cars.length===1?'veículo':'veículos'}`
    :`${gates.length} ${gates.length===1?'portão':'portões'}`;
}

// ════ PROFILE ════
function buildAvatarColors(){
  const wrap=document.getElementById('avatar-colors'); wrap.innerHTML='';
  AVATAR_COLORS.forEach(c=>{
    const btn=document.createElement('button');
    btn.type='button'; btn.className='avatar-color-btn'+(c===selectedAvatarColor?' selected':'');
    btn.style.backgroundColor=c;
    btn.onclick=()=>{selectedAvatarColor=c;document.querySelectorAll('.avatar-color-btn').forEach(b=>b.classList.remove('selected'));btn.classList.add('selected');updateProfileAvatar();};
    wrap.appendChild(btn);
  });
}
function updateProfileAvatar(){
  const av=document.getElementById('profile-avatar');
  av.style.background=selectedAvatarColor+'22'; av.style.borderColor=selectedAvatarColor+'44'; av.style.color=selectedAvatarColor;
  av.textContent=(currentUser?.displayName||currentUser?.email||'?')[0].toUpperCase();
}
document.getElementById('btn-profile').onclick=()=>{
  selectedAvatarColor=currentUser?.avatarColor||'#e53935';
  document.getElementById('profile-name-display').textContent=currentUser?.displayName||'Sem nome';
  document.getElementById('profile-email-display').textContent=currentUser?.email||'';
  document.getElementById('profile-name-inp').value=currentUser?.displayName||'';
  document.getElementById('profile-email-inp').value=currentUser?.email||'';
  document.getElementById('inp-pw-current').value='';
  document.getElementById('inp-pw-new').value='';
  document.getElementById('inp-pw-confirm').value='';
  buildAvatarColors(); updateProfileAvatar();
  showPage('profile');
};
document.getElementById('btn-back-profile').onclick=()=>showPage('app');
document.getElementById('btn-save-profile').onclick=async()=>{
  const name=document.getElementById('profile-name-inp').value.trim();
  const btn=document.getElementById('btn-save-profile'); btn.disabled=true;
  try{
    await api('PUT','api/auth.php?action=profile',{displayName:name,avatarColor:selectedAvatarColor});
    currentUser.displayName=name; currentUser.avatarColor=selectedAvatarColor;
    document.getElementById('profile-name-display').textContent=name||'Sem nome';
    updateProfileAvatar(); toast('Perfil atualizado!','','success');
  }catch(e){toast('Erro',e.message,'error');}
  finally{btn.disabled=false;}
};
document.getElementById('btn-change-pw').onclick=async()=>{
  const cur=document.getElementById('inp-pw-current').value;
  const nw=document.getElementById('inp-pw-new').value;
  const cf=document.getElementById('inp-pw-confirm').value;
  if(nw!==cf){toast('As passwords não coincidem','','error');return;}
  const btn=document.getElementById('btn-change-pw'); btn.disabled=true;
  try{
    await api('PUT','api/auth.php?action=password',{current:cur,new:nw});
    document.getElementById('inp-pw-current').value='';
    document.getElementById('inp-pw-new').value='';
    document.getElementById('inp-pw-confirm').value='';
    toast('Password alterada!','','success');
  }catch(e){toast('Erro',e.message,'error');}
  finally{btn.disabled=false;}
};

// ════ COLOR PICKER ════
function buildColorGrid(){
  const g=document.getElementById('color-grid'); g.innerHTML='';
  CAR_COLORS.forEach(c=>{
    const btn=document.createElement('button'); btn.type='button';
    btn.className='color-swatch'+(c.value===selectedColor?' selected':'');
    btn.style.backgroundColor=c.value; btn.title=c.name;
    btn.onclick=()=>{selectedColor=c.value;document.querySelectorAll('#color-grid .color-swatch').forEach(s=>s.classList.remove('selected'));btn.classList.add('selected');document.getElementById('color-name').textContent=c.name;};
    g.appendChild(btn);
  });
  document.getElementById('color-name').textContent=CAR_COLORS.find(c=>c.value===selectedColor)?.name||'';
}

// ════ BRAND ════
function setBrand(b){
  selectedBrand=b;
  const logo=getBrandLogo(b);
  document.getElementById('brand-combobox-label').innerHTML=b
    ?`${logo?`<img src="${logo}" alt="${b}" onerror="this.style.display='none'"/>`:''}<span>${b}</span>`
    :`<span style="color:var(--muted)">Pesquisar marca...</span>`;
}
function renderBrandList(f){
  const list=document.getElementById('brand-list');
  const items=CAR_BRANDS.filter(b=>b.toLowerCase().includes(f.toLowerCase()));
  if(!items.length){list.innerHTML='<div class="brand-empty">Não encontrado.</div>';return;}
  list.innerHTML=items.map(b=>{
    const logo=getBrandLogo(b);
    return`<div class="brand-item${b===selectedBrand?' active':''}" data-brand="${b}">${logo?`<img src="${logo}" alt="${b}" onerror="this.style.display='none'"/>`:''}${b}</div>`;
  }).join('');
  list.querySelectorAll('.brand-item').forEach(el=>el.onclick=()=>{setBrand(el.dataset.brand);closeBrand();});
}
function openBrand(){brandOpen=true;document.getElementById('brand-dropdown').classList.remove('hidden');document.getElementById('brand-search').value='';renderBrandList('');setTimeout(()=>document.getElementById('brand-search').focus(),50);}
function closeBrand(){brandOpen=false;document.getElementById('brand-dropdown').classList.add('hidden');}
document.getElementById('brand-combobox').onclick=()=>brandOpen?closeBrand():openBrand();
document.getElementById('brand-search').oninput=e=>renderBrandList(e.target.value);
document.addEventListener('click',e=>{if(brandOpen&&!document.querySelector('.brand-select-wrap').contains(e.target))closeBrand();});

// ════ CARS ════
function openCarForm(car){
  editingCarId=car?car.id:null; selectedBrand=car?car.brand:''; selectedColor=car?car.color:CAR_COLORS[0].value;
  document.getElementById('car-form-title').textContent=car?'Editar Carro':'Novo Carro';
  document.getElementById('inp-plate').value=car?car.plate:'';
  document.getElementById('plate-count').textContent=car?car.plate.length:0;
  document.getElementById('btn-car-submit').textContent=car?'Guardar':'Adicionar';
  setBrand(selectedBrand); buildColorGrid();
  document.getElementById('car-form-wrapper').classList.remove('hidden');
  document.getElementById('btn-add-car').classList.add('hidden');
  document.getElementById('cars-empty').classList.add('hidden');
  document.getElementById('inp-plate').focus();
}
function closeCarForm(){editingCarId=null;document.getElementById('car-form-wrapper').classList.add('hidden');document.getElementById('btn-add-car').classList.remove('hidden');renderCars();}
document.getElementById('inp-plate').oninput=e=>{e.target.value=e.target.value.replace(/[^a-zA-Z0-9-]/g,'').toUpperCase();document.getElementById('plate-count').textContent=e.target.value.length;};
document.getElementById('btn-add-car').onclick=()=>openCarForm(null);
document.getElementById('btn-add-car-empty').onclick=()=>openCarForm(null);
document.getElementById('btn-close-car-form').onclick=closeCarForm;
document.getElementById('btn-car-submit').onclick=async()=>{
  const plate=document.getElementById('inp-plate').value.trim().toUpperCase();
  if(!plate||plate.length>8){toast('Matrícula inválida','Máx. 8 caracteres','error');return;}
  if(!selectedBrand){toast('Marca obrigatória','','error');return;}
  const btn=document.getElementById('btn-car-submit'); btn.disabled=true;
  try{
    const body={plate,brand:selectedBrand,color:selectedColor};
    if(editingCarId){await api('PUT',`api/cars.php?id=${editingCarId}`,body);toast('Carro atualizado!','','success');}
    else{await api('POST','api/cars.php',body);toast('Carro adicionado!','','success');}
    await loadCars(); closeCarForm();
  }catch(e){toast('Erro',e.message,'error');}
  finally{btn.disabled=false;}
};
function renderCars(){
  const list=document.getElementById('cars-list');
  const empty=document.getElementById('cars-empty');
  document.getElementById('car-count-lbl').textContent=`${cars.length} ${cars.length===1?'veículo':'veículos'}`;
  if(!cars.length){list.innerHTML='';empty.classList.remove('hidden');return;}
  empty.classList.add('hidden');
  list.innerHTML=cars.map(car=>{
    const logo=getBrandLogo(car.brand);
    const colorName=CAR_COLORS.find(c=>c.value===car.color)?.name||'Personalizada';
    return`<div class="car-card">
      <div class="car-stripe" style="background:${car.color}"></div>
      <div class="car-inner">
        <div class="car-brand-logo">${logo?`<img src="${logo}" alt="${car.brand}" onerror="this.style.display='none'"/>`:`<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5"/><circle cx="16" cy="19" r="2"/><circle cx="7" cy="19" r="2"/></svg>`}</div>
        <div class="car-info">
          <div class="car-dot" style="background:${car.color}"></div>
          <div><div class="car-plate">${car.plate}</div><div class="car-sub">${car.brand} · ${colorName}</div></div>
        </div>
        <div class="card-actions">
          <button class="btn btn-ghost btn-icon btn-edit-car" data-id="${car.id}"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="btn btn-danger btn-icon btn-del-car" data-id="${car.id}"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
        </div>
      </div>
    </div>`;
  }).join('');
  list.querySelectorAll('.btn-edit-car').forEach(btn=>btn.onclick=()=>{const c=cars.find(c=>c.id==btn.dataset.id);if(c)openCarForm(c);});
  list.querySelectorAll('.btn-del-car').forEach(btn=>btn.onclick=async()=>{
    if(!confirm('Remover este carro?'))return;
    try{await api('DELETE',`api/cars.php?id=${btn.dataset.id}`);await loadCars();toast('Carro removido.');}
    catch(e){toast('Erro',e.message,'error');}
  });
}
async function loadCars(){
  document.getElementById('cars-loading').classList.remove('hidden');
  try{cars=await api('GET','api/cars.php');}catch{cars=[];}
  document.getElementById('cars-loading').classList.add('hidden');
  renderCars();
}

// ════ GATES ════
function buildGateIconGrid(){
  const g=document.getElementById('gate-icon-grid'); g.innerHTML='';
  GATE_ICONS.forEach(icon=>{
    const btn=document.createElement('button'); btn.type='button';
    btn.className='icon-btn'+(icon===selectedGateIcon?' selected':'');
    btn.textContent=icon;
    btn.onclick=()=>{selectedGateIcon=icon;document.querySelectorAll('.icon-btn').forEach(b=>b.classList.remove('selected'));btn.classList.add('selected');};
    g.appendChild(btn);
  });
}
function openGateForm(gate){
  editingGateId=gate?gate.id:null; selectedGateIcon=gate?gate.icon:'🏠';
  document.getElementById('gate-form-title').textContent=gate?'Editar Portão':'Novo Portão';
  document.getElementById('inp-gate-name').value=gate?gate.name:'';
  document.getElementById('inp-gate-relay').value=gate?gate.relay_id:'';
  document.getElementById('gate-name-count').textContent=gate?gate.name.length:0;
  document.getElementById('btn-gate-submit').textContent=gate?'Guardar':'Adicionar';
  buildGateIconGrid();
  document.getElementById('gate-form-wrapper').classList.remove('hidden');
  document.getElementById('btn-add-gate').classList.add('hidden');
  document.getElementById('gates-empty').classList.add('hidden');
  document.getElementById('inp-gate-name').focus();
}
function closeGateForm(){editingGateId=null;document.getElementById('gate-form-wrapper').classList.add('hidden');document.getElementById('btn-add-gate').classList.remove('hidden');renderGates();}
document.getElementById('inp-gate-name').oninput=e=>document.getElementById('gate-name-count').textContent=e.target.value.length;
document.getElementById('btn-add-gate').onclick=()=>openGateForm(null);
document.getElementById('btn-add-gate-empty').onclick=()=>openGateForm(null);
document.getElementById('btn-close-gate-form').onclick=closeGateForm;
document.getElementById('btn-gate-submit').onclick=async()=>{
  const name=document.getElementById('inp-gate-name').value.trim();
  const relayId=document.getElementById('inp-gate-relay').value.trim();
  if(!name){toast('Nome obrigatório','','error');return;}
  if(!relayId){toast('ID do relé obrigatório','','error');return;}
  const btn=document.getElementById('btn-gate-submit'); btn.disabled=true;
  try{
    const body={name,relayId,icon:selectedGateIcon};
    if(editingGateId){await api('PUT',`api/gates.php?id=${editingGateId}`,body);toast('Portão atualizado!','','success');}
    else{await api('POST','api/gates.php',body);toast('Portão adicionado!','','success');}
    await loadGates(); closeGateForm();
  }catch(e){toast('Erro',e.message,'error');}
  finally{btn.disabled=false;}
};
async function openGate(id,name,icon){
  try{await api('POST',`api/gates.php?id=${id}&action=open`);toast(`${icon} ${name}`,'✅ Sinal enviado!','success');}
  catch(e){toast('Erro ao abrir',e.message,'error');}
}
function renderGates(){
  const list=document.getElementById('gates-list');
  const empty=document.getElementById('gates-empty');
  document.getElementById('gate-count-lbl').textContent=`${gates.length} ${gates.length===1?'portão':'portões'}`;
  if(!gates.length){list.innerHTML='';empty.classList.remove('hidden');return;}
  empty.classList.add('hidden');
  list.innerHTML=gates.map(gate=>`
    <div class="gate-card">
      <div class="gate-inner">
        <div class="gate-icon-box">${gate.icon}</div>
        <div class="gate-info">
          <div class="gate-name">${gate.name}</div>
          <div class="gate-relay">relé: ${gate.relay_id}</div>
          ${!gate.owned?`<span class="badge badge-shared" style="margin-top:.3rem;display:inline-block">Partilhado por ${gate.sharedBy}</span>`:''}
        </div>
        <div class="gate-actions">
          <button class="btn btn-open btn-sm btn-open-gate" data-id="${gate.id}" data-name="${gate.name}" data-icon="${gate.icon}">▶ Abrir</button>
          <button class="btn btn-ghost btn-icon btn-detail-gate" data-id="${gate.id}" title="Detalhes"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></button>
          ${gate.owned?`
          <button class="btn btn-ghost btn-icon btn-edit-gate" data-id="${gate.id}" title="Editar"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
          <button class="btn btn-danger btn-icon btn-del-gate" data-id="${gate.id}" title="Remover"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
          `:''}
        </div>
      </div>
    </div>`).join('');
  list.querySelectorAll('.btn-open-gate').forEach(btn=>btn.onclick=()=>openGate(btn.dataset.id,btn.dataset.name,btn.dataset.icon));
  list.querySelectorAll('.btn-detail-gate').forEach(btn=>btn.onclick=()=>{const g=gates.find(g=>g.id==btn.dataset.id);if(g)openGateDetail(g);});
  list.querySelectorAll('.btn-edit-gate').forEach(btn=>btn.onclick=()=>{const g=gates.find(g=>g.id==btn.dataset.id);if(g)openGateForm(g);});
  list.querySelectorAll('.btn-del-gate').forEach(btn=>btn.onclick=async()=>{
    if(!confirm('Remover este portão?'))return;
    try{await api('DELETE',`api/gates.php?id=${btn.dataset.id}`);await loadGates();toast('Portão removido.');}
    catch(e){toast('Erro',e.message,'error');}
  });
}
async function loadGates(){
  document.getElementById('gates-loading').classList.remove('hidden');
  try{gates=await api('GET','api/gates.php');}catch{gates=[];}
  document.getElementById('gates-loading').classList.add('hidden');
  renderGates();
}

// ════ GATE DETAIL MODAL ════
function openGateDetail(gate){
  currentGate=gate;
  document.getElementById('modal-gate-title').textContent=`${gate.icon} ${gate.name}`;
  // reset tabs
  document.querySelectorAll('.modal-tab').forEach(t=>t.classList.toggle('active',t.dataset.mtab==='log'));
  ['mtab-log','mtab-cars','mtab-shares','mtab-schedules'].forEach(id=>
    document.getElementById(id).classList.toggle('hidden',id!=='mtab-log')
  );
  document.getElementById('modal-gate').classList.remove('hidden');
  loadGateLog(gate.id);
}
document.getElementById('btn-close-gate-modal').onclick=()=>document.getElementById('modal-gate').classList.add('hidden');

// Modal tabs
document.querySelectorAll('.modal-tab').forEach(tab=>{
  tab.onclick=()=>{
    document.querySelectorAll('.modal-tab').forEach(t=>t.classList.remove('active'));
    tab.classList.add('active');
    const n=tab.dataset.mtab;
    ['mtab-log','mtab-cars','mtab-shares','mtab-schedules'].forEach(id=>
      document.getElementById(id).classList.toggle('hidden',id!==('mtab-'+n))
    );
    if(!currentGate)return;
    if(n==='log')loadGateLog(currentGate.id);
    if(n==='cars')loadGateCars(currentGate.id);
    if(n==='shares')loadGateShares(currentGate.id);
    if(n==='schedules')loadGateSchedules(currentGate.id);
  };
});

// ── Log ──
async function loadGateLog(gateId){
  document.getElementById('mtab-log').innerHTML='<div class="skeleton" style="height:4rem;border-radius:.5rem;margin-top:.5rem"></div>';
  try{
    const rows=await api('GET',`api/gates.php?id=${gateId}&action=log`);
    if(!rows.length){document.getElementById('mtab-log').innerHTML='<p style="color:var(--muted);font-size:.85rem;padding:.5rem 0">Sem registos ainda.</p>';return;}
    document.getElementById('mtab-log').innerHTML=`<div style="background:var(--secondary);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:.5rem">${rows.map(r=>`<div class="log-item"><div class="log-icon">🔓</div><div class="log-info"><div>${r.users?.display_name||r.users?.email||r.plate||'Sistema'}</div><div class="log-time">${fmt(r.opened_at)} · ${r.method}${r.ip_address?' · '+r.ip_address:''}</div></div></div>`).join('')}</div>`;
  }catch(e){document.getElementById('mtab-log').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// ── Carros associados ──
async function loadGateCars(gateId){
  document.getElementById('mtab-cars').innerHTML='<div class="skeleton" style="height:4rem;border-radius:.5rem;margin-top:.5rem"></div>';
  try{
    const linked=await api('GET',`api/gates.php?id=${gateId}&action=linked-cars`);
    const linkedIds=linked.map(l=>l.car_id);
    const available=cars.filter(c=>!linkedIds.includes(c.id));
    document.getElementById('mtab-cars').innerHTML=`
      <div style="margin-top:.5rem">
        <div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.35rem">Carros associados a este portão</div>
        <div style="background:var(--secondary);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:.75rem">
          ${linked.length?linked.map(l=>`
            <div class="share-item">
              <div><div style="font-weight:500">${l.cars?.plate||'—'} · ${l.cars?.brand||'—'}</div></div>
              <button class="btn btn-danger btn-icon btn-unlink" data-lid="${l.id}" title="Remover associação"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>`).join(''):'<div style="padding:.75rem;font-size:.85rem;color:var(--muted)">Sem carros associados.</div>'}
        </div>
        ${available.length?`
        <div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.35rem">Associar carro</div>
        <div style="display:flex;gap:.5rem">
          <select id="sel-car" class="input" style="flex:1;font-size:.85rem;padding:.5rem .75rem">
            <option value="">Selecionar carro...</option>
            ${available.map(c=>`<option value="${c.id}">${c.plate} · ${c.brand}</option>`).join('')}
          </select>
          <button id="btn-link-car" class="btn btn-primary btn-sm">Associar</button>
        </div>`:'<p style="font-size:.8rem;color:var(--muted)">Todos os carros já estão associados.</p>'}
      </div>`;
    document.getElementById('btn-link-car')?.addEventListener('click',async()=>{
      const carId=document.getElementById('sel-car').value;
      if(!carId){toast('Seleciona um carro','','error');return;}
      try{await api('POST',`api/gates.php?id=${gateId}&action=link-car`,{carId:parseInt(carId)});toast('Carro associado!','','success');loadGateCars(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    });
    document.querySelectorAll('.btn-unlink').forEach(btn=>btn.addEventListener('click',async()=>{
      try{await api('DELETE',`api/gates.php?id=${gateId}&action=link-car&link_id=${btn.dataset.lid}`);toast('Associação removida.');loadGateCars(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    }));
  }catch(e){document.getElementById('mtab-cars').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// ── Acesso partilhado ──
async function loadGateShares(gateId){
  document.getElementById('mtab-shares').innerHTML='<div class="skeleton" style="height:4rem;border-radius:.5rem;margin-top:.5rem"></div>';
  try{
    const rows=await api('GET',`api/gates.php?id=${gateId}&action=shares`);
    document.getElementById('mtab-shares').innerHTML=`
      <div style="margin-top:.5rem">
        <div style="background:var(--secondary);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:.75rem">
          ${rows.length?rows.map(s=>`
            <div class="share-item">
              <div><div style="font-weight:500;font-size:.85rem">${s.shared_email}</div><div class="log-time">${s.expires_at?'Expira: '+fmt(s.expires_at):'Sem expiração'}</div></div>
              <button class="btn btn-danger btn-icon btn-del-share" data-sid="${s.id}"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
            </div>`).join(''):'<div style="padding:.75rem;font-size:.85rem;color:var(--muted)">Sem acessos partilhados.</div>'}
        </div>
        <div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.35rem">Novo acesso</div>
        <div style="display:flex;flex-direction:column;gap:.5rem">
          <input id="inp-share-email" class="input" type="email" placeholder="Email"/>
          <input id="inp-share-expires" class="input" type="datetime-local"/>
          <div class="input-hint" style="text-align:left">Data de expiração (opcional)</div>
          <button id="btn-add-share" class="btn btn-primary btn-sm">Partilhar Acesso</button>
        </div>
      </div>`;
    document.getElementById('btn-add-share').addEventListener('click',async()=>{
      const email=document.getElementById('inp-share-email').value.trim();
      const exp=document.getElementById('inp-share-expires').value||null;
      if(!email){toast('Email obrigatório','','error');return;}
      try{await api('POST',`api/gates.php?id=${gateId}&action=share`,{email,expiresAt:exp?new Date(exp).toISOString():null});toast('Acesso partilhado!','','success');loadGateShares(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    });
    document.querySelectorAll('.btn-del-share').forEach(btn=>btn.addEventListener('click',async()=>{
      try{await api('DELETE',`api/gates.php?id=${gateId}&action=share&share_id=${btn.dataset.sid}`);toast('Acesso removido.');loadGateShares(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    }));
  }catch(e){document.getElementById('mtab-shares').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// ── Agendamentos ──
async function loadGateSchedules(gateId){
  document.getElementById('mtab-schedules').innerHTML='<div class="skeleton" style="height:4rem;border-radius:.5rem;margin-top:.5rem"></div>';
  try{
    const rows=await api('GET',`api/gates.php?id=${gateId}&action=schedules`);
    selectedDays=new Set();
    document.getElementById('mtab-schedules').innerHTML=`
      <div style="margin-top:.5rem">
        <div style="background:var(--secondary);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:.75rem">
          ${rows.length?rows.map(s=>`
            <div class="share-item">
              <div>
                <div style="font-weight:500;font-size:.85rem">${s.label||'Agendamento'} — ${s.time_start}</div>
                <div style="display:flex;gap:.3rem;margin-top:.3rem;flex-wrap:wrap">
                  ${s.days.split(',').map(d=>`<span style="font-size:.65rem;padding:.1rem .35rem;border-radius:.25rem;background:${s.active?'var(--primary)':'var(--border)'};color:${s.active?'#fff':'var(--muted)'}">${DAY_LABELS[d]||d}</span>`).join('')}
                </div>
              </div>
              <div style="display:flex;gap:.3rem">
                <button class="btn btn-icon ${s.active?'btn-warning':'btn-success'} btn-toggle-sched" data-sid="${s.id}" data-active="${s.active}">${s.active?'⏸':'▶'}</button>
                <button class="btn btn-danger btn-icon btn-del-sched" data-sid="${s.id}"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
              </div>
            </div>`).join(''):'<div style="padding:.75rem;font-size:.85rem;color:var(--muted)">Sem agendamentos.</div>'}
        </div>
        <div style="border:1px solid var(--border);border-radius:var(--radius);padding:1rem">
          <div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.75rem">Novo Agendamento</div>
          <div class="form-group"><label class="label">Hora</label><input id="inp-sched-time" class="input" type="time"/></div>
          <div class="form-group"><label class="label">Dias</label><div class="days-row" id="days-row">${DAY_LABELS.map((d,i)=>`<button type="button" class="day-btn" data-day="${i}">${d}</button>`).join('')}</div></div>
          <div class="form-group"><label class="label">Etiqueta (opcional)</label><input id="inp-sched-label" class="input" type="text" placeholder="Ex: Abertura diária" maxlength="60"/></div>
          <button id="btn-add-sched" class="btn btn-primary btn-sm">Adicionar Agendamento</button>
        </div>
      </div>`;
    // Day buttons
    document.querySelectorAll('.day-btn').forEach(btn=>btn.addEventListener('click',()=>{
      const d=parseInt(btn.dataset.day);
      if(selectedDays.has(d)){selectedDays.delete(d);btn.classList.remove('active');}
      else{selectedDays.add(d);btn.classList.add('active');}
    }));
    document.getElementById('btn-add-sched').addEventListener('click',async()=>{
      const time=document.getElementById('inp-sched-time').value;
      const label=document.getElementById('inp-sched-label').value.trim();
      if(!time){toast('Hora obrigatória','','error');return;}
      if(!selectedDays.size){toast('Seleciona pelo menos um dia','','error');return;}
      try{
        await api('POST',`api/gates.php?id=${gateId}&action=schedule`,{time,days:[...selectedDays].sort().join(','),label});
        toast('Agendamento adicionado!','','success');loadGateSchedules(gateId);
      }catch(e){toast('Erro',e.message,'error');}
    });
    document.querySelectorAll('.btn-toggle-sched').forEach(btn=>btn.addEventListener('click',async()=>{
      const active=btn.dataset.active==='1'||btn.dataset.active==='true';
      try{await api('PATCH',`api/gates.php?id=${gateId}&action=schedule&schedule_id=${btn.dataset.sid}`,{active:!active});loadGateSchedules(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    }));
    document.querySelectorAll('.btn-del-sched').forEach(btn=>btn.addEventListener('click',async()=>{
      if(!confirm('Remover agendamento?'))return;
      try{await api('DELETE',`api/gates.php?id=${gateId}&action=schedule&schedule_id=${btn.dataset.sid}`);loadGateSchedules(gateId);}
      catch(e){toast('Erro',e.message,'error');}
    }));
  }catch(e){document.getElementById('mtab-schedules').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// ════ LOAD ALL ════
async function loadAll(){
  await Promise.all([loadCars(),loadGates()]);
  updateSub();
  document.getElementById('admin-link-card').classList.toggle('hidden',!currentUser?.isAdmin);
}

// ════ INIT ════
(async()=>{
  try{
    currentUser=await api('GET','api/auth.php?action=user');
    await loadAll(); showPage('app');
  }catch{showPage('auth');}
})();
</script>
</body>
</html>