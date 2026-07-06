import {defineStore} from 'pinia'
import {getBoot, hasBoot} from '@/boot.js'
import {getDirection, getLangPack, persistLocale, resolveLangPayload, resolveLangState} from '@/lang/index.js'
import {applyDirection} from '@/utils/direction.js'
import {syncBootstrapQueryRoute} from '@/utils/resolveInstallerApi.js'

const boot = getBoot()
const langState = resolveLangState(boot)

syncBootstrapQueryRoute(!hasBoot())

export const useInstallStore = defineStore('install', {
    state: () => ({
        LANG: langState.pack,
        OPTIONS: {
            lang: langState.locale,
            direction: langState.direction,
            version: boot.version ?? '',
        },
        bootstrapError: !hasBoot(),
        preflightPing: null,
        preflightLoading: true,
        isLoading: false,
        availableDbConnections: [],
        db: {
            connection: 'mysql',
            host: 'localhost',
            database: 'pinoox',
            username: 'root',
            password: '',
            prefix: 'pinx_',
            port: '3306',
            timezone: '+03:30',
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
        setLang(translations, selectedLang) {
            this.LANG = resolveLangPayload(translations, selectedLang)

            const resolvedDirection = getDirection(selectedLang)

            this.OPTIONS = {
                ...this.OPTIONS,
                lang: selectedLang,
                direction: resolvedDirection,
            }

            persistLocale(selectedLang)
            applyDirection(resolvedDirection, selectedLang)
        },

        ensureLang() {
            const state = resolveLangState(getBoot())

            this.LANG = state.pack
            this.OPTIONS = {
                ...this.OPTIONS,
                lang: state.locale,
                direction: state.direction,
            }
            applyDirection(state.direction, state.locale)
        },

        syncDirection() {
            applyDirection(this.OPTIONS.direction, this.OPTIONS.lang)
        },

        setPreflightPing(ping) {
            this.preflightPing = ping
            this.bootstrapError = !ping?.ok || !hasBoot()
            syncBootstrapQueryRoute(this.bootstrapError)
        },

        refreshBootstrapError() {
            this.bootstrapError = !this.preflightPing?.ok || !hasBoot()
            syncBootstrapQueryRoute(this.bootstrapError)
        },

        setAvailableDbConnections(connections) {
            this.availableDbConnections = Array.isArray(connections) ? connections : []
        },
    },
})
