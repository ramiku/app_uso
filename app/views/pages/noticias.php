<?php
declare(strict_types=1);

$news = $data['news'] ?? [];
$pagination = $data['pagination'] ?? null;
$isDetail = (bool)($data['isDetail'] ?? false);
$selectedNews = $data['selectedNews'] ?? null;
$isSearch = (bool)($data['isSearch'] ?? false);
$searchQuery = (string)($data['searchQuery'] ?? '');
$searchTotal = (int)($data['searchTotal'] ?? 0);
?>
<section class="section container" aria-labelledby="listado-noticias">
    <?php if ($isDetail && is_array($selectedNews)): ?>
        <article class="news-detail">
            <?php
            $detailDateRaw = (string)($selectedNews['date'] ?? '');
            $detailTimestamp = strtotime($detailDateRaw);
            $detailDateIso = $detailTimestamp !== false ? date('Y-m-d', $detailTimestamp) : '';
            $detailDateHuman = $detailTimestamp !== false ? date('d/m/Y', $detailTimestamp) : '';
            $detailTitle = (string)($selectedNews['title'] ?? 'Noticia');
            $detailId = (int)($selectedNews['id'] ?? 0);
            $detailAttachments = is_array($selectedNews['attachments'] ?? null) ? $selectedNews['attachments'] : ['images' => [], 'documents' => []];
            $detailImages = is_array($detailAttachments['images'] ?? null) ? $detailAttachments['images'] : [];
            $detailDocuments = is_array($detailAttachments['documents'] ?? null) ? $detailAttachments['documents'] : [];
            ?>
            <header class="news-detail__header">
                <p class="news-detail__kicker">USO OEST informa</p>
                <h1 id="listado-noticias" class="news-detail__title"><?php echo e($detailTitle); ?></h1>
                <div class="news-detail__rule" aria-hidden="true">
                    <?php if ($detailDateHuman !== ''): ?>
                        <time class="news-detail__date" datetime="<?php echo e($detailDateIso); ?>"><?php echo e($detailDateHuman); ?></time>
                    <?php endif; ?>
                </div>
                <div class="news-detail__actions js-news-actions" data-news-id="<?php echo e((string)$detailId); ?>" data-news-title="<?php echo e($detailTitle); ?>" data-share-endpoint="<?php echo e(BASE_URL . '/app/api/news_share.php'); ?>" aria-label="Acciones de la noticia">
                    <button type="button" class="news-detail__action-btn js-copy-news-link" aria-label="Copiar enlace de la noticia" title="Copiar enlace">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="9" y="9" width="11" height="11" rx="2"></rect>
                            <path d="M15 9V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h3"></path>
                        </svg>
                    </button>
                    <button type="button" class="news-detail__action-btn js-open-share-mail" aria-label="Enviar noticia por correo" title="Enviar por correo">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                            <path d="M4 8l8 5 8-5"></path>
                        </svg>
                    </button>
                    <span class="news-detail__action-feedback js-news-action-feedback" aria-live="polite"></span>
                </div>
            </header>
            <div class="news-detail__body">
                <?php
                $rawBody = (string)($selectedNews['text'] ?? '');
                $hasHtml = $rawBody !== strip_tags($rawBody);
                ?>
                <?php if ($hasHtml): ?>
                    <?php echo $rawBody; ?>
                <?php else: ?>
                    <?php foreach (preg_split('/\R/u', $rawBody) as $paragraph): ?>
                        <?php if (trim((string)$paragraph) !== ''): ?>
                            <p><?php echo e((string)$paragraph); ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($detailImages !== []): ?>
                <section class="news-detail__attachments" aria-label="Imágenes de la noticia">
                    <h2 class="news-detail__attachments-title">Imágenes asociadas</h2>
                    <div class="news-detail__images-grid">
                        <?php foreach ($detailImages as $imageItem): ?>
                            <?php
                            $imageUrl = (string)($imageItem['url'] ?? '');
                            $imageName = (string)($imageItem['name'] ?? 'Imagen asociada');
                            ?>
                            <?php if ($imageUrl !== ''): ?>
                                <button type="button"
                                        class="news-detail__image-link js-open-image-modal"
                                        data-img-src="<?php echo e($imageUrl); ?>"
                                        data-img-name="<?php echo e($imageName); ?>"
                                        aria-label="Ver imagen: <?php echo e($imageName); ?>">
                                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($imageName); ?>" loading="lazy">
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($detailDocuments !== []): ?>
                <section class="news-detail__attachments" aria-label="Documentos de la noticia">
                    <h2 class="news-detail__attachments-title">Documentos asociados</h2>
                    <ul class="news-detail__documents-list">
                        <?php foreach ($detailDocuments as $documentItem): ?>
                            <?php
                            $documentUrl = (string)($documentItem['url'] ?? '');
                            $documentName = (string)($documentItem['name'] ?? 'Documento asociado');
                            $documentDownloadName = build_download_filename($documentName, $documentUrl, 'documento');
                            $isPdf = strtolower(pathinfo($documentUrl, PATHINFO_EXTENSION)) === 'pdf';
                            ?>
                            <?php if ($documentUrl !== ''): ?>
                                <li>
                                    <?php if ($isPdf): ?>
                                        <button type="button"
                                                class="news-detail__doc-btn js-open-pdf-modal"
                                                data-pdf-src="<?php echo e($documentUrl); ?>"
                                                data-pdf-name="<?php echo e($documentName); ?>"
                                                data-pdf-download="<?php echo e($documentDownloadName); ?>">
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <?php echo e($documentName); ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo e($documentUrl); ?>" download="<?php echo e($documentDownloadName); ?>"><?php echo e($documentName); ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </article>

        <!-- Modal visualizador de imagen -->
        <div class="media-modal media-modal--image" id="image-modal" hidden aria-modal="true" role="dialog" aria-label="Imagen ampliada">
            <div class="media-modal__backdrop js-close-image-modal" aria-hidden="true"></div>
            <div class="media-modal__box">
                <button type="button" class="media-modal__close js-close-image-modal" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="media-modal__content">
                    <img id="image-modal-img" src="" alt="" class="media-modal__image">
                </div>
                <div class="media-modal__footer">
                    <span id="image-modal-name" class="media-modal__caption"></span>
                    <a id="image-modal-download" href="" download class="button button--ghost media-modal__download">
                        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Descargar imagen
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal visualizador de PDF -->
        <div class="media-modal media-modal--pdf" id="pdf-modal" hidden aria-modal="true" role="dialog" aria-label="Visualizador de documento">
            <div class="media-modal__backdrop js-close-pdf-modal" aria-hidden="true"></div>
            <div class="media-modal__box">
                <button type="button" class="media-modal__close js-close-pdf-modal" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.5" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="media-modal__content">
                    <div id="pdf-canvas-container" class="media-modal__pdf-canvas-wrap"></div>
                </div>
                <div class="media-modal__footer">
                    <span id="pdf-modal-name" class="media-modal__caption"></span>
                    <a id="pdf-modal-download" href="" download class="button button--ghost media-modal__download">
                        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Descargar PDF
                    </a>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" defer></script>

        <div class="news-share-modal" id="news-share-modal" hidden>
            <div class="news-share-modal__backdrop js-close-share-modal" aria-hidden="true"></div>
            <div class="news-share-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="news-share-modal-title">
                <h2 class="news-share-modal__title" id="news-share-modal-title">Enviar noticia por correo</h2>
                <p class="news-share-modal__text">Indica el correo al que enviarás esta noticia.</p>
                <form class="news-share-modal__form js-news-share-form" novalidate>
                    <label for="news-share-email">Correo destinatario</label>
                    <input id="news-share-email" type="email" name="email" autocomplete="email" placeholder="ejemplo@dominio.com" required>
                    <p class="news-share-modal__target">Se enviará a: <strong class="js-news-share-target">—</strong></p>
                    <p class="news-share-modal__status js-news-share-status" aria-live="polite"></p>
                    <div class="news-share-modal__actions">
                        <button type="button" class="button button--ghost js-close-share-modal">Cancelar</button>
                        <button type="submit" class="button">Enviar correo</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="section__heading">
            <h1 id="listado-noticias" class="section__title">Noticias</h1>
        </div>

        <form class="news-search" method="get" action="<?php echo e(BASE_URL . '/index.php'); ?>" role="search" aria-label="Buscar en noticias">
            <input type="hidden" name="page" value="noticias">
            <input
                class="news-search__input"
                type="search"
                name="q"
                placeholder="Buscar texto en noticias..."
                value="<?php echo e($searchQuery); ?>"
            >
            <button class="news-search__submit" type="submit" aria-label="Buscar">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="6.5"></circle>
                    <path d="M16 16l5 5"></path>
                </svg>
                <span class="sr-only">Buscar</span>
            </button>
            <?php if ($isSearch): ?>
                <a class="button button--ghost" href="<?php echo e(url_for('noticias')); ?>">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if ($isSearch): ?>
            <p class="section__lead">Se han encontrado <?php echo e((string)$searchTotal); ?> noticias que contienen “<?php echo e($searchQuery); ?>”.</p>
        <?php endif; ?>

        <div class="news-grid news-grid--dense">
            <?php foreach ($news as $newsItem): ?>
                <?php require APP_PATH . '/views/components/card_noticia.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if (!$isSearch): ?>
            <?php require APP_PATH . '/views/components/pagination.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
