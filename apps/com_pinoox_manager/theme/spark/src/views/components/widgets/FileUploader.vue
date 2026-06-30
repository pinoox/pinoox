<template>
    <div class="file-uploader" :class="{ 'is-compact': compact }">
        <p v-if="!compact" class="file-uploader__description">
            فایل با فرمت pinx را بارگذاری کنید
        </p>

        <div @click="triggerFileInput" class="file-uploader__drop-area" @dragover.prevent @drop="handleDrop">
            <p v-if="file">{{ file.name }}</p>
            <p v-else-if="compact">فایل pinx را انتخاب کنید</p>
            <p v-else>می‌توانید فایل را بکشید و رها کنید</p>
            <label class="file-uploader__file-label">
                <input type="file" @change="handleFileSelect" accept=".pinx" hidden />
                <Button variant="primary" label="انتخاب فایل"></Button>
            </label>
        </div>

    </div>
</template>

<script setup>
import {ref, watch} from "vue";
import {toast} from "@/utils/global.js";

defineProps({
    compact: {type: Boolean, default: false},
});

const file = ref(null);
const emit = defineEmits(['select']);

const handleFileSelect = (event) => {
    const selectedFile = event.target.files[0];
    file.value = selectedFile;
};

const handleDrop = (event) => {
    event.preventDefault();
    const droppedFile = event.dataTransfer.files[0];
    if (!droppedFile || !droppedFile.name.toLowerCase().endsWith('.pinx')) {
        toast({
            title: 'لطفاً فقط فایل با فرمت .pinx را رها کنید.',
            type: 'warn',
        });
        return;
    }
    file.value = droppedFile;
};

const triggerFileInput = () => {
    document.querySelector('input[type="file"]').click();
};

watch(()=>file.value, (newFile) => {
    emit('select',newFile);
});

const resetFile = () => {
    file.value = null;
};

defineExpose({ resetFile });
</script>
