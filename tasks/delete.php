<?php

require("../config/db.php");


if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$id = intval($_GET['id'] ?? 0);

// Verify ownership before deleting
$stmt = $pdo->prepare("SELECT titre FROM taches WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$tache = $stmt->fetch();

if ($tache) {
    $stmt = $pdo->prepare("DELETE FROM taches WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    log_action($pdo, $_SESSION['user_id'], 'DELETE_TASK', "Tache supprimee: " . $tache['titre']);
}

header("Location: dashboard.php");
exit;
?>
