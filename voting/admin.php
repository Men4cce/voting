<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';


$stmt = $pdo->query("SELECT name, voter_id, has_voted, voted_at FROM eligible_voters ORDER BY id ASC");
$voters = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8fafc;
      margin: 0;
      padding: 30px;
    }

    .header {
      font-size: 26px;
      color: #0f172a;
      font-weight: bold;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .subheader {
      color: #64748b;
      margin-bottom: 20px;
    }

    .status-box {
      background: #fefce8;
      border: 1px solid #facc15;
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: bold;
      color: #92400e;
    }

    .container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .card {
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 20px;
      flex: 1;
      min-width: 280px;
    }

    .card h3 {
      margin-top: 0;
      color: #0f172a;
      margin-bottom: 12px;
    }

    .btn {
      padding: 10px 18px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-start {
      background: #4ade80;
      color: #064e3b;
    }

    .btn-stop {
      background: #fca5a5;
      color: #7f1d1d;
    }

    .input {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
    }

    .voter-list {
      margin-top: 10px;
    }

    .voter-item {
      background: #f8fafc;
      padding: 10px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      margin-bottom: 6px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .log-box {
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      padding: 10px;
      border-radius: 8px;
      height: 240px;
      overflow-y: auto;
      font-family: monospace;
      font-size: 14px;
    }

    .log-line {
      margin-bottom: 4px;
    }

    .status-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
    }

    .status-connecting { background: orange; }
    .status-connected { background: green; }
    .status-error { background: red; }
  </style>
</head>
<body>

  <div class="header">⚙️ Admin Dashboard</div>
  <div class="subheader">Szavazáskezelés és valós idejű események követése</div>
    <a href="logout.php" style="position:absolute; top:20px; right:20px;">Kijelentkezés</a>

  <div class="status-box" id="connectionStatus">
    Csatlakozási állapot: <span id="statusText">Szerver indítása...</span>
    <span class="status-indicator status-connecting" id="statusDot"></span>
  </div>

  <div class="container">

    <!-- Szavazó vezérlőpult -->
    <div class="card">
      <h3>Szavazás vezérlés</h3>
      <p>Státusz: <strong id="voteStatus">Inaktív</strong></p>
      <form action="start.php" method="post" style="display:inline;">
        <button class="btn btn-start">Szavazás indítása</button>
      </form>
      <form action="stop.php" method="post" style="display:inline;">
        <button class="btn btn-stop">Szavazás leállítása</button>
      </form>
    </div>

    <!-- Szavazó hozzáadása -->
    <div class="card">
      <h3>Új szavazó hozzáadása</h3>
      <form method="post" action="add_voter.php">
        <input class="input" type="text" name="voter_name" placeholder="Szavazó neve" required>
        <input class="input" type="text" name="voter_id" placeholder="Szavazó azonosító (pl. V004)" required>
        <button class="btn btn-start" type="submit">Szavazó hozzáadása</button>
      </form>
    </div>

    <!-- Szerver log -->
    <div class="card">
      <h3>WebSocket napló</h3>
      <div id="logBox" class="log-box">
        <div class="log-line">📜 Log</div>
      </div>
    </div>

    
    <!-- Jogosultak -->
<div class="card" style="flex: 100%;">
  <h3>Jogosult szavazók</h3>
  <div class="voter-list" id="voterList">
    <?php foreach ($voters as $voter): ?>
      <div class="voter-item">
        <div>
          <?= htmlspecialchars($voter['name']) ?><br>
          <small>ID: <?= htmlspecialchars($voter['voter_id']) ?></small>
        </div>
        <div>
          <?php if ($voter['has_voted']): ?>
            <span style="background: #dcfce7; padding: 4px 10px; border-radius: 12px; color: green;">
              Szavazott
              <?php if ($voter['voted_at']): ?>
                <br><small><?= htmlspecialchars($voter['voted_at']) ?></small>
              <?php endif; ?>
            </span>
          <?php else: ?>
            <span style="background: #f3f4f6; padding: 4px 10px; border-radius: 12px;">Függőben</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>


<script>
  const logBox = document.getElementById('logBox');
  const statusText = document.getElementById('statusText');
  const statusDot = document.getElementById('statusDot');

  const socket = new WebSocket("ws://localhost:8080");

  socket.onopen = () => {
    statusText.textContent = "Csatlakozva";
    statusDot.className = "status-indicator status-connected";
    appendLog("🟢 WebSocket csatlakozva.");
  };

  socket.onmessage = (event) => {
    const msg = JSON.parse(event.data);
    appendLog("📩 " + msg.message);
  };

  socket.onerror = () => {
    statusText.textContent = "Hiba a kapcsolódás során";
    statusDot.className = "status-indicator status-error";
    appendLog("❌ WebSocket hiba.");
  };

  socket.onclose = () => {
    if (statusDot.className !== "status-indicator status-connected") {
      statusText.textContent = "Nincs kapcsolat";
      statusDot.className = "status-indicator status-error";
    }
  };

  function appendLog(message) {
    const line = document.createElement("div");
    line.className = "log-line";
    line.textContent = message;
    logBox.appendChild(line);
    logBox.scrollTop = logBox.scrollHeight;
  }
</script>
</body>
</html>
