<?php
require_once 'includes/db_connect.php'; // Establishes $pdo connection and session, helpers

// --- Pagination Settings ---
$per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $per_page;

// --- Search ---
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$base_sql = " FROM planten ";
$where_clauses = [];
$params = []; // Parameters for prepared statement
$base_url_params = []; // For pagination links

if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    // Simple search across multiple relevant fields
    $where_clauses[] = "(scientific_name LIKE :search_term OR local_names LIKE :search_term OR category LIKE :search_term OR description LIKE :search_term OR application LIKE :search_term OR synonym LIKE :search_term)";
    $params[':search_term'] = $search_term;
    $base_url_params['query'] = $search_query; // Keep search term in pagination links
    $page_title = 'Zoekresultaten voor "' . htmlspecialchars($search_query) . '"';
} else {
    $page_title = 'Planten';
}

$where_sql = "";
if (!empty($where_clauses)) {
  $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

if (!empty($search_term)) {
  $where_sql = str_replace(':search_term', "'".$search_term."'", $where_sql);
}

// --- Get Total Count for Pagination ---
$total_records = 0;
$total_pages = 0;
try {
    $count_sql = "SELECT COUNT(*) " . $base_sql . $where_sql;
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute(); // Execute with search params if present
    $total_records = (int)$stmt_count->fetchColumn();
    if ($total_records > 0) {
        $total_pages = ceil($total_records / $per_page);
    }
} catch (PDOException $e) {
    error_log("Error fetching plant count: " . $e->getMessage());
    set_flash_message('db_error_count', 'Fout bij ophalen aantal planten.', 'error');
}

// --- Get Plants for Current Page ---
$plants = [];
if ($total_records > 0 && $current_page <= $total_pages) { // Ensure current page is valid
    try {
        // Select fields needed for the grid view
        $plants_sql = "SELECT id, scientific_name, local_names, image1_path "
                    . $base_sql . $where_sql
                    . " ORDER BY scientific_name ASC LIMIT :limit OFFSET :offset";

        $stmt_plants = $pdo->prepare($plants_sql);

        // Bind search params if they exist
        //foreach ($params as $key => $value) {
        //    $stmt_plants->bindValue($key, $value);
        //}
        // Bind limit and offset - must be integers
        $stmt_plants->bindValue(':limit', $per_page, PDO::PARAM_INT);
        $stmt_plants->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt_plants->execute();
        $plants = $stmt_plants->fetchAll();

    } catch (PDOException $e) {
        error_log("Error fetching plants: " . $e->getMessage());
        set_flash_message('db_error_fetch', 'Fout bij ophalen planten.', 'error');
        $plants = []; // Ensure $plants is an array even on error
    }
} elseif ($total_records > 0 && $current_page > $total_pages) {
    // Handle case where page number is too high (e.g., user manually changed URL)
     set_flash_message('page_error', 'Ongeldige paginanummer.', 'warning');
     // Optionally redirect to last valid page:
     // $last_page_params = array_merge($base_url_params, ['page' => $total_pages]);
     // header('Location: ?' . http_build_query($last_page_params));
     // exit;
}


// --- Include Header ---
require 'templates/header.php';
?>

    <?php if (!empty($search_query)): ?>
        <h2>Zoekresultaten voor "<?php echo htmlspecialchars($search_query); ?>"</h2>
         <p><a href="index.php" class="back-link">Wis Zoekopdracht / Bekijk Alles</a></p>
    <?php else: ?>
        <h2>Alle Planten</h2>
    <?php endif; ?>

    <?php if (!empty($plants)): ?>
        <div class="plant-grid">
            <?php foreach ($plants as $plant):
                 // Prepare detail link, including search query if applicable
                 $detail_link_params = ['id' => $plant['id']];
                 if (!empty($search_query)) {
                     $detail_link_params['query'] = $search_query;
                 }
                 $detail_link = 'plant_detail.php?' . http_build_query($detail_link_params);

                 // Apply highlighting
                 $plant_name_highlighted = highlight_term($plant['scientific_name'] ?: 'Unknown Plant', $search_query);
                 $local_names_highlighted = highlight_term($plant['local_names'] ?: 'No local names', $search_query);
            ?>
            <div class="plant-card">
                <a href="<?php echo $detail_link; ?>">
                    <?php if (!empty($plant['image1_path'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars(basename($plant['image1_path'])); ?>" alt="Foto van <?php echo htmlspecialchars($plant['scientific_name']); ?>" loading="lazy">
                    <?php else: ?>
                         <div class="placeholder-image">Geen Afbeelding</div>
                    <?php endif; ?>
                    <h3><?php echo $plant_name_highlighted; ?></h3>
                    <p><em><?php echo $local_names_highlighted; ?></em></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Include Pagination -->
        <?php require 'templates/pagination.php'; // Pass vars implicitly ?>

    <?php elseif (!empty($search_query)): ?>
        <p>Geen planten gevonden voor zoekterm "<?php echo htmlspecialchars($search_query); ?>".</p>
     <?php elseif ($total_records === 0 && empty($search_query)): ?>
         <p>Er zijn nog geen planten toegevoegd. <?php if(is_logged_in()): ?><a href="add_plant.php">Voeg de eerste toe!</a><?php endif; ?></p>
    <?php else: ?>
         <!-- This case might occur if page > total_pages and not redirected -->
         <p>Geen planten gevonden op deze pagina.</p>
    <?php endif; ?>

<?php
// --- Include Footer ---
require 'templates/footer.php';
?>