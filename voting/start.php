<?php
require 'db.php';

// Előző szavazások leállítása
$pdo->exec("UPDATE polls SET active = 0");

// Legutóbbi szavazás aktiválása
$stmt = $pdo->query("SELECT id FROM polls ORDER BY id DESC LIMIT 1");
$latest = $stmt->fetch();

if ($latest) {
    $poll_id = $latest['id'];
    $pdo->prepare("UPDATE polls SET active = 1 WHERE id = ?")->execute([$poll_id]);
}

// (Opcionális) WebSocket broadcast küldése itt, ha szeretnéd

header("Location: admin.php");
exit;
