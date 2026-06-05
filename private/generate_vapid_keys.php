<?php
declare(strict_types=1);

/**
 * generate_vapid_keys.php
 * Script de un solo uso para generar las claves VAPID.
 *
 * Ejecutar UNA SOLA VEZ desde la CLI:
 *   php private/generate_vapid_keys.php
 *
 * Copiar los valores impresos en las variables de entorno del servidor.
 * NUNCA versionar las claves privadas.
 */

// Buscar autoload
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];
$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    fwrite(STDERR, "ERROR: vendor/autoload.php no encontrado.\nEjecuta: composer require minishlink/web-push\n");
    exit(1);
}

if (!class_exists('\Minishlink\WebPush\VAPID')) {
    fwrite(STDERR, "ERROR: minishlink/web-push no instalado.\nEjecuta: composer require minishlink/web-push\n");
    exit(1);
}

$keys = \Minishlink\WebPush\VAPID::createVapidKeys();

echo PHP_EOL;
echo '╔══════════════════════════════════════════════════════════════╗' . PHP_EOL;
echo '║           CLAVES VAPID GENERADAS — GUARDAR CON CUIDADO       ║' . PHP_EOL;
echo '╚══════════════════════════════════════════════════════════════╝' . PHP_EOL;
echo PHP_EOL;
echo 'Añadir como variables de entorno en el servidor (NO en código):' . PHP_EOL;
echo PHP_EOL;
echo 'WEB_PUSH_VAPID_PUBLIC_KEY='  . $keys['publicKey']  . PHP_EOL;
echo 'WEB_PUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;
echo 'WEB_PUSH_VAPID_SUBJECT=mailto:admin@uso-oest.es'    . PHP_EOL;
echo PHP_EOL;
echo '──────────────────────────────────────────────────────────────' . PHP_EOL;
echo 'La clave PÚBLICA se incluye en JS (head.php como window.WEB_PUSH_PUBLIC_KEY).' . PHP_EOL;
echo 'La clave PRIVADA NUNCA debe aparecer en JavaScript ni en code repositorio.' . PHP_EOL;
echo PHP_EOL;
