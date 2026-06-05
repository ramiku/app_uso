<?php
declare(strict_types=1);

function asset(string $path): string
{
    return rtrim(BASE_URL, '/') . '/public/assets/' . ltrim($path, '/');
}

function url_for(string $page = 'home', array $params = []): string
{
    $base = rtrim(BASE_URL, '/');

    if ($page === 'home') {
        return $base . '/';
    }

    if ($page === 'noticias') {
        $id = isset($params['id']) ? (int)$params['id'] : 0;
        if ($id > 0) {
            return $base . '/noticias/' . $id;
        }

        $slug = trim((string)($params['slug'] ?? ''));
        if ($slug !== '') {
            return $base . '/noticias/slug/' . rawurlencode($slug);
        }

        $search = trim((string)($params['q'] ?? ''));
        if ($search !== '') {
            return $base . '/noticias/buscar/' . rawurlencode($search);
        }

        $pageNumber = isset($params['p']) ? (int)$params['p'] : 1;
        if ($pageNumber > 1) {
            return $base . '/noticias/pagina/' . $pageNumber;
        }

        return $base . '/noticias';
    }

    if ($page === 'calendarios') {
        $url = $base . '/calendarios';
        $query = [];

        $year = isset($params['year']) ? (int)$params['year'] : 0;
        $rotation = isset($params['rotation']) ? (int)$params['rotation'] : 0;

        if ($year > 0) {
            $query['year'] = $year;
        }
        if ($rotation > 0) {
            $query['rotation'] = $rotation;
        }

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    return $base . '/' . rawurlencode($page);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function build_download_filename(string $label, string $pathOrUrl, string $fallbackBase = 'archivo'): string
{
    $baseName = trim($label);
    if ($baseName === '') {
        $baseName = trim($fallbackBase);
    }
    if ($baseName === '') {
        $baseName = 'archivo';
    }

    $baseName = preg_replace('/[\\\/:*?"<>|]+/u', ' ', $baseName) ?? $baseName;
    $baseName = trim(preg_replace('/\s+/u', ' ', $baseName) ?? $baseName);
    if ($baseName === '') {
        $baseName = 'archivo';
    }

    $parsedPath = parse_url($pathOrUrl, PHP_URL_PATH);
    $pathForExt = is_string($parsedPath) ? $parsedPath : $pathOrUrl;
    $extension = strtolower((string)pathinfo($pathForExt, PATHINFO_EXTENSION));

    if ($extension !== '' && preg_match('/\.' . preg_quote($extension, '/') . '$/i', $baseName) !== 1) {
        $baseName .= '.' . $extension;
    }

    return $baseName;
}

function mailer_config(): array
{
    $transport = defined('MAIL_TRANSPORT') ? strtolower(trim((string)MAIL_TRANSPORT)) : 'mail';
    if (!in_array($transport, ['mail', 'smtp'], true)) {
        $transport = 'mail';
    }

    $host = defined('MAIL_HOST') ? trim((string)MAIL_HOST) : '';
    $port = defined('MAIL_PORT') ? (int)MAIL_PORT : 587;
    $encryption = defined('MAIL_ENCRYPTION') ? strtolower(trim((string)MAIL_ENCRYPTION)) : 'tls';
    if (!in_array($encryption, ['', 'tls', 'ssl'], true)) {
        $encryption = 'tls';
    }

    return [
        'transport' => $transport,
        'host' => $host,
        'port' => $port > 0 ? $port : 587,
        'encryption' => $encryption,
        'username' => defined('MAIL_USERNAME') ? trim((string)MAIL_USERNAME) : '',
        'password' => defined('MAIL_PASSWORD') ? (string)MAIL_PASSWORD : '',
        'timeout' => max(3, defined('MAIL_TIMEOUT') ? (int)MAIL_TIMEOUT : 10),
        'from_email' => defined('MAIL_FROM_EMAIL') ? trim((string)MAIL_FROM_EMAIL) : 'no-reply@webuso.local',
        'from_name' => defined('MAIL_FROM_NAME') ? trim((string)MAIL_FROM_NAME) : 'Webuso',
    ];
}

function normalize_mail_header_value(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function encode_mail_subject(string $subject): string
{
    $safeSubject = normalize_mail_header_value($subject);
    return '=?UTF-8?B?' . base64_encode($safeSubject) . '?=';
}

function send_email_message(string $to, string $subject, string $body, array $options = []): bool
{
    $recipient = trim($to);
    if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    $config = mailer_config();
    $fromEmail = trim((string)($options['from_email'] ?? $config['from_email']));
    if ($fromEmail === '' || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
        $fromEmail = 'no-reply@webuso.local';
    }

    $fromName = normalize_mail_header_value((string)($options['from_name'] ?? $config['from_name']));
    if ($fromName === '') {
        $fromName = 'Webuso';
    }

    $replyTo = trim((string)($options['reply_to'] ?? ''));
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL) === false) {
        $replyTo = '';
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
    ];

    if ($replyTo !== '') {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    if ($config['transport'] === 'smtp' && (string)$config['host'] !== '') {
        return smtp_send_email($recipient, $subject, $body, $headers, $config, $fromEmail);
    }

    return @mail($recipient, encode_mail_subject($subject), $body, implode("\r\n", $headers));
}

function smtp_send_email(string $to, string $subject, string $body, array $headers, array $config, string $fromEmail): bool
{
    $host = trim((string)($config['host'] ?? ''));
    if ($host === '') {
        return false;
    }

    $port = (int)($config['port'] ?? 587);
    $timeout = (int)($config['timeout'] ?? 10);
    $encryption = (string)($config['encryption'] ?? 'tls');
    $username = trim((string)($config['username'] ?? ''));
    $password = (string)($config['password'] ?? '');

    $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errno, $errstr, $timeout);
    if (!is_resource($socket)) {
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $ok = smtp_expect_response($socket, [220])
        && smtp_send_command($socket, 'EHLO localhost', [250]);

    if ($ok && $encryption === 'tls') {
        $ok = smtp_send_command($socket, 'STARTTLS', [220]);
        if ($ok) {
            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $ok = $cryptoEnabled === true && smtp_send_command($socket, 'EHLO localhost', [250]);
        }
    }

    if ($ok && $username !== '') {
        $ok = smtp_send_command($socket, 'AUTH LOGIN', [334])
            && smtp_send_command($socket, base64_encode($username), [334])
            && smtp_send_command($socket, base64_encode($password), [235]);
    }

    $safeFrom = trim($fromEmail);
    $safeTo = trim($to);

    if ($ok) {
        $ok = smtp_send_command($socket, 'MAIL FROM:<' . $safeFrom . '>', [250])
            && smtp_send_command($socket, 'RCPT TO:<' . $safeTo . '>', [250, 251])
            && smtp_send_command($socket, 'DATA', [354]);
    }

    if ($ok) {
        $messageHeaders = $headers;
        $messageHeaders[] = 'To: <' . $safeTo . '>';
        $messageHeaders[] = 'Subject: ' . encode_mail_subject($subject);
        $rawMessage = implode("\r\n", $messageHeaders) . "\r\n\r\n" . $body;
        $normalized = preg_replace("/\r\n|\r|\n/u", "\n", $rawMessage) ?? $rawMessage;
        $lines = explode("\n", $normalized);
        foreach ($lines as &$line) {
            if (str_starts_with($line, '.')) {
                $line = '.' . $line;
            }
        }
        unset($line);
        $payload = implode("\r\n", $lines) . "\r\n.\r\n";

        $written = fwrite($socket, $payload);
        $ok = $written !== false && smtp_expect_response($socket, [250]);
    }

    smtp_send_command($socket, 'QUIT', [221]);
    fclose($socket);

    return $ok;
}

function smtp_send_command($socket, string $command, array $expectedCodes): bool
{
    $written = fwrite($socket, $command . "\r\n");
    if ($written === false) {
        return false;
    }

    return smtp_expect_response($socket, $expectedCodes);
}

function smtp_expect_response($socket, array $expectedCodes): bool
{
    $code = smtp_read_response_code($socket);
    if ($code === null) {
        return false;
    }

    return in_array($code, $expectedCodes, true);
}

function smtp_read_response_code($socket): ?int
{
    $code = null;

    while (!feof($socket)) {
        $line = fgets($socket, 1024);
        if (!is_string($line)) {
            break;
        }

        if (preg_match('/^(\d{3})([\s-])/', $line, $matches) !== 1) {
            continue;
        }

        $code = (int)$matches[1];
        if ($matches[2] === ' ') {
            break;
        }
    }

    return $code;
}

function get_page_meta(string $page): array
{
    $metaMap = [
        'home' => [
            'title' => 'Inicio | ' . SITE_NAME,
            'description' => 'Últimas noticias, análisis y temas destacados del día.',
        ],
        'noticias' => [
            'title' => 'Noticias | ' . SITE_NAME,
            'description' => 'Listado completo de noticias por categoría y fecha.',
        ],
        'contactanos' => [
            'title' => 'Contáctanos | ' . SITE_NAME,
            'description' => 'Canales de contacto para consultas y propuestas.',
        ],
        'documentacion' => [
            'title' => 'Documentación | ' . SITE_NAME,
            'description' => 'Repositorio de documentos, notas y comunicados.',
        ],
        'calendarios' => [
            'title' => 'Calendarios | ' . SITE_NAME,
            'description' => 'Consulta calendarios de trabajo, descanso y festivos por año y rotación.',
        ],
        'asistente' => [
            'title' => 'Asistente Virtual USO | ' . SITE_NAME,
            'description' => 'Acceso al asistente virtual para ayuda y orientación rápida.',
        ],
        'privacidad' => [
            'title' => 'Política de privacidad | ' . SITE_NAME,
            'description' => 'Política de privacidad para la aplicación Android y sitio web USO OEST.',
        ],
        '404' => [
            'title' => 'Página no encontrada | ' . SITE_NAME,
            'description' => 'La página solicitada no existe o ha sido movida.',
        ],
    ];

    return $metaMap[$page] ?? [
        'title' => SITE_NAME,
        'description' => 'Portal de noticias y actualidad.',
    ];
}

function paginate_items(array $items, int $page, int $perPage): array
{
    $totalItems = count($items);
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $safePage = max(1, min($page, $totalPages));
    $offset = ($safePage - 1) * $perPage;

    return [
        'items' => array_slice($items, $offset, $perPage),
        'currentPage' => $safePage,
        'perPage' => $perPage,
        'totalItems' => $totalItems,
        'totalPages' => $totalPages,
        'hasMore' => $safePage < $totalPages,
    ];
}

function get_db_connection(): ?PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbHost = defined('DB_HOST') ? DB_HOST : '';
    $dbName = defined('DB_NAME') ? DB_NAME : '';
    $dbUser = defined('DB_USER') ? DB_USER : '';
    $dbPass = defined('DB_PASS') ? DB_PASS : '';

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        return null;
    }

    try {
        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $exception) {
        return null;
    }

    return $pdo;
}

