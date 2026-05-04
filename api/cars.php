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

// GET /api/cars.php → listar carros do utilizador
if ($method === 'GET' && !$id) {
    $rows = supabase('cars?user_id=eq.' . $userId . '&order=created_at.asc&select=id,plate,brand,color,created_at');
    // Renomear created_at para createdAt (compatível com o frontend)
    $rows = array_map(function($r) {
        $r['createdAt'] = $r['created_at'];
        unset($r['created_at']);
        return $r;
    }, $rows);
    jsonResponse($rows);
}

// POST /api/cars.php → adicionar carro
if ($method === 'POST') {
    $body  = getBody();
    $plate = strtoupper(trim($body['plate'] ?? ''));
    $brand = trim($body['brand'] ?? '');
    $color = trim($body['color'] ?? '');

    if (!$plate || strlen($plate) > 8) jsonResponse(['error' => 'Matrícula inválida (máx. 8 caracteres)'], 400);
    if (!$brand)                       jsonResponse(['error' => 'Marca obrigatória'], 400);
    if (!$color)                       jsonResponse(['error' => 'Cor obrigatória'], 400);

    $result = supabase('cars', 'POST', [
        'user_id' => $userId,
        'plate'   => $plate,
        'brand'   => $brand,
        'color'   => $color,
    ]);

    if (empty($result[0])) jsonResponse(['error' => 'Erro ao adicionar carro'], 500);

    $car = $result[0];
    $car['createdAt'] = $car['created_at'];
    unset($car['created_at']);
    jsonResponse($car, 201);
}

// PUT /api/cars.php?id=X → editar carro
if ($method === 'PUT' && $id) {
    $body  = getBody();
    $plate = strtoupper(trim($body['plate'] ?? ''));
    $brand = trim($body['brand'] ?? '');
    $color = trim($body['color'] ?? '');

    if (!$plate || strlen($plate) > 8) jsonResponse(['error' => 'Matrícula inválida (máx. 8 caracteres)'], 400);
    if (!$brand)                       jsonResponse(['error' => 'Marca obrigatória'], 400);

    supabase('cars?id=eq.' . $id . '&user_id=eq.' . $userId, 'PATCH', [
        'plate' => $plate,
        'brand' => $brand,
        'color' => $color,
    ]);
    jsonResponse(['ok' => true]);
}

// DELETE /api/cars.php?id=X → remover carro
if ($method === 'DELETE' && $id) {
    supabase('cars?id=eq.' . $id . '&user_id=eq.' . $userId, 'DELETE');
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
