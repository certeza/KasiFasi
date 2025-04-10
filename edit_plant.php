<?php
require_once 'includes/db_connect.php';
require_login(); // Ensure user is logged in

$plant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($plant_id <= 0) {
    set_flash_message('edit_not_found_id', 'Ongeldig Plant ID.', 'error');
    header('Location: index.php');
    exit;
}

// Fetch existing plant data
$plant = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM planten WHERE id = :id");
    $stmt->bindParam(':id', $plant_id, PDO::PARAM_INT);
    $stmt->execute();
    $plant = $stmt->fetch();

    if (!$plant) {
        set_flash_message('edit_not_found_plant', 'Plant niet gevonden.', 'error');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching plant for edit (ID: $plant_id): " . $e->getMessage());
    set_flash_message('db_error_edit_fetch', 'Fout bij ophalen plant details.', 'error');
    header('Location: index.php');
    exit;
}

$page_title = 'Bewerk Plant: ' . htmlspecialchars($plant['scientific_name'] ?: 'N/A');
require 'templates/header.php';

// Retrieve form data if redirected back on error, otherwise use DB data
$form_data = $_SESSION['edit_plant_form_data'] ?? $plant;
unset($_SESSION['edit_plant_form_data']); // Clear session data after use
?>

<h2>Bewerk Plant: <?php echo htmlspecialchars($plant['scientific_name'] ?: 'N/A'); ?></h2>

<!-- Form errors are displayed by the header template now -->

