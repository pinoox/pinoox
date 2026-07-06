import { onMounted, onUnmounted, ref } from 'vue';

function resolveDateLocale() {
  const lang = document.documentElement.lang?.toLowerCase() ?? 'fa';

  if (lang.startsWith('fa')) {
    return 'fa-IR-u-ca-persian';
  }

  return 'en-GB';
}

/** Same time formatting as Toolbar — browser locale, no invalid IANA offset strings. */
export function formatClockTime(now = new Date()) {
  return now.toLocaleTimeString('fa-IR', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  });
}

export function formatClockDate(now = new Date()) {
  return now.toLocaleDateString(resolveDateLocale(), {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

/**
 * Fetch server time once, then tick locally from timestamp offset.
 */
export function useServerClock(fetchClockData) {
  const loading = ref(true);
  const clock = ref({ date: '', moment: '' });

  let serverOffsetMs = 0;
  let tickTimer = null;

  function serverNow() {
    return new Date(Date.now() + serverOffsetMs);
  }

  function tick() {
    const now = serverNow();
    clock.value.moment = formatClockTime(now);
    clock.value.date = formatClockDate(now);
  }

  async function syncOnce() {
    const data = await fetchClockData();
    const timestamp = Number(data?.timestamp ?? data?.time ?? 0);

    if (timestamp > 0) {
      serverOffsetMs = timestamp * 1000 - Date.now();
      tick();
      return;
    }

    clock.value.date = data?.date ?? '';
    clock.value.moment = data?.moment ?? '';
  }

  onMounted(async () => {
    try {
      await syncOnce();
    } finally {
      loading.value = false;
    }

    tickTimer = setInterval(tick, 1000);
  });

  onUnmounted(() => {
    if (tickTimer) {
      clearInterval(tickTimer);
      tickTimer = null;
    }
  });

  return { loading, clock };
}
