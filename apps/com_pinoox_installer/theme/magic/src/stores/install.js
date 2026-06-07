import {defineStore} from 'pinia'
import {applyDirection} from '@/utils/direction.js'
import {syncBootstrapQueryRoute} from '@/utils/resolveInstallerApi.js'

const bootstrap = typeof PINOOX !== 'undefined' ? PINOOX : null

syncBootstrapQueryRoute(bootstrap === null)

export const useInstallStore = defineStore('install', {
    state: () => ({
        LANG: bootstrap?.LANG ?? {},
        OPTIONS: bootstrap?.OPTIONS ?? {lang: 'en', direction: 'ltr', version: ''},
        bootstrapError: bootstrap === null,
        preflightPing: null,
        preflightLoading: true,
        isLoading: false,
        db: {
            host: 'localhost',
            database: 'pinoox',
            username: 'root',
            password: '',
            prefix: 'pinx_',
        },
        user: {
            fname: '',
            lname: '',
            username: '',
            email: '',
            password: '',
        },
    }),
    actions: {
        setLang(translations, selectedLang, direction) {
            if (translations?.install) {
                this.LANG = translations
            }

            const resolvedDirection = direction ?? this.OPTIONS.direction

            this.OPTIONS = {
                ...this.OPTIONS,
                lang: selectedLang,
                direction: resolvedDirection,
            }

            applyDirection(resolvedDirection, selectedLang)
        },

        syncDirection() {
            applyDirection(this.OPTIONS.direction, this.OPTIONS.lang)
        },

        setPreflightPing(ping) {
            this.preflightPing = ping
            this.bootstrapError = !ping?.ok || typeof PINOOX === 'undefined'
            syncBootstrapQueryRoute(this.bootstrapError)
        },

        refreshBootstrapError() {
            this.bootstrapError = !this.preflightPing?.ok || typeof PINOOX === 'undefined'
            syncBootstrapQueryRoute(this.bootstrapError)
        },
    },
})
