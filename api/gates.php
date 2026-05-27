<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requireAuth();

$user   = getLoggedUser();
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';

// ── GET: listar portões (próprios + partilhados) ──────
if ($method === 'GET' && !$id && !$action) {
    $own = supabase('gates?user_id=eq.' . $userId . '&order=created_at.asc&select=id,name,icon,relay_id,created_at');
    $own = array_map(fn($r) => array_merge(formatGate($r), ['owned' => true]), $own);

    // Portões partilhados com este utilizador
    $shared = supabase(
        'gate_shares?shared_email=eq.' . urlencode($user['email']) .
        '&select=gate_id,expires_at,gates(id,name,icon,relay_id,created_at,users(display_name,email))'
    );
    $sharedGates = [];
    foreach ($shared as $s) {
        if ($s['expires_at'] && strtotime($s['expires_at']) < time()) continue;
        if (!isset($s['gates'])) continue;
        $g = $s['gates'];
        $sharedGates[] = array_merge(formatGate($g), [
            'owned'     => false,
            'sharedBy'  => $g['users']['display_name'] ?? $g['users']['email'],
            'expiresAt' => $s['expires_at'],
        ]);
    }
    jsonResponse(array_merge($own, $sharedGates));
}

// ── GET: histórico de acessos ─────────────────────────
if ($method === 'GET' && $id && $action === 'log') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $rows = supabase(
        'access_log?gate_id=eq.' . $id .
        '&order=opened_at.desc&limit=100' .
        '&select=id,opened_at,ip_address,method,plate,users(id,display_name,email)'
    );
    jsonResponse($rows);
}

// ── GET: agendamentos ─────────────────────────────────
if ($method === 'GET' && $id && $action === 'schedules') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $rows = supabase('schedules?gate_id=eq.' . $id . '&order=created_at.asc&select=*');
    jsonResponse($rows);
}

// ── GET: partilhas do portão ──────────────────────────
if ($method === 'GET' && $id && $action === 'shares') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $rows = supabase('gate_shares?gate_id=eq.' . $id . '&order=created_at.desc&select=*');
    jsonResponse($rows);
}

// ── POST: adicionar portão ────────────────────────────
if ($method === 'POST' && !$action) {
    $body    = getBody();
    $name    = trim($body['name'] ?? '');
    $icon    = trim($body['icon'] ?? '🏠');
    $relayId = trim($body['relayId'] ?? '');

    if (!$name || strlen($name) > 60) jsonResponse(['error' => 'Nome inválido (máx 60 chars)'], 400);
    if (!$relayId) jsonResponse(['error' => 'ID do relé obrigatório'], 400);

    $existing = supabase('gates?user_id=eq.' . $userId . '&select=id');
    if (count($existing) >= 10) jsonResponse(['error' => 'Limite de 10 portões atingido'], 400);

    $result = supabase('gates', 'POST', [
        'user_id'  => $userId,
        'name'     => $name,
        'icon'     => $icon,
        'relay_id' => $relayId,
    ]);
    if (empty($result[0])) jsonResponse(['error' => 'Erro ao adicionar portão'], 500);
    jsonResponse(array_merge(formatGate($result[0]), ['owned' => true]), 201);
}

// ── POST: abrir portão + registar acesso ──────────────
if ($method === 'POST' && $id && $action === 'open') {
    // Verificar se é dono ou tem acesso partilhado
    $gate = ownsGate($id, $userId);
    if (!$gate) {
        // Verificar partilha
        $share = supabase(
            'gate_shares?gate_id=eq.' . $id .
            '&shared_email=eq.' . urlencode($user['email']) .
            '&select=expires_at,gates(id,relay_id)'
        );
        if (empty($share)) jsonResponse(['error' => 'Acesso negado'], 403);
        if ($share[0]['expires_at'] && strtotime($share[0]['expires_at']) < time())
            jsonResponse(['error' => 'Acesso expirado'], 403);
        $gate = $share[0]['gates'];
    }

    supabase('access_log', 'POST', [
        'gate_id'    => $id,
        'user_id'    => $userId,
        'method'     => 'app',
        'ip_address' => clientIp(),
    ]);
    jsonResponse(['ok' => true, 'relay_id' => $gate['relay_id']]);
}

// ── POST: partilhar portão ────────────────────────────
if ($method === 'POST' && $id && $action === 'share') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $body      = getBody();
    $email     = strtolower(trim($body['email'] ?? ''));
    $expiresAt = trim($body['expiresAt'] ?? '') ?: null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['error' => 'Email inválido'], 400);
    if ($email === strtolower($user['email']))
        jsonResponse(['error' => 'Não podes partilhar contigo mesmo'], 400);

    // Verificar se já partilhado
    $exists = supabase('gate_shares?gate_id=eq.' . $id . '&shared_email=eq.' . urlencode($email) . '&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Já partilhado com este email'], 400);

    // Resolver user_id se existir
    $sharedUser = supabase('users?email=eq.' . urlencode($email) . '&select=id');
    $sharedUserId = $sharedUser[0]['id'] ?? null;

    $result = supabase('gate_shares', 'POST', [
        'gate_id'        => $id,
        'owner_id'       => $userId,
        'shared_email'   => $email,
        'shared_user_id' => $sharedUserId,
        'expires_at'     => $expiresAt,
    ]);
    jsonResponse($result[0] ?? [], 201);
}

