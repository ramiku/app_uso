<?php
declare(strict_types=1);

/**
 * FcmSender — Envía notificaciones push mediante Firebase Cloud Messaging HTTP v1.
 *
 * Uso mínimo:
 *   $fcm = new FcmSender();
 *   $result = $fcm->enviarATtoken('TOKEN_FCM', 'Título', 'Mensaje', 'https://uso-oest.es/', 'aviso');
 *
 * La service account de Firebase debe estar fuera del document root.
 * Ruta por defecto: C:/xampp/private/firebase-service-account.json
 * Se puede sobreescribir con la variable de entorno FIREBASE_SERVICE_ACCOUNT_PATH
 * o pasando la ruta al constructor.
 */
class FcmSender
{
    /** @var string Ruta absoluta al JSON de la service account */
    private string $serviceAccountPath;

    /** @var string ID del proyecto Firebase */
    private string $projectId;

    /** @var string|null Token OAuth2 cacheado en memoria */
    private ?string $cachedAccessToken = null;

    /** @var int Expiración del token cacheado (timestamp UNIX) */
    private int $tokenExpiresAt = 0;

    // Códigos de error FCM que indican token de dispositivo inválido/no registrado
    private const INVALID_TOKEN_CODES = [
        'UNREGISTERED',
        'INVALID_ARGUMENT',
    ];

    public function __construct(?string $serviceAccountPath = null)
    {
        if ($serviceAccountPath !== null && $serviceAccountPath !== '') {
            $this->serviceAccountPath = $serviceAccountPath;
        } elseif (defined('FIREBASE_SERVICE_ACCOUNT_PATH') && FIREBASE_SERVICE_ACCOUNT_PATH !== '') {
            // Constante definida en app/config/config.php (entorno local o producción)
            $this->serviceAccountPath = FIREBASE_SERVICE_ACCOUNT_PATH;
        } else {
            // Fallback: variable de entorno directa (sin config.php cargado)
            $envPath = trim((string)(getenv('FIREBASE_SERVICE_ACCOUNT_PATH') ?: ''));
            $this->serviceAccountPath = $envPath !== ''
                ? $envPath
                : 'C:/xampp/private/firebase-service-account.json';
        }

        $this->projectId = $this->resolveProjectId();
    }

    // -----------------------------------------------------------------------
    // API pública
    // -----------------------------------------------------------------------

