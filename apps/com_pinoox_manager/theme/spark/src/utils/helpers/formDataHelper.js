export default {
    // Convert an object to FormData
    fromObject(obj) {
        const formData = new FormData();
        for (const [key, value] of Object.entries(obj)) {
            this.appendValue(formData, key, value);
        }
        return formData;
    },

    // Append a value to the FormData object based on its type
    appendValue(formData, key, value) {
        if (value === null) {
            formData.append(key, '');
        } else if (typeof value === 'boolean') {
            formData.append(key, value ? 1 : 0);
        } else if (value instanceof File) {
            formData.append(key, value);
        } else if (value && typeof value === 'object') {
            if (Array.isArray(value)) {
                this.appendArray(formData, key, value);
            } else {
                this.appendObject(formData, key, value);
            }
        } else {
            formData.append(key, value);
        }
    },

    // Append an array of values to the FormData object
    appendArray(formData, key, value) {
        for (let i = 0; i < value.length; i++) {
            formData.append(`${key}[]`, value[i]);
        }
    },

    // Append an object to the FormData object
    appendObject(formData, key, value) {
        for (const [subKey, subValue] of Object.entries(value)) {
            this.appendValue(formData, `${key}[${subKey}]`, subValue);
        }
    },
};