function get_news_excerpt(string $text, int $maxLength = 170): string
{
    $plainText = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $normalized = trim(preg_replace('/\s+/u', ' ', $plainText) ?? '');
    if ($normalized === '') {
        return '';
    }

    if (mb_strlen($normalized, 'UTF-8') <= $maxLength) {
        return $normalized;
    }

    return rtrim(mb_substr($normalized, 0, $maxLength, 'UTF-8')) . '…';
}

function news_select_fields(PDO $pdo): string
{
    static $hasImageColumn = null;

    if ($hasImageColumn === null) {
        try {
            $columnStmt = $pdo->query("SHOW COLUMNS FROM noticias LIKE 'ruta_imagen'");
            $column = $columnStmt ? $columnStmt->fetch() : false;
            $hasImageColumn = (bool)$column;
        } catch (Throwable $exception) {
            $hasImageColumn = false;
        }
    }

    if ($hasImageColumn) {
        return 'id, fecha_creaccion, titulo, texto, ruta_imagen';
    }

    return 'id, fecha_creaccion, titulo, texto';
}

function map_news_row(array $row): array
{
    $imagePath = trim((string)($row['ruta_imagen'] ?? ''));
    if ($imagePath === '' || str_contains($imagePath, '..')) {
        $imagePath = 'img/placeholder.svg';
    } else {
        $imagePath = ltrim(str_replace('\\', '/', $imagePath), '/');
        if ($imagePath === '' || !str_starts_with($imagePath, 'img/')) {
            $imagePath = 'img/placeholder.svg';
        }
    }

    return [
        'id' => (int)($row['id'] ?? 0),
        'title' => (string)($row['titulo'] ?? ''),
        'excerpt' => get_news_excerpt((string)($row['texto'] ?? '')),
        'text' => (string)($row['texto'] ?? ''),
        'date' => (string)($row['fecha_creaccion'] ?? date('Y-m-d H:i:s')),
        'imagePath' => $imagePath,
        'imageUrl' => asset($imagePath),
        'category' => 'Comunicado',
        'detailUrl' => url_for('noticias', [
            'id' => (int)($row['id'] ?? 0),
        ]),
    ];
}

