<?php
declare(strict_types=1);

/* ====================================================
   USO Admin Panel — Funciones auxiliares
   Extraídas de uso_admin.php para mejor mantenimiento.
   ==================================================== */

/* ── Constantes de sesión (declaradas aquí para evitar duplicados) ── */
if (!defined('ADMIN_SESSION_KEY')) {
    define('ADMIN_SESSION_KEY', 'uso_admin_user');
}
if (!defined('ADMIN_CSRF_KEY')) {
    define('ADMIN_CSRF_KEY', 'uso_admin_csrf');
}

/* ════════════════════════════════════════════════════
   INFRAESTRUCTURA: BASE DE DATOS Y REDIRECCIÓN
   ════════════════════════════════════════════════════ */

function admin_db(): PDO
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

function admin_redirect(array $params = []): void
{
    header('Location: ' . admin_url_from_params($params));
    exit;
}

/* ════════════════════════════════════════════════════
   CONSTRUCCIÓN DE URLS
   ════════════════════════════════════════════════════ */

function admin_url(
    string $section = 'home',
    string $view = '',
    ?int $editId = null,
    ?string $resetToken = null,
    ?string $resetEmail = null,
    ?string $registerToken = null,
    ?string $registerEmail = null
): string {
    $base = rtrim(BASE_URL, '/') . '/uso_admin';

    if ($section === 'reset') {
        if ($resetToken !== null && $resetToken !== '' && $resetEmail !== null && $resetEmail !== '') {
            return $base . '/reset/' . rawurlencode($resetToken) . '/' . rawurlencode($resetEmail);
        }
        return $base . '/reset';
    }

    if ($section === 'register') {
        if ($registerToken !== null && $registerToken !== '' && $registerEmail !== null && $registerEmail !== '') {
            return $base . '/register/' . rawurlencode($registerToken) . '/' . rawurlencode($registerEmail);
        }
        return $base . '/register';
    }

    if ($section === 'news') {
        if ($view === 'add') {
            return $base . '/news/add';
        }
        if ($view === 'manage') {
            if ($editId !== null && $editId > 0) {
                return $base . '/news/manage/edit/' . $editId;
            }
            return $base . '/news/manage';
        }
        return $base . '/news';
    }

    if ($section === 'documents') {
        if ($view === 'add')    return $base . '/documents/add';
        if ($view === 'manage') return $base . '/documents/manage';
        return $base . '/documents';
    }

    if ($section === 'images')   return $base . '/images';
    if ($section === 'calendar') return $base . '/calendar';
    if ($section === 'user')     return $base . '/user';
    if ($section === 'push')     return $base . '/push';

    return $base;
}

function admin_calendar_url(array $query = []): string
{
    $url = admin_url('calendar');
    if ($query === []) {
        return $url;
    }
    $normalized = [];
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') continue;
        $normalized[(string)$key] = (string)$value;
    }
    return $normalized !== [] ? $url . '?' . http_build_query($normalized) : $url;
}

function admin_url_from_params(array $params = []): string
{
    $section = trim((string)($params['section'] ?? 'home'));
    if (($params['reset'] ?? '') === '1') {
        $section = 'reset';
    }

    $newsView       = trim((string)($params['news_view'] ?? ''));
    $documentsView  = trim((string)($params['documents_view'] ?? ''));
    $editId         = isset($params['edit']) ? (int)$params['edit'] : null;
    $token          = trim((string)($params['token'] ?? ($params['reset_token'] ?? '')));
    $email          = trim((string)($params['email'] ?? ($params['reset_email'] ?? '')));
    $registerToken  = trim((string)($params['register_token'] ?? ''));
    $registerEmail  = trim((string)($params['register_email'] ?? ''));
    $calYearId      = isset($params['year_id']) ? (int)$params['year_id'] : 0;
    $calRotId       = isset($params['rotation_id']) ? (int)$params['rotation_id'] : 0;
    $calWeeksCycle  = isset($params['weeks_cycle']) ? (int)$params['weeks_cycle'] : 0;

    if (($params['register'] ?? '') === '1') {
        $section = 'register';
        if ($registerToken === '') $registerToken = $token;
        if ($registerEmail === '') $registerEmail = $email;
    }

    if ($section === 'calendar') {
        $query = [];
        if ($calYearId > 0)     $query['year_id']     = $calYearId;
        if ($calRotId > 0)      $query['rotation_id'] = $calRotId;
        if ($calWeeksCycle > 0) $query['weeks_cycle'] = $calWeeksCycle;
        return admin_calendar_url($query);
    }

    $view = '';
    if ($section === 'news')      $view = $newsView;
    if ($section === 'documents') $view = $documentsView;

    return admin_url($section, $view, $editId, $token, $email, $registerToken, $registerEmail);
}

