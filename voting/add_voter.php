<?php
session_start();

// Csak admin férhet hozzá
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Beküldés ellenőrzése
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['voter_name'] ?? '');
    $voterId = trim($_POST['voter_id'] ?? '');

    // Alap ellenőrzések
    if ($name === '' || $voterId === '') {
        $_SESSION['error'] = "Hiányzó adat!";
        header("Location: admin.php");
        exit;
    }

    // Duplikált azonosító ellenőrzés
    $stmt = $pdo->prepare("SELECT id FROM eligible_voters WHERE voter_id = ?");
    $stmt->execute([$voterId]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Ez az azonosító már létezik!";
        header("Location: admin.php");
        exit;
    }

    // Szavazó beszúrása
    $stmt = $pdo->prepare("INSERT INTO eligible_voters (name, voter_id) VALUES (?, ?)");
    $stmt->execute([$name, $voterId]);

    $_SESSION['success'] = "Szavazó sikeresen hozzáadva.";
    header("Location: admin.php");
    exit;
}

// Ha nem POST, vissza adminba
header("Location: admin.php");
exit;
