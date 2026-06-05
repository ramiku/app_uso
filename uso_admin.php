<?php
declare(strict_types=1);

// ── DIAGNÓSTICO TEMPORAL — eliminar tras identificar el error ──
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// ──────────────────────────────────────────────────────────────

/* ──────────────────────────────────────────────────────────────────────────
   uso_admin.php — Panel de administración USO OEST
   Punto de entrada delgado: bootstrap → acción → datos → vista
   ────────────────────────────────────────────────────────────────────────── */

/* ── Headers de seguridad ── */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

/* ── Sesión ── */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Zona horaria ── */
date_default_timezone_set('Europe/Madrid');

/* ── Dependencias ── */
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/helpers/push_helpers.php';
require_once __DIR__ . '/app/admin/functions.php';

/* ── Variable de error de formulario (login, reset, registro) ── */
$errorMessage = '';

/* ── Gestión de acciones POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/app/admin/actions.php';
}

/* ── Estado de sesión y tokens ── */
$isLoggedIn = admin_is_logged_in();
$flash      = admin_get_flash();
$csrfToken  = admin_csrf_token();

/* ── Parseo de la ruta de petición ── */
$r             = parse_admin_request($_GET);
$section       = $r['section'];
$newsView      = $r['news_view'];
$documentsView = $r['documents_view'];
$editId        = $r['edit'];

/* ── Modos especiales sin sesión ── */
$isResetMode    = false;
$resetEmail     = '';
$resetToken     = '';
$isRegisterMode = false;
$registerEmail  = '';
$registerToken  = '';

if (!$isLoggedIn) {
    if ($section === 'reset') {
        $resetEmail = $r['reset_email'];
        $resetToken = $r['reset_token'];
        if ($resetEmail !== '' && $resetToken !== '' && find_password_reset($resetEmail, $resetToken) !== null) {
            $isResetMode = true;
        } else {
            admin_set_flash('error', 'El enlace de recuperación no es válido o ha caducado.');
            admin_redirect();
        }
    } elseif ($section === 'register') {
        $registerEmail = $r['register_email'];
        $registerToken = $r['register_token'];
        if ($registerEmail !== '' && $registerToken !== '' && find_user_invitation($registerEmail, $registerToken) !== null) {
            $isRegisterMode = true;
        } else {
            admin_set_flash('error', 'El enlace de alta no es válido o ha caducado.');
            admin_redirect();
        }
    }
}

/* ── Título de página ── */
$pageTitles = [
    'news'      => 'Noticias — Panel USO',
    'documents' => 'Documentos — Panel USO',
    'images'    => 'Imágenes — Panel USO',
    'calendar'  => 'Calendario — Panel USO',
    'user'      => 'Mi usuario — Panel USO',
    'push'      => 'Notificaciones — Panel USO',
];
$pageTitle = $pageTitles[$section] ?? 'Panel USO';

/* ── Datos de sección (solo con sesión activa) ── */
$isEditing              = false;
$editingNews            = null;
$editingNewsAttachments = [];
$newsItems              = [];
$documentItems          = [];
$newsImageItems         = [];
$adminUsers             = [];

$calendarYears              = [];
$calendarSelectedYearId     = 0;
$calendarSelectedYear       = null;
$calendarHolidays           = [];
$calendarRotations          = [];
$calendarEditingRotation    = null;
$calendarEditorWeeks        = 1;
$calendarEditorPattern      = [];
$calendarEditorGrid         = [];
$calendarShowRotationEditor = false;
$calendarRotationMode       = 'new';
$calendarSelectedRotationId = 0;

if ($isLoggedIn) {

    /* ── Noticias ── */
    if ($section === 'news') {
        ensure_news_attachments_table();
        ensure_news_card_image_column();

        // La vista por defecto (newsView='') y 'manage' muestran la tabla; 'add' muestra el formulario
        if ($newsView !== 'add') {
            $newsItems = fetch_news_items_for_admin();
        }
        // Imágenes disponibles para el selector del formulario (add y edición)
        if ($newsView === 'add' || $editId > 0) {
            $newsImageItems = fetch_news_images_for_admin();
        }
        if ($editId > 0) {
            $editingNews = fetch_news_item_by_id($editId);
            if ($editingNews !== null) {
                $editingNewsAttachments = fetch_news_attachments_for_admin($editId);
                $newsView   = 'add'; // Edición usa el formulario del modo 'add'
                $isEditing  = true;
            }
        }
    }

    /* ── Documentos ── */
    // La vista por defecto (documentsView='') y 'manage' muestran la tabla
    if ($section === 'documents' && $documentsView !== 'add') {
        $documentItems = fetch_documents_for_admin();
    }

    /* ── Imágenes ── */
    if ($section === 'images') {
        $newsImageItems = fetch_news_images_for_admin();
    }

    /* ── Calendario ── */
    if ($section === 'calendar') {
        ensure_calendar_tables();

        $calendarYears          = calendar_list_years();
        $calendarSelectedYearId = (int)($_GET['year_id'] ?? 0);

        if ($calendarSelectedYearId > 0) {
            $calendarSelectedYear = calendar_get_year_by_id($calendarSelectedYearId);

            if ($calendarSelectedYear === null) {
                $calendarSelectedYearId = 0;
            } else {
                $calendarHolidays  = calendar_list_holidays($calendarSelectedYearId);
                $calendarRotations = calendar_list_rotations($calendarSelectedYearId);

                $rotationMode = trim((string)($_GET['rotation_mode'] ?? ''));
                if ($rotationMode === 'new' || $rotationMode === 'edit') {
                    $calendarShowRotationEditor = true;
                    $calendarRotationMode       = $rotationMode;

                    if ($rotationMode === 'edit') {
                        $calendarSelectedRotationId = (int)($_GET['rotation_id'] ?? 0);
                        if ($calendarSelectedRotationId > 0) {
                            $calendarEditingRotation = calendar_get_rotation($calendarSelectedRotationId);
                        }
                    }

                    $calendarEditorWeeks = $calendarEditingRotation !== null
                        ? max(1, min(4, (int)($calendarEditingRotation['weeks_cycle'] ?? 1)))
                        : 1;

                    $calendarEditorPattern = ($calendarEditingRotation !== null && $calendarSelectedRotationId > 0)
                        ? calendar_get_rotation_pattern($calendarSelectedRotationId)
                        : [];

                    $calendarEditorGrid = calendar_build_editor_grid(
                        (int)($calendarSelectedYear['year'] ?? $calendarSelectedYear['year_value'] ?? (int)date('Y')),
                        $calendarEditorWeeks,
                        $calendarEditorPattern
                    );
                }
            }
        }
    }

    /* ── Usuarios ── */
    if ($section === 'user' && admin_can_send_user_invites()) {
        $adminUsers = fetch_admin_users_for_management();
    }
}

/* ── Vista ── */
require __DIR__ . '/app/admin/views/layout.php';