function parse_admin_request(array $query): array
{
    $path = trim((string)($query['admin_path'] ?? ''), '/');
    $result = [
        'section'          => 'home',
        'news_view'        => '',
        'documents_view'   => '',
        'edit'             => 0,
        'is_reset_mode'    => false,
        'reset_email'      => '',
        'reset_token'      => '',
        'is_register_mode' => false,
        'register_email'   => '',
        'register_token'   => '',
    ];

    if ($path === '') return $result;

    $segments = array_values(
        array_filter(explode('/', $path), static fn (string $s): bool => $s !== '')
    );
    if ($segments === []) return $result;

    $first = urldecode($segments[0]);

    if ($first === 'news') {
        $result['section']  = 'news';
        $second = isset($segments[1]) ? urldecode($segments[1]) : '';
        if (in_array($second, ['add', 'manage'], true)) {
            $result['news_view'] = $second;
        }
        if ($second === 'manage'
            && isset($segments[2]) && urldecode($segments[2]) === 'edit'
            && isset($segments[3]) && ctype_digit($segments[3])) {
            $result['edit']      = (int)$segments[3];
            $result['news_view'] = 'manage';
        }
        return $result;
    }

    if ($first === 'documents') {
        $result['section'] = 'documents';
        $second = isset($segments[1]) ? urldecode($segments[1]) : '';
        if (in_array($second, ['add', 'manage'], true)) {
            $result['documents_view'] = $second;
        }
        return $result;
    }

    if ($first === 'images')   { $result['section'] = 'images';   return $result; }
    if ($first === 'calendar') { $result['section'] = 'calendar'; return $result; }
    if ($first === 'user')     { $result['section'] = 'user';     return $result; }
    if ($first === 'push')     { $result['section'] = 'push';     return $result; }

    if ($first === 'reset') {
        $result['section'] = 'reset';
        if (isset($segments[1], $segments[2])) {
            $result['is_reset_mode'] = true;
            $result['reset_token']   = urldecode($segments[1]);
            $result['reset_email']   = urldecode($segments[2]);
        }
        return $result;
    }

    if ($first === 'register') {
        $result['section'] = 'register';
        if (isset($segments[1], $segments[2])) {
            $result['is_register_mode'] = true;
            $result['register_token']   = urldecode($segments[1]);
            $result['register_email']   = urldecode($segments[2]);
        }
        return $result;
    }

    return $result;
}

/* ════════════════════════════════════════════════════
   AUTENTICACIÓN, SESIÓN Y SEGURIDAD
   ════════════════════════════════════════════════════ */

function admin_is_logged_in(): bool
{
    return isset($_SESSION[ADMIN_SESSION_KEY]) && is_array($_SESSION[ADMIN_SESSION_KEY]);
}

function admin_user(): array
{
    return $_SESSION[ADMIN_SESSION_KEY] ?? [];
}

