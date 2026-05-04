<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getLoggedUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireAuth(): void {
    $user = getLoggedUser();
    if (!$user) {
        http_response_code(401);
        die(json_encode(['error' => 'Não autenticado']));
    }
}

function setLoggedUser(array $user): void {
    startSession();
    $_SESSION['user'] = $user;
}

function logoutUser(): void {
    startSession();
    session_destroy();
}
