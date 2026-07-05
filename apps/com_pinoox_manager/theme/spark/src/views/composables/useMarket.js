import {useRouter} from 'vue-router';
import {MARKET_ID, useMarketWindowStore} from '@/stores/modules/marketWindow.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';

export {MARKET_ID};

export function isMarketRoute(route) {
    return String(route?.path ?? '').startsWith('/market');
}

export function useMarket() {
    const router = useRouter();
    const {isAdvanced} = useAppViewMode();
    const marketWindow = useMarketWindowStore();

    async function openMarket(path = '/market') {
        marketWindow.setLastPath(path);

        if (isAdvanced.value) {
            if (marketWindow.isMinimized) {
                marketWindow.restoreSession();
                return;
            }

            if (!marketWindow.isActive) {
                marketWindow.openFullscreen();
            }

            return;
        }

        await router.push(path);
    }

    function closeMarket() {
        if (isAdvanced.value && marketWindow.isOpen) {
            marketWindow.close();
        }

        if (isMarketRoute(router.currentRoute.value)) {
            router.push({name: 'desktop'});
        }
    }

    return {
        openMarket,
        closeMarket,
        isMarketRoute,
        marketWindow,
    };
}
