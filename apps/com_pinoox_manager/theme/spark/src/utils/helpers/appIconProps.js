/**
 * Map app API fields to AppIcon props.
 */
import { PINOOX_ICON_GRADIENT } from '@/const/pinooxBrand.js';

export function appIconProps(app, overrides = {}) {
  if (!app) {
    return overrides;
  }

  const forceLucide = overrides.forceLucide === true;
  const isCustom = !forceLucide && app.icon_source === 'custom';
  const iconStyle = app.icon_style ?? 'crystal';
  const colors = Array.isArray(app.icon_colors) && app.icon_colors.length
      ? app.icon_colors
      : (iconStyle === 'gradient' ? PINOOX_ICON_GRADIENT : []);

  return {
    src: isCustom ? (app.icon ?? '') : '',
    lucide: app.icon_lucide ?? '',
    colors,
    iconStyle: forceLucide ? 'crystal' : iconStyle,
    iconSource: forceLucide ? 'lucide' : (app.icon_source ?? ''),
    alt: app.name ?? '',
    forceLucide,
    ...overrides,
  };
}

/** Control panel: always Lucide, no custom PNG tiles. */
export function controlPanelIconProps(app, overrides = {}) {
  return appIconProps(app, { forceLucide: true, ...overrides });
}
