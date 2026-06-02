<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

$isAdmin = isset($_SESSION['admin_user']);
if (!$isAdmin) requireAuth();

$user   = $isAdmin ? $_SESSION['admin_user'] : getLoggedUser();
$userId = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

// ── GET: listar carros ────────────────────────────────────────────────────────
if ($method === 'GET' && !$id) {
    if ($isAdmin) {
        $uid  = $_GET['user_id'] ?? '';
        $cars = $uid
            ? supabase('cars?user_id=eq.' . urlencode($uid) . '&select=*&order=created_at.desc')
            : supabase('cars?select=*&order=created_at.desc');
    } else {
        $cars = supabase('cars?user_id=eq.' . $userId . '&select=*&order=created_at.desc');
    }
    jsonResponse($cars);
}

// ── POST: criar carro ─────────────────────────────────────────────────────────
if ($method === 'POST' && !$id) {
    $body    = getBody();
    $plate   = strtoupper(trim($body['plate']  ?? ''));
    $brand   = trim($body['brand']  ?? '');
    $model   = trim($body['model']  ?? '');
    $color   = trim($body['color']  ?? '');
    $ownerId = $isAdmin ? ($body['user_id'] ?? $userId) : $userId;

    if (!$plate) jsonResponse(['error' => 'Matrícula obrigatória'], 400);

    // Verificar duplicado
    $exists = supabase('cars?plate=eq.' . urlencode($plate) . '&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Matrícula já registada'], 400);

    $result = supabase('cars', 'POST', [
        'user_id' => $ownerId,
        'plate'   => $plate,
        'brand'   => $brand,
        'model'   => $model,
        'color'   => $color,
    ]);
    jsonResponse($result[0] ?? [], 201);
}

// ── PUT: editar carro ─────────────────────────────────────────────────────────
if ($method === 'PUT' && $id) {
    $body  = getBody();
    $plate = strtoupper(trim($body['plate'] ?? ''));
    $brand = trim($body['brand'] ?? '');
    $model = trim($body['model'] ?? '');
    $color = trim($body['color'] ?? '');

    if (!$plate) jsonResponse(['error' => 'Matrícula obrigatória'], 400);

    $filter = $isAdmin
        ? 'cars?id=eq.' . urlencode($id)
        : 'cars?id=eq.' . urlencode($id) . '&user_id=eq.' . $userId;

    supabase($filter, 'PATCH', [
        'plate' => $plate,
        'brand' => $brand,
        'model' => $model,
        'color' => $color,
    ]);
    jsonResponse(['ok' => true]);
}

// ── DELETE: eliminar carro ────────────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    $filter = $isAdmin
        ? 'cars?id=eq.' . urlencode($id)
        : 'cars?id=eq.' . urlencode($id) . '&user_id=eq.' . $userId;
    supabase($filter, 'DELETE');
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);