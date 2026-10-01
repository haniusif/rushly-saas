import * as React from 'react';
import { usePage } from '@inertiajs/react';

/**
 * Renders the current tenant's currency mark: the stored SVG when present
 * (e.g. the 2025 SAR/AED symbols), otherwise the unicode symbol / ISO code.
 *
 * Reads the shared `currency` prop ({ code, symbol, svg }) by default; pass
 * `meta` to override (e.g. a per-row currency object with the same shape).
 */

// Make a stored SVG follow the surrounding text color (light/dark safe).
function toCurrentColor(svg) {
    if (!svg) return '';
    return svg
        .replace(/fill\s*:\s*#?[0-9a-z]+/gi, 'fill:currentColor')
        .replace(/fill\s*=\s*"(?!none)[^"]*"/gi, 'fill="currentColor"');
}

export function CurrencySymbol({ meta, className = '' }) {
    const page = usePage();
    const cur = meta || page?.props?.currency || {};
    const svg = cur.svg ? toCurrentColor(cur.svg) : '';
    const text = cur.symbol || cur.code || '';

    if (svg) {
        return (
            <span
                className={'inline-flex items-center justify-center align-middle w-[1em] h-[1em] [&_svg]:w-full [&_svg]:h-full ' + className}
                aria-label={cur.code || undefined}
                role="img"
                dangerouslySetInnerHTML={{ __html: svg }}
            />
        );
    }
    return <span className={className}>{text}</span>;
}

/**
 * Amount + currency mark, e.g. "﷼ 1,250.00". `value` is a number (or numeric
 * string). `meta` overrides the shared currency. `symbolFirst` (default true)
 * places the mark before the amount.
 */
export function Money({ value, meta, decimals = 2, symbolFirst = true, className = '', symbolClassName = 'me-0.5' }) {
    const n = Number(value || 0);
    const amount = Number.isFinite(n)
        ? n.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })
        : value;
    const sym = <CurrencySymbol meta={meta} className={symbolClassName} />;
    return (
        <span className={'tabular-nums whitespace-nowrap ' + className}>
            {symbolFirst ? <>{sym}{amount}</> : <>{amount}{' '}{sym}</>}
        </span>
    );
}

export default CurrencySymbol;
