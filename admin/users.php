<?php

require(__DIR__ . "/../config/db.php");

// --- CONTRÔLE D'ACCÈS STRICT ---
// Si pas connecté → login
// Si connecté mais pas admin → dashboard user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    // Un simple user qui essaie d'accéder ici est redirigé
    log_action($pdo, $_SESSION['user_id'], 'UNAUTHORIZED_ADMIN_ACCESS',
               "Tentative d'accès admin par user_id: " . $_SESSION['user_id']);
    header("Location: ../tasks/dashboard.php");
    exit;
}

// --- Timeout de session 30 minutes ---
$timeout = 1800;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    session_destroy();
    header("Location: ../auth/login.php?timeout=1");
    exit;
}
$_SESSION['login_time'] = time();

// Journaliser l'accès admin
log_action($pdo, $_SESSION['user_id'], 'ADMIN_ACCESS', 'Panneau administration');

$success = "";
$error   = "";

// -------------------------------------------------------
// ACTIONS ADMIN
// -------------------------------------------------------

// Action : Activer / Désactiver un compte
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $uid = intval($_GET['toggle_status']);
    if ($uid == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas désactiver votre propre compte.";
    } else {
        $stmt = $pdo->prepare(
            "UPDATE utilisateurs
             SET statut = IF(statut='actif','inactif','actif')
             WHERE id = ?"
        );
        $stmt->execute([$uid]);
        // Récupérer le nouveau statut pour le journal
        $s = $pdo->prepare("SELECT statut, email FROM utilisateurs WHERE id=?");
        $s->execute([$uid]);
        $updated = $s->fetch();
        log_action($pdo, $_SESSION['user_id'], 'TOGGLE_USER_STATUS',
                   "Compte {$updated['email']} → {$updated['statut']}");
        $success = "Statut du compte mis à jour : {$updated['statut']}.";
    }
}

// Action : Supprimer un compte utilisateur
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    if ($uid == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte admin.";
    } else {
        $s = $pdo->prepare("SELECT email FROM utilisateurs WHERE id=?");
        $s->execute([$uid]);
        $target = $s->fetch();
        if ($target) {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$uid]);
            log_action($pdo, $_SESSION['user_id'], 'DELETE_USER',
                       "Compte supprimé: {$target['email']}");
            $success = "Compte \"{$target['email']}\" supprimé définitivement.";
        }
    }
}

// -------------------------------------------------------
// DONNÉES : Statistiques globales
// -------------------------------------------------------
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM utilisateurs WHERE role='user')   AS nb_users,
        (SELECT COUNT(*) FROM utilisateurs WHERE statut='actif') AS nb_actifs,
        (SELECT COUNT(*) FROM taches)                            AS nb_taches,
        (SELECT COUNT(*) FROM taches WHERE etat='terminee')      AS nb_terminees,
        (SELECT COUNT(*) FROM journaux WHERE action='LOGIN_FAILED'
                          AND date_action >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) AS echecs_24h
")->fetch();

// Tous les utilisateurs (sauf l'admin connecté en premier)
$users = $pdo->query(
    "SELECT * FROM utilisateurs ORDER BY role ASC, date_creation DESC"
)->fetchAll();

