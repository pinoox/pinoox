/**
 * Resolve a Lucide kebab-case name to a lucide-vue-next component.
 * @see https://lucide.dev/
 */
import * as LucideIcons from 'lucide-vue-next';

export function resolveLucideComponent(name) {
  if (!name || typeof name !== 'string') {
    return null;
  }

  const key = name
      .trim()
      .toLowerCase()
      .split('-')
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join('');

  return LucideIcons[key] ?? null;
}

export function lucideIconSize(size) {
  const map = {
    xs: 24,
    sm: 28,
    md: 48,
    lg: 56,
    xl: 60,
    tray: 40,
    dock: 32,
  };

  return map[size] ?? 48;
}

export function lucideStrokeWidth(size) {
  const map = {
    xs: 2.15,
    sm: 2.15,
    md: 2.35,
    lg: 2.4,
    xl: 2.4,
    tray: 2.2,
    dock: 2.2,
  };

  return map[size] ?? 2.35;
}
