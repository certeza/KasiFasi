<?php
require_once 'includes/db_connect.php'; // Needed for session, flash messages, auth functions, APP_TITLE

// If user is already logged in, redirect them away from login page
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$page_title = 'Inloggen'; // Set specific page title
require 'templates/header.php'; // Includes session_start, db_connect, flash message display

// Flash messages are now handled automatically by header.php
// No need to explicitly get 'login_error' or 'auth_required' here anymore.
?>

<div class="auth-container"> <!-- Wrapper for consistent styling -->
    <h2>Inloggen</h2>

    <form method="POST" action="process_login.php" class="auth-form">
         <div class="form-group">
            <label for="username">Gebruikersnaam:</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">Wachtwoord:</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="form-actions">
            <p>Nog geen account? <a href="register.php">Registreer hier</a></p>
            <button type="submit" class="btn-submit">Login</button>
        </div>
    </form>
</div>

<?php require 'templates/footer.php'; ?>