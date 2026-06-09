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
    $email       = strtolower(trim($body['email'] ?? ''));
    $password    = $body['password'] ?? '';
    $displayName = trim($body['displayName'] ?? '') ?: null;

    if (!$email || !$password) jsonResponse(['error' => 'Email e password são obrigatórios'], 400);
    if (strlen($password) < 6) jsonResponse(['error' => 'Password deve ter pelo menos 6 caracteres'], 400);

    // Verificar se email já existe (case-insensitive)
    $exists = supabase('users?email=ilike.' . urlencode($email) . '&select=id');
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
        'avatar'      => $result[0]['avatar'] ?? '',
        'theme'       => $result[0]['theme'] ?? 'dark',
        'isAdmin'      => (bool)($result[0]['is_admin'] ?? false),
        'isSuperAdmin' => (bool)($result[0]['is_super_admin'] ?? false),
    ];
    setLoggedUser($userData);
    jsonResponse($userData, 201);
}

// POST /api/auth.php?action=login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $body     = getBody();
    $email    = strtolower(trim($body['email'] ?? ''));
    $password = $body['password'] ?? '';

    if (!$email || !$password) jsonResponse(['error' => 'Email e password são obrigatórios'], 400);

    $result = supabase('users?email=ilike.' . urlencode($email) . '&select=*');
    if (empty($result)) jsonResponse(['error' => 'Email ou password incorretos'], 401);

    $row = $result[0];
    if (!password_verify($password, $row['password'])) {
        jsonResponse(['error' => 'Email ou password incorretos'], 401);
    }
    $blocked = supabase('blocked_users?blocked_user_id=eq.' . $row['id'] . '&select=id');
    if (!empty($blocked)) {
        jsonResponse(['error' => 'Conta bloqueada'], 403);
    }

    $userData = [
        'id'          => $row['id'],
        'email'       => $row['email'],
        'displayName' => $row['display_name'],
        'avatar'      => $row['avatar'] ?? '',
        'theme'       => $row['theme'] ?? 'dark',
        'isAdmin'      => (bool)($row['is_admin'] ?? false),
        'isSuperAdmin' => (bool)($row['is_super_admin'] ?? false),
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
    $avatar      = trim($body['avatar'] ?? '');
    $theme       = trim($body['theme'] ?? 'dark');

    supabase('users?id=eq.' . $user['id'], 'PATCH', [
        'display_name' => $displayName,
        'avatar'       => $avatar,
        'theme'        => $theme,
        'isAdmin'      => (bool)($row['is_admin'] ?? false),
        'isSuperAdmin' => (bool)($row['is_super_admin'] ?? false),
    ]);

    $_SESSION['user']['displayName'] = $displayName;
    $_SESSION['user']['avatar'] = $avatar;
    $_SESSION['user']['theme'] = $theme;
    jsonResponse(['ok' => true]);
}

// POST /api/auth.php?action=forgot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'forgot') {
    $body  = getBody();
    $email = strtolower(trim($body['email'] ?? ''));

    if (!$email) jsonResponse(['error' => 'Email obrigatório'], 400);

    $result = supabase('users?email=ilike.' . urlencode($email) . '&select=id,email');
    if (empty($result)) {
        // Por segurança, não revelamos se o email existe ou não
        jsonResponse(['ok' => true], 200);
    }

    $user   = $result[0];
    $token  = bin2hex(random_bytes(32));
    $expires = time() + 3600; // 1 hour

    // Armazenar token em ficheiro de cache
    $tokenDir = __DIR__ . '/../.cache';
    @mkdir($tokenDir, 0755, true);
    $tokenFile = $tokenDir . '/' . hash('sha256', $token) . '.json';
    file_put_contents($tokenFile, json_encode([
        'user_id'  => $user['id'],
        'email'    => $user['email'],
        'token'    => $token,
        'expires'  => $expires,
    ]));
    chmod($tokenFile, 0600);

    $resetUrl = 'reset_password.php?token=' . urlencode($token);
    jsonResponse(['ok' => true, 'resetUrl' => $resetUrl], 200);
}

// POST /api/auth.php?action=reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset') {
    $body     = getBody();
    $token    = trim($body['token'] ?? '');
    $password = $body['password'] ?? '';

    if (!$token || !$password) jsonResponse(['error' => 'Token e password são obrigatórios'], 400);
    if (strlen($password) < 6) jsonResponse(['error' => 'Password deve ter pelo menos 6 caracteres'], 400);

    $tokenDir = __DIR__ . '/../.cache';
    $tokenFile = $tokenDir . '/' . hash('sha256', $token) . '.json';
    
    if (!file_exists($tokenFile)) jsonResponse(['error' => 'Token inválido'], 400);
    
    $data = json_decode(file_get_contents($tokenFile), true);
    if (!$data || $data['token'] !== $token) jsonResponse(['error' => 'Token inválido'], 400);
    if ($data['expires'] < time()) jsonResponse(['error' => 'Token expirou'], 400);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $result = supabase('users?id=eq.' . $data['user_id'], 'PATCH', [
        'password' => $hash,
    ]);

    // Eliminar token após uso
    @unlink($tokenFile);

    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
