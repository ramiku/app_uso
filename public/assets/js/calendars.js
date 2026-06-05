$(function () {
    var nav = document.querySelector('[data-calendar-nav]');
    if (!nav) {
        return;
    }

    var monthSelect = nav.querySelector('[data-month-select]');
    var prevButton = nav.querySelector('[data-month-prev]');
    var nextButton = nav.querySelector('[data-month-next]');
    var monthSections = Array.prototype.slice.call(document.querySelectorAll('[data-month]'));

    if (!monthSelect || monthSections.length === 0) {
        return;
    }

    function showMonth(month) {
        var safeMonth = Math.max(1, Math.min(12, parseInt(month, 10) || 1));

        monthSections.forEach(function (section) {
            var sectionMonth = parseInt(section.getAttribute('data-month') || '0', 10);
            section.hidden = sectionMonth !== safeMonth;
        });

        monthSelect.value = String(safeMonth);
    }

    monthSelect.addEventListener('change', function () {
        showMonth(monthSelect.value);
    });

    prevButton.addEventListener('click', function () {
        var current = parseInt(monthSelect.value || '1', 10);
        var target = current <= 1 ? 12 : current - 1;
        showMonth(target);
    });

    nextButton.addEventListener('click', function () {
        var current = parseInt(monthSelect.value || '1', 10);
        var target = current >= 12 ? 1 : current + 1;
        showMonth(target);
    });

    showMonth(monthSelect.value || 1);
});
