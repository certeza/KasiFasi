        <!-- Main content ends here -->
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_TITLE; ?></p>
    </footer>

    <!-- JavaScript for Hamburger Menu & Flash Messages -->
     <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Hamburger Menu Toggle
            const hamburgerToggle = document.querySelector('.hamburger-toggle');
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const mainContent = document.getElementById('main-content'); // Target main content

            if (hamburgerToggle && hamburgerMenu) {
                hamburgerToggle.addEventListener('click', () => {
                    const isExpanded = hamburgerToggle.getAttribute('aria-expanded') === 'true';
                    hamburgerToggle.setAttribute('aria-expanded', !isExpanded);
                    hamburgerMenu.classList.toggle('menu-open');
                    // Optional: Toggle body class to prevent scrolling when menu is open
                    document.body.classList.toggle('no-scroll', !isExpanded);
                     // Make content behind inert when menu is open
                    if (mainContent) {
                       mainContent.inert = !isExpanded;
                    }
                });

                // Close menu when focus moves out or Esc is pressed
                hamburgerMenu.addEventListener('keydown', (e) => {
                     if (e.key === 'Escape') {
                        hamburgerToggle.setAttribute('aria-expanded', 'false');
                        hamburgerMenu.classList.remove('menu-open');
                        document.body.classList.remove('no-scroll');
                        if (mainContent) mainContent.inert = false;
                        hamburgerToggle.focus(); // Return focus to the button
                     }
                });

                 // Optional: Close menu when clicking outside
                document.addEventListener('click', (event) => {
                    if (!hamburgerMenu.contains(event.target) && !hamburgerToggle.contains(event.target) && hamburgerMenu.classList.contains('menu-open')) {
                        hamburgerToggle.setAttribute('aria-expanded', 'false');
                        hamburgerMenu.classList.remove('menu-open');
                        document.body.classList.remove('no-scroll');
                         if (mainContent) mainContent.inert = false;
                    }
                });
            }

            // Simple script to fade out flash messages
            const flashMessagesContainer = document.querySelector('.flash-messages');
            if (flashMessagesContainer) {
                setTimeout(() => {
                    let messages = flashMessagesContainer.querySelectorAll('.flash');
                    messages.forEach(msg => {
                        msg.style.transition = 'opacity 0.5s ease-out';
                        msg.style.opacity = '0';
                        setTimeout(() => {
                           if (msg.parentNode) {
                               msg.parentNode.removeChild(msg);
                               // If container is empty after removing, remove it too
                               if (flashMessagesContainer && !flashMessagesContainer.hasChildNodes()) {
                                   flashMessagesContainer.remove();
                               }
                           }
                        }, 500); // Wait for fade out
                    });
                }, 5000); // 5 seconds display time
            }
        });
    </script>
    <?php // Placeholder for potential page-specific scripts ?>
    <?php if (isset($page_specific_scripts)) { echo $page_specific_scripts; } ?>
</body>
</html>