function fetch_news_attachments_from_db(int $newsId): array
{
    $empty = [
        'images' => [],
        'documents' => [],
    ];

    if ($newsId <= 0) {
        return $empty;
    }

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return $empty;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, tipo, nombre_original, ruta_archivo
             FROM noticias_adjuntos
             WHERE noticia_id = :news_id
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['news_id' => $newsId]);

        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return $empty;
        }

        $images = [];
        $documents = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = trim((string)($row['tipo'] ?? 'documento'));
            $originalName = trim((string)($row['nombre_original'] ?? 'Archivo adjunto'));
            $relativePath = ltrim(str_replace('\\', '/', (string)($row['ruta_archivo'] ?? '')), '/');

            if ($relativePath === '' || !str_starts_with($relativePath, 'files/noticias/')) {
                continue;
            }

            $item = [
                'name' => $originalName !== '' ? $originalName : 'Archivo adjunto',
                'path' => $relativePath,
                'url' => asset($relativePath),
            ];

            if ($type === 'imagen') {
                $images[] = $item;
            } else {
                $documents[] = $item;
            }
        }

        return [
            'images' => $images,
            'documents' => $documents,
        ];
    } catch (Throwable $exception) {
        return $empty;
    }
}

function fetch_news_detail_from_db(?int $id = null, ?string $slug = null, string $status = 'publicada'): ?array
{
    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return null;
    }

    $safeSlug = trim((string)$slug);
    $safeId = $id !== null ? max(1, $id) : null;

    if ($safeId === null && $safeSlug === '') {
        return null;
    }

    try {
        $selectFields = news_select_fields($pdo);

        if ($safeId !== null) {
            $stmt = $pdo->prepare(
                'SELECT ' . $selectFields . '
                 FROM noticias
                 WHERE estado = :estado AND id = :id
                 LIMIT 1'
            );
            $stmt->bindValue(':estado', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $safeId, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare(
                'SELECT ' . $selectFields . '
                 FROM noticias
                 WHERE estado = :estado AND slug = :slug
                 LIMIT 1'
            );
            $stmt->bindValue(':estado', $status, PDO::PARAM_STR);
            $stmt->bindValue(':slug', $safeSlug, PDO::PARAM_STR);
        }

        $stmt->execute();
        $row = $stmt->fetch();

        if (!is_array($row) || !$row) {
            return null;
        }

        $news = map_news_row($row);
        $news['attachments'] = fetch_news_attachments_from_db((int)$news['id']);

        return $news;
    } catch (Throwable $exception) {
        return null;
    }
}

