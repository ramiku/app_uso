<?php
declare(strict_types=1);

// Iniciar sesión con las mismas opciones que el panel de administración
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

// ---------------------------------------------------------------------------
// Solo POST
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// Obtener id_usuario desde la sesión
// El panel admin guarda el usuario logado en $_SESSION['uso_admin_user'].
// Para usuarios de la app sin cuenta de admin se genera un id anónimo
// persistido en sesión, de modo que todos los trabajadores puedan recibir
// notificaciones aunque no tengan acceso al panel.
// ---------------------------------------------------------------------------
const ADMIN_SESSION_KEY_PUSH = 'uso_admin_user';
const ANON_DEVICE_KEY        = 'push_device_id';

$idUsuario = '';

if (isset($_SESSION[ADMIN_SESSION_KEY_PUSH]) && is_array($_SESSION[ADMIN_SESSION_KEY_PUSH])) {
    // Usuario con cuenta de administrador logado
    $adminData = $_SESSION[ADMIN_SESSION_KEY_PUSH];
    $idUsuario = 'admin:' . trim((string)($adminData['id'] ?? $adminData['username'] ?? ''));
}

if ($idUsuario === '') {
    // Usuario no logado como admin: crear / reutilizar un id de dispositivo anónimo
    if (empty($_SESSION[ANON_DEVICE_KEY])) {
        $_SESSION[ANON_DEVICE_KEY] = 'device:' . bin2hex(random_bytes(16));
    }
    $idUsuario = (string)$_SESSION[ANON_DEVICE_KEY];
}

if ($idUsuario === '') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// Leer y validar cuerpo JSON
// ---------------------------------------------------------------------------
$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody ?: '{}', true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenFcm  = trim((string)($input['token_fcm']  ?? ''));
$plataforma = trim((string)($input['plataforma'] ?? 'android'));

if ($tokenFcm === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Token vacío'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar longitud razonable del token FCM (evitar inyecciones enormes)
if (strlen($tokenFcm) > 255) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Token inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Plataformas permitidas
$plataformasPermitidas = ['android', 'ios', 'web'];
if (!in_array($plataforma, $plataformasPermitidas, true)) {
    $plataforma = 'android';
}

$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);

// ---------------------------------------------------------------------------
// Guardar en MySQL con prepared statement + ON DUPLICATE KEY UPDATE
// ---------------------------------------------------------------------------
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Crear tabla si no existe (primera vez en producción)
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS app_push_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario VARCHAR(100) NOT NULL,
            token_fcm VARCHAR(255) NOT NULL,
            plataforma VARCHAR(20) DEFAULT 'android',
            activo TINYINT(1) DEFAULT 1,
            user_agent TEXT NULL,
            fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_token (token_fcm),
            KEY idx_usuario (id_usuario),
            KEY idx_activo (activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $stmt = $pdo->prepare(
        'INSERT INTO app_push_tokens
            (id_usuario, token_fcm, plataforma, activo, user_agent)
         VALUES
            (:id_usuario, :token_fcm, :plataforma, 1, :user_agent)
         ON DUPLICATE KEY UPDATE
            id_usuario  = VALUES(id_usuario),
            plataforma  = VALUES(plataforma),
            activo      = 1,
            user_agent  = VALUES(user_agent),
            fecha_ultima_actualizacion = NOW()'
    );

    $stmt->execute([
        'id_usuario' => $idUsuario,
        'token_fcm'  => $tokenFcm,
        'plataforma' => $plataforma,
        'user_agent' => $userAgent,
    ]);

    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[guardar_token_push] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
}