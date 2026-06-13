import {defineStore} from 'pinia'
import {getBoot, hasBoot} from '@/boot.js'
import {applyDirection} from '@/utils/direction.js'
import {syncBootstrapQueryRoute} from '@/utils/resolveInstallerApi.js'

const boot = getBoot()

syncBootstrapQueryRoute(!hasBoot())

export const useInstallStore = defineStore('install', {
    state: () => ({
        LANG: boot.lang ?? {},
        OPTIONS: {
            lang: boot.locale ?? 'en',
            direction: boot.direction ?? 'ltr',
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
