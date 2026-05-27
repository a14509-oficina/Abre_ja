<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$adminId    = $_SERVER['HTTP_X_ADMIN_ID']    ?? '';
$adminEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? '';
$user = null;

if ($adminId && $adminEmail) {
    $result = supabase('users?id=eq.'.(int)$adminId.'&email=eq.'.urlencode($adminEmail).'&is_admin=eq.true&select=id,email,is_admin,is_super_admin');
    if (!empty($result)) {
        $user = ['id'=>$result[0]['id'],'email'=>$result[0]['email'],'isAdmin'=>true,'isSuperAdmin'=>(bool)($result[0]['is_super_admin']??false)];
    }
} else {
    session_name('PHPSESSID'); session_start();
    $u = $_SESSION['user'] ?? null;
    if ($u && !empty($u['isAdmin'])) $user = $u;
}

if (!$user) jsonResponse(['error'=>'Acesso negado'],403);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$uid    = isset($_GET['uid']) ? (int)$_GET['uid'] : null;

// ── Stats ─────────────────────────────────────────
if ($method==='GET' && $action==='stats') {
    jsonResponse([
        'totalUsers'    => count(supabase('users?select=id')),
        'totalCars'     => count(supabase('cars?select=id')),
        'totalGates'    => count(supabase('gates?select=id')),
        'totalBlocked'  => count(supabase('blocked_users?select=id')),
        'accessesToday' => count(supabase('access_log?opened_at=gte.'.date('Y-m-d').'T00:00:00Z&select=id')),
    ]);
}

// ── Users ─────────────────────────────────────────
if ($method==='GET' && $action==='users') {
    $rows    = supabase('users?order=created_at.desc&select=id,email,display_name,avatar_color,is_admin,is_super_admin,created_at');
    $blocked = supabase('blocked_users?select=blocked_user_id,reason');
    $bmap    = [];
    foreach ($blocked as $b) $bmap[$b['blocked_user_id']] = $b['reason'] ?? '';
    jsonResponse(array_map(fn($r) => [
        'id'          => $r['id'],
        'email'       => $r['email'],
        'displayName' => $r['display_name'],
        'avatarColor' => $r['avatar_color'] ?? '#e53935',
        'isAdmin'     => (bool)($r['is_admin'] ?? false),
        'isSuperAdmin'=> (bool)($r['is_super_admin'] ?? false),
        'isBlocked'   => array_key_exists($r['id'], $bmap),
        'blockReason' => $bmap[$r['id']] ?? null,
        'createdAt'   => $r['created_at'],
    ], $rows));
}

// ── User detail ───────────────────────────────────
if ($method==='GET' && $action==='user-detail' && $id) {
    $u = supabase('users?id=eq.'.$id.'&select=id,email,display_name,is_admin,is_super_admin,created_at');
    if (empty($u)) jsonResponse(['error'=>'Não encontrado'],404);
    $cars  = supabase('cars?user_id=eq.'.$id.'&select=id,plate,brand,color,created_at&order=created_at.asc');
    $gates = supabase('gates?user_id=eq.'.$id.'&select=id,name,icon,relay_id,created_at&order=created_at.asc');
    jsonResponse(['user'=>$u[0],'cars'=>$cars,'gates'=>$gates,'recentAccesses'=>[]]);
}

// ── Admin: adicionar carro a user ─────────────────
if ($method==='POST' && $action==='add-car' && $uid) {
    $body  = getBody();
    $plate = strtoupper(trim($body['plate'] ?? ''));
    $brand = trim($body['brand'] ?? '');
    $color = trim($body['color'] ?? '#111111');
    if (!$plate || !$brand) jsonResponse(['error'=>'Placa e marca obrigatórias'],400);
    $result = supabase('cars','POST',['user_id'=>$uid,'plate'=>$plate,'brand'=>$brand,'color'=>$color]);
    jsonResponse($result[0] ?? [],201);
}

// ── Admin: remover carro ──────────────────────────
if ($method==='DELETE' && $action==='del-car' && $id) {
    supabase('cars?id=eq.'.$id,'DELETE');
    jsonResponse(['ok'=>true]);
}

// ── Admin: adicionar portão a user ────────────────
if ($method==='POST' && $action==='add-gate' && $uid) {
    $body    = getBody();
    $name    = trim($body['name'] ?? '');
    $icon    = trim($body['icon'] ?? '🏠');
    $relayId = trim($body['relayId'] ?? '');
    if (!$name || !$relayId) jsonResponse(['error'=>'Nome e relay obrigatórios'],400);
    $result = supabase('gates','POST',['user_id'=>$uid,'name'=>$name,'icon'=>$icon,'relay_id'=>$relayId]);
    jsonResponse($result[0] ?? [],201);
}

