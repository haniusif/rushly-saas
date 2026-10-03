@extends('backend.partials.master')

@section('title', __('mobile_apps.title'))

@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <a href="{{ route('settings.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('settings_hub.title') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('mobile_apps.title') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">

                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                    <span class="tw-shrink-0 tw-w-10 tw-h-10 tw-rounded-lg tw-bg-brand-50 tw-text-brand-600 tw-flex tw-items-center tw-justify-center">
                        <i class="ti ti-device-mobile tw-text-lg"></i>
                    </span>
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('mobile_apps.title') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">{{ __('mobile_apps.subtitle') }}</p>
                    </div>
                </div>

                <div class="tw-p-6">
                    <div class="tw-grid tw-grid-cols-1 sm:tw-grid-cols-2 lg:tw-grid-cols-3 xl:tw-grid-cols-4 tw-gap-4">
                        @foreach ($apps as $app)
                            @php
                                $palette = [
                                    'emerald' => ['from' => '#10b981', 'to' => '#047857'],
                                    'indigo'  => ['from' => '#6366f1', 'to' => '#4338ca'],
                                    'rose'    => ['from' => '#f43f5e', 'to' => '#be123c'],
                                    'sky'     => ['from' => '#0ea5e9', 'to' => '#0369a1'],
                                    'amber'   => ['from' => '#f59e0b', 'to' => '#b45309'],
                                    'violet'  => ['from' => '#8b5cf6', 'to' => '#6d28d9'],
                                    'cyan'    => ['from' => '#06b6d4', 'to' => '#0e7490'],
                                    'blue'    => ['from' => '#3b82f6', 'to' => '#1d4ed8'],
                                ];
                                preg_match('/from-([a-z]+)-500/', $app['gradient'], $m);
                                $c = $palette[$m[1] ?? 'indigo'];
                            @endphp
                            <div class="tw-group tw-relative tw-flex tw-flex-col tw-p-5 tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl hover:tw-shadow-card-hover hover:tw-border-brand-200 tw-transition tw-h-full">
                                <div class="tw-flex tw-items-start tw-gap-3 tw-mb-3">
                                    <span class="tw-shrink-0 tw-w-12 tw-h-12 tw-rounded-xl tw-text-white tw-flex tw-items-center tw-justify-center tw-text-xl tw-shadow-sm"
                                          style="background: linear-gradient(135deg,{{ $c['from'] }},{{ $c['to'] }});">
                                        <i class="{{ $app['icon'] }}"></i>
                                    </span>
                                    <div class="tw-min-w-0">
                                        <div class="tw-text-sm tw-font-semibold tw-text-gray-900 tw-truncate">{{ $app['title'] }}</div>
                                        <span class="tw-inline-flex tw-items-center tw-gap-1 tw-mt-1 tw-px-2 tw-py-0.5 tw-text-[11px] tw-font-medium tw-text-gray-600 tw-bg-gray-100 tw-rounded-full">
                                            <i class="fa fa-user tw-text-[9px] tw-text-gray-400"></i>
                                            {{ $app['audience'] }}
                                        </span>
                                    </div>
                                </div>
                                <p class="tw-text-xs tw-text-gray-600 tw-leading-relaxed tw-m-0 tw-flex-1">{{ $app['description'] }}</p>
                                <div class="tw-mt-4 tw-pt-3 tw-border-t tw-border-dashed tw-border-gray-200 tw-flex tw-items-center tw-gap-2 tw-text-[11px] tw-text-gray-400 tw-font-mono">
                                    <i class="fa fa-code-branch"></i>
                                    <span class="tw-truncate">{{ $app['repo'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="tw-mt-6 tw-flex tw-items-start tw-gap-2 tw-p-3 tw-bg-brand-50 tw-border tw-border-brand-100 tw-rounded-lg">
                        <i class="fa fa-info-circle tw-text-brand-600 tw-mt-0.5"></i>
                        <p class="tw-text-xs tw-text-brand-800 tw-m-0 tw-leading-relaxed">
                            {{ __('mobile_apps.footer_note') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
