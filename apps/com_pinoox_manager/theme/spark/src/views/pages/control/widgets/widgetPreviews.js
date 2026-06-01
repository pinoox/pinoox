import WidgetPreviewClock from './previews/WidgetPreviewClock.vue';
import WidgetPreviewStorage from './previews/WidgetPreviewStorage.vue';

export const widgetPreviewMap = {
  clock: WidgetPreviewClock,
  storage: WidgetPreviewStorage,
};

export function getWidgetPreview(id) {
  return widgetPreviewMap[id] ?? null;
}
