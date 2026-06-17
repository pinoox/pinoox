const SYSTEM_APP_LABELS = {
    com_pinoox_manager: 'منجر — مدیریت پینوکس',
    com_pinoox_welcome: 'ولکام — صفحه اصلی',
    com_pinoox_installer: 'نصب‌کننده پینوکس',
    com_pinoox_comingsoon: 'به‌زودی',
};

function packageSlug(packageName) {
    if (!packageName) {
        return '';
    }

    if (packageName.startsWith('com_pinoox_')) {
        return packageName.slice('com_pinoox_'.length);
    }

    if (packageName.startsWith('com_')) {
        return packageName.slice('com_'.length);
    }

    return packageName;
}

function isGenericAppName(name, packageName) {
    if (!name || !packageName) {
        return false;
    }

    const normalized = name.trim().toLowerCase();
    const slug = packageSlug(packageName).toLowerCase();

    return normalized === slug
        || normalized === packageName.toLowerCase()
        || normalized === packageName.replace(/_/g, '-').toLowerCase();
}

export function isSystemAppPackage(packageName) {
    return Boolean(packageName && SYSTEM_APP_LABELS[packageName]);
}

export function resolveAppDisplayLabel(app, packageName = null) {
    const pkg = packageName ?? app?.package_name ?? app?.package ?? null;

    if (pkg && SYSTEM_APP_LABELS[pkg]) {
        if (!app?.name?.trim() || isGenericAppName(app.name, pkg)) {
            return SYSTEM_APP_LABELS[pkg];
        }
    }

    if (app?.name?.trim()) {
        return app.name.trim();
    }

    if (pkg && SYSTEM_APP_LABELS[pkg]) {
        return SYSTEM_APP_LABELS[pkg];
    }

    return 'برنامه نامشخص';
}
