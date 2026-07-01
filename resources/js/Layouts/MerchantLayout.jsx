import * as React from 'react';
import { Link, usePage, router, Head } from '@inertiajs/react';
import {
    LayoutDashboard, Package, Wallet, FileText, MessageCircle, Store,
    Banknote, Settings, BarChart3, Receipt, Menu, X, Sun, Moon,
    LogOut, ChevronDown, Bell, Search, Globe, Check, User,
    BookOpen,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/Button';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent,
    DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator,
} from '@/Components/ui/DropdownMenu';
import { Input } from '@/Components/ui/Input';
import { useT, useLocale, SUPPORTED_LOCALES } from '@/lib/i18n';
import TourLauncher from '@/Tour/TourLauncher';

const NAV = [
    { group: 'nav_overview', items: [
        { tKey: 'nav_dashboard',      icon: LayoutDashboard, route: 'dashboard.index',          match: ['dashboard'] },
        { tKey: 'nav_knowledge_base', icon: BookOpen,        route: 'merchant-panel.kb.index',  match: ['merchant/knowledge-base'] },
    ]},
    { group: 'nav_operations', items: [
        { tKey: 'nav_parcels',      icon: Package,       route: 'merchant-panel.parcel.index',      match: ['merchant/parcel'] },
        { tKey: 'nav_shops',        icon: Store,         route: 'merchant-panel.shops.index',       match: ['merchant/shops'] },
        { tKey: 'nav_support',      icon: MessageCircle, route: 'merchant-panel.support.index',     match: ['merchant/support'] },
    ]},
    { group: 'nav_finance', items: [
        { tKey: 'nav_statements',       icon: FileText, route: 'merchant.accounts.statements.index',          match: ['merchant/accounts/statements'] },
        { tKey: 'nav_account_tx',       icon: Wallet,   route: 'merchant.accounts.account-transaction.index', match: ['merchant/accounts/account-transaction'] },
        { tKey: 'nav_invoices',         icon: Receipt,  route: 'merchant.panel.invoice.index',                match: ['merchant/invoice'] },
        { tKey: 'nav_payment_received', icon: Banknote, route: 'online.payment.received',                     match: ['merchant/payment/received'] },
    ]},
    { group: 'nav_reports', items: [
        { tKey: 'nav_total_summary',  icon: BarChart3, route: 'merchant.total.summery',        match: ['merchant/reports/total-summery'] },
        { tKey: 'nav_parcel_reports', icon: BarChart3, route: 'merchant-panel.parcel.reports', match: ['merchant/reports/parcel-reports'] },
    ]},
    { group: 'nav_settings', items: [
        { tKey: 'nav_cod_charges',      icon: Settings, route: 'merchant.cod-charges.index',      match: ['merchant/settings/cod-charges'] },
        { tKey: 'nav_delivery_charges', icon: Settings, route: 'merchant.delivery-charges.index', match: ['merchant/settings/delivery-charges'] },
    ]},
];

function safeRoute(name, params) {
    try {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(name, params);
        }
    } catch (e) { /* route not in Ziggy */ }
    return '#';
}

function isActive(currentUrl, item) {
    const cur = (currentUrl || '').split('?')[0];

    // Exact-URL match keeps section roots highlighted (e.g. /merchant/dashboard).
    const itemUrl = safeRoute(item.route);
    if (itemUrl && itemUrl !== '#') {
        try {
            const itemPath = new URL(itemUrl, 'http://x').pathname;
            if (cur === itemPath) return true;
        } catch (_) { /* ignore malformed URLs */ }
    }

    return (item.match || []).some((m) => cur.startsWith('/' + m));
}

function useDarkMode() {
    const [dark, setDark] = React.useState(() =>
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark'),
    );
    const toggle = () => {
        const next = !dark;
        setDark(next);
        if (typeof document !== 'undefined') {
            document.documentElement.classList.toggle('dark', next);
            localStorage.setItem('merchant-theme', next ? 'dark' : 'light');
        }
    };
    return [dark, toggle];
}

