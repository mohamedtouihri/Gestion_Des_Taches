<?php
// ===================================================
// profile.php — Edit User Profile
// ===================================================
require("../config/db.php");


if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$error   = "";
$success = "";

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $error = "Requête non valide.";
    } else {
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $newpw  = trim($_POST['new_password'] ?? '');
        $confpw = trim($_POST['confirm_password'] ?? '');

        if (empty($nom) || empty($prenom)) {
            $error = "Le nom et le prénom sont obligatoires.";
        } else {
            // Update name
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, prenom=? WHERE id=?");
            $stmt->execute([$nom, $prenom, $_SESSION['user_id']]);
            $_SESSION['username'] = $nom;

            // Change password only if the user typed something
            if (!empty($newpw)) {
                if (strlen($newpw) < 8 || !preg_match('/[0-9]/', $newpw) || !preg_match('/[A-Z]/', $newpw)) {
                    $error = "Nouveau mot de passe trop faible (8 car., 1 chiffre, 1 majuscule).";
                } elseif ($newpw !== $confpw) {
                    $error = "Les mots de passe ne correspondent pas.";
                } else {
                    $hash = password_hash($newpw, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?");
                    $stmt->execute([$hash, $_SESSION['user_id']]);
                    log_action($pdo, $_SESSION['user_id'], 'PASSWORD_CHANGE', '');
                }
            }

            if (empty($error)) {
                log_action($pdo, $_SESSION['user_id'], 'PROFILE_UPDATE', '');
                $success = "Profil mis à jour avec succès.";
                // Re-fetch updated data
                $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            }
        }
    }
}
?>
<?php include("../includes/header.php"); ?>

<nav class="navbar">
    <span class="logo">📋 Mon Profil</span>
    <a href="../tasks/dashboard.php" style="color:#4299e1;text-decoration:none;font-size:0.9rem;font-weight:500;">← Tableau de bord</a>
</nav>

<div class="dashboard-wrapper">
    <div class="card" style="margin-top:20px;">
        <h2>Mon profil</h2>
        <p class="subtitle">Modifiez vos informations personnelles.</p>

        <?php if (!empty($error)): ?>
            <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="msg msg-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom"
                       value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom"
                       value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>
            <div class="form-group">
                <label>Email (non modifiable)</label>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                       style="background:#eee;cursor:not-allowed;">
            </div>
            <hr class="divider">
            <p style="font-size:0.85rem;color:#718096;margin-bottom:14px;">
                Laissez les champs mot de passe vides si vous ne souhaitez pas le changer.
            </p>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <div class="password-wrapper">
                    <input type="password" id="npw" name="new_password" placeholder="Laisser vide = inchangé">
                    <button type="button" class="toggle-password" onclick="togglePassword('npw',this)">👁</button>
                </div>
                <p class="hint">8 caractères min., 1 chiffre, 1 majuscule.</p>
            </div>
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <div class="password-wrapper">
                    <input type="password" id="cpw" name="confirm_password" placeholder="Confirmer">
                    <button type="button" class="toggle-password" onclick="togglePassword('cpw',this)">👁</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.textContent = f.type === 'text' ? '🙈' : '👁';
}
</script>

<?php include("../includes/footer.php"); ?>
