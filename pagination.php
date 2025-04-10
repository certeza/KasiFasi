<?php
// Expects: $current_page, $total_pages, $base_url_params (array of params like ['query' => 'term'])
if (isset($total_pages) && $total_pages > 1):
    $prev_page = $current_page - 1;
    $next_page = $current_page + 1;
    // Build base query string (handles search term etc.)
    $base_query_prev = http_build_query(array_merge($base_url_params, ['page' => $prev_page]));
    $base_query_next = http_build_query(array_merge($base_url_params, ['page' => $next_page]));
?>
<nav class="pagination" aria-label="Paginering">
    <?php if ($current_page > 1): ?>
        <a href="?<?php echo $base_query_prev; ?>" aria-label="Vorige pagina">&laquo; Vorige</a>
    <?php else: ?>
        <span class="disabled" aria-hidden="true">&laquo; Vorige</span>
    <?php endif; ?>

    <span class="page-info" aria-label="Huidige pagina <?php echo $current_page; ?> van <?php echo $total_pages; ?>">
        Pagina <?php echo $current_page; ?> van <?php echo $total_pages; ?>
    </span>

    <?php if ($current_page < $total_pages): ?>
        <a href="?<?php echo $base_query_next; ?>" aria-label="Volgende pagina">Volgende &raquo;</a>
    <?php else: ?>
         <span class="disabled" aria-hidden="true">Volgende &raquo;</span>
    <?php endif; ?>
</nav>
<?php endif; ?>