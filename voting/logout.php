<?php
session_start();
session_destroy();

$lockFile = __DIR__ . '/admin_session.lock';
if (file_exists($lockFile)) {
    unlink($lockFile);
}

// WebSocket szerver leállítása
$port = 8080;
$connection = @fsockopen('localhost', $port);
if ($connection) {
    fwrite($connection, json_encode(["type" => "shutdown"]));
    fclose($connection);
    file_put_contents(__DIR__ . "/websocket_launch.log", "[" . date('Y-m-d H:i:s') . "] 🛑 WebSocket leállítás parancs küldve\n", FILE_APPEND);
}

header('Location: login.php');
exit;
