<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

session_name('ABREJA_ADMIN'); session_start();
$user = $_SESSION["user"] ?? $_SESSION["admin_user"] ?? null;

$error = '';
$user = $_SESSION['admin_user'] ?? null;

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'] ?? '';
    $result   = supabase('users?email=eq.' . urlencode($email) . '&select=*');
    if (empty($result)) {
        $error = 'Email ou password incorretos';
    } else {
        $row = $result[0];
        if (!$row['is_admin']) {
            $error = 'Sem permissões de administrador';
        } elseif (!password_verify($password, $row['password'])) {
            $error = 'Email ou password incorretos';
        } else {
            $_SESSION['admin_user'] = [
                'id'           => $row['id'],
                'email'        => $row['email'],
                'displayName'  => $row['display_name'] ?? 'Admin',
                'isSuperAdmin' => (bool)($row['is_super_admin'] ?? false),
            ];
            header('Location: admin_panel.php');
            exit;
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_user']);
    header('Location: admin_panel.php');
    exit;
}

$user = $_SESSION['admin_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Admin — Abre Já</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--bg:hsl(220,20%,7%);--card:hsl(220,18%,11%);--secondary:hsl(220,15%,16%);--border:hsl(220,15%,18%);--fg:hsl(210,20%,92%);--muted:hsl(215,15%,50%);--primary:hsl(0,85%,55%);--success:hsl(142,70%,45%);--warning:hsl(38,92%,50%);--destructive:hsl(0,84%,60%);--radius:.75rem;--font-d:'Orbitron',monospace;--font-b:'Inter',sans-serif}
    body{background:var(--bg);color:var(--fg);font-family:var(--font-b);min-height:100vh}
    .hidden{display:none!important}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.45rem 1rem;border-radius:calc(var(--radius) - 2px);font-family:var(--font-d);font-size:.72rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;border:none;transition:all .15s}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{opacity:.85}
    .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}.btn-ghost:hover{color:var(--fg);background:var(--secondary)}
    .btn-success{background:hsl(142 70% 45%/.15);color:var(--success);border:1px solid hsl(142 70% 45%/.3)}
    .btn-warning{background:hsl(38 92% 50%/.15);color:var(--warning);border:1px solid hsl(38 92% 50%/.3)}
    .btn-danger{background:hsl(0 84% 60%/.1);color:var(--destructive);border:1px solid hsl(0 84% 60%/.3)}
    .btn-sm{font-size:.65rem;padding:.3rem .65rem}
    .input{width:100%;background:var(--secondary);border:1px solid var(--border);border-radius:calc(var(--radius) - 2px);color:var(--fg);padding:.6rem .85rem;font-size:.9rem;outline:none;transition:border-color .15s}
    .input:focus{border-color:hsl(0 85% 55%/.5)}
    .label{display:block;font-size:.7rem;font-weight:500;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.4rem}
    .form-group{margin-bottom:1rem}
    /* Login */
    .login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
    .login-box{width:100%;max-width:22rem}
    .login-title{font-family:var(--font-d);font-size:1.25rem;font-weight:700;letter-spacing:.05em;margin-bottom:.25rem;color:var(--warning)}
    .login-sub{color:var(--muted);font-size:.875rem;margin-bottom:2rem}
    .err{background:hsl(0 84% 60%/.1);border:1px solid hsl(0 84% 60%/.3);border-radius:calc(var(--radius) - 2px);padding:.6rem .9rem;font-size:.85rem;color:var(--destructive);margin-bottom:1rem}
    /* App */
    header{position:sticky;top:0;z-index:10;background:hsl(220 20% 7%/.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border)}
    .header-inner{max-width:64rem;margin:0 auto;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between}
    .main{max-width:64rem;margin:0 auto;padding:1.5rem 1rem}
    .stat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin-bottom:1.5rem}
    @media(min-width:640px){.stat-grid{grid-template-columns:repeat(5,1fr)}}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1rem}
    .stat-value{font-family:var(--font-d);font-size:1.75rem;font-weight:700;color:var(--primary)}
    .stat-label{font-size:.72rem;color:var(--muted);margin-top:.2rem}
    .tabs{display:flex;gap:.25rem;background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.3rem;margin-bottom:1.5rem;flex-wrap:wrap}
    .tab{flex:1;min-width:5rem;padding:.45rem .5rem;border-radius:calc(var(--radius) - 4px);border:none;background:transparent;color:var(--muted);font-family:var(--font-d);font-size:.58rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:all .15s}
    .tab.active{background:var(--secondary);color:var(--fg)}
    .card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1rem}
    .card-title{font-family:var(--font-d);font-size:.7rem;font-weight:700;letter-spacing:.15em;color:var(--muted);text-transform:uppercase;margin-bottom:1.25rem}
    .user-row{display:flex;align-items:center;gap:.75rem;padding:.75rem;border-radius:calc(var(--radius) - 2px);transition:background .15s;cursor:pointer}
    .user-row:hover{background:var(--secondary)}
    .avatar{width:2.25rem;height:2.25rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;flex-shrink:0;font-family:var(--font-d)}
    .user-info{flex:1;min-width:0}
    .user-name{font-size:.875rem;font-weight:500}
    .user-email{font-size:.75rem;color:var(--muted)}
    .badge{font-size:.6rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:.15rem .45rem;border-radius:.3rem;margin-left:.25rem}
    .badge-admin{background:hsl(0 85% 55%/.15);color:var(--primary);border:1px solid hsl(0 85% 55%/.3)}
    .badge-blocked{background:hsl(0 0% 50%/.15);color:var(--muted);border:1px solid hsl(0 0% 50%/.2)}
    .badge-super{background:hsl(38 92% 50%/.15);color:var(--warning);border:1px solid hsl(38 92% 50%/.3)}
    .log-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .9rem;border-bottom:1px solid var(--border);font-size:.82rem}
    .log-item:last-child{border-bottom:none}
    .log-icon{width:1.75rem;height:1.75rem;border-radius:.4rem;background:hsl(0 85% 55%/.08);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0}
    .log-time{font-size:.7rem;color:var(--muted);margin-top:.1rem}
    .skeleton{background:var(--secondary);border-radius:.5rem;animation:pulse 1.5s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
    #toast-wrap{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem}
    .toast{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.75rem 1.1rem;font-size:.875rem;box-shadow:0 8px 24px rgba(0,0,0,.4);animation:toastIn .2s ease;max-width:22rem}
    .toast.success{border-color:hsl(142 70% 45%/.4)}.toast.error{border-color:hsl(0 84% 60%/.4)}
    @keyframes toastIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
    .modal-overlay{position:fixed;inset:0;background:hsl(220 20% 4%/.85);backdrop-filter:blur(4px);z-index:100;display:flex;align-items:center;justify-content:center;padding:1rem}
    .modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;width:100%;max-width:30rem;max-height:88vh;overflow-y:auto;animation:slideIn .2s ease}
    @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
    .modal-title{font-family:var(--font-d);font-size:.85rem;font-weight:700;letter-spacing:.1em;margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between}
    .close-btn{background:none;border:none;color:var(--muted);cursor:pointer;padding:.25rem;border-radius:.375rem}.close-btn:hover{color:var(--fg)}
    svg{display:inline-block;vertical-align:middle}
  </style>
