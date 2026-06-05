<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../data/contacts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);

$action = trim((string)($payload['action'] ?? 'chat'));
$securityCode = trim((string)($payload['securityCode'] ?? ''));

if ($action === 'auth_status') {
    echo json_encode([
        'success' => true,
        'mode' => 'auth_status',
        'authorized' => is_ai_authorized(),
        'authRequired' => assistant_requires_security_code(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'unlock_ai') {
    echo json_encode(process_unlock_ai($securityCode), JSON_UNESCAPED_UNICODE);
    exit;
}

$message = trim((string)($payload['message'] ?? ''));
$contextMessages = sanitize_context_messages($payload['contextMessages'] ?? null, 5);
if ($message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'La consulta no puede estar vacía',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$normalized = normalize_text($message);
log_consulta_db('usuario_prueba', $message);

$routerResponse = route_assistant_command($normalized);
if ($routerResponse !== null) {
    echo json_encode($routerResponse, JSON_UNESCAPED_UNICODE);
    exit;
}

$authFlowResponse = handle_ai_auth_flow();
if ($authFlowResponse !== null) {
    echo json_encode($authFlowResponse, JSON_UNESCAPED_UNICODE);
    exit;
}

$aiResponse = consulta_openai($message, $contextMessages);
echo json_encode($aiResponse, JSON_UNESCAPED_UNICODE);
exit;

function handle_ai_auth_flow(): ?array
{
    if (!assistant_requires_security_code()) {
        return null;
    }

    if (is_ai_authorized()) {
        return null;
    }

    request_security_code();
    return [
        'success' => true,
        'mode' => 'auth_required',
        'message' => 'El uso del asesor con IA es una funcionalidad que solo está disponible para afiliados. Identifícate introduciendo el código de seguridad.',
        'authRequired' => true,
    ];
}

function process_unlock_ai(string $securityCode): array
{
    if (!assistant_requires_security_code()) {
        return [
            'success' => true,
            'mode' => 'auth_not_required',
            'unlocked' => true,
            'message' => 'La validación por código está desactivada actualmente.',
        ];
    }

    if (is_valid_security_code($securityCode)) {
        authorize_ai_access();
        return [
            'success' => true,
            'mode' => 'auth_unlocked',
            'unlocked' => true,
            'message' => 'Identificación correcta ✅. Se ha desbloqueado el asesor con IA durante 24 horas.',
        ];
    }

    request_security_code();
    return [
        'success' => true,
        'mode' => 'auth_invalid',
        'unlocked' => false,
        'message' => 'La clave es incorrecta ❌',
    ];
}

function route_assistant_command(string $normalized): ?array
{
    $baseChips = get_default_chips();

    if ($normalized === 'he tenido un accidente') {
        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => "Te comparto el protocolo rápido de accidente.\n\nSi lo necesitas, después te muestro contactos de guardia.",
            'ui' => [
                'chips' => [
                    ['label' => 'Teléfonos de guardia', 'prompt' => 'telefonos guardia'],
                    ['label' => 'Correos electrónicos y teléfonos', 'prompt' => 'contacto'],
                ],
                'images' => [
                    ['label' => 'Accidente - paso 1', 'url' => BASE_URL . '/public/assets/img/accidente_1.jpg'],
                    ['label' => 'Accidente - paso 2', 'url' => BASE_URL . '/public/assets/img/accidente_2.jpg'],
                ],
            ],
        ];
    }

    if ($normalized === 'telefonos guardia') {
        $text = "Teléfonos de guardia (pulsa para llamar):\n\n";
        $text .= "• Recepción OEST\n";
        $text .= "• Atención al cliente (Front y BO)\n";
        $text .= "• RSS-Messaging\n";
        $text .= "• Retención\n";
        $text .= "• SS Comerciales\n\n";
        $text .= "También te dejo correos de guardia para fines de semana.";

        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => $text,
            'ui' => [
                'links' => get_guardia_links(),
                'chips' => [
                    ['label' => 'Correos electrónicos y teléfonos', 'prompt' => 'contacto'],
                    ['label' => 'Enlaces', 'prompt' => 'enlaces'],
                ],
            ],
        ];
    }

    if ($normalized === 'contacto') {
        $text = "Correos electrónicos y teléfonos de contacto (pulsa para abrir):\n\n";
        $text .= "• Planificación y Recursos Humanos\n";
        $text .= "• Administración (ausencias, excedencias, nóminas y prevención)\n";
        $text .= "• Números de soporte y mutua";

        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => $text,
            'ui' => [
                'links' => get_admin_links(),
                'chips' => [
                    ['label' => 'Teléfonos de guardia', 'prompt' => 'telefonos guardia'],
                    ['label' => 'Documentos', 'prompt' => 'documentos'],
                ],
            ],
        ];
    }

    if ($normalized === 'enlaces') {
        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => 'A continuación te dejo varios enlaces de interés:',
            'ui' => [
                'links' => get_enlaces_links(),
                'chips' => [
                    ['label' => 'Documentos', 'prompt' => 'documentos'],
                    ['label' => 'Calendarios', 'prompt' => 'calendarios'],
                ],
            ],
        ];
    }

    if ($normalized === 'documentos') {
        $documentDownloads = get_assistant_catalog_downloads('documents');

        if ($documentDownloads === []) {
            return [
                'success' => true,
                'mode' => 'rule',
                'reply' => 'Ahora mismo no hay documentos disponibles en el catálogo.',
                'ui' => [
                    'chips' => [
                        ['label' => 'Calendarios', 'prompt' => 'calendarios'],
                        ['label' => 'Enlaces', 'prompt' => 'enlaces'],
                    ],
                ],
            ];
        }

        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => 'Documentos disponibles para descarga:',
            'ui' => [
                'downloads' => $documentDownloads,
                'chips' => [
                    ['label' => 'Calendarios', 'prompt' => 'calendarios'],
                    ['label' => 'Enlaces', 'prompt' => 'enlaces'],
                ],
            ],
        ];
    }

    if ($normalized === 'calendarios') {
        $calendarDownloads = get_assistant_catalog_downloads('calendars');

        if ($calendarDownloads === []) {
            return [
                'success' => true,
                'mode' => 'rule',
                'reply' => 'Ahora mismo no hay calendarios disponibles en el catálogo.',
                'ui' => [
                    'chips' => [
                        ['label' => 'Documentos', 'prompt' => 'documentos'],
                        ['label' => 'Correos electrónicos y teléfonos', 'prompt' => 'contacto'],
                    ],
                ],
            ];
        }

        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => 'Calendarios disponibles:',
            'ui' => [
                'downloads' => $calendarDownloads,
                'chips' => [
                    ['label' => 'Documentos', 'prompt' => 'documentos'],
                    ['label' => 'Correos electrónicos y teléfonos', 'prompt' => 'contacto'],
                ],
            ],
        ];
    }

    if (str_contains($normalized, 'gracias')) {
        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => 'Estoy aquí para ayudarte siempre que lo necesites. 🤖',
            'ui' => [
                'chips' => $baseChips,
            ],
        ];
    }

    if ($normalized === '/start' || is_greeting($normalized) || mb_strlen(trim($normalized), 'UTF-8') < 10) {
        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => '¡Hola! Soy el Asistente Virtual USO 🤖. ¿En qué puedo ayudarte?',
            'ui' => [
                'chips' => $baseChips,
            ],
        ];
    }

    if (
        $normalized === 'gestion bot'
        || str_starts_with($normalized, '/alta')
        || str_starts_with($normalized, '/baja')
        || str_starts_with($normalized, '/alias')
        || str_starts_with($normalized, '/admin')
        || $normalized === '/usuarios'
        || $normalized === '/consultas'
    ) {
        return [
            'success' => true,
            'mode' => 'rule',
            'reply' => "La gestión administrativa del bot está reservada para el canal interno (admin).\n\nEn esta versión web está deshabilitada por seguridad.",
            'ui' => [
                'chips' => $baseChips,
            ],
        ];
    }

    return null;
}

