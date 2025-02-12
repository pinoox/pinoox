import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useSidebarStore = defineStore('sidebar', () => {
    const isCollapsed = ref(false);

    const setCollapsed = (value) => {
        isCollapsed.value = value;
    };

    const toggleSidebar = () => {
        isCollapsed.value = !isCollapsed.value;
    };

    return {
        isCollapsed,
        setCollapsed,
        toggleSidebar,
    };
});