function admin_set_flash(string $type, string $message): void
{
    $_SESSION['uso_admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_get_flash(): ?array
{
    $flash = $_SESSION['uso_admin_flash'] ?? null;
    unset($_SESSION['uso_admin_flash']);
    return is_array($flash) ? $flash : null;
}

function admin_csrf_token(): string
{
    if (empty($_SESSION[ADMIN_CSRF_KEY])) {
        $_SESSION[ADMIN_CSRF_KEY] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION[ADMIN_CSRF_KEY];
}

function admin_validate_csrf(?string $token): bool
{
    $session = $_SESSION[ADMIN_CSRF_KEY] ?? '';
    return is_string($token) && $token !== '' && is_string($session) && hash_equals($session, $token);
}

function admin_absolute_url(string $path): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host   = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return $scheme . '://' . $host . $path;
}

function admin_can_send_user_invites(): bool
{
    $user     = admin_user();
    $username = strtolower(trim((string)($user['username'] ?? '')));
    return admin_is_logged_in() && $username === 'ramiku';
}

/* ════════════════════════════════════════════════════
   RECUPERACIÓN DE CONTRASEÑA
   ════════════════════════════════════════════════════ */

function create_password_reset_request(string $email): void
{
    $safeEmail = trim($email);
    if ($safeEmail === '') return;

    $stmt = admin_db()->prepare(
        'SELECT id, username, email FROM uso_users WHERE LOWER(email) = LOWER(:email) LIMIT 1'
    );
    $stmt->execute(['email' => $safeEmail]);
    $user = $stmt->fetch();
    if (!is_array($user)) return;

    $expiresAt  = date('Y-m-d H:i:s', time() + 3600);
    $rawToken   = bin2hex(random_bytes(32));
    $tokenHash  = password_hash($rawToken, PASSWORD_DEFAULT);

    $invalidate = admin_db()->prepare(
        'UPDATE uso_password_resets SET used_at = NOW()
         WHERE user_id = :user_id AND used_at IS NULL AND expires_at > NOW()'
    );
    $invalidate->execute(['user_id' => (int)$user['id']]);

    $insert = admin_db()->prepare(
        'INSERT INTO uso_password_resets (user_id, email, token_hash, expires_at)
         VALUES (:user_id, :email, :token_hash, :expires_at)'
    );
    $insert->execute([
        'user_id'    => (int)$user['id'],
        'email'      => (string)$user['email'],
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt,
    ]);

    $resetLink = admin_absolute_url(admin_url('reset', '', null, $rawToken, (string)$user['email']));
    $subject = 'Recuperación de contraseña - Panel USO';
    $message = "Hola " . (string)$user['username'] . ",\n\n"
        . "Hemos recibido una solicitud para restaurar tu contraseña.\n"
        . "Usa este enlace para establecer una nueva (caduca en 60 minutos):\n\n"
        . $resetLink . "\n\n"
        . "Si no solicitaste este cambio, ignora este correo.\n";

    send_email_message((string)$user['email'], $subject, $message, [
        'from_email' => MAIL_FROM_EMAIL,
        'from_name'  => 'USO Panel',
    ]);
}

function find_password_reset(string $email, string $rawToken): ?array
{
    $safeEmail = trim($email);
    if ($safeEmail === '' || trim($rawToken) === '') return null;

    $stmt = admin_db()->prepare(
        'SELECT pr.id, pr.user_id, pr.token_hash, pr.expires_at, pr.used_at, u.email
         FROM uso_password_resets pr
         INNER JOIN uso_users u ON u.id = pr.user_id
         WHERE LOWER(pr.email) = LOWER(:email)
         ORDER BY pr.id DESC LIMIT 8'
    );
    $stmt->execute(['email' => $safeEmail]);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) return null;

    $now = time();
    foreach ($rows as $row) {
        if (!is_array($row) || !empty($row['used_at'])) continue;
        $exp = strtotime((string)($row['expires_at'] ?? ''));
        if ($exp === false || $exp < $now) continue;
        if (password_verify($rawToken, (string)($row['token_hash'] ?? ''))) return $row;
    }
    return null;
}

function mark_reset_as_used(int $resetId): void
{
    $stmt = admin_db()->prepare(
        'UPDATE uso_password_resets SET used_at = NOW() WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $resetId]);
}

/* ════════════════════════════════════════════════════
   INVITACIONES DE USUARIO
   ════════════════════════════════════════════════════ */

function ensure_user_invites_table(): void
{
    admin_db()->exec(
        'CREATE TABLE IF NOT EXISTS uso_user_invites (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_by_user_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_expires_at (expires_at),
            INDEX idx_used_at (used_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function create_user_invitation_request(string $email, int $createdByUserId): void
{
    $safeEmail = trim($email);
    if ($safeEmail === '' || filter_var($safeEmail, FILTER_VALIDATE_EMAIL) === false) return;

    ensure_user_invites_table();

    $exists = admin_db()->prepare(
        'SELECT id FROM uso_users WHERE LOWER(email) = LOWER(:email) LIMIT 1'
    );
    $exists->execute(['email' => $safeEmail]);
    if ($exists->fetch()) return;

    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = password_hash($rawToken, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 172800);

    $invalidate = admin_db()->prepare(
        'UPDATE uso_user_invites SET used_at = NOW()
         WHERE LOWER(email) = LOWER(:email) AND used_at IS NULL AND expires_at > NOW()'
    );
    $invalidate->execute(['email' => $safeEmail]);

    $insert = admin_db()->prepare(
        'INSERT INTO uso_user_invites (email, token_hash, expires_at, created_by_user_id)
         VALUES (:email, :token_hash, :expires_at, :created_by_user_id)'
    );
    $insert->execute([
        'email'               => $safeEmail,
        'token_hash'          => $tokenHash,
        'expires_at'          => $expiresAt,
        'created_by_user_id'  => $createdByUserId > 0 ? $createdByUserId : null,
    ]);

    $registerLink = admin_absolute_url(admin_url('register', '', null, null, null, $rawToken, $safeEmail));
    $subject = 'Invitación de alta - Panel USO';
    $message = "Hola,\n\n"
        . "Has recibido una invitación para darte de alta en el Panel de Administración USO.\n"
        . "Accede al siguiente enlace y completa tu usuario y contraseña (caduca en 48 horas):\n\n"
        . $registerLink . "\n\n"
        . "Si no esperabas este correo, puedes ignorarlo.\n";

    send_email_message($safeEmail, $subject, $message, [
        'from_email' => MAIL_FROM_EMAIL,
        'from_name'  => 'USO Panel',
    ]);
}

function find_user_invitation(string $email, string $rawToken): ?array
{
    $safeEmail = trim($email);
    if ($safeEmail === '' || trim($rawToken) === '') return null;

    ensure_user_invites_table();

    $stmt = admin_db()->prepare(
        'SELECT id, email, token_hash, expires_at, used_at
         FROM uso_user_invites
         WHERE LOWER(email) = LOWER(:email)
         ORDER BY id DESC LIMIT 8'
    );
    $stmt->execute(['email' => $safeEmail]);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) return null;

    $now = time();
    foreach ($rows as $row) {
        if (!is_array($row) || !empty($row['used_at'])) continue;
        $exp = strtotime((string)($row['expires_at'] ?? ''));
        if ($exp === false || $exp < $now) continue;
        if (password_verify($rawToken, (string)($row['token_hash'] ?? ''))) return $row;
    }
    return null;
}

function mark_invite_as_used(int $inviteId): void
{
    ensure_user_invites_table();
    $stmt = admin_db()->prepare(
        'UPDATE uso_user_invites SET used_at = NOW() WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $inviteId]);
}

/* ════════════════════════════════════════════════════
   UTILIDADES DE FECHA Y HTML
   ════════════════════════════════════════════════════ */

function normalize_datetime_input(string $input): ?string
{
    $value = trim($input);
    if ($value === '') return null;

    $normalized = str_replace('T', ' ', $value);
    if (strlen($normalized) === 16) $normalized .= ':00';

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalized);
    if (!$dt) return null;

    return $dt->format('Y-m-d H:i:s');
}

function datetime_for_input(?string $dateTime): string
{
    if (!$dateTime) return date('Y-m-d\TH:i');
    $ts = strtotime($dateTime);
    if ($ts === false) return date('Y-m-d\TH:i');
    return date('Y-m-d\TH:i', $ts);
}

function sanitize_news_html(string $html): string
{
    // Normalize <div> line breaks inserted by contenteditable (Chrome default)
    $html = preg_replace('/<div\b[^>]*>/i', '<p>', $html) ?? $html;
    $html = str_ireplace('</div>', '</p>', $html);

    $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><span>';
    $clean = strip_tags($html, $allowedTags);

    $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;

    $clean = preg_replace_callback('/\sstyle\s*=\s*("|\')(.*?)\1/i', static function (array $m): string {
        $rawStyle = trim((string)($m[2] ?? ''));
        if ($rawStyle === '') return '';

        $safeStyles = [];
        foreach (array_filter(array_map('trim', explode(';', $rawStyle)), static fn (string $s): bool => $s !== '') as $decl) {
            $parts = explode(':', $decl, 2);
            if (count($parts) !== 2) continue;

            $property = strtolower(trim($parts[0]));
            $value    = strtolower(trim($parts[1]));

            if ($property === 'font-size') {
                // Allow px/pt units and rem units (used by the RTE toolbar)
                if (preg_match('/^(8|9|1[0-9]|2[0-9]|3[0-6])(px|pt)$/', $value) === 1
                    || preg_match('/^\d+(\.\d+)?rem$/', $value) === 1) {
                    $safeStyles[] = 'font-size:' . $value;
                }
                continue;
            }
            if ($property === 'color' || $property === 'background-color') {
                $isHex = preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/', $value) === 1;
                $isRgb = preg_match('/^rgba?\(\s*[0-9\s.,%]+\)$/', $value) === 1;
                if ($isHex || $isRgb) $safeStyles[] = $property . ':' . $value;
            }
        }

        return $safeStyles !== [] ? ' style="' . implode(';', $safeStyles) . '"' : '';
    }, $clean) ?? $clean;

    $clean = preg_replace_callback('/href\s*=\s*("|\')(.*?)\1/i', static function (array $m): string {
        $url = trim($m[2]);
        if ($url === '') return 'href="#"';
        if (preg_match('/^(https?:\/\/|mailto:|tel:|#|\/)/i', $url) !== 1) return 'href="#"';
        return 'href=' . $m[1] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $m[1];
    }, $clean) ?? $clean;

    return trim($clean);
}

function format_size(int $bytes): string
{
    if ($bytes < 1024)        return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return number_format($bytes / (1024 * 1024), 2, ',', '.') . ' MB';
}

/* ════════════════════════════════════════════════════
   SISTEMA DE ARCHIVOS Y SUBIDAS
   ════════════════════════════════════════════════════ */

function admin_absolute_assets_path(string $relative): string
{
    return __DIR__ . '/../../public/assets/' . ltrim($relative, '/');
}

function ensure_directory(string $relativeDir): void
{
    $path = admin_absolute_assets_path($relativeDir);
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('No se pudo preparar la carpeta: ' . $relativeDir);
    }
}

function delete_asset_file(string $relativePath, array $allowedRoots): bool
{
    $safePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if ($safePath === '' || str_contains($safePath, '..')) return false;

    $isAllowed = false;
    foreach ($allowedRoots as $root) {
        $normalizedRoot = rtrim(str_replace('\\', '/', ltrim($root, '/')), '/');
        if ($safePath === $normalizedRoot || str_starts_with($safePath, $normalizedRoot . '/')) {
            $isAllowed = true;
            break;
        }
    }
    if (!$isAllowed) return false;

    $absolutePath = admin_absolute_assets_path($safePath);
    if (!is_file($absolutePath)) return false;

    return unlink($absolutePath);
}

/* ── Normalización de archivos subidos ── */

function normalize_uploaded_files(?array $files): array
{
    if (!is_array($files) || !isset($files['name'], $files['type'], $files['tmp_name'], $files['error'], $files['size'])) {
        return [];
    }
    if (!is_array($files['name'])) return [$files];

    $normalized = [];
    for ($i = 0; $i < count($files['name']); $i++) {
        $normalized[] = [
            'name'     => (string)($files['name'][$i] ?? ''),
            'type'     => (string)($files['type'][$i] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
            'error'    => (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size'     => (int)($files['size'][$i] ?? 0),
        ];
    }
    return $normalized;
}

function merge_uploaded_files(?array ...$groups): array
{
    $merged = [];
    foreach ($groups as $group) {
        foreach (normalize_uploaded_files($group) as $file) {
            $merged[] = $file;
        }
    }
    return $merged;
}

function is_normalized_uploaded_file_list(array $files): bool
{
    if ($files === []) return true;
    $first = reset($files);
    return is_array($first)
        && array_key_exists('tmp_name', $first)
        && array_key_exists('error', $first)
        && array_key_exists('name', $first);
}

function normalize_news_upload_group(?array $files): array
{
    if (!is_array($files)) return [];
    if (is_normalized_uploaded_file_list($files)) return $files;
    return normalize_uploaded_files($files);
}

/* ── Documentos ── */

function normalize_document_target(string $target): string
{
    $safe = trim($target);
    return $safe === 'files/calendarios' ? 'files/calendarios' : 'files';
}

function handle_document_upload(?array $file, string $targetFolder): ?string
{
    if (!is_array($file) || !isset($file['error'])) return null;
    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE)  return null;
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el documento.');
    }

    $tmpPath      = (string)($file['tmp_name'] ?? '');
    $originalName = (string)($file['name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $originalName === '') {
        throw new RuntimeException('Archivo de documento no válido.');
    }

    $extension        = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Formato de documento no permitido.');
    }

    $targetFolder = normalize_document_target($targetFolder);
    ensure_directory($targetFolder);

    $fileName   = bin2hex(random_bytes(12)) . '.' . $extension;
    $targetPath = admin_absolute_assets_path($targetFolder . '/' . $fileName);
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('No se pudo guardar el documento.');
    }

    return $targetFolder . '/' . $fileName;
}

/* ════════════════════════════════════════════════════
   NOTICIAS — IMAGEN PRINCIPAL (CARD)
   ════════════════════════════════════════════════════ */

function ensure_news_card_image_column(): void
{
    $check = admin_db()->query("SHOW COLUMNS FROM noticias LIKE 'ruta_imagen'");
    if ($check && !$check->fetch()) {
        admin_db()->exec(
            "ALTER TABLE noticias
             ADD COLUMN ruta_imagen VARCHAR(255) NOT NULL DEFAULT 'img/placeholder.svg' AFTER texto"
        );
    }
    admin_db()->exec(
        "UPDATE noticias SET ruta_imagen = 'img/placeholder.svg'
         WHERE ruta_imagen IS NULL OR TRIM(ruta_imagen) = ''"
    );
}

function normalize_news_card_image_path(?string $path): string
{
    $cleanPath = trim((string)$path);
    if ($cleanPath === '' || str_contains($cleanPath, '..')) return 'img/placeholder.svg';
    $cleanPath = ltrim(str_replace('\\', '/', $cleanPath), '/');
    if ($cleanPath === '' || !str_starts_with($cleanPath, 'img/')) return 'img/placeholder.svg';
    return $cleanPath;
}

function is_default_news_card_image_path(string $path): bool
{
    return normalize_news_card_image_path($path) === 'img/placeholder.svg';
}

function upload_news_card_image(?array $file): ?string
{
    if (!is_array($file) || !isset($file['error'])) return null;
    if ((int)$file['error'] === UPLOAD_ERR_NO_FILE)  return null;

    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'La imagen supera el límite de tamaño permitido por el servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el tamaño máximo indicado en el formulario.',
        UPLOAD_ERR_PARTIAL    => 'La imagen se subió parcialmente. Inténtalo de nuevo.',
        UPLOAD_ERR_NO_TMP_DIR => 'No se encontró la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir la imagen en el disco del servidor.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida.',
    ];
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        $msg = $errorMessages[(int)$file['error']] ?? 'No se pudo subir la imagen principal de la noticia.';
        throw new RuntimeException($msg);
    }

    $tmpPath      = (string)($file['tmp_name'] ?? '');
    $originalName = trim((string)($file['name'] ?? ''));
    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $originalName === '') {
        throw new RuntimeException('Archivo de imagen principal no válido.');
    }

    if (filesize($tmpPath) > 4 * 1024 * 1024) {
        throw new RuntimeException('La imagen de portada supera el máximo de 4 MB.');
    }

    $extension        = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Formato de imagen principal no permitido.');
    }

    if ($extension !== 'svg') {
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath);
                if (is_string($detected)) $mimeType = $detected;
                finfo_close($finfo);
            }
        }
        if ($mimeType !== '' && stripos($mimeType, 'image/') !== 0) {
            throw new RuntimeException('El archivo de imagen principal no tiene un MIME válido.');
        }
    }

    ensure_directory('img/noticias/portada');

    $fileName     = bin2hex(random_bytes(16)) . '.' . $extension;
    $relativePath = 'img/noticias/portada/' . $fileName;
    $targetPath   = admin_absolute_assets_path($relativePath);

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('No se pudo guardar la imagen principal de la noticia.');
    }

    return $relativePath;
}

