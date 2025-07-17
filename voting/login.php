<?php
session_start();
$error = '';

// WebSocket már fut?
function isWebSocketRunning() {
    $connection = @fsockopen('localhost', 8080);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

$lockFile = __DIR__ . '/admin_session.lock';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['code'] === 'ADMIN123') {

        // Csak egy admin engedélyezett
        if (file_exists($lockFile)) {
            $error = '⚠️ Már van egy bejelentkezett admin!';
        } else {
            $_SESSION['admin'] = true;
            file_put_contents($lockFile, session_id());

            $logFile = __DIR__ . "/websocket_launch.log";
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] 🔐 Bejelentkezés indult\n", FILE_APPEND);

            if (!isWebSocketRunning()) {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] 🔄 WebSocket még nem fut, próbáljuk indítani...\n", FILE_APPEND);

                $phpPath = "C:\\xampp\\php\\php.exe";
                $scriptPath = realpath(__DIR__ . "/websocket/websocket-server.php");
                $wsOutputPath = realpath(__DIR__ . "/websocket") . "\\ws_output.log";

                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $vbscript = realpath(__DIR__ . '/websocket/start_socket.vbs');

                    if ($vbscript && file_exists($vbscript)) {
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ▶️ VBS indítása háttérben: $vbscript\n", FILE_APPEND);
                        pclose(popen("wscript \"$vbscript\"", "r"));
                    } else {
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ❌ start_socket.vbs nem található!\n", FILE_APPEND);
                    }
                } else {
                    $command = "php $scriptPath > /dev/null 2>/dev/null &";
                    shell_exec($command);
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] 🛠️ Parancs (Unix): $command\n", FILE_APPEND);
                }

                sleep(2);

                if (isWebSocketRunning()) {
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ✅ WebSocket sikeresen elindult\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ❌ WebSocket NEM indult el\n", FILE_APPEND);
                }
            } else {
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] ✅ WebSocket már fut\n", FILE_APPEND);
            }

            header('Location: admin.php');
            exit;
        }
    } else {
        $error = 'Hibás adminisztrátori kód.';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Admin Bejelentkezés</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f8fafc;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-box {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      width: 100%;
      max-width: 400px;
      text-align: center;
      border: 1px solid #e2e8f0;
    }

    .login-box h2 {
      font-size: 22px;
      color: #0f172a;
      margin-bottom: 20px;
    }

    .login-box form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .login-box input {
      padding: 12px;
      border: 1px solid #94a3b8;
      border-radius: 8px;
      font-size: 16px;
      outline: none;
      transition: border 0.2s;
    }

    .login-box input:focus {
      border-color: #3b82f6;
    }

    .login-box button {
      background: #eff6ff;
      color: #1d4ed8;
      padding: 12px;
      border: 1px solid #bfdbfe;
      font-weight: bold;
      border-radius: 8px;
      cursor: pointer;
    }

    .login-box button:hover {
      background: #dbeafe;
    }

    .login-box .demo {
      font-size: 14px;
      color: #64748b;
      margin-top: 10px;
    }

    .login-box .error {
      color: red;
      font-size: 14px;
    }

    .shield-icon {
      font-size: 24px;
      color: #3b82f6;
      margin-bottom: 8px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="shield-icon">🛡️</div>
    <h2>Admin Bejelentkezés</h2>

    <form method="POST">
      <input type="text" name="code" placeholder="Írd be az admin kódot" required />
      <button type="submit">Belépés az Admin Panelbe</button>
    </form>

    <?php if (!empty($error)): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <p class="demo">Demo kód: <strong>ADMIN123</strong></p>
  </div>
</body>
</html>
