<?php
require 'db.php';

// Minden szavazás leállítása
$pdo->exec("UPDATE polls SET active = 0");

// (Opcionális) WebSocket broadcast küldése itt, ha szeretnéd

header("Location: admin.php");
exit;
