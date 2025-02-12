import {defineStore} from 'pinia';

const typeToTypeof = {
    [String]: 'string', [Number]: 'number', [Boolean]: 'boolean', [Array]: 'array', [Object]: 'object',
};

const getType = (type) => {
    return typeToTypeof[type] ? typeToTypeof[type] : 'custom';
}

const getByParamOptions = (key, param, options, defaultValue = null) => {
    const option = getByOption(key, options, defaultValue);
    return getByParam(key, param, option);
}

const getByOption = (key, options, defaultValue = null) => {
    return key in options ? options[key] : defaultValue;
}

const getByParam = (key, param, defaultValue = null) => {
    return param && typeof param === 'object' && key in param ? param[key] : defaultValue;
}

const checkType = (value, type) => {
    type = getType(type);

    return type === 'custom' || typeof value === type;
}

const isEmpty = (value) => {
    return (value === null || value === undefined || (Array.isArray(value) && !value.every(() => true)) || (typeof value === 'object' && Object.keys(value).length === 0) || (typeof value === 'string' && value.trim() === '') || (typeof value === 'number' && value === 0));
}

const cloneValue = (value, deep = true) => {
    if (value === undefined) {
        return undefined;
    } else if (value === null) {
        return null;
    } else if (typeof value === 'object') {
        if (deep) {
            if (Array.isArray(value)) {
                return value.map(cloneValue);
            } else {
                const clonedObject = {};
                for (const key in value) {
                    if (Object.prototype.hasOwnProperty.call(value, key)) {
                        clonedObject[key] = cloneValue(value[key]);
                    }
                }
                return clonedObject;
            }
        } else {
            return Array.isArray(value) ? [...value] : {...value}
        }
    } else if (typeof value === 'function') {
        return value;
    } else {
        return value;
    }
}

const generateUniqueId = () => `${Date.now().toString(16)}${Math.floor(Math.random() * 0xffffffff).toString(16)}`;

const buildParams = (patterns, options = {}, data = {}) => {
    const result = {};
    data = !!data ? data : {};

    for (const [key, item] of Object.entries(patterns)) {
        const param = cloneValue(item);
        const field = param && typeof param === 'object' && 'field' in param ? param.field : key;
        const type = param && typeof param === 'object' && 'type' in param ? param.type : 'custom';
        const inputify = getByParamOptions('inputify', param, options);
        const valueParamDefault = typeof param === 'object' && !Array.isArray(param) ? getByOption('default', options, null) : param;
        const nullToUndefined = getByParamOptions('nullable', param, options) === false;
        const emptyToUndefined = getByParamOptions('blank', param, options) === false;

        let valueDefault = getByParam('default', param, valueParamDefault);

        if (typeof valueDefault === 'function') {
            valueDefault = valueDefault(data);
        }

        let value = field in data ? data[field] : null;

        if (!(field in data) || (type !== 'custom' && !checkType(value, type)) || (type === Array && !Array.isArray(value)) || value === undefined || (nullToUndefined && value === null) || (emptyToUndefined && isEmpty(value))) {
            value = valueDefault;
        }

        if (typeof inputify === 'function') {
            result[key] = inputify(value, data);
        } else {
            result[key] = value;
        }
    }
    return result;
};

const buildSubmitParams = (patterns, options = {}, data = {}) => {
    const result = {};
    data = !!data ? data : {};

    for (const [key, value] of Object.entries(data)) {
        const param = cloneValue(patterns[key], false);
        const valueParamDefault = typeof param === 'object' && !Array.isArray(param) ? getByOption('default', options, null) : param;

        let valueDefault = getByParam('default', param, valueParamDefault);

        const submitify = getByParamOptions('submitify', param, options);
        const nullToUndefined = getByParamOptions('nullable', param, options) === false;
        const emptyToUndefined = getByParamOptions('blank', param, options) === false;
        let resultValue = typeof submitify === 'function' ? submitify(value, data) : value;
        const buildField = getByOption('buildField', options);
        resultValue = buildField && typeof buildField === 'function' ? buildField(key, resultValue) : resultValue;
        resultValue = resultValue !== undefined ? resultValue : valueDefault;

        if (resultValue === undefined || (nullToUndefined && resultValue === null) || (emptyToUndefined && isEmpty(resultValue))) continue;

        result[key] = resultValue !== undefined ? resultValue : valueDefault;
    }

    const transform = getByOption('transform', options);
    return transform && typeof transform === 'function' ? transform(result) : result;
};

const generateStoreByParams = (patterns, options = {}, id = null) => {
    id = 'params:' + (id || generateUniqueId());

    return defineStore(id, {
        state: () => buildParams(patterns, options), getters: {
            params: (state) => buildSubmitParams(patterns, options, state.$state),
            patterns: () => patterns,
        }, actions: {
            setRawParams(params) {
                this.$state = params;
            }, setParams(params) {
                this.setRawParams(buildParams(patterns, options, params));
            }, resetParams() {
                this.$reset();
                // this.setRawParams(buildParams(patterns, options, null));
            }
        }
    });
};
export const defineParams = (patterns, options = {}, id = null) => generateStoreByParams(patterns, options, id);