function fetch_news_from_db(int $page, int $perPage, string $status = 'publicada'): array
{
    $safePage = max(1, $page);
    $safePerPage = max(1, min(20, $perPage));
    $offset = ($safePage - 1) * $safePerPage;

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return [
            'items' => [],
            'currentPage' => 1,
            'perPage' => $safePerPage,
            'totalItems' => 0,
            'totalPages' => 1,
            'hasMore' => false,
        ];
    }

    try {
        $selectFields = news_select_fields($pdo);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM noticias WHERE estado = :estado');
        $countStmt->execute(['estado' => $status]);
        $totalItems = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($totalItems / $safePerPage));
        $safePage = min($safePage, $totalPages);
        $offset = ($safePage - 1) * $safePerPage;

        $stmt = $pdo->prepare(
            'SELECT ' . $selectFields . '
             FROM noticias
             WHERE estado = :estado
             ORDER BY fecha_creaccion DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':estado', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $safePerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        $items = array_map('map_news_row', is_array($rows) ? $rows : []);

        return [
            'items' => $items,
            'currentPage' => $safePage,
            'perPage' => $safePerPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'hasMore' => $safePage < $totalPages,
        ];
    } catch (Throwable $exception) {
        return [
            'items' => [],
            'currentPage' => 1,
            'perPage' => $safePerPage,
            'totalItems' => 0,
            'totalPages' => 1,
            'hasMore' => false,
        ];
    }
}

