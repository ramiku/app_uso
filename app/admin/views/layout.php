<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Panel USO') ?></title>
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('img/icon-192.png')) ?>?v=20260301">
    <link rel="shortcut icon" type="image/png" href="<?= e(asset('img/icon-192.png')) ?>?v=20260301">
    <link rel="apple-touch-icon" href="<?= e(asset('img/icon-192.png')) ?>?v=20260301">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/admin.css') ?>">
</head>
<body>

<?php if (!$isLoggedIn): ?>
    <?php require __DIR__ . '/login.php'; ?>
<?php else: ?>

<?php /* ─── Mobile overlay ─── */ ?>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay" aria-hidden="true"></div>

<?php /* ─── Sidebar (desktop always visible, mobile drawer) ─── */ ?>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Navegación del panel">

    <a class="admin-sidebar__brand" href="<?= e(admin_url()) ?>">
        <img class="admin-sidebar__logo" src="<?= e(asset('img/logo_uso_oest.png')) ?>" alt="USO OEST">
        <span class="admin-sidebar__brand-texts">
            <span class="admin-sidebar__brand-name">Panel USO</span>
            <span class="admin-sidebar__brand-sub">Administración</span>
        </span>
    </a>

    <nav class="admin-sidebar__nav" aria-label="Menú principal">
        <span class="admin-sidebar__section-label">Contenido</span>

        <a class="admin-sidebar__link <?= $section === 'news' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('news')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 22V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2z"/>
                <line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/>
            </svg>
            Noticias
        </a>

        <a class="admin-sidebar__link <?= $section === 'documents' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('documents')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Documentos
        </a>

        <a class="admin-sidebar__link <?= $section === 'calendar' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('calendar')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            Calendario
        </a>

        <a class="admin-sidebar__link <?= $section === 'images' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('images')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
            Imágenes
        </a>

        <span class="admin-sidebar__section-label">Notificaciones</span>

        <a class="admin-sidebar__link <?= $section === 'push' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('push')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            Notificaciones push
        </a>

        <span class="admin-sidebar__section-label">Cuenta</span>

        <a class="admin-sidebar__link <?= $section === 'user' ? 'is-active' : '' ?>"
           href="<?= e(admin_url('user')) ?>">
            <svg class="admin-sidebar__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            Mi usuario
        </a>
    </nav>

    <div class="admin-sidebar__footer">
        <?php $cu = admin_user(); $cuInitial = strtoupper(mb_substr($cu['username'] ?? 'U', 0, 1, 'UTF-8')); ?>
        <div class="admin-sidebar__user-block">
            <div class="admin-sidebar__avatar" aria-hidden="true"><?= e($cuInitial) ?></div>
            <div class="admin-sidebar__user-info">
                <span class="admin-sidebar__user-name"><?= e($cu['username'] ?? '') ?></span>
                <span class="admin-sidebar__user-email"><?= e($cu['email'] ?? '') ?></span>
            </div>
        </div>
        <form method="post" action="<?= e(admin_url()) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn--ghost btn--sm" style="width:100%">Cerrar sesión</button>
        </form>
    </div>
</aside>

<?php /* ─── Main area (sidebar offset on desktop) ─── */ ?>
<div class="admin-main">

    <?php /* ─── Mobile topbar ─── */ ?>
    <header class="admin-topbar">
        <button type="button"
                class="admin-topbar__toggle js-sidebar-toggle"
                aria-controls="admin-sidebar"
                aria-expanded="false"
                aria-label="Abrir menú">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <a class="admin-topbar__brand" href="<?= e(admin_url()) ?>">
            <img class="admin-topbar__logo" src="<?= e(asset('img/logo_uso_oest.png')) ?>" alt="USO">
            Panel USO
        </a>
    </header>

    <?php /* ─── Page content ─── */ ?>
    <div class="admin-content">

        <?php if (is_array($flash) && isset($flash['type'], $flash['message'])): ?>
        <div class="flash flash--<?= e($flash['type']) ?>" role="alert" data-auto-dismiss="6000">
            <?php if ($flash['type'] === 'success'): ?>
            <svg class="flash__icon" viewBox="0 0 24 24" aria-hidden="true" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/>
            </svg>
            <?php else: ?>
            <svg class="flash__icon" viewBox="0 0 24 24" aria-hidden="true" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php endif; ?>
            <?= e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <?php /* ─── Section routing ─── */ ?>
        <?php if ($section === 'news'):        require __DIR__ . '/news.php';
        elseif ($section === 'documents'):     require __DIR__ . '/documents.php';
        elseif ($section === 'images'):        require __DIR__ . '/images.php';
        elseif ($section === 'calendar'):      require __DIR__ . '/calendar.php';
        elseif ($section === 'user'):          require __DIR__ . '/user.php';
        elseif ($section === 'push'):          require __DIR__ . '/push.php';
        else:                                  require __DIR__ . '/home.php';
        endif; ?>

    </div><?php /* .admin-content */ ?>
</div><?php /* .admin-main */ ?>

<?php endif; /* if isLoggedIn */ ?>

<script src="<?= e(asset('js/admin.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/admin.js') ?>"></script>
</body>
</html>
