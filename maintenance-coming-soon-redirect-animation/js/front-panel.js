jQuery(function ($) {
    var panels = $('.wploti-access-panel');
    var body = $('body');

    function closePanels() {
        panels.removeClass('is-open').attr('aria-hidden', 'true');
        body.removeClass('wploti-panel-open');
    }

    $('.wploti-access-trigger').on('click', function () {
        var panel = panels.filter('[data-wploti-panel="' + $(this).data('wploti-panel') + '"]');
        var isOpen = panel.hasClass('is-open');
        closePanels();
        if (!isOpen) {
            panel.addClass('is-open').attr('aria-hidden', 'false').find('input:first').trigger('focus');
            body.addClass('wploti-panel-open');
        }
    });

    $('.wploti-panel-close').on('click', closePanels);

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closePanels();
        }
    });
});
