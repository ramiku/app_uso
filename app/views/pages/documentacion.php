<?php
declare(strict_types=1);

$documents = $data['documents'] ?? [];
$calendars = $data['calendars'] ?? [];
?>
<section class="section container" aria-labelledby="documentacion-title">
    <div class="docs-section">
        <div class="section__heading">
            <h2 id="documentacion-title" class="docs-section__title">Documentos</h2>
        </div>
        <div class="docs-grid">
            <?php if ($documents === []): ?>
                <p class="section__lead">Todavía no hay documentos disponibles.</p>
            <?php else: ?>
                <?php foreach ($documents as $document): ?>
                    <?php
                    $documentTitle = (string)($document['title'] ?? 'Documento');
                    $documentPath = (string)($document['path'] ?? 'files');
                    $documentDownloadName = build_download_filename($documentTitle, $documentPath, 'documento');
                    ?>
                    <article class="doc-card">
                        <div class="doc-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M7 2h7l5 5v14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1zm7 1.5V8h4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12h6M9 16h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </div>
                        <h3 class="doc-card__title"><?php echo e($documentTitle); ?></h3>
                        <a class="button button--ghost doc-card__download" href="<?php echo e(asset($documentPath)); ?>" download="<?php echo e($documentDownloadName); ?>">Descargar documento</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="docs-section">
        <div class="section__heading">
            <h2 class="docs-section__title">Calendarios</h2>
        </div>
        <div class="docs-grid">
            <?php if ($calendars === []): ?>
                <p class="section__lead">Todavía no hay calendarios disponibles.</p>
            <?php else: ?>
                <?php foreach ($calendars as $calendar): ?>
                    <?php
                    $calendarTitle = (string)($calendar['title'] ?? 'Calendario');
                    $calendarPath = (string)($calendar['path'] ?? 'files/calendarios');
                    $calendarDownloadName = build_download_filename($calendarTitle, $calendarPath, 'calendario');
                    ?>
                    <article class="doc-card">
                        <div class="doc-card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 10h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        </div>
                        <h3 class="doc-card__title"><?php echo e($calendarTitle); ?></h3>
                        <a class="button button--ghost doc-card__download" href="<?php echo e(asset($calendarPath)); ?>" download="<?php echo e($calendarDownloadName); ?>">Descargar calendario</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
