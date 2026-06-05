<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

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

$name = trim((string)($payload['name'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Nombre, correo electrónico y mensaje son obligatorios.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'El correo electrónico no tiene un formato válido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$to = 'uso-oest@hotmail.es';
$subject = 'Nuevo mensaje desde formulario de contacto';

$bodyLines = [
    'Se ha recibido una nueva consulta desde la web:',
    '',
    'Nombre: ' . $name,
    'Correo: ' . $email,
    'Teléfono: ' . ($phone !== '' ? $phone : 'No indicado'),
    '',
    'Mensaje:',
    $message,
];

$body = implode("\n", $bodyLines);

$mailSent = send_email_message($to, $subject, $body, [
    'from_email' => MAIL_FROM_EMAIL,
    'from_name' => 'USO OEST Contacto',
    'reply_to' => $email,
]);

if (!$mailSent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo enviar el mensaje en este momento. Inténtalo de nuevo más tarde.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Mensaje enviado correctamente. Te responderemos lo antes posible.',
], JSON_UNESCAPED_UNICODE);
