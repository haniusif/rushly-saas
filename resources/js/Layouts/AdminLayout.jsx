import * as React from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard, Home, Package, Truck, Building2, Users, Warehouse,
    Boxes, ClipboardList, MapPin, Inbox, Send, ArrowRightLeft,
    CheckSquare, Bug, Map, MessageCircle, Newspaper, Activity,
    Settings, History, FileText, Receipt, Menu, X, Sun, Moon,
    LogOut, ChevronDown, Bell, Search, Globe, Check, User as UserIcon,
    BarChart3, AlertTriangle, Hourglass, Wand2, ListChecks, CheckCircle2, XCircle, Info,
    Wallet, ShieldAlert, DollarSign, CreditCard, BadgeDollarSign,
    UserCog, HardDrive, Briefcase, Tags, BellRing, KeyRound, Layers,
    Plug, MapPinned, Layout, ScrollText, Sliders, BookOpen,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/Button';
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent,
    DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator,
} from '@/Components/ui/DropdownMenu';
import { Input } from '@/Components/ui/Input';
import GlobalSearch from '@/Components/GlobalSearch';
import { useT, useLocale, SUPPORTED_LOCALES } from '@/lib/i18n';
import TourLauncher from '@/Tour/TourLauncher';

const NAV = [
    { group: 'menu_main', items: [
        { tKey: 'menu_summary',        icon: Home,            route: 'summary.index',       match: ['summary'] },
        { tKey: 'menu_ops_dashboard',  icon: Activity,        route: 'operations.index',    match: ['operations-dashboard'] },
        { tKey: 'menu_dashboard',      icon: LayoutDashboard, route: 'dashboard.index',     match: ['admin/dashboard', 'dashboard'] },
        { tKey: 'menu_performance',    icon: BarChart3,       route: 'performance.index',   match: ['admin/performance'] },
        { tKey: 'menu_knowledge_base', icon: BookOpen,        route: 'admin.kb.index',      match: ['admin/knowledge-base'] },
    ]},
    { group: 'menu_parcels', items: [
        { tKey: 'menu_parcel',      icon: Package,        route: 'parcel.index',       match: ['admin/parcel'] },
        { tKey: 'menu_bulk_action', icon: Wand2,          route: 'parcel.bulk_action', match: ['admin/parcel/bulk'] },
        { tKey: 'menu_ndr',         icon: AlertTriangle,  route: 'ndr.index',          match: ['admin/ndr'] },
        { tKey: 'menu_abnormal',    icon: Hourglass,      route: 'abnormal.index',     match: ['admin/abnormal'] },
    ]},
    { group: 'menu_wms', items: [
        { tKey: 'menu_wms_dashboard', icon: BarChart3,       route: 'wms.dashboard',          match: ['admin/wms/dashboard'] },
        { tKey: 'menu_products',      icon: Boxes,           route: 'wms.products.index',     match: ['admin/wms/products'] },
        { tKey: 'menu_stock',         icon: ClipboardList,   route: 'wms.stock.index',        match: ['admin/wms/stock'] },
        { tKey: 'menu_locations',     icon: MapPin,          route: 'wms.locations.index',    match: ['admin/wms/locations'] },
        { tKey: 'menu_grn',           icon: Inbox,           route: 'wms.grn.index',          match: ['admin/wms/grn'] },
        { tKey: 'menu_fulfillment',   icon: Package,         route: 'wms.fulfillment.index',  match: ['admin/wms/fulfillment'] },
        { tKey: 'menu_outbound',      icon: Send,            route: 'wms.outbound.index',     match: ['admin/wms/outbound'] },
        { tKey: 'menu_adjustments',   icon: ArrowRightLeft,  route: 'wms.adjustments.index',  match: ['admin/wms/adjustments'] },
        { tKey: 'menu_cycle_counts',  icon: CheckSquare,     route: 'wms.cycle-counts.index', match: ['admin/wms/cycle-counts'] },
        { tKey: 'menu_damage',        icon: Bug,             route: 'wms.damage.index',       match: ['admin/wms/damage'] },
        // WMS KB intentionally not listed here — reached via the unified
        // Knowledge Base hub (Main → Knowledge Base → WMS card).
    ]},
    { group: 'menu_operations', items: [
        { tKey: 'menu_tms',            icon: Map,        route: 'tms',                     match: ['admin/tms'],             perm: 'tms_read' },
        { tKey: 'menu_hubs',           icon: Warehouse,  route: 'hubs.index',              match: ['admin/hub'] },
        { tKey: 'menu_pickup_request', icon: Inbox,      route: 'pickup.request.regular',  match: ['admin/pickup-request'] },
    ]},
    // Users Management — customers of the platform (Clients = merchants),
    // internal delivery staff (Couriers = deliverymen), and platform back-
    // office logins (Users & Roles). Same routes as before; the sidebar
    // groups them under one heading so operators find them by function.
    { group: 'menu_users_management', items: [
        { tKey: 'menu_merchants',       icon: Users,      route: 'merchant.index',            match: ['admin/merchant'] },
        { tKey: 'menu_deliveryman',     icon: Truck,      route: 'deliveryman.index',         match: ['admin/deliveryman'] },
        { tKey: 'menu_users_roles',     icon: UserCog,    route: 'users.index',               match: ['admin/users','admin/roles'] },
        { tKey: 'menu_child_companies', icon: Building2,  route: 'child-companies.index',     match: ['admin/child-companies'], perm: 'company_create' },
    ]},
    { group: 'menu_finance', items: [
        { tKey: 'menu_payment_received', icon: BadgeDollarSign, route: 'paid.invoice.index',  match: ['admin/paid'] },
        { tKey: 'menu_payout',           icon: CreditCard,      route: 'payout.index',        match: ['admin/payout'] },
        { tKey: 'menu_accounts',         icon: DollarSign,      route: 'accounts.index',      match: ['admin/accounts'] },
        { tKey: 'menu_wallet_request',   icon: Wallet,          route: 'wallet.request.index', match: ['admin/wallet-request'] },
    ]},
    { group: 'menu_hr', items: [
        { tKey: 'menu_payroll',     icon: Briefcase,  route: 'salary.index',  match: ['admin/salary'] },
        { tKey: 'menu_assets',      icon: HardDrive,  route: 'asset.index',   match: ['admin/assets'] },
    ]},
    { group: 'menu_productivity', items: [
        { tKey: 'menu_todo',              icon: ListChecks,    route: 'todo.index',              match: ['admin/todo'] },
        { tKey: 'menu_support',           icon: MessageCircle, route: 'support.index',           match: ['admin/support'] },
        { tKey: 'menu_news',              icon: Newspaper,     route: 'news-offer.index',        match: ['admin/news-offer'] },
        { tKey: 'menu_push_notification', icon: BellRing,      route: 'push-notification.index', match: ['admin/push-notification'] },
        { tKey: 'menu_fraud',             icon: ShieldAlert,   route: 'fraud.index',             match: ['admin/fraud'] },
    ]},
    { group: 'menu_billing', items: [
        { tKey: 'menu_subscribe',    icon: Bell,        route: 'subscribe.index',    match: ['admin/subscribe'] },
        { tKey: 'menu_subscription', icon: Receipt,     route: 'subscription.index', match: ['subscription'] },
        { tKey: 'menu_reports',      icon: ScrollText,  route: 'parcel.reports',     match: ['admin/reports','admin/parcel-reports'] },
    ]},
    { group: 'menu_zatca', items: [
        { tKey: 'menu_zatca_invoices', icon: Receipt,   route: 'zatca.invoices.index', match: ['admin/zatca/invoices'] },
        { tKey: 'menu_zatca_settings', icon: FileText,  route: 'zatca.settings.index', match: ['admin/zatca/settings'] },
    ]},
    { group: 'menu_cms', items: [
        { tKey: 'menu_front_web', icon: Layout, route: 'blogs.index', match: ['admin/front-web'] },
    ]},
    { group: 'menu_system', items: [
        { tKey: 'menu_logs', icon: History, route: 'logs.index', match: ['admin/logs'] },
    ]},
    { group: 'menu_settings', items: [
        { tKey: 'menu_general_settings',        icon: Sliders,       route: 'general-settings.index',         match: ['admin/general-settings'] },
        { tKey: 'menu_integrations',            icon: Plug,          route: 'integrations.index',             match: ['admin/integrations'] },
        { tKey: 'menu_delivery_category',       icon: Tags,          route: 'delivery-category.index',        match: ['admin/delivery-category'] },
        { tKey: 'menu_delivery_charge',         icon: DollarSign,    route: 'delivery-charge.index',          match: ['admin/delivery-charge'] },
        { tKey: 'menu_delivery_type',           icon: Truck,         route: 'delivery-type.index',            match: ['admin/delivery-type'] },
        { tKey: 'menu_liquid_fragile',          icon: AlertTriangle, route: 'liquid-fragile.index',           match: ['admin/liquid-fragile'] },
        { tKey: 'menu_sms_setting',             icon: MessageCircle, route: 'sms-settings.index',             match: ['admin/sms-settings'] },
        { tKey: 'menu_sms_send_setting',        icon: Send,          route: 'sms-send-settings.index',        match: ['admin/sms-send-settings'] },
        { tKey: 'menu_notification_settings',   icon: BellRing,      route: 'notification-settings.index',    match: ['admin/notification-settings'] },
        { tKey: 'menu_googlemap_setting',       icon: MapPinned,     route: 'googlemap-settings.index',       match: ['admin/googlemap-settings'] },
        { tKey: 'menu_social_login_settings',   icon: KeyRound,      route: 'social.login.settings.index',    match: ['admin/social-login-settings'] },
        { tKey: 'menu_payment_gateway_setup',   icon: CreditCard,    route: 'payout.setup.settings.index',    match: ['admin/settings/pay-out'] },
        { tKey: 'menu_packaging',               icon: Boxes,         route: 'packaging.index',                match: ['admin/packaging'] },
        { tKey: 'menu_label_templates',         icon: FileText,      route: 'label-templates.index',          match: ['admin/settings/label-templates'], perm: 'label_template_manage' },
        { tKey: 'menu_assets_category',         icon: Tags,          route: 'asset-category.index',           match: ['admin/asset-category'] },
        { tKey: 'menu_invoice_generate',        icon: FileText,      route: 'invoice.generate.menually.index', match: ['admin/settings/invoice-generate'] },
        { tKey: 'menu_api_docs',                icon: BookOpen,      route: 'api-docs.merchant',              match: ['admin/api-docs'],           perm: 'integrations_read' },
        { tKey: 'menu_public_tracking_api',     icon: KeyRound,      route: 'settings.public-tracking-api-keys.index', match: ['admin/settings/public-tracking-api-keys'], perm: 'integrations_read' },
    ]},
];

