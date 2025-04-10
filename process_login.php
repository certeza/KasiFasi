<?php
require_once 'includes/db_connect.php'; // Establishes $pdo, session, functions

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Invalid request method.');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    set_flash_message('login_error', 'Username and password are required.', 'error');
    header('Location: login.php');
    exit;
}

try {
    // Find user by username
    $sql = "SELECT id, username, password_hash, role FROM users WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    // Verify user exists and password is correct
    if ($user && password_verify($password, $user['password_hash'])) {
        // --- Login Successful ---


        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Store user info in session
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        set_flash_message('login_success', 'Welcome back, ' . htmlspecialchars($user['username']) . '!', 'success');

        // Redirect to intended page or default
        // $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php';
        // unset($_SESSION['redirect_after_login']); // Clear redirect target
        header('Location: index.php'); // Simple redirect for now
        exit;

    } else {
        // --- Login Failed ---
        set_flash_message('login_error', 'Invalid username or password.', 'error');
        header('Location: login.php');
        exit;
    }



} catch (PDOException $e) {
    error_log("Login error for user '$username': " . $e->getMessage());
    set_flash_message('login_error', 'An error occurred during login. Please try again.', 'error');
    header('Location: login.php');
    exit;
}
?>