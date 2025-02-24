import { defineStore } from 'pinia';
import { authAPI } from "@api/auth.js";
import { computed, ref, watch } from "vue";

const tokenKey = 'manager_pinoox';

export const useAuthStore = defineStore('auth', () => {
    const auth = ref(false);
    const user = ref({});
    const token = ref(localStorage.getItem(tokenKey) || null);

    const isAuth = computed(() => {
        return !!token.value && auth.value;
    });

    const getUser = computed(() => user.value);

    const logout = async () => {
        try {
            await authAPI.logout();
            token.value = null;
            auth.value = false;
            user.value = {};
        } catch (error) {
            console.error("Logout failed:", error);
        }
    };

    const login = (login_key) => {
        token.value = login_key;
        auth.value = true;
    };

    const canUserAccess = async (refresh = false) => {
        if (!refresh) {
            if (!!token.value && auth.value) return true;
            if (!token.value) return false;
        }

        try {
            const response = await authAPI.get();
            user.value = response.data;
            auth.value = true;
            return true;
        } catch (error) {
            return false;
        }
    };

    watch(token, (newToken) => {
        if (newToken) {
            localStorage.setItem(tokenKey, newToken);
        } else {
            localStorage.removeItem(tokenKey);
        }
    });

    return {
        auth,
        user,
        isAuth,
        getUser,
        login,
        logout,
        canUserAccess,
        token,
    };
});
