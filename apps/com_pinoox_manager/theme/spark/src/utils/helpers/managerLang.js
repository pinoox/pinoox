import { getBoot } from '@/boot.js';
import { useOptionsStore } from '@/stores/modules/options.js';

const MESSAGES = {
    fa: {
        unknown_app: 'برنامه نامشخص',
        app_single_route_only: 'این برنامه فقط یک آدرس می‌پذیرد (در app.php با type: single)',
    },
    en: {
        unknown_app: 'Unknown app',
        app_single_route_only: 'This app accepts only one URL (router.type: single in app.php)',
    },
};

export function resolveLocale(lang) {
    const code = String(lang ?? readActiveLang() ?? 'fa').toLowerCase();

    if (code.startsWith('fa')) {
        return 'fa';
    }

    return 'en';
}

function readActiveLang() {
    try {
        const store = useOptionsStore();

        if (store.lang) {
            return store.lang;
        }
    } catch {
        // Pinia may not be ready during early boot.
    }

    return getBoot().locale ?? document.documentElement.lang ?? 'fa';
}

export function translate(key, lang) {
    const locale = resolveLocale(lang);
    return MESSAGES[locale]?.[key] ?? MESSAGES.en[key] ?? key;
}
