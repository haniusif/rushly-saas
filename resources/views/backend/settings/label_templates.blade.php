@extends('backend.partials.master')

@section('title', __('label_template.title'))

@section('mainContent')
<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-tags"></i> {{ __('label_template.title') }}</h5>
                    <span class="badge badge-info">{{ __('label_template.current') }}: {{ $current->label() }}</span>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('label_template.subtitle') }}</p>

                    <form method="POST" action="{{ route('label-templates.update-default') }}">
                        @csrf @method('PUT')
                        <div class="row">
                            @foreach ($templates as $tpl)
                                @php $isActive = $tpl === $current; @endphp
                                <div class="col-md-4 col-lg-3 mb-3">
                                    <label class="card h-100 {{ $isActive ? 'border-primary shadow-sm' : '' }}" style="cursor:pointer;">
                                        <div class="card-body p-3">
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" name="default_label_template" id="tpl-{{ $tpl->value }}"
                                                    value="{{ $tpl->value }}" class="custom-control-input"
                                                    {{ $isActive ? 'checked' : '' }}>
                                                <label class="custom-control-label font-weight-bold" for="tpl-{{ $tpl->value }}">
                                                    {{ $tpl->label() }}
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-2" style="min-height:54px;">{{ $tpl->description() }}</p>
                                            <small class="text-muted d-block">
                                                {{ __('label_template.size') }}: {{ $tpl->format()[0] }}×{{ $tpl->format()[1] }}mm
                                            </small>
                                            <a target="_blank" href="{{ route('label-templates.preview', $tpl->value) }}"
                                               class="btn btn-sm btn-outline-secondary mt-2">
                                                <i class="fa fa-eye"></i> {{ __('label_template.preview') }}
                                            </a>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ __('label_template.save_default') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><h6 class="mb-0">{{ __('label_template.merchant_overrides') }}</h6></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('label_template.merchant_overrides_hint') }}</p>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ __('label_template.merchant') }}</th>
                                    <th>{{ __('label_template.override') }}</th>
                                    <th style="width:120px;">{{ __('label_template.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($merchants as $m)
                                    <tr>
                                        <td>{{ $m->business_name ?: '#'.$m->id }}</td>
                                        <td>
                                            <form action="{{ route('label-templates.update-merchant', $m->id) }}" method="POST" class="d-inline-flex align-items-center">
                                                @csrf @method('PUT')
                                                <select name="label_template" class="form-control form-control-sm" style="min-width:180px;">
                                                    <option value="">— {{ __('label_template.use_default') }} —</option>
                                                    @foreach ($templates as $tpl)
                                                        <option value="{{ $tpl->value }}" {{ $m->label_template === $tpl->value ? 'selected' : '' }}>
                                                            {{ $tpl->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary ml-2">{{ __('label_template.save') }}</button>
                                            </form>
                                        </td>
                                        <td><span class="badge badge-secondary">{{ $m->label_template ?: __('label_template.default_uc') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center">{{ $merchants->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
