<?php
/**
 * Variables heredadas del scope de uso_admin.php:
 * @var array  $newsImageItems
 * @var string $csrfToken
 */
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Imágenes</span>
        </nav>
        <h1 class="page-header__title">Imágenes de portada</h1>
    </div>
</div>

<div class="panel" style="margin-bottom:1.25rem">
    <h2 class="panel__title">Subir nueva imagen de portada</h2>
    <form method="post"
          action="<?= e(admin_url()) ?>"
          enctype="multipart/form-data"
          autocomplete="off"
          novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="upload_cover_image">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <input id="cover_image" name="cover_image" type="file" class="input"
                   accept="image/jpeg,image/png,image/webp,image/gif"
                   style="flex:1;min-width:200px">
            <button type="submit" class="btn btn--primary btn--sm">Subir imagen</button>
        </div>
        <p class="helper" style="margin-top:.4rem">JPEG, PNG, WebP o GIF. Máx. 4 MB.</p>
    </form>
</div>

<div class="panel">
    <?php if (empty($newsImageItems)): ?>
    <p style="color:var(--a-muted);padding:.5rem 0">
        No hay imágenes de portada subidas todavía.
    </p>
    <?php else: ?>
    <div class="media-grid">
        <?php foreach ($newsImageItems as $img): ?>
        <?php
            $imgPath = (string)($img['relativePath'] ?? '');
            $imgName = (string)($img['name'] ?? basename($imgPath));
            $imgSize = isset($img['size']) ? format_size((int)$img['size']) : '';
        ?>
        <div class="media-card">
            <img class="media-card__thumb"
                 src="<?= e(asset($imgPath)) ?>"
                 alt="<?= e($imgName) ?>"
                 loading="lazy">
            <div class="media-card__body">
                <p class="media-card__name"><?= e($imgName) ?></p>
                <?php if ($imgSize !== ''): ?>
                <p class="media-card__meta"><?= e($imgSize) ?></p>
                <?php endif; ?>
            </div>
            <div class="media-card__footer">
                <form method="post" action="<?= e(admin_url()) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_image">
                    <input type="hidden" name="relative_path" value="<?= e($imgPath) ?>">
                    <button type="submit" class="btn btn--danger btn--sm" style="width:100%"
                            onclick="return confirm('¿Eliminar la imagen «<?= e(addslashes($imgName)) ?>»?')">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