function fetch_documents_catalog_from_db(): array
{
    $empty = [
        'documents' => [],
        'calendars' => [],
    ];

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return $empty;
    }

    try {
        $stmt = $pdo->query(
            "SELECT id, display_name, file_path, folder
             FROM uso_documents
             ORDER BY folder ASC, created_at DESC, id DESC"
        );

        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return $empty;
        }

        $documents = [];
        $calendars = [];

        foreach ($rows as $row) {
            $title = trim((string)($row['display_name'] ?? ''));
            $path = trim((string)($row['file_path'] ?? ''));
            $folder = trim((string)($row['folder'] ?? 'files'));

            if ($title === '' || $path === '') {
                continue;
            }

            $item = [
                'title' => $title,
                'path' => $path,
            ];

            if ($folder === 'files/calendarios') {
                $calendars[] = $item;
            } else {
                $documents[] = $item;
            }
        }

        return [
            'documents' => $documents,
            'calendars' => $calendars,
        ];
    } catch (Throwable $exception) {
        return $empty;
    }
}

function ensure_calendar_tables(): void
{
    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS uso_calendar_years (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            year SMALLINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_calendar_year (year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS uso_calendar_holidays (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            calendar_year_id BIGINT UNSIGNED NOT NULL,
            holiday_date DATE NOT NULL,
            holiday_type ENUM('nacional','local') NOT NULL DEFAULT 'nacional',
            holiday_label VARCHAR(160) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_calendar_holiday (calendar_year_id, holiday_date),
            KEY idx_calendar_holiday_year (calendar_year_id),
            CONSTRAINT fk_calendar_holiday_year FOREIGN KEY (calendar_year_id)
                REFERENCES uso_calendar_years(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        $holidayTypeColumnStmt = $pdo->query("SHOW COLUMNS FROM uso_calendar_holidays LIKE 'holiday_type'");
        $holidayTypeColumn = $holidayTypeColumnStmt ? $holidayTypeColumnStmt->fetch() : false;
        if (!$holidayTypeColumn) {
            $pdo->exec(
                "ALTER TABLE uso_calendar_holidays
                 ADD COLUMN holiday_type ENUM('nacional','local') NOT NULL DEFAULT 'nacional' AFTER holiday_date"
            );
        }
    } catch (Throwable $exception) {
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS uso_calendar_rotations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            calendar_year_id BIGINT UNSIGNED NOT NULL,
            rotation_name VARCHAR(160) NOT NULL,
            weeks_cycle TINYINT UNSIGNED NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_rotation_year (calendar_year_id),
            CONSTRAINT fk_rotation_year FOREIGN KEY (calendar_year_id)
                REFERENCES uso_calendar_years(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS uso_calendar_rotation_pattern (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rotation_id BIGINT UNSIGNED NOT NULL,
            week_index TINYINT UNSIGNED NOT NULL,
            day_of_week TINYINT UNSIGNED NOT NULL,
            is_working TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rotation_day (rotation_id, week_index, day_of_week),
            KEY idx_pattern_rotation (rotation_id),
            CONSTRAINT fk_pattern_rotation FOREIGN KEY (rotation_id)
                REFERENCES uso_calendar_rotations(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function calendar_normalize_holiday_type(string $holidayType): string
{
    $safeType = strtolower(trim($holidayType));
    return $safeType === 'local' ? 'local' : 'nacional';
}

function calendar_list_years(): array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return [];
    }

    try {
        $stmt = $pdo->query('SELECT id, year, created_at, updated_at FROM uso_calendar_years ORDER BY year DESC');
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    } catch (Throwable $exception) {
        return [];
    }
}

function calendar_get_year_by_value(int $year): ?array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $year < 2000 || $year > 2100) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, year, created_at, updated_at FROM uso_calendar_years WHERE year = :year LIMIT 1');
        $stmt->execute(['year' => $year]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function calendar_get_year_by_id(int $yearId): ?array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, year, created_at, updated_at FROM uso_calendar_years WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $yearId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function calendar_create_year(int $year): ?array
{
    ensure_calendar_tables();

    if ($year < 2000 || $year > 2100) {
        return null;
    }

    $existing = calendar_get_year_by_value($year);
    if ($existing !== null) {
        return $existing;
    }

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO uso_calendar_years (year) VALUES (:year)');
        $stmt->execute(['year' => $year]);
    } catch (Throwable $exception) {
    }

    return calendar_get_year_by_value($year);
}

function calendar_delete_year(int $yearId): bool
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM uso_calendar_years WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $yearId]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function calendar_list_holidays(int $yearId): array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, calendar_year_id, holiday_date, holiday_type, holiday_label, created_at, updated_at
             FROM uso_calendar_holidays
             WHERE calendar_year_id = :year_id
             ORDER BY holiday_date ASC'
        );
        $stmt->execute(['year_id' => $yearId]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $row['holiday_type'] = calendar_normalize_holiday_type((string)($row['holiday_type'] ?? 'nacional'));
            $items[] = $row;
        }

        return $items;
    } catch (Throwable $exception) {
        return [];
    }
}

function calendar_add_holiday(int $yearId, string $date, string $label = '', string $holidayType = 'nacional'): bool
{
    ensure_calendar_tables();

    if ($yearId <= 0) {
        return false;
    }

    $safeDate = trim($date);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $safeDate) !== 1) {
        return false;
    }

    $holidayType = calendar_normalize_holiday_type($holidayType);
    $label = mb_substr(trim($label), 0, 160, 'UTF-8');
    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO uso_calendar_holidays (calendar_year_id, holiday_date, holiday_type, holiday_label)
             VALUES (:year_id, :holiday_date, :holiday_type, :holiday_label)'
        );
        $stmt->execute([
            'year_id' => $yearId,
            'holiday_date' => $safeDate,
            'holiday_type' => $holidayType,
            'holiday_label' => $label,
        ]);
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

