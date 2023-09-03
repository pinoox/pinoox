<template>
  <CodeBlock
    class="codeBlock"
    :code="code"
    label="panda"
    dir="ltr"
    theme="base16-onedark"
    highlightjs
    lang="php"
  />
</template>

<script setup>
import { ref } from "vue";
import hljs from "highlight.js/lib/core";
import langPhp from "highlight.js/lib/languages/php";

hljs.registerLanguage("php", langPhp);

const code = ref(`<?php

return array(
    'package' => 'com_pinoox_panda',
    'name' => 'Panda',
    'description' => 'shopping app',
    'icon' => 'icon.png',
    'version-code' => 1,
    'version-name' => '1.0',
    'developer' => 'pinoox',
    'enable' => true,
    'theme' => 'default',
    'theme-panel' => 'papyrus',
    'lang' => 'fa',
);`);
</script>