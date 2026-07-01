/**
 * Bindings applied while a tour is active. Attach on tour start,
 * detach on end. All handlers no-op when the target isn't the document
 * body (so users typing in inputs aren't hijacked).
 */
export function bindKeyboard({ onNext, onPrev, onSkip }) {
    const handler = (e) => {
        // Ignore keys while user is typing.
        const tag = (e.target?.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable) return;

        if (e.key === 'ArrowRight' || e.key === 'Enter') { e.preventDefault(); onNext(); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); onPrev(); }
        else if (e.key === 'Escape') { e.preventDefault(); onSkip(); }
    };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
}
