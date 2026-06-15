import { defineStore } from 'pinia';

import { optionAPI } from '@api/option.js';

import { unwrapResponse } from '@utils/helpers/apiHelper.js';

import { resolveWallpaperId, wallpaperUrl } from '@utils/helpers/backgroundHelper.js';



export const useOptionsStore = defineStore('options', {

    state: () => ({

        background: '',

        defaultBackground: '',

        wallpapers: [],

        lock_time: 0,

        lang: 'fa',

        dockPins: null,

        appViewMode: 'simple',

        widgets: {},

        isLoaded: false,

    }),

    getters: {

        backgroundUrl(state) {

            return wallpaperUrl(state.wallpapers, state.background, state.defaultBackground);

        },

        isWidgetVisible: (state) => (id) => state.widgets[id]?.visible !== false,

    },

    actions: {

        async load() {

            const response = await optionAPI.get();

            const data = unwrapResponse(response) ?? {};



            this.wallpapers = Array.isArray(data.wallpapers) ? data.wallpapers : [];

            this.defaultBackground = String(data.defaultBackground ?? this.wallpapers[0]?.id ?? '');

            this.background = resolveWallpaperId(this.wallpapers, data.background, this.defaultBackground);

            this.lock_time = Number(data.lock_time ?? 0);

            this.lang = data.lang || 'fa';

            this.dockPins = Array.isArray(data.dock_pins) ? data.dock_pins : null;

            this.appViewMode = data.app_view_mode === 'advanced' ? 'advanced' : 'simple';

            this.widgets = (data.widgets && typeof data.widgets === 'object') ? data.widgets : {};

            this.isLoaded = true;

        },

        async changeBackground(name) {

            await optionAPI.changeBackground(name);

            this.background = resolveWallpaperId(this.wallpapers, name, this.defaultBackground);

        },

        async uploadWallpaper(file) {

            const formData = new FormData();

            formData.append('wallpaper', file);

            const response = await optionAPI.uploadWallpaper(formData);

            const data = unwrapResponse(response) ?? {};



            if (Array.isArray(data.wallpapers))

                this.wallpapers = data.wallpapers;



            return data.wallpaper ?? null;

        },

        async deleteWallpaper(id) {

            const response = await optionAPI.deleteWallpaper(id);

            const data = unwrapResponse(response) ?? {};



            if (Array.isArray(data.wallpapers))

                this.wallpapers = data.wallpapers;



            if (data.defaultBackground != null)

                this.defaultBackground = String(data.defaultBackground);



            if (data.background != null)

                this.background = resolveWallpaperId(this.wallpapers, data.background, this.defaultBackground);



            return data;

        },

        async changeLockTime(minutes) {

            const response = await optionAPI.changeLockTime(minutes);

            const saved = unwrapResponse(response);

            if (saved !== false && saved != null)

                this.lock_time = Number(saved);

        },

        async changeAppViewMode(mode) {
            const nextMode = mode === 'advanced' ? 'advanced' : 'simple';
            const previous = this.appViewMode;
            this.appViewMode = nextMode;

            try {
                const response = await optionAPI.changeAppViewMode(nextMode);
                const saved = unwrapResponse(response);

                if (saved === false) {
                    this.appViewMode = previous;
                    return saved;
                }

                if (typeof saved === 'string') {
                    this.appViewMode = saved === 'advanced' ? 'advanced' : 'simple';
                }

                return saved;
            } catch (error) {
                this.appViewMode = previous;
                throw error;
            }
        },

        async changeLang(lang) {

            const response = await optionAPI.changeLang(lang);

            const data = unwrapResponse(response) ?? {};

            this.lang = lang;

            if (data.direction)

                document.body.className = data.direction;

            return data;

        },

        async toggleDockPin(packageName, appList = []) {

            const previous = this.dockPins;

            let pins = Array.isArray(this.dockPins)
                ? [...this.dockPins]
                : [];



            if (pins.includes(packageName))

                pins = pins.filter((pkg) => pkg !== packageName);

            else

                pins = [...pins, packageName];



            this.dockPins = pins;



            try {

                const response = await optionAPI.toggleDockPin(packageName);

                const data = unwrapResponse(response) ?? {};



                if (Array.isArray(data.dock_pins))

                    this.dockPins = data.dock_pins;



                return data;

            } catch (error) {

                this.dockPins = previous;

                throw error;

            }

        },

        setWidgets(widgets) {

            if (widgets && typeof widgets === 'object')

                this.widgets = widgets;

        },

        reset() {

            this.background = this.defaultBackground || '';

            this.defaultBackground = '';

            this.wallpapers = [];

            this.lock_time = 0;

            this.lang = 'fa';

            this.dockPins = null;

            this.appViewMode = 'simple';

            this.widgets = {};

            this.isLoaded = false;

        },

    },

});


