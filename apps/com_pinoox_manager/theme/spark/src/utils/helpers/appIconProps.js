/**
 * Map app API fields to AppIcon props.
 */
export function appIconProps(app, overrides = {}) {
  if (!app) {
    return overrides;
  }

  const isCustom = app.icon_source === 'custom';

  return {
    src: isCustom ? (app.icon ?? '') : '',
    lucide: app.icon_lucide ?? '',
    colors: app.icon_colors ?? [],
    iconStyle: app.icon_style ?? 'crystal',
    iconSource: app.icon_source ?? '',
    alt: app.name ?? '',
    ...overrides,
  };
}