// Resolve all theme tokens from the brand prop with fallbacks. Returns null if no override is set.
function resolveTheme(brand) {
    if (!brand) return null;
    const primary = brand.primary_color || null;
    const textOn = brand.text_color || '#ffffff';
    const style = brand.sidebar_style; // 'dark' | 'light' | 'brand' | null
    const sidebarBg = brand.sidebar_color
        || (style === 'light' ? '#ffffff' : style === 'brand' ? primary : style === 'dark' ? '#0f172a' : null);
    const sidebarFg = brand.sidebar_text_color
        || (style === 'light' ? '#0f172a' : style === 'brand' ? textOn : style === 'dark' ? '#f1f5f9' : null);
    return {
        primary,
        textOn,
        sidebarBg,
        sidebarFg,
        topbarBg: brand.topbar_color || primary || null,
        topbarFg: brand.topbar_text_color || textOn,
        accent: brand.accent_color || primary || null,
        radius: brand.border_radius || null,
        density: brand.density || null,
        font: brand.font_family || null,
    };
}

const RADIUS_VAR = { sharp: '2px', default: '8px', rounded: '14px' };
const FONT_FAMILY = {
    inter: '"Inter", sans-serif',
    cairo: '"Cairo", "Tajawal", sans-serif',
    tajawal: '"Tajawal", sans-serif',
    roboto: '"Roboto", sans-serif',
    system: 'ui-sans-serif, system-ui, -apple-system, sans-serif',
};

