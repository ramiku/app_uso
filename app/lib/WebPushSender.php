<?php
declare(strict_types=1);

/**
 * WebPushSender.php
 * Clase para enviar notificaciones Web Push (VAPID) usando minishlink/web-push.
 *
 * Uso básico:
 *   require_once __DIR__ . '/../../vendor/autoload.php';
 *   $sender = new WebPushSender();
 *   $result = $sender->sendToAll('Título', 'Mensaje', 'https://uso-oest.es/');
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushSender
{
    private WebPush $webPush;
    private \mysqli $db;

    /**
     * @throws RuntimeException si faltan las claves VAPID o la BD.
     */
    public function __construct()
    {
        $publicKey   = defined('WEB_PUSH_VAPID_PUBLIC_KEY')  ? WEB_PUSH_VAPID_PUBLIC_KEY  : '';
        $privateKey  = defined('WEB_PUSH_VAPID_PRIVATE_KEY') ? WEB_PUSH_VAPID_PRIVATE_KEY : '';
        $subject     = defined('WEB_PUSH_VAPID_SUBJECT')     ? WEB_PUSH_VAPID_SUBJECT     : 'mailto:admin@uso-oest.es';

        if ($publicKey === '' || $privateKey === '') {
            throw new RuntimeException('Claves VAPID no configuradas. Define WEB_PUSH_VAPID_PUBLIC_KEY y WEB_PUSH_VAPID_PRIVATE_KEY.');
        }

        $auth = [
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
        $this->webPush->setDefaultOptions([
            'TTL'     => 86400, // 24 horas
            'urgency' => 'normal',
        ]);

        $this->db = $this->conectar();
    }

    /* ── Enviar a todos los suscriptores activos ──────────────────────── */

    /**
     * @return array{sent: int, failed: int, expired: int}
     */
    public function sendToAll(string $titulo, string $mensaje, string $url = ''): array
    {
        return $this->send($titulo, $mensaje, $url, null);
    }

    /* ── Enviar a un usuario concreto ─────────────────────────────────── */

    /**
     * @return array{sent: int, failed: int, expired: int}
     */
    public function sendToUser(string $usuario, string $titulo, string $mensaje, string $url = ''): array
    {
        return $this->send($titulo, $mensaje, $url, $usuario);
    }

    /* ── Lógica interna de envío ──────────────────────────────────────── */

    /**
     * @return array{sent: int, failed: int, expired: int}
     */
    private function send(string $titulo, string $mensaje, string $url, ?string $usuario): array
    {
        $suscripciones = $this->cargarSuscripciones($usuario);

        if ($suscripciones === []) {
            return ['sent' => 0, 'failed' => 0, 'expired' => 0];
        }

        $payload = json_encode([
            'titulo'  => $titulo,
            'mensaje' => $mensaje,
            'url'     => $url !== '' ? $url : 'https://uso-oest.es/',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Encolar
        foreach ($suscripciones as $row) {
            $subscription = Subscription::create([
                'endpoint' => $row['endpoint'],
                'keys'     => [
                    'p256dh' => $row['p256dh'],
                    'auth'   => $row['auth'],
                ],
            ]);
            $this->webPush->queueNotification($subscription, $payload);
        }

        // Enviar
        $sent    = 0;
        $failed  = 0;
        $expired = 0;
        $expiredHashes = [];

        foreach ($this->webPush->flush() as $report) {
            /** @var \Minishlink\WebPush\MessageSentReport $report */
            if ($report->isSuccess()) {
                $sent++;
            } else {
                $failed++;
                $reason = $report->getReason();
                error_log('[WebPush] Envío fallido — endpoint: ' . substr($report->getEndpoint(), 0, 80) . ' — razón: ' . $reason);
                if ($report->isSubscriptionExpired()) {
                    $expired++;
                    $ep = $report->getEndpoint();
                    $expiredHashes[] = hash('sha256', $ep);
                }
            }
        }

        if ($expiredHashes !== []) {
            $this->marcarExpiradas($expiredHashes);
        }

        return ['sent' => $sent, 'failed' => $failed, 'expired' => $expired];
    }

    /* ── BD: cargar suscripciones activas ────────────────────────────── */

    /** @return list<array{endpoint: string, p256dh: string, auth: string}> */
    private function cargarSuscripciones(?string $usuario): array
    {
        if ($usuario !== null) {
            $stmt = mysqli_prepare(
                $this->db,
                'SELECT endpoint, p256dh, auth FROM web_push_subscriptions WHERE activo = 1 AND usuario = ?'
            );
            if (!$stmt) { return []; }
            mysqli_stmt_bind_param($stmt, 's', $usuario);
        } else {
            $stmt = mysqli_prepare(
                $this->db,
                'SELECT endpoint, p256dh, auth FROM web_push_subscriptions WHERE activo = 1'
            );
            if (!$stmt) { return []; }
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows   = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'endpoint' => (string)$row['endpoint'],
                'p256dh'   => (string)$row['p256dh'],
                'auth'     => (string)$row['auth'],
            ];
        }

        mysqli_stmt_close($stmt);
        return $rows;
    }

    /* ── BD: marcar suscripciones expiradas ──────────────────────────── */

    /** @param list<string> $hashes */
    private function marcarExpiradas(array $hashes): void
    {
        foreach ($hashes as $hash) {
            $stmt = mysqli_prepare(
                $this->db,
                'UPDATE web_push_subscriptions SET activo = 0 WHERE endpoint_hash = ?'
            );
            if (!$stmt) { continue; }
            mysqli_stmt_bind_param($stmt, 's', $hash);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    /* ── Conexión MySQL ───────────────────────────────────────────────── */

    private function conectar(): \mysqli
    {
        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $user = defined('DB_USER') ? DB_USER : '';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $name = defined('DB_NAME') ? DB_NAME : '';

        $conn = @mysqli_connect($host, $user, $pass, $name);
        if (!$conn) {
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
        mysqli_set_charset($conn, 'utf8mb4');
        return $conn;
    }

    public function __destruct()
    {
        if (isset($this->db)) {
            @mysqli_close($this->db);
        }
    }
}
