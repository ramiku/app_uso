<?php
declare(strict_types=1);

/**
 * guardar_web_push_subscription.php
 * POST /api/guardar_web_push_subscription.php
 *
 * Guarda o actualiza una suscripción Web Push (VAPID) en MySQL.
 * Solo acepta navegadores reales; el wrapper Android nunca debe llamar a esto.
 */

session_set_cookie_params([
    'lifetime' => 86400,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../config/config.php';

/* ── Solo POST ────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Leer JSON ────────────────────────────────────────────────────────── */
$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody ?: '{}', true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Validar campos obligatorios ──────────────────────────────────────── */
$endpoint = trim((string)($input['endpoint'] ?? ''));
$keys     = is_array($input['keys'] ?? null) ? $input['keys'] : [];
$p256dh   = trim((string)($keys['p256dh'] ?? ''));
$auth     = trim((string)($keys['auth']   ?? ''));

if ($endpoint === '' || $p256dh === '' || $auth === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Suscripción incompleta'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Límites de seguridad
if (strlen($endpoint) > 2083 || strlen($p256dh) > 255 || strlen($auth) > 255) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Campo demasiado largo'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Metadatos ────────────────────────────────────────────────────────── */
$userAgent   = substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 512);
$plataforma  = 'web';
$endpointHash = hash('sha256', $endpoint);

// Identificar usuario de sesión si lo hay
$usuario = null;
if (isset($_SESSION['uso_admin_user']) && is_array($_SESSION['uso_admin_user'])) {
    $uid = trim((string)($_SESSION['uso_admin_user']['id'] ?? $_SESSION['uso_admin_user']['username'] ?? ''));
    if ($uid !== '') {
        $usuario = 'admin:' . $uid;
    }
}
if ($usuario === null && !empty($_SESSION['push_device_id'])) {
    $usuario = (string)$_SESSION['push_device_id'];
}

/* ── Guardar en MySQL ─────────────────────────────────────────────────── */
$dbHost = defined('DB_HOST') ? DB_HOST : '';
$dbName = defined('DB_NAME') ? DB_NAME : '';
$dbUser = defined('DB_USER') ? DB_USER : '';
$dbPass = defined('DB_PASS') ? DB_PASS : '';

if ($dbHost === '' || $dbName === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Configuración de base de datos no disponible'], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

$sql = <<<'SQL'
    INSERT INTO web_push_subscriptions
        (usuario, endpoint, endpoint_hash, p256dh, auth, user_agent, plataforma, fecha_ultima, activo)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, NOW(), 1)
    ON DUPLICATE KEY UPDATE
        usuario       = VALUES(usuario),
        endpoint      = VALUES(endpoint),
        p256dh        = VALUES(p256dh),
        auth          = VALUES(auth),
        user_agent    = VALUES(user_agent),
        plataforma    = VALUES(plataforma),
        fecha_ultima  = NOW(),
        activo        = 1
SQL;

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    mysqli_close($conn);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error preparando la consulta'], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    'sssssss',
    $usuario,
    $endpoint,
    $endpointHash,
    $p256dh,
    $auth,
    $userAgent,
    $plataforma
);

$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error guardando la suscripción'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
exit;
