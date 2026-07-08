import {getBoot} from '@/boot.js'
import {normalizePhpRequirements} from '@/utils/requirementTokens.js'

import enInstall from './en/install.js'
import enUser from './en/user.js'
import enLanguage from './en/language.js'
import enAgreement from './en/agreement.js'
import enBootstrap from './en/bootstrap.js'

import faInstall from './fa/install.js'
import faUser from './fa/user.js'
import faLanguage from './fa/language.js'
import faAgreement from './fa/agreement.js'
import faBootstrap from './fa/bootstrap.js'

export const SUPPORTED_LOCALES = ['en', 'fa']
export const DEFAULT_LOCALE = 'en'

const packs = {
    en: {
        install: enInstall,
        user: enUser,
        language: enLanguage,
        agreement: enAgreement,
        bootstrap: enBootstrap,
        requirements: {php: normalizePhpRequirements()},
    },
    fa: {
        install: faInstall,
        user: faUser,
        language: faLanguage,
        agreement: faAgreement,
        bootstrap: faBootstrap,
        requirements: {php: normalizePhpRequirements()},
    },
}

export function hasLocale(locale) {
    return SUPPORTED_LOCALES.includes(locale)
}

export function getDirection(locale) {
    return locale === 'fa' ? 'rtl' : 'ltr'
}

export function getLangPack(locale) {
    return packs[hasLocale(locale) ? locale : DEFAULT_LOCALE]
}

export function resolveInitialLocale() {
    const boot = getBoot()

    if (boot.locale && hasLocale(boot.locale)) {
        return boot.locale
    }

    return DEFAULT_LOCALE
}

function isPlainObject(value) {
    return typeof value === 'object' && value !== null && !Array.isArray(value)
}

export function isUsableLangPack(lang) {
    const install = lang?.install

    return isPlainObject(install)
        && typeof install.select_lang === 'string'
        && install.select_lang.length > 0
}

export function extractLangCandidate(source) {
    if (!isPlainObject(source)) {
        return null
    }

    if (isUsableLangPack(source)) {
        return source
    }

    if (isUsableLangPack(source.lang)) {
        return source.lang
    }

    return null
}

export function mergeLangPack(candidate, locale) {
    const fallback = getLangPack(locale)
    const server = extractLangCandidate(candidate)

    if (!server) {
        return fallback
    }

    return {
        install: isPlainObject(server.install)
            ? {...fallback.install, ...server.install}
            : fallback.install,
        user: isPlainObject(server.user)
            ? {...fallback.user, ...server.user}
            : fallback.user,
        language: isPlainObject(server.language)
            ? {...fallback.language, ...server.language}
            : fallback.language,
        agreement: server.agreement ?? fallback.agreement,
        bootstrap: server.bootstrap ?? fallback.bootstrap,
        requirements: isPlainObject(server.requirements)
            ? {...fallback.requirements, ...server.requirements}
            : fallback.requirements,
    }
}

export function resolveLangPayload(payload, locale) {
    if (isUsableLangPack(payload)) {
        return mergeLangPack(payload, locale)
    }

    return mergeLangPack(extractLangCandidate(payload), locale)
}

export function createInitialLangState() {
    const locale = resolveInitialLocale()

    return {
        locale,
        direction: getDirection(locale),
        pack: getLangPack(locale),
    }
}

export function resolveLangState(boot = getBoot()) {
    const locale = resolveInitialLocale()

    return {
        locale,
        direction: getDirection(locale),
        pack: mergeLangPack(boot.lang, locale),
    }
}

