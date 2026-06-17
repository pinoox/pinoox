import { translate } from '@utils/helpers/managerLang.js';

const MANAGER_PACKAGE = 'com_pinoox_manager';

export function isManagerBrandApp(app, packageName = null) {
    const pkg = packageName ?? app?.package_name ?? app?.package ?? null;

    if (pkg !== MANAGER_PACKAGE) {
        return false;
    }

    return !(app?.icon_source === 'custom' && app?.icon);
}

export function resolveAppDisplayLabel(app, packageName = null) {
    const pkg = packageName ?? app?.package_name ?? app?.package ?? null;

    if (pkg === MANAGER_PACKAGE) {
        return translate('system_app_manager');
    }

    if (app?.name?.trim()) {
        return app.name.trim();
    }

    return translate('unknown_app');
}

export function managerBrandIconProps(app, packageName = null) {
    return {
        lucide: 'settings',
        alt: resolveAppDisplayLabel(app, packageName),
    };
}
