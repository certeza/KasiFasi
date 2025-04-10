<?php
require_once 'includes/db_connect.php';

$plant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_query = isset($_GET['query']) ? trim($_GET['query']) : null; // Get potential search query

if ($plant_id <= 0) {
    set_flash_message('not_found_id', 'Ongeldig Plant ID.', 'error');
    header('Location: index.php');
    exit;
}

$plant = null;
try {
    $sql = "SELECT * FROM planten WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $plant_id, PDO::PARAM_INT);
    $stmt->execute();
    $plant = $stmt->fetch();

    if (!$plant) {
        set_flash_message('not_found_plant', 'Plant niet gevonden.', 'error');
        // Redirect back to index, possibly with search query
        $redirect_url = 'index.php' . ($search_query ? '?query=' . urlencode($search_query) : '');
        header('Location: ' . $redirect_url);
        exit;
    }
} catch (PDOException $e) {
     error_log("Error fetching plant detail (ID: $plant_id): " . $e->getMessage());
     set_flash_message('db_error_detail', 'Fout bij ophalen plant details.', 'error');
     $redirect_url = 'index.php' . ($search_query ? '?query=' . urlencode($search_query) : '');
     header('Location: ' . $redirect_url);
     exit;
}

$page_title = highlight_term($plant['scientific_name'] ?: 'Plant Details', $search_query); // Highlight title too
require 'templates/header.php'; // $search_query_global is set in header

// Construct the back link URL, preserving the search query if it exists
$back_link_url = 'index.php' . ($search_query ? '?query=' . urlencode($search_query) : '');
$back_link_text = '&laquo; Terug naar Browse' . ($search_query ? ' Resultaten' : '');

?>

<div class="plant-detail-container">
    <a href="<?php echo $back_link_url; ?>" class="back-link"><?php echo $back_link_text; ?></a>

    <h2><?php echo highlight_term($plant['scientific_name'] ?: 'N/A', $search_query); ?></h2>
    <?php if (!empty($plant['local_names'])): ?>
        <p><strong>Lokale Namen:</strong> <?php echo highlight_term($plant['local_names'], $search_query); ?></p>
    <?php endif; ?>

    <div class="plant-images">
        <?php $has_image = false; ?>
        <?php for ($i = 1; $i <= 3; $i++): ?>
            <?php $img_path_key = "image{$i}_path"; ?>
            <?php $img_illus_key = "image{$i}_illustration_by"; ?>
            <?php if (!empty($plant[$img_path_key])):
                    $has_image = true; ?>
            <div class="image-container">
                 <img src="uploads/<?php echo htmlspecialchars(basename($plant[$img_path_key])); ?>" alt="Foto <?php echo $i; ?> van <?php echo htmlspecialchars($plant['scientific_name']); ?>">
                 <?php if (!empty($plant[$img_illus_key])): ?>
                    <p class="illustration-credit">Illus. door: <?php echo highlight_term($plant[$img_illus_key], $search_query); ?></p>
                 <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endfor; ?>
         <?php // Show placeholder if no images exist at all
            if (!$has_image): ?>
             <div class="image-container placeholder-image">Geen Afbeeldingen Beschikbaar</div>
         <?php endif; ?>
    </div>

    <div class="plant-info">
        <?php if (!empty($plant['category'])): ?>
            <p><strong>Categorie:</strong> <?php echo highlight_term($plant['category'], $search_query); ?></p>
        <?php endif; ?>
        <?php if (!empty($plant['synonym'])): ?>
            <p><strong>Synoniem(en):</strong> <?php echo highlight_term($plant['synonym'], $search_query); ?></p>
        <?php endif; ?>
         <?php if (!empty($plant['name_meaning'])): ?>
            <p><strong>Naam Betekenis:</strong> <?php echo highlight_term($plant['name_meaning'], $search_query); ?></p>
        <?php endif; ?>

        <?php if (!empty($plant['description'])): ?>
            <h3>Beschrijving</h3>
            <?php echo highlight_term($plant['description'], $search_query); ?>
        <?php endif; ?>


        <h3>Details</h3>
        <ul>
            <?php $has_details = false; ?>
            <?php if (!empty($plant['occurrence'])): $has_details = true; ?><li><strong>Voorkomen:</strong> <?php echo highlight_term($plant['occurrence'], $search_query); ?></li><?php endif; ?>
            <?php if (!empty($plant['distribution'])): $has_details = true; ?><li><strong>Verspreiding:</strong> <?php echo highlight_term($plant['distribution'], $search_query); ?></li><?php endif; ?>
            <?php if (!empty($plant['domestication'])): $has_details = true; ?><li><strong>Domesticatie:</strong> <?php echo highlight_term($plant['domestication'], $search_query); ?></li><?php endif; ?>
            <?php if (!empty($plant['commercial_use'])): $has_details = true; ?><li><strong>Commercieel Gebruik:</strong> <?php echo highlight_term($plant['commercial_use'], $search_query); ?></li><?php endif; ?>
             <?php if (!$has_details): ?>
                 <li>Geen specifieke details opgegeven.</li>
             <?php endif; ?>
        </ul>

         <?php if (!empty($plant['application'])): ?>
            <h3>Toepassing</h3>
            <?php echo highlight_term($plant['application'], $search_query); ?>
        <?php endif; ?>

        <?php if (!empty($plant['created_at'])): ?>
        <p><small>Record Toegevoegd: <?php
            try {
                $date = new DateTime($plant['created_at']);
                echo $date->format('Y-m-d H:i');
            } catch (Exception $e) { echo 'N/A'; }
           ?></small></p>
        <?php endif; ?>

        <?php if (is_logged_in()): // Show Edit button only if logged in ?>
            <div class="plant-actions">
                 <a href="edit_plant.php?id=<?php echo $plant_id; ?>" class="btn-add">Bewerk Plant</a>
                 <!-- Add Delete button later if needed -->
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'templates/footer.php'; ?>