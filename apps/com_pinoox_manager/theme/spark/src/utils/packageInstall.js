export const INSTALL_STEP_LABELS = {
    validate: 'بررسی ساختار بسته',
    signature: 'اعتبارسنجی امضای بسته',
    minpin: 'بررسی سازگاری نسخه پینوکس',
    depends: 'بررسی وابستگی‌های اپ',
    detect: 'تشخیص نوع نصب',
    target: 'بررسی اپ میزبان',
    identity: 'تطبیق هویت ناشر',
    pinker_snapshot: 'حفظ تنظیمات اجرایی',
    extract: 'استخراج فایل‌های بسته',
    database: 'تنظیم پیشوند دیتابیس',
    theme_meta: 'اعتبارسنجی متادیتای قالب',
    pinker: 'بازسازی فایل‌های pinker',
    registry: 'ثبت در رجیستری سیستم',
    migrate: 'اجرای مایگریشن‌ها',
    patch: 'اعمال پچ‌های دیتابیس',
    cache: 'بازسازی کش اپلیکیشن',
    complete: 'پایان نصب',
    failed: 'خطا در نصب',
};

export function installStepLabel(step) {
    return INSTALL_STEP_LABELS[step] ?? step;
}

export function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}
