import { onMounted, onUnmounted, ref } from 'vue';

const DEFAULT_TIMEZONE = 'Asia/Tehran';

function resolveDateLocale() {
  const lang = document.documentElement.lang?.toLowerCase() ?? 'fa';

  if (lang.startsWith('fa')) {
    return 'fa-IR-u-ca-persian';
  }

  return 'en-GB';
}

function createFormatters(timezone) {
  return {
    moment: new Intl.DateTimeFormat('en-GB', {
      timeZone: timezone,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    }),
    date: new Intl.DateTimeFormat(resolveDateLocale(), {
      timeZone: timezone,
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
  };
}

/**
 * Fetch server time once, then tick locally from timestamp offset.
 */
export function useServerClock(fetchClockData) {
  const loading = ref(true);
  const clock = ref({ date: '', moment: '' });

  let serverOffsetMs = 0;
  let timezone = DEFAULT_TIMEZONE;
  let formatters = createFormatters(timezone);
  let tickTimer = null;

  function serverNow() {
    return new Date(Date.now() + serverOffsetMs);
  }

  function tick() {
    const now = serverNow();
    clock.value.moment = formatters.moment.format(now);
    clock.value.date = formatters.date.format(now);
  }

  async function syncOnce() {
    const data = await fetchClockData();
    const timestamp = Number(data?.timestamp ?? data?.time ?? 0);

    if (data?.timezone) {
      timezone = data.timezone;
      formatters = createFormatters(timezone);
    }

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
