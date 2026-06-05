<?php
declare(strict_types=1);

define('APP_PATH', __DIR__ . '/..');

// ── Variables de configuración externas (API keys y credenciales) ───────────
// Fichero fuera del document root, en la misma carpeta que firebase-service-account.json.
// - Local XAMPP (Windows): C:/xampp/private/config_variables.php
// - Producción Plesk:      /home/customer/www/uso-oest.es/private/config_variables.php
(static function (): void {
    $path = DIRECTORY_SEPARATOR === '\\'
        ? 'C:/xampp/private/config_variables.php'
        : '/home/customer/www/uso-oest.es/private/config_variables.php';
    if (is_file($path)) {
        require $path;
    }
})();

// BASE_URL: ruta base del sitio (vacía si está en el dominio raíz, /webuso si en subcarpeta).
// Prioridad: variable de entorno → detección automática por sistema de archivos.
$envBaseUrl = getenv('BASE_URL');
if ($envBaseUrl !== false) {
    // Configurado explícitamente vía variable de entorno (producción)
    $rawBaseUrl = trim((string)$envBaseUrl);
} elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
    // Detección automática: útil en desarrollo local (XAMPP en subcarpeta /webuso)
    // config.php está en {project_root}/app/config/, así que subimos 2 niveles para obtener project_root
    $projectRoot = rtrim(str_replace('\\', '/', dirname(dirname(__DIR__))), '/');
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
        $rawBaseUrl = (string)substr($projectRoot, strlen($docRoot));
    } else {
        $rawBaseUrl = '/';
    }
} else {
    $rawBaseUrl = '/';
}

$normalizedBaseUrl = '/' . trim($rawBaseUrl, '/');
if ($normalizedBaseUrl === '/') {
    $normalizedBaseUrl = '';
}

define('BASE_URL', $normalizedBaseUrl);
define('SITE_NAME', 'USO OEST');



// Ruta al JSON de la service account de Firebase (fuera del document root).
// - Local XAMPP (Windows): C:/xampp/private/firebase-service-account.json
// - Producción Plesk:      /home/customer/www/uso-oest.es/private/firebase-service-account.json
// Se puede sobreescribir definiendo la variable de entorno FIREBASE_SERVICE_ACCOUNT_PATH.

$firebase_json = realpath(__DIR__ . '/../../private/firebase-service-account.json');


define('FIREBASE_SERVICE_ACCOUNT_PATH', (function (): string {
    $fromEnv = trim((string)(getenv('FIREBASE_SERVICE_ACCOUNT_PATH') ?: ''));
    if ($fromEnv !== '') {
        return $fromEnv;
    }
    // Detección automática: Windows = XAMPP local, Linux = servidor Plesk
    return DIRECTORY_SEPARATOR === '\\'
        ? 'C:/xampp/private/firebase-service-account.json'
        : '/home/customer/www/uso-oest.es/private/firebase-service-account.json';
})());

// ── Web Push VAPID ──────────────────────────────────────────────────────────
// Claves leídas desde un JSON fuera del document root (igual que Firebase).
// Generarlas una única vez con: php private/generate_vapid_keys.php
// - Local XAMPP (Windows): C:/xampp/private/vapid-keys.json
// - Producción:            /home/customer/www/uso-oest.es/private/vapid-keys.json
// Se puede sobreescribir con la variable de entorno VAPID_KEYS_PATH.

define('VAPID_KEYS_PATH', (function (): string {
    $fromEnv = trim((string)(getenv('VAPID_KEYS_PATH') ?: ''));
    if ($fromEnv !== '') {
        return $fromEnv;
    }
    return DIRECTORY_SEPARATOR === '\\'
        ? 'C:/xampp/private/vapid-keys.json'
        : '/home/customer/www/uso-oest.es/private/vapid-keys.json';
})());

(function (): void {
    $path = VAPID_KEYS_PATH;
    if (!file_exists($path)) {
        define('WEB_PUSH_VAPID_PUBLIC_KEY',  '');
        define('WEB_PUSH_VAPID_PRIVATE_KEY', '');
        define('WEB_PUSH_VAPID_SUBJECT',     'mailto:admin@uso-oest.es');
        return;
    }
    $data = json_decode(file_get_contents($path), true) ?? [];
    define('WEB_PUSH_VAPID_PUBLIC_KEY',  trim((string)($data['public_key']  ?? '')));
    define('WEB_PUSH_VAPID_PRIVATE_KEY', trim((string)($data['private_key'] ?? '')));
    define('WEB_PUSH_VAPID_SUBJECT',     trim((string)($data['subject']     ?? 'mailto:admin@uso-oest.es')));
})();

// Clave interna para el endpoint /api/enviar_web_push.php (si se llama sin sesión admin)
define('WEB_PUSH_INTERNAL_API_KEY',  trim((string)(getenv('WEB_PUSH_INTERNAL_API_KEY')  ?: '')));

return [
    'site_name' => SITE_NAME,
    'default_title' => 'Actualidad y tendencias',
    'default_description' => 'Portal de noticias con destacados, actualidad y análisis.',
    'timezone' => 'Europe/Madrid',
];
