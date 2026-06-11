@extends('backend.partials.master')
@section('title') Integrations @endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('dashboard.title') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="#" class="breadcrumb-link active">{{ __('menus.settings') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('integrations.index') }}" class="breadcrumb-link active">Integrations</a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="h3 mb-1">E-commerce Integrations</p>
                    <p class="text-muted mb-0">
                        Connect Rushly to merchant storefronts. Each integration runs as a standalone bridge app
                        that talks to Rushly's v10 API. Edit the values below to point at your bridge instance and
                        set defaults for parcels created from that platform.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($integrations as $integration)
            @php
                $count = $parcelCounts[$integration->platform] ?? 0;
                $logo  = $integration->logoUrl();
                $ready = $integration->bridgeReady();
            @endphp
            <div class="col-12 col-md-6 col-xl-4 d-flex">
                <div class="card flex-fill" style="border:1px solid #eef0f3;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;background:#f7f7fb;border-radius:12px;">
                                @if ($logo)
                                    <img src="{{ $logo }}" alt="{{ $integration->displayName() }}"
                                         style="max-width:46px;max-height:46px;">
                                @else
                                    <span style="font-weight:700;font-size:22px;color:#333;">
                                        {{ strtoupper(substr($integration->displayName(), 0, 1)) }}
                                    </span>
                                @endif
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $integration->displayName() }}</h4>
                                <small class="text-muted">
                                    @switch ($integration->platform)
                                        @case ('salla')       salla.sa @break
                                        @case ('zid')         zid.sa @break
                                        @case ('shopify')     shopify.com @break
                                        @case ('woocommerce') woocommerce.com @break
                                    @endswitch
                                </small>
                            </div>
                            <div class="ml-auto">
                                @if ($ready)
                                    <span class="badge badge-success" style="font-size:12px;">Connected</span>
                                @elseif ($integration->is_enabled)
                                    <span class="badge badge-warning" style="font-size:12px;">Needs config</span>
                                @else
                                    <span class="badge badge-secondary" style="font-size:12px;">Disabled</span>
                                @endif
                            </div>
                        </div>

                        <dl class="row mb-2" style="font-size:13px;">
                            <dt class="col-5 text-muted">Bridge URL</dt>
                            <dd class="col-7">
                                @if (filled($integration->app_url))
                                    <a href="{{ $integration->app_url }}" target="_blank">{{ $integration->app_url }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </dd>

                            <dt class="col-5 text-muted">API base</dt>
                            <dd class="col-7">
                                <code style="font-size:12px;">{{ $integration->api_base ?: '—' }}</code>
                            </dd>

                            <dt class="col-5 text-muted">Parcels created</dt>
                            <dd class="col-7"><strong>{{ number_format($count) }}</strong></dd>

                            <dt class="col-5 text-muted">Writeback token</dt>
                            <dd class="col-7">
                                @if (filled($integration->writeback_token))
                                    <code style="font-size:12px;">••••{{ substr($integration->writeback_token, -4) }}</code>
                                @else
                                    <span class="text-danger">not set</span>
                                @endif
                            </dd>
                        </dl>

                        <div class="mt-auto pt-2 d-flex">
                            @if (hasPermission('integrations_update'))
                                <a href="{{ route('integrations.edit', $integration->platform) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="fa fa-cog"></i> Configure
                                </a>
                            @endif
                            @if (filled($integration->app_url))
                                <a href="{{ $integration->app_url }}" target="_blank" class="btn btn-outline-secondary btn-sm ml-2">
                                    <i class="fa fa-external-link"></i> Open bridge
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 3PL (courier) integrations --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="h3 mb-1">3PL (Courier) Integrations</p>
                    <p class="text-muted mb-0">
                        Outbound courier handover. Credentials live in <code>.env</code> and apply across all tenants —
                        see <code>3PL.md</code> in the repo root for the current state and known issues.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($threePLs as $tpl)
            <div class="col-12 col-md-6 col-xl-4 d-flex">
                <div class="card flex-fill" style="border:1px solid #eef0f3;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3" style="width:56px;height:56px;display:flex;align-items:center;justify-content:center;background:#f7f7fb;border-radius:12px;">
                                <span style="font-weight:700;font-size:22px;color:#333;">
                                    {{ strtoupper(substr($tpl['name'], 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-0">{{ $tpl['name'] }}</h4>
                                <small class="text-muted">{{ $tpl['host'] }}</small>
                            </div>
                            <div class="ml-auto">
                                @if ($tpl['key_set'])
                                    <span class="badge badge-success" style="font-size:12px;">Connected</span>
                                @else
                                    <span class="badge badge-warning" style="font-size:12px;">Needs config</span>
                                @endif
                            </div>
                        </div>

                        <dl class="row mb-2" style="font-size:13px;">
                            <dt class="col-5 text-muted">API base</dt>
                            <dd class="col-7">
                                <code style="font-size:12px;">{{ $tpl['base_url'] ?: '—' }}</code>
                            </dd>

                            <dt class="col-5 text-muted">API key</dt>
                            <dd class="col-7">
                                @if ($tpl['key_set'])
                                    <code style="font-size:12px;">••••{{ $tpl['key_tail'] }}</code>
                                @else
                                    <span class="text-danger">not set</span>
                                @endif
                            </dd>

                            <dt class="col-5 text-muted">Parcels assigned</dt>
                            <dd class="col-7"><strong>{{ number_format($tpl['parcels']) }}</strong></dd>

                            @foreach ($tpl['extras'] ?? [] as $label => $value)
                                @if ($value !== null && $value !== '')
                                    <dt class="col-5 text-muted">{{ $label }}</dt>
                                    <dd class="col-7"><code style="font-size:12px;word-break:break-all;">{{ $value }}</code></dd>
                                @endif
                            @endforeach

                            <dt class="col-5 text-muted">Config source</dt>
                            <dd class="col-7"><code style="font-size:12px;">.env</code> / <code style="font-size:12px;">config/services.php</code></dd>
                        </dl>

                        <div class="mt-auto pt-2">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                3PL credentials are global (not per-tenant). Edit <code>.env</code> on the server to change.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