/* ════════════════════════════════════════════════════
   NOTICIAS — ADJUNTOS
   ════════════════════════════════════════════════════ */

function ensure_news_attachments_table(): void
{
    admin_db()->exec(
        "CREATE TABLE IF NOT EXISTS noticias_adjuntos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            noticia_id BIGINT UNSIGNED NOT NULL,
            tipo ENUM('imagen','documento') NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            ruta_archivo VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_noticia_id (noticia_id),
            INDEX idx_tipo (tipo),
            CONSTRAINT fk_noticias_adjuntos_noticia
                FOREIGN KEY (noticia_id) REFERENCES noticias(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function upload_news_attachment_file(array $file, string $type): ?array
{
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir uno de los adjuntos.');
    }

    $tmpPath      = (string)($file['tmp_name'] ?? '');
    $originalName = trim((string)($file['name'] ?? ''));
    if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $originalName === '') {
        throw new RuntimeException('Archivo adjunto no válido.');
    }

    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $mimeType  = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_file($finfo, $tmpPath);
            if (is_string($detected)) $mimeType = $detected;
            finfo_close($finfo);
        }
    }

    if ($type === 'imagen') {
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new RuntimeException('Formato de imagen no permitido.');
        }
        if ($mimeType !== '' && stripos($mimeType, 'image/') !== 0) {
            throw new RuntimeException('El archivo de imagen no tiene un MIME válido.');
        }
    } else {
        if (!in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar'], true)) {
            throw new RuntimeException('Formato de documento no permitido.');
        }
    }

    ensure_directory('files/noticias');

    $uniqueName   = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
    $relativePath = 'files/noticias/' . $uniqueName;
    $targetPath   = admin_absolute_assets_path($relativePath);
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        throw new RuntimeException('No se pudo guardar el archivo adjunto.');
    }

    return ['nombre_original' => $originalName, 'ruta_archivo' => $relativePath, 'mime_type' => $mimeType];
}

