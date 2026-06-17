import { translate } from '@utils/helpers/managerLang.js';

export function resolveAppDisplayLabel(app) {
    if (app?.name?.trim()) {
        return app.name.trim();
    }

    return translate('unknown_app');
}
