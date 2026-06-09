import {computed, onUnmounted, ref, unref, watch} from 'vue';
import {
    appendManagerToken,
    isAppRootRoute,
    resolveAppRouteFromHref,
    stripManagerTokenFromUrl,
} from '@/views/composables/appViewRoute.js';

const PROGRESS_TICK_MS = 180;
const PROGRESS_CAP = 92;
const FINISH_HIDE_MS = 320;
const HREF_POLL_MS = 120;
const READY_CHECK_MS = 150;
const READY_MAX_WAIT_MS = 45000;

export function useAppViewFrameLoading(packageName = '', isActiveSource = null) {
    const loading = ref(true);
    const progress = ref(0);
    const frameHref = ref('');
    const navigationMethod = ref('GET');

    const historyStack = ref([]);
    const historyIndex = ref(-1);

    let progressTimer = null;
    let finishTimer = null;
    let pollTimer = null;
    let readyCheckTimer = null;
    let navigationStartHref = '';
    let lastHref = '';
    let pendingMethod = 'GET';
    let frameRef = null;
    let frameInteracting = false;
    let navigationBound = false;

    function setFrameInteracting(active) {
        frameInteracting = active;
    }

    function isActive() {
        if (isActiveSource === null) {
            return true;
        }

        return !!unref(isActiveSource);
    }

    function canStartLoading() {
        return isActive() && !frameInteracting;
    }

    const canGoBack = computed(() => {
        if (historyIndex.value <= 0) {
            return false;
        }

        if (packageName) {
            const route = resolveAppRouteFromHref(frameHref.value, packageName);

            if (isAppRootRoute(route)) {
                return false;
            }
        }

        return true;
    });
    const canGoForward = computed(() => historyIndex.value < historyStack.value.length - 1);

    function clearProgressTimer() {
        if (progressTimer !== null) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
    }

    function clearReadyCheck() {
        if (readyCheckTimer !== null) {
            clearInterval(readyCheckTimer);
            readyCheckTimer = null;
        }
    }

    function clearFinishTimer() {
        if (finishTimer !== null) {
            clearTimeout(finishTimer);
            finishTimer = null;
        }
    }

    function clearPollTimer() {
        if (pollTimer !== null) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function startLoading() {
        if (!canStartLoading()) {
            return;
        }

        clearFinishTimer();
        loading.value = true;
        progress.value = Math.max(progress.value, 8);
        clearProgressTimer();

        progressTimer = setInterval(() => {
            if (progress.value >= PROGRESS_CAP) {
                return;
            }

            const step = 4 + Math.random() * 10;

            progress.value = Math.min(PROGRESS_CAP, progress.value + step);
        }, PROGRESS_TICK_MS);
    }

    function finishLoading() {
        clearProgressTimer();
        clearReadyCheck();
        progress.value = 100;

        clearFinishTimer();
        finishTimer = setTimeout(() => {
            loading.value = false;
            progress.value = 0;
            finishTimer = null;
        }, FINISH_HIDE_MS);
    }

    function readFrameReadyState(frame) {
        try {
            return frame?.contentDocument?.readyState ?? '';
        } catch {
            return '';
        }
    }

    function scheduleReadyCheck(frame) {
        clearReadyCheck();

        if (!frame || !loading.value) {
            return;
        }

        const startedAt = Date.now();
        navigationStartHref = readFrameHref(frame);

        readyCheckTimer = setInterval(() => {
            if (!loading.value) {
                clearReadyCheck();
                return;
            }

            const href = readFrameHref(frame);
            const readyState = readFrameReadyState(frame);
            const hrefChanged = href && href !== navigationStartHref;
            const isComplete = readyState === 'complete';

            if (isComplete && (hrefChanged || Date.now() - startedAt > READY_CHECK_MS)) {
                clearReadyCheck();
                finishLoading();
                return;
            }

            if (Date.now() - startedAt >= READY_MAX_WAIT_MS) {
                clearReadyCheck();
                finishLoading();
            }
        }, READY_CHECK_MS);
    }

    function readFrameHref(frame) {
        try {
            return frame?.contentWindow?.location?.href ?? '';
        } catch {
            return '';
        }
    }

    function syncFrameHref(href, method = null) {
        const clean = stripManagerTokenFromUrl(href);

        if (!clean) {
            return;
        }

        frameHref.value = clean;

        if (method) {
            navigationMethod.value = method;
        }

        if (clean === historyStack.value[historyIndex.value]) {
            lastHref = href;
            return;
        }

        if (historyIndex.value > 0 && historyStack.value[historyIndex.value - 1] === clean) {
            historyIndex.value -= 1;
            lastHref = href;
            return;
        }

        if (historyIndex.value < historyStack.value.length - 1 && historyStack.value[historyIndex.value + 1] === clean) {
            historyIndex.value += 1;
            lastHref = href;
            return;
        }

        const nextStack = historyStack.value.slice(0, historyIndex.value + 1);
        nextStack.push(clean);
        historyStack.value = nextStack;
        historyIndex.value = nextStack.length - 1;

        if (method) {
            navigationMethod.value = method;
        } else if (pendingMethod !== 'GET') {
            navigationMethod.value = pendingMethod;
            pendingMethod = 'GET';
        } else {
            navigationMethod.value = 'GET';
        }

        lastHref = href;
    }

    function onHrefChanged(frame, href) {
        if (!href || href === lastHref) {
            return;
        }

        if (!isActive()) {
            lastHref = href;
            syncFrameHref(href);

            return;
        }

        syncFrameHref(href);

        if (!canStartLoading()) {
            lastHref = href;
            return;
        }

        startLoading();
        scheduleReadyCheck(frame);
    }

    function bindFrameNavigation(frame) {
        if (!frame || navigationBound) {
            return;
        }

        try {
            const win = frame.contentWindow;
            const doc = frame.contentDocument;

            if (!win || !doc) {
                return;
            }

            syncFrameHref(win.location.href);

            win.addEventListener('beforeunload', startLoading);

            doc.addEventListener('click', (event) => {
                const anchor = event.target?.closest?.('a');

                if (!anchor?.href) {
                    return;
                }

                if (anchor.target && anchor.target !== '_self') {
                    return;
                }

                if (anchor.hasAttribute('download')) {
                    return;
                }

                if (anchor.origin !== window.location.origin) {
                    return;
                }

                pendingMethod = 'GET';

                if (anchor.href !== win.location.href) {
                    startLoading();
                }
            }, true);

            doc.addEventListener('submit', () => {
                pendingMethod = 'POST';
                startLoading();
            }, true);

            navigationBound = true;
        } catch {
            // Same-origin access may fail for some documents.
        }
    }

    function startHrefPolling(frame) {
        clearPollTimer();
        frameRef = frame;

        if (!frame || !isActive()) {
            return;
        }

        const href = readFrameHref(frame);

        if (href) {
            syncFrameHref(href);
        }

        pollTimer = setInterval(() => {
            if (!isActive()) {
                return;
            }

            const nextHref = readFrameHref(frame);

            if (nextHref) {
                onHrefChanged(frame, nextHref);
            }
        }, HREF_POLL_MS);
    }

    function onFrameLoad(frame) {
        bindFrameNavigation(frame);
        const href = readFrameHref(frame);

        if (href) {
            syncFrameHref(href, pendingMethod !== 'GET' ? pendingMethod : 'GET');
            pendingMethod = 'GET';
        }

        if (!canStartLoading()) {
            return;
        }

        scheduleReadyCheck(frame);
    }

    function navigateFrameToUrl(cleanUrl) {
        if (!frameRef || !cleanUrl) {
            return;
        }

        const target = appendManagerToken(cleanUrl);

        pendingMethod = 'GET';
        lastHref = readFrameHref(frameRef);
        startLoading();

        try {
            frameRef.contentWindow.location.assign(target);
        } catch {
            frameRef.src = target;
        }
    }

    function frameGoBack() {
        if (!canGoBack.value || !frameRef) {
            return;
        }

        const target = historyStack.value[historyIndex.value - 1];

        if (target) {
            navigateFrameToUrl(target);
        }
    }

    function frameGoForward() {
        if (!canGoForward.value || !frameRef) {
            return;
        }

        const target = historyStack.value[historyIndex.value + 1];

        if (target) {
            navigateFrameToUrl(target);
        }
    }

    function resetHistory() {
        historyStack.value = [];
        historyIndex.value = -1;
        lastHref = '';
        frameHref.value = '';
        navigationMethod.value = 'GET';
        pendingMethod = 'GET';
    }

    function destroy() {
        clearProgressTimer();
        clearReadyCheck();
        clearFinishTimer();
        clearPollTimer();
        frameRef = null;
        navigationBound = false;
    }

    watch(
        () => (isActiveSource === null ? true : unref(isActiveSource)),
        (active, wasActive) => {
            if (active) {
                if (frameRef) {
                    startHrefPolling(frameRef);
                }

                return;
            }

            if (wasActive) {
                clearPollTimer();
                clearProgressTimer();
                clearReadyCheck();
                clearFinishTimer();
                loading.value = false;
                progress.value = 0;
            }
        },
    );

    loading.value = isActive();

    onUnmounted(destroy);

    return {
        loading,
        progress,
        frameHref,
        navigationMethod,
        canGoBack,
        canGoForward,
        startLoading,
        finishLoading,
        onFrameLoad,
        startHrefPolling,
        frameGoBack,
        frameGoForward,
        resetHistory,
        destroy,
        setFrameInteracting,
    };
}
