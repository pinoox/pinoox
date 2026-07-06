import { defineStore } from 'pinia';
import { notificationAPI } from '@api/notification.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import { toastInfo } from '@utils/helpers/toastHelper.js';

function unwrapNotificationList(response) {
  const data = unwrapResponse(response);

  if (Array.isArray(data)) {
    return data;
  }

  if (data && Array.isArray(data.items)) {
    return data.items;
  }

  return [];
}

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    items: [],
    loaded: false,
    loading: false,
  }),

  getters: {
    unreadItems(state) {
      return state.items.filter((item) => item?.status === 'send');
    },

    unreadCount(state) {
      return state.items.filter((item) => item?.status === 'send').length;
    },
  },

  actions: {
    async fetchAll() {
      this.loading = true;

      try {
        const response = await notificationAPI.getAll();
        this.items = unwrapNotificationList(response);
        this.loaded = true;
      } finally {
        this.loading = false;
      }
    },

    async pushUnreadToasts() {
      const pending = this.unreadItems;

      if (!pending.length) {
        return;
      }

      pending.forEach((item, index) => {
        window.setTimeout(() => {
          toastInfo(item.title ?? 'اعلان', item.message ?? item.text ?? '');
        }, 300 + index * 500);
      });

      window.setTimeout(async () => {
        try {
          await this.markSeen(pending.map((item) => item.ntf_id));
        } catch {
          // ignore
        }
      }, 300 + pending.length * 500 + 800);
    },

    async markSeen(ids) {
      const list = ids
          .map((id) => ({ ntf_id: id }))
          .filter((entry) => entry.ntf_id);

      if (!list.length) {
        return;
      }

      await notificationAPI.seen(list);

      list.forEach(({ ntf_id }) => {
        const item = this.items.find((entry) => entry.ntf_id === ntf_id);

        if (item) {
          item.status = 'seen';
        }
      });
    },

    async hide(id) {
      await notificationAPI.hide(id);
      this.items = this.items.filter((item) => item.ntf_id !== id);
    },

    reset() {
      this.items = [];
      this.loaded = false;
      this.loading = false;
    },
  },
});
