<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

session_start();
$admin = $_SESSION['admin_user'] ?? null;
if (!$admin) {
    header('Location: admin_panel.php');
    exit;
}

action:
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$email = $_GET['email'] ?? '';
$mode = $_GET['mode'] ?? '';

$validActions = ['delete', 'toggle'];
if (!in_array($action, $validActions, true) || !$id) {
    http_response_code(400);
    $errorMessage = 'Ação inválida.';
} else {
    $errorMessage = '';
}

if ($action === 'delete') {
    $pageTitle = 'Eliminar Utilizador';
    $pageSubtitle = 'Indica a razão para eliminar o utilizador.';
    $buttonLabel = 'Eliminar Utilizador';
} else {
    $pageTitle = $mode === 'demote' ? 'Remover Admin' : 'Promover Admin';
    $pageSubtitle = $mode === 'demote'
        ? 'Indica a razão para remover o acesso de administrador.'
        : 'Indica a razão para promover o utilizador a administrador.';
    $buttonLabel = $mode === 'demote' ? 'Remover Admin' : 'Tornar Admin';
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    body{background:var(--bg);color:var(--fg)}
    .action-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
    .action-card{width:min(34rem,100%);background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;box-shadow:0 24px 64px rgba(0,0,0,.18)}
    .action-card h1{font-family:var(--font-d);font-size:1.35rem;letter-spacing:.08em;margin-bottom:.5rem;color:var(--primary)}
    .action-card p{color:var(--muted);line-height:1.75;margin-bottom:1.5rem}
    .action-footer{display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;justify-content:flex-end;margin-top:1rem}
    .action-note{font-size:.83rem;color:var(--muted);margin-top:.75rem}
  </style>
</head>
<body>
  <div class="action-page">
    <div class="action-card">
      <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></h1>
      <p><?= htmlspecialchars($pageSubtitle, ENT_QUOTES) ?></p>

      <?php if ($errorMessage): ?>
        <div class="err"><?= htmlspecialchars($errorMessage, ENT_QUOTES) ?></div>
      <?php else: ?>
        <div class="form-group">
          <label class="label">Email do utilizador</label>
          <input type="text" class="input" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>" disabled />
        </div>
        <div class="form-group">
          <label class="label">Motivo</label>
          <textarea id="reason-input" class="input" rows="6" placeholder="Escreve aqui o motivo da ação"></textarea>
        </div>
        <div id="action-error" class="err hidden"></div>
        <div class="action-footer">
          <a href="admin_panel.php" class="btn btn-ghost btn-sm">Cancelar</a>
          <button id="action-submit" class="btn btn-primary btn-sm"><?= htmlspecialchars($buttonLabel, ENT_QUOTES) ?></button>
        </div>
        <div class="action-note">Após confirmar, serás redirecionado de volta ao painel admin.</div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const action = <?= json_encode($action) ?>;
    const mode = <?= json_encode($mode) ?>;
    const userId = <?= json_encode($id) ?>;
    const token = <?= json_encode(bin2hex($admin['email'] ?? '')) ?>;

    async function api(method, url, data=null) {
      const o = {method, headers: {'Content-Type':'application/json','X-Admin-Auth': token}};
      if (data) o.body = JSON.stringify(data);
      const r = await fetch(url, o);
      if (!r.ok) {
        const err = await r.json().catch(() => ({}));
        throw new Error(err.error || 'Erro na API');
      }
      return r.status === 204 ? null : r.json();
    }

    function showError(message) {
      const err = document.getElementById('action-error');
      err.textContent = message;
      err.classList.remove('hidden');
    }

    document.getElementById('action-submit')?.addEventListener('click', async () => {
      const reason = document.getElementById('reason-input').value.trim();
      if (!reason) {
        showError('O motivo é obrigatório.');
        return;
      }
      try {
        if (action === 'delete') {
          await api('DELETE', `api/admin.php?action=user&id=${encodeURIComponent(userId)}`, { reason, close_site: true });
        } else if (action === 'toggle') {
          const isAdmin = mode === 'promote';
          await api('PATCH', `api/admin.php?action=user&id=${encodeURIComponent(userId)}`, { is_admin: isAdmin, reason });
        } else {
          throw new Error('Ação inválida.');
        }
        window.location.href = 'admin_panel.php';
      } catch (err) {
        showError(err.message || 'Erro ao processar a ação.');
      }
    });
  </script>
</body>
</html>
