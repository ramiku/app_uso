<?php
declare(strict_types=1);
?>
<section class="section container" aria-labelledby="conocenos-title">
    <h1 id="conocenos-title" class="section__title"><?php echo e($data['title'] ?? 'Conócenos'); ?></h1>
    <p class="section__lead"><?php echo e($data['body'] ?? 'Equipo editorial de actualidad y análisis.'); ?></p>
</section>
