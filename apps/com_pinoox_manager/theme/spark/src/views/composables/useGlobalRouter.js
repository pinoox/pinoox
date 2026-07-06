import {getCurrentInstance} from 'vue';

export function useGlobalRouter() {
    const instance = getCurrentInstance();

    return instance?.appContext.config.globalProperties.$router ?? null;
}