function consulta_openai(string $pregunta, array $contextMessages = []): array
{
    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    $promptId = defined('OPENAI_PROMPT_ID') ? OPENAI_PROMPT_ID : '';
    $promptVer = defined('OPENAI_PROMPT_VERSION') ? OPENAI_PROMPT_VERSION : '';
    $vectorStore = defined('OPENAI_VECTOR_STORE_ID') ? OPENAI_VECTOR_STORE_ID : '';

    if ($apiKey === '' || !function_exists('curl_init')) {
        return [
            'success' => true,
            'mode' => 'demo',
            'reply' => build_demo_reply($pregunta),
            'ui' => ['chips' => get_default_chips()],
        ];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ];

    $aiInputMessages = build_ai_input_messages($pregunta, $contextMessages);

    $requestPayload = [
        'prompt' => [
            'id' => $promptId,
            'version' => $promptVer,
        ],
        'input' => $aiInputMessages,
        'tools' => [
            [
                'type' => 'file_search',
                'vector_store_ids' => [$vectorStore],
            ],
        ],
        'store' => true,
        'include' => [
            'reasoning.encrypted_content',
            'web_search_call.action.sources',
        ],
    ];

    if ($promptId === '' || $promptVer === '') {
        unset($requestPayload['prompt']);
        $requestPayload['model'] = 'gpt-4o-mini';
        array_unshift($requestPayload['input'], [
            'role' => 'system',
            'content' => 'Eres el Asistente Virtual USO. Responde en español de forma clara, breve y amable. Si falta contexto, pide una aclaración concreta.',
        ]);
    }

    if ($vectorStore === '') {
        unset($requestPayload['tools']);
    }

    $postResponse = curl_request('https://api.openai.com/v1/responses', $headers, json_encode($requestPayload, JSON_UNESCAPED_UNICODE), 90);

    if (($postResponse['curl_errno'] ?? 0) !== 0) {
        return [
            'success' => true,
            'mode' => 'demo',
            'reply' => build_demo_reply($pregunta),
            'ui' => ['chips' => get_default_chips()],
        ];
    }

    $data = json_decode($postResponse['body'] ?? '', true);
    if (!is_array($data) || !empty($data['error'])) {
        return [
            'success' => true,
            'mode' => 'demo',
            'reply' => build_demo_reply($pregunta),
            'ui' => ['chips' => get_default_chips()],
        ];
    }

    $status = strtolower((string)($data['status'] ?? ''));
    $responseId = (string)($data['id'] ?? '');
    $retries = 0;
    $maxRetries = 20;

    while ($responseId !== '' && $status !== 'completed' && $retries < $maxRetries) {
        sleep(1);
        $retries++;

        $poll = curl_request('https://api.openai.com/v1/responses/' . rawurlencode($responseId), $headers, null, 60);
        if (($poll['curl_errno'] ?? 0) !== 0) {
            break;
        }

        $pollData = json_decode($poll['body'] ?? '', true);
        if (is_array($pollData)) {
            $data = $pollData;
            $status = strtolower((string)($data['status'] ?? $status));
            if ($status === 'completed') {
                break;
            }
        }
    }

    if (!empty($data['error'])) {
        return [
            'success' => true,
            'mode' => 'demo',
            'reply' => build_demo_reply($pregunta),
            'ui' => ['chips' => get_default_chips()],
        ];
    }

    $text = extraer_texto_openai($data);
    if ($text === '') {
        return [
            'success' => true,
            'mode' => 'demo',
            'reply' => build_demo_reply($pregunta),
            'ui' => ['chips' => get_default_chips()],
        ];
    }

    $legal = "\n\n⚠️ Respuesta generada automáticamente por IA. Si tienes dudas, consulta con un/a delegad@ de personal.";

    return [
        'success' => true,
        'mode' => 'ai',
        'reply' => trim($text) . $legal,
        'ui' => ['chips' => get_default_chips()],
    ];
}