function calendar_delete_holiday(int $yearId, int $holidayId): bool
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0 || $holidayId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'DELETE FROM uso_calendar_holidays
             WHERE id = :id AND calendar_year_id = :year_id
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $holidayId,
            'year_id' => $yearId,
        ]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function calendar_list_rotations(int $yearId): array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, calendar_year_id, rotation_name, weeks_cycle, is_active, is_default, created_at, updated_at
             FROM uso_calendar_rotations
             WHERE calendar_year_id = :year_id
             ORDER BY is_default DESC, rotation_name ASC, id ASC'
        );
        $stmt->execute(['year_id' => $yearId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    } catch (Throwable $exception) {
        return [];
    }
}

function calendar_get_rotation(int $rotationId): ?array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $rotationId <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, calendar_year_id, rotation_name, weeks_cycle, is_active, is_default, created_at, updated_at
             FROM uso_calendar_rotations
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $rotationId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    } catch (Throwable $exception) {
        return null;
    }
}

function calendar_get_rotation_pattern(int $rotationId): array
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $rotationId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT week_index, day_of_week, is_working
             FROM uso_calendar_rotation_pattern
             WHERE rotation_id = :rotation_id
             ORDER BY week_index ASC, day_of_week ASC'
        );
        $stmt->execute(['rotation_id' => $rotationId]);
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $pattern = [];
        foreach ($rows as $row) {
            $weekIndex = (int)($row['week_index'] ?? 0);
            $dayOfWeek = (int)($row['day_of_week'] ?? 0);
            if ($weekIndex < 1 || $dayOfWeek < 1 || $dayOfWeek > 7) {
                continue;
            }

            $pattern[$weekIndex][$dayOfWeek] = (int)($row['is_working'] ?? 0) === 1 ? 1 : 0;
        }

        return $pattern;
    } catch (Throwable $exception) {
        return [];
    }
}

function calendar_normalize_pattern(array $patternInput, int $weeksCycle): array
{
    $normalized = [];

    for ($week = 1; $week <= $weeksCycle; $week++) {
        $normalized[$week] = [];
        for ($day = 1; $day <= 7; $day++) {
            $value = $patternInput[$week][$day] ?? $patternInput[(string)$week][(string)$day] ?? 0;
            $normalized[$week][$day] = ((int)$value === 1) ? 1 : 0;
        }
    }

    return $normalized;
}

