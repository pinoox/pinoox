import {defineStore} from 'pinia';
import {authAPI} from "@api/auth.js";

const tokenKey = 'pinoox_user'

export const useAuthStore = defineStore({
    id: 'auth',
    state: () => ({
        auth: false,
        user: {},
    }),
    getters: {
        isAuth() {
            let loginKey = localStorage.getItem(tokenKey);
            let auth = this.auth;
            return !!loginKey && auth;
        },
        getUser() {
            return this.user;
        }
    },
    actions: {
        async logout() {
            return new Promise((resolve, reject) => {
                authAPI.logout().then(() => {
                    localStorage.removeItem(tokenKey);
                    this.setAuth(false);
                    this.setUser({});
                    resolve();
                }).catch(error => {
                    reject(error);
                });
            });
        },
        login(login_key) {
            localStorage.setItem(tokenKey, login_key);
            this.setAuth(true);
        },
        async canUserAccess(refresh = false) {
            if (!refresh) {
                if (!!localStorage[tokenKey] && this.auth) return true;
                if (!localStorage[tokenKey]) return false;
            }

            try {
                const response = await authAPI.get();
                const userData = response.data;
                this.setUser(userData);
                this.setAuth(true);
                return true;
            } catch (error) {
                return false;
            }
        },
        setAuth(newValue) {
            this.auth = newValue;
        },
        setUser(newValue) {
            this.user = newValue;
        },
    },
});