</head>
<body>
<div id="toast-wrap"></div>

<?php if (!$user): ?>
<!-- LOGIN -->
<div class="login-wrap">
  <div class="login-box">
    <div style="text-align:center;margin-bottom:2rem">
      <div style="width:3.5rem;height:3.5rem;border-radius:1rem;background:hsl(38 92% 50%/.1);border:1px solid hsl(38 92% 50%/.2);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem">⭐</div>
      <div class="login-title">PAINEL ADMIN</div>
      <div class="login-sub">Abre Já — Área Restrita</div>
    </div>
    <?php if ($error): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif ?>
    <form method="POST">
      <div class="form-group"><label class="label">Email</label><input class="input" type="email" name="email" placeholder="admin@exemplo.com" required/></div>
      <div class="form-group"><label class="label">Password</label><input class="input" type="password" name="password" placeholder="••••••••" required/></div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:.7rem;font-size:.8rem">Entrar no Painel</button>
    </form>
    <div style="text-align:center;margin-top:1.5rem"><a href="index.php" style="font-size:.8rem;color:var(--muted);text-decoration:none">← Voltar à app</a></div>
  </div>
</div>

<?php else: ?>
<!-- PAINEL -->
<header>
  <div class="header-inner">
    <div style="display:flex;align-items:center;gap:.75rem">
      <div style="font-family:var(--font-d);font-size:.85rem;font-weight:700;letter-spacing:.1em;color:var(--warning)">⭐ ADMIN</div>
      <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($user['displayName'] ?? $user['email']) ?></div>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
      <a href="api/admin.php?action=export&type=users" class="btn btn-ghost btn-sm" target="_blank">⬇ Users</a>
      <a href="api/admin.php?action=export&type=access-log" class="btn btn-ghost btn-sm" target="_blank">⬇ Log</a>
      <a href="index.php" class="btn btn-ghost btn-sm">App</a>
      <a href="admin_panel.php?logout=1" class="btn btn-danger btn-sm">Sair</a>
    </div>
  </div>
