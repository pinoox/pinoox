import {computed, onUnmounted, ref, unref} from 'vue';

const MIN_WIDTH = 420;
const MIN_HEIGHT = 280;
const MARGIN = 12;

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function readRect(rectSource) {
    const value = unref(rectSource);

    if (!value) {
        return null;
    }

    return {...value};
}

export function useAppViewFloating(shellRef, rectSource, {onRectCommit, onInteract} = {}) {
    const interacting = ref(false);
    const isDragging = ref(false);
    const isResizing = ref(false);
    let dragState = null;
    let resizeState = null;
    let frameId = null;
    let pendingMove = null;

    const shellStyle = computed(() => {
        const value = unref(rectSource);

        if (!value || interacting.value) {
            return {};
        }

        return {
            top: `${value.y}px`,
            left: `${value.x}px`,
            width: `${value.w}px`,
            height: `${value.h}px`,
            right: 'auto',
            bottom: 'auto',
        };
    });

    function getShell() {
        return shellRef?.value ?? null;
    }

    function applyShellRect(rect, {translateX = 0, translateY = 0} = {}) {
        const shell = getShell();

        if (!shell || !rect) {
            return;
        }

        shell.style.top = `${rect.y}px`;
        shell.style.left = `${rect.x}px`;
        shell.style.width = `${rect.w}px`;
        shell.style.height = `${rect.h}px`;
        shell.style.right = 'auto';
        shell.style.bottom = 'auto';

        if (translateX || translateY) {
            shell.style.transform = `translate3d(${translateX}px, ${translateY}px, 0)`;
        } else {
            shell.style.transform = '';
        }
    }

    function clearShellInlineStyles() {
        const shell = getShell();

        if (!shell) {
            return;
        }

        shell.style.transform = '';
        shell.style.top = '';
        shell.style.left = '';
        shell.style.width = '';
        shell.style.height = '';
        shell.style.right = '';
        shell.style.bottom = '';
    }

    function setInteracting(active) {
        interacting.value = active;
        onInteract?.(active);
    }

    function scheduleFrame(task) {
        pendingMove = task;

        if (frameId !== null) {
            return;
        }

        frameId = requestAnimationFrame(() => {
            frameId = null;
            const run = pendingMove;
            pendingMove = null;
            run?.();
        });
    }

    function cancelScheduledFrame() {
        if (frameId !== null) {
            cancelAnimationFrame(frameId);
            frameId = null;
        }

        pendingMove = null;
    }

    function canStartDrag(event) {
        if (event.button !== 0) {
            return false;
        }

        const target = event.target;

        if (!(target instanceof Element)) {
            return false;
        }

        return !target.closest('button, input, a, .appViewChrome, .appView__resizeHandle');
    }

    function onDragStart(event) {
        const baseRect = readRect(rectSource);

        if (!canStartDrag(event) || !baseRect) {
            return;
        }

        isDragging.value = true;
        setInteracting(true);
        applyShellRect(baseRect);

        dragState = {
            startX: event.clientX,
            startY: event.clientY,
            baseRect,
        };

        document.addEventListener('mousemove', onDragMove);
        document.addEventListener('mouseup', onDragEnd);
    }

    function onDragMove(event) {
        if (!dragState) {
            return;
        }

        scheduleFrame(() => {
            if (!dragState) {
                return;
            }

            const dx = event.clientX - dragState.startX;
            const dy = event.clientY - dragState.startY;

            applyShellRect(dragState.baseRect, {translateX: dx, translateY: dy});
        });
    }

    function onDragEnd(event) {
        if (!dragState) {
            return;
        }

        const dx = event.clientX - dragState.startX;
        const dy = event.clientY - dragState.startY;
        const maxX = window.innerWidth - dragState.baseRect.w - MARGIN;
        const maxY = window.innerHeight - dragState.baseRect.h - MARGIN;
        const nextRect = {
            ...dragState.baseRect,
            x: clamp(dragState.baseRect.x + dx, MARGIN, Math.max(MARGIN, maxX)),
            y: clamp(dragState.baseRect.y + dy, MARGIN, Math.max(MARGIN, maxY)),
        };

        dragState = null;
        cancelScheduledFrame();
        isDragging.value = false;
        applyShellRect(nextRect);
        onRectCommit?.(nextRect);
        setInteracting(false);
        document.removeEventListener('mousemove', onDragMove);
        document.removeEventListener('mouseup', onDragEnd);
    }

    function onResizeStart(event) {
        event.preventDefault();
        event.stopPropagation();

        const baseRect = readRect(rectSource);

        if (!baseRect) {
            return;
        }

        isResizing.value = true;
        setInteracting(true);
        applyShellRect(baseRect);

        resizeState = {
            startX: event.clientX,
            startY: event.clientY,
            baseRect,
            originW: baseRect.w,
            originH: baseRect.h,
        };

        document.addEventListener('mousemove', onResizeMove);
        document.addEventListener('mouseup', onResizeEnd);
    }

    function onResizeMove(event) {
        if (!resizeState) {
            return;
        }

        scheduleFrame(() => {
            if (!resizeState) {
                return;
            }

            const dx = event.clientX - resizeState.startX;
            const dy = event.clientY - resizeState.startY;
            const maxW = window.innerWidth - resizeState.baseRect.x - MARGIN;
            const maxH = window.innerHeight - resizeState.baseRect.y - MARGIN;

            applyShellRect({
                ...resizeState.baseRect,
                w: clamp(resizeState.originW + dx, MIN_WIDTH, maxW),
                h: clamp(resizeState.originH + dy, MIN_HEIGHT, maxH),
            });
        });
    }

    function onResizeEnd() {
        if (!resizeState) {
            return;
        }

        const shell = getShell();
        let nextRect = resizeState.baseRect;

        if (shell) {
            nextRect = {
                ...resizeState.baseRect,
                w: parseFloat(shell.style.width) || resizeState.baseRect.w,
                h: parseFloat(shell.style.height) || resizeState.baseRect.h,
            };
        }

        resizeState = null;
        cancelScheduledFrame();
        isResizing.value = false;
        applyShellRect(nextRect);
        onRectCommit?.(nextRect);
        setInteracting(false);
        document.removeEventListener('mousemove', onResizeMove);
        document.removeEventListener('mouseup', onResizeEnd);
    }

    function stopInteraction() {
        cancelScheduledFrame();
        document.removeEventListener('mousemove', onDragMove);
        document.removeEventListener('mouseup', onDragEnd);
        document.removeEventListener('mousemove', onResizeMove);
        document.removeEventListener('mouseup', onResizeEnd);
        dragState = null;
        resizeState = null;
        isDragging.value = false;
        isResizing.value = false;

        if (interacting.value) {
            setInteracting(false);
            clearShellInlineStyles();
        }
    }

    onUnmounted(stopInteraction);

    return {
        shellStyle,
        onDragStart,
        onResizeStart,
        interacting,
        isDragging,
        isResizing,
    };
}
