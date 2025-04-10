<?php
// functions.php
require_once 'config.php';

// Make sure to start the session on pages that need it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in.
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Requires the user to be logged in. Redirects to login page if not.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Gets the logged-in user's role.
 * @return string|null Role ('editor', 'admin') or null if not logged in.
 */
function get_user_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/**
 * Checks if the logged-in user has editor or admin privileges.
 * @return bool
 */
function can_edit(): bool {
    $role = get_user_role();
    return $role === 'editor' || $role === 'admin';
}

/**
 * Simple helper for outputting HTML-safe text.
 * @param string|null $string
 * @return string
 */
function htmlspecialchars_safe(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
?>