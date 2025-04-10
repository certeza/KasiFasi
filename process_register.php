<?php
require_once 'includes/db_connect.php'; // Establishes $pdo, session, functions

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Invalid request method.');
}

// --- Get and Trim Input ---
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// --- Validation ---
$errors = [];

// Username validation (example: alphanumeric + underscore, 3-20 chars)
if (empty($username)) {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    $errors[] = 'Username must be 3-20 characters and contain only letters, numbers, or underscores.';
}

// Password validation (example: minimum length)
if (empty($password)) {
    $errors[] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

// Confirm password validation
if (empty($confirm_password)) {
    $errors[] = 'Please confirm your password.';
} elseif ($password !== $confirm_password) {
    $errors[] = 'Passwords do not match.';
}

// --- Check if Username Already Exists ---
if (empty($errors)) { // Only check DB if basic validation passes
    try {
        $sql_check = "SELECT id FROM users WHERE username = :username";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->fetch()) {
            $errors[] = 'Username is already taken. Please choose another.';
        }
    } catch (PDOException $e) {
        error_log("Username check error for '$username': " . $e->getMessage());
        $errors[] = 'An error occurred checking the username. Please try again.';
        // Don't proceed if we couldn't check the username
    }
}


// --- If Errors, Redirect Back ---
if (!empty($errors)) {
    foreach ($errors as $i => $error) {
        set_flash_message('register_error_' . $i, $error, 'error');
    }
    $_SESSION['register_form_data'] = $_POST; // Store submitted data (except passwords)
    unset($_SESSION['register_form_data']['password'], $_SESSION['register_form_data']['confirm_password']);
    header('Location: register.php');
    exit;
}

// --- Hash Password ---
// Use default algorithm (currently bcrypt), which is recommended
$password_hash = password_hash($password, PASSWORD_DEFAULT);

if ($password_hash === false) {
     error_log("Password hashing failed for user '$username'.");
     set_flash_message('register_error_hash', 'Could not process registration. Please try again later.', 'error');
     $_SESSION['register_form_data'] = $_POST; // Store submitted data (except passwords)
     unset($_SESSION['register_form_data']['password'], $_SESSION['register_form_data']['confirm_password']);
     header('Location: register.php');
     exit;
}

// --- Insert New User ---
try {
    $sql_insert = "INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt_insert->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
    $stmt_insert->execute();

    // --- Registration Successful ---
    set_flash_message('register_success', 'Registration successful!', 'success');
    header('Location: login.php'); // Redirect to login page
    exit;

} catch (PDOException $e) {
    // Check for specific duplicate entry error (code 23000 usually)
    if ($e->getCode() == 23000) {
         set_flash_message('register_error_duplicate', 'Username is already taken. Please choose another.', 'error');
    } else {
        error_log("User registration error for '$username': " . $e->getMessage());
        set_flash_message('register_error_db', 'An error occurred during registration. Please try again.', 'error');
    }
     $_SESSION['register_form_data'] = $_POST;
     unset($_SESSION['register_form_data']['password'], $_SESSION['register_form_data']['confirm_password']);
     header('Location: register.php');
     exit;
}
?>