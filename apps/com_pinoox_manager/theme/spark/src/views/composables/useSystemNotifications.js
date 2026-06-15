import { useNotificationStore } from '@/stores/modules/notification.js';

export async function pushSystemNotifications() {
  const notificationStore = useNotificationStore();

  try {
    await notificationStore.fetchAll();
    await notificationStore.pushUnreadToasts();
  } catch {
    // Non-blocking
  }
}

export async function refreshNotifications() {
  const notificationStore = useNotificationStore();

  try {
    await notificationStore.fetchAll();
  } catch {
    // ignore
  }
}
