/**
 * Normalize Pinoox API responses ({ result, message, error } or raw data).
 */
export function unwrapResponse(response) {
    const data = response?.data;
    if (data == null) return data;
    if (Object.prototype.hasOwnProperty.call(data, 'result'))
        return data.result;
    return data;
}

export function unwrapList(response) {
    const data = unwrapResponse(response);
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.apps)) return data.apps;
    if (data && Array.isArray(data.items)) return data.items;
    return [];
}
