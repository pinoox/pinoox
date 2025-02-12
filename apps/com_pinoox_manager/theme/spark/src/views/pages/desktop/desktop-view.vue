<template>

  <StorageWidget/>

  <Dockbar :apps="apps"/>

</template>

<script setup>
import {reactive} from "vue";

const apps = reactive([]);
</script>