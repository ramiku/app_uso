<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

ensure_calendar_tables();

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = [];

if ($method === 'POST') {
    $rawBody = file_get_contents('php://input');
    $decoded = json_decode($rawBody ?: '{}', true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$action = trim((string)($input['action'] ?? ($_GET['action'] ?? 'list_years')));

if ($action === 'list_years') {
    respond([
        'success' => true,
        'years' => calendar_list_years(),
    ]);
}

if ($action === 'create_year') {
    if ($method !== 'POST') {
        respondError('Método no permitido', 405);
    }

    $year = (int)($input['year'] ?? 0);
    $created = calendar_create_year($year);
    if (!is_array($created)) {
        respondError('No se pudo crear el año indicado.', 422);
    }

    respond([
        'success' => true,
        'year' => $created,
    ]);
}

if ($action === 'year_detail') {
    $year = (int)($_GET['year'] ?? ($input['year'] ?? 0));
    $yearItem = calendar_get_year_by_value($year);
    if (!is_array($yearItem)) {
        respondError('El año indicado no existe.', 404);
    }

    $yearId = (int)($yearItem['id'] ?? 0);

    respond([
        'success' => true,
        'year' => $yearItem,
        'holidays' => calendar_list_holidays($yearId),
        'rotations' => calendar_list_rotations($yearId),
    ]);
}

if ($action === 'list_holidays') {
    $yearId = (int)($_GET['year_id'] ?? ($input['year_id'] ?? 0));
    respond([
        'success' => true,
        'holidays' => calendar_list_holidays($yearId),
    ]);
}

if ($action === 'create_holiday') {
    if ($method !== 'POST') {
        respondError('Método no permitido', 405);
    }

    $yearId = (int)($input['year_id'] ?? 0);
    $date = (string)($input['date'] ?? '');
    $label = (string)($input['label'] ?? '');
    $holidayType = (string)($input['holiday_type'] ?? 'nacional');

    if (!calendar_add_holiday($yearId, $date, $label, $holidayType)) {
        respondError('No se pudo guardar el festivo (posible duplicado).', 422);
    }

    respond([
        'success' => true,
        'holidays' => calendar_list_holidays($yearId),
    ]);
}

if ($action === 'delete_holiday') {
    if ($method !== 'POST') {
        respondError('Método no permitido', 405);
    }

    $yearId = (int)($input['year_id'] ?? 0);
    $holidayId = (int)($input['holiday_id'] ?? 0);

    if (!calendar_delete_holiday($yearId, $holidayId)) {
        respondError('No se pudo eliminar el festivo.', 422);
    }

    respond([
        'success' => true,
        'holidays' => calendar_list_holidays($yearId),
    ]);
}

if ($action === 'list_rotations') {
    $yearId = (int)($_GET['year_id'] ?? ($input['year_id'] ?? 0));
    respond([
        'success' => true,
        'rotations' => calendar_list_rotations($yearId),
    ]);
}

if ($action === 'save_rotation') {
    if ($method !== 'POST') {
        respondError('Método no permitido', 405);
    }

    $yearId = (int)($input['year_id'] ?? 0);
    $rotationId = isset($input['rotation_id']) ? (int)$input['rotation_id'] : null;
    $name = (string)($input['name'] ?? '');
    $weeksCycle = (int)($input['weeks_cycle'] ?? 1);
    $isActive = (bool)($input['is_active'] ?? true);
    $isDefault = (bool)($input['is_default'] ?? false);
    $pattern = is_array($input['pattern'] ?? null) ? $input['pattern'] : [];

    $savedRotationId = calendar_save_rotation($yearId, $rotationId, $name, $weeksCycle, $isActive, $isDefault, $pattern);
    if ($savedRotationId === null) {
        respondError('No se pudo guardar la rotación.', 422);
    }

    respond([
        'success' => true,
        'rotation' => calendar_get_rotation($savedRotationId),
        'pattern' => calendar_get_rotation_pattern($savedRotationId),
        'rotations' => calendar_list_rotations($yearId),
    ]);
}

if ($action === 'delete_rotation') {
    if ($method !== 'POST') {
        respondError('Método no permitido', 405);
    }

    $yearId = (int)($input['year_id'] ?? 0);
    $rotationId = (int)($input['rotation_id'] ?? 0);

    if (!calendar_delete_rotation($yearId, $rotationId)) {
        respondError('No se pudo eliminar la rotación.', 422);
    }

    respond([
        'success' => true,
        'rotations' => calendar_list_rotations($yearId),
    ]);
}

if ($action === 'get_rotation_pattern') {
    $rotationId = (int)($_GET['rotation_id'] ?? ($input['rotation_id'] ?? 0));
    $rotation = calendar_get_rotation($rotationId);
    if (!is_array($rotation)) {
        respondError('La rotación indicada no existe.', 404);
    }

    respond([
        'success' => true,
        'rotation' => $rotation,
        'pattern' => calendar_get_rotation_pattern($rotationId),
    ]);
}

if ($action === 'generate') {
    $year = (int)($_GET['year'] ?? ($input['year'] ?? 0));
    $rotationId = (int)($_GET['rotation_id'] ?? ($input['rotation_id'] ?? 0));

    if ($year <= 0 || $rotationId <= 0) {
        respondError('Debes indicar año y rotación.', 422);
    }

    $generated = calendar_generate_calendar($year, $rotationId);
    respond([
        'success' => true,
        'items' => $generated,
    ]);
}

respondError('Acción no soportada.', 404);

function respond(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondError(string $message, int $statusCode = 400): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
