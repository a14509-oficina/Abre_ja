<?php
// ─────────────────────────────────────────────
//  Ligação ao Supabase (substitui o MySQL/XAMPP)
// ─────────────────────────────────────────────
define('SUPABASE_URL', 'https://fmjytigqgpfocurpjvtv.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_PD-ZlUEG1KFgRI8fi3nxBA_Pcx3JNmx');

/**
 * Faz um pedido REST à API do Supabase.
 *
 * @param string      $endpoint  ex: "users?email=eq.foo@bar.com&select=*"
 * @param string      $method    GET | POST | PATCH | DELETE
 * @param array|null  $data      corpo JSON (para POST/PATCH)
 * @return array                 resposta descodificada
 */
function supabase(string $endpoint, string $method = 'GET', ?array $data = null): array {
    $ch = curl_init(SUPABASE_URL . '/rest/v1/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'apikey: '               . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ],
    ]);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?? [];
}
