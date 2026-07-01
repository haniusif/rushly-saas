import * as React from 'react';
import { createPortal } from 'react-dom';
import { Sparkles, X } from 'lucide-react';

/**
 * First-login welcome modal — offers the user a tour of the first auto-start
 * tour applicable to their role. Non-blocking: users can dismiss it.
 */
export default function WelcomeModal({ open, tour, onStart, onDismiss, t = {} }) {
    if (!open || !tour) return null;
    return createPortal(
        <div className="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/60 px-4" role="dialog" aria-modal="true">
            <div className="w-full max-w-md rounded-xl border border-border bg-card shadow-2xl">
                <div className="flex items-start justify-between gap-2 border-b border-border px-5 py-4">
                    <div className="flex items-center gap-2">
                        <span className="grid h-9 w-9 place-items-center rounded-full bg-primary/10 text-primary">
                            <Sparkles className="h-5 w-5" />
                        </span>
                        <div>
                            <div className="text-base font-semibold">{t.welcome_title || 'Welcome!'}</div>
                            <div className="text-xs text-muted-foreground">{t.welcome_subtitle || 'Let us show you around.'}</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={onDismiss}
                        className="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                        aria-label={t.dismiss || 'Dismiss'}
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
                <div className="px-5 py-4">
                    <div className="text-sm font-medium">{tour.title}</div>
                    {tour.description && (
                        <p className="mt-1 text-sm text-muted-foreground m-0">{tour.description}</p>
                    )}
                    <div className="mt-2 text-xs text-muted-foreground">
                        {(tour.steps?.length || 0)} {t.steps_count || 'steps'}
                    </div>
                </div>
                <div className="flex items-center justify-end gap-2 border-t border-border px-5 py-3">
                    <button
                        type="button"
                        onClick={onDismiss}
                        className="rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted"
                    >
                        {t.maybe_later || 'Maybe later'}
                    </button>
                    <button
                        type="button"
                        onClick={onStart}
                        className="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                    >
                        {t.start_tour || 'Take a tour'}
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
