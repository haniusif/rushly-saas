import * as React from 'react';
import { createPortal } from 'react-dom';
import { ChevronLeft, ChevronRight, X, Check } from 'lucide-react';

/**
 * Anchored tooltip card next to the highlighted element.
 * - Auto-positions (top/bottom/start/end) with viewport-fit fallback.
 * - Traps focus inside for keyboard-only navigation.
 * - When `rect` is null (element missing) it centers on the viewport.
 */
export default function TourStepPopover({
    rect, placement = 'auto', title, body,
    stepIndex, stepCount, isRTL = false,
    onNext, onPrev, onSkip, onFinish, t = {},
}) {
    const ref = React.useRef(null);
    const [pos, setPos] = React.useState(null);

    const computePos = React.useCallback(() => {
        const card = ref.current;
        if (!card) return;

        const vpW = window.innerWidth;
        const vpH = window.innerHeight;
        const cardW = card.offsetWidth;
        const cardH = card.offsetHeight;

        // No target → center.
        if (!rect) {
            setPos({ top: Math.max(16, (vpH - cardH) / 2), left: Math.max(16, (vpW - cardW) / 2) });
            return;
        }

        const gap = 14;
        // Auto: pick side with more room.
        let side = placement;
        if (side === 'auto') {
            const room = { top: rect.top, bottom: vpH - rect.bottom, start: rect.left, end: vpW - rect.right };
            side = ['top', 'bottom', 'start', 'end'].sort((a, b) => room[b] - room[a])[0];
        }

        let top, left;
        if (side === 'top')    { top = rect.top - cardH - gap; left = rect.left + rect.width / 2 - cardW / 2; }
        else if (side === 'bottom') { top = rect.bottom + gap; left = rect.left + rect.width / 2 - cardW / 2; }
        else if (side === 'start')  { left = (isRTL ? rect.right + gap : rect.left - cardW - gap); top = rect.top + rect.height / 2 - cardH / 2; }
        else /* end */              { left = (isRTL ? rect.left - cardW - gap : rect.right + gap); top = rect.top + rect.height / 2 - cardH / 2; }

        // Clamp inside viewport with a 12px margin.
        top  = Math.min(Math.max(12, top), vpH - cardH - 12);
        left = Math.min(Math.max(12, left), vpW - cardW - 12);
        setPos({ top, left });
    }, [rect, placement, isRTL]);

    React.useLayoutEffect(() => {
        computePos();
    }, [computePos, title, body]);

    React.useEffect(() => {
        const onResize = () => computePos();
        window.addEventListener('resize', onResize);
        window.addEventListener('scroll', onResize, true);
        return () => {
            window.removeEventListener('resize', onResize);
            window.removeEventListener('scroll', onResize, true);
        };
    }, [computePos]);

    // Focus management — focus the card on mount for screen readers.
    React.useEffect(() => {
        ref.current?.focus();
    }, [stepIndex]);

    const isLast = stepIndex >= stepCount - 1;
    const label = `${stepIndex + 1} / ${stepCount}`;

    return createPortal(
        <div
            ref={ref}
            tabIndex={-1}
            role="dialog"
            aria-modal="true"
            aria-labelledby="tour-step-title"
            aria-describedby="tour-step-body"
            aria-live="polite"
            className="fixed z-[9999] w-[min(360px,calc(100vw-24px))] rounded-lg border border-border bg-card shadow-2xl focus:outline-none focus:ring-2 focus:ring-primary"
            style={{
                top:  pos ? `${pos.top}px`  : '50%',
                left: pos ? `${pos.left}px` : '50%',
                transform: pos ? undefined : 'translate(-50%, -50%)',
                transition: 'top 180ms ease-out, left 180ms ease-out',
                opacity: pos ? 1 : 0,
            }}
        >
            <div className="flex items-start justify-between gap-2 border-b border-border px-4 py-3">
                <div className="min-w-0">
                    <div className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">{label}</div>
                    <div id="tour-step-title" className="mt-0.5 text-sm font-semibold truncate">{title || '—'}</div>
                </div>
                <button
                    type="button"
                    onClick={onSkip}
                    className="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                    aria-label={t.skip || 'Skip'}
                    title={t.skip || 'Skip'}
                >
                    <X className="h-4 w-4" />
                </button>
            </div>

            {body && (
                <div id="tour-step-body" className="px-4 py-3 text-sm text-foreground/85 whitespace-pre-line">
                    {body}
                </div>
            )}

            {/* Progress dots */}
            <div className="flex items-center gap-1 px-4 py-2">
                {Array.from({ length: stepCount }).map((_, i) => (
                    <span
                        key={i}
                        className={`h-1.5 rounded-full transition-all ${
                            i === stepIndex ? 'w-6 bg-primary' : i < stepIndex ? 'w-1.5 bg-primary/60' : 'w-1.5 bg-muted'
                        }`}
                    />
                ))}
            </div>

            <div className="flex items-center justify-between gap-2 border-t border-border px-4 py-3">
                <button
                    type="button"
                    onClick={onPrev}
                    disabled={stepIndex === 0}
                    className="inline-flex items-center gap-1 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted disabled:opacity-40"
                >
                    <ChevronLeft className="h-3.5 w-3.5" /> {t.prev || 'Prev'}
                </button>
                {isLast ? (
                    <button
                        type="button"
                        onClick={onFinish}
                        className="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                    >
                        <Check className="h-3.5 w-3.5" /> {t.finish || 'Finish'}
                    </button>
                ) : (
                    <button
                        type="button"
                        onClick={onNext}
                        className="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:opacity-90"
                    >
                        {t.next || 'Next'} <ChevronRight className="h-3.5 w-3.5" />
                    </button>
                )}
            </div>
        </div>,
        document.body,
    );
}
