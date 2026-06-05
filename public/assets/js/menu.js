$(function () {
    var $menuToggle = $('.js-menu-toggle');
    var $nav = $('#main-navigation');
    var $submenuToggle = $('.js-submenu-toggle');

    // ── Drawer "Más" — vanilla JS (robusto en WebView Android/iOS) ────
    var moreToggleEl   = document.querySelector('.js-more-toggle');
    var moreDrawerEl   = document.getElementById('mobile-app-more');
    var moreBackdropEl = document.getElementById('mobile-app-more-backdrop');

    function openMore() {
        if (!moreDrawerEl || !moreToggleEl) { return; }
        moreDrawerEl.classList.add('is-open');
        moreDrawerEl.setAttribute('aria-hidden', 'false');
        moreToggleEl.classList.add('is-active');
        moreToggleEl.setAttribute('aria-expanded', 'true');
    }

    function closeMore() {
        if (!moreDrawerEl || !moreToggleEl) { return; }
        moreDrawerEl.classList.remove('is-open');
        moreDrawerEl.setAttribute('aria-hidden', 'true');
        moreToggleEl.classList.remove('is-active');
        moreToggleEl.setAttribute('aria-expanded', 'false');
    }

    if (moreToggleEl) {
        moreToggleEl.addEventListener('click', function () {
            if (moreDrawerEl && moreDrawerEl.classList.contains('is-open')) {
                closeMore();
            } else {
                // Cerrar el top nav si estuviese abierto
                $nav.removeClass('is-open');
                $menuToggle.attr('aria-expanded', 'false');
                openMore();
            }
        });
    }

    if (moreBackdropEl) {
        moreBackdropEl.addEventListener('click', closeMore);
    }

    if (moreDrawerEl) {
        moreDrawerEl.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMore);
        });
    }
    // ─────────────────────────────────────────────────────────────────────

    $menuToggle.on('click', function () {
        var expanded = $menuToggle.attr('aria-expanded') === 'true';
        $menuToggle.attr('aria-expanded', String(!expanded));
        $nav.toggleClass('is-open', !expanded);
    });

    $submenuToggle.on('click', function (event) {
        event.preventDefault();
        var button = $(event.currentTarget);
        var parentItem = button.closest('.site-nav__item--has-submenu');
        var expanded = button.attr('aria-expanded') === 'true';

        button.attr('aria-expanded', String(!expanded));
        parentItem.toggleClass('is-open', !expanded);
    });

    $(document).on('click', function (event) {
        var target = event.target;
        var navElement    = $nav[0];
        var toggleElement = $menuToggle[0];

        if (!navElement || !toggleElement) {
            return;
        }

        if (!navElement.contains(target) && !toggleElement.contains(target)) {
            $nav.removeClass('is-open');
            $menuToggle.attr('aria-expanded', 'false');
        }

        // Cerrar el drawer "Más" si el clic es fuera del panel y del botón
        if (moreDrawerEl && moreDrawerEl.classList.contains('is-open')) {
            var drawerPanel = moreDrawerEl.querySelector('.mobile-app-more__panel');
            if (drawerPanel && moreToggleEl &&
                !drawerPanel.contains(target) && !moreToggleEl.contains(target)) {
                closeMore();
            }
        }
    });
});
