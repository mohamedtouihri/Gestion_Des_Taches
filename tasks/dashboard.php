<?php

require(__DIR__ . "/../config/db.php");

// --- Vérification : est-ce que l'utilisateur est connecté ? ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// --- Redirection : si c'est un admin, il n'a rien à faire ici ---
if ($_SESSION['role'] === 'admin') {
    header("Location: ../admin/users.php");
    exit;
}

// --- Timeout de session : 30 minutes d'inactivité ---
$timeout = 1800;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    session_destroy();
    header("Location: ../auth/login.php?timeout=1");
    exit;
}
$_SESSION['login_time'] = time();

// --- Gestion du toggle (terminer / rouvrir une tâche) ---
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tid  = intval($_GET['toggle']);
    $stmt = $pdo->prepare(
        "UPDATE taches
         SET etat = IF(etat='terminee','en_cours','terminee')
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$tid, $_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit;
}

// --- Récupérer les tâches de cet utilisateur uniquement ---
$stmt = $pdo->prepare(
    "SELECT * FROM taches WHERE user_id = ? ORDER BY date_creation DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$taches   = $stmt->fetchAll();
$total    = count($taches);
$termines = count(array_filter($taches, fn($t) => $t['etat'] === 'terminee'));
?>
<?php require(__DIR__ . "/../includes/header.php"); ?>

<!-- ── BARRE DE NAVIGATION UTILISATEUR ── -->
<nav class="navbar">
    <span class="logo">📋 Mes Tâches</span>
    <span style="font-size:0.85rem; color:#718096;">
        Bonjour, <?= htmlspecialchars($_SESSION['username']) ?> 👋
    </span>
    <div>
        <a href="../auth/profile.php"
           style="color:#4299e1;margin-right:16px;text-decoration:none;font-size:0.9rem;font-weight:500;">
            Mon profil
        </a>
        <a href="../auth/logout.php"
           style="color:#e53e3e;text-decoration:none;font-size:0.9rem;font-weight:500;">
            Déconnexion
        </a>
    </div>
</nav>

<div class="dashboard-wrapper">
    <h2>Tableau de bord</h2>
    <p class="subtitle" style="margin-bottom:20px;">
        <?= $termines ?> / <?= $total ?> tâche(s) terminée(s)
    </p>

    <a href="add.php">
        <button class="btn btn-primary"
                style="max-width:200px;margin-bottom:20px;">
            + Ajouter une tâche
        </button>
    </a>

    <?php if (empty($taches)): ?>
        <div class="empty-state">
            <p>Aucune tâche pour l'instant.</p>
            <p>Cliquez sur "Ajouter une tâche" pour commencer !</p>
        </div>
    <?php else: ?>
        <ul class="task-list">
            <?php foreach ($taches as $t): ?>
                <li style="<?= $t['etat']==='terminee' ? 'opacity:0.6;' : '' ?>">
                    <div>
                        <span style="<?= $t['etat']==='terminee'
                            ? 'text-decoration:line-through;' : '' ?>">
                            <?= htmlspecialchars($t['titre']) ?>
                        </span>
                        <?php if (!empty($t['description'])): ?>
                            <br>
                            <small style="color:#a0aec0;">
                                <?= htmlspecialchars($t['description']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="task-actions">
                        <a href="dashboard.php?toggle=<?= $t['id'] ?>" class="edit">
                            <?= $t['etat']==='terminee' ? '↩️ Rouvrir' : '✅ Terminer' ?>
                        </a>
                        <a href="edit.php?id=<?= $t['id'] ?>" class="edit">
                            ✏️ Modifier
                        </a>
                        <a href="delete.php?id=<?= $t['id'] ?>" class="delete"
                           onclick="return confirm('Supprimer cette tâche ?')">
                            🗑️ Supprimer
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require(__DIR__ . "/../includes/footer.php"); ?>
