<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

session_start();

if (!isset($_SESSION['admin_user'])) {
    header('Location: admin_panel.php');
    exit;
}

$client_id = $_GET['id'] ?? '';
if (empty($client_id)) {
    die('ID do cliente não fornecido.');
}

$client_res = supabase('users?id=eq.' . urlencode($client_id) . '&select=*');
if (empty($client_res)) {
    die('Cliente não encontrado.');
}
$client = $client_res[0];

// Carregar carros e portões diretamente no PHP (mais seguro, sem expor dados no JS)
$cars  = supabase('cars?user_id=eq.' . urlencode($client_id) . '&select=*&order=created_at.desc');
$gates = supabase('gates?user_id=eq.' . urlencode($client_id) . '&select=*&order=name.asc');
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
    .empty{color:var(--muted);font-size:.85rem;padding:.5rem 0}
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
      <?php if (!empty($client['created_at'])): ?>
      <div class="info-item">
        <div class="info-label">Conta criada em</div>
        <div class="info-value"><?=htmlentities(date('d/m/Y', strtotime($client['created_at'])))?></div>
      </div>
      <?php endif; ?>
      <div class="info-item">
        <div class="info-label">Estado</div>
        <div class="info-value" style="color:<?=$client['is_blocked'] ? 'hsl(0,84%,60%)' : 'hsl(142,70%,45%)'?>">
          <?=$client['is_blocked'] ? 'Bloqueado' : 'Ativo'?>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3 class="card-title">🚗 Carros Registados</h3>
    <?php if (empty($cars)): ?>
      <p class="empty">Este cliente não tem nenhum carro associado.</p>
    <?php else: ?>
      <?php foreach ($cars as $car): ?>
        <div class="list-item">
          <div>
            <span class="plate-box"><?=htmlentities($car['plate'])?></span>
            <span style="margin-left:.75rem;font-weight:500;">
              <?=htmlentities(trim(($car['brand'] ?? '') . ' ' . ($car['model'] ?? '')))?>
            </span>
          </div>
          <?php if (!empty($car['color'])): ?>
            <span style="font-size:.75rem;color:var(--muted)"><?=htmlentities($car['color'])?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3 class="card-title">🚪 Portões do Sistema</h3>
    <?php if (empty($gates)): ?>
      <p class="empty">Nenhum portão encontrado no sistema.</p>
    <?php else: ?>
      <?php foreach ($gates as $gate): ?>
        <div class="list-item">
          <div>
            <span style="font-weight:600;font-size:.9rem;"><?=htmlentities($gate['name'])?></span>
            <br>
            <span style="font-size:.7rem;color:var(--muted)">
              Relay: <code><?=htmlentities($gate['relay_trigger'] ?? $gate['relay_id'] ?? 'N/D')?></code>
            </span>
          </div>
          <span style="font-size:.72rem;color:var(--success);background:hsl(142 70% 45%/.1);padding:.2rem .5rem;border-radius:.25rem;font-family:var(--font-d);font-weight:600;">
            Autorizado
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
</body>
</html>