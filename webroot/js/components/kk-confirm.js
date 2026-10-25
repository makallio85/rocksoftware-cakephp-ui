/**
 * The confirmation before a destructive action, without inline script.
 *
 * CakePHP's `confirm` option renders its prompt as an `onclick` attribute,
 * which a Content-Security-Policy of `script-src 'self'` refuses to run, so
 * the control silently does nothing. Here the question lives in data
 * attributes and this file asks it:
 *
 *     <form method="post" action="/providers/delete/7">
 *         <button type="submit" data-confirm="Delete this provider?">Delete</button>
 *     </form>
 *
 * `UiHelper::postButton($title, $url, ['confirm' => …])` renders exactly that.
 *
 * Attributes on the submit button (or on the form, or on a link):
 *   data-confirm         the question; without it the form submits at once
 *   data-confirm-title   the dialog's heading (default "Are you sure?")
 *   data-confirm-button  the label of the button that goes ahead (default "Confirm")
 *   data-confirm-tone    `danger` (the default, a red button) or `primary`
 *
 * The question is the submitting button's own: a form with a Save and a
 * Delete button asks only for Delete. A link with `data-confirm` is followed
 * once confirmed.
 *
 * For a script: `window.KkConfirm.ask({ message, title, button, tone })`
 * returns a Promise of true (go ahead) or false.
 *
 * Loaded by the default layout when the `confirm` component is on, or on any
 * page that renders a `postButton()` with a question. Texts come from the
 * layout's `kk-ui-texts` block.
 */
(function () {
    'use strict';

    var DEFAULTS = {
        confirmTitle: 'Are you sure?',
        confirmButton: 'Confirm',
        confirmCancel: 'Cancel'
    };
    var texts = null;
    var sequence = 0;

    function text(key) {
        if (texts === null) {
            texts = {};
            var el = document.getElementById('kk-ui-texts');
            if (el) {
                try {
                    texts = JSON.parse(el.textContent) || {};
                } catch (e) {
                    texts = {};
                }
            }
        }

        return texts[key] || DEFAULTS[key];
    }

    function element(tag, className, content) {
        var el = document.createElement(tag);
        if (className) {
            el.className = className;
        }
        if (content !== undefined) {
            el.textContent = content;
        }

        return el;
    }

    /**
     * Asks the question in a modal dialog.
     *
     * @param {{message: string, title?: string, button?: string, tone?: string}} question
     * @returns {Promise<boolean>}
     */
    function ask(question) {
        if (typeof HTMLDialogElement !== 'function') {
            return Promise.resolve(window.confirm(question.message));
        }

        return new Promise(function (resolve) {
            sequence += 1;
            var id = 'kk-confirm-' + sequence;
            var dialog = element('dialog', 'kk-confirm');
            dialog.setAttribute('aria-labelledby', id + '-title');
            dialog.setAttribute('aria-describedby', id + '-body');

            var header = element('div', 'kk-confirm-header', question.title || text('confirmTitle'));
            header.id = id + '-title';
            var body = element('div', 'kk-confirm-body', question.message);
            body.id = id + '-body';
            var footer = element('div', 'kk-confirm-footer');
            var cancel = element('button', 'btn btn-outline-secondary btn-sm', text('confirmCancel'));
            cancel.type = 'button';
            var tone = question.tone === 'primary' ? 'btn-primary' : 'btn-danger';
            var ok = element('button', 'btn btn-sm ' + tone, question.button || text('confirmButton'));
            ok.type = 'button';
            ok.setAttribute('data-kk-confirm-ok', '');
            footer.append(cancel, ok);
            dialog.append(header, body, footer);

            var answer = false;
            function finish(value) {
                answer = value;
                dialog.close();
            }
            cancel.addEventListener('click', function () { finish(false); });
            ok.addEventListener('click', function () { finish(true); });
            // A click on the backdrop lands on the dialog itself.
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) {
                    finish(false);
                }
            });
            dialog.addEventListener('close', function () {
                dialog.remove();
                resolve(answer);
            });

            document.body.appendChild(dialog);
            dialog.showModal();
            cancel.focus();
        });
    }

    function questionOf(el) {
        return {
            message: el.getAttribute('data-confirm'),
            title: el.getAttribute('data-confirm-title') || '',
            button: el.getAttribute('data-confirm-button') || '',
            tone: el.getAttribute('data-confirm-tone') || 'danger'
        };
    }

    function hasQuestion(el) {
        return !!el && el.hasAttribute('data-confirm') && el.getAttribute('data-confirm') !== '';
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (form.hasAttribute('data-kk-confirmed')) {
            form.removeAttribute('data-kk-confirmed');
            return;
        }
        // The button that submitted carries the question; only when nothing
        // names one (Enter in a field, a script's requestSubmit()) does the
        // form's own question, or its one asking button's, count.
        var submitter = event.submitter || null;
        var source = null;
        if (submitter && submitter !== form) {
            source = hasQuestion(submitter) ? submitter : (hasQuestion(form) ? form : null);
        } else {
            source = hasQuestion(form) ? form : form.querySelector('[data-confirm]:not([data-confirm=""])');
        }
        if (!source) {
            return;
        }
        // Stop the submit before the page's own handlers see it.
        event.preventDefault();
        event.stopImmediatePropagation();
        ask(questionOf(source)).then(function (isConfirmed) {
            if (!isConfirmed) {
                return;
            }
            form.setAttribute('data-kk-confirmed', '');
            if (typeof form.requestSubmit === 'function') {
                if (submitter && submitter !== form) {
                    form.requestSubmit(submitter);
                } else {
                    form.requestSubmit();
                }
            } else {
                form.removeAttribute('data-kk-confirmed');
                form.submit();
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        var link = event.target instanceof Element ? event.target.closest('a[data-confirm]') : null;
        if (!link || !hasQuestion(link) || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        event.preventDefault();
        ask(questionOf(link)).then(function (isConfirmed) {
            if (isConfirmed) {
                window.location.assign(link.href);
            }
        });
    }, true);

    window.KkConfirm = { ask: ask };
}());
