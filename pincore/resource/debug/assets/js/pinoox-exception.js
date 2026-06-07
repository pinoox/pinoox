(function () {
    var body = document.body;
    var payloadNode = document.getElementById('px-exception-payload');
    var payload = {};

    try {
        payload = payloadNode ? JSON.parse(payloadNode.textContent || '{}') : {};
    } catch (e) {
        payload = {};
    }

    function toast(message) {
        var node = document.querySelector('.px-toast');
        if (!node) {
            node = document.createElement('div');
            node.className = 'px-toast';
            body.appendChild(node);
        }
        node.textContent = message;
        node.classList.add('is-visible');
        clearTimeout(node._timer);
        node._timer = setTimeout(function () {
            node.classList.remove('is-visible');
        }, 1800);
    }

    function copyText(text, label) {
        if (!text) {
            toast('Nothing to copy');
            return;
        }

        navigator.clipboard.writeText(text).then(function () {
            toast(label || 'Copied');
        }).catch(function () {
            toast('Copy failed');
        });
    }

    function stackTraceText() {
        var traces = document.querySelectorAll('.trace-text');
        var parts = [];
        traces.forEach(function (node) {
            parts.push(node.innerText.trim());
        });
        return parts.join('\n\n');
    }

    function curlCommand() {
        var method = (payload.method || 'GET').toUpperCase();
        var url = payload.url || '';
        var parts = ['curl -X ' + method];

        if (url) {
            parts.push("'" + url.replace(/'/g, "'\\''") + "'");
        }

        var headers = payload.headers || {};
        Object.keys(headers).forEach(function (name) {
            if (/^cookie$/i.test(name)) {
                return;
            }
            parts.push("-H '" + name + ': ' + String(headers[name]).replace(/'/g, "'\\''") + "'");
        });

        if (payload.body && method !== 'GET' && method !== 'HEAD') {
            parts.push("--data '" + String(payload.body).replace(/'/g, "'\\''") + "'");
        }

        return parts.join(' \\\n  ');
    }

    function setToggleState(toggle, expand) {
        if (!toggle) {
            return;
        }

        var selector = toggle.getAttribute('data-toggle-selector');
        var element = selector ? document.querySelector(selector) : null;
        if (!element) {
            return;
        }

        toggle.classList.toggle('sf-toggle-on', expand);
        toggle.classList.toggle('sf-toggle-off', !expand);
        element.classList.toggle('sf-toggle-visible', expand);
        element.classList.toggle('sf-toggle-hidden', !expand);
    }

    function isPincoreHidden() {
        return body.classList.contains('px-hide-pincore');
    }

    function isVendorHidden() {
        return body.classList.contains('px-hide-vendor');
    }

    function syncFilterButtons() {
        document.querySelectorAll('[data-action="filter-pincore"]').forEach(function (button) {
            var hidden = isPincoreHidden();
            var label = hidden ? 'Show pincore frames' : 'Hide pincore frames';
            if (button.classList.contains('px-tool-card')) {
                var title = button.querySelector('strong');
                if (title) {
                    title.textContent = label.charAt(0).toUpperCase() + label.slice(1);
                }
            } else {
                button.textContent = label;
            }
            button.setAttribute('aria-pressed', hidden ? 'false' : 'true');
        });

        document.querySelectorAll('[data-action="filter-vendor"]').forEach(function (button) {
            var hidden = isVendorHidden();
            var label = hidden ? 'Show vendor frames' : 'Hide vendor frames';
            if (button.classList.contains('px-tool-card')) {
                var title = button.querySelector('strong');
                if (title) {
                    title.textContent = label.charAt(0).toUpperCase() + label.slice(1);
                }
            } else {
                button.textContent = label;
            }
            button.setAttribute('aria-pressed', hidden ? 'false' : 'true');
        });
    }

    function setPincoreHidden(hidden, silent) {
        body.classList.toggle('px-hide-pincore', hidden);
        syncFilterButtons();
        if (!silent) {
            toast(hidden ? 'Pincore frames hidden' : 'Pincore frames shown');
        }
    }

    function setVendorHidden(hidden, silent) {
        body.classList.toggle('px-hide-vendor', hidden);
        syncFilterButtons();
        if (!silent) {
            toast(hidden ? 'Vendor frames hidden' : 'Vendor frames shown');
        }
    }

    function toggleTraces(mode) {
        var expand = mode === 'expand';
        document.querySelectorAll('.px-trace-wrap .sf-toggle').forEach(function (toggle) {
            if (!toggle.getAttribute('data-toggle-selector')) {
                return;
            }
            setToggleState(toggle, expand);
        });
    }

    function copyByKind(kind) {
        if (kind === 'message') {
            var message = payload.message || '';
            if (payload.class) {
                message = payload.class + ': ' + message;
            }
            if (payload.file) {
                message += '\n at ' + payload.file + ':' + (payload.line || '');
            }
            copyText(message, 'Message copied');
            return;
        }

        if (kind === 'url') {
            copyText(payload.url || '', 'URL copied');
            return;
        }

        if (kind === 'curl') {
            copyText(curlCommand(), 'cURL copied');
            return;
        }

        copyText(stackTraceText() || body.innerText, 'Trace copied');
    }

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            copyByKind(button.getAttribute('data-copy'));
        });
    });

    var themeToggle = document.getElementById('px-theme-toggle');
    if (themeToggle) {
        function isDarkMode() {
            return !body.classList.contains('theme-light');
        }

        function syncThemeLabel() {
            var dark = isDarkMode();
            themeToggle.textContent = dark ? 'Light mode' : 'Dark mode';
            themeToggle.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
        }

        syncThemeLabel();
        themeToggle.addEventListener('click', function () {
            var next = isDarkMode() ? 'theme-light' : 'theme-dark';
            body.classList.remove('theme-dark', 'theme-light');
            body.classList.add(next);
            localStorage.setItem('pinoox/debug-theme', next.replace('theme-', ''));
            syncThemeLabel();
        });
    }

    document.querySelectorAll('[data-px-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var name = tab.getAttribute('data-px-tab');
            document.querySelectorAll('[data-px-tab]').forEach(function (node) {
                var active = node === tab;
                node.classList.toggle('is-active', active);
                node.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-px-panel]').forEach(function (panel) {
                var active = panel.getAttribute('data-px-panel') === name;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });

    document.querySelectorAll('[data-toggle-traces]').forEach(function (button) {
        button.addEventListener('click', function () {
            toggleTraces(button.getAttribute('data-toggle-traces'));
        });
    });

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-action]');
        if (!target) {
            return;
        }

        var action = target.getAttribute('data-action');

        if (action === 'jump-origin') {
            event.preventDefault();
            var origin = document.getElementById('px-trace-origin');
            if (!origin) {
                toast('Origin frame not found');
                return;
            }

            var exceptionTab = document.querySelector('[data-px-tab="exception"]');
            if (exceptionTab) {
                exceptionTab.click();
            }

            setPincoreHidden(true, true);

            var toggle = origin.querySelector('.trace-line-header.sf-toggle');
            if (toggle) {
                setToggleState(toggle, true);
            }

            var traceBox = origin.closest('.trace-as-html');
            var traceBoxToggle = traceBox ? traceBox.querySelector('.trace-head > .sf-toggle') : null;
            if (traceBoxToggle) {
                setToggleState(traceBoxToggle, true);
            }

            origin.scrollIntoView({ behavior: 'smooth', block: 'center' });
            origin.classList.add('px-trace-flash');
            setTimeout(function () {
                origin.classList.remove('px-trace-flash');
            }, 1400);
        }
    });

    document.querySelectorAll('[data-action="filter-pincore"], [data-action="filter-vendor"]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            var action = button.getAttribute('data-action');
            if (action === 'filter-pincore') {
                setPincoreHidden(!isPincoreHidden());
            } else if (action === 'filter-vendor') {
                setVendorHidden(!isVendorHidden());
            }
        });
    });

    syncFilterButtons();

    document.querySelectorAll('.px-disclosure-response').forEach(function (node) {
        if (window.matchMedia('(max-width: 768px)').matches) {
            node.removeAttribute('open');
        }
    });
})();
