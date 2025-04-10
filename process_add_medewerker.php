<?php
require_once 'includes/db_connect.php';
require_login(); // <-- !!! BELANGRIJK: Autorisatie Check !!!

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    set_flash_message('invalid_request', 'Invalid request method.', 'error');
    header('Location: add_medewerker.php');
    exit;
}

// --- CSRF Check (Aanbevolen) ---
// if (!verify_csrf_token($_POST['csrf_token'])) { ... }

// --- Haal data op en trim ---
$naam = trim($_POST['naam'] ?? '');
$rol = trim($_POST['rol'] ?? '');

// --- Basis Validatie ---
$errors = [];
if (empty($naam)) {
    $errors[] = 'Naam is verplicht.';
}
if (empty($rol)) {
    $errors[] = 'Rol is verplicht.';
}
// Voeg eventueel meer validaties toe (bv. max lengte)

// --- Handel Foto Upload ---
$uploaded_foto_path = null; // Start met null
$foto_file = $_FILES['foto'] ?? null;

if ($foto_file && $foto_file['error'] === UPLOAD_ERR_OK) {
    // Check grootte
    if ($foto_file['size'] > MAX_FILE_SIZE) {
        $errors[] = "Foto is te groot (Max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB).";
    } else {
        // Check extensie
        $original_filename = basename($foto_file['name']);
        if (!is_allowed_extension($original_filename, ALLOWED_EXTENSIONS)) {
            $errors[] = "Ongeldig bestandstype voor foto. Toegestaan: " . implode(', ', ALLOWED_EXTENSIONS) . ".";
        } else {
            // Genereer unieke bestandsnaam
            $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
            $safe_basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
            $unique_filename = 'medewerker_' . time() . '_' . uniqid() . '_' . $safe_basename . '.' . $extension;
            $target_path = UPLOAD_DIR . $unique_filename;

            // Verplaats bestand
            if (move_uploaded_file($foto_file['tmp_name'], $target_path)) {
                $uploaded_foto_path = $unique_filename; // Sla alleen bestandsnaam op
            } else {
                error_log("Failed to move medewerker photo: " . $target_path);
                $errors[] = "Kon de foto niet opslaan.";
            }
        }
    }
} elseif ($foto_file && $foto_file['error'] !== UPLOAD_ERR_NO_FILE) {
    // Andere upload errors
    $errors[] = "Fout bij uploaden foto. Code: " . $foto_file['error'];
}

// --- Als er errors zijn, ga terug naar formulier ---
if (!empty($errors)) {
    foreach ($errors as $i => $error) {
        set_flash_message('medewerker_error_' . $i, $error, 'error');
    }
    $_SESSION['medewerker_form_data'] = $_POST; // Sla ingevulde data op
    header('Location: add_medewerker.php');
    exit;
}

// --- Voeg toe aan Database ---
$sql = "INSERT INTO medewerkers (naam, rol, foto_path) VALUES (:naam, :rol, :foto_path)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':naam', $naam, PDO::PARAM_STR);
    $stmt->bindParam(':rol', $rol, PDO::PARAM_STR);
    $stmt->bindParam(':foto_path', $uploaded_foto_path, PDO::PARAM_STR); // Bind path (kan null zijn)
    $stmt->execute();

    set_flash_message('medewerker_success', 'Medewerker "' . htmlspecialchars($naam) . '" succesvol toegevoegd!', 'success');
    header('Location: medewerkers.php'); // Ga naar overzichtspagina
    exit;

} catch (PDOException $e) {
    error_log("Error inserting medewerker: " . $e->getMessage());
    set_flash_message('medewerker_error_db', 'Databasefout bij toevoegen medewerker.', 'error');

    // Belangrijk: Verwijder de geüploade foto als de DB insert mislukt!
    if ($uploaded_foto_path && file_exists(UPLOAD_DIR . $uploaded_foto_path)) {
        unlink(UPLOAD_DIR . $uploaded_foto_path);
    }

    $_SESSION['medewerker_form_data'] = $_POST; // Houd data vast voor formulier
    header('Location: add_medewerker.php');
    exit;
}
?>