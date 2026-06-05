<?php
/**
 * Variables heredadas del scope de uso_admin.php:
 * @var string   $newsView
 * @var bool     $isEditing
 * @var array|null $editingNews
 * @var array    $editingNewsAttachments
 * @var array    $newsItems
 * @var array    $newsImageItems
 * @var string   $csrfToken
 */
/* ── Breadcrumb ───────────────────────────────────────── */
$navAddClass = ($newsView === 'add' && !$isEditing) ? 'is-active' : '';
$navManClass = ($newsView !== 'add') ? 'is-active' : '';
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Noticias</span>
        </nav>
        <h1 class="page-header__title">Noticias</h1>
    </div>
    <div class="btn-group">
        <a class="btn btn--ghost btn--sm <?= $navAddClass ?>"
           href="<?= e(admin_url('news', 'add')) ?>">Añadir</a>
        <a class="btn btn--ghost btn--sm <?= $navManClass ?>"
           href="<?= e(admin_url('news', 'manage')) ?>">Gestionar</a>
    </div>
</div>

<?php if ($newsView === 'add'): ?>
<?php /* ═══════════════════════════════════════════
        FORMULARIO AÑADIR / EDITAR NOTICIA
   ═══════════════════════════════════════════ */ ?>

<div class="panel">
    <h2 class="panel__title">
        <?= $isEditing ? 'Editar noticia #' . e((string)($editingNews['id'] ?? '')) : 'Nueva noticia' ?>
        <?php if ($isEditing): ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(admin_url('news')) ?>">
            + Añadir otra
        </a>
        <?php endif; ?>
    </h2>

    <form method="post"
          action="<?= e(admin_url()) ?>"
          enctype="multipart/form-data"
          autocomplete="off"
          novalidate
          class="js-news-form">

        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="save_news">
        <input type="hidden" name="redirect_section" value="news">
        <input type="hidden" name="redirect_news_view" value="<?= $isEditing ? 'manage' : 'add' ?>">
        <?php if ($isEditing): ?>
        <input type="hidden" name="id" value="<?= (int)($editingNews['id'] ?? 0) ?>">
        <input type="hidden" name="current_ruta_imagen"
               value="<?= e(normalize_news_card_image_path((string)($editingNews['ruta_imagen'] ?? ''))) ?>">
        <?php endif; ?>

        <div class="form-grid">

            <?php /* Fecha, título, estado */ ?>
            <div class="grid-2">
                <div class="form-group">
                    <label class="label label--required" for="fecha_creaccion">Fecha de publicación</label>
                    <input id="fecha_creaccion" name="fecha_creaccion" type="datetime-local" class="input"
                           value="<?= e(datetime_for_input($isEditing ? (string)($editingNews['fecha_creaccion'] ?? '') : '')) ?>"
                           required>
                </div>
                <div class="form-group">
                    <label class="label label--required" for="estado">Estado</label>
                    <select id="estado" name="estado" class="select">
                        <?php foreach (['borrador' => 'Borrador', 'publicada' => 'Publicada', 'archivada' => 'Archivada'] as $val => $label): ?>
                        <option value="<?= e($val) ?>"
                            <?= ($isEditing ? ($editingNews['estado'] ?? 'borrador') : 'borrador') === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="label label--required" for="titulo">Título</label>
                <input id="titulo" name="titulo" type="text" class="input"
                       value="<?= e((string)($editingNews['titulo'] ?? '')) ?>"
                       maxlength="255" required>
            </div>

            <?php /* Imagen de tarjeta */ ?>
            <div class="form-group">
                <label class="label">Imagen de tarjeta (portada)</label>

                <?php /* Vista previa de la imagen actual al editar */ ?>
                <?php if ($isEditing): ?>
                    <?php $imgPath = normalize_news_card_image_path((string)($editingNews['ruta_imagen'] ?? '')); ?>
                    <?php if (!is_default_news_card_image_path($imgPath)): ?>
                    <div style="margin-bottom:.75rem">
                        <img src="<?= e(asset($imgPath)) ?>" alt="Imagen actual"
                             style="height:80px;border-radius:6px;object-fit:cover;border:1px solid var(--a-border)">
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php /* Selector de imagen existente */ ?>
                <input type="hidden" name="selected_card_image" id="selected_card_image" value="">

                <?php if (!empty($newsImageItems)): ?>
                <div class="img-picker" id="img-picker">
                    <div class="img-picker__preview" id="img-picker-preview" hidden>
                        <img id="img-picker-preview-thumb" src="" alt="">
                        <span id="img-picker-preview-name"></span>
                        <button type="button" class="btn btn--ghost btn--sm" id="img-picker-clear">
                            Quitar selección
                        </button>
                    </div>

                    <div class="img-picker__grid media-grid" id="img-picker-grid">
                        <?php foreach ($newsImageItems as $img): ?>
                        <?php
                            $imgRelPath = (string)($img['relativePath'] ?? '');
                            $imgName    = (string)($img['name'] ?? basename($imgRelPath));
                        ?>
                        <button type="button"
                                class="media-card img-picker__item"
                                data-img-path="<?= e($imgRelPath) ?>"
                                data-img-src="<?= e(asset($imgRelPath)) ?>"
                                data-img-name="<?= e($imgName) ?>"
                                title="<?= e($imgName) ?>">
                            <img class="media-card__thumb"
                                 src="<?= e(asset($imgRelPath)) ?>"
                                 alt="<?= e($imgName) ?>"
                                 loading="lazy">
                            <div class="media-card__body">
                                <p class="media-card__name"><?= e($imgName) ?></p>
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <p class="helper">
                    No hay imágenes de portada disponibles.
                    <a href="<?= e(admin_url('images')) ?>">Sube imágenes desde la sección Imágenes</a>
                    para poder seleccionarlas aquí.
                </p>
                <?php endif; ?>
            </div>

            <?php /* Editor de texto enriquecido */ ?>
            <div class="form-group">
                <label class="label label--required">Contenido</label>

                <div class="rte-toolbar" role="toolbar" aria-label="Barra de herramientas del editor">
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="bold"
                            title="Negrita" aria-label="Negrita"><strong>N</strong></button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="italic"
                            title="Cursiva" aria-label="Cursiva"><em>K</em></button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="underline"
                            title="Subrayado" aria-label="Subrayado"><u>S</u></button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="strikeThrough"
                            title="Tachado" aria-label="Tachado"><s>T</s></button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="justifyLeft"
                            title="Alinear izquierda" aria-label="Alinear izquierda">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="0" y="1" width="14" height="2" rx="1"/><rect x="0" y="5" width="9" height="2" rx="1"/><rect x="0" y="9" width="14" height="2" rx="1"/><rect x="0" y="13" width="6" height="0" rx="1"/></svg>
                        Izq.
                    </button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="justifyCenter"
                            title="Centrar" aria-label="Centrar">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="0" y="1" width="14" height="2" rx="1"/><rect x="2.5" y="5" width="9" height="2" rx="1"/><rect x="0" y="9" width="14" height="2" rx="1"/></svg>
                        Cen.
                    </button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="justifyRight"
                            title="Alinear derecha" aria-label="Alinear derecha">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="0" y="1" width="14" height="2" rx="1"/><rect x="5" y="5" width="9" height="2" rx="1"/><rect x="0" y="9" width="14" height="2" rx="1"/></svg>
                        Der.
                    </button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="outdent"
                            title="Disminuir sangría" aria-label="Disminuir sangría">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="4" y="1" width="10" height="2" rx="1"/><rect x="4" y="5" width="10" height="2" rx="1"/><rect x="4" y="9" width="10" height="2" rx="1"/><polygon points="3,5 0,7 3,9"/></svg>
                        ←
                    </button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="indent"
                            title="Aumentar sangría" aria-label="Aumentar sangría">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><rect x="0" y="1" width="10" height="2" rx="1"/><rect x="0" y="5" width="10" height="2" rx="1"/><rect x="0" y="9" width="10" height="2" rx="1"/><polygon points="11,5 14,7 11,9"/></svg>
                        →
                    </button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="insertUnorderedList"
                            title="Lista sin orden">• Lista</button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="insertOrderedList"
                            title="Lista numerada">1. Lista</button>
                    <button type="button" class="btn btn--ghost btn--sm rte-btn" data-command="createLink"
                            title="Insertar enlace">🔗</button>
                    <select class="rte-select" data-rte-font-size title="Tamaño de fuente" aria-label="Tamaño de fuente">
                        <option value="">Tamaño</option>
                        <option value="0.85rem">Pequeño</option>
                        <option value="1rem">Normal</option>
                        <option value="1.15rem">Grande</option>
                        <option value="1.35rem">Título</option>
                    </select>
                    <label class="rte-color-group" title="Color de texto">
                        T
                        <input type="color" value="#111827" data-rte-fore-color aria-label="Color de texto">
                    </label>
                    <label class="rte-color-group" title="Color de fondo">
                        Fondo
                        <input type="color" value="#ffffff" data-rte-back-color aria-label="Color de fondo">
                    </label>
                </div>

                <div id="texto-editor"
                     class="rte-editor"
                     contenteditable="true"
                     role="textbox"
                     aria-multiline="true"
                     aria-label="Contenido de la noticia"
                     aria-required="true"><?= $isEditing ? ($editingNews['texto'] ?? '') : '' ?></div>

                <textarea id="texto" name="texto" class="rte-source" hidden
                          aria-hidden="true"><?= e((string)($editingNews['texto'] ?? '')) ?></textarea>
            </div>

            <?php /* Imágenes adjuntas */ ?>
            <div class="form-group">
                <label class="label">Imágenes adicionales</label>
                <div class="file-group"
                     data-file-group
                     data-input-name="news_images[]"
                     data-input-id-prefix="news_img"
                     data-accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml">
                    <div class="file-row" data-file-row>
                        <input id="news_img_1" name="news_images[]" type="file" class="input"
                               accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml">
                        <button type="button" class="btn btn--ghost btn--sm" data-remove-file-row hidden>
                            Quitar
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-add-file-row style="margin-top:.35rem">
                    + Añadir imagen
                </button>
                <p class="helper">JPEG, PNG, WebP, GIF o SVG. Máx. 8 MB por imagen.</p>
            </div>

            <?php /* Documentos adjuntos */ ?>
            <div class="form-group">
                <label class="label">Documentos adjuntos</label>
                <div class="file-group"
                     data-file-group
                     data-input-name="news_documents[]"
                     data-input-id-prefix="news_doc"
                     data-accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.ppt,.pptx">
                    <div class="file-row" data-file-row>
                        <input id="news_doc_1" name="news_documents[]" type="file" class="input"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.ppt,.pptx">
                        <button type="button" class="btn btn--ghost btn--sm" data-remove-file-row hidden>
                            Quitar
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn--ghost btn--sm" data-add-file-row style="margin-top:.35rem">
                    + Añadir documento
                </button>
                <p class="helper">PDF, Word, Excel, LibreOffice o PowerPoint. Máx. 20 MB por archivo.</p>
            </div>

            <?php /* Submit */ ?>
            <div class="btn-group">
                <button type="submit" class="btn btn--primary js-submit-btn">
                    <?= $isEditing ? 'Guardar cambios' : 'Crear noticia' ?>
                </button>
                <?php if ($isEditing): ?>
                <a class="btn btn--ghost" href="<?= e(admin_url('news', 'manage')) ?>">
                    Cancelar edición
                </a>
                <?php endif; ?>
            </div>

        </div><?php /* .form-grid */ ?>
    </form>
