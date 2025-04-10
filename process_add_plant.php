<?php
require_once 'includes/db_connect.php';
require_login(); // <-- User must be logged in to process adding a plant

// --- Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    // Set flash message? Maybe not needed as it shouldn't happen normally
    exit('Invalid request method.');
}

// --- CSRF Check (Recommended for production) ---
// if (!verify_csrf_token($_POST['csrf_token'])) {
//     set_flash_message('csrf_error', 'Ongeldig beveiligingstoken. Probeer opnieuw.', 'error');
//     header('Location: add_plant.php');
//     exit;
// }

// --- Get & Sanitize Form Data ---
$form_data = [];
// Define all fields expected from the form (matching table columns)
$form_fields = [
    'category', 'scientific_name', 'synonym', 'local_names', 'description',
    'occurrence', 'distribution', 'domestication', 'application', 'commercial_use',
    'name_meaning', 'image1_illustration_by', 'image2_illustration_by', 'image3_illustration_by'
];

foreach ($form_fields as $field) {
    // Trim whitespace, allow null if empty string after trimming
    $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    $form_data[$field] = ($value === '') ? null : $value;
}

// --- Basic Validation ---
$errors = [];
if (empty($form_data['scientific_name'])) {
    $errors[] = 'Wetenschappelijke Naam is verplicht.';
}
// Add more validation rules here if needed (e.g., check lengths against DB limits)
// Example
}

// --- Basic Validation ---
$errors = [];
if (empty($form_data['scientific_name'])) {
    $errors[] = 'Wetenschappelijke Naam is verplicht.';
}
// Voeg meer validaties toe indien nodig (bv. max lengte)

// --- Handle Image Uploads ---
$uploaded_image_paths = [
    'image1_path' => null,
    'image2_path' => null,
    'image3_path' => null
];
$temp_files_to_delete_on_error = []; // Keep track of successfully uploaded files

for ($i = 1; $i <= 3; $i++) {
    $file_key = "image{$i}";
    $path_key = "image{$i}_path";

    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {

        // Check file size
        if ($_FILES[$file_key]['size'] > MAX_FILE_SIZE) {
             $errors[] = "Afbeelding {$i} is te groot (Max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB).";
             continue; // Skip this file, proceed with validation
        }

        // Check file type/extension
        $original_filename = basename($_FILES[$file_key]['name']);
        if (!is_allowed_extension($original_filename, ALLOWED_EXTENSIONS)) {
            $errors[] = "Afbeelding {$i} heeft een ongeldig bestandstype. Toegestaan: " . implode(', ', ALLOWED_EXTENSIONS) . ".";
            continue; // Skip this file
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        // Sanitize base name to prevent path traversal or other issues
        $safe_basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
        // Add timestamp and uniqid for uniqueness, maybe user ID?
        $user_id = get_user_id() ?? 0; // Get user ID: if ($form_data['scientific_name'] && strlen($form_data['scientific_name']) > 80) { ... }

// --- Handle Image Uploads ---
$uploaded_image_paths = [
    'image1_path' => null,
    'image2_path' => null,
    'image3_path' => null
];
$files_to_delete_on_error = []; // Keep track of successfully uploaded files in this request

for ($i = 1; $i <= 3; $i++) {
    $file_key = "image{$i}";
    $path_key = "image{$i}_path";

    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {

        // Check file size
        if ($_FILES[$file_key]['size'] > MAX_FILE_SIZE) {
             $errors[] = "Afbeelding {$i} is te groot (Max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB).";
             continue; // Skip this file
        }

        // Check file type/extension
        $original_filename = basename($_FILES[$file_key]['name']);
        if (!is_allowed_extension($original_filename, ALLOWED_EXTENSIONS)) {
            $errors[] = "Afbeelding {$i} heeft een ongeldig bestandstype. Toegestaan: " . implode(', ', ALLOWED_EXTENSIONS) . ".";
            continue; // Skip this file
        }

        // Generate if available
        $unique_filename = time() . '_' . uniqid() . '_user' . $user_id . '_plant_' . $safe_basename . '.' . $extension;
        $target_path = UPLOAD_DIR . $unique_filename;

        // Move the uploaded file
        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
            $uploaded_image_paths[$path_key] = $unique_filename; // Store only the filename
            $temp_files_to_delete_on_error[] = $target_path; // Add to list for potential rollback
        } else {
            error_log("Failed to move uploaded file for new plant: " . $target_path . " from tmp: " . $_FILES[$file_key]['tmp_name']);
            $errors[] = "Kon geüploade Afbeelding {$i} niet opslaan. Controleer map permissies.";
            // Don't add to $uploaded_image_paths if move failed
        }

    } elseif (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (e.g., UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_PARTIAL)
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "Afbeelding {$i} is groter dan de server toestaat.",
            UPLOAD_ERR unique filename
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        // Sanitize base name to prevent issues
        $safe_basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
        // Make filename reasonably unique and somewhat descriptive
        $unique_filename = time() . '_' . uniqid() . '_plant_' . $safe_basename . '.' . $extension;
        $target_path = UPLOAD_DIR . $unique_filename;

        // Move the uploaded file
        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
            $uploaded_image_paths[$path_key] = $unique_filename; // Store only the filename
            $files_to_delete_on_error[] = $target_path; // Add to list for potential rollback
        } else {
            error_log("Failed to move uploaded file: " . $target_path . " from tmp: " . $_FILES[$file_key]['tmp_name']);
            $errors[] = "Kon geüploade Afbeelding {$i} niet opslaan._FORM_SIZE  => "Afbeelding {$i} is groter dan het formulier toestaat.",
            UPLOAD_ERR_PARTIAL    => "Afbeelding {$i} is slechts gedeeltelijk geüpload.",
            UPLOAD_ERR_NO_TMP_DIR => "Server configuratiefout: Geen tijdelijke map gevonden.",
            UPLOAD_ERR_CANT_WRITE => "Server configuratiefout: Kan bestand niet schrijven.",
            UPLOAD_ERR_EXTENSION  => "Server configuratiefout: Bestandsupload gestopt door extensie.",
        ];
        $error_message = $upload_errors[$_FILES[$file_key]['error']] ?? "Onbekende uploadfout (Code: " . $_FILES[$file_key]['error'] . ") voor afbeelding {$i}.";
        $errors[] = $error_message;
        error_log("Upload error for image {$i}: Code " . $_FILES[$file_key]['error']);
    }
} Serverfout.";
        }

    } elseif (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors (permissions, partial upload, etc.)
         $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => "Afbeelding {$i} is te groot (server limiet).",
            UPLOAD_ERR_FORM_SIZE  => "Afbeelding {$i} is te groot (formulier limiet).",
            UPLOAD_ERR_PARTIAL    => "Afbeelding {$i} is slechts gedeeltelijk geüpload.",
            UPLOAD_ERR_NO_TMP_DIR => "Server configuratiefout (geen temp map).",
            UPLOAD_ERR_CANT_WRITE => "Serverfout (schrijven mislukt).",
            UPLOAD_ERR_EXTENSION  => "Upload gestopt door PHP extensie.",
        ];
        $error_message = $upload_errors[$_FILES[$file_key]['error']]


