import {computed} from 'vue'
import {storeToRefs} from 'pinia'
import {useInstallStore} from '@/stores/install.js'
import {getLangPack, isUsableLangPack} from '@/lang/index.js'

export function useInstallerLang() {
    const store = useInstallStore()
    const {LANG, OPTIONS} = storeToRefs(store)

    const pack = computed(() => {
        const locale = OPTIONS.value?.lang ?? 'en'

        if (isUsableLangPack(LANG.value)) {
            return LANG.value
        }

        return getLangPack(locale)
    })

    const install = computed(() => pack.value.install)
    const user = computed(() => pack.value.user)
    const language = computed(() => pack.value.language)

    return {
        LANG: pack,
        install,
        user,
        language,
        OPTIONS,
        store,
    }
}
