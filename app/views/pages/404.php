<?php
declare(strict_types=1);
?>
<section class="section container" aria-labelledby="not-found-title">
    <h1 id="not-found-title" class="section__title"><?php echo e($data['title'] ?? '404'); ?></h1>
    <p class="section__lead"><?php echo e($data['body'] ?? 'Página no encontrada.'); ?></p>
    <a class="button" href="<?php echo e(url_for('home')); ?>">Volver al inicio</a>
</section>
