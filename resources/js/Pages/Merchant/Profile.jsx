import * as React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { User, Building2, Lock, AlertCircle, CheckCircle2 } from 'lucide-react';
import MerchantLayout from '@/Layouts/MerchantLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/Card';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Label } from '@/Components/ui/Label';
import { useT } from '@/lib/i18n';

function safeRoute(name, params) {
    try { return window.route(name, params); } catch (e) { return '#'; }
}

function Field({ label, error, children, required }) {
    return (
        <div className="space-y-1.5">
            <Label className="flex items-center gap-1">
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && (
                <p className="text-xs text-destructive flex items-center gap-1">
                    <AlertCircle className="h-3 w-3" /> {error}
                </p>
            )}
        </div>
    );
}

function FlashBanner() {
    const { flash } = usePage().props;
    if (!flash?.success && !flash?.error) return null;
    const success = !!flash?.success;
    return (
        <div className={`mb-4 rounded-md border p-3 text-sm flex items-center gap-2 ${success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-destructive/30 bg-destructive/10 text-destructive'}`}>
            {success ? <CheckCircle2 className="h-4 w-4" /> : <AlertCircle className="h-4 w-4" />}
            <span>{flash.success || flash.error}</span>
        </div>
    );
}

export default function Profile({ profile }) {
    const t = useT();
    const { auth } = usePage().props;
    const userId = profile.user.id;

    const profileForm = useForm({
        name:          profile.user.name || '',
        email:         profile.user.email || '',
        mobile:        profile.user.mobile || '',
        address:       profile.merchant.address || '',
        business_name: profile.merchant.business_name || '',
        image_id:      null,
    });

    const passwordForm = useForm({
        old_password:     '',
        new_password:     '',
        confirm_password: '',
    });

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.post(safeRoute('merchant-profile.update', { id: userId }), {
            forceFormData: true,
            preserveScroll: true,
            _method: 'put',
            onSuccess: () => profileForm.reset('image_id'),
        });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        passwordForm.put(safeRoute('merchant-profile.password.update', { id: userId }), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const avatar = profile.user.image
        ? <img src={profile.user.image} alt={profile.user.name} className="h-16 w-16 rounded-full object-cover ring-2 ring-background shadow" />
        : <div className="h-16 w-16 rounded-full bg-primary/10 text-primary grid place-items-center font-semibold text-xl">{(profile.user.name || 'M').charAt(0).toUpperCase()}</div>;

    return (
        <MerchantLayout title={t('profile_title')} breadcrumbs={[t('nav_dashboard'), t('profile')]}>
            <Head title={t('profile_title')} />

            <FlashBanner />

            <div className="grid gap-6 md:grid-cols-3">
                {/* Identity card */}
                <Card className="md:col-span-1 h-fit">
                    <CardContent className="pt-6">
                        <div className="flex flex-col items-center text-center gap-3">
                            {avatar}
                            <div>
                                <div className="font-semibold text-lg">{profile.user.name}</div>
                                <div className="text-sm text-muted-foreground">{profile.user.email}</div>
                                {profile.user.mobile && <div className="text-sm text-muted-foreground mt-0.5">{profile.user.mobile}</div>}
                            </div>
                            {profile.merchant.business_name && (
                                <div className="w-full pt-3 mt-1 border-t text-sm">
                                    <div className="text-muted-foreground text-xs uppercase tracking-wide mb-1">{t('business_name')}</div>
                                    <div className="font-medium">{profile.merchant.business_name}</div>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Edit forms */}
                <div className="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><User className="h-4 w-4" /> {t('profile_account')}</CardTitle>
                            <CardDescription>{t('profile_business')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitProfile} className="grid gap-4 sm:grid-cols-2" encType="multipart/form-data">
                                <Field label={t('nav_dashboard') && 'Name'} error={profileForm.errors.name} required>
                                    <Input value={profileForm.data.name} onChange={(e) => profileForm.setData('name', e.target.value)} />
                                </Field>
                                <Field label="Email" error={profileForm.errors.email}>
                                    <Input type="email" value={profileForm.data.email} onChange={(e) => profileForm.setData('email', e.target.value)} />
                                </Field>
                                <Field label="Mobile" error={profileForm.errors.mobile}>
                                    <Input value={profileForm.data.mobile} onChange={(e) => profileForm.setData('mobile', e.target.value)} />
                                </Field>
                                <Field label={t('business_name')} error={profileForm.errors.business_name}>
                                    <Input value={profileForm.data.business_name} onChange={(e) => profileForm.setData('business_name', e.target.value)} />
                                </Field>
                                <Field label="Address" error={profileForm.errors.address} required>
                                    <Input value={profileForm.data.address} onChange={(e) => profileForm.setData('address', e.target.value)} />
                                </Field>
                                <Field label="Profile photo" error={profileForm.errors.image_id}>
                                    <Input type="file" accept="image/*" onChange={(e) => profileForm.setData('image_id', e.target.files?.[0] || null)} />
                                </Field>
                                <div className="sm:col-span-2 flex justify-end">
                                    <Button type="submit" disabled={profileForm.processing}>
                                        {profileForm.processing ? '…' : t('save_changes')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base"><Lock className="h-4 w-4" /> {t('profile_security')}</CardTitle>
                            <CardDescription>{t('change_password')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitPassword} className="grid gap-4 sm:grid-cols-3">
                                <Field label={t('old_password')} error={passwordForm.errors.old_password} required>
                                    <Input type="password" autoComplete="current-password" value={passwordForm.data.old_password} onChange={(e) => passwordForm.setData('old_password', e.target.value)} />
                                </Field>
                                <Field label={t('new_password')} error={passwordForm.errors.new_password} required>
                                    <Input type="password" autoComplete="new-password" value={passwordForm.data.new_password} onChange={(e) => passwordForm.setData('new_password', e.target.value)} />
                                </Field>
                                <Field label={t('confirm_password')} error={passwordForm.errors.confirm_password} required>
                                    <Input type="password" autoComplete="new-password" value={passwordForm.data.confirm_password} onChange={(e) => passwordForm.setData('confirm_password', e.target.value)} />
                                </Field>
                                <div className="sm:col-span-3 flex justify-end">
                                    <Button type="submit" disabled={passwordForm.processing}>
                                        {passwordForm.processing ? '…' : t('change_password')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </MerchantLayout>
    );
}
