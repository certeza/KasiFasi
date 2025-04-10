<?php
require_once 'includes/db_connect.php';
require_login(); // <-- BELANGRIJK: Autorisatie Check!

// --- Check Request Method & Basis Input ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['page_key'])) {
    set_flash_message('edit_page_invalid_request', 'Ongeldig verzoek.', 'error');
    header('Location: index.php'); // Terug naar index als basis info mist
    exit;
}

$page_key = trim($_POST['page_key']);
$title = isset($_POST['title']) ? trim($_POST['title']) : null;
$content = isset($_POST['content']) ? $_POST['content'] : null; // NIET trimmen, HTML kan beginnen/eindigen met spaties

// --- CSRF Check (Aanbevolen) ---
// if (!verify_csrf_token($_POST['csrf_token'])) { ... }

// --- Validatie ---
$errors = [];
if (empty($page_key)) {
    $errors[] = 'Paginasleutel ontbreekt.'; // Zou niet mogen gebeuren
}
if (empty($title)) {
    $errors[] = 'Paginatitel is verplicht.';
}
// Basis check of content niet *volledig* leeg is (kan legitiem zijn, maar vaak onbedoeld)
if (trim(strip_tags($content ?? '')) === '') { // Check na verwijderen van tags en spaties
   $errors[] = 'Pagina inhoud mag niet leeg zijn.';
}

// === HTML Sanitization / Purification (STERK AANBEVOLEN) ===
// Omdat WYSIWYG editors complexe HTML genereren en potentieel onveilige
// code kunnen bevatten (vooral als 'source code' view is toegestaan),
// is het zeer aan te raden een library zoals HTMLPurifier te gebruiken
// voordat je de $content opslaat.
// Voorbeeld (vereist `composer require ezyang/htmlpurifier`):
/*
require 'vendor/autoload.php'; // Als je Composer gebruikt
$config = HTMLPurifier_Config::createDefault();
// Configureer toegestane tags/attributen (belangrijk!)
// $config->set('HTML.Allowed', 'p,a[href|title],img[src|alt|width|height],strong,em,ul,ol,li,br,h2,h3,h4');
$purifier = new HTMLPurifier($config);
$clean_content = $purifier->purify($content);
// Gebruik $clean_content hieronder ipv $content
*/
// Zonder purifier (minder veilig):
$clean_content = $content; // !! Let op veiligheidsrisico's !!

// --- Als er errors zijn, ga terug ---
if (!empty($errors)) {
    foreach ($errors as $i => $error) {
        set_flash_message('edit_page_error_' . $i, $error, 'error');
    }
    // Sla ingevulde data op (gebruik de *originele*, niet-gepurifieerde content hier!)
    $_SESSION['edit_inleiding_form_data'] = ['title' => $title, 'content' => $content];
    header('Location: edit_inleiding.php'); // Redirect terug naar edit pagina
    exit;
}

// --- Opslaan in Database (Insert or Update) ---
// Gebruik INSERT ... ON DUPLICATE KEY UPDATE voor eenvoud
$sql = "INSERT INTO page_content (page_key, title, content, updated_at)
        VALUES (:page_key, :title, :content, NOW())
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            content = VALUES(content),
            updated_at = NOW()";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':page_key', $page_key, PDO::PARAM_STR);
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':content', $clean_content, PDO::PARAM_STR); // Gebruik gezuiverde content
    $stmt->execute();

    set_flash_message('edit_page_success', 'Pagina "' . htmlspecialchars($title) . '" succesvol opgeslagen!', 'success');
    header('Location: inleiding.php'); // Ga naar de publieke pagina na succes
    exit;

} catch (PDOException $e) {
    error_log("Error saving page content for key '{$page_key}': " . $e->getMessage());
    set_flash_message('edit_page_db_error', 'Databasefout bij opslaan pagina.', 'error');

    // Houd data vast voor formulier (gebruik originele content)
    $_SESSION['edit_inleiding_form_data'] = ['title' => $title, 'content' => $content];
    header('Location: edit_inleiding.php'); // Terug naar edit pagina
    exit;
}
?>