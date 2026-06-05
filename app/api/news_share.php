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

$recipient = trim((string)($payload['email'] ?? ''));
$newsId = isset($payload['newsId']) ? (int)$payload['newsId'] : 0;
$title = trim((string)($payload['title'] ?? 'Noticia'));
$url = trim((string)($payload['url'] ?? ''));
$contentFromPayload = trim((string)($payload['content'] ?? ''));

if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'El correo destinatario no es válido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'La URL de la noticia no es válida.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($title === '') {
    $title = 'Noticia';
}

$newsContent = $contentFromPayload;
if ($newsId > 0) {
    $pdo = get_db_connection();
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare('SELECT titulo, texto FROM noticias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $newsId]);
        $row = $stmt->fetch();

        if (is_array($row) && $row) {
            $dbTitle = trim((string)($row['titulo'] ?? ''));
            if ($dbTitle !== '') {
                $title = $dbTitle;
            }

            $rawText = (string)($row['texto'] ?? '');
            $withBreaks = preg_replace('/<\s*br\s*\/?>/iu', "\n", $rawText) ?? $rawText;
            $withParagraphs = preg_replace('/<\s*\/p\s*>/iu', "\n\n", $withBreaks) ?? $withBreaks;
            $plain = html_entity_decode(strip_tags($withParagraphs), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalized = trim(preg_replace('/\n{3,}/u', "\n\n", preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain) ?? $plain);
            if ($normalized !== '') {
                $newsContent = $normalized;
            }
        }
    }
}

if ($newsContent === '') {
    $newsContent = 'No se pudo recuperar el contenido de la noticia.';
}

$subject = 'USO OEST | Noticia compartida: ' . $title;
$bodyLines = [$title, '', $newsContent];

$mailSent = send_email_message($recipient, $subject, implode("\n", $bodyLines), [
    'from_name' => 'USO OEST Noticias',
]);

if (!$mailSent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo enviar el correo en este momento.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Correo enviado correctamente.',
], JSON_UNESCAPED_UNICODE);
