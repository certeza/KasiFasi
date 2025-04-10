<?php
// Ensure session is started and db_connect included
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Use require_once if db_connect might be included multiple times indirectly
require_once __DIR__ . '/../includes/db_connect.php';

$search_query_global = isset($_GET['query']) ? htmlspecialchars(trim($_GET['query']), ENT_QUOTES, 'UTF-8') : '';
$page_has_search_results = !empty($search_query_global); // Flag for highlighting context
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; echo APP_TITLE; ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); // Cache busting ?>">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,300;0,400;0,700;1,400&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <!-- Add icon library if needed for hamburger icon (e.g., Font Awesome) -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
</head>
<body>
    <header>
        <nav class="main-nav">
            <a href="index.php" class="logo"><?php echo APP_TITLE; ?></a>

            <div class="header-controls">
                <form action="index.php" method="get" class="search-form">
                    <input type="search" name="query" placeholder="Zoek planten..." value="<?php echo $search_query_global; ?>" aria-label="Zoek planten">
                    <button type="submit" aria-label="Zoek">Zoek</button>
                </form>

                <button class="hamburger-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="hamburgerMenu">
                    <span></span> <!-- Burger lines -->
                    <span></span>
                    <span></span>
                </button>
            </div>

             <!-- Hamburger Menu Container - initially hidden -->
            <div class="hamburger-menu" id="hamburgerMenu" role="navigation" aria-label="Hoofdmenu">
                 <ul class="menu-links">
                    <li><a href="inleiding.php">Inleiding</a></li> 
                    <li><a href="index.php">Planten</a></li>
                    <li><a href="medewerkers.php">Medewerkers</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="add_plant.php">Plant Toevoegen</a></li>
                        <li><a href="add_medewerker.php">Medewerker Toevoegen</a></li>
                        <hr> <!-- Separator -->
                        <li class="welcome-user">Welkom, <?php echo htmlspecialchars(get_username()); ?>!</li>
                        <li><a href="logout.php">Uitloggen</a></li>
                    <?php else: ?>
                         <hr> <!-- Separator -->
                        <li><a href="login.php">Inloggen</a></li>
                        <li><a href="register.php">Registreren</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <?php
        // Display Flash Messages
        if (!empty($_SESSION['flash_messages'])) {
            echo '<div class="flash-messages" role="alert" aria-live="polite">';
            foreach ($_SESSION['flash_messages'] as $key => $message_data) {
                // Check if message still exists (might have been unset individually)
                if(isset($message_data['message'])) {
                    $message = htmlspecialchars($message_data['message'], ENT_QUOTES, 'UTF-8');
                    $type = htmlspecialchars($message_data['type'], ENT_QUOTES, 'UTF-8');
                    // Assign role based on type for better accessibility
                    $role = ($type === 'error' || $type === 'warning') ? 'alert' : 'status';
                    echo "<div class=\"flash {$type}\" role=\"{$role}\">{$message}</div>";
                }
            }
            echo '</div>';
            unset($_SESSION['flash_messages']); // Clear all messages after displaying container
        }
        ?>
        <!-- Main content starts here -->