</div>

<?php /* Adjuntos actuales — FUERA del formulario principal para evitar forms anidados */ ?>
<?php if ($isEditing && is_array($editingNewsAttachments) && count($editingNewsAttachments) > 0): ?>
<div class="panel" style="margin-top:1rem">
    <h2 class="panel__title">Adjuntos actuales</h2>
    <ul class="attachments-list">
        <?php foreach ($editingNewsAttachments as $att): ?>
        <?php
            $attPath  = (string)($att['ruta_archivo'] ?? '');
            $attLabel = (string)($att['nombre_original'] ?? basename($attPath));
        ?>
        <li class="attachments-list__item">
            <a class="attachments-list__link"
               href="<?= e(asset($attPath)) ?>"
               target="_blank" rel="noopener">
                <?= e($attLabel) ?>
            </a>
            <span style="color:var(--a-muted);font-size:.8rem">
                <?= e(ucfirst((string)($att['tipo'] ?? ''))) ?>
            </span>
            <form method="post" action="<?= e(admin_url()) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_news_attachment">
                <input type="hidden" name="attachment_id" value="<?= (int)$att['id'] ?>">
                <input type="hidden" name="news_id" value="<?= (int)($editingNews['id'] ?? 0) ?>">
                <button type="submit" class="btn btn--danger btn--sm"
                        onclick="return confirm('¿Eliminar este adjunto?')">
                    Eliminar
                </button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php else: ?>
