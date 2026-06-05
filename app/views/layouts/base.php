<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="es">
<?php require APP_PATH . '/views/partials/head.php'; ?>
<body>
    <?php require APP_PATH . '/views/partials/header.php'; ?>
    <main id="contenido" class="site-main" tabindex="-1">
        <?php require $viewFile; ?>
    </main>
    <!-- Banner Web Push: solo visible en navegadores compatibles, ocultado en wrapper Android por JS -->
    <div id="webpush-banner" class="webpush-banner" role="region" aria-label="Notificaciones" style="display:none">
        <div class="webpush-banner__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        </div>
        <p class="webpush-banner__text">Activa las notificaciones para recibir avisos importantes.</p>
        <button id="webpush-btn-activar" class="webpush-banner__btn webpush-banner__btn--primary" type="button">
            Activar
        </button>
        <button id="webpush-btn-cerrar" class="webpush-banner__btn webpush-banner__btn--close" type="button" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <p id="webpush-denied-msg" class="webpush-denied" style="display:none"></p>
    <p id="webpush-quiet-msg" class="webpush-denied webpush-quiet" style="display:none">
        <strong>¿No apareció ningún cuadro de permiso?</strong><br>
        Chrome lo reemplaza por un icono de campana <span class="webpush-bell-icon" aria-hidden="true">🔔</span> en la barra de direcciones (arriba a la derecha). Pulsa en él y selecciona <em>"Permitir"</em>.
    </p>
    <?php require APP_PATH . '/views/partials/footer.php'; ?>
</body>
</html>