    /**
     * Envía una notificación a un token FCM concreto.
     *
     * @return array{ok: bool, error?: string, errorCode?: string}
     */
    public function enviarATtoken(
        string $tokenFcm,
        string $titulo,
        string $mensaje,
        ?string $url  = null,
        string  $tipo = 'aviso'
    ): array {
        if ($tokenFcm === '' || $titulo === '' || $mensaje === '') {
            return ['ok' => false, 'error' => 'Parámetros requeridos vacíos'];
        }

        // Validar URL si se proporciona
        if ($url !== null && $url !== '') {
            if (!$this->esUrlSegura($url)) {
                $url = null;
            }
        }

        try {
            $accessToken = $this->obtenerAccessToken();
        } catch (Throwable $e) {
            error_log('[FcmSender] Error obteniendo access token: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Error de autenticación Firebase'];
        }

        $payload = $this->construirPayload($tokenFcm, $titulo, $mensaje, $url, $tipo);
        $endpoint = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($this->projectId) . '/messages:send';

        $ch = curl_init($endpoint);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'No se pudo inicializar cURL'];
        }

        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonBody === false) {
            return ['ok' => false, 'error' => 'Error serializando payload'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json; charset=UTF-8',
                'Accept: application/json',
            ],
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            error_log('[FcmSender] cURL error: ' . $curlError);
            return ['ok' => false, 'error' => 'Error de red'];
        }

        $response = json_decode((string)$responseBody, true);

        if ($httpCode === 200) {
            return ['ok' => true];
        }

        // Error de FCM — extraer código de error
        $errorCode = '';
        if (is_array($response)) {
            $errorCode = (string)($response['error']['details'][0]['errorCode']
                ?? $response['error']['status']
                ?? '');
        }

        error_log(sprintf(
            '[FcmSender] HTTP %d | errorCode: %s | body: %s',
            $httpCode,
            $errorCode,
            substr((string)$responseBody, 0, 500)
        ));

        return [
            'ok'        => false,
            'error'     => 'Error FCM HTTP ' . $httpCode,
            'errorCode' => $errorCode,
        ];
    }

    /**
     * Devuelve true si el errorCode de FCM indica que el token es inválido/no registrado.
     */
    public function esTokenInvalido(string $errorCode): bool
    {
        return in_array(strtoupper($errorCode), self::INVALID_TOKEN_CODES, true);
    }

    // -----------------------------------------------------------------------
    // Privados
    // -----------------------------------------------------------------------

    private function resolveProjectId(): string
    {
        $data = $this->leerServiceAccount();
        $id = trim((string)($data['project_id'] ?? ''));
        if ($id === '') {
            throw new RuntimeException('project_id no encontrado en la service account.');
        }
        return $id;
    }

    private function leerServiceAccount(): array
    {
        if (!is_file($this->serviceAccountPath)) {
            throw new RuntimeException(
                'firebase-service-account.json no encontrado en: ' . $this->serviceAccountPath
            );
        }

        $raw = file_get_contents($this->serviceAccountPath);
        if ($raw === false) {
            throw new RuntimeException('No se pudo leer la service account.');
        }

        // Limpiar BOM UTF-8 y normalizar saltos de línea (FTP ASCII corrupts \n → \r\n)
        $raw = ltrim($raw, "\xEF\xBB\xBF");
        $raw = str_replace("\r\n", "\n", $raw);

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('El archivo de service account no contiene JSON válido.');
        }

        return $data;
    }

    /**
     * Genera un JWT firmado con RS256 y lo intercambia por un access token OAuth2.
     * Se cachea en memoria durante la vida de la petición.
     */
    private function obtenerAccessToken(): string
    {
        if ($this->cachedAccessToken !== null && time() < $this->tokenExpiresAt - 60) {
            return $this->cachedAccessToken;
        }

        $sa = $this->leerServiceAccount();

        $clientEmail = (string)($sa['client_email'] ?? '');
        $privateKey  = (string)($sa['private_key']  ?? '');
        $tokenUri    = (string)($sa['token_uri']    ?? 'https://oauth2.googleapis.com/token');

        if ($clientEmail === '' || $privateKey === '') {
            throw new RuntimeException('client_email o private_key ausentes en la service account.');
        }

        $now = time();
        $exp = $now + 3600;

        $header  = $this->base64UrlEncode((string)json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claimset = $this->base64UrlEncode((string)json_encode([
            'iss'   => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $tokenUri,
            'iat'   => $now,
            'exp'   => $exp,
        ]));

        $signingInput = $header . '.' . $claimset;

        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false) {
            throw new RuntimeException('No se pudo cargar la clave privada de la service account.');
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Error firmando el JWT.');
        }

        $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

        // Intercambiar JWT por access token
        $ch = curl_init($tokenUri);
        if ($ch === false) {
            throw new RuntimeException('No se pudo inicializar cURL para el token.');
        }

        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $responseBody = curl_exec($ch);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new RuntimeException('cURL error obteniendo access token: ' . $curlError);
        }

        $tokenData = json_decode((string)$responseBody, true);
        if (!is_array($tokenData) || empty($tokenData['access_token'])) {
            throw new RuntimeException(
                'Respuesta inválida al obtener access token: ' . substr((string)$responseBody, 0, 300)
            );
        }

        $this->cachedAccessToken = (string)$tokenData['access_token'];
        $this->tokenExpiresAt    = $now + (int)($tokenData['expires_in'] ?? 3600);

        return $this->cachedAccessToken;
    }

    private function construirPayload(
        string  $tokenFcm,
        string  $titulo,
        string  $mensaje,
        ?string $url,
        string  $tipo
    ): array {
        $data = [
            'title' => $titulo,
            'body'  => $mensaje,
            'url'   => $url ?? 'https://uso-oest.es/',
            'tipo'  => $tipo,
        ];

        return [
            'message' => [
                'token'        => $tokenFcm,
                'notification' => [
                    'title' => $titulo,
                    'body'  => $mensaje,
                ],
                'data'         => $data,
                'android'      => [
                    'priority'     => 'high',
                    'notification' => [
                        'channel_id' => 'canal_general',
                    ],
                ],
            ],
        ];
    }

    /**
     * Valida que la URL pertenece al dominio autorizado (uso-oest.es).
     */
    private function esUrlSegura(string $url): bool
    {
        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return false;
        }

        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        $host   = strtolower((string)($parsed['host']   ?? ''));

        if ($scheme !== 'https') {
            return false;
        }

        return $host === 'uso-oest.es' || str_ends_with($host, '.uso-oest.es');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
