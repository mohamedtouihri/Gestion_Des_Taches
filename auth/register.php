<?php

require(__DIR__ . "/../config/db.php");

$error   = "";
$success = "";

// -------------------------------------------------------
// HELPER: Password strength validation
// -------------------------------------------------------
function validatePassword($p) {
    $errors = [];
    if (strlen($p) < 8)             $errors[] = "au moins 8 caractères";
    if (!preg_match('/[0-9]/', $p)) $errors[] = "au moins un chiffre";
    if (!preg_match('/[A-Z]/', $p)) $errors[] = "au moins une majuscule";
    return $errors;
}

// -------------------------------------------------------
// PROCESS FORM SUBMISSION
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!csrf_verify()) {
        $error = "Requête non valide. Veuillez réessayer.";

    } else {
        $nom     = trim($_POST['nom']              ?? '');
        $prenom  = trim($_POST['prenom']           ?? '');
        $email   = trim($_POST['email']            ?? '');
        $pass    = trim($_POST['password']         ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        // Step 1: Check empty fields
        if (empty($nom) || empty($prenom) || empty($email) || empty($pass) || empty($confirm)) {
            $error = "Veuillez remplir tous les champs.";

        // Step 2: Validate email format
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse email n'est pas valide (exemple : nom@email.com).";

        // Step 3: Check password strength
        } elseif (!empty($pwErrors = validatePassword($pass))) {
            $error = "Le mot de passe doit contenir : " . implode(", ", $pwErrors) . ".";

        // Step 4: Confirm passwords match
        } elseif ($pass !== $confirm) {
            $error = "Les deux mots de passe ne correspondent pas.";

        } else {
            // Step 5: Check if email is already used
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                // Step 6: Save — password hashed with bcrypt, never stored plain
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut, date_creation)
                     VALUES (?, ?, ?, ?, 'user', 'actif', NOW())"
                );
                $stmt->execute([$nom, $prenom, $email, $hash]);
                $new_id = $pdo->lastInsertId();

                log_action($pdo, $new_id, 'REGISTER', "Nouveau compte: $email");
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                $nom = $prenom = $email = "";
            }
        }
    }
}
?>
<?php require(__DIR__ . "/../includes/header.php"); ?>

<div class="card">
    <h2>Créer un compte</h2>
    <p class="subtitle">Rejoignez-nous ! C'est rapide et gratuit.</p>

    <?php if (!empty($error)): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="msg msg-success">
            <?= htmlspecialchars($success) ?>
            <br><a href="login.php" style="color:#276749; font-weight:600;">→ Se connecter</a>
        </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom"
                   placeholder="Dupont"
                   value="<?= htmlspecialchars($nom ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom"
                   placeholder="Jean"
                   value="<?= htmlspecialchars($prenom ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   placeholder="exemple@email.com"
                   value="<?= htmlspecialchars($email ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password"
                       placeholder="Choisissez un mot de passe" required>
                <button type="button" class="toggle-password"
                        onclick="togglePassword('password', this)">👁</button>
            </div>
            <p class="hint">Au moins 8 caractères, un chiffre, une majuscule.</p>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <div class="password-wrapper">
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Répétez le mot de passe" required>
                <button type="button" class="toggle-password"
                        onclick="togglePassword('confirm_password', this)">👁</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Créer mon compte</button>
    </form>
    <?php endif; ?>

    <hr class="divider">
    <div class="form-footer">
        Déjà un compte ? <a href="login.php">Se connecter</a>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.textContent = f.type === 'text' ? '🙈' : '👁';
}
</script>

<?php require(__DIR__ . "/../includes/footer.php"); ?>