/**
 * Super-admin sidebar. Deliberately terse — the platform super admin
 * only needs cross-tenant surfaces (tenants, plans, billing, tickets,
 * global settings). Tenant-scoped screens (parcels, WMS, TMS, etc.)
 * are hidden here because they're meaningless on the central domain.
 */
const SUPER_NAV = [
    { group: 'menu_main', items: [
        { tKey: 'menu_summary',   icon: Home,            route: 'summary.index',   match: ['summary'] },
        { tKey: 'menu_dashboard', icon: LayoutDashboard, route: 'dashboard.index', match: ['admin/dashboard', 'dashboard'] },
    ]},
    { group: 'menu_users_management', items: [
        { tKey: 'menu_users_roles', icon: UserCog, route: 'users.index', match: ['admin/users','admin/roles'] },
    ]},
    { group: 'menu_billing', items: [
        { tKey: 'menu_company',              icon: Building2, route: 'company.index',             match: ['super-admin/company','admin/company'] },
        { tKey: 'menu_plans',                icon: Layers,    route: 'plan.index',                match: ['super-admin/plan','admin/plans'] },
        { tKey: 'menu_subscribe',            icon: Bell,      route: 'subscribe.index',           match: ['admin/subscribe'] },
        { tKey: 'menu_subscription_history', icon: Receipt,   route: 'admin.subscription.history',match: ['admin/subscription/history'] },
    ]},
    { group: 'menu_productivity', items: [
        { tKey: 'menu_support', icon: MessageCircle, route: 'support.index', match: ['admin/support'] },
    ]},
    { group: 'menu_cms', items: [
        { tKey: 'menu_front_web', icon: Layout, route: 'blogs.index', match: ['admin/front-web'] },
    ]},
    { group: 'menu_settings', items: [
        { tKey: 'menu_general_settings', icon: Sliders, route: 'general-settings.index', match: ['admin/general-settings'] },
    ]},
];

