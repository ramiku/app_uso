<?php
declare(strict_types=1);
/** @var array $meta */
$meta ??= [];
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo e(SITE_NAME); ?></title>
    <meta name="description" content="<?php echo e($meta['description']); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('img/icon-192.png')); ?>?v=20260301">
    <link rel="shortcut icon" type="image/png" href="<?php echo e(asset('img/icon-192.png')); ?>?v=20260301">
    <link rel="apple-touch-icon" href="<?php echo e(asset('img/icon-192.png')); ?>?v=20260301">
    <?php
    $stylesVersion = @filemtime(APP_PATH . '/../public/assets/css/styles.css');
    $responsiveVersion = @filemtime(APP_PATH . '/../public/assets/css/responsive.css');
    ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/styles.css')); ?>?v=<?php echo e((string)($stylesVersion ?: date('YmdHis'))); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/responsive.css')); ?>?v=<?php echo e((string)($responsiveVersion ?: date('YmdHis'))); ?>">
    <!-- Función global para recibir token FCM desde la app Android (WebView).
         Se define en <head> para garantizar que esté disponible antes de que
         Android llame a window.recibirTokenPushAndroid(). -->
    <script>
    window.recibirTokenPushAndroid = function(token) {
        if (!token || typeof token !== 'string' || token.trim() === '') {
            console.warn('recibirTokenPushAndroid: token vacío o inválido');
            return;
        }
        console.log('Token FCM recibido desde Android:', token);

        fetch('/api/guardar_token_push.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ token_fcm: token.trim(), plataforma: 'android' })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) { console.log('Respuesta guardar token FCM: ' + JSON.stringify(data)); })
        .catch(function(error) { console.error('Error guardando token FCM:', error); });
    };
    // Clave pública VAPID para Web Push (no es secreta).
    window.WEB_PUSH_PUBLIC_KEY = <?php echo json_encode((string)(defined('WEB_PUSH_VAPID_PUBLIC_KEY') ? WEB_PUSH_VAPID_PUBLIC_KEY : (getenv('WEB_PUSH_VAPID_PUBLIC_KEY') ?: '')), JSON_UNESCAPED_SLASHES); ?>;
    // Base URL del proyecto (vacío en producción, /webuso en local, etc.).
    window.APP_BASE_URL = <?php echo json_encode(defined('BASE_URL') ? BASE_URL : '', JSON_UNESCAPED_SLASHES); ?>;
    </script>
</head>