<?php /* ═══════════════════════════════════════════
        GESTIONAR NOTICIAS
   ═══════════════════════════════════════════ */ ?>

<div class="panel">
    <h2 class="panel__title">Noticias publicadas</h2>

    <?php if (empty($newsItems)): ?>
    <p style="color:var(--a-muted);padding:.5rem 0">No hay noticias registradas todavía.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th style="width:1%">Portada</th>
                <th>Título</th>
                <th>Estado</th>
                <th style="min-width:160px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($newsItems as $item): ?>
        <?php
            $itemId    = (int)($item['id'] ?? 0);
            $itemTitle = (string)($item['titulo'] ?? '');
            $itemDate  = (string)($item['fecha_creaccion'] ?? '');
            $itemState = (string)($item['estado'] ?? 'borrador');
            $itemImg   = normalize_news_card_image_path((string)($item['ruta_imagen'] ?? ''));
            $displayDate = $itemDate !== '' ? date('d/m/Y H:i', strtotime($itemDate)) : '—';
        ?>
        <tr>
            <td style="white-space:nowrap"><?= e($displayDate) ?></td>
            <td style="width:52px">
                <img src="<?= e(asset($itemImg)) ?>"
                     alt=""
                     style="width:52px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--a-border);background:#f3f4f6;display:block"
                     loading="lazy">
            </td>
            <td class="td-clamp">
                <span class="td-clamp__text"><?= e($itemTitle) ?></span>
                <button type="button" class="td-clamp__btn">ver más</button>
            </td>
            <td>
                <span class="badge badge--<?= e($itemState) ?>">
                    <?= e(ucfirst($itemState)) ?>
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <a class="btn btn--ghost btn--sm"
                       href="<?= e(admin_url('news', 'manage', $itemId)) ?>">
                        Editar
                    </a>
                    <form method="post" action="<?= e(admin_url()) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="send_push_news">
                        <input type="hidden" name="id" value="<?= $itemId ?>">
                        <button type="submit" class="btn btn--ghost btn--sm"
                                title="Enviar push de esta noticia">
                            🔔 Push
                        </button>
                    </form>
                    <form method="post" action="<?= e(admin_url()) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete_news">
                        <input type="hidden" name="id" value="<?= $itemId ?>">
                        <button type="submit" class="btn btn--danger btn--sm"
                                onclick="return confirm('¿Eliminar la noticia «<?= e(addslashes($itemTitle)) ?>»? Esta acción no se puede deshacer.')">
                            Eliminar
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; /* newsView */ ?>
