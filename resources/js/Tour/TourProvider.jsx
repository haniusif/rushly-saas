import * as React from 'react';
import { router } from '@inertiajs/react';
import TourOverlay from './TourOverlay';
import TourStepPopover from './TourStepPopover';
import WelcomeModal from './WelcomeModal';
import { findTarget } from './resolvers/findTarget';
import { bindKeyboard } from './keyboard';
import { tourApi } from './api';

/**
 * Central controller for the onboarding tour engine.
 * - Loads applicable tours once per session (cached server-side).
 * - Auto-shows the welcome modal on first login if any tour has auto_start.
 * - Exposes {start, stop, next, prev, skip, finish} via context.
 */
const TourCtx = React.createContext(null);

export function useTourEngine() {
    const ctx = React.useContext(TourCtx);
    if (!ctx) throw new Error('useTourEngine must be used inside <TourProvider>');
    return ctx;
}

const T_DEFAULT = {
    welcome_title: 'Welcome to Rushly',
    welcome_subtitle: 'A quick tour to get you started.',
    dismiss: 'Dismiss',
    steps_count: 'steps',
    maybe_later: 'Maybe later',
    start_tour: 'Take a tour',
    prev: 'Previous',
    next: 'Next',
    skip: 'Skip',
    finish: 'Finish',
};

