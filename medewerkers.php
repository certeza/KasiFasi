<?php
require_once 'includes/db_connect.php'; // Nodig voor $pdo, auth functies, APP_TITLE

// --- Haal medewerkers op, gesorteerd op Rol en dan Naam ---
$medewerkers_raw = [];
try {
    $sql = "SELECT id, naam, foto_path, rol
            FROM medewerkers
            ORDER BY
                CASE
                    WHEN rol = 'Samensteller' THEN 0 
                    ELSE 1                         
                END ASC,                         
                rol ASC,                         
                naam ASC";                       
    // Sorteer eerst op rol, dan op naam binnen de rol
    $stmt = $pdo->query($sql);
    $medewerkers_raw = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching medewerkers: " . $e->getMessage());
    set_flash_message('db_error_medewerkers', 'Kon medewerkers niet ophalen.', 'error');
}

// --- Groepeer medewerkers op rol ---
$grouped_medewerkers = [];
foreach ($medewerkers_raw as $medewerker) {
    $rol = $medewerker['rol'] ?: 'Overig'; // Geef een default rol als deze leeg is
    if (!isset($grouped_medewerkers[$rol])) {
        $grouped_medewerkers[$rol] = []; // Maak array aan voor deze rol indien nog niet bestaat
    }
    $grouped_medewerkers[$rol][] = $medewerker; // Voeg medewerker toe aan de groep
}
// Optioneel: Sorteer de rollen zelf alfabetisch (keys van de array)
// ksort($grouped_medewerkers); // Als je de groepen alfabetisch wilt

$page_title = 'Medewerkers'; // Titel aangepast
require 'templates/header.php';
?>

<div class="medewerkers-container">
    <h1><?php echo $page_title; ?></h1> <!-- Gebruik H1 voor hoofdtitel pagina -->

    <?php if (is_logged_in()): // Toon knop alleen als ingelogd ?>
        <div class="action-buttons">
            <a href="add_medewerker.php" class="btn-add">Nieuwe Medewerker Toevoegen</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($grouped_medewerkers)): ?>
        <div class="role-groups-container">
            <?php foreach ($grouped_medewerkers as $rol => $medewerkers_in_rol): ?>
                <?php
                    // Bepaal of deze groep standaard open moet zijn
                    // Maak de vergelijking case-insensitive voor zekerheid
                    $is_samensteller = (strtolower($rol) === 'samensteller');
                ?>
                <details class="role-group" <?php if ($is_samensteller) echo 'open'; ?>>
                    <summary class="role-summary">
                        <h2><?php echo htmlspecialchars($rol); ?> <span class="group-toggle-indicator"></span></h2>
                    </summary>
                    <div class="medewerker-list">
                        <?php foreach ($medewerkers_in_rol as $medewerker): ?>
                            <div class="medewerker-card">
                                <div class="medewerker-foto">
                                    <?php if (!empty($medewerker['foto_path'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars(basename($medewerker['foto_path'])); ?>" alt="Foto van <?php echo htmlspecialchars($medewerker['naam']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="placeholder-foto">?</div>
                                    <?php endif; ?>
                                </div>
                                <div class="medewerker-info">
                                    <h3><?php echo htmlspecialchars($medewerker['naam']); ?></h3>
                                    <!-- Rol hoeft niet per kaart getoond te worden, staat al in de groepstitel -->
                                    <!-- <p><?php // echo htmlspecialchars($medewerker['rol']); ?></p> -->
                                    <?php if (is_logged_in()): // Toon acties alleen als ingelogd ?>
                                        <div class="medewerker-actions">
                                            <!-- TODO: Voeg Edit/Delete links toe -->
                                            <!-- <a href="edit_medewerker.php?id=<?php // echo $medewerker['id']; ?>" class="btn-edit-small">Wijzig</a> -->
                                            <!-- <a href="process_delete_medewerker.php?id=<?php // echo $medewerker['id']; ?>" class="btn-delete-small" onclick="return confirm('Weet je zeker dat je <?php // echo htmlspecialchars($medewerker['naam']); ?> wilt verwijderen?');">Verwijder</a> -->
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div> <!-- end .medewerker-list -->
                </details> <!-- end .role-group -->
            <?php endforeach; ?>
        </div> <!-- end .role-groups-container -->

    <?php elseif (empty($medewerkers_raw) && !isset($_SESSION['flash_messages']['db_error_medewerkers'])): // Check originele array en geen db error?>
        <p>Er zijn nog geen medewerkers toegevoegd.</p>
    <?php endif; ?>

</div>

<?php require 'templates/footer.php'; ?>