// --- If Validation Errors, Redirect Back ---
if (!empty($errors)) {
    // Delete any files that were successfully uploaded during this failed attempt
    foreach ($temp_files_to_delete_on_error as $filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // Set flash messages for each error
    foreach ($errors as $i => $error) {
        set_flash_message('add_plant_error_' . $i, $error, 'error');
    }
    // Store submitted data (text fields only) to repopulate form
    $_SESSION['add_plant_form_data'] = $_POST;
    header('Location: add_plant.php');
    exit ?? "Onbekende uploadfout voor Afbeelding {$i}. Code: " . $_FILES[$file_key]['error'];
        $errors[] = $error_message;
        error_log("Upload error for {$file_key}: Code " . $_FILES[$file_key]['error']);
    }
}


// --- If Validation or Upload Errors, Redirect Back ---
if (!empty($errors)) {
    foreach ($errors as $i => $error) {
        set_flash_message('add_plant_error_' . $i, $error, 'error');
    }
    // Delete any files that *were* successfully uploaded during this failed attempt
    foreach($files_to_delete_on_error as $filepath) {
        if(file_exists($filepath)) {
            unlink($filepath);
        }
    }
    $_SESSION['add_plant_form_data'] =;
}

// --- Prepare Data for Database ---
// Combine text form data and successfully uploaded image paths
$db_data = array_merge($form_data, $uploaded_image_paths);
// Add created_at timestamp (MySQL handles this by default if defined in table)
// $db_data['created_at'] = date('Y-m-d H:i:s'); // Only if not $_POST; // Store submitted text data to repopulate form
    header('Location: add_plant.php');
    exit;
}

// --- Prepare Data for Database ---
// Combine form data and successfully uploaded image paths
$db_data = array_merge($form_data, $uploaded_image_paths);
// Add created_at timestamp? DB default CURRENT_TIMESTAMP handles this if set in schema.

// --- Insert into Database ---
// Build column list using DB default

// Build columns and placeholders dynamically for insertion
$columns = implode(', ', array_keys($db_data));
$placeholders = ':' . implode(', :', array_keys($db_data));

// --- Insert into Database ---
$sql = "INSERT INTO planten ({$columns}) VALUES ({$placeholders})";

try {
    $stmt = $pdo->prepare($sql);
 and placeholder list dynamically based on $db_data keys
$columns = implode(', ', array_map(function($key) { return "`" . $key . "`"; }, array_keys($db_data)));
$placeholders = implode(', ', array_map    // Bind values using the $db_data associative array
    $stmt->execute($(function($key) { return ":" . $key; }, array_keys($db_data)));

$sql = "INSERT INTO planten ({$columns}) VALUES ({$placedb_data);
    $new_plant_id = $pdo->lastInsertId();

    set_flash_message('add_success', 'Plant "' . htmlholders})";

try {
    $stmt = $pdo->prepare($specialchars($db_data['scientific_name']) . '" succesvol toegevoegdsql);
    // Bind values from the associative array $db_data
    $stmt->!', 'success');
    header('Location: plant_detail.php?id=' . $new_plant_id); // Redirect to the new plant's detail page
    exit;

} catch (PDOException $e) {
execute($db_data);
    $new_plant_id = $pdo->lastInsertId();

    error_log("Error inserting plant: " . $e->getMessage() . " Data: " . print_r($db_data, true));
    set_flash_message('db_error_insert', 'Databasefout bij toevoegen plant. Probeer opnieuw.', 'error');

    // Roll    set_flash_message('add_success', 'Plant "' . htmlspecialchars($db_data['scientific_name']) . '" succesvol toegevoegd!', 'back: Delete successfully uploaded files if insert fails
    foreach ($temp_files_success');
    header('Location: plant_detail.php?id='to_delete_on_error as $filepath) {
        if ( . $new_plant_id); // Redirect to the new plant'sfile_exists($filepath)) {
            unlink($filepath);
        } detail page
    exit;

} catch (PDOException $e) {
    error_log("Error inserting plant: " . $e->getMessage() . " Data:
    }

    // Keep form data for repopulation
    $_SESSION['add_plant_form_data'] = $_POST;
    header('Location " . print_r($db_data, true));
    set_flash_message('add: add_plant.php');
    exit;
}
?>