/**
 * Map app API fields to AppIcon props.
 */
import { PINOOX_ICON_GRADIENT } from '@/const/pinooxBrand.js';

const SYSTEM_ROUTE_APPS = {
  com_pinoox_manager: {
    lucide: 'layout-dashboard',
    colors: PINOOX_ICON_GRADIENT,
  },
  com_pinoox_welcome: {
    lucide: 'sparkles',
    colors: ['#E879F9', '#A855F7', '#6B21A8'],
  },
  com_pinoox_installer: {
    lucide: 'download',
    colors: ['#34D399', '#059669', '#064E3B'],
  },
  com_pinoox_comingsoon: {
    lucide: 'clock',
    colors: ['#FBBF24', '#D97706', '#92400E'],
  },
};

export function appIconProps(app, overrides = {}) {
  if (!app) {
    return overrides;
  }

  const isCustom = app.icon_source === 'custom';
  const iconStyle = app.icon_style ?? 'crystal';
  const colors = Array.isArray(app.icon_colors) && app.icon_colors.length
      ? app.icon_colors
      : (iconStyle === 'gradient' ? PINOOX_ICON_GRADIENT : []);

  return {
    src: isCustom ? (app.icon ?? '') : '',
    lucide: app.icon_lucide ?? '',
    colors,
    iconStyle,
    iconSource: app.icon_source ?? '',
    alt: app.name ?? '',
    ...overrides,
  };
}

/** @deprecated use appIconProps — kept for existing imports */
export function controlPanelIconProps(app, overrides = {}) {
  return appIconProps(app, overrides);
}

export function appIconPropsForPackage(appStore, packageName, fallback = {}) {
  const app = appStore.fetchAppByPackage(packageName);

  if (app) {
    return appIconProps(app);
  }

  const image = fallback.image ?? fallback.icon ?? '';

  if (!image && !fallback.lucide) {
    return null;
  }

  return {
    src: image,
    lucide: fallback.lucide ?? '',
    colors: fallback.colors ?? [],
    iconStyle: fallback.iconStyle ?? 'crystal',
    iconSource: fallback.iconSource ?? (image ? 'custom' : 'lucide'),
    alt: fallback.name ?? packageName,
  };
}

export function packageMetaIconProps(meta, appStore = null) {
  if (!meta) {
    return {};
  }

  const alt = meta.name || meta.template_name || meta.package_name || meta.filename || '';

  if (meta.has_icon && meta.icon) {
    return {
      src: meta.icon,
      iconSource: 'custom',
      alt,
    };
  }

  if (meta.type === 'theme' && meta.cover && !String(meta.cover).includes('theme.jpg')) {
    return {
      src: meta.cover,
      iconSource: 'custom',
      alt,
    };
  }

  const packageName = meta.type === 'app'
      ? (meta.package_name || meta.package)
      : meta.app;

  if (packageName && appStore) {
    const props = appIconPropsForPackage(appStore, packageName, {name: alt});

    if (props) {
      return props;
    }
  }

  if (meta.type === 'theme') {
    return {
      lucide: 'palette',
      colors: ['#F472B6', '#DB2777', '#831843'],
      iconStyle: 'gradient',
      iconSource: 'lucide',
      alt,
    };
  }

  return {
    lucide: 'box',
    colors: PINOOX_ICON_GRADIENT,
    iconStyle: 'gradient',
    iconSource: 'lucide',
    alt,
  };
}

export function resolveRouteAppIconProps(app, packageName = null) {
  const pkg = packageName ?? app?.package_name ?? app?.package ?? null;

  if (app?.icon_source === 'custom' && app?.icon) {
    return appIconProps(app);
  }

  const system = pkg ? SYSTEM_ROUTE_APPS[pkg] : null;

  if (system) {
    return {
      lucide: system.lucide,
      colors: system.colors,
      iconStyle: 'gradient',
      iconSource: 'lucide',
      alt: app?.name ?? pkg,
    };
  }

  if (app) {
    return appIconProps(app);
  }

  return {
    lucide: 'box',
    colors: PINOOX_ICON_GRADIENT,
    iconStyle: 'gradient',
    iconSource: 'lucide',
    alt: pkg ?? '',
  };
}
