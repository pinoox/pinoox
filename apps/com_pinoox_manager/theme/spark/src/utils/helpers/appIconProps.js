/**
 * Map app API fields to AppIcon props.
 */
import { PINOOX_ICON_GRADIENT } from '@/const/pinooxBrand.js';

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
