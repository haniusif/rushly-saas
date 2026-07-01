/**
 * Resolve a step's `target` descriptor to a DOM element.
 * Returns null when nothing matches — caller decides whether to skip
 * the step or emit an `element_missing` analytics event.
 *
 * Descriptor shapes:
 *   { type: 'data-tour', value: 'sidebar-parcels' }
 *   { type: 'selector',  value: '#dashboard-kpis' }
 *   { type: 'route-name', value: 'merchant-panel.parcel.index' } — used for navigation actions, not spotlighting
 */
export function findTarget(target) {
    if (!target || !target.type || !target.value) return null;
    try {
        if (target.type === 'data-tour') {
            return document.querySelector(`[data-tour="${cssEscape(target.value)}"]`);
        }
        if (target.type === 'selector') {
            return document.querySelector(target.value);
        }
        if (target.type === 'route-name') {
            // Non-spotlightable — used by action.navigate only.
            return null;
        }
    } catch {
        return null;
    }
    return null;
}

function cssEscape(v) {
    if (typeof window !== 'undefined' && window.CSS && CSS.escape) return CSS.escape(v);
    return String(v).replace(/([^a-zA-Z0-9_-])/g, '\\$1');
}
