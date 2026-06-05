/* ====================================================
   USO Admin Panel — JavaScript
   Separado de uso_admin.php para mejor mantenimiento.
   ==================================================== */

(function () {
    'use strict';

    /* ╔══════════════════════════════════════════════╗
       ║ SIDEBAR MÓVIL                               ║
       ╚══════════════════════════════════════════════╝ */

    var sidebar  = document.getElementById('admin-sidebar');
    var overlay  = document.getElementById('admin-sidebar-overlay');
    var sidebarToggle = document.querySelector('.js-sidebar-toggle');

    if (sidebar && overlay && sidebarToggle) {
        function openSidebar() {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-visible');
            sidebarToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-visible');
            sidebarToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
        });

        overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });
    }

    /* ╔══════════════════════════════════════════════╗
       ║ AUTO-DISMISS DE MENSAJES FLASH              ║
       ╚══════════════════════════════════════════════╝ */

    document.querySelectorAll('.flash[data-auto-dismiss]').forEach(function (el) {
        var delay = parseInt(el.getAttribute('data-auto-dismiss') || '5500', 10);
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease, margin-bottom 0.4s ease';
            el.style.opacity = '0';
            el.style.marginBottom = '0';
            setTimeout(function () { el.remove(); }, 420);
        }, delay);
    });

    /* ╔══════════════════════════════════════════════╗
       ║ EDITOR DE TEXTO ENRIQUECIDO (RTE)           ║
       ╚══════════════════════════════════════════════╝ */

    var newsForm = document.querySelector('.js-news-form');
    if (newsForm) {
        var editor = newsForm.querySelector('#texto-editor');
        var source = newsForm.querySelector('#texto');

        if (editor && source) {

            /* Usar <p> como separador de párrafos (Chrome usa <div> por defecto) */
            document.execCommand('defaultParagraphSeparator', false, 'p');

            /* Sincronizar el div editable con el textarea oculto */
            function syncSource() {
                source.value = (editor.innerHTML || '').trim();
            }

            /* Devuelve el Range si la selección está dentro del editor */
            function selectionInEditor() {
                var sel = window.getSelection();
                if (!sel || sel.rangeCount === 0) return null;
                var range = sel.getRangeAt(0);
                var ancestor = range.commonAncestorContainer;
                if (!editor.contains(ancestor) && ancestor !== editor) return null;
                return range;
            }

            /* Aplica un estilo CSS inline al texto seleccionado */
            function applyStyle(property, value) {
                var range = selectionInEditor();
                if (!range || range.collapsed) {
                    alert('Selecciona primero el texto al que quieres aplicar el estilo.');
                    return;
                }
                var fragment = range.extractContents();
                var span = document.createElement('span');
                span.style.setProperty(property, value);
                span.appendChild(fragment);
                range.insertNode(span);

                var sel = window.getSelection();
                if (sel) {
                    sel.removeAllRanges();
                    var r = document.createRange();
                    r.selectNodeContents(span);
                    sel.addRange(r);
                }
                editor.focus();
                syncSource();
            }

            /* Botones de formato */
            newsForm.querySelectorAll('.rte-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var cmd = btn.getAttribute('data-command') || '';
                    if (!cmd) return;
                    if (cmd === 'createLink') {
                        var url = prompt('Introduce la URL del enlace:', 'https://');
                        if (!url || !url.trim()) return;
                        document.execCommand('createLink', false, url.trim());
                    } else {
                        document.execCommand(cmd, false);
                    }
                    editor.focus();
                    syncSource();
                });
            });

            editor.addEventListener('input', syncSource);

            /* Selector de tamaño de fuente */
            var fontSizeSelect = newsForm.querySelector('[data-rte-font-size]');
            if (fontSizeSelect) {
                fontSizeSelect.addEventListener('change', function () {
                    var size = fontSizeSelect.value;
                    if (!size) return;
                    applyStyle('font-size', size);
                    fontSizeSelect.value = '';
                });
            }

            /* Color de texto */
            var foreColor = newsForm.querySelector('[data-rte-fore-color]');
            if (foreColor) {
                foreColor.addEventListener('input', function () {
                    if (foreColor.value) applyStyle('color', foreColor.value);
                });
            }

            /* Color de fondo del texto */
            var backColor = newsForm.querySelector('[data-rte-back-color]');
            if (backColor) {
                backColor.addEventListener('input', function () {
                    if (backColor.value) applyStyle('background-color', backColor.value);
                });
            }

            /* ── Gestión de filas de ficheros múltiples ── */
            var fileGroups = newsForm.querySelectorAll('[data-file-group]');

            function updateRemoveButtons(group) {
                var rows = group.querySelectorAll('[data-file-row]');
                rows.forEach(function (row, idx) {
                    var btn = row.querySelector('[data-remove-file-row]');
                    if (btn) btn.hidden = (rows.length <= 1 && idx === 0);
                });
            }

            fileGroups.forEach(function (group) {
                var addBtn = group.parentElement && group.parentElement.querySelector('[data-add-file-row]');
                if (!addBtn) return;

                addBtn.addEventListener('click', function () {
                    var existingRows = group.querySelectorAll('[data-file-row]');
                    var nextIdx = existingRows.length + 1;
                    var inputName   = group.getAttribute('data-input-name') || '';
                    var idPrefix    = group.getAttribute('data-input-id-prefix') || 'file';
                    var accept      = group.getAttribute('data-accept') || '';

                    var row = document.createElement('div');
                    row.className = 'file-row';
                    row.setAttribute('data-file-row', '');

                    var input = document.createElement('input');
                    input.type = 'file';
                    input.name = inputName;
                    input.id   = idPrefix + '_' + nextIdx;
                    input.className = 'input';
                    if (accept) input.setAttribute('accept', accept);

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn--ghost btn--sm';
                    removeBtn.setAttribute('data-remove-file-row', '');
                    removeBtn.textContent = 'Quitar';

                    row.appendChild(input);
                    row.appendChild(removeBtn);
                    group.appendChild(row);
                    updateRemoveButtons(group);
                });

                group.addEventListener('click', function (e) {
                    var target = e.target;
                    if (!target || !target.matches('[data-remove-file-row]')) return;
                    e.preventDefault();
                    var row = target.closest('[data-file-row]');
                    if (row) { row.remove(); updateRemoveButtons(group); }
                });

                updateRemoveButtons(group);
            });

            /* Validación antes de enviar */
            newsForm.addEventListener('submit', function (e) {
                syncSource();
                if ((editor.textContent || '').trim() === '') {
                    e.preventDefault();
                    alert('El contenido de la noticia es obligatorio.');
                }
            });

            syncSource(); // inicial
        }
    }

    /* ╔══════════════════════════════════════════════╗
       ║ EDITOR DE ROTACIÓN DE CALENDARIO            ║
       ╚══════════════════════════════════════════════╝ */

    var calendarForm = document.querySelector('.js-calendar-form');
    if (calendarForm) {
        var weeksSelect = calendarForm.querySelector('[data-calendar-weeks]');
        var weekBlocks  = calendarForm.querySelectorAll('[data-week]');

        /* Mostrar/ocultar semanas según el número de semanas del ciclo */
        function applyWeeksVisibility() {
            if (!weeksSelect) return;
            var weeks = parseInt(weeksSelect.value || '1', 10) || 1;
            weekBlocks.forEach(function (block) {
                var w = parseInt(block.getAttribute('data-week') || '0', 10);
                block.hidden = w > weeks;
            });
        }

        /* Toggle trabajo / descanso en cada día */
        calendarForm.querySelectorAll('[data-calendar-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target') || '';
                if (!targetId) return;
                var input = calendarForm.querySelector('#' + CSS.escape(targetId));
                if (!input) return;

                var next = input.value === '1' ? '0' : '1';
                input.value = next;
                btn.classList.toggle('is-working', next === '1');
                btn.classList.toggle('is-rest',    next !== '1');

                var statusEl = btn.querySelector('.day-status');
                if (statusEl) statusEl.textContent = next === '1' ? 'Trabajo' : 'Descanso';
            });
        });

        if (weeksSelect) {
            weeksSelect.addEventListener('change', applyWeeksVisibility);
        }

        applyWeeksVisibility();
    }

    /* ╔══════════════════════════════════════════════╗
       ║ ESTADO DE CARGA EN BOTONES DE FORMULARIO    ║
       ╚══════════════════════════════════════════════╝ */

    document.querySelectorAll('form:not(.js-no-loading)').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submitBtns = form.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(function (btn) {
                if (!btn.closest('[data-no-loading]')) {
                    btn.disabled = true;
                    btn.style.opacity = '0.65';
                }
            });
        });
    });

    /* ╔══════════════════════════════════════════════╗
       ║ SELECTOR DE IMAGEN EXISTENTE (img-picker)   ║
       ╚══════════════════════════════════════════════╝ */

    var imgPickerToggle  = document.getElementById('img-picker-toggle');
    var imgPickerGrid    = document.getElementById('img-picker-grid');
    var imgPickerPreview = document.getElementById('img-picker-preview');
    var imgPickerThumb   = document.getElementById('img-picker-preview-thumb');
    var imgPickerName    = document.getElementById('img-picker-preview-name');
    var imgPickerClear   = document.getElementById('img-picker-clear');
    var imgPickerInput   = document.getElementById('selected_card_image');
    var newsCardFile     = document.getElementById('news_card_image');

    if (imgPickerToggle && imgPickerGrid) {
        // Toggle del panel
        imgPickerToggle.addEventListener('click', function () {
            var open = imgPickerToggle.getAttribute('aria-expanded') === 'true';
            imgPickerToggle.setAttribute('aria-expanded', String(!open));
            imgPickerGrid.hidden = open;
        });
    }

    if (imgPickerGrid) {
        // Seleccionar imagen
        imgPickerGrid.addEventListener('click', function (e) {
            var item = e.target.closest('.img-picker__item');
            if (!item) return;

            var path = item.getAttribute('data-img-path');
            var src  = item.getAttribute('data-img-src');
            var name = item.getAttribute('data-img-name');

            // Desmarcar other
            imgPickerGrid.querySelectorAll('.img-picker__item').forEach(function (el) {
                el.classList.remove('is-selected');
            });
            item.classList.add('is-selected');

            // Rellenar hidden input
            imgPickerInput.value = path;

            // Mostrar preview
            imgPickerThumb.src = src;
            imgPickerName.textContent = name;
            imgPickerPreview.hidden = false;

            // Limpiar el file input para que no entre en conflicto
            if (newsCardFile) newsCardFile.value = '';

            // Cerrar grid solo si hay toggle
            if (imgPickerToggle) {
                imgPickerToggle.setAttribute('aria-expanded', 'false');
                imgPickerGrid.hidden = true;
            }
        });

        // Quitar selección
        if (imgPickerClear) {
            imgPickerClear.addEventListener('click', function () {
                imgPickerInput.value = '';
                imgPickerPreview.hidden = true;
                imgPickerGrid.querySelectorAll('.img-picker__item').forEach(function (el) {
                    el.classList.remove('is-selected');
                });
            });
        }

        // Si el usuario sube un archivo nuevo, limpiar la selección
        if (newsCardFile) {
            newsCardFile.addEventListener('change', function () {
                if (newsCardFile.value !== '') {
                    imgPickerInput.value = '';
                    imgPickerPreview.hidden = true;
                    imgPickerGrid.querySelectorAll('.img-picker__item').forEach(function (el) {
                        el.classList.remove('is-selected');
                    });
                }
            });
        }
    }

    /* ╔══════════════════════════════════════════════╗
       ║ EXPANDIR TEXTO TRUNCADO EN TABLAS           ║
       ╚══════════════════════════════════════════════╝ */

    function initTdClamp() {
        document.querySelectorAll('.td-clamp__text').forEach(function (text) {
            var btn = text.parentElement.querySelector('.td-clamp__btn');
            if (!btn) return;
            // Mostrar botón solo si el texto está recortado
            btn.style.display = text.scrollHeight > text.clientHeight ? 'block' : 'none';
        });
    }

    initTdClamp();

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.td-clamp__btn');
        if (!btn) return;
        var cell = btn.closest('.td-clamp');
        if (!cell) return;

        var isOpen = cell.classList.toggle('is-expanded');
        btn.textContent = isOpen ? 'ver menos' : 'ver más';
    });

    /* ╔══════════════════════════════════════════════╗
       ║ MODAL RECUPERAR CONTRASEÑA                  ║
       ╚══════════════════════════════════════════════╝ */

    var forgotBtn      = document.getElementById('js-forgot-btn');
    var forgotBackdrop = document.getElementById('js-forgot-backdrop');
    var forgotClose    = document.getElementById('js-forgot-close');
    var forgotInput    = document.getElementById('reset_email_input');

    function openForgotModal() {
        if (!forgotBackdrop) return;
        forgotBackdrop.hidden = false;
        forgotBackdrop.removeAttribute('aria-hidden');
        if (forgotInput) forgotInput.focus();
        document.body.style.overflow = 'hidden';
    }

    function closeForgotModal() {
        if (!forgotBackdrop) return;
        forgotBackdrop.hidden = true;
        forgotBackdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (forgotBtn) forgotBtn.focus();
    }

    if (forgotBtn)     forgotBtn.addEventListener('click', openForgotModal);
    if (forgotClose)   forgotClose.addEventListener('click', closeForgotModal);

    if (forgotBackdrop) {
        // Cerrar al hacer clic en el fondo
        forgotBackdrop.addEventListener('click', function (e) {
            if (e.target === forgotBackdrop) closeForgotModal();
        });
        // Cerrar con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !forgotBackdrop.hidden) closeForgotModal();
        });
        // Si hay error en el reset, re-abrir el modal automáticamente
        var forgotForm = document.getElementById('js-forgot-form');
        if (forgotForm && forgotForm.querySelector('[role="alert"]')) {
            openForgotModal();
        }
    }

})()
