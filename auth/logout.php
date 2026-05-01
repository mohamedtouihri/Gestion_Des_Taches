<?php
// ===================================================
// logout.php — Secure Logout
// ===================================================
require("../config/db.php");


// Log the logout before destroying the session
if (isset($_SESSION['user_id'])) {
    log_action($pdo, $_SESSION['user_id'], 'LOGOUT', '');
}

// Destroy all session data
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>