// ── Admin: remover portão ─────────────────────────
if ($method==='DELETE' && $action==='del-gate' && $id) {
    supabase('gates?id=eq.'.$id,'DELETE');
    jsonResponse(['ok'=>true]);
}

// ── Access log ────────────────────────────────────
if ($method==='GET' && $action==='access-log') {
    jsonResponse(supabase('access_log?order=opened_at.desc&limit=200&select=id,opened_at,ip_address,method,plate,gates(id,name),users(id,display_name,email)'));
}

// ── Admin log ─────────────────────────────────────
if ($method==='GET' && $action==='admin-log') {
    jsonResponse(supabase('admin_log?order=created_at.desc&limit=100&select=id,action,target,details,created_at,users(id,display_name,email)'));
}

// ── Settings ──────────────────────────────────────
if ($method==='GET' && $action==='settings') {
    jsonResponse(array_column(supabase('app_settings?select=key,value'),'value','key'));
}

// ── Export CSV ────────────────────────────────────
if ($method==='GET' && $action==='export') {
    $type = $_GET['type'] ?? 'access-log';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_'.$type.'_'.date('Y-m-d').'.csv"');
    if ($type==='users') {
        $rows = supabase('users?order=created_at.desc&select=id,email,display_name,is_admin,created_at');
        echo "ID,Email,Nome,Admin,Registado\n";
        foreach ($rows as $r) echo implode(',',[$r['id'],$r['email'],$r['display_name']??'',$r['is_admin']?'sim':'não',$r['created_at']])."\n";
    } else {
        $rows = supabase('access_log?order=opened_at.desc&limit=10000&select=opened_at,method,plate,ip_address,gates(name),users(email)');
        echo "Data,Portão,Utilizador,Método,Matrícula,IP\n";
        foreach ($rows as $r) echo implode(',',[$r['opened_at'],$r['gates']['name']??'—',$r['users']['email']??'Sistema',$r['method'],$r['plate']??'',$r['ip_address']??''])."\n";
    }
    exit;
}

// ── Block / Unblock ───────────────────────────────
if ($method==='POST' && $action==='block' && $id) {
    if ($id==$user['id']) jsonResponse(['error'=>'Não podes bloquear-te'],400);
    if (!empty(supabase('blocked_users?blocked_user_id=eq.'.$id.'&select=id'))) jsonResponse(['error'=>'Já bloqueado'],400);
    $body = getBody();
    supabase('blocked_users','POST',['blocked_user_id'=>$id,'reason'=>trim($body['reason']??'')?:null,'blocked_by'=>$user['id']]);
    jsonResponse(['ok'=>true]);
}
if ($method==='DELETE' && $action==='block' && $id) {
    supabase('blocked_users?blocked_user_id=eq.'.$id,'DELETE');
    jsonResponse(['ok'=>true]);
}

// ── Delete user ───────────────────────────────────
if ($method==='DELETE' && $action==='user' && $id) {
    if ($id==$user['id']) jsonResponse(['error'=>'Não podes apagar a tua conta'],400);
    supabase('users?id=eq.'.$id,'DELETE');
    jsonResponse(['ok'=>true]);
}

// ── Toggle admin ──────────────────────────────────
if ($method==='PATCH' && $action==='toggle-admin' && $id) {
    if (empty($user['isSuperAdmin'])) jsonResponse(['error'=>'Apenas super admins'],403);
    $target = supabase('users?id=eq.'.$id.'&select=is_admin');
    if (empty($target)) jsonResponse(['error'=>'Não encontrado'],404);
    $newVal = !((bool)$target[0]['is_admin']);
    supabase('users?id=eq.'.$id,'PATCH',['is_admin'=>$newVal]);
    jsonResponse(['ok'=>true,'isAdmin'=>$newVal]);
}

// ── Settings update ───────────────────────────────
if ($method==='PATCH' && $action==='settings') {
    foreach (getBody() as $k=>$v) {
        if (!empty(supabase('app_settings?key=eq.'.urlencode($k).'&select=key')))
            supabase('app_settings?key=eq.'.urlencode($k),'PATCH',['value'=>(string)$v]);
        else supabase('app_settings','POST',['key'=>$k,'value'=>(string)$v]);
    }
    jsonResponse(['ok'=>true]);
}

jsonResponse(['error'=>'Rota não encontrada'],404);