function Sidebar({ open, onClose, currentUrl, brand, theme }) {
    const t = useT();
    const brandName = brand?.name || 'Merchant';
    const brandLogo = brand?.light_logo || brand?.logo || null;
    const initial = brandName.charAt(0).toUpperCase();
    const sidebarStyle = theme?.sidebarBg
        ? { backgroundColor: theme.sidebarBg, color: theme.sidebarFg || '#ffffff' }
        : undefined;
    const dense = theme?.density === 'dense';
    return (
        <>
            <div
                className={cn(
                    'fixed inset-0 z-40 bg-black/40 backdrop-blur-sm md:hidden',
                    open ? 'block' : 'hidden',
                )}
                onClick={onClose}
            />
            <aside
                style={sidebarStyle}
                className={cn(
                    'fixed inset-y-0 start-0 z-50 w-64 transition-transform',
                    'border-e border-sidebar-border',
                    theme?.sidebarBg ? '' : 'bg-sidebar text-sidebar-foreground',
                    open
                        ? 'translate-x-0'
                        : 'ltr:-translate-x-full rtl:translate-x-full',
                    'md:!translate-x-0',
                )}
            >
                <div className="flex h-16 items-center justify-between px-6 border-b border-sidebar-border">
                    <Link href="/dashboard" className="flex items-center gap-2 font-semibold text-base min-w-0">
                        {brandLogo ? (
                            <img
                                src={brandLogo}
                                alt={brandName}
                                className="h-8 w-8 rounded-lg object-contain shrink-0 bg-white/5"
                            />
                        ) : (
                            <span className="grid h-8 w-8 place-items-center rounded-lg bg-primary text-primary-foreground shrink-0">{initial}</span>
                        )}
                        <span className="truncate">{brandName}</span>
                    </Link>
                    <Button variant="ghost" size="icon" className="md:hidden hover:bg-white/10" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
                <nav className={cn('px-3 overflow-y-auto h-[calc(100vh-4rem)]', dense ? 'py-2 space-y-3' : 'py-4 space-y-6')}>
                    {NAV.map((section) => (
                        <div key={section.group}>
                            <div className={cn('px-3 mb-2 text-xs font-semibold uppercase tracking-wider opacity-50', !theme?.sidebarBg && 'text-sidebar-foreground')}>
                                {t(section.group)}
                            </div>
                            <ul className={cn(dense ? 'space-y-0.5' : 'space-y-1')}>
                                {section.items.map((item) => {
                                    const active = isActive(currentUrl, item);
                                    const Icon = item.icon;
                                    const activeStyle = active && theme?.primary
                                        ? { backgroundColor: theme.primary, color: theme.textOn }
                                        : undefined;
                                    return (
                                        <li key={item.tKey}>
                                            <Link
                                                href={safeRoute(item.route)}
                                                style={activeStyle}
                                                data-tour={`sidebar-${item.tKey}`}
                                                className={cn(
                                                    'group flex items-center gap-3 rounded-md text-sm font-medium transition-colors',
                                                    dense ? 'px-3 py-1.5' : 'px-3 py-2',
                                                    active
                                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                                        : theme?.sidebarBg ? 'opacity-80 hover:opacity-100 hover:bg-white/10' : 'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground',
                                                )}
                                            >
                                                <Icon className="h-4 w-4 shrink-0" />
                                                <span>{t(item.tKey)}</span>
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>
            </aside>
        </>
    );
}

function LanguageMenu({ accent }) {
    const locale = useLocale();
    const t = useT();
    const current = SUPPORTED_LOCALES.find((l) => l.code === locale) || SUPPORTED_LOCALES[0];
    const checkStyle = accent ? { color: accent } : undefined;

    const switchTo = (code) => {
        if (code === locale) return;
        // Full reload so the server-rendered html lang/dir + session locale stay aligned.
        window.location.href = safeRoute('setlocalization', { language: code });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={t('language')}>
                    <Globe className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                <DropdownMenuLabel className="text-xs">{t('language')}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {SUPPORTED_LOCALES.map((l) => (
                    <DropdownMenuItem
                        key={l.code}
                        onClick={() => switchTo(l.code)}
                        className="flex items-center justify-between gap-2"
                    >
                        <span>{l.native}</span>
                        {l.code === current.code && <Check style={checkStyle} className={cn('h-4 w-4', !accent && 'text-primary')} />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Topbar({ onSidebarOpen, user, brand, theme }) {
    const [dark, toggleDark] = useDarkMode();
    const t = useT();
    const branded = !!theme?.topbarBg;
    const brandStyle = branded
        ? { backgroundColor: theme.topbarBg, color: theme.topbarFg, borderBottomColor: 'rgba(0,0,0,0.12)' }
        : undefined;
    const tintBg = branded ? { backgroundColor: 'rgba(255,255,255,0.18)' } : undefined;
    const mutedFg = branded ? { color: 'currentColor', opacity: 0.75 } : undefined;
    const dense = theme?.density === 'dense';
    return (
        <header
            style={brandStyle}
            className={cn(
                'sticky top-0 z-30 backdrop-blur border-b border-border flex items-center gap-3 px-4 md:px-6',
                dense ? 'h-12' : 'h-16',
                branded ? 'text-current' : 'bg-background/80',
            )}
        >
            <Button variant="ghost" size="icon" className="md:hidden" onClick={onSidebarOpen}>
                <Menu className="h-5 w-5" />
            </Button>

            <div className="hidden md:flex items-center gap-2 max-w-md flex-1">
                <div className="relative w-full">
                    <Search style={mutedFg} className={cn('absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4', !branded && 'text-muted-foreground')} />
                    <Input
                        placeholder={t('search_placeholder')}
                        style={tintBg}
                        className={cn('ps-9 h-9 border-0', !branded && 'bg-muted/40', branded && 'placeholder:text-current placeholder:opacity-60 text-current')}
                    />
                </div>
            </div>

            <div className="ms-auto flex items-center gap-2">
                <TourLauncher label={t('take_a_tour')} />
                <LanguageMenu accent={theme?.accent} />
                <Button variant="ghost" size="icon" onClick={toggleDark} aria-label={t('toggle_theme')}>
                    {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                </Button>
                <Button variant="ghost" size="icon" aria-label={t('notifications')} data-tour="topbar-notifications">
                    <Bell className="h-4 w-4" />
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="gap-2 h-9 px-2">
                            <span
                                style={tintBg}
                                className={cn(
                                    'grid h-8 w-8 place-items-center rounded-full text-sm font-semibold',
                                    branded ? 'text-current' : 'bg-primary/10 text-primary',
                                )}
                            >
                                {(user?.name || 'M').charAt(0).toUpperCase()}
                            </span>
                            <span className="hidden sm:inline text-sm">{user?.name || 'Merchant'}</span>
                            <ChevronDown style={mutedFg} className={cn('h-4 w-4', !branded && 'text-muted-foreground')} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>
                            <div className="font-medium">{user?.name || 'Merchant'}</div>
                            <div className="text-xs text-muted-foreground font-normal">{user?.email}</div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onClick={() => router.get(safeRoute('merchant-profile.index', { id: user?.id }))}>
                            <User className="h-4 w-4 me-2" /> {t('profile')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={(e) => {
                                e.preventDefault();
                                if (typeof window !== 'undefined' && !window.confirm(t('logout_confirm'))) return;
                                router.post(safeRoute('logout'));
                            }}
                            className="text-destructive focus:text-destructive"
                        >
                            <LogOut className="h-4 w-4 me-2" /> {t('logout')}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}

function ImpersonationBanner({ impersonator, user }) {
    if (!impersonator) return null;
    const stop = () => {
        if (typeof window !== 'undefined' && !window.confirm(`Return to ${impersonator.name}'s session?`)) return;
        router.post(safeRoute('merchant.impersonate.stop'));
    };
    return (
        <div className="sticky top-0 z-50 bg-amber-500 text-amber-950 text-sm">
            <div className="flex items-center justify-between gap-3 px-4 md:px-6 py-1.5">
                <span className="flex items-center gap-2">
                    <span aria-hidden>👁</span>
                    <strong>{impersonator.name}</strong> is viewing as <strong>{user?.name || 'this merchant'}</strong>
                </span>
                <button onClick={stop} className="rounded px-3 py-1 text-xs font-semibold bg-amber-950 text-amber-50 hover:bg-amber-900 transition-colors">
                    Return to admin
                </button>
            </div>
        </div>
    );
}

export default function MerchantLayout({ title, children, breadcrumbs }) {
    const [sidebarOpen, setSidebarOpen] = React.useState(false);
    const { url, props } = usePage();
    const user = props?.auth?.user;
    const brand = props?.brand;
    const impersonator = props?.impersonator;
    const theme = resolveTheme(brand);

    const rootStyle = {};
    if (theme?.font && FONT_FAMILY[theme.font]) rootStyle.fontFamily = FONT_FAMILY[theme.font];
    if (theme?.radius && RADIUS_VAR[theme.radius]) rootStyle['--radius'] = RADIUS_VAR[theme.radius];

    const brandName = brand?.name || 'Merchant';
    const docTitle = title ? `${title} · ${brandName}` : brandName;
    const brandDesc = brandName + ' — Merchant portal';
    const brandPrimary = brand?.primary_color || '#a21f5c';
    const ogImage = brand?.logo || brand?.light_logo || null;

    return (
        <div className="min-h-screen bg-background" style={rootStyle}>
            <Head title={docTitle}>
                <meta name="application-name" content={brandName} head-key="application-name" />
                <meta name="apple-mobile-web-app-title" content={brandName} head-key="apple-mobile-web-app-title" />
                <meta name="description" content={brandDesc} head-key="description" />
                <meta name="theme-color" content={brandPrimary} head-key="theme-color" />

                {brand?.favicon && <link rel="icon" type="image/png" href={brand.favicon} head-key="icon" />}
                {brand?.favicon && <link rel="shortcut icon" type="image/png" href={brand.favicon} head-key="shortcut-icon" />}
                {brand?.favicon && <link rel="apple-touch-icon" href={brand.favicon} head-key="apple-touch-icon" />}

                <meta property="og:title" content={brandName} head-key="og:title" />
                <meta property="og:description" content={brandDesc} head-key="og:description" />
                <meta property="og:site_name" content={brandName} head-key="og:site_name" />
                {ogImage && <meta property="og:image" content={ogImage} head-key="og:image" />}

                <meta name="twitter:title" content={brandName} head-key="twitter:title" />
                <meta name="twitter:description" content={brandDesc} head-key="twitter:description" />
                {ogImage && <meta name="twitter:image" content={ogImage} head-key="twitter:image" />}
            </Head>
            <ImpersonationBanner impersonator={impersonator} user={user} />
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} currentUrl={url} brand={brand} theme={theme} />
            <div className="md:ps-64">
                <Topbar onSidebarOpen={() => setSidebarOpen(true)} user={user} brand={brand} theme={theme} />
                <main className="p-4 md:p-8">
                    {(title || breadcrumbs) && (
                        <div className="mb-6">
                            {breadcrumbs && (
                                <nav className="text-xs text-muted-foreground mb-2 flex gap-1.5">
                                    {breadcrumbs.map((b, i) => (
                                        <React.Fragment key={i}>
                                            {i > 0 && <span>/</span>}
                                            <span className={i === breadcrumbs.length - 1 ? 'text-foreground' : ''}>{b}</span>
                                        </React.Fragment>
                                    ))}
                                </nav>
                            )}
                            {title && <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
