<?php
declare(strict_types=1);

/* ====================================================
   USO Admin Panel — Manejadores de acciones POST
   Incluido desde uso_admin.php; comparte su ámbito.
   ==================================================== */

$action = trim((string)($_POST['action'] ?? ''));

/* ────────────────────────────────────────────────────
   Acciones PÚBLICAS (no requieren sesión activa)
   ──────────────────────────────────────────────────── */

if ($action === 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errorMessage = 'Debes indicar usuario y contraseña.';
    } else {
        $stmt = admin_db()->prepare(
            'SELECT id, username, password, email FROM uso_users WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $userRow = $stmt->fetch();

        if (is_array($userRow) && password_verify($password, (string)($userRow['password'] ?? ''))) {
            $_SESSION[ADMIN_SESSION_KEY] = [
                'id'       => (int)$userRow['id'],
                'username' => (string)$userRow['username'],
                'email'    => (string)$userRow['email'],
            ];
            admin_set_flash('success', 'Sesión iniciada correctamente.');
            admin_redirect();
        }

        $errorMessage = 'Credenciales incorrectas.';
    }
}

if ($action === 'request_password_reset') {
    $email = trim((string)($_POST['email'] ?? ''));

    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errorMessage = 'Introduce un correo electrónico válido.';
    } else {
        create_password_reset_request($email);
        admin_set_flash('success', 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.');
        admin_redirect();
    }
}

if ($action === 'reset_password') {
    $email           = trim((string)($_POST['reset_email'] ?? ''));
    $token           = trim((string)($_POST['reset_token'] ?? ''));
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $confirmPassword === '') {
        $errorMessage = 'Debes completar los campos de nueva contraseña.';
    } elseif (mb_strlen($newPassword, 'UTF-8') < 8) {
        $errorMessage = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $errorMessage = 'La confirmación no coincide con la nueva contraseña.';
    } else {
        $resetRow = find_password_reset($email, $token);
        if (!is_array($resetRow)) {
            $errorMessage = 'El enlace de recuperación no es válido o ha caducado.';
        } else {
            $stmt = admin_db()->prepare(
                'UPDATE uso_users SET password = :password WHERE id = :id LIMIT 1'
            );
            $stmt->execute([
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id'       => (int)$resetRow['user_id'],
            ]);
            mark_reset_as_used((int)$resetRow['id']);
            admin_set_flash('success', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
            admin_redirect();
        }
    }
}

if ($action === 'complete_registration') {
    $email           = trim((string)($_POST['register_email'] ?? ''));
    $token           = trim((string)($_POST['register_token'] ?? ''));
    $username        = trim((string)($_POST['register_username'] ?? ''));
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || $newPassword === '' || $confirmPassword === '') {
        $errorMessage = 'Debes completar usuario y contraseña.';
    } elseif (preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $username) !== 1) {
        $errorMessage = 'El usuario debe tener entre 3 y 40 caracteres (letras, números, guiones, puntos o subrayado).';
    } elseif (mb_strlen($newPassword, 'UTF-8') < 8) {
        $errorMessage = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $errorMessage = 'La confirmación no coincide con la contraseña.';
    } else {
        $inviteRow = find_user_invitation($email, $token);

        if (!is_array($inviteRow)) {
            $errorMessage = 'El enlace de alta no es válido o ha caducado.';
        } else {
            $byEmail = admin_db()->prepare(
                'SELECT id FROM uso_users WHERE LOWER(email) = LOWER(:email) LIMIT 1'
            );
            $byEmail->execute(['email' => $email]);

            if ($byEmail->fetch()) {
                $errorMessage = 'Ya existe un usuario registrado con ese correo electrónico.';
            } else {
                $byUser = admin_db()->prepare(
                    'SELECT id FROM uso_users WHERE LOWER(username) = LOWER(:username) LIMIT 1'
                );
                $byUser->execute(['username' => $username]);

                if ($byUser->fetch()) {
                    $errorMessage = 'Ese nombre de usuario ya está en uso.';
                } else {
                    $insert = admin_db()->prepare(
                        'INSERT INTO uso_users (username, password, email)
                         VALUES (:username, :password, :email)'
                    );
                    $insert->execute([
                        'username' => $username,
                        'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'email'    => $email,
                    ]);
                    mark_invite_as_used((int)$inviteRow['id']);
                    admin_set_flash('success', 'Tu usuario se ha creado correctamente. Ya puedes iniciar sesión.');
                    admin_redirect();
                }
            }
        }
    }
}

