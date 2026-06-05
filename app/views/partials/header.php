<?php
declare(strict_types=1);

$currentPage = $page ?? 'home';
$isSectionsActive = in_array($currentPage, ['directorio', 'documentacion', 'calendarios'], true);
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?php echo e(url_for('home')); ?>" aria-label="Ir a inicio">
            <img class="site-header__logo-image" src="<?php echo e(asset('img/logo_uso_oest.png')); ?>" alt="USO Asturias" loading="eager">
        </a>

        <button
            class="site-header__toggle js-menu-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="main-navigation"
            aria-label="Abrir menú principal"
        >
            <span class="site-header__toggle-line"></span>
            <span class="site-header__toggle-line"></span>
            <span class="site-header__toggle-line"></span>
        </button>

        <nav id="main-navigation" class="site-nav" aria-label="Principal">
            <ul class="site-nav__list">
                <li class="site-nav__item <?php echo $currentPage === 'home' ? 'is-active' : ''; ?>">
                    <a class="site-nav__link" href="<?php echo e(url_for('home')); ?>">Inicio</a>
                </li>

                <li class="site-nav__item site-nav__item--has-submenu <?php echo $isSectionsActive ? 'is-active' : ''; ?>">
                    <button class="site-nav__link site-nav__link--button js-submenu-toggle" type="button" aria-expanded="false">
                        Secciones
                    </button>
                    <ul class="site-nav__submenu">
                        <li><a class="site-nav__submenu-link <?php echo $currentPage === 'directorio' ? 'is-active' : ''; ?>" href="<?php echo e(url_for('directorio')); ?>">Directorio</a></li>
                        <li><a class="site-nav__submenu-link <?php echo $currentPage === 'documentacion' ? 'is-active' : ''; ?>" href="<?php echo e(url_for('documentacion')); ?>">Documentación</a></li>
                        <li><a class="site-nav__submenu-link <?php echo $currentPage === 'calendarios' ? 'is-active' : ''; ?>" href="<?php echo e(url_for('calendarios')); ?>">Calendarios</a></li>
                    </ul>
                </li>

                <li class="site-nav__item <?php echo $currentPage === 'asistente' ? 'is-active' : ''; ?>">
                    <a class="site-nav__link" href="<?php echo e(url_for('asistente')); ?>">Asistente Virtual USO</a>
                </li>

                <li class="site-nav__item <?php echo $currentPage === 'contactanos' ? 'is-active' : ''; ?>">
                    <a class="site-nav__link" href="<?php echo e(url_for('contactanos')); ?>">Contáctanos</a>
                </li>

            </ul>
        </nav>
    </div>
</header>

<!-- Barra de navegación inferior (solo móvil) — .mobile-app-nav se activa en responsive.css -->
<?php $isMoreActive = in_array($currentPage, ['calendarios', 'documentacion', 'directorio'], true); ?>

<!-- Drawer "Más": panel que sube sobre la tab bar -->
<div class="mobile-app-more" id="mobile-app-more" aria-hidden="true">
    <div class="mobile-app-more__backdrop" id="mobile-app-more-backdrop"></div>
    <div class="mobile-app-more__panel">
        <p class="mobile-app-more__heading">Más secciones</p>
        <a class="mobile-app-more__link <?php echo $currentPage === 'noticias' ? 'is-active' : ''; ?>"
           href="<?php echo e(url_for('noticias')); ?>">
            <svg class="mobile-app-more__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7z"/>
            </svg>
            <span>Noticias</span>
        </a>
        <a class="mobile-app-more__link <?php echo $currentPage === 'calendarios' ? 'is-active' : ''; ?>"
           href="<?php echo e(url_for('calendarios')); ?>">
            <svg class="mobile-app-more__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Calendarios</span>
        </a>
        <a class="mobile-app-more__link <?php echo $currentPage === 'documentacion' ? 'is-active' : ''; ?>"
           href="<?php echo e(url_for('documentacion')); ?>">
            <svg class="mobile-app-more__icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Documentación</span>
        </a>
        <a class="mobile-app-more__link"
           href="<?php echo e(BASE_URL); ?>/uso_admin"
           target="_blank"
           rel="noopener"
           aria-label="Panel de administración">
            <svg class="mobile-app-more__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <span>Administración</span>
        </a>
    </div>
</div>

<nav class="mobile-app-nav" aria-label="Navegación principal" id="mobile-app-nav">
    <ul class="mobile-app-nav__list">

        <li class="mobile-app-nav__item">
            <a class="mobile-app-nav__link <?php echo $currentPage === 'home' ? 'is-active' : ''; ?>"
               href="<?php echo e(url_for('home')); ?>"
               aria-current="<?php echo $currentPage === 'home' ? 'page' : 'false'; ?>"
               aria-label="Inicio">
                <svg class="mobile-app-nav__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Inicio</span>
            </a>
        </li>

        <li class="mobile-app-nav__item">
            <a class="mobile-app-nav__link <?php echo $currentPage === 'directorio' ? 'is-active' : ''; ?>"
               href="<?php echo e(url_for('directorio')); ?>"
               aria-current="<?php echo $currentPage === 'directorio' ? 'page' : 'false'; ?>"
               aria-label="Directorio de contacto">
                <svg class="mobile-app-nav__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21 11.36 11.36 0 003.54.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.54a1 1 0 01-.24 1.04l-2.21 2.21z"/>
                </svg>
                <span>Directorio</span>
            </a>
        </li>

        <li class="mobile-app-nav__item">
            <a class="mobile-app-nav__link <?php echo $currentPage === 'asistente' ? 'is-active' : ''; ?>"
               href="<?php echo e(url_for('asistente')); ?>"
               aria-current="<?php echo $currentPage === 'asistente' ? 'page' : 'false'; ?>"
               aria-label="Asistente Virtual">
                <svg class="mobile-app-nav__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span>Asistente</span>
            </a>
        </li>

        <li class="mobile-app-nav__item">
            <a class="mobile-app-nav__link <?php echo $currentPage === 'contactanos' ? 'is-active' : ''; ?>"
               href="<?php echo e(url_for('contactanos')); ?>"
               aria-current="<?php echo $currentPage === 'contactanos' ? 'page' : 'false'; ?>"
               aria-label="Contáctanos">
                <svg class="mobile-app-nav__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>Contacto</span>
            </a>
        </li>

        <li class="mobile-app-nav__item">
            <button class="mobile-app-nav__link js-more-toggle <?php echo $isMoreActive ? 'is-active' : ''; ?>"
                    type="button"
                    aria-expanded="false"
                    aria-controls="mobile-app-more"
                    aria-label="Más secciones">
                <svg class="mobile-app-nav__icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="5" cy="12" r="1.5" fill="currentColor" stroke="none"/>
                    <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/>
                    <circle cx="19" cy="12" r="1.5" fill="currentColor" stroke="none"/>
                </svg>
                <span>Más</span>
            </button>
        </li>

    </ul>
</nav>
