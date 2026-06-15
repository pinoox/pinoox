import { notificationAPI } from '@api/notification.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import { toastInfo } from '@utils/helpers/toastHelper.js';

export async function pushSystemNotifications() {
  try {
    const response = await notificationAPI.getAll();
    const items = unwrapResponse(response);

    if (!Array.isArray(items) || items.length === 0) {
      return;
    }

    const pending = items.filter((item) => item?.status === 'send');

    pending.forEach((item, index) => {
      window.setTimeout(() => {
        toastInfo(item.title ?? 'اعلان', item.message ?? '');
      }, index * 450);
    });

    if (pending.length > 0) {
      await notificationAPI.seen(
          pending.map((item) => ({ ntf_id: item.ntf_id })),
      );
    }
  } catch {
    // Non-blocking: manager still loads if notifications fail.
  }
}