// ── DELETE: remover partilha ──────────────────────────
if ($method === 'DELETE' && $id && $action === 'share') {
    $shareId = isset($_GET['share_id']) ? (int)$_GET['share_id'] : null;
    if (!$shareId) jsonResponse(['error' => 'share_id obrigatório'], 400);

    supabase('gate_shares?id=eq.' . $shareId . '&owner_id=eq.' . $userId, 'DELETE');
    jsonResponse(['ok' => true]);
}

// ── POST: adicionar agendamento ───────────────────────
if ($method === 'POST' && $id && $action === 'schedule') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $body  = getBody();
    $days  = trim($body['days'] ?? '');
    $time  = trim($body['time'] ?? '');
    $label = trim($body['label'] ?? '') ?: null;

    if (!$days || !$time) jsonResponse(['error' => 'Dias e hora são obrigatórios'], 400);
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) jsonResponse(['error' => 'Hora inválida (HH:MM)'], 400);

    $result = supabase('schedules', 'POST', [
        'gate_id'    => $id,
        'user_id'    => $userId,
        'label'      => $label,
        'days'       => $days,
        'time_start' => $time,
    ]);
    jsonResponse($result[0] ?? [], 201);
}

// ── PATCH: toggle agendamento ativo ──────────────────
if ($method === 'PATCH' && $id && $action === 'schedule') {
    $schedId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;
    if (!$schedId) jsonResponse(['error' => 'schedule_id obrigatório'], 400);

    $body = getBody();
    supabase('schedules?id=eq.' . $schedId . '&user_id=eq.' . $userId, 'PATCH', [
        'active' => (bool)($body['active'] ?? true),
    ]);
    jsonResponse(['ok' => true]);
}

// ── DELETE: remover agendamento ───────────────────────
if ($method === 'DELETE' && $id && $action === 'schedule') {
    $schedId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;
    if (!$schedId) jsonResponse(['error' => 'schedule_id obrigatório'], 400);
    supabase('schedules?id=eq.' . $schedId . '&user_id=eq.' . $userId, 'DELETE');
    jsonResponse(['ok' => true]);
}

// ── PUT: editar portão ────────────────────────────────
if ($method === 'PUT' && $id && !$action) {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $body    = getBody();
    $name    = trim($body['name'] ?? '');
    $icon    = trim($body['icon'] ?? '🏠');
    $relayId = trim($body['relayId'] ?? '');

    if (!$name || strlen($name) > 60) jsonResponse(['error' => 'Nome inválido'], 400);
    if (!$relayId) jsonResponse(['error' => 'ID do relé obrigatório'], 400);

    supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId, 'PATCH', [
        'name'     => $name,
        'icon'     => $icon,
        'relay_id' => $relayId,
    ]);
    jsonResponse(['ok' => true]);
}

// ── DELETE: remover portão ────────────────────────────
if ($method === 'DELETE' && $id && !$action) {
    supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId, 'DELETE');
    jsonResponse(['ok' => true]);
}

// ── GET: carros associados ao portão ──────────────
if ($method === 'GET' && $id && $action === 'linked-cars') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);
    $links = supabase('car_gate_links?gate_id=eq.'.$id.'&select=id,car_id,cars(id,plate,brand,color)');
    jsonResponse($links);
}

// ── POST: associar carro ao portão ───────────────
if ($method === 'POST' && $id && $action === 'link-car') {
    $gate = ownsGate($id, $userId);
    if (!$gate) jsonResponse(['error' => 'Portão não encontrado'], 404);
    $body  = getBody();
    $carId = (int)($body['carId'] ?? 0);
    if (!$carId) jsonResponse(['error' => 'carId obrigatório'], 400);
    // confirmar que o carro pertence ao user
    $car = supabase('cars?id=eq.'.$carId.'&user_id=eq.'.$userId.'&select=id');
    if (empty($car)) jsonResponse(['error' => 'Carro não encontrado'], 404);
    // verificar se já existe
    $exists = supabase('car_gate_links?car_id=eq.'.$carId.'&gate_id=eq.'.$id.'&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Já associado'], 400);
    $result = supabase('car_gate_links','POST',['car_id'=>$carId,'gate_id'=>$id,'user_id'=>$userId]);
    jsonResponse($result[0] ?? [],201);
}

// ── DELETE: remover associação carro-portão ───────
if ($method === 'DELETE' && $id && $action === 'link-car') {
    $linkId = isset($_GET['link_id']) ? (int)$_GET['link_id'] : 0;
    if (!$linkId) jsonResponse(['error' => 'link_id obrigatório'], 400);
    supabase('car_gate_links?id=eq.'.$linkId.'&user_id=eq.'.$userId,'DELETE');
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);

// ── Helpers ───────────────────────────────────────────
function ownsGate(int $gateId, int $userId): ?array {
    $g = supabase('gates?id=eq.' . $gateId . '&user_id=eq.' . $userId . '&select=id,relay_id,name,icon');
    return $g[0] ?? null;
}
function formatGate(array $r): array {
    return [
        'id'        => $r['id'],
        'name'      => $r['name'],
        'icon'      => $r['icon'],
        'relay_id'  => $r['relay_id'],
        'createdAt' => $r['created_at'] ?? null,
    ];
}
