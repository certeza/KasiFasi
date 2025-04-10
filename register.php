<?php
require_once 'includes/db_connect.php'; // Needed for session, flash messages, auth functions, APP_TITLE

// If user is already logged in, redirect them away
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$page_title = 'Registreren'; // Set specific page title
require 'templates/header.php'; // Includes session_start, db_connect, flash message display

// Retrieve previously submitted data if redirected back on error
$form_data = isset($_SESSION['register_form_data']) ? $_SESSION['register_form_data'] : [];
unset($_SESSION['register_form_data']); // Clear after retrieving

// Flash messages (including validation errors prefixed like 'register_error_...')
// are now handled automatically by header.php.
?>

<div class="auth-container"> <!-- Wrapper for consistent styling -->
    <h2>Registreer Nieuw Account</h2>

    <!-- Flash messages are displayed by header.php -->

    <form method="POST" action="process_register.php" class="auth-form">
         <!-- Add CSRF token for production -->
         <!-- <input type="hidden" name="csrf_token" value="<?php // echo generate_csrf_token(); ?>"> -->

         <div class="form-group">
            <label for="username">Gebruikersnaam:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? '', ENT_QUOTES); ?>" required pattern="^[a-zA-Z0-9_]{3,20}$" title="Alleen letters, cijfers, en underscore (_), 3-20 karakters." autocomplete="username">
            <small>(Alleen letters, cijfers, en underscore, 3-20 karakters)</small>
        </div>
        <div class="form-group">
            <label for="password">Wachtwoord:</label>
            <input type="password" id="password" name="password" required minlength="8" title="Minimaal 8 karakters." autocomplete="new-password">
            <small>(Minimaal 8 karakters)</small>
        </div>
         <div class="form-group">
            <label for="confirm_password">Bevestig Wachtwoord:</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-actions">
             <p>Al een account? <a href="login.php">Login hier</a></p>
            <button type="submit" class="btn-submit">Registreer</button>
        </div>
    </form>
</div>

<?php require 'templates/footer.php'; ?>