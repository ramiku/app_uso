<?php
declare(strict_types=1);

$featured = $featured ?? [];
?>
<section class="hero" aria-label="Destacados">
    <div class="hero__slider js-hero-slider">
        <?php foreach ($featured as $index => $item): ?>
            <article class="hero__slide <?php echo $index === 0 ? 'is-active' : ''; ?>" data-slide-index="<?php echo $index; ?>">
                <img class="hero__image" src="<?php echo e(asset($item['image'])); ?>" alt="<?php echo e($item['title']); ?>" width="1240" height="480">
                <div class="hero__content">
                    <p class="hero__category"><?php echo e($item['category']); ?></p>
                    <h1 class="hero__title"><?php echo e($item['title']); ?></h1>
                    <p class="hero__excerpt"><?php echo e($item['excerpt']); ?></p>
                    <a class="button" href="<?php echo e(url_for($item['link'])); ?>">Ver noticia</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (count($featured) > 1): ?>
        <div class="hero__controls" role="tablist" aria-label="Controles de destacados">
            <?php foreach ($featured as $index => $item): ?>
                <button class="hero__dot <?php echo $index === 0 ? 'is-active' : ''; ?>" type="button" data-target-slide="<?php echo $index; ?>" aria-label="Ir al destacado <?php echo $index + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