function sanitize_context_messages($value, int $max = 5): array
{
    if (!is_array($value) || $max < 1) {
        return [];
    }

    $clean = [];
    foreach ($value as $item) {
        if (!is_string($item)) {
            continue;
        }

        $text = trim($item);
        if ($text === '') {
            continue;
        }

        if (is_low_value_context_message($text)) {
            continue;
        }

        $clean[] = mb_substr($text, 0, 900, 'UTF-8');
    }

    if ($clean === []) {
        return [];
    }

    if (count($clean) > $max) {
        $clean = array_slice($clean, -$max);
    }

    return array_values($clean);
}

function is_low_value_context_message(string $message): bool
{
    $normalized = normalize_text($message);
    if ($normalized === '') {
        return true;
    }

    if (mb_strlen($normalized, 'UTF-8') <= 2) {
        return true;
    }

    $acknowledgements = [
        'ok',
        'oki',
        'okay',
        'vale',
        'perfecto',
        'de acuerdo',
        'bien',
        'genial',
        'gracias',
        'muchas gracias',
        'mil gracias',
    ];

    return in_array($normalized, $acknowledgements, true);
}

function build_ai_input_messages(string $question, array $contextMessages): array
{
    $messages = [];

    foreach ($contextMessages as $message) {
        $text = trim((string)$message);
        if ($text === '') {
            continue;
        }

        $messages[] = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => $text,
                ],
            ],
        ];
    }

    $currentQuestion = trim($question);
    if ($currentQuestion !== '') {
        $messages[] = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => $currentQuestion,
                ],
            ],
        ];
    }

    return $messages;
}

