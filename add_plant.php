<?php
require_once 'includes/db_connect.php';
require_login(); // <-- User must be logged in to add a plant

$page_title = 'Nieuwe Plant Toevoegen';
require 'templates/header.php'; // Includes session_start, db_connect, APP_TITLE, flash display

// Retrieve previously submitted data if redirected back on error
$form_data = isset($_SESSION['add_plant_form_data']) ? $_SESSION['add_plant_form_data'] : [];
unset($_SESSION['add_plant_form_data']); // Clear after retrieving

// Flash messages (including validation errors) are handled by header.php
?>

<h2>Nieuwe Plant Toevoegen</h2>

<!-- Flash messages displayed by header -->

<form method="POST" action="process_add_plant.php" enctype="multipart/form-data" class="add-plant-form">
    <!-- Add CSRF token for production -->
    <!-- <input type="hidden" name="csrf_token" value="<?php // echo generate_csrf_token(); ?>"> -->

    <div class="form-grid">
        <!-- Scientific Name -->
        <div class="form-group">
            <label for="scientific_name">Wetenschappelijke Naam <span class="required">*</span>:</label>
            <input type="text" id="scientific_name" name="scientific_name" value="<?php echo htmlspecialchars($form_data['scientific_name'] ?? '', ENT_QUOTES); ?>" required maxlength="80">
        </div>
        <!-- Local Names -->
        <div class="form-group">
            <label for="local_names">Lokale Namen:</label>
            <input type="text" id="local_names" name="local_names" value="<?php echo htmlspecialchars($form_data['local_names'] ?? '', ENT_QUOTES); ?>" maxlength="200">
        </div>
        <!-- Category -->
        <div class="form-group">
            <label for="category">Categorie:</label>
            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($form_data['category'] ?? '', ENT_QUOTES); ?>" maxlength="80">
        </div>
        <!-- Synonym -->
        <div class="form-group">
            <label for="synonym">Synoniem(en):</label>
            <input type="text" id="synonym" name="synonym" value="<?php echo htmlspecialchars($form_data['synonym'] ?? '', ENT_QUOTES); ?>" maxlength="160">
        </div>

        <!-- Description -->
        <div class="form-group full-width">
            <label for="description">Beschrijving:</label>
            <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($form_data['description'] ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <!-- Occurrence -->
         <div class="form-group full-width">
         <label for="domestication">Voorkomen:</label>
         <input type="text" id="domestication" name="domestication" value="<?php echo htmlspecialchars($form_data['domestication'] ?? '', ENT_QUOTES); ?>">
        </div>
         <!-- Commercieel Gebruik -->
         <div class="form-group">
            <label for="commercial_use">Commercieel Gebruik:</label>
            <input type="text" id="commercial_use" name="commercial_use" value="<?php echo htmlspecialchars($form_data['commercial_use'] ?? '', ENT_QUOTES); ?>">
        </div>

         <!-- Toepassing -->
         <div class="form-group full-width">
            <label for="application">Toepassing:</label>
            <textarea id="application" name="application" rows="3"><?php echo htmlspecialchars($form_data['application'] ?? '', ENT_QUOTES); ?></textarea>
        </div>
        <!-- Naam Betekenis -->
        <div class="form-group">
            <label for="name_meaning">Naam Betekenis:</label>
            <input type="text" id="name_meaning" name="name_meaning" value="<?php echo htmlspecialchars($form_data['name_meaning'] ?? '', ENT_QUOTES); ?>">
        </div>

        <!-- Afbeeldingen -->
        <hr class="full-width">
        <h3 class="full-width">Afbeeldingen</h3>

        <?php for ($i = 1; $i <= 3; $i++):
            $img_illus_key = "image{$i}_illustration_by";
        ?>
        <div class="form-group image-upload">
            <label for="image<?php echo $i; ?>">Afbeelding <?php echo $i; ?>:</label>
            <input type="file" id="image<?php echo $i; ?>" name="image<?php echo $i; ?>" accept="image/png, image/jpeg, image/gif, image/webp">

            <label for="<?php echo $img_illus_key; ?>" style="margin-top: 10px;">Illustratie Door:</label>
            <input type="text" id="<?php echo $img_illus_key; ?>" name="<?php echo $img_illus_key; ?>" value="<?php echo htmlspecialchars($form_data[$img_illus_key] ?? '', ENT_QUOTES); ?>">
        </div>
        <?php endfor; ?>
        <small class="full-width">Toegestane types: png, jpg, gif, webp. Max grootte: <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB per afbeelding.</small>


    </div> <!-- End form-grid -->

    <div class="form-actions">
         <p><span class="required">*</span> Verplicht veld</p>
         <div> <!-- Wrapper for button(s) -->
            <button type="submit" class="btn-submit">Plant Toevoegen</button>
         </div>
    </div>
</form>

<?php require 'templates/footer.php'; ?>