function safeRoute(name, params) {
    try {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(name, params);
        }
    } catch (_) {}
    return '#';
}

function isActive(currentUrl, item) {
    // Normalise — strip any query string so /admin/wms?from=X still highlights.
    const cur = (currentUrl || '').split('?')[0];

    // Exact match against the URL the entry's own route resolves to.
    // Catches root-of-section pages like /admin/wms whose match[] was
    // out of sync with the real URL. Cheap and self-correcting whenever
    // a route is renamed (no need to keep match[] in sync separately).
    const itemUrl = safeRoute(item.route);
    if (itemUrl && itemUrl !== '#') {
        try {
            const itemPath = new URL(itemUrl, 'http://x').pathname;
            if (cur === itemPath) return true;
        } catch (_) { /* ignore malformed URLs */ }
    }

    // Prefix match against the curated match[] patterns. Used for entries
    // whose child pages live below the route URL (e.g. /admin/parcel/bulk
    // should still highlight Shipments which lives at /admin/parcel).
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
            localStorage.setItem('admin-theme', next ? 'dark' : 'light');
        }
    };
    React.useEffect(() => {
        const saved = localStorage.getItem('admin-theme');
        if (saved === 'dark') {
            document.documentElement.classList.add('dark');
            setDark(true);
        }
    }, []);
    return [dark, toggle];
}