function calendar_save_rotation(
    int $yearId,
    ?int $rotationId,
    string $name,
    int $weeksCycle,
    bool $isActive,
    bool $isDefault,
    array $patternInput
): ?int {
    ensure_calendar_tables();

    if ($yearId <= 0 || $weeksCycle < 1 || $weeksCycle > 4) {
        return null;
    }

    $safeName = mb_substr(trim($name), 0, 160, 'UTF-8');
    if ($safeName === '') {
        return null;
    }

    $pattern = calendar_normalize_pattern($patternInput, $weeksCycle);

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $pdo->beginTransaction();

        if ($isDefault) {
            $resetDefaultStmt = $pdo->prepare('UPDATE uso_calendar_rotations SET is_default = 0 WHERE calendar_year_id = :year_id');
            $resetDefaultStmt->execute(['year_id' => $yearId]);
        }

        $savedRotationId = $rotationId !== null && $rotationId > 0 ? $rotationId : null;

        if ($savedRotationId !== null) {
            $updateStmt = $pdo->prepare(
                'UPDATE uso_calendar_rotations
                 SET rotation_name = :rotation_name,
                     weeks_cycle = :weeks_cycle,
                     is_active = :is_active,
                     is_default = :is_default
                 WHERE id = :id AND calendar_year_id = :year_id
                 LIMIT 1'
            );
            $updateStmt->execute([
                'rotation_name' => $safeName,
                'weeks_cycle' => $weeksCycle,
                'is_active' => $isActive ? 1 : 0,
                'is_default' => $isDefault ? 1 : 0,
                'id' => $savedRotationId,
                'year_id' => $yearId,
            ]);

            if ($updateStmt->rowCount() < 1) {
                $pdo->rollBack();
                return null;
            }

            $deletePatternStmt = $pdo->prepare('DELETE FROM uso_calendar_rotation_pattern WHERE rotation_id = :rotation_id');
            $deletePatternStmt->execute(['rotation_id' => $savedRotationId]);
        } else {
            $insertStmt = $pdo->prepare(
                'INSERT INTO uso_calendar_rotations (calendar_year_id, rotation_name, weeks_cycle, is_active, is_default)
                 VALUES (:year_id, :rotation_name, :weeks_cycle, :is_active, :is_default)'
            );
            $insertStmt->execute([
                'year_id' => $yearId,
                'rotation_name' => $safeName,
                'weeks_cycle' => $weeksCycle,
                'is_active' => $isActive ? 1 : 0,
                'is_default' => $isDefault ? 1 : 0,
            ]);

            $savedRotationId = (int)$pdo->lastInsertId();
        }

        $insertPatternStmt = $pdo->prepare(
            'INSERT INTO uso_calendar_rotation_pattern (rotation_id, week_index, day_of_week, is_working)
             VALUES (:rotation_id, :week_index, :day_of_week, :is_working)'
        );

        for ($week = 1; $week <= $weeksCycle; $week++) {
            for ($day = 1; $day <= 7; $day++) {
                $insertPatternStmt->execute([
                    'rotation_id' => $savedRotationId,
                    'week_index' => $week,
                    'day_of_week' => $day,
                    'is_working' => $pattern[$week][$day],
                ]);
            }
        }

        $pdo->commit();
        return $savedRotationId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return null;
    }
}

function calendar_delete_rotation(int $yearId, int $rotationId): bool
{
    ensure_calendar_tables();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO || $yearId <= 0 || $rotationId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'DELETE FROM uso_calendar_rotations
             WHERE id = :rotation_id AND calendar_year_id = :year_id
             LIMIT 1'
        );
        $stmt->execute([
            'rotation_id' => $rotationId,
            'year_id' => $yearId,
        ]);

        return $stmt->rowCount() > 0;
    } catch (Throwable $exception) {
        return false;
    }
}

function calendar_build_editor_grid(int $year, int $weeksCycle, array $pattern): array
{
    $weeks = [];

    if ($weeksCycle < 1) {
        return $weeks;
    }

    $firstDay = new DateTimeImmutable(sprintf('%04d-01-01', $year));
    $firstMonday = $firstDay->modify('monday this week');

    for ($week = 1; $week <= $weeksCycle; $week++) {
        $weekDays = [];
        for ($day = 1; $day <= 7; $day++) {
            $offset = (($week - 1) * 7) + ($day - 1);
            $date = $firstMonday->modify('+' . $offset . ' days');
            $weekDays[] = [
                'week' => $week,
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('d/m'),
                'isWorking' => (int)($pattern[$week][$day] ?? 0) === 1,
            ];
        }

        $weeks[] = [
            'week' => $week,
            'days' => $weekDays,
        ];
    }

    return $weeks;
}

