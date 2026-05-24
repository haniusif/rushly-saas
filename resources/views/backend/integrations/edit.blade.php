@extends('backend.partials.master')
@section('title') {{ $setting->displayName() }} integration @endsection
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
                                <a href="{{ route('integrations.index') }}" class="breadcrumb-link">Integrations</a>
                            </li>
                            <li class="breadcrumb-item">
                                <span class="breadcrumb-link active">{{ $setting->displayName() }}</span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        @if ($logo = $setting->logoUrl())
                            <img src="{{ $logo }}" alt="{{ $setting->displayName() }}" style="width:42px;height:42px;margin-right:12px;border-radius:10px;">
                        @endif
                        <h3 class="mb-0">{{ $setting->displayName() }} integration</h3>
                    </div>

                    <form action="{{ route('integrations.update', $setting->platform) }}" method="POST">
                        @method('PUT')
                        @csrf

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_enabled" value="0">
                                <input type="checkbox" class="custom-control-input" id="is_enabled"
                                       name="is_enabled" value="1" {{ $setting->is_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_enabled">
                                    Enabled — parcel writeback to this platform is active
                                </label>
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-2">Bridge connection</h5>
                        <p class="text-muted" style="font-size:13px;">
                            These values override the equivalent <code>.env</code> entries
                            (<code>RUSHLY_{{ strtoupper($setting->platform) }}_APP_URL</code>,
                            <code>RUSHLY_{{ strtoupper($setting->platform) }}_WRITEBACK_TOKEN</code>).
                            Leave a field empty to fall back to <code>.env</code>.
                        </p>

                        <div class="form-group">
                            <label for="app_url">Bridge app URL</label>
                            <input type="url" name="app_url" id="app_url"
                                   class="form-control @error('app_url') is-invalid @enderror"
                                   placeholder="https://{{ $setting->platform }}.rushly.test"
                                   value="{{ old('app_url', $setting->app_url) }}">
                            @error('app_url') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">
                                Where rushly-saas POSTs parcel status updates to (<code>{URL}/internal/parcel-status</code>).
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="writeback_token">Writeback bearer token</label>
                            <input type="text" name="writeback_token" id="writeback_token"
                                   class="form-control @error('writeback_token') is-invalid @enderror"
                                   placeholder="32+ char random string"
                                   value="{{ old('writeback_token', $setting->writeback_token) }}">
                            @error('writeback_token') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">
                                Must match <code>RUSHLY_WRITEBACK_TOKEN</code> on the bridge app's side.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="api_base">Platform API base URL</label>
                            <input type="url" name="api_base" id="api_base"
                                   class="form-control @error('api_base') is-invalid @enderror"
                                   placeholder="https://api.{{ $setting->platform }}.{{ in_array($setting->platform, ['shopify', 'woocommerce']) ? 'com' : 'sa' }}"
                                   value="{{ old('api_base', $setting->api_base) }}">
                            @error('api_base') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">
                                Only used for reference; the bridge app owns the actual API client.
                            </small>
                        </div>

                        <hr>

                        <h5 class="mb-2">Defaults for parcels created via this platform</h5>
                        <p class="text-muted" style="font-size:13px;">
                            When a {{ $setting->displayName() }} order arrives without a city / category / delivery type,
                            these values are used. The bridge app falls back to these via
                            <code>config('services.{{ $setting->platform }}.default_*')</code>.
                        </p>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="default_city_id">Default city</label>
                                <select name="default_city_id" id="default_city_id" class="form-control">
                                    <option value="">— none —</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}"
                                                @selected(old('default_city_id', $setting->default_city_id) == $city->id)>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="default_category_id">Default delivery category</label>
                                <select name="default_category_id" id="default_category_id" class="form-control">
                                    <option value="">— none —</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                                @selected(old('default_category_id', $setting->default_category_id) == $cat->id)>
                                            {{ $cat->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label for="default_delivery_type_id">Default delivery type</label>
                                <select name="default_delivery_type_id" id="default_delivery_type_id" class="form-control">
                                    <option value="">— none —</option>
                                    @foreach ($deliveryTypes as $id => $key)
                                        <option value="{{ $id }}"
                                                @selected(old('default_delivery_type_id', $setting->default_delivery_type_id) == $id)>
                                            {{ __('deliveryType.'.$key) === 'deliveryType.'.$key ? str_replace('_', ' ', $key) : __('deliveryType.'.$key) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save
                            </button>
                            <a href="{{ route('integrations.index') }}" class="btn btn-outline-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>Where things live</h5>
                    <ul class="pl-3" style="font-size:13px;line-height:1.7;">
                        <li>Bridge app code: <code>rushly-{{ $setting->platform }}/</code></li>
                        <li>Link table: <code>{{ $setting->platform }}_orders</code></li>
                        <li>External parcel endpoint:
                            <code>POST /api/v10/external/{{ $setting->platform }}/parcel</code></li>
                        <li>Status writeback endpoint on bridge:
                            <code>POST /internal/parcel-status</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
