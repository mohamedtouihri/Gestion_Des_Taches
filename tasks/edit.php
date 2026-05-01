<?php

require("../config/db.php");


if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$error = "";
$id    = intval($_GET['id'] ?? 0); // intval() ensures it's an integer (security)

// Fetch only if task belongs to logged-in user (access control)
$stmt = $pdo->prepare("SELECT * FROM taches WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$tache = $stmt->fetch();

if (!$tache) { die("Tâche introuvable ou accès refusé."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $error = "Requête non valide.";
    } else {
        $titre       = trim($_POST['titre']       ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($titre)) {
            $error = "Le titre ne peut pas être vide.";
        } elseif (strlen($titre) > 150) {
            $error = "Le titre est trop long.";
        } else {
            $stmt = $pdo->prepare(
                "UPDATE taches SET titre=?, description=?, date_modification=NOW()
                 WHERE id=? AND user_id=?"
            );
            $stmt->execute([$titre, $description, $id, $_SESSION['user_id']]);
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<?php include("../includes/header.php"); ?>

<div class="card">
    <h2>Modifier la tâche</h2>
    <p class="subtitle">Modifiez les informations de votre tâche.</p>

    <?php if (!empty($error)): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre"
                   value="<?= htmlspecialchars($tache['titre']) ?>"
                   maxlength="150" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description"
                   value="<?= htmlspecialchars($tache['description'] ?? '') ?>"
                   maxlength="500">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
    </form>

    <div class="form-footer" style="margin-top:16px;">
        <a href="dashboard.php">← Annuler</a>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
