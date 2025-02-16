<template>
    <div class="file-uploader">
        <p class="file-uploader__description">
            فایل با فرمت pin را بارگذاری کنید
        </p>

        <div @click="triggerFileInput" class="file-uploader__drop-area" @dragover.prevent @drop="handleDrop">
            <p v-if="file">{{ file.name }}</p>
            <p v-else>می‌توانید فایل را بکشید و رها کنید</p>
            <label class="file-uploader__file-label">
                <input type="file" @change="handleFileSelect" accept=".pin" hidden />
                <Button variant="primary" label="انتخاب فایل"></Button>
            </label>
        </div>

    </div>
</template>

<script setup>
import {ref, watch} from "vue";

const file = ref(null);
const emit = defineEmits(['select']);

const handleFileSelect = (event) => {
    const selectedFile = event.target.files[0];
    // if (!selectedFile || !selectedFile.name.endsWith('.pin')) {
    //     alert("لطفاً فقط فایل با فرمت .pin را انتخاب کنید.");
    //     return;
    // }
    file.value = selectedFile;
};

const handleDrop = (event) => {
    event.preventDefault();
    const droppedFile = event.dataTransfer.files[0];
    if (!droppedFile || !droppedFile.name.endsWith('.pin')) {
        alert("لطفاً فقط فایل با فرمت .pin را رها کنید.");
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
