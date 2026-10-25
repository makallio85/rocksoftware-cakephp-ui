/**
 * The components page's buttons that show toasts and ask a script's
 * question (window.KkConfirm.ask).
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target instanceof Element ? event.target.closest('[data-demo-toast]') : null;
        if (button) {
            var kind = button.getAttribute('data-demo-toast');
            var messages = {
                success: { title: 'Saved.', text: 'The provider settings are stored.' },
                error: { title: 'Could not send.', text: 'The gateway did not answer.' },
                warning: 'Check the reply address.',
                info: 'Refreshing the list…'
            };
            window.appBanner[kind](messages[kind]);
            return;
        }
        var asker = event.target instanceof Element ? event.target.closest('[data-demo-ask]') : null;
        if (asker) {
            window.KkConfirm.ask({
                title: 'Send to every recipient?',
                message: '47 messages will be queued.',
                button: 'Send',
                tone: 'primary'
            }).then(function (isConfirmed) {
                window.appBanner[isConfirmed ? 'success' : 'info'](isConfirmed ? 'Queued.' : 'Nothing was sent.');
            });
        }
    });
}());