</header>

<div class="main">
  <div class="stat-grid">
    <div class="stat-card"><div class="stat-value" id="s-users">—</div><div class="stat-label">Utilizadores</div></div>
    <div class="stat-card"><div class="stat-value" id="s-cars">—</div><div class="stat-label">Carros</div></div>
    <div class="stat-card"><div class="stat-value" id="s-gates">—</div><div class="stat-label">Portões</div></div>
    <div class="stat-card"><div class="stat-value" id="s-blocked">—</div><div class="stat-label">Bloqueados</div></div>
    <div class="stat-card"><div class="stat-value" id="s-today">—</div><div class="stat-label">Acessos Hoje</div></div>
  </div>

  <nav class="tabs">
    <button class="tab active" data-tab="users">👥 Utilizadores</button>
    <button class="tab" data-tab="log">📋 Acessos</button>
    <button class="tab" data-tab="adminlog">🛡️ Admin Log</button>
    <button class="tab" data-tab="settings">⚙️ Definições</button>
  </nav>

  <div id="tab-users"><div id="users-wrap"><div class="skeleton" style="height:8rem;border-radius:var(--radius)"></div></div></div>
  <div id="tab-log" class="hidden"><div id="log-wrap"></div></div>
  <div id="tab-adminlog" class="hidden"><div id="adminlog-wrap"></div></div>
  <div id="tab-settings" class="hidden">
    <div class="card">
      <div class="card-title">Modo Manutenção</div>
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:1rem">Quando ativo, apenas admins conseguem entrar.</p>
      <div class="form-group"><label class="label">Mensagem</label><input id="inp-maint" class="input" type="text" placeholder="Sistema em manutenção..."/></div>
      <div style="display:flex;gap:.5rem">
        <button id="btn-maint-on" class="btn btn-warning">Ativar</button>
        <button id="btn-maint-off" class="btn btn-success">Desativar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div id="modal" class="modal-overlay hidden">
  <div class="modal">
    <div class="modal-title">
      <span id="modal-title">Utilizador</span>
      <button class="close-btn" onclick="document.getElementById('modal').classList.add('hidden')">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div id="modal-body"></div>
    <div id="modal-actions" style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.25rem"></div>
  </div>
</div>

<script>
const IS_SUPER = <?= $user['isSuperAdmin'] ? 'true' : 'false' ?>;
const MY_ID    = <?= (int)$user['id'] ?>;
let allUsers   = [];