/* ────────────────────────────────────────────────────
   Acciones AUTENTICADAS (requieren sesión + CSRF)
   ──────────────────────────────────────────────────── */

if (!admin_is_logged_in()) {
    return; // No seguir si no hay sesión
}

if (!admin_validate_csrf($_POST['csrf_token'] ?? null)) {
    admin_set_flash('error', 'Token de seguridad inválido. Vuelve a intentarlo.');
    admin_redirect();
}

/* ── Sesión ── */

if ($action === 'logout') {
    unset($_SESSION[ADMIN_SESSION_KEY]);
    admin_set_flash('success', 'Sesión cerrada.');
    admin_redirect();
}

/* ── Contraseña ── */

if ($action === 'change_password') {
    $redirectSection    = trim((string)($_POST['redirect_section'] ?? 'user'));
    if (!in_array($redirectSection, ['home', 'news', 'documents', 'images', 'calendar', 'user', 'push'], true)) {
        $redirectSection = 'user';
    }

    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $currentUser     = admin_user();
    $currentUserId   = (int)($currentUser['id'] ?? 0);

    if ($currentUserId <= 0) {
        admin_set_flash('error', 'No se pudo validar el usuario activo.');
        admin_redirect();
    }

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        admin_set_flash('error', 'Completa todos los campos para cambiar la contraseña.');
        admin_redirect(['section' => $redirectSection]);
    }

    if (mb_strlen($newPassword, 'UTF-8') < 8) {
        admin_set_flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        admin_redirect(['section' => $redirectSection]);
    }

    if (!hash_equals($newPassword, $confirmPassword)) {
        admin_set_flash('error', 'La confirmación de contraseña no coincide.');
        admin_redirect(['section' => $redirectSection]);
    }

    $stmt = admin_db()->prepare('SELECT password FROM uso_users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $currentUserId]);
    $userRow = $stmt->fetch();

    if (!is_array($userRow) || !password_verify($currentPassword, (string)($userRow['password'] ?? ''))) {
        admin_set_flash('error', 'La contraseña actual no es correcta.');
        admin_redirect(['section' => $redirectSection]);
    }

    $update = admin_db()->prepare('UPDATE uso_users SET password = :password WHERE id = :id LIMIT 1');
    $update->execute(['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $currentUserId]);
    admin_set_flash('success', 'Tu contraseña se actualizó correctamente.');
    admin_redirect(['section' => $redirectSection]);
}

/* ── Invitaciones y gestión de usuarios ── */

if ($action === 'send_user_invite') {
    $redirectSection = 'user';
    if (!admin_can_send_user_invites()) {
        admin_set_flash('error', 'No tienes permisos para enviar invitaciones de alta.');
        admin_redirect(['section' => $redirectSection]);
    }

    $email = trim((string)($_POST['invite_email'] ?? ''));
    if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        admin_set_flash('error', 'Introduce un correo electrónico válido para la invitación.');
        admin_redirect(['section' => $redirectSection]);
    }

    try {
        $cu = admin_user();
        create_user_invitation_request($email, (int)($cu['id'] ?? 0));
        admin_set_flash('success', 'Si el correo no estaba registrado, se ha enviado el enlace de alta.');
    } catch (Throwable) {
        admin_set_flash('error', 'No se pudo enviar la invitación de alta. Inténtalo de nuevo.');
    }
    admin_redirect(['section' => $redirectSection]);
}

if ($action === 'delete_admin_user') {
    if (!admin_can_send_user_invites()) {
        admin_set_flash('error', 'No tienes permisos para eliminar usuarios.');
        admin_redirect(['section' => 'user']);
    }

    $deleteUserId  = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $currentUser   = admin_user();
    $currentUserId = (int)($currentUser['id'] ?? 0);

    if ($deleteUserId <= 0) {
        admin_set_flash('error', 'Usuario no válido para eliminar.');
        admin_redirect(['section' => 'user']);
    }
    if ($deleteUserId === $currentUserId) {
        admin_set_flash('error', 'No puedes eliminar tu propio usuario.');
        admin_redirect(['section' => 'user']);
    }

    $target = admin_db()->prepare('SELECT id, username FROM uso_users WHERE id = :id LIMIT 1');
    $target->execute(['id' => $deleteUserId]);
    if (!is_array($target->fetch())) {
        admin_set_flash('error', 'El usuario seleccionado no existe.');
        admin_redirect(['section' => 'user']);
    }

    admin_db()->beginTransaction();
    try {
        $del1 = admin_db()->prepare('DELETE FROM uso_password_resets WHERE user_id = :user_id');
        $del1->execute(['user_id' => $deleteUserId]);
        $del2 = admin_db()->prepare('DELETE FROM uso_users WHERE id = :id LIMIT 1');
        $del2->execute(['id' => $deleteUserId]);
        admin_db()->commit();
        admin_set_flash('success', 'Usuario eliminado correctamente.');
    } catch (Throwable) {
        if (admin_db()->inTransaction()) admin_db()->rollBack();
        admin_set_flash('error', 'No se pudo eliminar el usuario seleccionado.');
    }
    admin_redirect(['section' => 'user']);
}

/* ── Noticias ── */

if ($action === 'save_news') {
    $id              = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $redirectSection = trim((string)($_POST['redirect_section'] ?? 'news'));
    $redirectView    = trim((string)($_POST['redirect_news_view'] ?? 'add'));
    $fechaCreaccion  = normalize_datetime_input((string)($_POST['fecha_creaccion'] ?? ''));
    $titulo          = trim((string)($_POST['titulo'] ?? ''));
    $texto           = sanitize_news_html((string)($_POST['texto'] ?? ''));
    $estado          = trim((string)($_POST['estado'] ?? 'borrador'));

    if (!in_array($estado, ['borrador', 'publicada', 'archivada'], true)) $estado = 'borrador';

    ensure_news_card_image_column();

    $currentImagePath = normalize_news_card_image_path((string)($_POST['current_ruta_imagen'] ?? 'img/placeholder.svg'));
    try {
        $newImagePath = upload_news_card_image($_FILES['news_card_image'] ?? null);
    } catch (Throwable $ex) {
        admin_set_flash('error', $ex->getMessage());
        admin_redirect(array_filter([
            'section'    => $redirectSection,
            'news_view'  => $redirectView,
            'edit'       => $id > 0 ? $id : null,
        ], static fn ($v) => $v !== null && $v !== ''));
    }
    // Si no se subió archivo nuevo, comprobar si se seleccionó una imagen existente
    if ($newImagePath === null) {
        $selectedImg = trim((string)($_POST['selected_card_image'] ?? ''));
        $selectedImg = normalize_news_card_image_path($selectedImg);
        if (!is_default_news_card_image_path($selectedImg)
            && str_starts_with($selectedImg, 'img/noticias/portada/')) {
            $newImagePath = $selectedImg;
        }
    }
    $rutaImagen = $newImagePath ?? $currentImagePath;

    if ($fechaCreaccion === null || $titulo === '' || $texto === '') {
        admin_set_flash('error', 'Fecha, título y texto son obligatorios.');
        admin_redirect(array_filter([
            'section'   => $redirectSection,
            'news_view' => $redirectView,
            'edit'      => $id > 0 ? $id : null,
        ], static fn ($v) => $v !== null && $v !== ''));
    }

    ensure_news_attachments_table();

    if ($id > 0) {
        $existingItem = fetch_news_item_by_id($id);
        if ($existingItem === null) {
            admin_set_flash('error', 'La noticia indicada no existe.');
            admin_redirect(['section' => 'news', 'news_view' => 'manage']);
        }

        $update = admin_db()->prepare(
            'UPDATE noticias
             SET fecha_creaccion = :fecha_creaccion,
                 titulo = :titulo,
                 texto = :texto,
                 ruta_imagen = :ruta_imagen,
                 estado = :estado
             WHERE id = :id'
        );
        $update->execute([
            'id'             => $id,
            'fecha_creaccion'=> $fechaCreaccion,
            'titulo'         => $titulo,
            'texto'          => $texto,
            'ruta_imagen'    => $rutaImagen,
            'estado'         => $estado,
        ]);

        $attachmentResult = store_news_attachments_from_request(
            $id,
            merge_uploaded_files($_FILES['news_images'] ?? null, $_FILES['news_image'] ?? null),
            merge_uploaded_files($_FILES['news_documents'] ?? null, $_FILES['news_document'] ?? null)
        );

        $msg = 'Noticia actualizada correctamente.';
        if ((int)$attachmentResult['stored_images'] > 0 || (int)$attachmentResult['stored_documents'] > 0) {
            $msg .= ' Se añadieron ' . $attachmentResult['stored_images'] . ' imagen(es) y ' . $attachmentResult['stored_documents'] . ' documento(s).';
        }
        if ((array)$attachmentResult['errors'] !== []) {
            $msg .= ' Algunos adjuntos no se pudieron subir.';
        }

        admin_set_flash('success', $msg);
        admin_redirect(['section' => 'news', 'news_view' => 'manage']);
    }

    // Nueva noticia
    $insert = admin_db()->prepare(
        'INSERT INTO noticias (fecha_creaccion, titulo, texto, ruta_imagen, estado)
         VALUES (:fecha_creaccion, :titulo, :texto, :ruta_imagen, :estado)'
    );
    $insert->execute([
        'fecha_creaccion' => $fechaCreaccion,
        'titulo'          => $titulo,
        'texto'           => $texto,
        'ruta_imagen'     => $rutaImagen,
        'estado'          => $estado,
    ]);
    $newId = (int)admin_db()->lastInsertId();

    $attachmentResult = store_news_attachments_from_request(
        $newId,
        merge_uploaded_files($_FILES['news_images'] ?? null, $_FILES['news_image'] ?? null),
        merge_uploaded_files($_FILES['news_documents'] ?? null, $_FILES['news_document'] ?? null)
    );

    $msg = 'Noticia creada correctamente.';
    if ((int)$attachmentResult['stored_images'] > 0 || (int)$attachmentResult['stored_documents'] > 0) {
        $msg .= ' Se añadieron ' . $attachmentResult['stored_images'] . ' imagen(es) y ' . $attachmentResult['stored_documents'] . ' documento(s).';
    }
    if ((array)$attachmentResult['errors'] !== []) {
        $msg .= ' Algunos adjuntos no se pudieron subir.';
    }

    admin_set_flash('success', $msg);
    admin_redirect(['section' => 'news', 'news_view' => 'manage']);
}

if ($action === 'delete_news') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        try {
            $result = delete_news_with_attachments($id);
            $msg    = 'Noticia eliminada correctamente.';
            if ((int)$result['deleted_files'] > 0) $msg .= ' Se eliminaron ' . $result['deleted_files'] . ' archivo(s) asociado(s).';
            if ((int)$result['failed_files'] > 0)  $msg .= ' ' . $result['failed_files'] . ' archivo(s) no se pudieron borrar del disco.';
            admin_set_flash('success', $msg);
        } catch (Throwable) {
            admin_set_flash('error', 'No se pudo eliminar la noticia.');
        }
    } else {
        admin_set_flash('error', 'No se pudo eliminar la noticia.');
    }
    admin_redirect(['section' => 'news', 'news_view' => 'manage']);
}