function curl_request(string $url, array $headers = [], $postData = null, int $timeoutSec = 60): array
{
    $ch = curl_init();

    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeoutSec,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FAILONERROR => false,
    ];

    if ($postData !== null) {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = $postData;
    }

    curl_setopt_array($ch, $opts);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'curl_errno' => $errno,
        'curl_error' => $error,
        'body' => is_string($body) ? $body : '',
    ];
}

function extraer_primera_string_por_claves(array $arr, array $keys): string
{
    foreach ($keys as $key) {
        if (isset($arr[$key]) && is_string($arr[$key]) && trim($arr[$key]) !== '') {
            return trim($arr[$key]);
        }
    }
    return '';
}

function buscar_string_recursiva($node, array $keys, int $depth, int $maxDepth): string
{
    if ($depth > $maxDepth) {
        return '';
    }

    if (is_array($node)) {
        foreach ($keys as $key) {
            if (isset($node[$key]) && is_string($node[$key]) && trim($node[$key]) !== '') {
                return trim($node[$key]);
            }
        }

        foreach ($node as $value) {
            $found = buscar_string_recursiva($value, $keys, $depth + 1, $maxDepth);
            if ($found !== '') {
                return $found;
            }
        }
    }

    return '';
}

function extraer_texto_openai(array $data): string
{
    $candidatos = [];

    if (!empty($data['output']) && is_array($data['output'])) {
        foreach ($data['output'] as $outItem) {
            if (!is_array($outItem)) {
                continue;
            }

            if (($outItem['type'] ?? '') === 'message') {
                $content = $outItem['content'] ?? [];
                if (is_array($content)) {
                    foreach ($content as $chunk) {
                        if (!is_array($chunk)) {
                            continue;
                        }

                        if (($chunk['type'] ?? '') === 'output_text' && isset($chunk['text']) && is_string($chunk['text'])) {
                            $candidatos[] = $chunk['text'];
                        }

                        $maybe = extraer_primera_string_por_claves($chunk, ['text', 'value']);
                        if ($maybe !== '') {
                            $candidatos[] = $maybe;
                        }
                    }
                }
            }
        }
    }

    if (isset($data['output_text']) && is_string($data['output_text']) && $data['output_text'] !== '') {
        $candidatos[] = $data['output_text'];
    }

    if (isset($data['text']) && is_array($data['text'])) {
        $maybe = extraer_primera_string_por_claves($data['text'], ['value', 'text']);
        if ($maybe !== '') {
            $candidatos[] = $maybe;
        }
    }

    if (empty($candidatos)) {
        if (isset($data['output'])) {
            $candidatos[] = buscar_string_recursiva($data['output'], ['text', 'value'], 0, 20);
        }
        if (trim(implode("\n", $candidatos)) === '') {
            $candidatos[] = buscar_string_recursiva($data, ['text', 'value'], 0, 25);
        }
    }

    $candidatos = array_filter(array_map(static function ($item) {
        return is_string($item) ? trim($item) : '';
    }, $candidatos));

    $uniq = [];
    foreach ($candidatos as $item) {
        if (!in_array($item, $uniq, true)) {
            $uniq[] = $item;
        }
    }

    return trim(implode("\n\n", $uniq));
}