// Toutes les tâches avec le nom du propriétaire
$all_tasks = $pdo->query("
    SELECT t.*, u.nom, u.prenom, u.email
    FROM taches t
    JOIN utilisateurs u ON t.user_id = u.id
    ORDER BY t.date_creation DESC
    LIMIT 50
")->fetchAll();

// Journaux des 30 dernières actions
$logs = $pdo->query("
    SELECT j.*, u.email
    FROM journaux j
    LEFT JOIN utilisateurs u ON j.utilisateur_id = u.id
    ORDER BY j.date_action DESC
    LIMIT 30
")->fetchAll();
?>
<?php require(__DIR__ . "/../includes/header.php"); ?>

<!-- ── STYLE SPÉCIFIQUE ADMIN ── -->
<style>
.admin-navbar {
    background: #1e3a5f;
    color: white;
}
.admin-navbar .logo { color: white; }
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 18px 16px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    border-top: 4px solid #4299e1;
}
.stat-card.red   { border-top-color: #e53e3e; }
.stat-card.green { border-top-color: #38a169; }
.stat-card.orange{ border-top-color: #dd6b20; }
.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1a202c;
    line-height: 1;
}
.stat-label {
    font-size: 0.78rem;
    color: #718096;
    margin-top: 6px;
}
.admin-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
}
.admin-tab {
    padding: 8px 18px;
    border: none;
    background: none;
    font-size: 0.9rem;
    font-weight: 500;
    color: #718096;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    font-family: inherit;
    transition: all 0.15s;
}
.admin-tab.active {
    color: #1a56a4;
    border-bottom-color: #1a56a4;
}
.admin-tab:hover { color: #1a56a4; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-actif    { background:#c6f6d5; color:#22543d; }
.badge-inactif  { background:#fed7d7; color:#c53030; }
.badge-admin    { background:#bee3f8; color:#1a56a4; }
.badge-user     { background:#e2e8f0; color:#4a5568; }
.badge-terminee { background:#c6f6d5; color:#22543d; }
.badge-encours  { background:#fefcbf; color:#744210; }
.log-action {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    background: #e2e8f0;
    color: #2d3748;
}
.log-action.failed  { background:#fed7d7; color:#c53030; }
.log-action.success { background:#c6f6d5; color:#22543d; }
.log-action.admin   { background:#bee3f8; color:#1a56a4; }
</style>

<!-- ── BARRE DE NAVIGATION ADMIN (différente du user) ── -->
<nav class="navbar admin-navbar">
    <span class="logo">🔐 Administration</span>
    <span style="font-size:0.85rem; color:#a0aec0;">
        Admin : <?= htmlspecialchars($_SESSION['username']) ?>
    </span>
    <a href="../auth/logout.php"
       style="color:#fc8181;text-decoration:none;font-size:0.9rem;font-weight:500;">
        Déconnexion
    </a>
</nav>

<div class="dashboard-wrapper">

    <?php if (!empty($success)): ?>
        <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ── STATISTIQUES GLOBALES ── -->
    <h2 style="margin-bottom:16px;">📊 Vue d'ensemble</h2>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['nb_users'] ?></div>
            <div class="stat-label">Utilisateurs inscrits</div>
        </div>
        <div class="stat-card green">
            <div class="stat-number"><?= $stats['nb_actifs'] ?></div>
            <div class="stat-label">Comptes actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $stats['nb_taches'] ?></div>
            <div class="stat-label">Tâches totales</div>
        </div>
        <div class="stat-card green">
            <div class="stat-number"><?= $stats['nb_terminees'] ?></div>
            <div class="stat-label">Tâches terminées</div>
        </div>
        <div class="stat-card red">
            <div class="stat-number"><?= $stats['echecs_24h'] ?></div>
            <div class="stat-label">Échecs de connexion (24h)</div>
        </div>
    </div>

    <!-- ── ONGLETS ── -->
    <div class="admin-tabs">
        <button class="admin-tab active" onclick="showTab('users', this)">
            👥 Utilisateurs (<?= count($users) ?>)
        </button>
        <button class="admin-tab" onclick="showTab('tasks', this)">
            📋 Toutes les tâches (<?= count($all_tasks) ?>)
        </button>
        <button class="admin-tab" onclick="showTab('logs', this)">
            📜 Journal de sécurité
        </button>
    </div>

    <!-- ══════════════════════════════════════════════
         ONGLET 1 : GESTION DES UTILISATEURS
    ══════════════════════════════════════════════ -->
    <div id="tab-users" class="tab-content active">
        <ul class="task-list">
            <?php foreach ($users as $u): ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars($u['nom'].' '.$u['prenom']) ?></strong>
                        <span class="badge badge-<?= $u['role'] ?>"><?= htmlspecialchars($u['role']) ?></span>
                        <br>
                        <span style="font-size:0.85rem;color:#718096;">
                            <?= htmlspecialchars($u['email']) ?>
                        </span>
                        <br>
                        <small style="color:#a0aec0;">
                            Inscrit le <?= htmlspecialchars(substr($u['date_creation'], 0, 10)) ?>
                        </small>
                        &nbsp;
                        <span class="badge badge-<?= $u['statut'] ?>">
                            <?= htmlspecialchars($u['statut']) ?>
                        </span>
                    </div>

                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <div class="task-actions">
                        <a href="?toggle_status=<?= $u['id'] ?>" class="edit"
                           onclick="return confirm('Changer le statut de ce compte ?')">
                            <?= $u['statut']==='actif'
                                ? '🚫 Désactiver'
                                : '✅ Activer' ?>
                        </a>
                        <a href="?delete_user=<?= $u['id'] ?>" class="delete"
                           onclick="return confirm('Supprimer définitivement le compte de <?= htmlspecialchars($u['email']) ?> ?')">
                            🗑️ Supprimer
                        </a>
                    </div>
                    <?php else: ?>
                        <span style="font-size:0.8rem;color:#a0aec0;font-style:italic;">
                            (votre compte)
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- ══════════════════════════════════════════════
         ONGLET 2 : TOUTES LES TÂCHES
         (Admin peut voir les tâches de tout le monde,
          contrairement à un user normal)
    ══════════════════════════════════════════════ -->
    <div id="tab-tasks" class="tab-content">
        <?php if (empty($all_tasks)): ?>
            <div class="empty-state"><p>Aucune tâche enregistrée.</p></div>
        <?php else: ?>
        <ul class="task-list">
            <?php foreach ($all_tasks as $t): ?>
                <li style="<?= $t['etat']==='terminee' ? 'opacity:0.7;' : '' ?>">
                    <div>
                        <span style="<?= $t['etat']==='terminee'
                            ? 'text-decoration:line-through;' : '' ?>">
                            <?= htmlspecialchars($t['titre']) ?>
                        </span>
                        <span class="badge badge-<?= $t['etat']==='terminee'
                            ? 'terminee' : 'encours' ?>">
                            <?= $t['etat']==='terminee' ? 'Terminée' : 'En cours' ?>
                        </span>
                        <br>
                        <small style="color:#a0aec0;">
                            Propriétaire :
                            <strong><?= htmlspecialchars($t['nom'].' '.$t['prenom']) ?></strong>
                            — <?= htmlspecialchars($t['email']) ?>
                        </small>
                        <br>
                        <small style="color:#a0aec0;">
                            Créée le <?= htmlspecialchars(substr($t['date_creation'], 0, 10)) ?>
                        </small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════
         ONGLET 3 : JOURNAL DE SÉCURITÉ
    ══════════════════════════════════════════════ -->
    <div id="tab-logs" class="tab-content">
        <?php if (empty($logs)): ?>
            <div class="empty-state"><p>Aucune action enregistrée.</p></div>
        <?php else: ?>
        <ul class="task-list" style="font-size:0.85rem;">
            <?php foreach ($logs as $log):
                // Déterminer la classe CSS selon le type d'action
                $cls = '';
                if (str_contains($log['action'], 'FAILED') ||
                    str_contains($log['action'], 'UNAUTHORIZED')) $cls = 'failed';
                elseif (str_contains($log['action'], 'SUCCESS') ||
                        str_contains($log['action'], 'REGISTER')) $cls = 'success';
                elseif (str_contains($log['action'], 'ADMIN'))    $cls = 'admin';
            ?>
                <li>
                    <div>
                        <span class="log-action <?= $cls ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                        &nbsp;
                        <span style="color:#4a5568;">
                            <?= htmlspecialchars($log['email'] ?? 'Inconnu') ?>
                        </span>
                        <?php if (!empty($log['details'])): ?>
                            <br>
                            <small style="color:#a0aec0;">
                                <?= htmlspecialchars($log['details']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <small style="color:#a0aec0;white-space:nowrap;">
                            <?= htmlspecialchars($log['date_action']) ?>
                        </small>
                        <br>
                        <small style="color:#a0aec0;">
                            IP: <?= htmlspecialchars($log['adresse_ip']) ?>
                        </small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

</div><!-- end dashboard-wrapper -->

<!-- ── JavaScript pour les onglets ── -->
<script>
function showTab(name, btn) {
    // Cacher tous les onglets
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.admin-tab').forEach(b => b.classList.remove('active'));
    // Afficher l'onglet choisi
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>

<?php require(__DIR__ . "/../includes/footer.php"); ?>
