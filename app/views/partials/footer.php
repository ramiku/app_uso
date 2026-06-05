<?php
declare(strict_types=1);
?>
<footer class="site-footer">
    <div class="container site-footer__grid">
        <section>
            <h2 class="site-footer__title">Navegación</h2>
            <ul class="site-footer__links">
                <li><a href="<?php echo e(url_for('noticias')); ?>" aria-label="Ir a Noticias">Noticias</a></li>
                <li><a href="<?php echo e(url_for('directorio')); ?>" aria-label="Ir al Directorio de contacto">Directorio</a></li>
                <li><a href="<?php echo e(url_for('documentacion')); ?>" aria-label="Ir a Documentación">Documentación</a></li>
                <li><a href="<?php echo e(url_for('calendarios')); ?>" aria-label="Ir a Calendarios">Calendarios</a></li>
                <li><a href="<?php echo e(url_for('asistente')); ?>" aria-label="Ir a Asistente Virtual USO">Asistente Virtual USO</a></li>
                <li><a href="<?php echo e(url_for('privacidad')); ?>" aria-label="Ir a Política de privacidad">Política de privacidad</a></li>
                <li><a href="<?php echo e(BASE_URL); ?>/uso_admin" target="_blank" rel="noopener" aria-label="Panel de administración">Administración</a></li>
            </ul>
        </section>

        <section>
            <h2 class="site-footer__title">Síguenos</h2>
            <ul class="site-footer__social" aria-label="Redes sociales">
                <li>
                    <a href="https://www.facebook.com/groups/USOOEST" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="site-footer__social-link">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-5 2.2-5 5v3H6v4h3v4h4v-4h3.2l.8-4H13V9c0-.6.4-1 1-1z"></path></svg>
                    </a>
                </li>
                <li>
                    <a href="https://www.instagram.com/uso_oest/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="site-footer__social-link">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"></rect><circle cx="12" cy="12" r="3.5"></circle><circle cx="17" cy="7" r="1"></circle></svg>
                    </a>
                </li>
                <li>
                    <a href="mailto:uso-oest@hotmail.es" aria-label="Correo electrónico" class="site-footer__social-link">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"></rect><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    </a>
                </li>
            </ul>
        </section>

        <section>
            <h2 class="site-footer__title">USO OEST</h2>
            <p class="site-footer__text">uso-oest@hotmail.es</p>
        </section>
    </div>

    <div class="site-footer__bottom">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> USO OEST. Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        var BASE_URL = <?php echo json_encode(BASE_URL, JSON_UNESCAPED_SLASHES); ?>;
        var BASE_ASSET = <?php echo json_encode(BASE_URL . '/public/assets', JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <?php
    $jsV = static function (string $file): string {
        $path = APP_PATH . '/../public/assets/js/' . $file;
        $ts   = @filemtime($path) ?: date('YmdHis');
        return (string)$ts;
    };
    ?>
    <script src="<?php echo e(asset('js/jquery.min.js')); ?>?v=<?php echo $jsV('jquery.min.js'); ?>"></script>
    <script src="<?php echo e(asset('js/menu.js')); ?>?v=<?php echo $jsV('menu.js'); ?>"></script>
    <script src="<?php echo e(asset('js/main.js')); ?>?v=<?php echo $jsV('main.js'); ?>"></script>
    <script src="<?php echo e(asset('js/assistant.js')); ?>?v=<?php echo $jsV('assistant.js'); ?>"></script>
    <script src="<?php echo e(asset('js/calendars.js')); ?>?v=<?php echo $jsV('calendars.js'); ?>"></script>
    <script src="<?php echo e(asset('js/contact.js')); ?>?v=<?php echo $jsV('contact.js'); ?>"></script>
    <script src="<?php echo e(asset('js/webpush.js')); ?>?v=<?php echo $jsV('webpush.js'); ?>"></script>
</footer>

<?php if (($page ?? '') !== 'asistente'): ?>
    <a class="assistant-float" href="<?php echo e(url_for('asistente')); ?>" aria-label="Abrir Asistente Virtual USO">
        <span class="assistant-float__label">¡Hola! Preguntame lo que necesites</span>
        <img class="assistant-float__image" src="<?php echo e(asset('img/uso-assistant-bot.svg')); ?>" alt="Asistente Virtual USO">
    </a>
<?php endif; ?>
