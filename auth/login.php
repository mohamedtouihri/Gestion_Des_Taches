<?php
require(__DIR__ . "/../config/db.php");

$error = "";

// -------------------------------------------------------
// PROTECTION BRUTE-FORCE
// -------------------------------------------------------
if (!isset($_SESSION['login_attempts']))    $_SESSION['login_attempts']   = 0;
if (!isset($_SESSION['last_attempt_time'])) $_SESSION['last_attempt_time'] = 0;

$locked       = false;
$wait_seconds = 30;

if ($_SESSION['login_attempts'] >= 5) {
    $elapsed = time() - $_SESSION['last_attempt_time'];
    if ($elapsed < $wait_seconds) {
        $remaining = $wait_seconds - $elapsed;
        $locked    = true;
        $error     = "Trop de tentatives. Attendez {$remaining} seconde(s).";
    } else {
        $_SESSION['login_attempts'] = 0;
    }
}

// -------------------------------------------------------
// TRAITEMENT DU FORMULAIRE
// -------------------------------------------------------
if (!$locked && $_SERVER["REQUEST_METHOD"] == "POST") {

    if (!csrf_verify()) {
        $error = "Requête non valide. Veuillez réessayer.";

    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email non valide.";

        } else {
            // Requête préparée — seuls les comptes actifs peuvent se connecter
            $stmt = $pdo->prepare(
                "SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // CONNEXION RÉUSSIE
                $_SESSION['login_attempts'] = 0;
                session_regenerate_id(true); // Prévient la fixation de session

                $_SESSION['user_id']    = $user['id'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['username']   = $user['nom'];
                $_SESSION['login_time'] = time();

                log_action($pdo, $user['id'], 'LOGIN_SUCCESS', "Email: $email");

                // ─── REDIRECTION SELON LE RÔLE ───────────────────────
                // C'est ici la correction principale :
                // admin  → son panneau d'administration
                // user   → son tableau de bord personnel
                if ($user['role'] === 'admin') {
                    header("Location: ../admin/users.php");
                } else {
                    header("Location: ../tasks/dashboard.php");
                }
                exit;

            } else {
                // ÉCHEC — message générique (ne dit pas lequel est faux)
                $_SESSION['login_attempts']++;
                $_SESSION['last_attempt_time'] = time();
                log_action($pdo, null, 'LOGIN_FAILED', "Email tente: $email");

                $left  = max(0, 5 - $_SESSION['login_attempts']);
                $error = $left > 0
                    ? "Email ou mot de passe incorrect. ($left tentative(s) restante(s))"
                    : "Compte temporairement bloqué. Attendez {$wait_seconds}s.";
            }
        }
    }
}
?>
<?php require(__DIR__ . "/../includes/header.php"); ?>

<div class="card">
    <h2>Connexion</h2>
    <p class="subtitle">Entrez vos identifiants pour accéder à votre espace.</p>

    <?php if (!empty($error)): ?>
        <div class="msg msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   placeholder="exemple@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   <?= $locked ? 'disabled' : '' ?> required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="password-wrapper">
                <input type="password" id="password" name="password"
                       placeholder="Votre mot de passe"
                       <?= $locked ? 'disabled' : '' ?> required>
                <button type="button" class="toggle-password"
                        onclick="togglePassword('password', this)">👁</button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"
                <?= $locked ? 'disabled' : '' ?>>
            Se connecter
        </button>
    </form>

    <hr class="divider">
    <div class="form-footer">
        Pas encore de compte ? <a href="register.php">Créer un compte</a>
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
