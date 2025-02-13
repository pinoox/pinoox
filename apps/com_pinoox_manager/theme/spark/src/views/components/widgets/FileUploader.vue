<template>
  <div class="file-uploader">
    <p class="file-uploader__description">
      فایل با فرمت pin را بارگذاری کنید
    </p>

    <div class="file-uploader__drop-area" @dragover.prevent @drop="handleDrop">
      <p>می‌توانید فایل را بکشید و رها کنید</p>
      <label class="file-uploader__file-label">
        <input type="file" multiple @change="handleFileSelect" accept=".pin" hidden />
        <Button variant="primary" label="انتخاب فایل"></Button>
      </label>
    </div>

  </div>
</template>

<script setup>
import { ref } from "vue";

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
};

const handleDrop = (event) => {
};


</script>