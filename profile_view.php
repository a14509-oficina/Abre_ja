<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

session_start();

// Verificar se o administrador está logado
if (!isset($_SESSION['admin_user'])) {
    header('Location: admin_panel.php');
    exit;
}

$client_id = $_GET['id'] ?? '';

if (empty($client_id)) {
    die('ID do cliente não fornecido.');
}

// Procurar dados do cliente no Supabase
$client_res = supabase('users?id=eq.' . urlencode($client_id) . '&select=*');
if (empty($client_res)) {
    die('Cliente não encontrado.');
}
$client = $client_res[0];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Perfil de <?=htmlentities($client['display_name'] ?? $client['email'])?> — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --bg:hsl(220,20%,7%);--card:hsl(220,18%,11%);--secondary:hsl(220,15%,16%);
      --border:hsl(220,15%,18%);--fg:hsl(210,20%,92%);--muted:hsl(215,15%,50%);
      --primary:hsl(0,85%,55%);--success:hsl(142,70%,45%);--radius:.75rem;
      --font-d:'Orbitron',monospace;--font-b:'Inter',sans-serif;
    }
    body{background:var(--bg);color:var(--fg);font-family:var(--font-b);min-height:100vh;padding:2rem 1rem}
    .container{max-width:44rem;margin:0 auto}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:.45rem 1rem;border-radius:calc(var(--radius) - 2px);font-family:var(--font-d);font-size:.72rem;font-weight:600;text-transform:uppercase;cursor:pointer;border:none;transition:all .15s;text-decoration:none}
    .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}.btn-ghost:hover{color:var(--fg);background:var(--secondary)}
    .card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.5rem;margin-bottom:1.5rem}
    .card-title{font-family:var(--font-d);font-size:.75rem;font-weight:700;letter-spacing:.12em;color:var(--primary);text-transform:uppercase;margin-bottom:1rem}
    .info-grid{display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1rem}
    @media(min-width:480px){.info-grid{grid-template-columns:1fr 1fr}}
    .info-item{background:var(--secondary);padding:.75rem;border-radius:calc(var(--radius) - 4px);border:1px solid var(--border)}
    .info-label{font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem}
    .info-value{font-size:.9rem;font-weight:500}
    .list-item{display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--border)}
    .list-item:last-child{border-bottom:none}
    .plate-box{font-family:var(--font-d);font-weight:700;letter-spacing:.1em;background:var(--secondary);padding:.3rem .6rem;border-radius:.25rem;border:1px solid var(--border)}
  </style>
</head>
<body>

<div class="container">
  <div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;">
    <a href="admin_panel.php" class="btn btn-ghost">◀ Voltar ao Painel</a>
    <div style="font-family:var(--font-d);font-size:.8rem;color:var(--muted)">Ficha de Auditoria</div>
  </div>

  <div class="card">
    <h3 class="card-title">Dados Gerais</h3>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Nome de Exibição</div>
        <div class="info-value"><?=htmlentities($client['display_name'] ?? 'Não definido')?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Endereço de Email</div>
        <div class="info-value"><?=htmlentities($client['email'])?></div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3 class="card-title">🚗 Carros Registados</h3>
    <div id="admin-cars-list">
      <p style="color:var(--muted);font-size:.85rem;">A carregar veículos do cliente...</p>
    </div>
  </div>

  <div class="card">
    <h3 class="card-title">🚪 Portões do Sistema</h3>
    <div id="admin-gates-list">
      <p style="color:var(--muted);font-size:.85rem;">A carregar permissões de portões...</p>
    </div>
  </div>
</div>

<script>
// Passagem segura do ID do cliente
const targetClientId = <?php echo json_encode($client_id); ?>;
const adminToken = '<?=bin2hex($_SESSION['admin_user']['email'] ?? '')?>';

async function fetchAdmin(url) {
  const r = await fetch(url, {
    method: 'GET',
    headers: { 
      'X-Admin-Auth': adminToken, 
      'Content-Type': 'application/json' 
    }
  });
  if(!r.ok) throw new Error('Erro ao ler a API administrativa.');
  return r.json();
}

async function loadProfileData() {
  try {
    // 1. Chamar a API do Admin para recolher todos os carros do sistema
    const allCars = await fetchAdmin('api/admin.php?action=cars').catch(() => []);
    
    // Filtrar para isolar apenas as matrículas que pertencem a este cliente
    const clientCars = allCars.filter(c => c.user_id === targetClientId || c.owner_id === targetClientId);
    const carsBox = document.getElementById('admin-cars-list');

    if(!clientCars.length) {
      carsBox.innerHTML = '<p style="color:var(--muted);font-size:.85rem;padding:.5rem;">Este cliente não tem nenhum carro associado.</p>';
    } else {
      carsBox.innerHTML = clientCars.map(c => `
        <div class="list-item">
          <div>
            <span class="plate-box">${c.plate}</span>
            <span style="margin-left:.75rem;font-weight:500;">${c.brand || ''} ${c.model || ''}</span>
          </div>
        </div>
      `).join('');
    }

    // 2. Chamar a API do Admin para recolher os portões do ecossistema
    const gates = await fetchAdmin('api/admin.php?action=gates').catch(() => []);
    const gatesBox = document.getElementById('admin-gates-list');

    if(!gates.length) {
      gatesBox.innerHTML = '<p style="color:var(--muted);font-size:.85rem;padding:.5rem;">Nenhum portão encontrado.</p>';
    } else {
      gatesBox.innerHTML = gates.map(g => `
        <div class="list-item">
          <div>
            <span style="font-weight:600;font-size:.9rem;">${g.name}</span>
            <br><span style="font-size:.7rem;color:var(--muted)">Relay Trigger: <code>${g.relay_trigger || 'Padrão'}</code></span>
          </div>
          <div>
            <span style="font-size:.72rem;color:var(--success);background:hsl(142 70% 45%/.1);padding:.2rem .5rem;border-radius:.25rem;font-family:var(--font-d);font-weight:600;">Autorizado</span>
          </div>
        </div>
      `).join('');
    }

  } catch (e) {
    console.error(e);
    document.getElementById('admin-cars-list').innerHTML = `<p style="color:var(--primary);font-size:.85rem;">Erro ao carregar dados: ${e.message}</p>`;
  }
}

window.onload = loadProfileData;
</script>
</body>
</html>