<form method="POST" action="process_edit_plant.php" enctype="multipart/form-data" class="edit-plant-form">
    <input type="hidden" name="plant_id" value="<?php echo $plant_id; ?>">
    <!-- Add CSRF token here for production -->
    <!-- <input type="hidden" name="csrf_token" value="<?php // echo generate_csrf_token(); ?>"> -->

    <div class="form-grid">
        <!-- Scientific Name -->
        <div class="form-group">
            <label for="scientific_name">Wetenschappelijke Naam <span class="required">*</span>:</label>
            <input type="text" id="scientific_name" name="scientific_name" value="<?php echo htmlspecialchars($form_data['scientific_name'] ?? '', ENT_QUOTES); ?>" required>
        </div>
        <!-- Local Names -->
        <div class="form-group">
            <label for="local_names">Lokale Namen:</label>
            <input type="text" id="local_names" name="local_names" value="<?php echo htmlspecialchars($form_data['local_names'] ?? '', ENT_QUOTES); ?>">
        </div>
        <!-- Category -->
        <div class="form-group">
            <label for="category">Categorie:</label>
            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($form_data['category'] ?? '', ENT_QUOTES); ?>">
        </div>
        <!-- Synonym -->
        <div class="form-group">
            <label for="synonym">Synoniem(en):</label>
            <input type="text" id="synonym" name="synonym" value="<?php echo htmlspecialchars($form_data['synonym'] ?? '', ENT_QUOTES); ?>">
        </div>

        <!-- Description & Audio -->
        <div class="form-group full-width">
            <label for="description">Beschrijving:</label>
            <textarea id="description" name="description" rows="8"><?php echo htmlspecialchars($form_data['description'] ?? '', ENT_QUOTES); ?></textarea>

            <div class="audio-recording-section">
                <label>Auditieve Beschrijving (Sranan Tongo):</label>
                <button type="button" id="recordButton" class="btn-record">Start Opname</button>
                <span id="recordingStatus" style="margin-left: 10px; font-style: italic;"></span>
                <audio id="audioPlayback" controls style="display: none; margin-top: 5px;"></audio>
                 <p><small>Klik "Start Opname", spreek in Sranan Tongo, klik "Stop Opname". De beschrijving wordt vertaald naar Nederlands en hieronder toegevoegd.</small></p>
                 <div id="audioError" class="flash error" style="display: none; margin-top: 10px;"></div>
            </div>
        </div>

        <!-- Other Fields -->
        <div class="form-group">
            <label for="occurrence">Voorkomen:</label>
            <input type="text" id="occurrence" name="occurrence" value="<?php echo htmlspecialchars($form_data['occurrence'] ?? '', ENT_QUOTES); ?>">
        </div>
         <div class="form-group">
            <label for="distribution">Verspreiding:</label>
            <input type="text" id="distribution" name="distribution" value="<?php echo htmlspecialchars($form_data['distribution'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="form-group">
            <label for="domestication">Domesticatie:</label>
            <input type="text" id="domestication" name="domestication" value="<?php echo htmlspecialchars($form_data['domestication'] ?? '', ENT_QUOTES); ?>">
        </div>
         <div class="form-group">
            <label for="commercial_use">Commercieel Gebruik:</label>
            <input type="text" id="commercial_use" name="commercial_use" value="<?php echo htmlspecialchars($form_data['commercial_use'] ?? '', ENT_QUOTES); ?>">
        </div>
         <div class="form-group full-width">
            <label for="application">Toepassing:</label>
            <textarea id="application" name="application" rows="3"><?php echo htmlspecialchars($form_data['application'] ?? '', ENT_QUOTES); ?></textarea>
        </div>
        <div class="form-group">
            <label for="name_meaning">Naam Betekenis:</label>
            <input type="text" id="name_meaning" name="name_meaning" value="<?php echo htmlspecialchars($form_data['name_meaning'] ?? '', ENT_QUOTES); ?>">
        </div>

        <!-- Images -->
        <hr class="full-width">
        <h3 class="full-width">Afbeeldingen</h3>
        <?php for ($i = 1; $i <= 3; $i++):
            $img_path_key = "image{$i}_path";
            $img_illus_key = "image{$i}_illustration_by";
            $current_image_path = $plant[$img_path_key] ?? null;
            $current_illustration = $form_data[$img_illus_key] ?? ''; // Default to form data or DB data
        ?>
            <div class="form-group image-upload">
                <label>Afbeelding <?php echo $i; ?>:</label>
                <?php if(!empty($current_image_path)): ?>
                    <div class="current-image">
                        <img src="uploads/<?php echo htmlspecialchars(basename($current_image_path)); ?>" alt="Huidige Afbeelding <?php echo $i; ?>" width="100">
                        <small><?php echo htmlspecialchars(basename($current_image_path)); ?></small>
                        <label style="display:inline-block; margin-left: 10px; font-size: 0.9em;">
                           <input type="checkbox" name="delete_image<?php echo $i; ?>" value="1"> Verwijder
                        </label>
                    </div>
                    <label for="image<?php echo $i; ?>">Vervang Afbeelding <?php echo $i; ?>:</label>
                <?php else: ?>
                     <label for="image<?php echo $i; ?>">Upload Afbeelding <?php echo $i; ?>:</label>
                <?php endif; ?>

                <input type="file" id="image<?php echo $i; ?>" name="image<?php echo $i; ?>" accept="image/*">
                <input type="hidden" name="current_image<?php echo $i; ?>_path" value="<?php echo htmlspecialchars($current_image_path ?? ''); ?>"> <!-- Keep track of current image -->

                <label for="<?php echo $img_illus_key; ?>">Illustratie Door:</label>
                <input type="text" id="<?php echo $img_illus_key; ?>" name="<?php echo $img_illus_key; ?>" value="<?php echo htmlspecialchars($current_illustration, ENT_QUOTES); ?>">
            </div>
        <?php endfor; ?>

    </div><!-- End form-grid -->

    <div class="form-actions">
        <p><span class="required">*</span> Verplicht veld</p>
        <div> <!-- Wrapper for buttons -->
            <button type="submit" name="save_plant" class="btn-submit">Opslaan</button>
            <a href="plant_detail.php?id=<?php echo $plant_id; ?>" class="btn-cancel">Annuleren</a>
        </div>
    </div>
</form>

