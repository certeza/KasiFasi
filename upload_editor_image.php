<?php
require_once 'includes/db_connect.php'; // Voor $pdo, constants, en auth check

// === Autorisatie Check ===
// Alleen ingelogde gebruikers mogen uploaden via de editor
if (!is_logged_in()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => ['message' => 'Authenticatie vereist.']]);
    exit;
}

// === Configuratie ===
$accepted_origins = ["http://localhost", "http://jouw-domein.com"]; // Voeg je domeinen toe! Belangrijk tegen CSRF.
$image_folder = UPLOAD_DIR . "editor_images/"; // Submap voor editor afbeeldingen
$base_url_path = "/uploads/editor_images/"; // Het URL pad dat correspondeert met $image_folder

// === Origin Check (Beveiliging) ===
if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (!in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('HTTP/1.1 403 Origin Denied');
        echo json_encode(['error' => ['message' => 'Ongeldige origin.']]);
        exit;
    }
}

// === Reset CORS headers ===
// Deze zijn nodig zodat de editor (die draait in de browser) de response kan lezen
header('Access-Control-Allow-Credentials: true'); // Belangrijk indien images_upload_credentials true is
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*')); // Sta origin toe
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With'); // Nodige headers
header('Access-Control-Allow-Methods: POST, OPTIONS'); // Sta POST en OPTIONS (preflight) toe
header('Content-Type: application/json; charset=utf-8');

// === Handle OPTIONS request (CORS preflight) ===
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); // OK
    exit();
}

// === Handle POST request ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     header('HTTP/1.1 405 Method Not Allowed');
     echo json_encode(['error' => ['message' => 'Methode niet toegestaan.']]);
     exit;
}

// === Zoek de upload (TinyMCE stuurt het vaak als 'file') ===
if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    header("HTTP/1.1 400 Invalid Upload");
    echo json_encode(['error' => ['message' => 'Geen bestand geüpload of ongeldige upload.']]);
    exit;
}

// === Validatie ===
$file = $_FILES['file'];

// Check Error Code
if ($file['error'] !== UPLOAD_ERR_OK) {
    header("HTTP/1.1 500 Upload Error");
    echo json_encode(['error' => ['message' => 'Uploadfout code: ' . $file['error']]]);
    exit;
}

// Check Grootte
if ($file['size'] > MAX_FILE_SIZE) { // Gebruik constante uit db_connect
     header("HTTP/1.1 413 Payload Too Large");
     echo json_encode(['error' => ['message' => 'Bestand te groot.']]);
     exit;
}

// Check Extensie (dubbel check)
$original_filename = basename($file['name']);
if (!is_allowed_extension($original_filename, ALLOWED_EXTENSIONS)) {
    header("HTTP/1.1 415 Unsupported Media Type");
    echo json_encode(['error' => ['message' => 'Ongeldig bestandstype.']]);
    exit;
}

// === Genereer Unieke Bestandsnaam & Pad ===
$extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
$safe_basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
$unique_filename = 'editor_' . time() . '_' . uniqid() . '_' . $safe_basename . '.' . $extension;

// Maak de doelmap aan als deze niet bestaat
if (!is_dir($image_folder)) {
    if (!mkdir($image_folder, 0775, true)) {
        header("HTTP/1.1 500 Server Error");
        error_log("Failed to create editor image directory: " . $image_folder);
        echo json_encode(['error' => ['message' => 'Serverfout bij aanmaken map.']]);
        exit;
    }
}
$file_path = $image_folder . $unique_filename;

// === Verplaats Bestand ===
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    header("HTTP/1.1 500 Server Error");
    error_log("Failed to move editor image to: " . $file_path);
    echo json_encode(['error' => ['message' => 'Kon bestand niet opslaan.']]);
    exit;
}

// === Succes! Return JSON met locatie ===
// TinyMCE verwacht een JSON object met een 'location' key
// De URL moet absoluut zijn vanaf de web root (of relatief t.o.v. het domein)
$file_url = $base_url_path . $unique_filename;

echo json_encode(['location' => $file_url]);
exit;

?>