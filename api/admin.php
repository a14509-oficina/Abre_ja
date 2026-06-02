<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

session_start();

// ── Verificar autenticação de admin ──────────────────────────────────────────
if (!isset($_SESSION['admin_user'])) {
    jsonResponse(['error' => 'Não autenticado'], 401);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ── Estatísticas ──────────────────────────────────────────────────────────
    case 'stats':
        $users  = supabase('users?select=id');
        $cars   = supabase('cars?select=id');
        $gates  = supabase('gates?select=id');
        $shares = supabase('gate_shares?select=id');
        $today  = date('Y-m-d') . 'T00:00:00';
        $logs   = supabase('access_log?created_at=gte.' . urlencode($today) . '&select=id');
        jsonResponse([
            'users'      => count($users),
            'cars'       => count($cars),
            'gates'      => count($gates),
            'shares'     => count($shares),
            'logs_today' => count($logs),
        ]);

    // ── Lista de utilizadores ─────────────────────────────────────────────────
    case 'users':
        $users = supabase('users?select=*&order=created_at.desc');
        jsonResponse($users);

    // ── Lista de carros (opcional: filtrar por user_id) ───────────────────────
    case 'cars':
        $userId = $_GET['user_id'] ?? '';
        if ($userId !== '') {
            $cars = supabase('cars?user_id=eq.' . urlencode($userId) . '&select=*&order=created_at.desc');
        } else {
            $cars = supabase('cars?select=*&order=created_at.desc');
        }
        jsonResponse($cars);

    // ── Lista de portões ──────────────────────────────────────────────────────
    case 'gates':
        $gates = supabase('gates?select=*&order=name.asc');
        jsonResponse($gates);

    // ── Log de ações administrativas ──────────────────────────────────────────
    case 'logs':
        $userId = $_GET['user_id'] ?? '';
        $filter = $userId
            ? 'access_log?user_id=eq.' . urlencode($userId) . '&order=opened_at.desc&limit=50&select=*'
            : 'access_log?order=opened_at.desc&limit=100&select=*';
        $rows = supabase($filter);
        jsonResponse($rows);

    case 'admin-log':
        $rows = supabase('admin_logs?select=*,users(display_name,email)&order=created_at.desc&limit=100');
        jsonResponse($rows);

    // ── Definições ────────────────────────────────────────────────────────────
    case 'settings':
        if ($method === 'GET') {
            $rows = supabase('settings?select=*');
            $out  = [];
            foreach ($rows as $row) {
                $out[$row['key']] = $row['value'];
            }
            jsonResponse($out);
        }
        if ($method === 'PATCH') {
            $body = getBody();
            foreach ($body as $key => $value) {
                $existing = supabase('settings?key=eq.' . urlencode($key) . '&select=key');
                if (!empty($existing)) {
                    supabase('settings?key=eq.' . urlencode($key), 'PATCH', ['value' => $value]);
                } else {
                    supabase('settings', 'POST', ['key' => $key, 'value' => $value]);
                }
            }
            jsonResponse(['ok' => true]);
        }
        jsonResponse(['error' => 'Método não suportado'], 405);

    default:
        jsonResponse(['error' => 'Ação inválida: ' . $action], 404);
}