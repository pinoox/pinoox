import { computed } from 'vue';
import { useAppStore } from '@/stores/modules/app.js';
import { useOptionsStore } from '@/stores/modules/options.js';
import { saxIcon } from '@/const/icons.js';

import { PINOOX_ICON_GRADIENT } from '@/const/pinooxBrand.js';

export const systemDockApps = [
    { id: 'apps', name: 'اپ‌ها', action: 'launcher', route: null, icon: saxIcon.manager, image: null },
    { id: 'control', name: 'کنترل پنل', route: '/control/apps', icon: saxIcon.control, image: null },
    { id: 'market', name: 'مارکت', route: '/market', icon: saxIcon.market, image: null },
];

export function resolveAppRoute(app) {
    if (app.open === 'app-users')
        return `/app-manager/${app.package_name}/users`;
    if (app.open === 'app-config')
        return `/app-manager/${app.package_name}/config`;
    if (app.open === 'app-view')
        return { name: 'app-view', params: { package_name: app.package_name } };
    if (app.open)
        return { name: app.open, params: { package_name: app.package_name } };
    return `/app-manager/${app.package_name}/details`;
}

function mapAppToDockItem(app) {
    const iconStyle = app.icon_style ?? 'crystal';
    const colors = Array.isArray(app.icon_colors) && app.icon_colors.length
        ? app.icon_colors
        : (iconStyle === 'gradient' ? PINOOX_ICON_GRADIENT : []);

    return {
        id: app.package_name,
        name: app.name,
        image: app.icon_source === 'custom' ? app.icon : null,
        lucide: app.icon_lucide,
        colors,
        iconStyle,
        iconSource: app.icon_source,
        route: resolveAppRoute(app),
    };
}

export function useDockApps() {
    const appStore = useAppStore();
    const optionsStore = useOptionsStore();

    const dockApps = computed(() => {
        const list = appStore.appList ?? [];
        const pins = optionsStore.dockPins;

        if (pins !== null) {
            return pins
                .map((packageName) => list.find((app) => app.package_name === packageName))
                .filter(Boolean)
                .map(mapAppToDockItem);
        }

        return [];
    });

    function isDockPinned(packageName) {
        const pins = optionsStore.dockPins;

        if (pins !== null)
            return pins.includes(packageName);

        return false;
    }

    async function toggleDockPin(packageName) {
        await optionsStore.toggleDockPin(packageName, appStore.appList ?? []);
    }

    const unpinnedApps = computed(() =>
        (appStore.appList ?? []).filter((app) => !isDockPinned(app.package_name))
    );

    return { dockApps, systemDockApps, unpinnedApps, isDockPinned, toggleDockPin };
}
