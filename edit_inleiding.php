<?php
require_once 'includes/db_connect.php';
require_login(); // <-- BELANGRIJK: Autorisatie Check!

$page_key = 'inleiding';
$current_data = ['title' => 'Inleiding', 'content' => '']; // Defaults

// Haal huidige data op om het formulier voor te vullen
try {
    $stmt = $pdo->prepare("SELECT title, content FROM page_content WHERE page_key = :page_key");
    $stmt->bindParam(':page_key', $page_key, PDO::PARAM_STR);
    $stmt->execute();
    $fetched_data = $stmt->fetch();
    if ($fetched_data) {
        $current_data = $fetched_data;
    }
} catch (PDOException $e) {
    error_log("Error fetching page content for editing '{$page_key}': " . $e->getMessage());
    set_flash_message('db_error_edit_fetch', 'Kon huidige pagina inhoud niet laden voor bewerken.', 'error');
    // Ga door met de defaults
}

$page_title = 'Bewerk Inleiding Pagina';
require 'templates/header.php';

// Haal eventuele data op na een error bij het opslaan
$form_data = $_SESSION['edit_inleiding_form_data'] ?? $current_data;
unset($_SESSION['edit_inleiding_form_data']);

// Flash messages (ook validatie errors) worden door header getoond
?>

<h2>Bewerk Inleiding Pagina</h2>

<!-- Flash messages worden hierboven getoond -->

<form method="POST" action="process_edit_inleiding.php" class="edit-page-form">
    <!-- Voeg CSRF token toe -->
    <!-- <input type="hidden" name="csrf_token" value="..."> -->
    <input type="hidden" name="page_key" value="<?php echo $page_key; ?>">

    <div class="form-group">
        <label for="title">Pagina Titel:</label>
        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($form_data['title'] ?? '', ENT_QUOTES); ?>" required>
    </div>

    <div class="form-group">
        <label for="wysiwyg-editor">Inhoud:</label>
        <!-- De textarea die TinyMCE zal vervangen -->
        <textarea id="wysiwyg-editor" name="content" rows="20"><?php echo htmlspecialchars($form_data['content'] ?? '', ENT_QUOTES); // Moet ge-escaped zijn voor weergave IN de textarea ?></textarea>
    </div>

    <div class="form-actions">
        <a href="inleiding.php" class="btn-cancel">Annuleren</a>
        <button type="submit" class="btn-submit">Opslaan</button>
    </div>
</form>

<?php
// --- TinyMCE Initialisatie Script ---
// Plaats dit VOOR de footer include. Vervang 'YOUR_API_KEY' door je TinyMCE API key (of gebruik de cloud gratis versie)
ob_start(); // Start output buffering voor het script
?>
<!-- Voeg TinyMCE toe via CDN -->
<!--
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
-->
<script src="https://cdn.tiny.cloud/1/hh83a03snqvpx4nqrmts2lk0pam80bcp6kezjvpeg0xjul10/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<!-- Place the following <script> and <textarea> tags your HTML's <body> -->
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: [
      // Core editing features
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'image', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
      // Your account includes a free trial of TinyMCE premium features
      // Try the most popular premium features until Apr 23, 2025:
      'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'editimage', 'advtemplate', 'ai', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
    ],
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    mergetags_list: [
      { value: 'First.Name', title: 'First Name' },
      { value: 'Email', title: 'Email' },
    ],
    ai_request: (request, respondWith) => respondWith.string(() => Promise.reject('See docs to implement AI Assistant')),
  });
</script>
<textarea>
  Welcome to TinyMCE!
</textarea>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    tinymce.init({
      selector: '#wysiwyg-editor', // Target de textarea
      plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount', // Voeg 'image' plugin toe
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
      height: 500, // Stel hoogte in
      menubar: true, // Toon menubar voor meer opties (zoals Insert -> Image)

      // === BELANGRIJK: Image Upload Configuratie ===
      // Zorg dat je PHP backend (upload_editor_image.php) dit ondersteunt
      images_upload_url: 'upload_editor_image.php', // Het pad naar je upload script
      images_upload_base_path: '/uploads/editor_images/', // Basis pad voor de URL die terugkomt (pas aan indien nodig)
      images_upload_credentials: true, // Stuur cookies mee (nodig voor sessie/authenticatie check in PHP)
      relative_urls: false, // Gebruik absolute URLs voor afbeeldingen
      remove_script_host: false, // Behoud het host deel van de URL

      // Optioneel: bestandskiezer voor lokale bestanden
      file_picker_types: 'image',
      file_picker_callback: (cb, value, meta) => {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');

        input.addEventListener('change', (e) => {
          const file = e.target.files[0];

          const reader = new FileReader();
          reader.addEventListener('load', () => {
            // Hier gebruiken we de interne upload handler van TinyMCE
            // die de images_upload_url gebruikt.
            const id = 'blobid' + (new Date()).getTime();
            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
            const base64 = reader.result.split(',')[1];
            const blobInfo = blobCache.create(id, file, base64);
            blobCache.add(blobInfo);

            // Roep de callback aan met de blob URL en bestandsnaam
            cb(blobInfo.blobUri(), { title: file.name });
          });
          reader.readAsDataURL(file);
        });
        input.click();
      },
       content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }' // Optionele styling in editor
    });
  });
</script>
<?php
$page_specific_scripts = ob_get_clean(); // Haal het gebufferde script op
?>

<?php require 'templates/footer.php'; // Footer zal $page_specific_scripts outputten ?>