function store_news_attachments_from_request(int $newsId, ?array $imageFiles, ?array $documentFiles): array
{
    if ($newsId <= 0) {
        return ['stored' => 0, 'stored_images' => 0, 'stored_documents' => 0, 'errors' => []];
    }

    ensure_news_attachments_table();

    $insertStmt = admin_db()->prepare(
        'INSERT INTO noticias_adjuntos (noticia_id, tipo, nombre_original, ruta_archivo, mime_type)
         VALUES (:noticia_id, :tipo, :nombre_original, :ruta_archivo, :mime_type)'
    );

    $stored = $storedImages = $storedDocuments = 0;
    $errors = [];

    foreach (['imagen' => normalize_news_upload_group($imageFiles), 'documento' => normalize_news_upload_group($documentFiles)] as $type => $files) {
        foreach ($files as $file) {
            try {
                $uploaded = upload_news_attachment_file($file, $type);
                if ($uploaded === null) continue;

                $insertStmt->execute([
                    'noticia_id'      => $newsId,
                    'tipo'            => $type,
                    'nombre_original' => (string)$uploaded['nombre_original'],
                    'ruta_archivo'    => (string)$uploaded['ruta_archivo'],
                    'mime_type'       => (string)$uploaded['mime_type'],
                ]);

                $stored++;
                if ($type === 'imagen') $storedImages++; else $storedDocuments++;
            } catch (Throwable $ex) {
                $errors[] = $ex->getMessage();
            }
        }
    }

    return ['stored' => $stored, 'stored_images' => $storedImages, 'stored_documents' => $storedDocuments, 'errors' => $errors];
}

