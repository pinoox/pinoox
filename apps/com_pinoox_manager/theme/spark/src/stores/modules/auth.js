import { defineStore } from 'pinia';
import { authAPI } from "@api/auth.js";
import {computed, ref} from "vue";

const tokenKey = 'manager_pinoox';

export const useAuthStore = defineStore('auth', () => {
    const auth = ref(false);
    const user = ref({});

    const isAuth = computed(() => {
        let loginKey = localStorage.getItem(tokenKey);
        return !!loginKey && auth.value;
    });

    const getUser = computed(() => user.value);

    const logout = async () => {
        try {
            await authAPI.logout();
            localStorage.removeItem(tokenKey);
            auth.value = false;
            user.value = {};
        } catch (error) {
            console.error("Logout failed:", error);
        }
    };

    const login = (login_key) => {
        localStorage.setItem(tokenKey, login_key);
        auth.value = true;
    };

    const canUserAccess = async (refresh = false) => {
        if (!refresh) {
            if (!!localStorage[tokenKey] && auth.value) return true;
            if (!localStorage[tokenKey]) return false;
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

    return {
        auth,
        user,
        isAuth,
        getUser,
        login,
        logout,
        canUserAccess
    };
});