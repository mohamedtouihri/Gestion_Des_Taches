<?php
// --- 1. START SESSION ---
// Must be called before any HTML output
session_start();

// --- 2. DATABASE CONNECTION ---
$pdo = new PDO(
  "mysql:host=sql309.infinityfree.com;dbname=if0_41810543_db_gestion;charset=utf8",
  "if0_41810543",
  "vel9kKBBM0JtF"
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// ===================================================
// CSRF PROTECTION FUNCTIONS
// ===================================================

// Step A: Generate a secret token and store it in the session
function csrf_generate() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Step B: Output a hidden <input> field inside a form
// Usage: echo csrf_field();
function csrf_field() {
    $token = csrf_generate();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Step C: Verify the token when a form is submitted
function csrf_verify() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}


// ===================================================
// LOGGING FUNCTION
// ===================================================

function log_action($pdo, $user_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare(
        "INSERT INTO journaux (utilisateur_id, action, adresse_ip, details, date_action)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$user_id, $action, $ip, $details]);
}
?>
