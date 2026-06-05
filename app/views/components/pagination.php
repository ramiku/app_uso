<?php
declare(strict_types=1);

$pagination = $pagination ?? null;
if (!$pagination || $pagination['totalPages'] <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Paginación de noticias">
    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
        <?php $isCurrent = $i === $pagination['currentPage']; ?>
        <a
            class="pagination__link <?php echo $isCurrent ? 'is-current' : ''; ?>"
            href="<?php echo e(url_for('noticias', ['p' => $i])); ?>"
            <?php echo $isCurrent ? 'aria-current="page"' : ''; ?>
        >
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</nav>
