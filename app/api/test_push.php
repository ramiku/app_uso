<?php
declare(strict_types=1);

/**
 * test_push.php — Endpoint protegido para probar el envío de notificaciones push.
 *
 * Solo accesible para el usuario administrador logado en el panel USO
 * o desde localhost/127.0.0.1 en entorno de desarrollo.
 *
 * Ejemplo de llamada (POST JSON):
 *   {
 *     "titulo": "Prueba USO OEST",
 *     "mensaje": "Notificación push funcionando correctamente",
 *     "url": "https://uso-oest.es/"
 *   }
 *
 * Respuesta:
 *   { "ok": true, "enviados": 1, "fallidos": 0, "tokens_desactivados": 0 }
 */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/push_helpers.php';

// ---------------------------------------------------------------------------
// Control de acceso: solo admin o localhost
// ---------------------------------------------------------------------------
$isLocalhost = in_array(
    $_SERVER['REMOTE_ADDR'] ?? '',
    ['127.0.0.1', '::1', 'localhost'],
    true
);

$adminData  = $_SESSION['uso_admin_user'] ?? null;
$isAdmin    = is_array($adminData) && isset($adminData['id']);

if (!$isAdmin && !$isLocalhost) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// Método: POST normal, o GET desde navegador con sesión admin (prueba rápida)
// ---------------------------------------------------------------------------
$isGetPreview = $_SERVER['REQUEST_METHOD'] === 'GET' && $isAdmin;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$isGetPreview) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido. Usa POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// Leer cuerpo JSON (POST) o parámetros GET (prueba desde navegador)
// ---------------------------------------------------------------------------
if ($isGetPreview) {
    $input = [
        'titulo'  => (string)($_GET['titulo']  ?? 'Prueba USO OEST'),
        'mensaje' => (string)($_GET['mensaje'] ?? 'Notificación push funcionando correctamente'),
        'url'     => (string)($_GET['url']     ?? 'https://uso-oest.es/'),
        'tipo'    => (string)($_GET['tipo']    ?? 'aviso'),
    ];
} else {
    $rawBody = file_get_contents('php://input');
    $input   = json_decode($rawBody ?: '{}', true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$titulo  = trim((string)($input['titulo']  ?? 'Prueba USO OEST'));
$mensaje = trim((string)($input['mensaje'] ?? 'Notificación push funcionando correctamente'));
$url     = trim((string)($input['url']     ?? 'https://uso-oest.es/'));
$tipo    = trim((string)($input['tipo']    ?? 'aviso'));

// Validar que la URL pertenece al dominio autorizado
if ($url !== '') {
    $parsed = parse_url($url);
    $scheme = strtolower((string)($parsed['scheme'] ?? ''));
    $host   = strtolower((string)($parsed['host']   ?? ''));
    $dominioValido = ($host === 'uso-oest.es' || str_ends_with($host, '.uso-oest.es'));
    if (!$dominioValido || $scheme !== 'https') {
        $url = 'https://uso-oest.es/';
    }
}

// ---------------------------------------------------------------------------
// Enviar — siempre broadcast a todos los tokens activos.
// Los tokens de la app Android se almacenan con id de dispositivo anónimo
// (device:...), no con el id de admin, por lo que el broadcast es la única
// forma fiable de alcanzar todos los dispositivos registrados.
// ---------------------------------------------------------------------------
// Diagnóstico previo: verificar que la service account es accesible
$saPath = defined('FIREBASE_SERVICE_ACCOUNT_PATH') ? FIREBASE_SERVICE_ACCOUNT_PATH : '(no definida)';
if (!is_file($saPath)) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Service account no encontrada en: ' . $saPath,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Diagnóstico del contenido del archivo
$rawSa = file_get_contents($saPath);
if ($rawSa === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo leer el archivo (permisos?)'], JSON_UNESCAPED_UNICODE);
    exit;
}
// Limpiar BOM UTF-8 si existe
$rawSa = ltrim($rawSa, "\xEF\xBB\xBF");
// Normalizar saltos de línea (FTP ASCII convierte \n a \r\n en algunos clientes)
$rawSa = str_replace("\r\n", "\n", $rawSa);
$decoded = json_decode($rawSa, true);
if (!is_array($decoded)) {
    http_response_code(500);
    echo json_encode([
        'ok'            => false,
        'error'         => 'JSON inválido en service account',
        'json_error'    => json_last_error_msg(),
        'file_size'     => strlen($rawSa),
        'primeros_bytes' => bin2hex(substr($rawSa, 0, 6)),
        'inicio'        => substr($rawSa, 0, 80),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $resultado = enviarPushBroadcast($titulo, $mensaje, $url, $tipo);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[test_push] Error: ' . $e->getMessage());
    http_response_code(500);
    // Admin ve el error real para poder diagnosticarlo
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
