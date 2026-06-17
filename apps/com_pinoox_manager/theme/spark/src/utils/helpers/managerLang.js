import { getBoot } from '@/boot.js';
import { useOptionsStore } from '@/stores/modules/options.js';

const MESSAGES = {
    fa: {
        unknown_app: 'برنامه نامشخص',
        system_app_manager: 'منجر — مدیریت پینوکس',
        app_single_route_only: 'این برنامه فقط یک آدرس می‌پذیرد (در app.php با type: single)',
        route_action_edit: 'ویرایش',
        route_action_delete: 'حذف',
        route_action_no_delete: 'غیرقابل حذف',
        route_actions_locked: 'آدرس ثابت — قابل ویرایش نیست',
        route_actions_unavailable: 'عملیاتی وجود ندارد',
        route_url_copy: 'کپی آدرس',
        route_url_copied: 'کپی شد',
        route_url_open: 'باز کردن در تب جدید',
        route_delete_title: 'حذف آدرس',
        route_delete_lead: 'آیا از حذف این مسیریابی مطمئن هستید؟',
        route_delete_hint: 'با حذف، این آدرس دیگر به برنامه‌ای متصل نخواهد بود.',
        route_delete_cancel: 'انصراف',
        route_delete_confirm: 'بله، حذف شود',
        route_delete_progress: 'در حال حذف…',
    },
    en: {
        unknown_app: 'Unknown app',
        system_app_manager: 'Manager — Pinoox Control Panel',
        app_single_route_only: 'This app accepts only one URL (router.type: single in app.php)',
        route_action_edit: 'Edit',
        route_action_delete: 'Delete',
        route_action_no_delete: 'Cannot delete',
        route_actions_locked: 'Fixed route — not editable',
        route_actions_unavailable: 'No actions available',
        route_url_copy: 'Copy address',
        route_url_copied: 'Copied',
        route_url_open: 'Open in new tab',
        route_delete_title: 'Delete route',
        route_delete_lead: 'Are you sure you want to delete this route?',
        route_delete_hint: 'After deletion, this address will no longer be linked to an app.',
        route_delete_cancel: 'Cancel',
        route_delete_confirm: 'Yes, delete',
        route_delete_progress: 'Deleting…',
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