function fetch_news_attachments_for_admin(int $newsId): array
{
    if ($newsId <= 0) return [];
    ensure_news_attachments_table();

    $stmt = admin_db()->prepare(
        'SELECT id, noticia_id, tipo, nombre_original, ruta_archivo, mime_type, created_at
         FROM noticias_adjuntos WHERE noticia_id = :news_id
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute(['news_id' => $newsId]);
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetch_news_attachment_by_id_for_admin(int $attachmentId, int $newsId): ?array
{
    if ($attachmentId <= 0 || $newsId <= 0) return null;
    ensure_news_attachments_table();

    $stmt = admin_db()->prepare(
        'SELECT id, noticia_id, tipo, nombre_original, ruta_archivo
         FROM noticias_adjuntos WHERE id = :attachment_id AND noticia_id = :news_id LIMIT 1'
    );
    $stmt->execute(['attachment_id' => $attachmentId, 'news_id' => $newsId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function fetch_news_attachment_paths_for_admin(int $newsId): array
{
    if ($newsId <= 0) return [];
    ensure_news_attachments_table();

    $stmt = admin_db()->prepare(
        'SELECT ruta_archivo FROM noticias_adjuntos WHERE noticia_id = :news_id'
    );
    $stmt->execute(['news_id' => $newsId]);
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) return [];

    $paths = [];
    foreach ($rows as $row) {
        $p = trim((string)($row['ruta_archivo'] ?? ''));
        if ($p !== '') $paths[] = $p;
    }
    return $paths;
}

function delete_news_with_attachments(int $newsId): array
{
    if ($newsId <= 0) throw new RuntimeException('Noticia no válida.');

    ensure_news_attachments_table();

    $attachmentPaths = fetch_news_attachment_paths_for_admin($newsId);

    admin_db()->beginTransaction();
    try {
        $del1 = admin_db()->prepare('DELETE FROM noticias_adjuntos WHERE noticia_id = :news_id');
        $del1->execute(['news_id' => $newsId]);

        $del2 = admin_db()->prepare('DELETE FROM noticias WHERE id = :id LIMIT 1');
        $del2->execute(['id' => $newsId]);

        if ($del2->rowCount() < 1) throw new RuntimeException('La noticia no existe o no pudo eliminarse.');

        admin_db()->commit();
    } catch (Throwable $ex) {
        if (admin_db()->inTransaction()) admin_db()->rollBack();
        throw $ex;
    }

    $deletedFiles = $failedFiles = 0;
    foreach ($attachmentPaths as $rp) {
        delete_asset_file((string)$rp, ['files/noticias']) ? $deletedFiles++ : $failedFiles++;
    }
    // Las imágenes de portada solo se borran desde la sección Imágenes, no al eliminar la noticia.

    return ['deleted_files' => $deletedFiles, 'failed_files' => $failedFiles];
}

/* ════════════════════════════════════════════════════
   CONSULTAS PRINCIPALES DE DATOS
   ════════════════════════════════════════════════════ */

function fetch_news_items_for_admin(): array
{
    ensure_news_card_image_column();
    $stmt = admin_db()->query(
        'SELECT id, fecha_creaccion, titulo, texto, ruta_imagen, estado
         FROM noticias ORDER BY fecha_creaccion DESC, id DESC'
    );
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}

function fetch_news_item_by_id(int $id): ?array
{
    ensure_news_card_image_column();
    $stmt = admin_db()->prepare(
        'SELECT id, fecha_creaccion, titulo, texto, ruta_imagen, estado
         FROM noticias WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function fetch_documents_for_admin(): array
{
    $stmt = admin_db()->query(
        'SELECT id, display_name, file_path, folder, created_at
         FROM uso_documents ORDER BY created_at DESC, id DESC'
    );
    $rows = $stmt->fetchAll();
    if (!is_array($rows)) return [];

    $items = [];
    foreach ($rows as $row) {
        $relativePath = (string)($row['file_path'] ?? '');
        $absolutePath = admin_absolute_assets_path($relativePath);
        $items[] = [
            'id'          => (int)($row['id'] ?? 0),
            'displayName' => (string)($row['display_name'] ?? ''),
            'relativePath'=> $relativePath,
            'folder'      => (string)($row['folder'] ?? 'files'),
            'size'        => is_file($absolutePath) ? (int)filesize($absolutePath) : 0,
            'modified'    => is_file($absolutePath) ? (int)filemtime($absolutePath) : 0,
            'exists'      => is_file($absolutePath),
            'createdAt'   => (string)($row['created_at'] ?? ''),
        ];
    }

    // Ordenar: primero por carpeta alfabéticamente (files < files/calendarios),
    // luego por nombre de fichero alfabéticamente dentro de cada carpeta
    usort($items, static function (array $a, array $b): int {
        $folderCmp = strcasecmp($a['folder'], $b['folder']);
        if ($folderCmp !== 0) return $folderCmp;
        return strcasecmp($a['displayName'], $b['displayName']);
    });

    return $items;
}

function fetch_news_images_for_admin(): array
{
    $folder   = 'img/noticias/portada';
    $absolute = admin_absolute_assets_path($folder);
    if (!is_dir($absolute)) return [];

    $entries = scandir($absolute);
    if (!is_array($entries)) return [];

    $items = [];
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $fullPath = $absolute . '/' . $entry;
        if (!is_file($fullPath)) continue;
        $items[] = [
            'name'         => $entry,
            'relativePath' => $folder . '/' . $entry,
            'size'         => (int)filesize($fullPath),
            'modified'     => (int)filemtime($fullPath),
        ];
    }

    usort($items, static fn (array $a, array $b): int => ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0));
    return $items;
}

function fetch_admin_users_for_management(): array
{
    $stmt = admin_db()->query(
        'SELECT id, username, email, created_at FROM uso_users ORDER BY created_at DESC, id DESC'
    );
    $rows = $stmt->fetchAll();
    return is_array($rows) ? $rows : [];
}
