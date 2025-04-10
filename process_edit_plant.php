<?php
require_once 'includes/db_connect.php';
require_login(); // Ensure user is logged in

// --- Check Request Method & Basic Input ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['plant_id'])) {
    set_flash_message('edit_invalid_request', 'Ongeldig verzoek.', 'error');
    header('Location: index.php'); // Redirect to index if ID is missing or method wrong
    exit;
}

$plant_id = (int)$_POST['plant_id'];
if ($plant_id <= 0) {
    set_flash_message('edit_invalid_id', 'Ongeldig Plant ID.', 'error');
    header('Location: index.php');
    exit;
}

// --- CSRF Check (Aanbevolen in productie) ---
// if (!verify_csrf_token($_POST['csrf_token'])) { ... }

// --- Get & Sanitize Form Data ---
$form_data = [];
// Define all fields from the 'planten' table that can be edited via the form
$editable_fields = [
    'category', 'scientific_name', 'synonym', 'local_names', 'description',
    'occurrence', 'distribution', 'domestication', 'application', 'commercial_use',
    'name_meaning', 'image1_illustration_by', 'image2_illustration_by', 'image3_illustration_by'
];

foreach ($editable_fields as $field) {
    $value = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    $form_data[$field] = ($value === '') ? null : $value;
}

// --- Basic Validation ---
$errors = [];
if (empty($form_data['scientific_name'])) {
    $errors[] = 'Wetenschappelijke Naam is verplicht.';
}
// Add more validation rules as needed (e.g., max length)

// --- Handle Image Deletion & Uploads ---
$image_paths_to_update = []; // Store final paths for DB update
$old_image_paths_to_delete = []; // Store paths of files to delete from disk

for ($i = 1; $i <= 3; $i++) {
    $file_key = "image{$i}";
    $path_key = "image{$i}_path";
    $delete_key = "delete_image{$i}";
    $current_path_key = "current_image{$i}_path"; // From hidden input

    $current_path = $_POST[$current_path_key] ?? null;
    $delete_checked = isset($_POST[$delete_key]) && $_POST[$delete_key] == '1';
    $new_file_uploaded = isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK;

    $final_path = $current_path; // Start with current path

    // 1. Check for Deletion Request
    if ($delete_checked && !empty($current_path)) {
        $old_image_paths_to_delete[] = $current_path; // Mark for deletion from disk
        $final_path = null; // Set path to null for DB
        // Also clear the illustration if image is deleted
        $form_data["image{$i}_illustration_by"] = null;
    }

    // 2. Check for New Upload (only if not deleted or if replacing)
    if ($new_file_uploaded) {
        // Validation (Size, Type)
        if ($_FILES[$file_key]['size'] > MAX_FILE_SIZE) {
            $errors[] = "Afbeelding {$i} is te groot (Max: " . (MAX_FILE_SIZE / 1024 / 1024) . "MB).";
            continue; // Skip processing this file further
        }
        $original_filename = basename($_FILES[$file_key]['name']);
        if (!is_allowed_extension($original_filename, ALLOWED_EXTENSIONS)) {
            $errors[] = "Afbeelding {$i} heeft een ongeldig bestandstype.";
            continue; // Skip processing this file further
        }

        // Generate unique filename & move
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $safe_basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_filename, PATHINFO_FILENAME));
        $unique_filename = time() . '_' . uniqid() . '_plant_' . $plant_id . '_' . $safe_basename . '.' . $extension;
        $target_path = UPLOAD_DIR . $unique_filename;

        if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_path)) {
            // New file uploaded successfully
            // If there was an old image (and delete wasn't checked), mark old one for deletion
            if (!$delete_checked && !empty($current_path)) {
                $old_image_paths_to_delete[] = $current_path;
            }
            $final_path = $unique_filename; // Set new path for DB
        } else {
            error_log("Failed to move uploaded file for edit: " . $target_path);
            $errors[] = "Kon geüploade Afbeelding {$i} niet opslaan.";
            // Don't update the path if move failed
            $final_path = $current_path; // Revert to current path
        }
    }

    // Store the final path for this image slot
    $image_paths_to_update[$path_key] = $final_path;
}


// --- If Validation Errors, Redirect Back ---
if (!empty($errors)) {
    foreach ($errors as $i => $error) {
        // Use unique keys for flash messages
        set_flash_message('edit_error_' . $i, $error, 'error');
    }
    // Store submitted data (including potentially failed image paths) to repopulate form
    $_SESSION['edit_plant_form_data'] = array_merge($_POST, $image_paths_to_update);
    header('Location: edit_plant.php?id=' . $plant_id);
    exit;
}

// --- Prepare Data for Database Update ---
$db_data = array_merge($form_data, $image_paths_to_update); // Combine text fields and final image paths
$db_data['plant_id'] = $plant_id; // Add plant_id for WHERE clause

// Build the SET part of the UPDATE query dynamically
$set_parts = [];
foreach (array_keys($db_data) as $key) {
    if ($key !== 'plant_id') { // Don't include plant_id in SET clause
        $set_parts[] = "`" . $key . "` = :" . $key; // e.g., `scientific_name` = :scientific_name
    }
}
$set_sql = implode(', ', $set_parts);

// --- Update Database ---
$sql = "UPDATE planten SET " . $set_sql . " WHERE id = :plant_id";

try {
    $stmt = $pdo->prepare($sql);
    // Bind all values from $db_data (includes plant_id for WHERE)
    $stmt->execute($db_data);

    // --- Delete Old Image Files (after successful DB update) ---
    foreach ($old_image_paths_to_delete as $old_path) {
        $full_old_path = UPLOAD_DIR . basename($old_path); // Use basename for safety
        if (file_exists($full_old_path) && is_file($full_old_path)) {
             if (!unlink($full_old_path)) {
                 error_log("Failed to delete old image file: " . $full_old_path);
                 // Non-fatal error, maybe log or flash a warning
                 set_flash_message('delete_warning', 'Kon oude afbeelding niet verwijderen: ' . basename($old_path), 'warning');
             }
        }
    }

    set_flash_message('edit_success', 'Plant "' . htmlspecialchars($db_data['scientific_name']) . '" succesvol bijgewerkt!', 'success');
    header('Location: plant_detail.php?id=' . $plant_id); // Redirect to detail page
    exit;

} catch (PDOException $e) {
    error_log("Error updating plant ID {$plant_id}: " . $e->getMessage() . " Data: " . print_r($db_data, true));
    set_flash_message('edit_db_error', 'Databasefout bij bijwerken plant.', 'error');

    // Re-populate form data on DB error
    $_SESSION['edit_plant_form_data'] = array_merge($_POST, $image_paths_to_update);
    header('Location: edit_plant.php?id=' . $plant_id);
    exit;
}
?>