<?php
declare(strict_types=1);

/**
 * enviar_web_push.php
 * POST/GET (interno) /api/enviar_web_push.php
 *
 * Endpoint para enviar notificaciones Web Push desde PHP.
 * Protegido con API key interna — no exponer sin autenticación.
 *
 * Parámetros JSON (POST):
 *   - titulo    string   Título de la notificación
 *   - mensaje   string   Cuerpo
 *   - url       string   URL a abrir al hacer clic (opcional)
 *   - usuario   string   Si se indica, envía solo a ese usuario (opcional)
 *   - api_key   string   Clave de autorización interna
 *
 * Respuesta:
 *   { "ok": true, "sent": N, "failed": N, "expired": N }
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../config/config.php';

// ── Cargar vendor autoload ──────────────────────────────────────────────────
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',   // Raíz del proyecto
    __DIR__ . '/../vendor/autoload.php',
];
$autoloaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaded = true;
        break;
    }
}

if (!$autoloaded) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'vendor/autoload.php no encontrado. Instala dependencias con Composer.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../lib/WebPushSender.php';

// ── Solo POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Autenticación por API key o sesión admin ───────────────────────────────
$internalApiKey = defined('WEB_PUSH_INTERNAL_API_KEY') ? WEB_PUSH_INTERNAL_API_KEY : '';

$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody ?: '{}', true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Autenticación: API key en cuerpo o sesión admin activa
$providedKey = trim((string)($input['api_key'] ?? ''));
$isAdminSession = false;

session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_start();
if (isset($_SESSION['uso_admin_user']) && is_array($_SESSION['uso_admin_user'])) {
    $isAdminSession = true;
}

if (!$isAdminSession && ($internalApiKey === '' || $providedKey !== $internalApiKey)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Parámetros del mensaje ─────────────────────────────────────────────────
$titulo  = trim((string)($input['titulo']  ?? ''));
$mensaje = trim((string)($input['mensaje'] ?? ''));
$url     = trim((string)($input['url']     ?? ''));
$usuario = trim((string)($input['usuario'] ?? ''));

if ($titulo === '' || $mensaje === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'titulo y mensaje son obligatorios'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Enviar ─────────────────────────────────────────────────────────────────
try {
    $sender = new WebPushSender();
    if ($usuario !== '') {
        $result = $sender->sendToUser($usuario, $titulo, $mensaje, $url);
    } else {
        $result = $sender->sendToAll($titulo, $mensaje, $url);
    }

    echo json_encode(array_merge(['ok' => true], $result), JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
