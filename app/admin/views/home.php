<div class="page-header">
    <div>
        <h1 class="page-header__title">Inicio</h1>
        <p class="page-header__sub">Bienvenido al panel de administración de USO OEST.</p>
    </div>
</div>

<div class="menu-grid">

    <a class="menu-card" href="<?= e(admin_url('news')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 22V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v18l-4-2-4 2-4-2z"/>
            <line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
        </div>
        <h2 class="menu-card__title">Noticias</h2>
        <p class="menu-card__desc">Añade, edita y administra las noticias del sitio.</p>
    </a>

    <a class="menu-card" href="<?= e(admin_url('documents')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <h2 class="menu-card__title">Documentos</h2>
        <p class="menu-card__desc">Gestiona documentos y archivos descargables.</p>
    </a>

    <a class="menu-card" href="<?= e(admin_url('calendar')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <h2 class="menu-card__title">Calendario</h2>
        <p class="menu-card__desc">Administra festivos y rotaciones de turno por año.</p>
    </a>

    <a class="menu-card" href="<?= e(admin_url('images')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
        <h2 class="menu-card__title">Imágenes</h2>
        <p class="menu-card__desc">Visualiza y elimina las imágenes subidas.</p>
    </a>

    <a class="menu-card" href="<?= e(admin_url('push')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <h2 class="menu-card__title">Notificaciones</h2>
        <p class="menu-card__desc">Envía notificaciones push al app y los navegadores.</p>
    </a>

    <a class="menu-card" href="<?= e(admin_url('user')) ?>">
        <div class="menu-card__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/></svg>
        </div>
        <h2 class="menu-card__title">Mi usuario</h2>
        <p class="menu-card__desc">Cambia tu contraseña y gestiona usuarios administradores.</p>
    </a>

</div>
