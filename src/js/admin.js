/* FRAP WordPress — admin (ES5 + jQuery). */
(function ($) {
    'use strict';

    // Copiar el token nuevo.
    $(document).on('click', '.frapwp-copy', function () {
        var btn = $(this);
        var input = btn.closest('.frapwp-new').find('.frapwp-copy-src').get(0);
        input.select();
        var done = function () {
            btn.text('Copiado');
            setTimeout(function () {
                btn.text('Copiar token');
            }, 1500);
        };
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value).then(done, function () {
                document.execCommand('copy');
                done();
            });
        } else {
            document.execCommand('copy');
            done();
        }
    });

    // Ver es requisito de crear/editar/eliminar: marcar otra acción marca Ver; desmarcar Ver desmarca el resto.
    $(document).on('change', '.frapwp-perm', function () {
        var box = $(this);
        var row = box.closest('tr');
        if (box.data('action') === 'read') {
            if (!box.prop('checked')) {
                row.find('.frapwp-perm').prop('checked', false);
            }
        } else if (box.prop('checked')) {
            row.find('.frapwp-perm[data-action="read"]').prop('checked', true);
        }
    });

    // Confirmar antes de eliminar.
    $(document).on('submit', '.frapwp-confirm', function (e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });
})(jQuery);