function Sidebar({ open, onClose, currentUrl, brand, appName }) {
    const t = useT();
    const { props } = usePage();
    // Flat permission list shared by HandleInertiaRequests.share.
    // Server-side middleware remains authoritative — this is a UX
    // gate so users don't see menu rows that would 403 anyway.
    const perms = new Set(props?.auth?.permissions ?? []);
    const canSee = (item) => ! item.perm || perms.has(item.perm);
    // user_type = 6 → super admin. Central-domain super admins see a
    // terse cross-tenant nav (SUPER_NAV); everyone else keeps NAV.
    const isSuperAdmin = Number(props?.auth?.user?.user_type) === 6;
    const nav = isSuperAdmin ? SUPER_NAV : NAV;
    // Fallback chain: tenant setting → app.name → literal (last-resort).
    // Empty general_settings.name shouldn't leave the sidebar reading "Admin".
    const brandName = (brand?.name || appName || 'Admin');
    const initial = brandName.charAt(0).toUpperCase();
    const homeHref = safeRoute('summary.index');
    const homeUrl  = homeHref === '#' ? '/summary' : homeHref;
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
                className={cn(
                    'fixed inset-y-0 start-0 z-50 w-64 transition-transform',
                    'bg-sidebar text-sidebar-foreground border-e border-sidebar-border',
                    open ? 'translate-x-0' : 'ltr:-translate-x-full rtl:translate-x-full',
                    'md:!translate-x-0',
                )}
            >
                <div className="flex h-16 items-center justify-between border-b border-sidebar-border px-5">
                    {/*
                     * Sidebar header widget. Layout is driven by
                     * general_settings.logo_style (surfaced on brand.logo_style):
                     *   logo_text (default) — icon + name
                     *   logo_only           — image only, sized up for the row
                     *   text_only           — hide image; show wordmark only
                     * When the tenant hasn't uploaded a logo we fall back to
                     * the primary-colored initial tile — except in text_only,
                     * where the whole tile is intentionally hidden.
                     */}
                    {(() => {
                        const style  = brand?.logo_style || 'logo_text';
                        const source = brand?.logo_source || 'logo';
                        // Pick which uploaded image to render. Falls back to the
                        // regular logo when the tenant hasn't uploaded a light
                        // variant, so choosing "light logo" never leaves the
                        // sidebar with a broken image.
                        const imageUrl = source === 'light_logo'
                            ? (brand?.light_logo || brand?.logo)
                            : brand?.logo;
                        const showImage = style !== 'text_only';
                        const showText  = style !== 'logo_only';
                        return (
                            <Link href={homeUrl} className="flex min-w-0 items-center gap-2 font-semibold">
                                {showImage && (imageUrl ? (
                                    <img
                                        src={imageUrl}
                                        alt=""
                                        className={cn(
                                            'shrink-0 rounded-lg bg-white/5 object-contain',
                                            style === 'logo_only' ? 'h-10 w-auto max-w-[168px]' : 'h-8 w-8',
                                        )}
                                    />
                                ) : (
                                    <span className="grid h-8 w-8 place-items-center rounded-lg bg-primary text-primary-foreground shrink-0">{initial}</span>
                                ))}
                                {showText && <span className="truncate">{brandName}</span>}
                            </Link>
                        );
                    })()}
                    <Button variant="ghost" size="icon" className="md:hidden hover:bg-white/10" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
                <nav className="h-[calc(100vh-4rem)] space-y-5 overflow-y-auto px-3 py-4">
                    {nav.map((section) => {
                        const visibleItems = section.items.filter(canSee);
                        if (visibleItems.length === 0) return null;   // hide empty groups
                        return (
                        <div key={section.group}>
                            <div className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/50">
                                {t(section.group)}
                            </div>
                            <ul className="space-y-0.5">
                                {visibleItems.map((item) => {
                                    const active = isActive(currentUrl, item);
                                    const Icon = item.icon;
                                    return (
                                        <li key={item.tKey}>
                                            <a
                                                href={safeRoute(item.route)}
                                                data-tour={`sidebar-${item.tKey}`}
                                                className={cn(
                                                    'group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                                    active
                                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                                        : 'text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground',
                                                )}
                                            >
                                                <Icon className="h-4 w-4 shrink-0" />
                                                <span>{t(item.tKey)}</span>
                                            </a>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                        );
                    })}
                </nav>
            </aside>
        </>
    );
}

function LanguageMenu() {
    const locale = useLocale();
    const t = useT();
    const current = SUPPORTED_LOCALES.find((l) => l.code === locale) || SUPPORTED_LOCALES[0];
    const switchTo = (code) => {
        if (code === locale) return;
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
                        {l.code === current.code && <Check className="h-4 w-4 text-primary" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function Topbar({ onSidebarOpen, user }) {
    const [dark, toggleDark] = useDarkMode();
    const t = useT();
    return (
        <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-border bg-background/85 px-4 backdrop-blur md:px-6">
            <Button variant="ghost" size="icon" className="md:hidden" onClick={onSidebarOpen}>
                <Menu className="h-5 w-5" />
            </Button>

            <div className="hidden flex-1 max-w-md md:flex">
                <GlobalSearch placeholder={t('search_placeholder')} />
            </div>

            <div className="ms-auto flex items-center gap-1">
                <TourLauncher label={t('take_a_tour')} />
                <LanguageMenu />
                <Button variant="ghost" size="icon" onClick={toggleDark} aria-label={t('toggle_theme')}>
                    {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                </Button>
                <Button variant="ghost" size="icon" aria-label={t('notifications')} data-tour="topbar-notifications">
                    <Bell className="h-4 w-4" />
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-9 gap-2 px-2">
                            <span className="grid h-8 w-8 place-items-center rounded-full bg-primary/10 text-primary text-sm font-semibold">
                                {(user?.name || 'A').charAt(0).toUpperCase()}
                            </span>
                            <span className="hidden text-sm sm:inline">{user?.name || 'Admin'}</span>
                            <ChevronDown className="h-4 w-4 text-muted-foreground" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>
                            <div className="font-medium">{user?.name || 'Admin'}</div>
                            <div className="text-xs font-normal text-muted-foreground">{user?.email}</div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={() => {
                                if (!user?.id) return;
                                window.location.href = safeRoute('profile.index', { id: user.id });
                            }}
                            disabled={!user?.id}
                        >
                            <UserIcon className="h-4 w-4 me-2" /> {t('profile')}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={(e) => {
                                e.preventDefault();
                                if (typeof window !== 'undefined' && !window.confirm(t('logout_confirm'))) return;
                                // Build a real form POST so Laravel's logout
                                // gets a proper CSRF token and we land on the
                                // login page via a normal browser navigation
                                // (Inertia's router.post can't follow the
                                // non-Inertia redirect that Auth::logout fires).
                                const form = document.createElement('form');
                                form.action = safeRoute('logout');
                                form.method = 'POST';
                                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                const inp = document.createElement('input');
                                inp.type = 'hidden';
                                inp.name = '_token';
                                inp.value = csrf;
                                form.appendChild(inp);
                                document.body.appendChild(form);
                                form.submit();
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

function FlashBanner() {
    const { props } = usePage();
    const flash = props?.flash || {};
    const errorsList = Array.isArray(flash.errors_list) ? flash.errors_list : null;
    const entries = [
        flash.success && { key: 'success', tone: 'success', text: flash.success },
        flash.error   && { key: 'error',   tone: 'error',   text: flash.error },
        flash.warning && { key: 'warning', tone: 'warning', text: flash.warning },
        flash.message && { key: 'message', tone: 'info',    text: flash.message },
    ].filter(Boolean);
    const [dismissed, setDismissed] = React.useState({});
    // Reset dismissed state when a new set of flashes appears (e.g. after a POST).
    React.useEffect(() => { setDismissed({}); }, [flash.success, flash.error, flash.warning, flash.message]);
    if (entries.length === 0) return null;
    const tones = {
        success: { bg: 'bg-emerald-50 dark:bg-emerald-950/40', text: 'text-emerald-800 dark:text-emerald-200', border: 'border-emerald-200 dark:border-emerald-900', icon: CheckCircle2 },
        error:   { bg: 'bg-rose-50 dark:bg-rose-950/40',       text: 'text-rose-800 dark:text-rose-200',       border: 'border-rose-200 dark:border-rose-900',       icon: XCircle },
        warning: { bg: 'bg-amber-50 dark:bg-amber-950/40',     text: 'text-amber-800 dark:text-amber-200',     border: 'border-amber-200 dark:border-amber-900',     icon: AlertTriangle },
        info:    { bg: 'bg-sky-50 dark:bg-sky-950/40',         text: 'text-sky-800 dark:text-sky-200',         border: 'border-sky-200 dark:border-sky-900',         icon: Info },
    };
    return (
        <div className="mb-4 space-y-2">
            {entries.map(e => {
                if (dismissed[e.key]) return null;
                const t = tones[e.tone];
                const Icon = t.icon;
                return (
                    <div key={e.key} className={cn('flex items-start gap-3 rounded-md border px-4 py-3 text-sm', t.bg, t.text, t.border)}>
                        <Icon className="mt-0.5 h-4 w-4 shrink-0" />
                        <div className="flex-1 min-w-0">
                            <div>{e.text}</div>
                            {(e.tone === 'error' || e.tone === 'warning') && errorsList && errorsList.length > 0 && (
                                <ul className="mt-1.5 list-disc ps-5 space-y-0.5 text-xs opacity-90">
                                    {errorsList.slice(0, 20).map((line, i) => <li key={i}>{String(line)}</li>)}
                                    {errorsList.length > 20 && <li>… {errorsList.length - 20} more</li>}
                                </ul>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => setDismissed(d => ({ ...d, [e.key]: true }))}
                            className="opacity-60 hover:opacity-100"
                            aria-label="Dismiss"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}

export default function AdminLayout({ title, breadcrumbs, children }) {
    const [sidebarOpen, setSidebarOpen] = React.useState(false);
    const { url, props } = usePage();
    const user = props?.auth?.user;
    const brand = props?.brand;
    const appName = props?.app?.name || 'Admin';
    const docTitle = title ? `${title} · ${appName}` : appName;
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title={docTitle} />
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} currentUrl={url} brand={brand} appName={appName} />
            <div className="md:ps-64">
                <Topbar onSidebarOpen={() => setSidebarOpen(true)} user={user} />
                <main className="p-4 md:p-8">
                    <FlashBanner />
                    {(title || breadcrumbs) && (
                        <div className="mb-6">
                            {breadcrumbs && (
                                <nav className="mb-2 flex gap-1.5 text-xs text-muted-foreground">
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
