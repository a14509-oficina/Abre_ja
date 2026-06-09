<?php
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";
echo "Cookie PHPSESSID: " . ($_COOKIE['PHPSESSID'] ?? 'NOT SET') . "\n";

// Se for POST, guardar dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['test'] = 'data_' . time();
    session_write_close();
    header('Location: test_session.php');
    exit;
}
?>
<form method="POST">
    <button type="submit">Set Session Data</button>
</form>