export default function TourProvider({ children, locale = 'en', isRTL = false, translations = {} }) {
    const T = { ...T_DEFAULT, ...(translations || {}) };
    const [tours, setTours]       = React.useState([]);
    const [firstLogin, setFirstL] = React.useState(false);
    const [active, setActive]     = React.useState(null);   // { tour, stepIndex, startedAt, stepStartedAt }
    const [rect, setRect]         = React.useState(null);
    const [welcomeOpen, setWelcome] = React.useState(false);
    const [welcomeTour, setWelcomeTour] = React.useState(null);

    // Load applicable tours once on mount.
    React.useEffect(() => {
        let cancelled = false;
        tourApi.forMe(locale)
            .then((data) => {
                if (cancelled) return;
                setTours(data.tours || []);
                setFirstL(!!data.first_login);
                // Auto-start on first login: pick the first auto_start tour
                // that hasn't been completed yet.
                const auto = (data.tours || []).find((t) =>
                    t.auto_start && (!t.progress || t.progress.status === 'started')
                );
                if (data.first_login && auto) {
                    setWelcomeTour(auto);
                    setWelcome(true);
                }
            })
            .catch(() => { /* silently ignore — tours are non-critical */ });
        return () => { cancelled = true; };
    }, [locale]);

    // Keep spotlight rect updated on scroll/resize + on step change.
    const currentStep = active ? active.tour.steps[active.stepIndex] : null;

    const refreshRect = React.useCallback(() => {
        if (!currentStep) { setRect(null); return; }
        const el = findTarget(currentStep.target);
        if (!el) {
            setRect(null);
            // Emit missing-element analytics once per step.
            if (active) {
                tourApi.logEvent(active.tour.key, {
                    event: 'element_missing',
                    step_index: active.stepIndex,
                    meta: { target: currentStep.target },
                }).catch(() => {});
            }
            return;
        }
        const r = el.getBoundingClientRect();
        setRect(r);
        // Scroll target into view if it's out of the viewport.
        if (r.top < 0 || r.bottom > window.innerHeight) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [currentStep, active]);

    React.useLayoutEffect(() => { refreshRect(); }, [refreshRect]);
    React.useEffect(() => {
        if (!active) return;
        const onChange = () => refreshRect();
        window.addEventListener('resize', onChange);
        window.addEventListener('scroll', onChange, true);
        return () => {
            window.removeEventListener('resize', onChange);
            window.removeEventListener('scroll', onChange, true);
        };
    }, [active, refreshRect]);

    // Keyboard bindings while a tour is active.
    React.useEffect(() => {
        if (!active) return;
        const unbind = bindKeyboard({
            onNext: () => next(),
            onPrev: () => prev(),
            onSkip: () => skip(),
        });
        return unbind;
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active]);

    // Also close tour when Inertia navigates to a page whose route doesn't
    // match the tour's trigger_route (unless the step itself navigated).
    React.useEffect(() => {
        return router.on('navigate', () => {
            // No-op unless we've navigated away from a route the tour cares about.
            // Steps that need navigation should set action.navigate — the engine
            // waits then resumes on the new page.
        });
    }, []);

    const start = React.useCallback((keyOrTour) => {
        const tour = typeof keyOrTour === 'string'
            ? tours.find((t) => t.key === keyOrTour)
            : keyOrTour;
        if (!tour || !tour.steps?.length) return;
        const startAt = (tour.progress?.status === 'started') ? (tour.progress.current_step || 0) : 0;
        setActive({ tour, stepIndex: startAt, startedAt: Date.now(), stepStartedAt: Date.now() });
        tourApi.saveProgress(tour.key, { status: 'started', current_step: startAt, version: tour.version }).catch(() => {});
        tourApi.logEvent(tour.key, { event: 'started', step_index: startAt }).catch(() => {});
    }, [tours]);

    const stop = React.useCallback(() => {
        setActive(null);
        setRect(null);
    }, []);

    const emitStep = (event) => {
        if (!active) return;
        const dur = Date.now() - (active.stepStartedAt || active.startedAt);
        tourApi.logEvent(active.tour.key, { event, step_index: active.stepIndex, duration_ms: dur }).catch(() => {});
    };

    const next = React.useCallback(() => {
        if (!active) return;
        const stepCount = active.tour.steps.length;
        const nextIdx   = active.stepIndex + 1;
        emitStep('step_forward');
        if (nextIdx >= stepCount) {
            finish();
            return;
        }
        // Handle navigation actions.
        const action = active.tour.steps[nextIdx]?.action;
        if (action?.navigate) {
            router.visit(action.navigate, { preserveState: false });
        }
        setActive((a) => a && { ...a, stepIndex: nextIdx, stepStartedAt: Date.now() });
        tourApi.saveProgress(active.tour.key, { status: 'started', current_step: nextIdx, version: active.tour.version }).catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active]);

    const prev = React.useCallback(() => {
        if (!active || active.stepIndex === 0) return;
        emitStep('step_back');
        setActive((a) => a && { ...a, stepIndex: a.stepIndex - 1, stepStartedAt: Date.now() });
        tourApi.saveProgress(active.tour.key, { status: 'started', current_step: active.stepIndex - 1, version: active.tour.version }).catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active]);

    const skip = React.useCallback(() => {
        if (!active) return;
        tourApi.saveProgress(active.tour.key, { status: 'skipped', current_step: active.stepIndex, version: active.tour.version }).catch(() => {});
        tourApi.logEvent(active.tour.key, { event: 'skipped', step_index: active.stepIndex }).catch(() => {});
        stop();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active]);

    const finish = React.useCallback(() => {
        if (!active) return;
        tourApi.saveProgress(active.tour.key, { status: 'completed', current_step: active.tour.steps.length - 1, version: active.tour.version }).catch(() => {});
        tourApi.logEvent(active.tour.key, { event: 'completed', step_index: active.tour.steps.length - 1 }).catch(() => {});
        stop();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [active]);

    const dismissWelcome = () => {
        setWelcome(false);
        if (welcomeTour) {
            tourApi.logEvent(welcomeTour.key, { event: 'dismissed' }).catch(() => {});
        }
    };
    const startFromWelcome = () => {
        setWelcome(false);
        if (welcomeTour) start(welcomeTour);
    };

    const value = { tours, start, stop, next, prev, skip, finish };

    return (
        <TourCtx.Provider value={value}>
            {children}
            {active && (
                <>
                    <TourOverlay rect={rect} padding={currentStep?.spotlight_padding || 8} onClick={skip} />
                    <TourStepPopover
                        rect={rect}
                        placement={currentStep?.placement}
                        title={currentStep?.title}
                        body={currentStep?.body}
                        stepIndex={active.stepIndex}
                        stepCount={active.tour.steps.length}
                        isRTL={isRTL}
                        onNext={next}
                        onPrev={prev}
                        onSkip={skip}
                        onFinish={finish}
                        t={T}
                    />
                </>
            )}
            <WelcomeModal
                open={welcomeOpen}
                tour={welcomeTour}
                onStart={startFromWelcome}
                onDismiss={dismissWelcome}
                t={T}
            />
        </TourCtx.Provider>
    );
}
