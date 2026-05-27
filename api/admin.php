<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação - aceita sessão da app ou token admin
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
$user = null;

if ($token) {
    // Verificar token direto na base de dados
    $result = supabase('users?email=eq.' . urlencode(base64_decode($token)) . '&select=*');
    if (!empty($result) && $result[0]['is_admin']) {
        $user = [
            'id'           => $result[0]['id'],
            'email'        => $result[0]['email'],
            'isAdmin'      => true,
            'isSuperAdmin' => (bool)($result[0]['is_super_admin'] ?? false),
        ];
    }
} else {
    session_name('ABREJA_ADMIN');
    session_start();
    $user = $_SESSION['admin_user'] ?? null;
    
    if (!$user) {
        session_name('PHPSESSID');
        session_start();
        $u = $_SESSION['user'] ?? null;
        if ($u && !empty($u['isAdmin'])) $user = $u;
    }
}

if (!$user || empty($user['isAdmin'])) {
    jsonResponse(['error' => 'Acesso negado'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($method === 'GET' && $action === 'stats') {
    $users   = supabase('users?select=id');
    $cars    = supabase('cars?select=id');
    $gates   = supabase('gates?select=id');
    $blocked = supabase('blocked_users?select=id');
    $today   = supabase('access_log?opened_at=gte.' . date('Y-m-d') . 'T00:00:00Z&select=id');
    jsonResponse([
        'totalUsers'    => count($users),
        'totalCars'     => count($cars),
        'totalGates'    => count($gates),
        'totalBlocked'  => count($blocked),
        'accessesToday' => count($today),
    ]);
}

if ($method === 'GET' && $action === 'users') {
    $rows    = supabase('users?order=created_at.desc&select=id,email,display_name,avatar_color,is_admin,is_super_admin,created_at');
    $blocked = supabase('blocked_users?select=blocked_user_id,reason');
    $blockedMap = [];
    foreach ($blocked as $b) $blockedMap[$b['blocked_user_id']] = $b['reason'] ?? '';
    $rows = array_map(fn($r) => [
        'id'           => $r['id'],
        'email'        => $r['email'],
        'displayName'  => $r['display_name'],
        'avatarColor'  => $r['avatar_color'] ?? '#e53935',
        'isAdmin'      => (bool)($r['is_admin'] ?? false),
        'isSuperAdmin' => (bool)($r['is_super_admin'] ?? false),
        'isBlocked'    => array_key_exists($r['id'], $blockedMap),
        'blockReason'  => $blockedMap[$r['id']] ?? null,
        'createdAt'    => $r['created_at'],
    ], $rows);
    jsonResponse($rows);
}

if ($method === 'GET' && $action === 'user-detail' && $id) {
    $u = supabase('users?id=eq.' . $id . '&select=id,email,display_name,is_admin,is_super_admin,created_at');
    if (empty($u)) jsonResponse(['error' => 'Não encontrado'], 404);
    $cars  = supabase('cars?user_id=eq.' . $id . '&select=id,plate,brand,color');
    $gates = supabase('gates?user_id=eq.' . $id . '&select=id,name,icon,relay_id');
    jsonResponse(['user' => $u[0], 'cars' => $cars, 'gates' => $gates, 'recentAccesses' => []]);
}

if ($method === 'GET' && $action === 'access-log') {
    $rows = supabase('access_log?order=opened_at.desc&limit=200&select=id,opened_at,ip_address,method,plate,gates(id,name),users(id,display_name,email)');
    jsonResponse($rows);
}

if ($method === 'GET' && $action === 'admin-log') {
    $rows = supabase('admin_log?order=created_at.desc&limit=100&select=id,action,target,details,created_at,users(id,display_name,email)');
    jsonResponse($rows);
}

if ($method === 'GET' && $action === 'settings') {
    $rows = supabase('app_settings?select=key,value');
    jsonResponse(array_column($rows, 'value', 'key'));
}

if ($method === 'GET' && $action === 'export') {
    $type = $_GET['type'] ?? 'access-log';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_'.$type.'_'.date('Y-m-d').'.csv"');
    if ($type === 'users') {
        $rows = supabase('users?order=created_at.desc&select=id,email,display_name,is_admin,created_at');
        echo "ID,Email,Nome,Admin,Registado\n";
        foreach ($rows as $r) echo implode(',', [$r['id'],$r['email'],$r['display_name']??'',$r['is_admin']?'sim':'não',$r['created_at']])."\n";
    } else {
        $rows = supabase('access_log?order=opened_at.desc&limit=10000&select=opened_at,method,plate,ip_address,gates(name),users(email)');
        echo "Data,Portão,Utilizador,Método,Matrícula,IP\n";
        foreach ($rows as $r) echo implode(',', [$r['opened_at'],$r['gates']['name']??'—',$r['users']['email']??'Sistema',$r['method'],$r['plate']??'',$r['ip_address']??''])."\n";
    }
    exit;
}

if ($method === 'POST' && $action === 'block' && $id) {
    if ($id == $user['id']) jsonResponse(['error' => 'Não podes bloquear-te'], 400);
    $exists = supabase('blocked_users?blocked_user_id=eq.'.$id.'&select=id');
    if (!empty($exists)) jsonResponse(['error' => 'Já bloqueado'], 400);
    $body = getBody();
    supabase('blocked_users', 'POST', ['blocked_user_id' => $id, 'reason' => trim($body['reason']??'')?:null, 'blocked_by' => $user['id']]);
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE' && $action === 'block' && $id) {
    supabase('blocked_users?blocked_user_id=eq.'.$id, 'DELETE');
    jsonResponse(['ok' => true]);
}

if ($method === 'DELETE' && $action === 'user' && $id) {
    if ($id == $user['id']) jsonResponse(['error' => 'Não podes apagar a tua conta'], 400);
    supabase('users?id=eq.'.$id, 'DELETE');
    jsonResponse(['ok' => true]);
}

if ($method === 'PATCH' && $action === 'toggle-admin' && $id) {
    if (empty($user['isSuperAdmin'])) jsonResponse(['error' => 'Apenas super admins'], 403);
    $target = supabase('users?id=eq.'.$id.'&select=is_admin');
    if (empty($target)) jsonResponse(['error' => 'Não encontrado'], 404);
    $newVal = !((bool)$target[0]['is_admin']);
    supabase('users?id=eq.'.$id, 'PATCH', ['is_admin' => $newVal]);
    jsonResponse(['ok' => true, 'isAdmin' => $newVal]);
}

if ($method === 'PATCH' && $action === 'settings') {
    $body = getBody();
    foreach ($body as $key => $value) {
        $exists = supabase('app_settings?key=eq.'.urlencode($key).'&select=key');
        if (!empty($exists)) supabase('app_settings?key=eq.'.urlencode($key), 'PATCH', ['value' => (string)$value]);
        else supabase('app_settings', 'POST', ['key' => $key, 'value' => (string)$value]);
    }
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'Rota não encontrada'], 404);