if ($action === 'delete_news_attachment') {
    $attachmentId = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;
    $newsId       = isset($_POST['news_id']) ? (int)$_POST['news_id'] : 0;

    if ($attachmentId <= 0 || $newsId <= 0) {
        admin_set_flash('error', 'Adjunto no válido para eliminar.');
        admin_redirect(['section' => 'news', 'news_view' => 'manage', 'edit' => $newsId > 0 ? $newsId : null]);
    }

    $attachment = fetch_news_attachment_by_id_for_admin($attachmentId, $newsId);
    if ($attachment === null) {
        admin_set_flash('error', 'El adjunto indicado no existe.');
        admin_redirect(['section' => 'news', 'news_view' => 'manage', 'edit' => $newsId]);
    }

    $relativePath = (string)($attachment['ruta_archivo'] ?? '');
    $del = admin_db()->prepare(
        'DELETE FROM noticias_adjuntos WHERE id = :attachment_id AND noticia_id = :news_id LIMIT 1'
    );
    $del->execute(['attachment_id' => $attachmentId, 'news_id' => $newsId]);

    $deletedFile = $relativePath !== '' ? delete_asset_file($relativePath, ['files/noticias']) : false;
    $msg = 'Adjunto eliminado correctamente.';
    if ($relativePath !== '' && !$deletedFile) {
        $msg .= ' El registro se eliminó, pero el archivo físico no se pudo borrar.';
    }

    admin_set_flash('success', $msg);
    admin_redirect(['section' => 'news', 'news_view' => 'manage', 'edit' => $newsId]);
}

