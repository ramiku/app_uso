<?php
declare(strict_types=1);

$newsItem = $newsItem ?? [];
?>
<article class="news-card">
    <div class="news-card__media">
        <img
            src="<?php echo e((string)($newsItem['imageUrl'] ?? asset('img/placeholder.svg'))); ?>"
            alt="<?php echo e((string)($newsItem['title'] ?? 'Noticia')); ?>"
            loading="lazy"
        >
    </div>
    <div class="news-card__body">
        <?php
        $cardDateRaw = (string)($newsItem['date'] ?? 'now');
        $cardTimestamp = strtotime($cardDateRaw);
        $cardDateIso = $cardTimestamp !== false ? date('Y-m-d', $cardTimestamp) : date('Y-m-d');
        $cardDateHuman = $cardTimestamp !== false ? date('d/m/Y', $cardTimestamp) : date('d/m/Y');
        ?>
        <header class="news-card__header">
            <h3 class="news-card__title"><?php echo e($newsItem['title'] ?? 'Sin título'); ?></h3>
            <time class="news-card__date" datetime="<?php echo e($cardDateIso); ?>"><?php echo e($cardDateHuman); ?></time>
            <div class="news-card__rule" aria-hidden="true"></div>
        </header>
        <p class="news-card__excerpt"><?php echo e($newsItem['excerpt'] ?? ''); ?></p>
        <a class="button button--ghost news-card__cta" href="<?php echo e($newsItem['detailUrl'] ?? url_for('noticias')); ?>">Leer más</a>
    </div>
</article>
