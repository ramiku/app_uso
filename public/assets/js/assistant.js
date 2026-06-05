$(function () {
    var chatbot = document.getElementById('uso-chatbot');
    if (!chatbot) {
        return;
    }

    var endpoint = chatbot.getAttribute('data-endpoint');
    var messages = document.getElementById('chatbot-messages');
    var form = document.getElementById('chatbot-form');
    var input = document.getElementById('chatbot-input');
    var statusText = document.getElementById('chatbot-status');
    var chips = chatbot.querySelectorAll('.chatbot__chip');
    var submitButton = form.querySelector('button[type="submit"]');
    var authModal = document.getElementById('auth-modal');
    var authModalText = document.getElementById('auth-modal-text');
    var authModalForm = document.getElementById('auth-modal-form');
    var authModalInput = document.getElementById('auth-security-code');
    var authModalStatus = document.getElementById('auth-modal-status');
    var authModalSubmit = document.getElementById('auth-modal-submit');
    var userPromptHistory = [];

    function copyTextToClipboard(text) {
        if (!text) {
            return Promise.reject(new Error('empty'));
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var temp = document.createElement('textarea');
            temp.value = text;
            temp.setAttribute('readonly', 'readonly');
            temp.style.position = 'fixed';
            temp.style.opacity = '0';
            temp.style.pointerEvents = 'none';
            document.body.appendChild(temp);
            temp.focus();
            temp.select();

            try {
                var copied = document.execCommand('copy');
                document.body.removeChild(temp);
                if (!copied) {
                    reject(new Error('copy-failed'));
                    return;
                }
                resolve();
            } catch (error) {
                document.body.removeChild(temp);
                reject(error);
            }
        });
    }

    function addCopyActionToBotMessage(article, textToCopy) {
        if (!article || article.classList.contains('chatbot__message--user')) {
            return;
        }

        if (article.querySelector('.chatbot__copy-btn')) {
            return;
        }

        var safeText = String(textToCopy || '').trim();
        if (!safeText) {
            return;
        }

        var actions = document.createElement('div');
        actions.className = 'chatbot__message-actions';

        var copyButton = document.createElement('button');
        copyButton.type = 'button';
        copyButton.className = 'chatbot__copy-btn';
        copyButton.setAttribute('aria-label', 'Copiar respuesta');
        copyButton.setAttribute('title', 'Copiar respuesta');
        copyButton.textContent = '⧉';

        copyButton.addEventListener('click', function () {
            copyTextToClipboard(safeText).then(function () {
                copyButton.classList.add('is-copied');
                copyButton.textContent = '✓';
                window.setTimeout(function () {
                    copyButton.classList.remove('is-copied');
                    copyButton.textContent = '⧉';
                }, 1200);
            }).catch(function () {
                copyButton.classList.add('is-copied');
                copyButton.textContent = '!';
                window.setTimeout(function () {
                    copyButton.classList.remove('is-copied');
                    copyButton.textContent = '⧉';
                }, 1200);
            });
        });

        actions.appendChild(copyButton);
        article.appendChild(actions);
    }

    function openAuthModal(message) {
        if (!authModal) {
            return;
        }
        authModal.hidden = false;
        authModal.setAttribute('aria-hidden', 'false');
        authModal.classList.add('is-open');
        authModalText.textContent = message || 'El uso del asesor con IA es una funcionalidad exclusiva para afiliados.';
        authModalStatus.textContent = '';
        authModalInput.value = '';
        document.body.style.overflow = 'hidden';
        authModalInput.focus();
    }

    function closeAuthModal() {
        if (!authModal) {
            return;
        }
        authModal.hidden = true;
        authModal.setAttribute('aria-hidden', 'true');
        authModal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function setAuthStatus(text, type) {
        authModalStatus.textContent = text || '';
        authModalStatus.classList.remove('is-error', 'is-success');
        if (type === 'error') {
            authModalStatus.classList.add('is-error');
        }
        if (type === 'success') {
            authModalStatus.classList.add('is-success');
        }
    }

    function setAuthPending(isPending) {
        authModalInput.disabled = isPending;
        authModalSubmit.disabled = isPending;
        authModalSubmit.classList.toggle('is-loading', isPending);
        authModalSubmit.textContent = isPending ? 'Validando' : 'Validar código';
    }

    function appendMessage(text, role) {
        var article = document.createElement('article');
        article.className = 'chatbot__message chatbot__message--' + role;

        var paragraph = document.createElement('p');
        paragraph.textContent = text;
        article.appendChild(paragraph);

        if (role === 'bot') {
            addCopyActionToBotMessage(article, text);
        }

        messages.appendChild(article);
        messages.scrollTop = messages.scrollHeight;

        return article;
    }

    function renderLinks(container, links) {
        if (!Array.isArray(links) || !links.length) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'chatbot__resources';
        links.forEach(function (item) {
            if (!item || !item.url) {
                return;
            }

            var link = document.createElement('a');
            link.className = 'chatbot__resource-link';
            link.href = item.url;
            var url = String(item.url || '');
            var isDirectProtocol = url.indexOf('tel:') === 0 || url.indexOf('mailto:') === 0;
            if (!isDirectProtocol) {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            }
            link.textContent = item.label || item.url;

            if (isDirectProtocol) {
                var itemWrap = document.createElement('div');
                itemWrap.className = 'chatbot__resource-item';

                var copyButton = document.createElement('button');
                copyButton.type = 'button';
                copyButton.className = 'chatbot__resource-copy-btn';
                copyButton.setAttribute('aria-label', 'Copiar dato de contacto');
                copyButton.setAttribute('title', 'Copiar');
                copyButton.textContent = '⧉';

                var copyValue = url;
                if (url.indexOf('tel:') === 0) {
                    copyValue = decodeURIComponent(url.slice(4));
                }
                if (url.indexOf('mailto:') === 0) {
                    copyValue = decodeURIComponent(url.slice(7));
                }

                copyButton.addEventListener('click', function () {
                    copyTextToClipboard(copyValue).then(function () {
                        copyButton.classList.add('is-copied');
                        copyButton.textContent = '✓';
                        window.setTimeout(function () {
                            copyButton.classList.remove('is-copied');
                            copyButton.textContent = '⧉';
                        }, 1200);
                    }).catch(function () {
                        copyButton.classList.add('is-copied');
                        copyButton.textContent = '!';
                        window.setTimeout(function () {
                            copyButton.classList.remove('is-copied');
                            copyButton.textContent = '⧉';
                        }, 1200);
                    });
                });

                itemWrap.appendChild(link);
                itemWrap.appendChild(copyButton);
                wrap.appendChild(itemWrap);
                return;
            }

            wrap.appendChild(link);
        });
        container.appendChild(wrap);
    }

    function renderDownloads(container, downloads) {
        if (!Array.isArray(downloads) || !downloads.length) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'chatbot__resources';
        downloads.forEach(function (item) {
            if (!item || !item.url) {
                return;
            }
            var link = document.createElement('a');
            link.className = 'chatbot__resource-link chatbot__resource-link--download';
            link.href = item.url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            function sanitizeBaseName(value) {
                var safe = String(value || '').replace(/[\\/:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim();
                return safe || 'archivo';
            }

            var fileName = sanitizeBaseName(item.fileName || '');
            var extension = '';
            try {
                var parsedUrl = new URL(item.url, window.location.origin);
                var segments = parsedUrl.pathname.split('/');
                var lastSegment = segments[segments.length - 1] || '';
                if (lastSegment) {
                    var decodedSegment = decodeURIComponent(lastSegment);
                    var dotIndex = decodedSegment.lastIndexOf('.');
                    if (dotIndex > -1 && dotIndex < decodedSegment.length - 1) {
                        extension = decodedSegment.slice(dotIndex + 1);
                    }
                }
            } catch (error) {
                var fallback = (item.url || '').split('/').pop();
                if (fallback) {
                    var decodedFallback = decodeURIComponent(fallback);
                    var fallbackDotIndex = decodedFallback.lastIndexOf('.');
                    if (fallbackDotIndex > -1 && fallbackDotIndex < decodedFallback.length - 1) {
                        extension = decodedFallback.slice(fallbackDotIndex + 1);
                    }
                }
            }

            if (!item.fileName) {
                fileName = sanitizeBaseName(item.label || 'archivo');
            }

            if (extension && !new RegExp('\\.' + extension.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&') + '$', 'i').test(fileName)) {
                fileName += '.' + extension;
            }

            link.setAttribute('download', fileName);
            link.textContent = '⬇ ' + (item.label || 'Descargar archivo');
            wrap.appendChild(link);
        });
        container.appendChild(wrap);
    }

    function renderImages(container, imagesData) {
        if (!Array.isArray(imagesData) || !imagesData.length) {
            return;
        }

        var gallery = document.createElement('div');
        gallery.className = 'chatbot__images';
        imagesData.forEach(function (item) {
            if (!item || !item.url) {
                return;
            }
            var figure = document.createElement('figure');
            figure.className = 'chatbot__image-item';

            var img = document.createElement('img');
            img.className = 'chatbot__image';
            img.src = item.url;
            img.alt = item.label || 'Imagen adjunta';
            img.loading = 'lazy';

            figure.appendChild(img);
            gallery.appendChild(figure);
        });

        container.appendChild(gallery);
    }

    function appendBotPayload(payload) {
        var article = appendMessage(payload.reply || 'No hay respuesta disponible.', 'bot');
        if (!payload.ui) {
            return;
        }

        renderLinks(article, payload.ui.links);
        renderDownloads(article, payload.ui.downloads);
        renderImages(article, payload.ui.images);

    }

    function setPendingState(isPending) {
        input.disabled = isPending;
        submitButton.disabled = isPending;
        submitButton.classList.toggle('is-loading', isPending);
        submitButton.textContent = isPending ? 'Consultando' : 'Enviar';
        statusText.textContent = isPending ? 'Consultando IA...' : 'Listo para responder.';
    }

    function getRecentUserMessages(limit) {
        var max = Number(limit || 0);
        if (!max || max < 1) {
            return [];
        }

        if (!userPromptHistory.length) {
            return [];
        }

        return userPromptHistory.slice(-max);
    }

    async function sendPrompt(prompt, displayText, options) {
        var text = (prompt || '').trim();
        if (!text) {
            return;
        }

        var skipFocus = Boolean(options && options.skipFocus === true);

        var previousUserMessages = getRecentUserMessages(5);
        userPromptHistory.push(text);
        appendMessage((displayText || text).trim(), 'user');
        input.value = '';
        setPendingState(true);

        try {
            var response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    message: text,
                    contextMessages: previousUserMessages
                })
            });

            var payload = await response.json();
            if (!response.ok || !payload.success) {
                appendMessage('No he podido procesar la consulta ahora mismo. Inténtalo de nuevo en unos segundos.', 'bot');
                statusText.textContent = 'Error de respuesta.';
                return;
            }

            if (payload.mode === 'auth_required') {
                openAuthModal(payload.message);
                statusText.textContent = 'Validación requerida para IA.';
                return;
            }

            appendBotPayload(payload);
            if (payload.mode === 'demo') {
                statusText.textContent = 'Modo demo activo.';
            } else if (payload.mode === 'rule') {
                statusText.textContent = 'Respuesta guiada.';
            } else if (payload.mode === 'ai') {
                statusText.textContent = 'Respuesta de IA completada.';
            }
        } catch (error) {
            appendMessage('Se produjo un problema de red al contactar con el asistente.', 'bot');
            statusText.textContent = 'Error de red.';
        } finally {
            setPendingState(false);
            if (!skipFocus) {
                input.focus();
            }
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendPrompt(input.value);
    });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var prompt = chip.getAttribute('data-prompt') || '';
            var label = (chip.textContent || '').trim();
            var isMobile = window.matchMedia('(max-width: 768px)').matches;
            sendPrompt(prompt, label || prompt, { skipFocus: isMobile });
        });
    });

    messages.querySelectorAll('.chatbot__message--bot').forEach(function (article) {
        var textNode = article.querySelector('p');
        var text = textNode ? String(textNode.textContent || '').trim() : '';
        addCopyActionToBotMessage(article, text);
    });

    if (authModalForm) {
        authModalForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            var securityCode = (authModalInput.value || '').trim();
            if (!securityCode) {
                setAuthStatus('Introduce el código de seguridad.', 'error');
                return;
            }

            setAuthStatus('');
            setAuthPending(true);

            try {
                var response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ action: 'unlock_ai', securityCode: securityCode })
                });

                var payload = await response.json();
                if (!response.ok || !payload.success) {
                    setAuthStatus(payload.message || 'No se pudo validar la clave.', 'error');
                    return;
                }

                if (payload.mode === 'auth_unlocked') {
                    setAuthStatus(payload.message || 'Clave validada correctamente.', 'success');
                    statusText.textContent = 'IA desbloqueada durante 24 horas.';
                    setTimeout(function () {
                        closeAuthModal();
                        input.focus();
                    }, 850);
                    return;
                }

                if (payload.mode === 'auth_invalid') {
                    setAuthStatus(payload.message || 'Clave incorrecta.', 'error');
                    return;
                }

                setAuthStatus('Respuesta inesperada del servidor.', 'error');
            } catch (error) {
                setAuthStatus('Error de red al validar la clave.', 'error');
            } finally {
                setAuthPending(false);
            }
        });

        authModal.querySelectorAll('[data-auth-close="true"]').forEach(function (node) {
            node.addEventListener('click', function () {
                closeAuthModal();
            });
        });
    }
});