/* ── Push Notifications ── */

if ($action === 'send_push_custom') {
    $pushTitulo  = trim((string)($_POST['push_titulo'] ?? ''));
    $pushMensaje = trim((string)($_POST['push_mensaje'] ?? ''));
    $pushUrl     = trim((string)($_POST['push_url'] ?? ''));

    if ($pushTitulo === '' || $pushMensaje === '') {
        admin_set_flash('error', 'El título y el mensaje son obligatorios.');
        admin_redirect(['section' => 'push']);
    }

    $urlFinal = null;
    if ($pushUrl !== '') {
        if (str_starts_with($pushUrl, '/')) {
            $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host    = $_SERVER['HTTP_HOST'] ?? 'uso-oeste.es';
            $pushUrl = $scheme . '://' . $host . $pushUrl;
        }
        $parsed = parse_url($pushUrl);
        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        $host   = strtolower((string)($parsed['host'] ?? ''));
        if ($scheme === 'https' && ($host === 'uso-oeste.es' || str_ends_with($host, '.uso-oeste.es') || $host === 'uso-oest.es' || str_ends_with($host, '.uso-oest.es'))) {
            $urlFinal = $pushUrl;
        } else {
            admin_set_flash('error', 'La URL debe ser https y pertenecer al dominio uso-oeste.es o uso-oest.es.');
            admin_redirect(['section' => 'push']);
        }
    }

    try {
        $result = enviarNotificacionUnificada($pushTitulo, $pushMensaje, $urlFinal, 'aviso');
        $fcm    = $result['fcm'];
        $web    = $result['web'];
        $total  = $result['total_enviados'];

        if ($total > 0) {
            $detalle = [];
            if ($fcm['enviados'] > 0) $detalle[] = $fcm['enviados'] . ' app';
            if ($web['sent'] > 0)     $detalle[] = $web['sent'] . ' navegador(es)';
            $msg = 'Notificación enviada a ' . implode(' + ', $detalle) . '.';
            if ($result['total_fallidos'] > 0) $msg .= ' (' . $result['total_fallidos'] . ' fallido(s))';
            admin_set_flash('success', $msg);
        } elseif ($fcm['usuarios'] === 0 && $web['sent'] === 0 && $web['failed'] === 0) {
            admin_set_flash('error', 'No hay destinatarios registrados (ni app ni navegadores).');
        } else {
            admin_set_flash('error', 'No se pudo enviar la notificación (' . $result['total_fallidos'] . ' fallido(s)).');
        }
    } catch (Throwable $ex) {
        error_log('[admin] send_push_custom error: ' . $ex->getMessage());
        admin_set_flash('error', 'Error al enviar la notificación: ' . $ex->getMessage());
    }

    admin_redirect(['section' => 'push']);
}

