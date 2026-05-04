<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// GET users → listar todos os utilizadores
if ($method === 'GET' && $action === 'users') {
    $rows = supabase(
        'users?order=created_at.desc&select=id,email,display_name,is_admin,email_verified,created_at'
    );
    // Para cada user, verificar se está bloqueado
    $blocked = supabase('blocked_users?select=blocked_user_id');
    $blockedIds = array_column($blocked, 'blocked_user_id');

    $rows = array_map(fn($r) => [
        'id'          => $r['id'],
        'email'       => $r['email'],
        'displayName' => $r['display_name'],
        'isAdmin'     => (bool)$r['is_admin'],
        'isBlocked'   => in_array($r['id'], $blockedIds),
        'createdAt'   => $r['created_at'],
    ], $rows);
    jsonResponse($rows);
}

// GET stats → estatísticas gerais
if ($method === 'GET' && $action === 'stats') {
    $users  = supabase('users?select=id');
    $cars   = supabase('cars?select=id');
    $gates  = supabase('gates?select=id');
    $logs   = supabase('access_log?select=id&order=opened_at.desc&limit=1');
    $blocked = supabase('blocked_users?select=id');

    jsonResponse([
        'totalUsers'   => count($users),
        'totalCars'    => count($cars),
        'totalGates'   => count($gates),
        'totalBlocked' => count($blocked),
    ]);
}

// GET user-detail → ver carros e portões de um user
if ($method === 'GET' && $action === 'user-detail' && $id) {
    $user  = supabase('users?id=eq.' . $id . '&select=id,email,display_name,is_admin,created_at');
    if (empty($user)) jsonResponse(['error' => 'Utilizador não encontrado'], 404);

    $cars  = supabase('cars?user_id=eq.' . $id . '&select=id,plate,brand,color,created_at');
    $gates = supabase('gates?user_id=eq.' . $id . '&select=id,name,icon,relay_id,created_at');

    jsonResponse([
        'user'  => $user[0],
        'cars'  => $cars,
        'gates' => $gates,
    ]);
}

// GET access-log → histórico global de acessos
if ($method === 'GET' && $action === 'access-log') {
    $rows = supabase(
        'access_log?order=opened_at.desc&limit=100' .
        '&select=id,opened_at,ip_address,gates(id,name),users(id,display_name,email)'
    );
    jsonResponse($rows);
}

// POST block → bloquear utilizador
if ($method === 'POST' && $action === 'block' && $id) {
    $admin = getLoggedUser();
    if ($id == $admin['id']) jsonResponse(['error' => 'Não podes bloquear-te a ti mesmo'], 400);

    $exists = supabase('blocked_users?blocked_user_id=eq.' . $id . '&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Utilizador já bloqueado'], 400);

    $body   = getBody();
    $reason = trim($body['reason'] ?? '') ?: null;

    supabase('blocked_users', 'POST', [
        'blocked_user_id' => $id,
        'reason'          => $reason,
    ]);
    jsonResponse(['ok' => true]);
}

// DELETE block → desbloquear utilizador
if ($method === 'DELETE' && $action === 'block' && $id) {
    supabase('blocked_users?blocked_user_id=eq.' . $id, 'DELETE');
    jsonResponse(['ok' => true]);
}

// DELETE user → apagar conta
if ($method === 'DELETE' && $action === 'user' && $id) {
    $admin = getLoggedUser();
    if ($id == $admin['id']) jsonResponse(['error' => 'Não podes apagar a tua própria conta'], 400);

    // Apagar user (CASCADE apaga carros, portões, logs)
    supabase('users?id=eq.' . $id, 'DELETE');
    jsonResponse(['ok' => true]);
}

// PATCH user → promover/remover admin
if ($method === 'PATCH' && $action === 'toggle-admin' && $id) {
    $admin = getLoggedUser();
    if ($id == $admin['id']) jsonResponse(['error' => 'Não podes alterar o teu próprio papel'], 400);

    $user = supabase('users?id=eq.' . $id . '&select=is_admin');
    if (empty($user)) jsonResponse(['error' => 'Utilizador não encontrado'], 404);

    $newVal = !((bool)$user[0]['is_admin']);
    supabase('users?id=eq.' . $id, 'PATCH', ['is_admin' => $newVal]);
    jsonResponse(['ok' => true, 'isAdmin' => $newVal]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
