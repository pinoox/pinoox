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
        route_save_progress_create: 'در حال ایجاد آدرس…',
        route_save_progress_edit: 'در حال ذخیره آدرس…',
        route_save_progress_home: 'در حال ذخیره…',
        route_save_success_create: 'آدرس با موفقیت ایجاد شد',
        route_save_success_edit: 'آدرس ذخیره شد',
        route_save_success_home: 'برنامهٔ صفحه اصلی ذخیره شد',
        route_path_duplicate: 'این آدرس قبلاً ثبت شده و به برنامه «{app}» متصل است.',
        are_you_sure_delete_app: 'حذف این برنامه؟',
        cannot_delete_system_app: 'این برنامه بخشی از سیستم است و قابل حذف نیست.',
        app_uninstall_lead: 'این برنامه از سایت شما پاک می‌شود.',
        app_uninstall_hint: 'همهٔ اطلاعات و تنظیمات این برنامه حذف می‌شود و امکان بازگردانی وجود ندارد. برای استفادهٔ دوباره باید برنامه را از نو نصب کنید.',
        app_uninstall_routes_warning: 'این برنامه به {count} آدرس متصل است. با حذف برنامه، این آدرس‌ها هم حذف می‌شوند:',
        app_uninstall_routes_single: 'این برنامه به آدرس {path} متصل است. با حذف برنامه، این آدرس هم حذف می‌شود.',
        app_uninstall_confirm: 'بله، حذف شود',
        app_uninstall_progress: 'در حال حذف برنامه…',
        cancel: 'انصراف',
        delete_successfully: 'برنامه با موفقیت حذف شد',
        app_details_about: 'درباره برنامه',
        app_details_addresses: 'آدرس‌های فعال',
        app_details_more_addresses: 'آدرس دیگر',
        app_run: 'باز کردن برنامه',
        app_settings: 'تنظیمات',
        app_templates: 'قالب‌ها',
        app_badge_system: 'برنامه سیستمی',
        app_badge_hidden: 'مخفی',
        app_badge_no_dock: 'بدون میانبر',
        app_system_notice: 'این برنامه بخشی از سیستم پینوکس است و از اینجا قابل حذف نیست.',
        app_uninstall_title: 'حذف برنامه',
        app_uninstall_intro: 'با حذف این برنامه، همهٔ فایل‌ها و اطلاعات مربوط به آن از سایت پاک می‌شود. برای استفادهٔ دوباره باید آن را از نو نصب کنید.',
        app_uninstall_button: 'حذف این برنامه',
        app_stat_version: 'نسخه',
        app_stat_version_code: 'شماره نسخه',
        app_stat_developer: 'سازنده',
        app_stat_package: 'شناسه برنامه',
        app_stat_routing: 'نوع آدرس',
        app_stat_address_count: 'تعداد آدرس',
        app_routing_single: 'یک آدرس',
        app_routing_multiple: 'چند آدرس',
        template_activate_button: 'فعال‌سازی',
        template_delete_button: 'حذف قالب',
        template_active_badge: 'قالب فعال',
        template_active_no_delete: 'قالب فعال قابل حذف نیست.',
        template_delete_confirm: 'آیا از حذف این قالب مطمئن هستید؟',
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
        route_save_progress_create: 'Creating address…',
        route_save_progress_edit: 'Saving address…',
        route_save_progress_home: 'Saving…',
        route_save_success_create: 'Address created successfully',
        route_save_success_edit: 'Address saved',
        route_save_success_home: 'Homepage app saved',
        route_path_duplicate: 'This address already exists and is linked to «{app}».',
        are_you_sure_delete_app: 'Remove this app?',
        cannot_delete_system_app: 'This app is part of the system and cannot be removed.',
        app_uninstall_lead: 'This app will be removed from your site.',
        app_uninstall_hint: 'All data and settings for this app will be deleted and cannot be restored. To use it again, you will need to install it again.',
        app_uninstall_routes_warning: 'This app is linked to {count} addresses. Removing the app will delete these routes too:',
        app_uninstall_routes_single: 'This app is linked to {path}. Removing the app will delete this route too.',
        app_uninstall_confirm: 'Yes, remove it',
        app_uninstall_progress: 'Removing app…',
        cancel: 'Cancel',
        delete_successfully: 'App removed successfully',
        app_details_about: 'About this app',
        app_details_addresses: 'Active addresses',
        app_details_more_addresses: 'more addresses',
        app_run: 'Open app',
        app_settings: 'Settings',
        app_templates: 'Themes',
        app_badge_system: 'System app',
        app_badge_hidden: 'Hidden',
        app_badge_no_dock: 'No shortcut',
        app_system_notice: 'This app is part of Pinoox and cannot be removed from here.',
        app_uninstall_title: 'Remove app',
        app_uninstall_intro: 'Removing this app deletes all its files and data from your site. To use it again, you will need to install it again.',
        app_uninstall_button: 'Remove this app',
        app_stat_version: 'Version',
        app_stat_version_code: 'Version number',
        app_stat_developer: 'Developer',
        app_stat_package: 'App ID',
        app_stat_routing: 'Address type',
        app_stat_address_count: 'Address count',
        app_routing_single: 'Single address',
        app_routing_multiple: 'Multiple addresses',
        template_activate_button: 'Activate',
        template_delete_button: 'Delete template',
        template_active_badge: 'Active theme',
        template_active_no_delete: 'The active theme cannot be deleted.',
        template_delete_confirm: 'Are you sure you want to delete this template?',
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