if ($action === 'send_push_news') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        admin_set_flash('error', 'Noticia no válida para enviar notificación.');
        admin_redirect(['section' => 'news', 'news_view' => 'manage']);
    }

    $newsItem = fetch_news_item_by_id($id);
    if ($newsItem === null) {
        admin_set_flash('error', 'La noticia indicada no existe.');
        admin_redirect(['section' => 'news', 'news_view' => 'manage']);
    }

    $titulo  = (string)($newsItem['titulo'] ?? '');
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'uso-oeste.es';
    $newsUrl = $scheme . '://' . $host . '/noticias/' . $id;

    try {
        $result = enviarNotificacionUnificada('Nueva noticia: ' . $titulo, $titulo, $newsUrl, 'noticia');
        $fcm    = $result['fcm'];
        $web    = $result['web'];
        $total  = $result['total_enviados'];

        if ($total > 0) {
            $detalle = [];
            if ($fcm['enviados'] > 0) $detalle[] = $fcm['enviados'] . ' app';
            if ($web['sent'] > 0)     $detalle[] = $web['sent'] . ' navegador(es)';
            $msg = 'Notificación enviada a ' . implode(' + ', $detalle) . '.';
            if ($result['total_fallidos'] > 0) $msg .= ' (' . $result['total_fallidos'] . ' fallido(s))';
            admin_set_flash('success', $msg);
        } elseif ($fcm['usuarios'] === 0 && $web['sent'] === 0 && $web['failed'] === 0) {
            admin_set_flash('error', 'No hay destinatarios registrados (ni app ni navegadores).');
        } else {
            admin_set_flash('error', 'No se pudo enviar la notificación (' . $result['total_fallidos'] . ' fallido(s)).');
        }
    } catch (Throwable $ex) {
        error_log('[admin] send_push_news error: ' . $ex->getMessage());
        admin_set_flash('error', 'Error al enviar la notificación: ' . $ex->getMessage());
    }

    admin_redirect(['section' => 'news', 'news_view' => 'manage']);
}

