<?php
require_once 'includes/db_connect.php'; // Nodig voor $pdo en APP_TITLE

$page_key = 'inleiding'; // De sleutel voor deze specifieke pagina
$content_data = null;
$page_content_html = '<p>De inhoud voor de inleiding is nog niet toegevoegd.</p>'; // Default content
$page_title_db = 'Inleiding'; // Default title

try {
    $stmt = $pdo->prepare("SELECT title, content FROM page_content WHERE page_key = :page_key");
    $stmt->bindParam(':page_key', $page_key, PDO::PARAM_STR);
    $stmt->execute();
    $content_data = $stmt->fetch();

    if ($content_data) {
        // Gebruik htmlspecialchars alleen voor de titel, niet voor de content!
        $page_title_db = $content_data['title'] ? htmlspecialchars($content_data['title']) : 'Inleiding';
        // De content IS HTML en mag direct worden weergegeven (vertrouwd vanuit editor)
        $page_content_html = $content_data['content'] ?: $page_content_html; // Gebruik default als content leeg is
    }

} catch (PDOException $e) {
    error_log("Error fetching page content for key '{$page_key}': " . $e->getMessage());
    // Gebruik de default content en titel, maar log de fout
    set_flash_message('db_error_page', 'Kon pagina inhoud niet laden.', 'error');
}

$page_title = $page_title_db; // Zet de titel voor de header
require 'templates/header.php';
?>

<div class="page-content-container"> <!-- Gebruik een generieke container -->
    <h2><?php echo $page_title_db; // Echo de (eventueel gehtmlspecialcharsde) titel ?></h2>

    <?php if (is_logged_in()): // Toon bewerk knop alleen als ingelogd ?>
        <div class="action-buttons" style="margin-bottom: 20px; text-align: right;">
            <a href="edit_inleiding.php" class="btn-add">Bewerk Pagina</a>
        </div>
    <?php endif; ?>

    <div class="wysiwyg-content">
        <?php echo $page_content_html; // Echo de HTML content direct ?>
    </div>
</div>

<?php require 'templates/footer.php'; ?>