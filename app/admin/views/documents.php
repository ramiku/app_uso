<?php
$navAddClass = ($documentsView === 'add') ? 'is-active' : '';
$navManClass = ($documentsView !== 'add') ? 'is-active' : '';
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Documentos</span>
        </nav>
        <h1 class="page-header__title">Documentos</h1>
    </div>
    <div class="btn-group">
        <a class="btn btn--ghost btn--sm <?= $navAddClass ?>"
           href="<?= e(admin_url('documents', 'add')) ?>">Subir</a>
        <a class="btn btn--ghost btn--sm <?= $navManClass ?>"
           href="<?= e(admin_url('documents', 'manage')) ?>">Gestionar</a>
    </div>
</div>

<?php if ($documentsView === 'add'): ?>
<?php /* ═══ Subir documento ═══ */ ?>

<div class="panel">
    <h2 class="panel__title">Subir documento</h2>
    <form method="post"
          action="<?= e(admin_url()) ?>"
          enctype="multipart/form-data"
          autocomplete="off"
          novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="upload_document">
        <div class="form-grid">
            <div class="form-group">
                <label class="label label--required" for="display_name">Nombre a mostrar</label>
                <input id="display_name" name="display_name" type="text" class="input"
                       maxlength="255" required placeholder="Ejemplo: Convenio colectivo 2025">
            </div>
            <div class="form-group">
                <label class="label label--required" for="document_target">Carpeta destino</label>
                <select id="document_target" name="document_target" class="select">
                    <option value="files">Documentos generales</option>
                    <option value="files/calendarios">Calendarios</option>
                </select>
            </div>
            <div class="form-group">
                <label class="label label--required" for="document_file">Archivo</label>
                <input id="document_file" name="document_file" type="file" class="input"
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.ppt,.pptx,.zip,.rar" required>
                <p class="helper">
                    PDF, Word, Excel, LibreOffice, PowerPoint, ZIP. Máx. 50 MB.
                </p>
            </div>
            <div>
                <button type="submit" class="btn btn--primary js-submit-btn">
                    Subir documento
                </button>
            </div>
        </div>
    </form>
</div>

<?php else: ?>
<?php /* ═══ Gestionar documentos ═══ */ ?>

<div class="panel">
    <h2 class="panel__title">Documentos subidos</h2>

    <?php if (empty($documentItems)): ?>
    <p style="color:var(--a-muted);padding:.5rem 0">No hay documentos registrados todavía.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Carpeta</th>
                <th>Archivo</th>
                <th style="min-width:100px">Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($documentItems as $doc): ?>
        <?php
            $docId      = (int)($doc['id'] ?? 0);
            $docName    = (string)($doc['displayName'] ?? '');
            $docPath    = (string)($doc['relativePath'] ?? '');
            $docFolder  = (string)($doc['folder'] ?? 'files');
        ?>
        <tr>
            <td>
                <a href="<?= e(asset($docPath)) ?>"
                   class="attachments-list__link"
                   target="_blank" rel="noopener"><?= e($docName) ?></a>
            </td>
            <td><?= e($docFolder ?: 'files') ?></td>
            <td style="font-size:.82rem;color:var(--a-muted)"><?= e(basename($docPath)) ?></td>
            <td>
                <form method="post" action="<?= e(admin_url()) ?>" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="document_id" value="<?= $docId ?>">
                    <input type="hidden" name="relative_path" value="<?= e($docPath) ?>">
                    <button type="submit" class="btn btn--danger btn--sm"
                            onclick="return confirm('¿Eliminar el documento «<?= e(addslashes($docName)) ?>»?')">
                        Eliminar
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; /* documentsView */ ?>