/* ── Calendario ── */

if ($action === 'create_calendar_year') {
    ensure_calendar_tables();
    $yearValue   = isset($_POST['calendar_year']) ? (int)$_POST['calendar_year'] : 0;
    $createdYear = calendar_create_year($yearValue);

    if (!is_array($createdYear)) {
        admin_set_flash('error', 'No se pudo crear el año. Debe estar entre 2000 y 2100 y no duplicarse.');
    } else {
        admin_set_flash('success', 'Año de calendario creado correctamente.');
    }
    admin_redirect(['section' => 'calendar', 'year_id' => is_array($createdYear) ? (int)($createdYear['id'] ?? 0) : 0]);
}

if ($action === 'delete_calendar_year') {
    ensure_calendar_tables();
    $yearId = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    if ($yearId <= 0 || !calendar_delete_year($yearId)) {
        admin_set_flash('error', 'No se pudo eliminar el año seleccionado.');
    } else {
        admin_set_flash('success', 'Año de calendario eliminado (incluidos festivos y rotaciones).');
    }
    admin_redirect(['section' => 'calendar']);
}

if ($action === 'add_calendar_holiday') {
    ensure_calendar_tables();
    $yearId       = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    $holidayDate  = trim((string)($_POST['holiday_date'] ?? ''));
    $holidayLabel = trim((string)($_POST['holiday_label'] ?? ''));
    $holidayType  = trim((string)($_POST['holiday_type'] ?? 'nacional'));

    if ($yearId <= 0 || !calendar_add_holiday($yearId, $holidayDate, $holidayLabel, $holidayType)) {
        admin_set_flash('error', 'No se pudo guardar el festivo (revisa fecha o duplicados).');
    } else {
        admin_set_flash('success', 'Festivo añadido correctamente.');
    }
    admin_redirect(['section' => 'calendar', 'year_id' => $yearId]);
}

if ($action === 'delete_calendar_holiday') {
    ensure_calendar_tables();
    $yearId    = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    $holidayId = isset($_POST['holiday_id']) ? (int)$_POST['holiday_id'] : 0;

    if ($yearId <= 0 || $holidayId <= 0 || !calendar_delete_holiday($yearId, $holidayId)) {
        admin_set_flash('error', 'No se pudo eliminar el festivo indicado.');
    } else {
        admin_set_flash('success', 'Festivo eliminado correctamente.');
    }
    admin_redirect(['section' => 'calendar', 'year_id' => $yearId]);
}

if ($action === 'save_calendar_rotation') {
    ensure_calendar_tables();
    $yearId       = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    $rotationId   = isset($_POST['rotation_id']) && (int)$_POST['rotation_id'] > 0 ? (int)$_POST['rotation_id'] : null;
    $rotationName = trim((string)($_POST['rotation_name'] ?? ''));
    $weeksCycle   = isset($_POST['weeks_cycle']) ? (int)$_POST['weeks_cycle'] : 1;
    $isActive     = isset($_POST['is_active']) && (string)$_POST['is_active'] === '1';
    $isDefault    = isset($_POST['is_default']) && (string)$_POST['is_default'] === '1';
    $pattern      = is_array($_POST['pattern'] ?? null) ? (array)$_POST['pattern'] : [];

    $savedId = calendar_save_rotation($yearId, $rotationId, $rotationName, $weeksCycle, $isActive, $isDefault, $pattern);

    if ($savedId === null) {
        admin_set_flash('error', 'No se pudo guardar la rotación. Revisa nombre y patrón semanal.');
    } else {
        admin_set_flash('success', 'Rotación guardada correctamente.');
    }
    admin_redirect(['section' => 'calendar', 'year_id' => $yearId]);
}

