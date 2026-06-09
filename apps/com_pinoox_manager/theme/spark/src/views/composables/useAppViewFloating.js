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
    const overrideRect = ref(null);
    let dragState = null;
    let resizeState = null;
    let frameId = null;
    let pendingMove = null;

    const shellStyle = computed(() => {
        const value = overrideRect.value ?? readRect(rectSource);

        if (!value) {
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
        overrideRect.value = {...baseRect};

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
            const maxX = window.innerWidth - dragState.baseRect.w - MARGIN;
            const maxY = window.innerHeight - dragState.baseRect.h - MARGIN;

            overrideRect.value = {
                ...dragState.baseRect,
                x: clamp(dragState.baseRect.x + dx, MARGIN, Math.max(MARGIN, maxX)),
                y: clamp(dragState.baseRect.y + dy, MARGIN, Math.max(MARGIN, maxY)),
            };
        });
    }

    function onDragEnd() {
        if (!dragState) {
            return;
        }

        const nextRect = overrideRect.value ?? dragState.baseRect;

        dragState = null;
        cancelScheduledFrame();
        isDragging.value = false;
        overrideRect.value = null;
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
        overrideRect.value = {...baseRect};

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

            overrideRect.value = {
                ...resizeState.baseRect,
                w: clamp(resizeState.originW + dx, MIN_WIDTH, maxW),
                h: clamp(resizeState.originH + dy, MIN_HEIGHT, maxH),
            };
        });
    }

    function onResizeEnd() {
        if (!resizeState) {
            return;
        }

        const nextRect = overrideRect.value ?? resizeState.baseRect;

        resizeState = null;
        cancelScheduledFrame();
        isResizing.value = false;
        overrideRect.value = null;
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
        overrideRect.value = null;

        if (interacting.value) {
            setInteracting(false);
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
