/**
 * Thin fetch wrappers for the tour engine's JSON endpoints. Uses the
 * session cookie (same as Inertia) — no bearer tokens.
 */
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function json(url, method = 'GET', body = null) {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
            ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrf() } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.status === 204 ? null : await res.json();
}

export const tourApi = {
    forMe:        (locale) => json(`/tours/for-me?locale=${encodeURIComponent(locale || '')}`),
    saveProgress: (key, payload) => json(`/tours/${encodeURIComponent(key)}/progress`, 'POST', payload),
    logEvent:     (key, payload) => json(`/tours/${encodeURIComponent(key)}/event`, 'POST', payload),
};
