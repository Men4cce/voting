<?php session_start(); ?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8">
  <title>Biztonságos Szavazási Rendszer</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f8fafc;
      margin: 0;
    }

    .header {
      padding: 40px 20px;
      text-align: center;
    }

    .header h1 {
      font-size: 28px;
      font-weight: bold;
      color: #1e293b;
    }

    .header p {
      font-size: 16px;
      color: #64748b;
      margin-top: 5px;
    }

    .admin-button {
      position: absolute;
      top: 20px;
      right: 20px;
      background: #f1f5f9;
      padding: 10px 16px;
      border-radius: 8px;
      color: #0f172a;
      text-decoration: none;
      font-weight: 600;
      border: 1px solid #e2e8f0;
    }

    .status-card {
      max-width: 600px;
      margin: 0 auto 20px;
      background: #fff;
      padding: 20px 30px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .status-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .status-icon {
      font-size: 24px;
      color: #9ca3af;
    }

    .status-info {
      display: flex;
      flex-direction: column;
    }

    .status-info strong {
      font-size: 16px;
      color: #1e293b;
    }

    .status-info small {
      font-size: 14px;
      color: #64748b;
    }

    .status-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .status-badge {
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: bold;
      color: white;
    }

    .badge-connecting {
      background: #facc15;
    }

    .badge-disconnected {
      background: #ef4444;
    }

    .badge-connected {
      background: #10b981;
    }

    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .dot-connecting {
      background: #facc15;
    }

    .dot-disconnected {
      background: #dc2626;
    }

    .dot-connected {
      background: #10b981;
    }

    .main-card {
      max-width: 600px;
      margin: 0 auto;
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }

    .main-card .icon {
      font-size: 48px;
      color: #64748b;
    }

    .main-card h2 {
      margin-top: 20px;
      font-size: 20px;
      color: #0f172a;
    }

    .main-card p {
      color: #64748b;
      margin-top: 8px;
    }
  </style>
</head>
<body>

  <a href="login.php" class="admin-button">Admin felület</a>

  <div class="header">
    <h1>Biztonságos Szavazási Rendszer</h1>
    <p>Valós idejű elektronikus szavazási platform</p>
  </div>

  <div class="status-card">
    <div class="status-left">
      <div class="status-icon">📡</div>
      <div class="status-info">
        <strong>Kapcsolati állapot</strong>
        <small id="status-text">Kapcsolódás a szavazási szerverhez...</small>
      </div>
    </div>
    <div class="status-right">
      <div id="status-badge" class="status-badge badge-connecting">Kapcsolódás...</div>
      <div id="status-dot" class="status-dot dot-connecting"></div>
    </div>
  </div>

  <div class="main-card">
    <div class="icon">🕒</div>
    <h2>Szavazás nem aktív</h2>
    <p>Kérlek várj, amíg az adminisztrátor elindítja a szavazást.</p>
  </div>

  <script>
    const statusText = document.getElementById('status-text');
    const statusBadge = document.getElementById('status-badge');
    const statusDot = document.getElementById('status-dot');

    function updateStatus(text, badgeClass, dotClass, badgeLabel) {
      statusText.textContent = text;
      statusBadge.className = 'status-badge ' + badgeClass;
      statusDot.className = 'status-dot ' + dotClass;
      statusBadge.textContent = badgeLabel;
    }

    try {
      const socket = new WebSocket('ws://localhost:8080');

      updateStatus('Kapcsolódás a szavazási szerverhez...', 'badge-connecting', 'dot-connecting', 'Kapcsolódás...');

      socket.onopen = () => {
        updateStatus('Kapcsolódva a szavazási szerverhez', 'badge-connected', 'dot-connected', 'Kapcsolódva');
      };

      socket.onerror = () => {
        updateStatus('Nem sikerült kapcsolódni a szerverhez', 'badge-disconnected', 'dot-disconnected', 'Nincs kapcsolat');
      };

      socket.onclose = () => {
        updateStatus('Nincs kapcsolat a szavazási szerverrel', 'badge-disconnected', 'dot-disconnected', 'Nincs kapcsolat');
      };
    } catch (err) {
      updateStatus('Hiba történt a kapcsolat során', 'badge-disconnected', 'dot-disconnected', 'Nincs kapcsolat');
    }
  </script>

</body>
</html>