<?php
// Add page specific script for audio recording
ob_start(); // Start output buffering
?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const recordButton = document.getElementById('recordButton');
    const recordingStatus = document.getElementById('recordingStatus');
    const descriptionTextarea = document.getElementById('description');
    const audioPlayback = document.getElementById('audioPlayback');
    const audioError = document.getElementById('audioError');
    const plantId = document.querySelector('input[name="plant_id"]').value;

    let mediaRecorder;
    let audioChunks = [];
    let isRecording = false;
    let audioStream = null; // Keep track of the stream

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        recordButton.disabled = true;
        recordingStatus.textContent = 'Audio opname niet ondersteund door uw browser.';
        return;
    }

    recordButton.addEventListener('click', async () => {
        audioError.style.display = 'none';
        audioError.textContent = '';

        if (isRecording) {
            // --- Stop Recording ---
            try {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop(); // Triggers 'stop' event
                }
                // Clean up stream tracks AFTER stop event finishes naturally
                // if (audioStream) {
                //    audioStream.getTracks().forEach(track => track.stop());
                //    audioStream = null;
                //}
                // setRecordingState(false, 'Gestopt'); // Set state in 'stop' event
            } catch (err) {
                 console.error('Error stopping recorder:', err);
                 setRecordingState(false, 'Fout bij stoppen.');
                 showError('Kon opname niet correct stoppen.');
                 if (audioStream) audioStream.getTracks().forEach(track => track.stop()); // Force stop tracks on error
            }

        } else {
            // --- Start Recording ---
            try {
                // Ensure previous stream is stopped if any exists
                if (audioStream) audioStream.getTracks().forEach(track => track.stop());

                audioStream = await navigator.mediaDevices.getUserMedia({ audio: true }); // Get new stream
                mediaRecorder = new MediaRecorder(audioStream, { mimeType: 'audio/webm' }); // Specify mime if possible
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };

                mediaRecorder.onstop = async () => {
                    setRecordingState(false, 'Verwerken...');

                    if (audioChunks.length === 0) {
                        console.warn('No audio data recorded.');
                        setRecordingState(false, 'Geen audio opgenomen.');
                        if (audioStream) audioStream.getTracks().forEach(track => track.stop()); // Clean up tracks
                        return;
                    }

                    const audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    audioChunks = []; // Clear chunks after creating blob

                    // --- Send to Server ---
                    const formData = new FormData();
                    formData.append('audio_blob', audioBlob, `plant_${plantId}_${Date.now()}.webm`);
                    formData.append('plant_id', plantId);
                    // Add CSRF token if using

                    try {
                        const response = await fetch('process_audio_description.php', {
                            method: 'POST',
                            body: formData
                            // Add headers if needed (e.g., CSRF token)
                        });

                        const result = await response.json(); // Assume server ALWAYS returns JSON

                        if (!response.ok) {
                             throw new Error(result.error || `HTTP error! Status: ${response.status}`);
                        }

                        if (result.success && result.new_description) {
                             // Append the new description cleanly
                             const currentDesc = descriptionTextarea.value.trim();
                             const separator = "\n\n---\n";
                             descriptionTextarea.value = currentDesc + (currentDesc ? separator : '') + result.new_description_entry; // Append only the new entry

                            setRecordingState(false, 'Vertaling toegevoegd!');
                             setTimeout(() => setRecordingState(false), 3000);
                        } else {
                            throw new Error(result.error || 'Verwerking mislukt op server.');
                        }

                    } catch (serverError) {
                        console.error('Error sending/processing audio:', serverError);
                        showError(`Fout: ${serverError.message}`);
                        setRecordingState(false);
                    } finally {
                         // Clean up stream tracks associated with THIS recording attempt
                        if (audioStream) audioStream.getTracks().forEach(track => track.stop());
                        audioStream = null;
                    }
                };

                 mediaRecorder.onerror = (event) => {
                    console.error('MediaRecorder error:', event.error);
                    showError(`Opnamefout: ${event.error.name || 'Unknown error'}`);
                    setRecordingState(false);
                     if (audioStream) audioStream.getTracks().forEach(track => track.stop());
                     audioStream = null;
                };

                mediaRecorder.start();
                setRecordingState(true, 'Opname...');

            } catch (err) {
                console.error('Error accessing microphone:', err);
                 let userMessage = `Kon opname niet starten: ${err.name || 'Unknown error'}`;
                 if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                     userMessage = 'Microfoon toegang geweigerd. Sta toegang toe in browserinstellingen.';
                 } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                     userMessage = 'Geen microfoon gevonden.';
                 } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                     userMessage = 'Microfoon is mogelijk in gebruik door een andere applicatie.';
                 }
                showError(userMessage);
                setRecordingState(false);
                 if (audioStream) audioStream.getTracks().forEach(track => track.stop()); // Clean up if stream was obtained but start failed
                 audioStream = null;
            }
        }
    });

    function setRecordingState(recording, statusText = '') {
        isRecording = recording;
        recordButton.textContent = isRecording ? 'Stop Opname' : 'Start Opname';
        recordButton.classList.toggle('recording', isRecording);
        recordingStatus.textContent = statusText;
        // Disable form submission while recording/processing? Maybe not necessary.
    }

     function showError(message) {
        audioError.textContent = message;
        audioError.style.display = 'block';
    }
});
</script>
<?php
$page_specific_scripts = ob_get_clean(); // Get buffered JS
?>

<?php require 'templates/footer.php'; // Footer will output $page_specific_scripts ?>