import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    BookOpen, LayoutDashboard, MessageCircle, Wallet, Receipt,
    Package, PiggyBank, BarChart3, Settings, ChevronRight,
} from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent } from '@/Components/ui/Card';

const ICONS = {
    LayoutDashboard, MessageCircle, Wallet, Receipt,
    Package, PiggyBank, BarChart3, Settings, BookOpen,
};

function SectionCard({ section, t }) {
    const Icon = ICONS[section.icon] || BookOpen;
    return (
        <Link href={section.url} className="block">
            <Card className="h-full hover:shadow-md hover:border-primary/40 transition-all">
                <CardContent className="flex items-start gap-4 p-5">
                    <div className="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                        <Icon className="h-6 w-6" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <h3 className="text-base font-semibold tracking-tight truncate">{section.label}</h3>
                        <p className="mt-1 line-clamp-3 text-xs text-muted-foreground">{section.overview || '—'}</p>
                        <div className="mt-2 inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                            {section.sub_count > 0
                                ? <span>{section.sub_count} {t.sub_pages}</span>
                                : <span>{t.no_subs}</span>}
                        </div>
                    </div>
                    <ChevronRight className="mt-1 h-4 w-4 shrink-0 text-muted-foreground" />
                </CardContent>
            </Card>
        </Link>
    );
}

export default function Hub({ sections = [], t = {} }) {
    return (
        <MerchantLayout title={t.title}>
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
                {sections.map((s) => <SectionCard key={s.slug} section={s} t={t} />)}
            </div>
        </MerchantLayout>
    );
}
