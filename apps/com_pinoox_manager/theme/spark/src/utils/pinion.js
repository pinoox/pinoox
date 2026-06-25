import {http} from "@global";
import formDataHelper from "@utils/helpers/formDataHelper.js";

const BASE_URL = '/app/pinion';
const FALLBACK_THRESHOLD = 8 * 1024 * 1024;
const FALLBACK_CHUNK = 5 * 1024 * 1024;
const FALLBACK_PARALLEL = 2;
const STORAGE_KEY = 'pinion_sessions';

const unwrapData = (response) => response?.data?.data ?? response?.data ?? null;

let cachedLimits = null;
let limitsPromise = null;

export async function getPinionLimits({force = false} = {}) {
    if (cachedLimits && !force) {
        return cachedLimits;
    }

    if (limitsPromise && !force) {
        return limitsPromise;
    }

    limitsPromise = http.get(`${BASE_URL}/limits`, {alert: false})
        .then((response) => {
            cachedLimits = unwrapData(response) ?? {};
            return cachedLimits;
        })
        .catch(() => {
            cachedLimits = {
                pinion_threshold: FALLBACK_THRESHOLD,
                chunk_size: FALLBACK_CHUNK,
                parallel: FALLBACK_PARALLEL,
                direct_upload_enabled: true,
            };

            return cachedLimits;
        })
        .finally(() => {
            limitsPromise = null;
        });

    return limitsPromise;
}

async function sha256Hex(blob) {
    const buffer = await blob.arrayBuffer();
    const hash = await crypto.subtle.digest('SHA-256', buffer);
    return Array.from(new Uint8Array(hash)).map((b) => b.toString(16).padStart(2, '0')).join('');
}

export function buildFingerprint(file) {
    return [file.name, file.size, file.lastModified, file.type || ''].join(':');
}

function readStoredSessions() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch {
        return {};
    }
}

function storeSession(fingerprint, session) {
    const map = readStoredSessions();
    map[fingerprint] = {
        upload_id: session.id,
        missing_indexes: session.missing_indexes ?? [],
        updated_at: Date.now(),
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
}

function clearStoredSession(fingerprint) {
    const map = readStoredSessions();
    delete map[fingerprint];
    localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
}

export async function uploadWithPinion(file, {
    onProgress,
    chunkSize,
    signal,
    parallel,
} = {}) {
    const limits = await getPinionLimits();
    const fingerprint = buildFingerprint(file);
    const resolvedChunk = chunkSize ?? limits.chunk_size ?? FALLBACK_CHUNK;
    const resolvedParallel = parallel ?? limits.parallel ?? FALLBACK_PARALLEL;

    const initResponse = await http.post(`${BASE_URL}/init`, {
        filename: file.name,
        size: file.size,
        mime: file.type || null,
        chunk_size: resolvedChunk,
        fingerprint,
    }, {alert: false, signal});

    const initData = unwrapData(initResponse);
    const session = initData?.session ?? initData;
    if (!session?.id) {
        throw new Error('pinion_init_failed');
    }

    storeSession(fingerprint, session);

    const size = session.chunk_size || resolvedChunk;
    const indexes = (session.missing_indexes?.length ? session.missing_indexes : Array.from({
        length: session.total_chunks || Math.ceil(file.size / size),
    }, (_, i) => i));

    let uploadedBytes = session.bytes_received || 0;
    const queue = [...indexes];
    const workers = Array.from({length: Math.max(1, resolvedParallel)}, async () => {
        while (queue.length) {
            const index = queue.shift();
            const start = index * size;
            const end = Math.min(start + size, file.size);
            const blob = file.slice(start, end);
            const chunkHash = await sha256Hex(blob);
            const formData = new FormData();
            formData.append('upload_id', session.id);
            formData.append('index', String(index));
            formData.append('chunk_hash', chunkHash);
            formData.append('chunk', blob, `${file.name}.part`);

            await http.post(`${BASE_URL}/upload`, formData, {
                alert: false,
                signal,
            });

            uploadedBytes += blob.size;
            if (onProgress) {
                onProgress(Math.min(100, Math.round((uploadedBytes / file.size) * 100)));
            }
        }
    });

    await Promise.all(workers);

    const completeResponse = await http.post(`${BASE_URL}/complete`, {
        upload_id: session.id,
    }, {alert: false, signal});

    clearStoredSession(fingerprint);

    return unwrapData(completeResponse);
}

export async function shouldUsePinion(file, threshold) {
    if (!(file instanceof File)) {
        return false;
    }

    const limits = await getPinionLimits();
    const resolvedThreshold = threshold ?? limits.pinion_threshold ?? FALLBACK_THRESHOLD;

    if (limits.direct_upload_enabled === false) {
        return true;
    }

    if (limits.max_file_size > 0 && file.size > limits.max_file_size) {
        return false;
    }

    return file.size > resolvedThreshold;
}

export function resolveUploadedFilename(result, fallbackFile) {
    if (!result) {
        return fallbackFile?.name ?? null;
    }

    const session = result.session ?? result;

    if (result.filename) {
        return result.filename;
    }

    if (session?.filename) {
        return session.filename;
    }

    const pathValue = result.path ?? session?.path ?? '';

    if (pathValue) {
        const parts = String(pathValue).split(/[/\\]/);

        return parts[parts.length - 1] || fallbackFile?.name || null;
    }

    return fallbackFile?.name ?? null;
}

export async function uploadPackageFile(file, options = {}) {
    if (await shouldUsePinion(file, options.threshold)) {
        return uploadWithPinion(file, options);
    }

    const formData = formDataHelper.fromObject({files: [file]});
    const response = await http.postForm('/app/filesUpload', formData, {alert: false, signal: options.signal});
    return unwrapData(response);
}

export const pinionAPI = {
    limits: (config = {}) => http.get(`${BASE_URL}/limits`, {alert: false, ...config}),
    init: (payload) => http.post(`${BASE_URL}/init`, payload, {alert: false}),
    upload: (formData, config = {}) => http.post(`${BASE_URL}/upload`, formData, {
        alert: false,
        ...config,
    }),
    complete: (uploadId) => http.post(`${BASE_URL}/complete`, {upload_id: uploadId}, {alert: false}),
    status: (uploadId) => http.get(`${BASE_URL}/status/${uploadId}`, {alert: false}),
    abort: (uploadId) => http.post(`${BASE_URL}/abort/${uploadId}`, {}, {alert: false}),
};
