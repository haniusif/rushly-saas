import * as React from 'react';
import { ArrowLeft } from 'lucide-react';
import { Card, CardContent } from '@/Components/ui/Card';
import { cn } from '@/lib/utils';

/**
 * Compact merchant header used by the per-merchant sub-pages (delivery
 * charges / shops / payments / invoices). Shows avatar, name, unique id,
 * and a "back to merchant" link. Each sub-page renders its own title /
 * actions on the right via the `actions` slot.
 */
export default function MerchantSubHeader({ merchant = {}, backUrl, backLabel = 'Back to merchant', title, actions }) {
    const initials = (merchant.name || merchant.business_name || '?')
        .trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    return (
        <Card className="mb-5">
            <CardContent className="flex items-center gap-4 p-4">
                {merchant.image
                    ? <img src={merchant.image} alt="" className="h-12 w-12 rounded-full object-cover ring-2 ring-background shadow shrink-0" />
                    : <div className="grid h-12 w-12 place-items-center rounded-full bg-primary/10 text-primary font-semibold shrink-0">{initials}</div>}
                <div className="min-w-0">
                    <div className="text-xs uppercase tracking-wide text-muted-foreground font-medium">{title}</div>
                    <div className="font-semibold truncate">{merchant.business_name || merchant.name || '—'}</div>
                    {merchant.unique_id && (
                        <div className="text-xs text-muted-foreground font-mono">#{merchant.unique_id}</div>
                    )}
                </div>
                <div className="ms-auto flex items-center gap-2">
                    {backUrl && (
                        <a href={backUrl} className="inline-flex h-9 items-center rounded-md border border-input bg-background px-3 text-xs font-medium hover:bg-accent">
                            <ArrowLeft className="h-3.5 w-3.5 me-1" /> {backLabel}
                        </a>
                    )}
                    {actions}
                </div>
            </CardContent>
        </Card>
    );
}
