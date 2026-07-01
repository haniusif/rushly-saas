import * as React from 'react';
import { createPortal } from 'react-dom';

/**
 * Full-viewport dark overlay with an animated spotlight cut-out around
 * the target element. Uses an SVG mask (single element, cheap re-renders
 * on scroll/resize). If `rect` is null, the whole screen is dimmed
 * (used during transitions / first-mount).
 */
export default function TourOverlay({ rect, padding = 8, onClick }) {
    const [size, setSize] = React.useState(() => vpSize());

    React.useEffect(() => {
        const onResize = () => setSize(vpSize());
        window.addEventListener('resize', onResize);
        return () => window.removeEventListener('resize', onResize);
    }, []);

    const w = size.w;
    const h = size.h;

    // Compute the spotlight rect (padded around the target).
    const hole = rect ? {
        x: Math.max(0, rect.left - padding),
        y: Math.max(0, rect.top - padding),
        w: rect.width + padding * 2,
        h: rect.height + padding * 2,
        r: 8,
    } : null;

    return createPortal(
        <div
            className="fixed inset-0 z-[9998] pointer-events-auto"
            onClick={onClick}
            aria-hidden="true"
            style={{ transition: 'opacity 150ms ease-out' }}
        >
            <svg width={w} height={h} className="absolute inset-0">
                <defs>
                    <mask id="tour-mask">
                        {/* full white = show overlay */}
                        <rect x="0" y="0" width={w} height={h} fill="white" />
                        {/* black = cut out (spotlight) */}
                        {hole && (
                            <rect
                                x={hole.x} y={hole.y}
                                width={hole.w} height={hole.h}
                                rx={hole.r} ry={hole.r}
                                fill="black"
                            />
                        )}
                    </mask>
                </defs>
                <rect x="0" y="0" width={w} height={h} fill="rgba(15, 23, 42, 0.72)" mask="url(#tour-mask)" />
                {/* Soft glow ring around the spotlight */}
                {hole && (
                    <rect
                        x={hole.x - 2} y={hole.y - 2}
                        width={hole.w + 4} height={hole.h + 4}
                        rx={hole.r + 2} ry={hole.r + 2}
                        fill="none"
                        stroke="rgba(59, 130, 246, 0.55)"
                        strokeWidth="2"
                        className="animate-pulse"
                    />
                )}
            </svg>
        </div>,
        document.body,
    );
}

function vpSize() {
    return typeof window === 'undefined'
        ? { w: 1280, h: 720 }
        : { w: window.innerWidth, h: window.innerHeight };
}
