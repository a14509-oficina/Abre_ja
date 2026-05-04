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

// GET /api/gates.php → listar portões do utilizador
if ($method === 'GET' && !$id && !$action) {
    $rows = supabase(
        'gates?user_id=eq.' . $userId .
        '&order=created_at.asc&select=id,name,icon,relay_id,created_at'
    );
    $rows = array_map(fn($r) => [
        'id'        => $r['id'],
        'name'      => $r['name'],
        'icon'      => $r['icon'],
        'relay_id'  => $r['relay_id'],
        'createdAt' => $r['created_at'],
    ], $rows);
    jsonResponse($rows);
}

// GET /api/gates.php?id=X&action=log → histórico de acessos
if ($method === 'GET' && $id && $action === 'log') {
    // Confirmar que o portão pertence ao user
    $gate = supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId . '&select=id');
    if (empty($gate)) jsonResponse(['error' => 'Portão não encontrado'], 404);

    $rows = supabase(
        'access_log?gate_id=eq.' . $id .
        '&order=opened_at.desc&limit=50' .
        '&select=id,opened_at,ip_address,users(id,display_name,email)'
    );
    jsonResponse($rows);
}

// POST /api/gates.php → adicionar portão
if ($method === 'POST' && !$action) {
    $body    = getBody();
    $name    = trim($body['name'] ?? '');
    $icon    = trim($body['icon'] ?? '🏠');
    $relayId = trim($body['relayId'] ?? '');

    if (!$name)             jsonResponse(['error' => 'Nome do portão é obrigatório'], 400);
    if (strlen($name) > 60) jsonResponse(['error' => 'Nome demasiado longo (máx. 60 caracteres)'], 400);
    if (!$relayId)          jsonResponse(['error' => 'ID do relé é obrigatório'], 400);

    $existing = supabase('gates?user_id=eq.' . $userId . '&select=id');
    if (count($existing) >= 10) jsonResponse(['error' => 'Limite de 10 portões atingido'], 400);

    $result = supabase('gates', 'POST', [
        'user_id'  => $userId,
        'name'     => $name,
        'icon'     => $icon,
        'relay_id' => $relayId,
    ]);

    if (empty($result[0])) jsonResponse(['error' => 'Erro ao adicionar portão'], 500);

    $gate = $result[0];
    jsonResponse([
        'id'        => $gate['id'],
        'name'      => $gate['name'],
        'icon'      => $gate['icon'],
        'relay_id'  => $gate['relay_id'],
        'createdAt' => $gate['created_at'],
    ], 201);
}

// POST /api/gates.php?id=X&action=open → registar abertura
if ($method === 'POST' && $id && $action === 'open') {
    $gate = supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId . '&select=id,relay_id');
    if (empty($gate)) jsonResponse(['error' => 'Portão não encontrado'], 404);

    supabase('access_log', 'POST', [
        'gate_id'    => $id,
        'user_id'    => $userId,
        'ip_address' => clientIp(),
    ]);

    jsonResponse(['ok' => true, 'relay_id' => $gate[0]['relay_id']]);
}

// PUT /api/gates.php?id=X → editar portão
if ($method === 'PUT' && $id) {
    $body    = getBody();
    $name    = trim($body['name'] ?? '');
    $icon    = trim($body['icon'] ?? '🏠');
    $relayId = trim($body['relayId'] ?? '');

    if (!$name)             jsonResponse(['error' => 'Nome do portão é obrigatório'], 400);
    if (strlen($name) > 60) jsonResponse(['error' => 'Nome demasiado longo (máx. 60 caracteres)'], 400);
    if (!$relayId)          jsonResponse(['error' => 'ID do relé é obrigatório'], 400);

    supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId, 'PATCH', [
        'name'     => $name,
        'icon'     => $icon,
        'relay_id' => $relayId,
    ]);
    jsonResponse(['ok' => true]);
}

// DELETE /api/gates.php?id=X → remover portão
if ($method === 'DELETE' && $id) {
    supabase('gates?id=eq.' . $id . '&user_id=eq.' . $userId, 'DELETE');
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
