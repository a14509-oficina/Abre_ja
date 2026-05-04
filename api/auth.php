<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

// GET /api/auth.php?action=user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'user') {
    $user = getLoggedUser();
    if (!$user) jsonResponse(['error' => 'Não autenticado'], 401);
    jsonResponse($user);
}

// POST /api/auth.php?action=register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $body        = getBody();
    $email       = trim($body['email'] ?? '');
    $password    = $body['password'] ?? '';
    $displayName = trim($body['displayName'] ?? '') ?: null;

    if (!$email || !$password) jsonResponse(['error' => 'Email e password são obrigatórios'], 400);
    if (strlen($password) < 6) jsonResponse(['error' => 'Password deve ter pelo menos 6 caracteres'], 400);

    // Verificar se email já existe
    $exists = supabase('users?email=eq.' . urlencode($email) . '&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Email já registado'], 400);

    $hash   = password_hash($password, PASSWORD_BCRYPT);
    $result = supabase('users', 'POST', [
        'email'        => $email,
        'password'     => $hash,
        'display_name' => $displayName,
    ]);

    if (empty($result[0])) jsonResponse(['error' => 'Erro ao criar conta'], 500);

    $userData = [
        'id'          => $result[0]['id'],
        'email'       => $result[0]['email'],
        'displayName' => $result[0]['display_name'],
    ];
    setLoggedUser($userData);
    jsonResponse($userData, 201);
}

// POST /api/auth.php?action=login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $body     = getBody();
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) jsonResponse(['error' => 'Email e password são obrigatórios'], 400);

    $result = supabase('users?email=eq.' . urlencode($email) . '&select=*');
    if (empty($result)) jsonResponse(['error' => 'Email ou password incorretos'], 401);

    $row = $result[0];
    if (!password_verify($password, $row['password'])) {
        jsonResponse(['error' => 'Email ou password incorretos'], 401);
    }

    $userData = [
        'id'          => $row['id'],
        'email'       => $row['email'],
        'displayName' => $row['display_name'],
    ];
    setLoggedUser($userData);
    jsonResponse($userData);
}

// POST /api/auth.php?action=logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
    logoutUser();
    jsonResponse(['ok' => true]);
}

// PUT /api/auth.php?action=profile
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'profile') {
    requireAuth();
    $user        = getLoggedUser();
    $body        = getBody();
    $displayName = trim($body['displayName'] ?? '');

    supabase('users?id=eq.' . $user['id'], 'PATCH', ['display_name' => $displayName]);

    $_SESSION['user']['displayName'] = $displayName;
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
