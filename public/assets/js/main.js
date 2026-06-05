$(function () {
    var robotElements = document.querySelectorAll('.assistant-float, .assistant-visual');
    var robotImages = document.querySelectorAll('.assistant-float__image, .assistant-visual__bot');
    var authorizedRobotSrc = BASE_ASSET + '/img/uso-assistant-bot-authorized.svg';

    robotImages.forEach(function (image) {
        if (!image.getAttribute('data-default-src')) {
            image.setAttribute('data-default-src', image.getAttribute('src') || '');
        }
    });

    function setRobotAuthorizedState(isAuthorized) {
        robotElements.forEach(function (element) {
            element.classList.toggle('robot--authorized', Boolean(isAuthorized));
        });

        robotImages.forEach(function (image) {
            var defaultSrc = image.getAttribute('data-default-src') || image.getAttribute('src') || '';
            image.setAttribute('src', isAuthorized ? authorizedRobotSrc : defaultSrc);
        });
    }

    async function syncRobotAuthState() {
        if (!robotElements.length) {
            return;
        }

        try {
            var response = await fetch(BASE_URL + '/app/api/assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: 'auth_status' })
            });

            if (!response.ok) {
                setRobotAuthorizedState(false);
                return;
            }

            var payload = await response.json();
            setRobotAuthorizedState(payload && payload.success && payload.authorized === true);
        } catch (error) {
            setRobotAuthorizedState(false);
        }
    }

    syncRobotAuthState();

    var $assistantFloat = $('.assistant-float');
    if ($assistantFloat.elements.length) {
        var floatLink = $assistantFloat.elements[0];
        var hideHintTimeout;

        function isMobileInteraction() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        function hideHint() {
            floatLink.classList.remove('is-hint-visible');
        }

        floatLink.addEventListener('click', function (event) {
            if (!isMobileInteraction()) {
                return;
            }

            if (!floatLink.classList.contains('is-hint-visible')) {
                event.preventDefault();
                floatLink.classList.add('is-hint-visible');

                clearTimeout(hideHintTimeout);
                hideHintTimeout = setTimeout(hideHint, 2600);
            }
        });

        document.addEventListener('touchstart', function (event) {
            if (!isMobileInteraction()) {
                return;
            }

            if (!floatLink.contains(event.target)) {
                hideHint();
            }
        }, { passive: true });
    }

    var newsActions = document.querySelector('.js-news-actions');
    var shareModal = document.getElementById('news-share-modal');

    /* ── Modal imagen ── */
    var imageModal     = document.getElementById('image-modal');
    var imageModalImg  = document.getElementById('image-modal-img');
    var imageModalName = document.getElementById('image-modal-name');
    var imageModalDl   = document.getElementById('image-modal-download');

    function openImageModal(src, name) {
        if (!imageModal || !imageModalImg) return;
        imageModalImg.src = src;
        imageModalImg.alt = name;
        if (imageModalName) imageModalName.textContent = name;
        if (imageModalDl)   { imageModalDl.href = src; imageModalDl.download = name; }
        imageModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        if (!imageModal) return;
        imageModal.hidden = true;
        if (imageModalImg) imageModalImg.src = '';
        document.body.style.overflow = '';
    }

    if (imageModal) {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-open-image-modal');
            if (trigger) { openImageModal(trigger.getAttribute('data-img-src'), trigger.getAttribute('data-img-name') || ''); return; }
            if (e.target.closest('.js-close-image-modal')) closeImageModal();
        });
    }

    /* ── Modal PDF (PDF.js) ── */
    var pdfModal        = document.getElementById('pdf-modal');
    var pdfCanvasWrap   = document.getElementById('pdf-canvas-container');
    var pdfModalName    = document.getElementById('pdf-modal-name');
    var pdfModalDl      = document.getElementById('pdf-modal-download');

    var PDFJS_SRC    = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
    var PDFJS_WORKER = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    function getPdfjsLib(cb) {
        var lib = window['pdfjs-dist/build/pdf'];
        if (lib) { cb(lib); return; }
        var s = document.createElement('script');
        s.src = PDFJS_SRC;
        s.onload = function () {
            var l = window['pdfjs-dist/build/pdf'];
            if (l) l.GlobalWorkerOptions.workerSrc = PDFJS_WORKER;
            cb(l || null);
        };
        s.onerror = function () { cb(null); };
        document.head.appendChild(s);
    }

    function renderPdfIntoContainer(src, container) {
        container.innerHTML = '<p class="media-modal__pdf-loading">Cargando PDF\u2026</p>';
        getPdfjsLib(function (pdfjsLib) {
            if (!pdfjsLib) {
                container.innerHTML = '<p class="media-modal__pdf-error">No se pudo cargar el visor de PDF.</p>';
                return;
            }
            pdfjsLib.getDocument({ url: src, withCredentials: false }).promise.then(function (pdf) {
                container.innerHTML = '';
                var total = pdf.numPages;
                // Renderizar páginas en orden secuencial para preservar el orden correcto
                function renderPage(num) {
                    pdf.getPage(num).then(function (page) {
                        var containerWidth = container.clientWidth || 600;
                        var baseViewport = page.getViewport({ scale: 1 });
                        var scale = Math.max(containerWidth / baseViewport.width, 0.5);
                        var viewport = page.getViewport({ scale: scale });
                        var canvas = document.createElement('canvas');
                        var ctx = canvas.getContext('2d');
                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        container.appendChild(canvas);
                        page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                            if (num < total) renderPage(num + 1);
                        });
                    });
                }
                renderPage(1);
            }).catch(function () {
                container.innerHTML = '<p class="media-modal__pdf-error">No se pudo abrir el PDF. Usa el botón de descarga.</p>';
            });
        });
    }

    function openPdfModal(src, name, downloadName) {
        if (!pdfModal || !pdfCanvasWrap) return;
        if (pdfModalName) pdfModalName.textContent = name;
        if (pdfModalDl)   { pdfModalDl.href = src; pdfModalDl.download = downloadName || name; }
        pdfModal.hidden = false;
        document.body.style.overflow = 'hidden';
        renderPdfIntoContainer(src, pdfCanvasWrap);
    }

    function closePdfModal() {
        if (!pdfModal) return;
        pdfModal.hidden = true;
        if (pdfCanvasWrap) pdfCanvasWrap.innerHTML = '';
        document.body.style.overflow = '';
    }

    if (pdfModal) {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-open-pdf-modal');
            if (trigger) { openPdfModal(trigger.getAttribute('data-pdf-src'), trigger.getAttribute('data-pdf-name') || '', trigger.getAttribute('data-pdf-download') || ''); return; }
            if (e.target.closest('.js-close-pdf-modal')) closePdfModal();
        });
    }

    /* Escape cierra cualquier modal media abierto */
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (imageModal && !imageModal.hidden) { closeImageModal(); return; }
        if (pdfModal && !pdfModal.hidden) { closePdfModal(); return; }
    });

    if (newsActions && shareModal) {
        var copyButton = newsActions.querySelector('.js-copy-news-link');
        var openShareButton = newsActions.querySelector('.js-open-share-mail');
        var feedbackNode = newsActions.querySelector('.js-news-action-feedback');
        var closeModalButtons = shareModal.querySelectorAll('.js-close-share-modal');
        var shareForm = shareModal.querySelector('.js-news-share-form');
        var shareInput = shareModal.querySelector('#news-share-email');
        var shareTarget = shareModal.querySelector('.js-news-share-target');
        var shareStatus = shareModal.querySelector('.js-news-share-status');
        var newsBodyNode = document.querySelector('.news-detail__body');
        var newsId = parseInt(newsActions.getAttribute('data-news-id') || '0', 10);
        var newsTitle = (newsActions.getAttribute('data-news-title') || '').trim() || 'Noticia';
        var shareEndpoint = (newsActions.getAttribute('data-share-endpoint') || '').trim();
        var newsBodyText = newsBodyNode ? (newsBodyNode.textContent || '').trim() : '';

        function setFeedback(message) {
            if (!feedbackNode) {
                return;
            }

            feedbackNode.textContent = message;
            if (message !== '') {
                window.setTimeout(function () {
                    if (feedbackNode.textContent === message) {
                        feedbackNode.textContent = '';
                    }
                }, 2000);
            }
        }

        async function copyCurrentLink() {
            var currentUrl = window.location.href;
            try {
                await navigator.clipboard.writeText(currentUrl);
                setFeedback('Enlace copiado');
            } catch (error) {
                var fallback = document.createElement('textarea');
                fallback.value = currentUrl;
                fallback.setAttribute('readonly', 'readonly');
                fallback.style.position = 'absolute';
                fallback.style.left = '-9999px';
                document.body.appendChild(fallback);
                fallback.select();
                document.execCommand('copy');
                document.body.removeChild(fallback);
                setFeedback('Enlace copiado');
            }
        }

        function openShareModal() {
            shareModal.hidden = false;
            if (shareStatus) {
                shareStatus.textContent = '';
                shareStatus.classList.remove('is-success');
            }
            if (shareInput) {
                shareInput.focus();
            }
        }

        function closeShareModal() {
            shareModal.hidden = true;
            if (shareForm) {
                shareForm.reset();
            }
            if (shareTarget) {
                shareTarget.textContent = '—';
            }
            if (shareStatus) {
                shareStatus.textContent = '';
                shareStatus.classList.remove('is-success');
            }
        }

        if (copyButton) {
            copyButton.addEventListener('click', copyCurrentLink);
        }

        if (openShareButton) {
            openShareButton.addEventListener('click', openShareModal);
        }

        closeModalButtons.forEach(function (button) {
            button.addEventListener('click', closeShareModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !shareModal.hidden) {
                closeShareModal();
            }
        });

        if (shareInput && shareTarget) {
            shareInput.addEventListener('input', function () {
                var email = (shareInput.value || '').trim();
                shareTarget.textContent = email !== '' ? email : '—';
            });
        }

        if (shareForm && shareInput) {
            shareForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                var email = (shareInput.value || '').trim();
                if (email === '' || !shareInput.checkValidity()) {
                    if (shareStatus) {
                        shareStatus.textContent = 'Introduce un correo válido.';
                        shareStatus.classList.remove('is-success');
                    }
                    shareInput.focus();
                    return;
                }

                if (!shareEndpoint) {
                    if (shareStatus) {
                        shareStatus.textContent = 'No se pudo determinar el endpoint de envío.';
                        shareStatus.classList.remove('is-success');
                    }
                    return;
                }

                if (shareStatus) {
                    shareStatus.textContent = 'Enviando correo...';
                    shareStatus.classList.remove('is-success');
                }

                try {
                    var response = await fetch(shareEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            email: email,
                            newsId: Number.isFinite(newsId) ? newsId : 0,
                            title: newsTitle,
                            url: window.location.href,
                            content: newsBodyText
                        })
                    });

                    var payload = await response.json();
                    if (!response.ok || !payload || payload.success !== true) {
                        throw new Error((payload && payload.message) ? payload.message : 'No se pudo enviar el correo.');
                    }

                    if (shareStatus) {
                        shareStatus.textContent = 'Correo enviado correctamente.';
                        shareStatus.classList.add('is-success');
                    }

                    setTimeout(function () {
                        closeShareModal();
                    }, 900);
                } catch (error) {
                    if (shareStatus) {
                        shareStatus.textContent = (error && error.message) ? error.message : 'No se pudo enviar el correo.';
                        shareStatus.classList.remove('is-success');
                    }
                }
            });
        }
    }

    /* ── Modal de contacto (Directorio) ── */
    var contactModal       = document.getElementById('contact-modal');
    var contactIconWrap    = document.getElementById('contact-modal-icon-wrap');
    var contactModalLabel  = document.getElementById('contact-modal-label');
    var contactModalValue  = document.getElementById('contact-modal-value');
    var contactCopyBtn     = document.getElementById('contact-modal-copy');
    var contactCopyLabel   = document.getElementById('contact-modal-copy-label');
    var contactActionBtn   = document.getElementById('contact-modal-action');
    var contactActionIcon  = document.getElementById('contact-modal-action-icon');
    var contactActionLabel = document.getElementById('contact-modal-action-label');
    var contactFeedback    = document.getElementById('contact-modal-feedback');

    var SVG_TEL   = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21 11.36 11.36 0 003.54.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.54a1 1 0 01-.24 1.04l-2.21 2.21z"/></svg>';
    var SVG_EMAIL = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
    var SVG_CALL  = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.11-.21 11.36 11.36 0 003.54.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.54a1 1 0 01-.24 1.04l-2.21 2.21z"/></svg>';
    var SVG_SEND  = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2z"/><polyline points="22,6 12,13 2,6"/></svg>';

    function openContactModal(type, label, value) {
        if (!contactModal) return;
        var isTel = type === 'tel';

        contactIconWrap.className = 'contact-modal__icon-wrap contact-modal__icon-wrap--' + type;
        contactIconWrap.innerHTML = isTel ? SVG_TEL : SVG_EMAIL;
        contactModalLabel.textContent = label;
        contactModalValue.textContent = value;
        contactCopyLabel.textContent  = isTel ? 'Copiar teléfono' : 'Copiar correo';

        contactActionIcon.innerHTML = isTel ? SVG_CALL : SVG_SEND;
        contactActionLabel.textContent = isTel ? 'Llamar' : 'Enviar correo';
        contactActionBtn.href = isTel ? 'tel:' + value : 'mailto:' + value;

        if (contactFeedback) contactFeedback.textContent = '';
        contactModal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeContactModal() {
        if (!contactModal) return;
        contactModal.hidden = true;
        document.body.style.overflow = '';
    }

    if (contactModal) {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-dir-contact');
            if (trigger) {
                openContactModal(
                    trigger.getAttribute('data-type'),
                    trigger.getAttribute('data-label'),
                    trigger.getAttribute('data-value')
                );
                return;
            }
            if (e.target.closest('.js-close-contact-modal')) closeContactModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !contactModal.hidden) closeContactModal();
        });

        if (contactCopyBtn) {
            contactCopyBtn.addEventListener('click', function () {
                var valueToCopy = contactModalValue ? contactModalValue.textContent.trim() : '';
                if (!valueToCopy) return;
                var feedback = contactFeedback;
                function showFeedback(msg) {
                    if (!feedback) return;
                    feedback.textContent = msg;
                    setTimeout(function () { if (feedback.textContent === msg) feedback.textContent = ''; }, 2000);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(valueToCopy).then(function () {
                        showFeedback('¡Copiado al portapapeles!');
                    }).catch(function () { showFeedback('No se pudo copiar.'); });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = valueToCopy;
                    ta.style.position = 'absolute';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); showFeedback('¡Copiado al portapapeles!'); }
                    catch (err) { showFeedback('No se pudo copiar.'); }
                    document.body.removeChild(ta);
                }
            });
        }
    }
});
