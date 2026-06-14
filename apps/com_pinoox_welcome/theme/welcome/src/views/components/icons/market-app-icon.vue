<template>
    <svg
        class="market-app-icon"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
    >
        <!-- shop -->
        <template v-if="name === 'shop'">
            <path d="M4 7h16l-1.2 11H5.2L4 7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M9 7V6a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </template>

        <!-- blog -->
        <template v-else-if="name === 'blog'">
            <path d="M6 5h12v14H6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M9 9h6M9 12h6M9 15h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </template>

        <!-- panel -->
        <template v-else-if="name === 'panel'">
            <rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
            <rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
            <rect x="4" y="13" width="16" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/>
        </template>

        <!-- chat -->
        <template v-else-if="name === 'chat'">
            <path d="M5 6h14v9H9l-4 3V6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M9 10h6M9 13h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
        </template>

        <!-- forms -->
        <template v-else-if="name === 'forms'">
            <path d="M7 5h10v14H7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
            <path d="M10 9h7M10 12h7M10 15h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            <circle cx="9" cy="9" r="0.8" fill="currentColor"/>
            <circle cx="9" cy="12" r="0.8" fill="currentColor"/>
            <circle cx="9" cy="15" r="0.8" fill="currentColor"/>
        </template>
    </svg>
</template>

<script setup>
defineProps({
    name: {
        type: String,
        required: true,
    },
});
</script>