function calendar_generate_calendar(int $year, int $rotationId): array
{
    ensure_calendar_tables();

    $rotation = calendar_get_rotation($rotationId);
    if ($rotation === null || (int)($rotation['weeks_cycle'] ?? 0) < 1) {
        return [];
    }

    $rotationYear = calendar_get_year_by_id((int)($rotation['calendar_year_id'] ?? 0));
    if ($rotationYear === null || (int)($rotationYear['year'] ?? 0) !== $year) {
        return [];
    }

    $weeksCycle = (int)$rotation['weeks_cycle'];
    $pattern = calendar_get_rotation_pattern($rotationId);
    $holidays = calendar_list_holidays((int)$rotation['calendar_year_id']);
    $holidayByDate = [];

    foreach ($holidays as $holiday) {
        if (!is_array($holiday)) {
            continue;
        }
        $date = (string)($holiday['holiday_date'] ?? '');
        if ($date === '') {
            continue;
        }

        $holidayByDate[$date] = [
            'label' => trim((string)($holiday['holiday_label'] ?? '')),
            'type' => calendar_normalize_holiday_type((string)($holiday['holiday_type'] ?? 'nacional')),
        ];
    }

    $firstDay = new DateTimeImmutable(sprintf('%04d-01-01', $year));
    $lastDay = new DateTimeImmutable(sprintf('%04d-12-31', $year));
    $firstMonday = $firstDay->modify('monday this week');

    $result = [];
    $current = $firstDay;

    while ($current <= $lastDay) {
        $date = $current->format('Y-m-d');

        if (array_key_exists($date, $holidayByDate)) {
            $holidayData = is_array($holidayByDate[$date]) ? $holidayByDate[$date] : ['label' => '', 'type' => 'nacional'];
            $result[] = [
                'fecha' => $date,
                'tipo' => 'festivo',
                'label_festivo' => (string)($holidayData['label'] ?? ''),
                'tipo_festivo' => calendar_normalize_holiday_type((string)($holidayData['type'] ?? 'nacional')),
            ];
            $current = $current->modify('+1 day');
            continue;
        }

        $daysFromBase = (int)$firstMonday->diff($current)->format('%a');
        $weekOffset = intdiv($daysFromBase, 7);
        $cycleWeekIndex = ($weekOffset % $weeksCycle) + 1;
        $dayOfWeek = (int)$current->format('N');
        $isWorking = (int)($pattern[$cycleWeekIndex][$dayOfWeek] ?? 0) === 1;

        $result[] = [
            'fecha' => $date,
            'tipo' => $isWorking ? 'trabajo' : 'descanso',
            'label_festivo' => null,
            'tipo_festivo' => null,
        ];

        $current = $current->modify('+1 day');
    }

    return $result;
}

function calendar_group_generated_by_month(array $generated): array
{
    $grouped = [];

    foreach ($generated as $item) {
        if (!is_array($item)) {
            continue;
        }

        $date = (string)($item['fecha'] ?? '');
        if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            continue;
        }

        $monthKey = substr($date, 0, 7);
        if (!isset($grouped[$monthKey])) {
            $grouped[$monthKey] = [];
        }

        $grouped[$monthKey][] = $item;
    }

    ksort($grouped);
    return $grouped;
}

function search_news_in_db(string $queryText, string $status = 'publicada'): array
{
    $search = normalize_search_text($queryText);
    if ($search === '') {
        return [];
    }

    $terms = array_values(array_filter(explode(' ', $search), static function (string $term): bool {
        return $term !== '';
    }));

    if ($terms === []) {
        return [];
    }

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        return [];
    }

    try {
        $selectFields = news_select_fields($pdo);

        $stmt = $pdo->prepare(
            'SELECT ' . $selectFields . '
             FROM noticias
             WHERE estado = :estado
             ORDER BY fecha_creaccion DESC, id DESC'
        );
        $stmt->bindValue(':estado', $status, PDO::PARAM_STR);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            return [];
        }

        $matches = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = (string)($row['titulo'] ?? '');
            $body = (string)($row['texto'] ?? '');
            $haystack = normalize_search_text($title . ' ' . strip_tags($body));

            if ($haystack === '') {
                continue;
            }

            $allTermsFound = true;
            foreach ($terms as $term) {
                if (mb_strpos($haystack, $term, 0, 'UTF-8') === false) {
                    $allTermsFound = false;
                    break;
                }
            }

            if ($allTermsFound) {
                $matches[] = map_news_row($row);
            }
        }

        return $matches;
    } catch (Throwable $exception) {
        return [];
    }
}

function normalize_search_text(string $value): string
{
    $text = mb_strtolower(trim($value), 'UTF-8');

    if (class_exists('Transliterator')) {
        $text = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC', $text);
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            $text = $converted;
        }
    }

    $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

    return $text;
}