async function api(action, body = null) {
    // Tenta pegar o email da sessão PHP injetada
    const adminEmail = '<?php echo isset($user["email"]) ? base64_encode($user["email"]) : ""; ?>';
    
    if (!adminEmail) {
        console.error("Erro: Usuário não identificado pelo PHP.");
        return;
    }

    const url = `api/admin.php?action=${action}`;
    const options = {
        method: body ? 'POST' : 'GET',
        headers: {
            'X-Admin-Token': adminEmail,
            'Content-Type': 'application/json'
        }
    };
    if (body) options.body = JSON.stringify(body);

    try {
        const res = await fetch(url, options);
        if (res.status === 403) {
            console.error("Acesso Negado na API");
            return { error: "Acesso Negado" };
        }
        return await res.json();
    } catch (e) {
        console.error("Erro na chamada fetch:", e);
    }
}

// Função para carregar tudo ao iniciar
async function loadDashboard() {
    const stats = await api('stats');
    if (stats && !stats.error) {
        document.getElementById('totalUsers').innerText = stats.totalUsers || 0;
        document.getElementById('totalCars').innerText = stats.totalCars || 0;
        document.getElementById('totalGates').innerText = stats.totalGates || 0;
        document.getElementById('totalBlocked').innerText = stats.totalBlocked || 0;
        document.getElementById('accessesToday').innerText = stats.accessesToday || 0;
    }
    loadUsers(); // Carrega a tabela de utilizadores
}
window.onload = loadDashboard;
`;
    
    try {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Token': adminToken
            },
            body: body ? JSON.stringify(body) : null
        });
        if (res.status === 403) { window.location.href = 'index.php'; return; }
        return await res.json();
    } catch (e) {
        console.error("Erro na API:", e);
    }
}
`;
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-Admin-Token': adminToken
        }
    };
    if (body) options.body = JSON.stringify(body);
    
    const res = await fetch(url, options);
    if (res.status === 403) {
        window.location.href = 'index.php';
        return;
    }
    return res.json();
}
};
  if (body) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(body); }
  const r = await fetch(url, o);
  const d = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(d.error || 'Erro desconhecido');
  return d;
}