if ($action === 'delete_calendar_rotation') {
    ensure_calendar_tables();
    $yearId     = isset($_POST['year_id']) ? (int)$_POST['year_id'] : 0;
    $rotationId = isset($_POST['rotation_id']) ? (int)$_POST['rotation_id'] : 0;

    if ($yearId <= 0 || $rotationId <= 0 || !calendar_delete_rotation($yearId, $rotationId)) {
        admin_set_flash('error', 'No se pudo eliminar la rotación indicada.');
    } else {
        admin_set_flash('success', 'Rotación eliminada correctamente.');
    }
    admin_redirect(['section' => 'calendar', 'year_id' => $yearId]);
}

/* ── Documentos ── */

if ($action === 'upload_document') {
    $targetFolder = normalize_document_target((string)($_POST['document_target'] ?? 'files'));
    $displayName  = trim((string)($_POST['display_name'] ?? ''));

    if ($displayName === '') {
        admin_set_flash('error', 'Debes indicar el nombre a mostrar del documento.');
        admin_redirect(['section' => 'documents', 'documents_view' => 'add']);
    }

    try {
        $uploadedPath = handle_document_upload($_FILES['document_file'] ?? null, $targetFolder);
        if ($uploadedPath === null) {
            admin_set_flash('error', 'Debes seleccionar un documento para subir.');
        } else {
            $insert = admin_db()->prepare(
                'INSERT INTO uso_documents (display_name, file_path, folder)
                 VALUES (:display_name, :file_path, :folder)'
            );
            $insert->execute([
                'display_name' => $displayName,
                'file_path'    => $uploadedPath,
                'folder'       => $targetFolder,
            ]);
            admin_set_flash('success', 'Documento subido correctamente en ' . $targetFolder . '.');
        }
    } catch (Throwable $ex) {
        $msg = $ex->getMessage();
        if (stripos($msg, 'Duplicate entry') !== false) $msg = 'Ya existe un documento registrado con esa ruta.';
        admin_set_flash('error', $msg);
    }

    admin_redirect(['section' => 'documents']);
}

if ($action === 'delete_document') {
    $documentId   = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
    $relativePath = (string)($_POST['relative_path'] ?? '');
    $deleted      = delete_asset_file($relativePath, ['files', 'files/calendarios']);

    if ($documentId > 0) {
        $del = admin_db()->prepare('DELETE FROM uso_documents WHERE id = :id LIMIT 1');
        $del->execute(['id' => $documentId]);
    } else {
        $del = admin_db()->prepare('DELETE FROM uso_documents WHERE file_path = :file_path LIMIT 1');
        $del->execute(['file_path' => $relativePath]);
    }

    if ($deleted) {
        admin_set_flash('success', 'Documento eliminado correctamente.');
    } else {
        admin_set_flash('success', 'Registro eliminado. El archivo físico no estaba disponible o ya fue eliminado.');
    }
    admin_redirect(['section' => 'documents', 'documents_view' => 'manage']);
}

/* ── Imágenes ── */

if ($action === 'delete_image') {
    $relativePath = (string)($_POST['relative_path'] ?? '');
    if (delete_asset_file($relativePath, ['img/noticias/portada', 'img/noticias'])) {
        admin_set_flash('success', 'Imagen eliminada correctamente.');
    } else {
        admin_set_flash('error', 'No se pudo eliminar la imagen seleccionada.');
    }
    admin_redirect(['section' => 'images']);
}

if ($action === 'upload_cover_image') {
    $fileData = $_FILES['cover_image'] ?? null;
    try {
        $path = upload_news_card_image($fileData);
        if ($path === null) {
            admin_set_flash('error', 'No se seleccionó ningún archivo para subir.');
        } else {
            admin_set_flash('success', 'Imagen de portada subida correctamente.');
        }
    } catch (Throwable $ex) {
        admin_set_flash('error', $ex->getMessage());
    }
    admin_redirect(['section' => 'images']);
}
