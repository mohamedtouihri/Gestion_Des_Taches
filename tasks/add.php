<?php
// ===================================================
// add.php — Add a New Task
// ===================================================
require("../config/db.php");


if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify()) {
        $error = "Requête non valide.";
    } else {
        $titre       = trim($_POST['titre']       ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($titre)) {
            $error = "Le titre ne peut pas être vide.";
        } elseif (strlen($titre) > 150) {
            $error = "Le titre est trop long (150 caractères max).";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO taches (user_id, titre, description, etat, date_creation)
                 VALUES (?, ?, ?, 'en_cours', NOW())"
            );
            $stmt->execute([$_SESSION['user_id'], $titre, $description]);
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>
<?php include("../includes/header.php"); ?>

<div class="card">
    <h2>Nouvelle tâche</h2>
    <p class="subtitle">Ajoutez une tâche à votre liste.</p>

    <?php if (!empty($error)): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre"
                   placeholder="Ex : Finir le projet PHP"
                   maxlength="150" required>
        </div>

        <div class="form-group">
            <label for="description">Description (optionnelle)</label>
            <input type="text" id="description" name="description"
                   placeholder="Détails supplémentaires..."
                   maxlength="500">
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
    </form>

    <div class="form-footer" style="margin-top:16px;">
        <a href="dashboard.php">← Retour au tableau de bord</a>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
