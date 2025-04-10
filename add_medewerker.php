<?php
require_once 'includes/db_connect.php';
require_login(); 

$page_title = 'Nieuwe Medewerker Toevoegen';
require 'templates/header.php';

// Haal eventuele eerder ingevulde data op (na een error)
$form_data = isset($_SESSION['medewerker_form_data']) ? $_SESSION['medewerker_form_data'] : [];
unset($_SESSION['medewerker_form_data']); // Wis na ophalen

// Haal eventuele errors op
$form_errors = [];
if (isset($_SESSION['flash_messages'])) {
    foreach ($_SESSION['flash_messages'] as $key => $msg_data) {
        if (strpos($key, 'medewerker_error_') === 0) {
            $form_errors[] = $msg_data;
            // Wis de specifieke error flash message niet hier, laat de header het doen
        }
    }
}
?>

<h2>Nieuwe Medewerker Toevoegen</h2>

<?php if (!empty($form_errors)): ?>
    <div class="flash error">
        <strong>Corrigeer de volgende fouten:</strong>
        <ul>
            <?php foreach ($form_errors as $error): ?>
                <li><?php echo htmlspecialchars($error['message']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>


<form method="POST" action="process_add_medewerker.php" enctype="multipart/form-data" class="add-medewerker-form">
    <!-- CSRF token aanbevolen voor productie -->
    <!-- <input type="hidden" name="csrf_token" value="generate_token()"> -->

    <div class="form-group">
        <label for="naam">Naam <span class="required">*</span>:</label>
        <input type="text" id="naam" name="naam" value="<?php echo htmlspecialchars($form_data['naam'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="rol">Rol <span class="required">*</span>:</label>
        <input type="text" id="rol" name="rol" value="<?php echo htmlspecialchars($form_data['rol'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="foto">Foto (optioneel):</label>
        <input type="file" id="foto" name="foto" accept="image/png, image/jpeg, image/gif, image/webp">
        <small>Toegestane types: png, jpg, gif, webp. Max grootte: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB</small>
    </div>

    <div class="form-actions">
        <p><span class="required">*</span> Verplicht veld</p>
        <button type="submit" class="btn-submit">Medewerker Toevoegen</button>
    </div>
</form>

<?php require 'templates/footer.php'; ?>