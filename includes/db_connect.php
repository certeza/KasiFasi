<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

define('APP_TITLE', "KasiFasi - The Voice of Suriname's Rainforest");

// --- Database Configuration ---
// IMPORTANT: Use environment variables or a config file outside the web root in production!
define('DB_HOST', 'host');  
define('DB_NAME', 'db name');     // Replace with your database name
define('DB_USER', 'db user');      // Replace with your DB username
define('DB_PASS', 'db pass');     // Replace with your DB password
define('DB_CHARSET', 'utf8mb4');

// --- PDO Connection ---
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    // In production, log the error instead of displaying it
    error_log("Database Connection Error: " . $e->getMessage());
    die("Sorry, een database connectie fout is opgetreden. Probeer het later opnieuw."); // User-friendly message
}

// --- Start Session (Needed for Flash Messages & Login) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Flash Message Functions ---
/**
 * Simple function to set a flash message.
 */
function set_flash_message(string $name, string $message, string $type = 'info'): void {
    // Use unique keys to prevent overwriting if multiple errors of same type occur
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
     $_SESSION['flash_messages'][$type . '_' . $name . '_' . uniqid()] = ['message' => $message, 'type' => $type];
}

/**
 * Simple function to get and clear ALL flash messages (usually done in header/footer).
 * Individual message retrieval is less common now.
 */
// function get_flash_message(string $name): ?array { ... } // Less needed now

// --- File Upload Functions/Constants ---
/**
 * Check if a file extension is allowed.
 */
function is_allowed_extension(string $filename, array $allowed_extensions): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed_extensions);
}
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Absolute path to uploads
define('ALLOWED_EXTENSIONS', ['png', 'jpg', 'jpeg', 'gif', 'webp']);
define('MAX_FILE_SIZE', 16 * 1024 * 1024); // 16MB

// --- Authentication Helper Functions ---
/**
 * Checks if the user is currently logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}
/**
 * Returns the logged-in user's ID.
 */
function get_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}
/**
 * Returns the logged-in user's username.
 */
function get_username(): ?string {
    return $_SESSION['username'] ?? null;
}
/**
 * If the user is not logged in, redirects them to the login page.
 */
function require_login(string $redirect_page = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash_message('auth_required', 'Log in om deze pagina te bekijken.', 'warning');
        // Store the intended destination? Maybe later.
        header('Location: ' . $redirect_page);
        exit;
    }
}

// --- Highlight Helper Function ---
/**
 * Highlights occurrences of a search term within a text string.
 * Avoids highlighting inside HTML tags. Case-insensitive.
 */
function highlight_term(?string $text, ?string $term): string {
    if ($text === null) {
        return '';
    }
    $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // Escape text first
    if ($term === null || trim($term) === '' || $safe_text === '') {
        return $safe_text; // Return escaped text if no term or text
    }
    $safe_term = preg_quote(trim($term), '/');
    // Regex to find the term case-insensitively, avoiding matches inside HTML tags
    $pattern = '/(' . $safe_term . ')(?![^<]*>)/i';
    $replacement = '<span class="highlight">$1</span>';
    $highlighted_text = preg_replace($pattern, $replacement, $safe_text);
    return $highlighted_text ?? $safe_text; // Handle preg_replace error
}

// --- Ensure uploads directory exists ---
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0775, true)) {
         error_log("Failed to create uploads directory: " . UPLOAD_DIR);
         die("Configuratie error: Upload map kon niet worden aangemaakt.");
    }
}
if (!is_writable(UPLOAD_DIR)) {
     error_log("Upload directory is not writable: " . UPLOAD_DIR);
     die("Configuratie error: Upload map is niet schrijfbaar door de webserver.");
}
// Ensure temp subdir for audio exists
$temp_audio_dir = UPLOAD_DIR . 'temp/';
if (!is_dir($temp_audio_dir)) {
    if (!mkdir($temp_audio_dir, 0775, true)) {
        error_log("Failed to create temp audio directory: " . $temp_audio_dir);
        // Non-fatal, audio processing will fail later
    }
}

?>
