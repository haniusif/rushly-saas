@extends('backend.partials.master')
@section('title')
    {{ __('levels.company') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <div class="tw-px-1 tw-pt-4 sm:tw-px-2">

            {{-- Breadcrumb --}}
            <nav class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-text-gray-500 tw-mb-4">
                <a href="{{ route('dashboard.index') }}" class="hover:tw-text-brand-600 tw-no-underline">{{ __('levels.dashboard') }}</a>
                <i class="fa fa-angle-right tw-text-[10px] tw-text-gray-400 tw-rtl-flip"></i>
                <span class="tw-text-gray-700 tw-font-medium">{{ __('menus.company') }}</span>
            </nav>

            <div class="tw-bg-white tw-border tw-border-gray-100 tw-rounded-xl tw-shadow-card tw-overflow-hidden">

                {{-- Header --}}
                <div class="tw-flex tw-items-center tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-b tw-border-gray-100">
                    <div>
                        <h1 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-m-0">{{ __('levels.companies') }}</h1>
                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1 tw-m-0">{{ $companies->total() }} {{ __('Showing') }}</p>
                    </div>
                    @if (hasPermission('company_create') == true)
                        <a href="{{ route('company.create') }}"
                           class="tw-inline-flex tw-items-center tw-gap-2 tw-h-10 tw-px-4 tw-text-sm tw-font-medium tw-text-white tw-bg-brand-600 hover:tw-bg-brand-700 tw-rounded-lg tw-no-underline">
                            <i class="fa fa-plus tw-text-xs"></i>
                            {{ __('levels.add') }}
                        </a>
                    @endif
                </div>

                {{-- Table --}}
                <div class="tw-overflow-x-auto">
                    <table class="tw-w-full tw-text-sm">
                        <thead class="tw-bg-gray-50">
                            <tr class="tw-text-left tw-text-xs tw-uppercase tw-tracking-wider tw-text-gray-500">
                                <th class="tw-px-5 tw-py-3 tw-font-medium tw-w-12">#</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.name') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.domain') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.user_details') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.plan') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.subscription') }}</th>
                                <th class="tw-px-5 tw-py-3 tw-font-medium">{{ __('levels.status') }}</th>
                                @if (hasPermission('company_update') == true || hasPermission('company_delete') == true)
                                    <th class="tw-px-5 tw-py-3 tw-font-medium tw-text-right">{{ __('levels.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="tw-divide-y tw-divide-gray-100">
                            @php $i = $companies->firstItem(); @endphp
                            @forelse ($companies as $company)
                                <tr class="hover:tw-bg-gray-50 tw-transition-colors">
                                    <td class="tw-px-5 tw-py-3 tw-text-gray-500 tw-tabular-nums">{{ $i++ }}</td>

                                    {{-- Company --}}
                                    <td class="tw-px-5 tw-py-3">
                                        <div class="tw-flex tw-items-center tw-gap-3">
                                            <img src="{{ $company->company?->LogoImage }}" alt="logo" class="tw-w-9 tw-h-9 tw-rounded-lg tw-object-cover tw-bg-gray-100" />
                                            <div class="tw-min-w-0">
                                                <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ @$company->company->name }}</div>
                                                @if (!empty($company->company->plan))
                                                    <div class="tw-text-xs tw-text-gray-500">
                                                        <span class="tw-inline-flex tw-items-center tw-gap-1">
                                                            <i class="fa fa-cubes tw-text-[10px]"></i>
                                                            {{ count($company->company->plan->modules) }} {{ __('levels.modules') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Domain --}}
                                    <td class="tw-px-5 tw-py-3 tw-text-xs">
                                        @if (!empty($company->tenantDetails) && isset($company->tenantDetails->domains))
                                            @foreach ($company->tenantDetails->domains as $domain)
                                                <a href="{{ scheme_name($domain->domain) }}" target="_blank"
                                                   class="tw-inline-flex tw-items-center tw-gap-1 tw-text-brand-600 hover:tw-text-brand-700 tw-no-underline tw-mb-0.5">
                                                    <i class="fa fa-link tw-text-[9px]"></i>
                                                    {{ scheme_name() }}{{ $domain->domain }}
                                                </a><br/>
                                            @endforeach
                                        @else
                                            <span class="tw-text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- User --}}
                                    <td class="tw-px-5 tw-py-3">
                                        <div class="tw-flex tw-items-center tw-gap-3">
                                            <img src="{{ $company->image }}" alt="user" class="tw-w-9 tw-h-9 tw-rounded-full tw-object-cover tw-bg-gray-100" />
                                            <div class="tw-min-w-0">
                                                <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ $company->name }}</div>
                                                <div class="tw-text-xs tw-text-gray-500 tw-truncate">{{ $company->email }}</div>
                                                @if ($company->mobile)
                                                    <div class="tw-text-xs tw-text-gray-400 tw-truncate tw-tabular-nums">{{ $company->mobile }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Plan --}}
                                    <td class="tw-px-5 tw-py-3">
                                        @if (!empty($company->company->plan))
                                            <span class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-1 tw-text-xs tw-font-medium tw-rounded-full tw-bg-indigo-50 tw-text-indigo-700">
                                                {{ @$company->company->plan->name }}
                                            </span>
                                        @else
                                            <span class="tw-text-xs tw-text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Subscription --}}
                                    <td class="tw-px-5 tw-py-3">
                                        <div class="tw-flex tw-flex-col tw-gap-1.5">
                                            @if (subscriptionCheck($company))
                                                <span class="tw-inline-flex tw-items-center tw-gap-1 tw-text-xs tw-text-emerald-700">
                                                    <span class="tw-w-1.5 tw-h-1.5 tw-rounded-full tw-bg-emerald-500"></span>
                                                    {{ __('levels.remaining') }} {{ subscriptionCheck($company) }} {{ __('levels.days') }}
                                                </span>
                                            @else
                                                <span class="tw-inline-flex tw-items-center tw-gap-1 tw-px-2 tw-py-0.5 tw-text-xs tw-font-medium tw-rounded-full tw-bg-red-50 tw-text-red-700 tw-w-fit">
                                                    {{ __('levels.expired') }}
                                                </span>
                                            @endif
                                            @if (hasPermission('company_subscribe'))
                                                <button class="modalBtn tw-inline-flex tw-items-center tw-justify-center tw-h-7 tw-px-2.5 tw-text-xs tw-font-medium tw-text-brand-700 tw-bg-brand-50 hover:tw-bg-brand-100 tw-rounded-md tw-border-0 tw-w-fit"
                                                        data-bs-toggle="modal" data-bs-target="#dynamic-modal"
                                                        data-title="{{ @$company->company->name }}"
                                                        data-url="{{ route('company.subscription.switch', $company->id) }}">
                                                    {{ __('Subscribe Now') }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="tw-px-5 tw-py-3">{!! $company->my_status !!}</td>

                                    @if (hasPermission('company_update') == true || hasPermission('company_delete') == true)
                                        <td class="tw-px-5 tw-py-3 tw-text-right">
                                            <div class="dropdown tw-inline-block">
                                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                                        class="tw-inline-flex tw-items-center tw-justify-center tw-w-9 tw-h-9 tw-rounded-lg hover:tw-bg-gray-100 tw-text-gray-600 tw-border-0 tw-bg-transparent"
                                                        aria-label="actions">
                                                    <i class="fa fa-ellipsis-h"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @if (hasPermission('company_update') == true)
                                                        <a href="{{ route('company.edit', $company->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit"></i> {{ __('levels.edit') }}
                                                        </a>
                                                    @endif
                                                    @if (hasPermission('company_delete') == true && $company->id != 1)
                                                        <form id="delete" action="{{ route('company.delete', $company->company_id) }}" method="POST" data-title="{{ __('delete.company') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" value="Company" id="deleteTitle">
                                                            <button type="submit" class="dropdown-item tw-text-red-600">
                                                                <i class="fa fa-trash"></i> {{ __('levels.delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="tw-px-5 tw-py-12 tw-text-center">
                                        <div class="tw-text-gray-300 tw-mb-2"><i class="fa fa-building tw-text-3xl"></i></div>
                                        <p class="tw-text-sm tw-text-gray-500 tw-m-0">No companies yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($companies->hasPages())
                    <div class="tw-flex tw-flex-col sm:tw-flex-row sm:tw-items-center sm:tw-justify-between tw-gap-3 tw-px-5 tw-py-4 tw-border-t tw-border-gray-100">
                        <p class="tw-text-xs tw-text-gray-500 tw-m-0">
                            {!! __('Showing') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $companies->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $companies->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="tw-font-medium tw-text-gray-700">{{ $companies->total() }}</span>
                            {!! __('results') !!}
                        </p>
                        <div>{{ $companies->links() }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
