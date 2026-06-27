@extends('backend.partials.master')
@section('title')
    {{ __('backup.title') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('backup.title') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">
                <div class="tw-px-6 tw-py-5 tw-border-b tw-border-gray-100 tw-flex tw-items-center tw-gap-3">
                    <span class="tw-shrink-0 tw-w-10 tw-h-10 tw-rounded-lg tw-bg-brand-50 tw-text-brand-600 tw-flex tw-items-center tw-justify-center">
                        <i class="fa fa-database tw-text-lg"></i>
                    </span>
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('backup.title') }} {{ __('backup.backup') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-0.5 tw-m-0">Download a full snapshot of the database.</p>
                    </div>
                </div>

                <div class="tw-p-6">
                    <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-12 tw-gap-6 tw-items-start">
                        <div class="lg:tw-col-span-8">
                            <h2 class="tw-text-sm tw-font-semibold tw-text-gray-800 tw-mb-2">About this backup</h2>
                            <p class="tw-text-sm tw-text-gray-600 tw-leading-relaxed tw-m-0">
                                {{ __('backup.backup_description') }}
                            </p>

                            <div class="tw-mt-4 tw-flex tw-items-start tw-gap-2 tw-p-3 tw-bg-amber-50 tw-border tw-border-amber-200 tw-rounded-lg">
                                <i class="fa fa-exclamation-triangle tw-text-amber-600 tw-mt-0.5"></i>
                                <p class="tw-text-xs tw-text-amber-800 tw-m-0 tw-leading-relaxed">
                                    Generating a backup can take several seconds on large databases. Don't close this tab once you start the download.
                                </p>
                            </div>
                        </div>

                        <div class="lg:tw-col-span-4">
                            <div class="tw-bg-gray-50 tw-border tw-border-gray-200 tw-rounded-lg tw-p-4 tw-text-center">
                                <div class="tw-text-xs tw-uppercase tw-tracking-wider tw-font-medium tw-text-gray-500 tw-mb-3">{{ __('backup.database_backup') }}</div>
                                <a href="{{ route('database.backup.download') }}"
                                   class="tw-inline-flex tw-items-center tw-gap-2 tw-h-11 tw-px-5 tw-text-sm tw-font-medium tw-text-white tw-bg-emerald-600 hover:tw-bg-emerald-700 tw-rounded-lg tw-no-underline tw-w-full tw-justify-center">
                                    <i class="fa fa-download"></i>
                                    {{ __('backup.database_backup') }}
                                </a>
                                <p class="tw-text-[11px] tw-text-gray-500 tw-mt-2 tw-m-0">{{ now()->format('Y-m-d H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