function build_demo_reply(string $message): string
{
    $lower = normalize_text($message);

    if (str_contains($lower, 'afili')) {
        return "Para afiliarte, normalmente necesitarás tus datos personales y de contacto.\n\nSi quieres, te guío paso a paso según tu situación laboral actual.";
    }

    if (str_contains($lower, 'cita') || str_contains($lower, 'reun')) {
        return "Puedes solicitar una cita indicando tu consulta, disponibilidad y un teléfono de contacto.\n\nSi me dices tu provincia, te preparo un mensaje tipo para enviarlo.";
    }

    if (str_contains($lower, 'document')) {
        return "Para consultas laborales suele ser útil aportar contrato, últimas nóminas y cualquier comunicación de la empresa.\n\nSi me explicas el caso, te digo qué documentos priorizar.";
    }

    return "He recibido tu consulta: \"{$message}\".\n\nEstoy en modo demo en este entorno. Para activar IA real, revisa la configuración OpenAI en el servidor.";
}

function log_consulta_db(string $usuario, string $consulta): void
{
    $dbHost = defined('DB_HOST') ? DB_HOST : '';
    $dbName = defined('DB_NAME') ? DB_NAME : '';
    $dbUser = defined('DB_USER') ? DB_USER : '';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        return;
    }

    $conex = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
    if (!$conex) {
        return;
    }

    mysqli_set_charset($conex, 'utf8mb4');
    $sql = 'INSERT INTO log_consultas (USER, CONSULTA) VALUES (?, ?)';
    $stmt = mysqli_prepare($conex, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $usuario, $consulta);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conex);
}

function get_default_chips(): array
{
    return [
        ['label' => 'Teléfonos de guardia', 'prompt' => 'telefonos guardia'],
        ['label' => 'Correos electrónicos y teléfonos', 'prompt' => 'contacto'],
        ['label' => 'Enlaces', 'prompt' => 'enlaces'],
        ['label' => 'He tenido un accidente', 'prompt' => 'he tenido un accidente'],
        ['label' => 'Documentos', 'prompt' => 'documentos'],
        ['label' => 'Calendarios', 'prompt' => 'calendarios'],
    ];
}

function get_assistant_catalog_downloads(string $type): array
{
    $catalog = fetch_documents_catalog_from_db();
    $items = $type === 'calendars'
        ? (is_array($catalog['calendars'] ?? null) ? $catalog['calendars'] : [])
        : (is_array($catalog['documents'] ?? null) ? $catalog['documents'] : []);

    $downloads = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $title = trim((string)($item['title'] ?? ''));
        $path = ltrim(str_replace('\\', '/', trim((string)($item['path'] ?? ''))), '/');

        if ($path === '') {
            continue;
        }

        $downloads[] = [
            'label' => $title !== '' ? $title : ($type === 'calendars' ? 'Calendario' : 'Documento'),
            'url' => BASE_URL . '/public/assets/' . $path,
            'fileName' => build_download_filename(
                $title !== '' ? $title : ($type === 'calendars' ? 'Calendario' : 'Documento'),
                $path,
                $type === 'calendars' ? 'calendario' : 'documento'
            ),
        ];
    }

    return $downloads;
}

function normalize_text(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
    return trim($value);
}

function is_greeting(string $value): bool
{
    return str_contains($value, 'hola')
        || str_contains($value, 'buenos dias')
        || str_contains($value, 'buenas tardes')
        || str_contains($value, 'buenas noches');
}

function is_valid_security_code(string $value): bool
{
    $configuredCode = trim((string)(defined('ASSISTANT_SECURITY_CODE') ? ASSISTANT_SECURITY_CODE : 'clemen'));
    if ($configuredCode === '') {
        $configuredCode = 'clemen';
    }

    return normalize_text($value) === normalize_text($configuredCode);
}

function is_ai_authorized(): bool
{
    if (!assistant_requires_security_code()) {
        return true;
    }

    $until = (int)($_SESSION['assistant_ai_authorized_until'] ?? 0);
    return $until > time();
}

function assistant_requires_security_code(): bool
{
    return defined('ASSISTANT_REQUIRE_SECURITY_CODE') && ASSISTANT_REQUIRE_SECURITY_CODE;
}

function authorize_ai_access(): void
{
    $_SESSION['assistant_ai_authorized_until'] = time() + 86400;
    $_SESSION['assistant_ai_waiting_code'] = false;
}

function request_security_code(): void
{
    $_SESSION['assistant_ai_waiting_code'] = true;
}

function is_waiting_security_code(): bool
{
    return (bool)($_SESSION['assistant_ai_waiting_code'] ?? false);
}
