import { defineStore } from 'pinia';
import { authAPI } from "@api/auth.js";
import { userAPI } from "@api/user.js";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";
import { computed, ref, watch } from "vue";

const tokenKey = 'manager_pinoox';

export const useAuthStore = defineStore('auth', () => {
    const auth = ref(false);
    const user = ref({});
    const token = ref(localStorage.getItem(tokenKey) || null);
    const isLock = ref(false);
    const isLoggingOut = ref(false);

    const isAuth = computed(() => auth.value);
    const getUser = computed(() => user.value);

    const setUser = (data) => {
        user.value = data || {};
        isLock.value = !!data?.isLock;
    };

    const logout = async () => {
        isLoggingOut.value = true;

        try {
            await authAPI.logout();
        } catch (error) {
            console.error("Logout failed:", error);
        } finally {
            token.value = null;
            auth.value = false;
            user.value = {};
            isLock.value = false;
        }
    };

    const finishLogout = () => {
        isLoggingOut.value = false;
    };

    const login = (login_key) => {
        token.value = login_key;
        auth.value = true;
    };

    const canUserAccess = async (refresh = false) => {
        if (!refresh && auth.value) {
            return true;
        }

        try {
            const response = token.value
                ? await userAPI.get()
                : await authAPI.get();
            setUser(unwrapResponse(response));
            auth.value = true;
            return true;
        } catch (error) {
            auth.value = false;
            return false;
        }
    };

    watch(token, (newToken) => {
        if (newToken)
            localStorage.setItem(tokenKey, newToken);
        else
            localStorage.removeItem(tokenKey);
    });

    return {
        auth,
        user,
        isAuth,
        isLock,
        isLoggingOut,
        getUser,
        login,
        logout,
        finishLogout,
        setUser,
        canUserAccess,
        token,
    };
});
