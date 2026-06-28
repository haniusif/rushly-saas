import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    BookOpen, LayoutDashboard, Package, Warehouse, Truck, DollarSign,
    UserCog, ListChecks, Receipt, FileText, Layout, History, Settings,
    ExternalLink, ChevronRight,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/Card';

const ICONS = {
    LayoutDashboard, Package, Warehouse, Truck, DollarSign, UserCog,
    ListChecks, Receipt, FileText, Layout, History, Settings,
};

function SectionCard({ section, t }) {
    const Icon = ICONS[section.icon] || BookOpen;
    const inner = (
        <CardContent className="flex items-start gap-4 p-5">
            <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                <Icon className="h-6 w-6" />
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <h3 className="text-base font-semibold tracking-tight truncate">{section.label}</h3>
                    {section.is_external ? <ExternalLink className="h-3.5 w-3.5 text-muted-foreground" /> : null}
                </div>
                <p className="mt-1 line-clamp-3 text-xs text-muted-foreground">
                    {section.overview || '—'}
                </p>
                <div className="mt-2 inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                    {section.sub_count > 0
                        ? <span>{section.sub_count} {t.sub_pages}</span>
                        : <span>{t.no_subs}</span>}
                </div>
            </div>
            <ChevronRight className="mt-1 h-4 w-4 shrink-0 text-muted-foreground" />
        </CardContent>
    );

    if (section.is_external) {
        return (
            <a href={section.url} className="block">
                <Card className="h-full hover:shadow-md hover:border-primary/40 transition-all">{inner}</Card>
            </a>
        );
    }

    return (
        <Link href={section.url} className="block">
            <Card className="h-full hover:shadow-md hover:border-primary/40 transition-all">{inner}</Card>
        </Link>
    );
}

export default function Hub({ sections = [], t = {} }) {
    return (
        <AdminLayout>
            <Head title={t.title} />

            <div className="mb-6 flex items-start gap-3">
                <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                    <BookOpen className="h-6 w-6" />
                </div>
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">{t.title}</h1>
                    <p className="mt-1 text-sm text-muted-foreground max-w-3xl">{t.subtitle}</p>
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {sections.map((s) => (
                    <SectionCard key={s.slug} section={s} t={t} />
                ))}
            </div>
        </AdminLayout>
    );
}
