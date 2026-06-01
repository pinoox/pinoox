import { computed, ref } from 'vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import { useOptionsStore } from '@/stores/modules/options.js';

export function useWidgetSettings() {
  const optionsStore = useOptionsStore();
  const widgets = ref({});
  const loading = ref(false);

  const widgetList = computed(() => Object.values(widgets.value));

  function getWidget(id) {
    return widgets.value[id] ?? null;
  }

  async function loadWidgets() {
    loading.value = true;

    try {
      if (!optionsStore.isLoaded)
        await optionsStore.load();

      const response = await widgetAPI.settings();
      const data = unwrapResponse(response) ?? {};

      widgets.value = data.widgets ?? optionsStore.widgets ?? {};
    } finally {
      loading.value = false;
    }
  }

  async function toggleVisibility(id, visible) {
    const previous = { ...widgets.value };

    widgets.value = {
      ...widgets.value,
      [id]: {
        ...widgets.value[id],
        visible,
      },
    };

    try {
      const response = await widgetAPI.saveWidgets({
        widgets: {
          [id]: { visible },
        },
      });

      const data = unwrapResponse(response) ?? {};

      if (data.widgets)
        widgets.value = data.widgets;

      optionsStore.setWidgets(data.widgets ?? widgets.value);

      return data;
    } catch (error) {
      widgets.value = previous;
      throw error;
    }
  }

  return {
    widgets,
    widgetList,
    loading,
    getWidget,
    loadWidgets,
    toggleVisibility,
  };
}
