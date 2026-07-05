import {computed, onMounted, onUnmounted, ref} from 'vue';
import {toast} from '@global';
import {usePackageInstaller} from '@/views/composables/usePackageInstaller.js';

function isFileDragEvent(event) {
    return Array.from(event?.dataTransfer?.types ?? []).includes('Files');
}

function extractPinxFile(dataTransfer) {
    const files = dataTransfer?.files;

    if (!files?.length) {
        return null;
    }

    for (const file of files) {
        if (String(file.name).toLowerCase().endsWith('.pinx')) {
            return file;
        }
    }

    return null;
}

export function usePackageInstallerDrop(enabledRef) {
    const {openInstaller} = usePackageInstaller();
    const isDragging = ref(false);
    let dragDepth = 0;

    const canAcceptDrop = computed(() => Boolean(enabledRef?.value ?? enabledRef));

    function resetDragState() {
        dragDepth = 0;
        isDragging.value = false;
    }

    function onDragEnter(event) {
        if (!canAcceptDrop.value || !isFileDragEvent(event)) {
            return;
        }

        dragDepth += 1;
        isDragging.value = true;
    }

    function onDragLeave(event) {
        if (!isFileDragEvent(event)) {
            return;
        }

        dragDepth = Math.max(0, dragDepth - 1);

        if (dragDepth === 0) {
            isDragging.value = false;
        }
    }

    function onDragOver(event) {
        if (!canAcceptDrop.value || !isFileDragEvent(event)) {
            return;
        }

        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'copy';
        }
    }

    function onDrop(event) {
        if (!isFileDragEvent(event)) {
            resetDragState();
            return;
        }

        event.preventDefault();
        resetDragState();

        if (!canAcceptDrop.value) {
            return;
        }

        const file = extractPinxFile(event.dataTransfer);

        if (!file) {
            toast({
                title: 'لطفاً فقط فایل با فرمت .pinx را رها کنید.',
                type: 'warn',
            });
            return;
        }

        openInstaller(file);
    }

    onMounted(() => {
        window.addEventListener('dragenter', onDragEnter, true);
        window.addEventListener('dragleave', onDragLeave, true);
        window.addEventListener('dragover', onDragOver, true);
        window.addEventListener('drop', onDrop, true);
        window.addEventListener('blur', resetDragState);
    });

    onUnmounted(() => {
        window.removeEventListener('dragenter', onDragEnter, true);
        window.removeEventListener('dragleave', onDragLeave, true);
        window.removeEventListener('dragover', onDragOver, true);
        window.removeEventListener('drop', onDrop, true);
        window.removeEventListener('blur', resetDragState);
    });

    return {
        isDragging,
    };
}
