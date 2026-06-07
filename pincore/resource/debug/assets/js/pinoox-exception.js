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

    function toggleTraces(mode) {
        document.querySelectorAll('.px-trace-wrap .sf-toggle').forEach(function (toggle) {
            setToggleState(toggle, mode === 'expand');
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

    var vendorToggle = document.querySelector('[data-action="filter-vendor"]');
    if (vendorToggle) {
        vendorToggle.addEventListener('click', function () {
            var hide = !body.classList.contains('px-hide-vendor');
            body.classList.toggle('px-hide-vendor', hide);
            toast(hide ? 'Vendor frames hidden' : 'Vendor frames shown');
        });
    }

    var pincoreToggle = document.querySelector('[data-action="filter-pincore"]');
    if (pincoreToggle) {
        pincoreToggle.addEventListener('click', function () {
            var hide = !body.classList.contains('px-hide-pincore');
            body.classList.toggle('px-hide-pincore', hide);
            pincoreToggle.textContent = hide ? 'Show pincore frames' : 'Hide pincore frames';
            toast(hide ? 'Pincore frames hidden' : 'Pincore frames shown');
        });
    }

    var jumpOrigin = document.querySelector('[data-action="jump-origin"]');
    if (jumpOrigin) {
        jumpOrigin.addEventListener('click', function () {
            var origin = document.getElementById('px-trace-origin');
            if (!origin) {
                toast('Origin frame not found');
                return;
            }

            body.classList.add('px-hide-pincore');
            if (pincoreToggle) {
                pincoreToggle.textContent = 'Show pincore frames';
            }

            var toggle = origin.querySelector('.trace-line-header.sf-toggle');
            if (toggle) {
                setToggleState(toggle, true);
            }

            origin.scrollIntoView({ behavior: 'smooth', block: 'center' });
            origin.classList.add('px-trace-flash');
            setTimeout(function () {
                origin.classList.remove('px-trace-flash');
            }, 1400);
        });
    }
})();
