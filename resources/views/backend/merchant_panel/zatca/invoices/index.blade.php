@extends('backend.partials.master')

@section('title', __('zatca.invoices_title'))

@section('mainContent')
<section class="section">
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-white" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
                <div class="card-body p-3">
                    <small class="opacity-75">{{ __('zatca.stat_total') }}</small>
                    <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background:linear-gradient(135deg,#10b981,#047857)">
                <div class="card-body p-3">
                    <small class="opacity-75">{{ __('zatca.stat_generated') }}</small>
                    <h3 class="mb-0">{{ number_format($stats['generated']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
                <div class="card-body p-3">
                    <small class="opacity-75">{{ __('zatca.stat_failed') }}</small>
                    <h3 class="mb-0">{{ number_format($stats['failed']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">
                <div class="card-body p-3">
                    <small class="opacity-75">{{ __('zatca.stat_vat') }}</small>
                    <h3 class="mb-0">{{ number_format($stats['vat_amount'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>{{ __('zatca.invoices_title') }}</strong></div>
        <div class="card-body">
            <form method="GET" class="form-row mb-3">
                <div class="form-group col-md-3">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                        placeholder="{{ __('zatca.search') }}">
                </div>
                <div class="form-group col-md-2">
                    <select name="status" class="form-control">
                        <option value="">{{ __('zatca.all_statuses') }}</option>
                        @foreach (\App\Enums\Zatca\ZatcaInvoiceStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <select name="type" class="form-control">
                        <option value="">{{ __('zatca.all_types') }}</option>
                        @foreach (\App\Enums\Zatca\ZatcaInvoiceType::options() as $val => $label)
                            <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-primary btn-block"><i class="fa fa-filter"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ __('zatca.invoice_number') }}</th>
                            <th>{{ __('zatca.type') }}</th>
                            <th>{{ __('zatca.issued_at') }}</th>
                            <th class="text-right">{{ __('zatca.subtotal') }}</th>
                            <th class="text-right">{{ __('zatca.vat_amount') }}</th>
                            <th class="text-right">{{ __('zatca.total_inclusive') }}</th>
                            <th>{{ __('zatca.status') }}</th>
                            <th>{{ __('zatca.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $z)
                            <tr>
                                <td><code>{{ $z->invoice_number }}</code></td>
                                <td><span class="badge badge-info">{{ ucfirst($z->invoice_type) }}</span></td>
                                <td>{{ optional($z->issued_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-right">{{ number_format($z->subtotal, 2) }}</td>
                                <td class="text-right">{{ number_format($z->vat_amount, 2) }}</td>
                                <td class="text-right"><strong>{{ number_format($z->total_inclusive, 2) }} {{ $z->currency }}</strong></td>
                                <td>
                                    @php $st = $z->statusEnum(); @endphp
                                    <span class="badge badge-{{ $st->color() }}">{{ $st->label() }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('merchant.panel.zatca.invoices.show', $z->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('merchant.panel.zatca.invoices.pdf', $z->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-file-pdf"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">{{ __('zatca.no_rows') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $invoices->withQueryString()->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
