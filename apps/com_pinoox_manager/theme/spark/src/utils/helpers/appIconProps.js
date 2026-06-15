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
