<?php
declare(strict_types=1);

$sections  = $data['sections']  ?? [];
$enlaces   = $data['enlaces']   ?? [];
?>
<section class="section container" aria-labelledby="directorio-title">

    <div class="section__heading">
        <h1 id="directorio-title" class="section__title">Directorio de contacto</h1>
        <p class="section__lead">Teléfonos, correos electrónicos y enlaces de uso habitual.</p>
    </div>

    <?php foreach ($sections as $section): ?>
        <div class="dir-section" aria-labelledby="dir-<?php echo e(mb_strtolower(preg_replace('/\W+/', '-', $section['heading']), 'UTF-8')); ?>">
            <h2 class="dir-section__heading" id="dir-<?php echo e(mb_strtolower(preg_replace('/\W+/', '-', $section['heading']), 'UTF-8')); ?>">
                <?php echo e($section['heading']); ?>
            </h2>
            <?php if (!empty($section['description'])): ?>
                <p class="dir-section__desc"><?php echo e($section['description']); ?></p>
            <?php endif; ?>

            <div class="dir-groups">
                <?php foreach ($section['groups'] as $group): ?>
                    <div class="dir-group">
                        <h3 class="dir-group__label"><?php echo e($group['label']); ?></h3>
                        <ul class="dir-list">
                            <?php foreach ($group['items'] as $item): ?>
                                <?php
                                    $isTel = $item['type'] === 'tel';
                                ?>
                                <li class="dir-list__item">
                                    <button type="button"
                                            class="dir-list__link dir-list__link--<?php echo e($item['type']); ?> js-dir-contact"
                                            data-type="<?php echo e($item['type']); ?>"
                                            data-label="<?php echo e($item['label']); ?>"
                                            data-value="<?php echo e($item['value']); ?>">
                                        <span class="dir-list__icon" aria-hidden="true">
                                            <?php if ($isTel): ?>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21 11.36 11.36 0 003.54.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.54a1 1 0 01-.24 1.04l-2.21 2.21z"/></svg>
                                            <?php else: ?>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="dir-list__text">
                                            <span class="dir-list__name"><?php echo e($item['label']); ?></span>
                                            <span class="dir-list__value"><?php echo e($item['value']); ?></span>
                                        </span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if ($enlaces !== []): ?>
        <div class="dir-section dir-section--enlaces">
            <h2 class="dir-section__heading">Enlaces de interés</h2>
            <p class="dir-section__desc">Portales corporativos y herramientas de uso habitual.</p>
            <ul class="dir-links-grid">
                <?php foreach ($enlaces as $enlace): ?>
                    <li>
                        <a class="dir-link-card"
                           href="<?php echo e($enlace['url']); ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            <span class="dir-link-card__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
                            </span>
                            <span class="dir-link-card__label"><?php echo e($enlace['label']); ?></span>
                            <span class="dir-link-card__arrow" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</section>

<!-- Modal de contacto -->
<div class="contact-modal" id="contact-modal" hidden aria-modal="true" role="dialog" aria-labelledby="contact-modal-title">
    <div class="contact-modal__backdrop js-close-contact-modal" aria-hidden="true"></div>
    <div class="contact-modal__dialog">
        <button type="button" class="contact-modal__close js-close-contact-modal" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>

        <div class="contact-modal__header">
            <div class="contact-modal__icon-wrap" id="contact-modal-icon-wrap">
                <!-- ícono inyectado por JS -->
            </div>
            <div>
                <p class="contact-modal__label" id="contact-modal-label"></p>
                <p class="contact-modal__value" id="contact-modal-value"></p>
            </div>
        </div>

        <div class="contact-modal__actions">
            <button type="button" class="contact-modal__btn contact-modal__btn--primary js-contact-copy" id="contact-modal-copy">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3"/></svg>
                <span id="contact-modal-copy-label">Copiar</span>
            </button>
            <a href="#" class="contact-modal__btn contact-modal__btn--secondary" id="contact-modal-action">
                <span id="contact-modal-action-icon" aria-hidden="true"></span>
                <span id="contact-modal-action-label"></span>
            </a>
        </div>

        <p class="contact-modal__feedback" id="contact-modal-feedback" aria-live="polite"></p>
    </div>
</div>
