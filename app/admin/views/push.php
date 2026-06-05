<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Notificaciones push</span>
        </nav>
        <h1 class="page-header__title">Notificaciones push</h1>
    </div>
</div>

<div class="panel">
    <h2 class="panel__title">Enviar notificación personalizada</h2>
    <p style="color:var(--a-muted);font-size:.9rem;margin:0 0 1.25rem">
        Se enviará a todos los usuarios suscritos, tanto en la app como en los navegadores.
    </p>

    <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="send_push_custom">
        <div class="form-grid">
            <div class="form-group">
                <label class="label label--required" for="push_titulo">Título</label>
                <input id="push_titulo" name="push_titulo" type="text" class="input"
                       maxlength="100" required
                       placeholder="Ejemplo: Aviso importante de USO OEST">
            </div>
            <div class="form-group">
                <label class="label label--required" for="push_mensaje">Mensaje</label>
                <textarea id="push_mensaje" name="push_mensaje" class="textarea"
                          maxlength="400" required rows="3"
                          style="min-height:90px"
                          placeholder="Texto breve del aviso que recibirán los dispositivos..."></textarea>
                <p class="helper">Máx. 400 caracteres. Sé conciso para que se lea correctamente en el dispositivo.</p>
            </div>
            <div class="form-group">
                <label class="label" for="push_url">URL de destino <span class="label--optional">(opcional)</span></label>
                <input id="push_url" name="push_url" type="url" class="input"
                       placeholder="https://uso-oeste.es/…"
                       pattern="https://.*">
                <p class="helper">
                    Si se indica, la URL debe ser <code>https</code> y pertenecer al dominio
                    <code>uso-oeste.es</code> o <code>uso-oest.es</code>. Si se deja vacía,
                    al pulsar la notificación se abre la portada del sitio.
                </p>
            </div>
            <div>
                <button type="submit" class="btn btn--primary js-submit-btn">
                    Enviar notificación
                </button>
            </div>
        </div>
    </form>
</div>
