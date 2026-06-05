<?php
declare(strict_types=1);

/**
 * push_helpers.php — Funciones de alto nivel para envío de notificaciones push.
 *
 * Depende de FcmSender.php y de las constantes DB_* definidas en config.php.
 * Incluir este archivo después de config.php y FcmSender.php.
 */

require_once __DIR__ . '/FcmSender.php';

// ---------------------------------------------------------------------------
// Conexión PDO compartida (reutiliza la misma instancia si ya existe)
// ---------------------------------------------------------------------------
function push_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

// ---------------------------------------------------------------------------
// enviarPushUsuario()
// ---------------------------------------------------------------------------

/**
 * Envía una notificación push a todos los tokens activos de un usuario.
 *
 * @param string      $idUsuario  Identificador del usuario en app_push_tokens
 * @param string      $titulo     Título de la notificación
 * @param string      $mensaje    Cuerpo de la notificación
 * @param string|null $url        URL opcional que abrirá la app al pulsar
 * @param string      $tipo       Tipo de notificación (aviso, noticia, etc.)
 *
 * @return array{ok: bool, enviados: int, fallidos: int, tokens_desactivados: int}
 */
function enviarPushUsuario(
    string  $idUsuario,
    string  $titulo,
    string  $mensaje,
    ?string $url  = null,
    string  $tipo = 'aviso'
): array {
    $resultado = ['ok' => false, 'enviados' => 0, 'fallidos' => 0, 'tokens_desactivados' => 0];

    if ($idUsuario === '' || $titulo === '' || $mensaje === '') {
        error_log('[push_helpers] enviarPushUsuario: parámetros requeridos vacíos');
        return $resultado;
    }

    // Buscar tokens activos del usuario
    try {
        $stmt = push_db()->prepare(
            'SELECT id, token_fcm
             FROM app_push_tokens
             WHERE id_usuario = :id_usuario AND activo = 1'
        );
        $stmt->execute(['id_usuario' => $idUsuario]);
        $tokens = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('[push_helpers] Error consultando tokens: ' . $e->getMessage());
        return $resultado;
    }

    if (empty($tokens)) {
        $resultado['ok'] = true; // No hay tokens, nada que hacer
        return $resultado;
    }

    $fcm = new FcmSender();

    foreach ($tokens as $row) {
        $tokenId  = (int)$row['id'];
        $tokenFcm = (string)$row['token_fcm'];

        $res = $fcm->enviarATtoken($tokenFcm, $titulo, $mensaje, $url, $tipo);

        if ($res['ok']) {
            $resultado['enviados']++;
        } else {
            $resultado['fallidos']++;
            $errorCode = (string)($res['errorCode'] ?? '');

            // Si el token ya no es válido, marcarlo como inactivo
            if ($errorCode !== '' && $fcm->esTokenInvalido($errorCode)) {
                try {
                    push_db()->prepare(
                        'UPDATE app_push_tokens SET activo = 0 WHERE id = :id LIMIT 1'
                    )->execute(['id' => $tokenId]);
                    $resultado['tokens_desactivados']++;
                    error_log(sprintf(
                        '[push_helpers] Token desactivado (id=%d, code=%s)',
                        $tokenId,
                        $errorCode
                    ));
                } catch (Throwable $e) {
                    error_log('[push_helpers] Error desactivando token: ' . $e->getMessage());
                }
            } else {
                error_log(sprintf(
                    '[push_helpers] Error enviando notificación a token id=%d: %s (code=%s)',
                    $tokenId,
                    $res['error'] ?? '',
                    $errorCode
                ));
            }
        }
    }

    $resultado['ok'] = true;
    return $resultado;
}

/**
 * Devuelve todos los id_usuario distintos que tienen tokens activos.
 * Útil para envíos masivos (broadcast).
 *
 * @return string[]
 */
function push_obtener_usuarios_activos(): array
{
    try {
        $stmt = push_db()->query(
            'SELECT DISTINCT id_usuario FROM app_push_tokens WHERE activo = 1 ORDER BY id_usuario'
        );
        $rows = $stmt->fetchAll();
        return array_column(is_array($rows) ? $rows : [], 'id_usuario');
    } catch (Throwable $e) {
        error_log('[push_helpers] Error obteniendo usuarios activos: ' . $e->getMessage());
        return [];
    }
}

/**
 * Envía una notificación push a TODOS los usuarios con tokens activos.
 *
 * @return array{ok: bool, usuarios: int, enviados: int, fallidos: int, tokens_desactivados: int}
 */
function enviarPushBroadcast(
    string  $titulo,
    string  $mensaje,
    ?string $url  = null,
    string  $tipo = 'aviso'
): array {
    $usuarios  = push_obtener_usuarios_activos();
    $totales   = ['ok' => true, 'usuarios' => 0, 'enviados' => 0, 'fallidos' => 0, 'tokens_desactivados' => 0];

    foreach ($usuarios as $idUsuario) {
        $res = enviarPushUsuario($idUsuario, $titulo, $mensaje, $url, $tipo);
        if ($res['ok']) {
            $totales['usuarios']++;
            $totales['enviados']            += $res['enviados'];
            $totales['fallidos']            += $res['fallidos'];
            $totales['tokens_desactivados'] += $res['tokens_desactivados'];
        }
    }

    return $totales;
}

/**
 * Envía una notificación a TODOS los canales activos:
 *   - FCM (app Android)
 *   - Web Push VAPID (navegadores suscritos)
 *
 * @return array{
 *   fcm:  array{ok: bool, usuarios: int, enviados: int, fallidos: int},
 *   web:  array{sent: int, failed: int, expired: int},
 *   total_enviados: int,
 *   total_fallidos: int
 * }
 */
function enviarNotificacionUnificada(
    string  $titulo,
    string  $mensaje,
    ?string $url  = null,
    string  $tipo = 'aviso'
): array {
    // ── FCM ──────────────────────────────────────────────────────────────
    $fcmResult = enviarPushBroadcast($titulo, $mensaje, $url, $tipo);

    // ── Web Push ─────────────────────────────────────────────────────────
    $webResult = ['sent' => 0, 'failed' => 0, 'expired' => 0];

    $autoloadPaths = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ];
    $autoloaded = false;
    foreach ($autoloadPaths as $ap) {
        if (file_exists($ap)) {
            require_once $ap;
            $autoloaded = true;
            break;
        }
    }

    if ($autoloaded && class_exists('\Minishlink\WebPush\WebPush')) {
        $senderFile = __DIR__ . '/../lib/WebPushSender.php';
        if (file_exists($senderFile)) {
            require_once $senderFile;
            try {
                $sender    = new WebPushSender();
                $webResult = $sender->sendToAll($titulo, $mensaje, $url ?? '');
            } catch (Throwable $e) {
                error_log('[push_helpers] Web Push error: ' . $e->getMessage());
            }
        }
    }

    return [
        'fcm'            => $fcmResult,
        'web'            => $webResult,
        'total_enviados' => $fcmResult['enviados'] + $webResult['sent'],
        'total_fallidos' => $fcmResult['fallidos'] + $webResult['failed'],
    ];
}
