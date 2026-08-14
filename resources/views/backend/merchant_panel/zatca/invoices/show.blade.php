@extends('backend.partials.master')

@section('title', $invoice->invoice_number)

@section('mainContent')
<section class="section">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa fa-file-invoice"></i> {{ $invoice->invoice_number }}</h5>
                    @php $st = $invoice->statusEnum(); @endphp
                    <span class="badge badge-{{ $st->color() }}">{{ $st->label() }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('zatca.type') }}</dt>
                        <dd class="col-sm-8">{{ ucfirst($invoice->invoice_type) }}</dd>

                        <dt class="col-sm-4">{{ __('zatca.uuid') }}</dt>
                        <dd class="col-sm-8"><code>{{ $invoice->uuid }}</code></dd>

                        <dt class="col-sm-4">{{ __('zatca.issued_at') }}</dt>
                        <dd class="col-sm-8">{{ optional($invoice->issued_at)->format('Y-m-d H:i:s') }} UTC</dd>

                        <dt class="col-sm-4">{{ __('zatca.buyer') }}</dt>
                        <dd class="col-sm-8">{{ $invoice->buyer_name ?: '—' }}</dd>

                        @if ($invoice->buyer_vat_number)
                            <dt class="col-sm-4">{{ __('zatca.buyer_vat') }}</dt>
                            <dd class="col-sm-8">{{ $invoice->buyer_vat_number }}</dd>
                        @endif

                        <dt class="col-sm-4">{{ __('zatca.subtotal') }}</dt>
                        <dd class="col-sm-8">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</dd>

                        <dt class="col-sm-4">{{ __('zatca.vat_amount') }} ({{ rtrim(rtrim(number_format($invoice->vat_rate, 2), '0'), '.') }}%)</dt>
                        <dd class="col-sm-8">{{ number_format($invoice->vat_amount, 2) }} {{ $invoice->currency }}</dd>

                        <dt class="col-sm-4"><strong>{{ __('zatca.total_inclusive') }}</strong></dt>
                        <dd class="col-sm-8"><strong>{{ number_format($invoice->total_inclusive, 2) }} {{ $invoice->currency }}</strong></dd>

                        <dt class="col-sm-4">{{ __('zatca.hash') }}</dt>
                        <dd class="col-sm-8"><code class="small">{{ $invoice->hash }}</code></dd>

                        @if ($invoice->error_message)
                            <dt class="col-sm-4 text-danger">{{ __('zatca.error') }}</dt>
                            <dd class="col-sm-8 text-danger">{{ $invoice->error_message }}</dd>
                        @endif
                    </dl>

                    <div class="mt-4">
                        <h6>{{ __('zatca.tlv_payload') }}</h6>
                        <textarea class="form-control" rows="3" readonly>{{ $invoice->qr_payload }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('merchant.panel.zatca.invoices.pdf', $invoice->id) }}" class="btn btn-secondary">
                        <i class="fa fa-file-pdf"></i> {{ __('zatca.download_pdf') }}
                    </a>
                    <a href="{{ route('merchant.panel.zatca.invoices.qr', $invoice->id) }}" class="btn btn-secondary" target="_blank">
                        <i class="fa fa-qrcode"></i> {{ __('zatca.download_qr') }}
                    </a>
                    <form action="{{ route('merchant.panel.zatca.invoices.regenerate', $invoice->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-warning"><i class="fa fa-sync"></i> {{ __('zatca.regenerate') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">{{ __('zatca.qr_preview') }}</div>
                <div class="card-body text-center">
                    @if ($qrSvg)
                        <div style="max-width:240px;margin:0 auto;">{!! $qrSvg !!}</div>
                        <small class="text-muted d-block mt-2">{{ __('zatca.qr_scannable_hint') }}</small>
                    @else
                        <div class="text-muted py-5">{{ __('zatca.no_qr') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
