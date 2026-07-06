import {lucideSidebar} from '@/const/icons.js';

export const controlMenuItems = [
    {
        href: '/control/widgets',
        title: 'ویجت‌ها',
        iconName: lucideSidebar.widgets,
    },
    {
        href: '/control/apps',
        title: 'اپلیکیشن‌ها',
        iconName: lucideSidebar.apps,
    },
    {
        href: '/control/routes',
        title: 'مسیریابی',
        iconName: lucideSidebar.routes,
    },
    {
        title: 'تنظیمات',
        iconName: lucideSidebar.setting,
        children: [
            {
                href: '/control/settings/appearance',
                title: 'ظاهر و زمینه',
            },
            {
                href: '/control/settings/application',
                title: 'تنظیمات اپلیکیشن',
            },
        ],
    },
    {
        href: '/control/profile',
        title: 'حساب کاربری',
        iconName: lucideSidebar.profile,
    },
    {
        href: '/control/pincore',
        title: 'پینوکس',
        iconName: lucideSidebar.pincore,
    },
    {
        href: '/market',
        title: 'مارکت',
        iconName: lucideSidebar.market,
    },
];

export function menuIconComponent(LucideIcon, name) {
    return {
        element: LucideIcon,
        attributes: {name},
    };
}

export function toSidebarMenuItems(LucideIcon) {
    return controlMenuItems.map((item) => {
        if (item.children) {
            return {
                title: item.title,
                icon: menuIconComponent(LucideIcon, item.iconName),
                attributes: {'aria-label': item.title},
                child: item.children.map((child) => ({...child})),
            };
        }

        return {
            href: item.href,
            title: item.title,
            icon: menuIconComponent(LucideIcon, item.iconName),
            attributes: {'aria-label': item.title},
        };
    });
}

export function isControlMenuItemActive(routePath, item) {
    if (item.href) {
        return routePath === item.href || routePath.startsWith(`${item.href}/`);
    }

    if (item.children) {
        return item.children.some((child) =>
            routePath === child.href || routePath.startsWith(`${child.href}/`)
        );
    }

    return false;
}
