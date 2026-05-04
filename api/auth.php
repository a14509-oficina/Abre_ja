<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET user ──────────────────────────────────────────
if ($method === 'GET' && $action === 'user') {
    $user = getLoggedUser();
    if (!$user) jsonResponse(['error' => 'Não autenticado'], 401);
    jsonResponse($user);
}

// ── POST register ─────────────────────────────────────
if ($method === 'POST' && $action === 'register') {
    $body        = getBody();
    $email       = strtolower(trim($body['email'] ?? ''));
    $password    = $body['password'] ?? '';
    $displayName = trim($body['displayName'] ?? '') ?: null;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['error' => 'Email inválido'], 400);
    if (strlen($password) < 6)
        jsonResponse(['error' => 'Password deve ter pelo menos 6 caracteres'], 400);

    $exists = supabase('users?email=eq.' . urlencode($email) . '&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Email já registado'], 400);

    $hash   = password_hash($password, PASSWORD_BCRYPT);
    $result = supabase('users', 'POST', [
        'email'        => $email,
        'password'     => $hash,
        'display_name' => $displayName,
    ]);

    if (empty($result[0])) jsonResponse(['error' => 'Erro ao criar conta'], 500);

    $row      = $result[0];
    $userData = [
        'id'          => $row['id'],
        'email'       => $row['email'],
        'displayName' => $row['display_name'],
        'isAdmin'     => (bool)($row['is_admin'] ?? false),
    ];
    setLoggedUser($userData);
    jsonResponse($userData, 201);
}

// ── POST login ────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body     = getBody();
    $email    = strtolower(trim($body['email'] ?? ''));
    $password = $body['password'] ?? '';

    if (!$email || !$password)
        jsonResponse(['error' => 'Email e password são obrigatórios'], 400);

    $result = supabase('users?email=eq.' . urlencode($email) . '&select=*');
    if (empty($result)) jsonResponse(['error' => 'Email ou password incorretos'], 401);

    $row = $result[0];

    // Verificar se está bloqueado
    $blocked = supabase('blocked_users?blocked_user_id=eq.' . $row['id'] . '&select=id');
    if (!empty($blocked))
        jsonResponse(['error' => 'Esta conta foi suspensa. Contacta o administrador.'], 403);

    if (!password_verify($password, $row['password']))
        jsonResponse(['error' => 'Email ou password incorretos'], 401);

    $userData = [
        'id'          => $row['id'],
        'email'       => $row['email'],
        'displayName' => $row['display_name'],
        'isAdmin'     => (bool)($row['is_admin'] ?? false),
    ];
    setLoggedUser($userData);
    jsonResponse($userData);
}

// ── POST logout ───────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    logoutUser();
    jsonResponse(['ok' => true]);
}

// ── PUT profile ───────────────────────────────────────
if ($method === 'PUT' && $action === 'profile') {
    requireAuth();
    $user        = getLoggedUser();
    $body        = getBody();
    $displayName = trim($body['displayName'] ?? '');

    if (!$displayName) jsonResponse(['error' => 'Nome não pode estar vazio'], 400);

    supabase('users?id=eq.' . $user['id'], 'PATCH', ['display_name' => $displayName]);
    $_SESSION['user']['displayName'] = $displayName;
    jsonResponse(['ok' => true]);
}

// ── PUT password ──────────────────────────────────────
if ($method === 'PUT' && $action === 'password') {
    requireAuth();
    $user    = getLoggedUser();
    $body    = getBody();
    $current = $body['current'] ?? '';
    $new     = $body['new']     ?? '';

    if (strlen($new) < 6)
        jsonResponse(['error' => 'Nova password deve ter pelo menos 6 caracteres'], 400);

    $result = supabase('users?id=eq.' . $user['id'] . '&select=password');
    if (empty($result)) jsonResponse(['error' => 'Utilizador não encontrado'], 404);

    if (!password_verify($current, $result[0]['password']))
        jsonResponse(['error' => 'Password atual incorreta'], 401);

    $hash = password_hash($new, PASSWORD_BCRYPT);
    supabase('users?id=eq.' . $user['id'], 'PATCH', ['password' => $hash]);
    jsonResponse(['ok' => true]);
}

// ── POST forgot-password ──────────────────────────────
if ($method === 'POST' && $action === 'forgot') {
    $body  = getBody();
    $email = strtolower(trim($body['email'] ?? ''));

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['error' => 'Email inválido'], 400);

    $result = supabase('users?email=eq.' . urlencode($email) . '&select=id,display_name');
    // Responder sempre ok para não revelar se o email existe
    if (empty($result)) jsonResponse(['ok' => true]);

    $userId = $result[0]['id'];
    $token  = bin2hex(random_bytes(32));
    $expires = date('c', strtotime('+1 hour'));

    // Apagar tokens anteriores deste user
    supabase('password_resets?user_id=eq.' . $userId, 'DELETE');

    supabase('password_resets', 'POST', [
        'user_id'    => $userId,
        'token'      => $token,
        'expires_at' => $expires,
    ]);

    // TODO: enviar email com link de reset
    // Por agora retorna o token para teste (em produção remover)
    jsonResponse(['ok' => true, 'token' => $token]);
}

// ── POST reset-password ───────────────────────────────
if ($method === 'POST' && $action === 'reset') {
    $body     = getBody();
    $token    = trim($body['token'] ?? '');
    $password = $body['password'] ?? '';

    if (!$token) jsonResponse(['error' => 'Token inválido'], 400);
    if (strlen($password) < 6)
        jsonResponse(['error' => 'Password deve ter pelo menos 6 caracteres'], 400);

    $result = supabase(
        'password_resets?token=eq.' . urlencode($token) .
        '&used=eq.false&select=id,user_id,expires_at'
    );
    if (empty($result)) jsonResponse(['error' => 'Token inválido ou expirado'], 400);

    $reset = $result[0];
    if (strtotime($reset['expires_at']) < time())
        jsonResponse(['error' => 'Token expirado'], 400);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    supabase('users?id=eq.' . $reset['user_id'], 'PATCH', ['password' => $hash]);
    supabase('password_resets?id=eq.' . $reset['id'], 'PATCH', ['used' => true]);

    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
