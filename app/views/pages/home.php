<?php
declare(strict_types=1);

$news = $data['news'] ?? [];
?>

<section class="section container home-banner" aria-label="Acceso al asistente virtual">
    <a class="home-banner__link" href="<?php echo e(url_for('asistente')); ?>" aria-label="Ir al Asistente Virtual USO">
        <img class="home-banner__image" src="<?php echo e(asset('img/banner.svg')); ?>" alt="Asistente Virtual USO" loading="eager">
    </a>
</section>

<section class="section container" aria-labelledby="noticias-destacadas">
    <div class="section__heading">
        <h2 id="noticias-destacadas" class="section__title">Últimas noticias</h2>
    </div>

    <div class="news-grid js-news-grid" id="news-grid">
        <?php foreach ($news as $newsItem): ?>
            <?php require APP_PATH . '/views/components/card_noticia.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="home-more-news">
        <a class="button button--ghost" href="<?php echo e(url_for('noticias')); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7z"/></svg>
            Mostrar más noticias
        </a>
    </div>
</section>
