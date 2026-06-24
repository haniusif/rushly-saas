import * as React from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard, Package, Truck, Building2, Users, Warehouse,
    Boxes, ClipboardList, MapPin, Inbox, Send, ArrowRightLeft,
    CheckSquare, Bug, Map, MessageCircle, Newspaper, Activity,
    Settings, History, FileText, Receipt, Menu, X, Sun, Moon,
    LogOut, ChevronDown, Bell, Search, Globe, Check, User as UserIcon,
    BarChart3, AlertTriangle, Hourglass, Wand2, ListChecks,
    Wallet, ShieldAlert, DollarSign, CreditCard, BadgeDollarSign,
    UserCog, HardDrive, Briefcase, Tags, BellRing, KeyRound,
    Plug, MapPinned, Layout, ScrollText, Sliders,
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

const NAV = [
    { group: 'menu_main', items: [
        { tKey: 'menu_dashboard', icon: LayoutDashboard, route: 'dashboard.index', match: ['admin/dashboard', 'dashboard'] },
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
    ]},
    { group: 'menu_operations', items: [
        { tKey: 'menu_deliveryman',    icon: Truck,      route: 'deliveryman.index',       match: ['admin/deliveryman'] },
        { tKey: 'menu_tms',            icon: Map,        route: 'tms',                     match: ['admin/tms'] },
        { tKey: 'menu_hubs',           icon: Warehouse,  route: 'hubs.index',              match: ['admin/hub'] },
        { tKey: 'menu_merchants',      icon: Users,      route: 'merchant.index',          match: ['admin/merchant'] },
        { tKey: 'menu_pickup_request', icon: Inbox,      route: 'pickup.request.regular',  match: ['admin/pickup-request'] },
    ]},
    { group: 'menu_finance', items: [
        { tKey: 'menu_payment_received', icon: BadgeDollarSign, route: 'paid.invoice.index',  match: ['admin/paid'] },
        { tKey: 'menu_payout',           icon: CreditCard,      route: 'payout.index',        match: ['admin/payout'] },
        { tKey: 'menu_accounts',         icon: DollarSign,      route: 'accounts.index',      match: ['admin/accounts'] },
        { tKey: 'menu_wallet_request',   icon: Wallet,          route: 'wallet.request.index', match: ['admin/wallet-request'] },
    ]},
    { group: 'menu_hr', items: [
        { tKey: 'menu_users_roles', icon: UserCog,    route: 'users.index',   match: ['admin/users','admin/roles'] },
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
        { tKey: 'menu_assets_category',         icon: Tags,          route: 'asset-category.index',           match: ['admin/asset-category'] },
        { tKey: 'menu_invoice_generate',        icon: FileText,      route: 'invoice.generate.menually.index', match: ['admin/settings/invoice-generate'] },
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

function isActive(currentUrl, matches) {
    return matches.some((m) => currentUrl.startsWith('/' + m));
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

function Sidebar({ open, onClose, currentUrl, brand }) {
    const t = useT();
    const brandName = brand?.name || 'Admin';
    const initial = brandName.charAt(0).toUpperCase();
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
                    <Link href="/admin/dashboard" className="flex min-w-0 items-center gap-2 font-semibold">
                        {brand?.logo ? (
                            <img src={brand.logo} alt="" className="h-8 w-8 shrink-0 rounded-lg bg-white/5 object-contain" />
                        ) : (
                            <span className="grid h-8 w-8 place-items-center rounded-lg bg-primary text-primary-foreground shrink-0">{initial}</span>
                        )}
                        <span className="truncate">{brandName}</span>
                    </Link>
                    <Button variant="ghost" size="icon" className="md:hidden hover:bg-white/10" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
                <nav className="h-[calc(100vh-4rem)] space-y-5 overflow-y-auto px-3 py-4">
                    {NAV.map((section) => (
                        <div key={section.group}>
                            <div className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/50">
                                {t(section.group)}
                            </div>
                            <ul className="space-y-0.5">
                                {section.items.map((item) => {
                                    const active = isActive(currentUrl, item.match);
                                    const Icon = item.icon;
                                    return (
                                        <li key={item.tKey}>
                                            <a
                                                href={safeRoute(item.route)}
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
                    ))}
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
                <LanguageMenu />
                <Button variant="ghost" size="icon" onClick={toggleDark} aria-label={t('toggle_theme')}>
                    {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
                </Button>
                <Button variant="ghost" size="icon" aria-label={t('notifications')}>
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
            <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} currentUrl={url} brand={brand} />
            <div className="md:ps-64">
                <Topbar onSidebarOpen={() => setSidebarOpen(true)} user={user} />
                <main className="p-4 md:p-8">
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