function toast(title, desc='', type='') {
  const el = document.createElement('div');
  el.className = 'toast' + (type ? ' '+type : '');
  el.innerHTML = `<div style="font-weight:600">${title}</div>${desc ? `<div style="color:var(--muted);font-size:.8rem">${desc}</div>` : ''}`;
  document.getElementById('toast-wrap').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

function fmt(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('pt-PT', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

function ini(name, email) { return (name||email||'?')[0].toUpperCase(); }

// Tabs
document.querySelectorAll('.tab').forEach(tab => {
  tab.onclick = () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const n = tab.dataset.tab;
    ['users','log','adminlog','settings'].forEach(id => document.getElementById('tab-'+id).classList.toggle('hidden', id !== n));
    if (n==='log') loadLog();
    if (n==='adminlog') loadAdminLog();
    if (n==='settings') loadSettings();
  };
});

// Stats
async function loadStats() {
  try {
    const s = await api('GET', 'api/admin.php?action=stats');
    document.getElementById('s-users').textContent   = s.totalUsers;
    document.getElementById('s-cars').textContent    = s.totalCars;
    document.getElementById('s-gates').textContent   = s.totalGates;
    document.getElementById('s-blocked').textContent = s.totalBlocked;
    document.getElementById('s-today').textContent   = s.accessesToday;
  } catch(e) { console.error(e); }
}

// Users
async function loadUsers() {
  document.getElementById('users-wrap').innerHTML = '<div class="skeleton" style="height:8rem;border-radius:var(--radius)"></div>';
  try {
    allUsers = await api('GET', 'api/admin.php?action=users');
    if (!allUsers.length) { document.getElementById('users-wrap').innerHTML = '<p style="color:var(--muted);padding:1rem">Sem utilizadores.</p>'; return; }
    document.getElementById('users-wrap').innerHTML = `<div class="card" style="padding:.5rem">${allUsers.map(u => `
      <div class="user-row" data-uid="${u.id}">
        <div class="avatar" style="background:${u.avatarColor}22;color:${u.avatarColor};border:1px solid ${u.avatarColor}44">${ini(u.displayName,u.email)}</div>
        <div class="user-info">
          <div class="user-name">${u.displayName||'—'}
            ${u.isSuperAdmin?'<span class="badge badge-super">⭐ Super</span>':''}
            ${u.isAdmin&&!u.isSuperAdmin?'<span class="badge badge-admin">Admin</span>':''}
            ${u.isBlocked?'<span class="badge badge-blocked">Bloqueado</span>':''}
          </div>
          <div class="user-email">${u.email} · ${fmt(u.createdAt)}</div>
        </div>
      </div>`).join('')}</div>`;
    document.querySelectorAll('.user-row').forEach(row => row.onclick = () => showUser(parseInt(row.dataset.uid)));
  } catch(e) { document.getElementById('users-wrap').innerHTML = `<p style="color:var(--destructive)">${e.message}</p>`; }
}

async function showUser(uid) {
  const u = allUsers.find(u => u.id === uid); if (!u) return;
  document.getElementById('modal-title').textContent = u.displayName || u.email;
  document.getElementById('modal-body').innerHTML = '<div class="skeleton" style="height:6rem;border-radius:.5rem"></div>';
  document.getElementById('modal-actions').innerHTML = '';
  document.getElementById('modal').classList.remove('hidden');
  try {
    const d = await api('GET', `api/admin.php?action=user-detail&id=${uid}`);
    document.getElementById('modal-body').innerHTML = `
      <div style="font-size:.82rem;color:var(--muted);margin-bottom:1rem">
        <div>📧 ${u.email}</div><div>📅 ${fmt(d.user.created_at)}</div>
        ${u.isBlocked&&u.blockReason?`<div style="color:var(--destructive);margin-top:.2rem">🚫 ${u.blockReason}</div>`:''}
      </div>
      <div style="display:flex;gap:.5rem;margin-bottom:1rem">
        <div class="stat-card" style="padding:.5rem .75rem;flex:1"><div style="font-family:var(--font-d);font-size:1.25rem;color:var(--primary)">${d.cars.length}</div><div style="font-size:.7rem;color:var(--muted)">Carros</div></div>
        <div class="stat-card" style="padding:.5rem .75rem;flex:1"><div style="font-family:var(--font-d);font-size:1.25rem;color:var(--primary)">${d.gates.length}</div><div style="font-size:.7rem;color:var(--muted)">Portões</div></div>
      </div>
      ${d.cars.length?`<div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.3rem">Carros</div><div style="background:var(--secondary);border:1px solid var(--border);border-radius:.5rem;overflow:hidden;margin-bottom:.75rem">${d.cars.map(c=>`<div style="font-size:.8rem;padding:.35rem .75rem;border-bottom:1px solid var(--border)">${c.plate} · ${c.brand}</div>`).join('')}</div>`:''}
      ${d.gates.length?`<div style="font-size:.72rem;color:var(--muted);text-transform:uppercase;margin-bottom:.3rem">Portões</div><div style="background:var(--secondary);border:1px solid var(--border);border-radius:.5rem;overflow:hidden">${d.gates.map(g=>`<div style="font-size:.8rem;padding:.35rem .75rem;border-bottom:1px solid var(--border)">${g.icon} ${g.name}</div>`).join('')}</div>`:''} `;
    const actions = document.getElementById('modal-actions');
    if (u.id !== MY_ID) {
      if (u.isBlocked) {
        const b = document.createElement('button'); b.className='btn btn-success'; b.textContent='✓ Desbloquear';
        b.onclick=async()=>{try{await api('DELETE',`api/admin.php?action=block&id=${uid}`);toast('Desbloqueado','','success');document.getElementById('modal').classList.add('hidden');await loadUsers();await loadStats();}catch(e){toast('Erro',e.message,'error');}};
        actions.appendChild(b);
      } else {
        const b = document.createElement('button'); b.className='btn btn-warning'; b.textContent='🚫 Bloquear';
        b.onclick=async()=>{const r=prompt('Motivo (opcional):');try{await api('POST',`api/admin.php?action=block&id=${uid}`,{reason:r});toast('Bloqueado','','success');document.getElementById('modal').classList.add('hidden');await loadUsers();await loadStats();}catch(e){toast('Erro',e.message,'error');}};
        actions.appendChild(b);
      }
      if (IS_SUPER) {
        const b = document.createElement('button'); b.className='btn'; b.style.background='var(--secondary)'; b.style.color='var(--fg)'; b.style.border='1px solid var(--border)';
        b.textContent = u.isAdmin ? '⬇️ Remover Admin' : '⭐ Tornar Admin';
        b.onclick=async()=>{try{await api('PATCH',`api/admin.php?action=toggle-admin&id=${uid}`);toast('Papel alterado','','success');document.getElementById('modal').classList.add('hidden');await loadUsers();}catch(e){toast('Erro',e.message,'error');}};
        actions.appendChild(b);
      }
      const bd = document.createElement('button'); bd.className='btn btn-danger'; bd.textContent='🗑️ Apagar Conta';
      bd.onclick=async()=>{if(!confirm(`Apagar conta de ${u.email}? Isto é irreversível.`))return;try{await api('DELETE',`api/admin.php?action=user&id=${uid}`);toast('Conta apagada','','success');document.getElementById('modal').classList.add('hidden');await loadUsers();await loadStats();}catch(e){toast('Erro',e.message,'error');}};
      actions.appendChild(bd);
    }
  } catch(e) { document.getElementById('modal-body').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`; }
}

// Log
async function loadLog() {
  document.getElementById('log-wrap').innerHTML='<div class="skeleton" style="height:8rem;border-radius:var(--radius)"></div>';
  try {
    const rows=await api('GET','api/admin.php?action=access-log');
    if(!rows.length){document.getElementById('log-wrap').innerHTML='<p style="color:var(--muted);padding:1rem">Sem registos.</p>';return;}
    document.getElementById('log-wrap').innerHTML=`<div class="card" style="padding:0;overflow:hidden">${rows.map(r=>`<div class="log-item"><div class="log-icon">🔓</div><div><div><strong>${r.gates?.name||'—'}</strong> · ${r.users?.display_name||r.users?.email||r.plate||'Sistema'}</div><div class="log-time">${fmt(r.opened_at)} · ${r.method}${r.ip_address?' · '+r.ip_address:''}</div></div></div>`).join('')}</div>`;
  }catch(e){document.getElementById('log-wrap').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// Admin log
async function loadAdminLog() {
  document.getElementById('adminlog-wrap').innerHTML='<div class="skeleton" style="height:8rem;border-radius:var(--radius)"></div>';
  try {
    const rows=await api('GET','api/admin.php?action=admin-log');
    if(!rows.length){document.getElementById('adminlog-wrap').innerHTML='<p style="color:var(--muted);padding:1rem">Sem ações.</p>';return;}
    document.getElementById('adminlog-wrap').innerHTML=`<div class="card" style="padding:0;overflow:hidden">${rows.map(r=>`<div class="log-item"><div class="log-icon">🛡️</div><div><div><strong>${r.action}</strong>${r.target?' → '+r.target:''}</div><div class="log-time">${r.users?.display_name||r.users?.email||'Sistema'} · ${fmt(r.created_at)}</div></div></div>`).join('')}</div>`;
  }catch(e){document.getElementById('adminlog-wrap').innerHTML=`<p style="color:var(--destructive)">${e.message}</p>`;}
}

// Settings
async function loadSettings() {
  try{const s=await api('GET','api/admin.php?action=settings');document.getElementById('inp-maint').value=s.maintenance_message||'';}catch(e){}
}
document.getElementById('btn-maint-on').onclick=async()=>{
  const msg=document.getElementById('inp-maint').value.trim()||'Sistema em manutenção.';
  try{await api('PATCH','api/admin.php?action=settings',{maintenance_mode:'true',maintenance_message:msg});toast('Manutenção ativada','','success');}catch(e){toast('Erro',e.message,'error');}
};
document.getElementById('btn-maint-off').onclick=async()=>{
  try{await api('PATCH','api/admin.php?action=settings',{maintenance_mode:'false'});toast('Manutenção desativada','','success');}catch(e){toast('Erro',e.message,'error');}
};

loadStats();
loadUsers();
</script>
<?php endif ?>
</body>
</html>