@extends('backend.partials.master')

@section('title', __('settings_hub.title'))

@push('css')
<style>
    .settings-hub .hub-card {
        position: relative;
        display: block;
        padding: 18px 16px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid #e5e7eb;
        text-decoration: none !important;
        color: inherit;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        height: 100%;
    }
    .settings-hub .hub-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px -10px rgba(15,23,42,.18);
        border-color: #c7d2fe;
    }
    .settings-hub .hub-icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: 22px;
        margin-bottom: 10px;
    }
    .settings-hub .hub-title { font-weight: 600; font-size: 14px; color: #0f172a; }
    .settings-hub .hub-desc  { font-size: 12px; color: #64748b; margin-top: 4px; line-height: 1.4; min-height: 32px; }
    .settings-hub .hub-arrow { position: absolute; top: 18px; right: 18px; color: #cbd5e1; }
    .settings-hub .group-title {
        font-size: 11px; font-weight: 700; letter-spacing: .08em;
        text-transform: uppercase; color: #64748b;
        margin: 20px 0 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e5e7eb;
    }
    .gradient-from-indigo-500   { background: linear-gradient(135deg,#6366f1,#4338ca); }
    .gradient-from-sky-500      { background: linear-gradient(135deg,#0ea5e9,#0369a1); }
    .gradient-from-cyan-500     { background: linear-gradient(135deg,#06b6d4,#0e7490); }
    .gradient-from-emerald-500  { background: linear-gradient(135deg,#10b981,#047857); }
    .gradient-from-amber-500    { background: linear-gradient(135deg,#f59e0b,#b45309); }
    .gradient-from-violet-500   { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
    .gradient-from-blue-500     { background: linear-gradient(135deg,#3b82f6,#1d4ed8); }
    .gradient-from-rose-500     { background: linear-gradient(135deg,#f43f5e,#be123c); }
</style>
@endpush

@section('mainContent')
<section class="section settings-hub">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center mb-1">
                <h4 class="mb-0"><i class="ti ti-settings"></i> {{ __('settings_hub.title') }}</h4>
            </div>
            <p class="text-muted small mb-2">{{ __('settings_hub.subtitle') }}</p>
        </div>
    </div>

    @foreach ($groups as $key => $group)
        @php
            $visibleCards = collect($group['cards'])->filter(fn ($c) => hasPermission($c['perm']) == true);
        @endphp
        @if ($visibleCards->isEmpty()) @continue @endif

        <div class="row">
            <div class="col-12">
                <div class="group-title">{{ $group['title'] }}</div>
            </div>
        </div>

        <div class="row">
            @foreach ($visibleCards as $card)
                @php
                    // Derive the gradient class. The "from-X" portion picks the color.
                    preg_match('/from-([a-z]+)-500/', $card['gradient'], $m);
                    $gradientClass = 'gradient-from-' . ($m[1] ?? 'indigo') . '-500';
                @endphp
                <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                    <a href="{{ route($card['route']) }}" class="hub-card">
                        <div class="hub-icon {{ $gradientClass }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="hub-arrow"><i class="ti ti-arrow-right"></i></div>
                        <div class="hub-title">{{ $card['title'] }}</div>
                        <div class="hub-desc">{{ $card['description'] }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    @endforeach
</section>
@endsection
