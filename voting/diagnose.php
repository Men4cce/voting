<?php
function test_shell_exec() {
    $result = shell_exec("php -v");
    return $result ? "✅ Működik (php -v)" : "❌ Nem működik vagy le van tiltva";
}

function test_popen() {
    $handle = @popen('dir', 'r');
    if ($handle) {
        $output = fread($handle, 1024);
        pclose($handle);
        return $output ? "✅ Működik (dir parancs)" : "❌ Nem adott vissza kimenetet";
    }
    return "❌ Nem működik vagy le van tiltva";
}

function start_websocket_server() {
    $websocketScript = __DIR__ . "\\websocket\\websocket-server.php";
    if (!file_exists($websocketScript)) return "❌ websocket-server.php nem található";

    $command = "start /B \"websocket\" C:\\xampp\\php\\php.exe $websocketScript";
    pclose(popen($command, "r"));
    sleep(1); // adunk egy kis időt az indulásra

    return is_websocket_running() ? "✅ Elindult a WebSocket szerver" : "❌ Nem sikerült elindítani";
}

function is_websocket_running() {
    $connection = @fsockopen('localhost', 8080);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Diagnosztikai Teszt</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f8fafc; padding: 30px; }
    table { border-collapse: collapse; width: 100%; max-width: 700px; margin: auto; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    th, td { padding: 12px 20px; border: 1px solid #e2e8f0; text-align: left; }
    th { background: #f1f5f9; }
    tr:nth-child(even) { background: #f9fafb; }
    h2 { text-align: center; color: #0f172a; }
  </style>
</head>
<body>
  <h2>🔍 WebSocket Diagnosztika</h2>
  <table>
    <tr><th>Teszt</th><th>Eredmény</th></tr>
    <tr><td><strong>shell_exec()</strong> teszt</td><td><?= test_shell_exec() ?></td></tr>
    <tr><td><strong>popen()</strong> teszt</td><td><?= test_popen() ?></td></tr>
    <tr><td><strong>WebSocket indítás</strong></td><td><?= start_websocket_server() ?></td></tr>
    <tr><td><strong>Port 8080 figyel-e</strong></td><td><?= is_websocket_running() ? "✅ Igen" : "❌ Nem" ?></td></tr>
  </table>
</body>
</html>
