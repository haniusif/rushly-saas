@extends('backend.partials.master')
@section('title')
    {{ __('ndr.title') }} #{{ $ndr->id }}
@endsection

@push('styles')
<style>
  .attempt-badge { padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
  .att-1 { background: #fef3c7; color: #92400e; }
  .att-2 { background: #ffedd5; color: #9a3412; }
  .att-3 { background: #fee2e2; color: #991b1b; }
  .nstatus { padding: 3px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; }
  .ns-open        { background: #fff7ed; color: #9a3412; }
  .ns-in_progress { background: #e0f2fe; color: #075985; }
  .ns-resolved    { background: #dcfce7; color: #166534; }
  .ns-returned    { background: #fee2e2; color: #991b1b; }
  .timeline { border-left: 2px solid #e5e7eb; padding-left: 16px; }
  .timeline .tl-item { position: relative; margin-bottom: 14px; }
  .timeline .tl-item::before { content: ""; width: 10px; height: 10px; background: #6366f1; border-radius: 50%; position: absolute; left: -22px; top: 6px; }
  .info-row { display: flex; padding: 8px 0; border-bottom: 1px dashed #e5e7eb; }
  .info-row dt { width: 40%; color: #6b7280; font-size: 13px; }
  .info-row dd { width: 60%; margin: 0; font-size: 14px; font-weight: 500; }
</style>
@endpush

@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ndr.index') }}" class="breadcrumb-link">{{ __('ndr.title') }}</a></li>
                            <li class="breadcrumb-item active">#{{ $ndr->id }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- LEFT: parcel + customer + NDR core --}}
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">{{ __('ndr.title') }} #{{ $ndr->id }}</h5>
                    <div>
                        <span class="attempt-badge att-{{ (int) $ndr->attempt_number }}">{{ __('ndr.attempt') }} {{ $ndr->attempt_number }}/3</span>
                        <span class="nstatus ns-{{ $ndr->status }} ml-1">{{ ucwords(str_replace('_',' ',$ndr->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small">{{ __('ndr.parcel') }}</h6>
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('ndr.tracking_id') }}</dt><dd>{{ optional($ndr->parcel)->tracking_id }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.merchant') }}</dt><dd>{{ optional($ndr->parcel?->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.customer') }}</dt><dd>{{ optional($ndr->parcel)->customer_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.phone') }}</dt><dd>{{ optional($ndr->parcel)->customer_phone ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('levels.address') }}</dt><dd>{{ optional($ndr->parcel)->customer_address ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('ndr.cash_collection') }}</dt><dd>{{ number_format((float) (optional($ndr->parcel)->cash_collection ?? 0), 2) }}</dd></div>
                    </dl>

                    <h6 class="text-uppercase text-muted small mt-4">{{ __('ndr.failure') }}</h6>
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('ndr.reason') }}</dt><dd><strong>{{ ucwords(str_replace('_',' ',$ndr->failure_reason)) }}</strong></dd></div>
                        <div class="info-row"><dt>{{ __('levels.deliveryman') }}</dt><dd>{{ optional($ndr->deliveryman)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('ndr.created_by') }}</dt><dd>{{ optional($ndr->createdBy)->name ?? '—' }} <small class="text-muted">{{ $ndr->created_at?->diffForHumans() }}</small></dd></div>
                        @if ($ndr->next_attempt_date)
                            <div class="info-row"><dt>{{ __('ndr.next_attempt') }}</dt><dd>{{ $ndr->next_attempt_date->format('Y-m-d') }}</dd></div>
                        @endif
                        @if ($ndr->driver_notes)
                            <div class="info-row"><dt>{{ __('ndr.driver_notes') }}</dt><dd>{{ $ndr->driver_notes }}</dd></div>
                        @endif
                        @if ($ndr->driver_photo)
                            <div class="info-row"><dt>{{ __('ndr.driver_photo') }}</dt><dd><a href="{{ url($ndr->driver_photo) }}" target="_blank"><img src="{{ url($ndr->driver_photo) }}" style="max-width:220px;border-radius:8px;" /></a></dd></div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- RIGHT: action panel + timeline --}}
        <div class="col-lg-5">
            @if ($ndr->status !== 'resolved' && $ndr->status !== 'returned')
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('ndr.take_action') }}</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ndr.action', $ndr->id) }}">
                            @csrf @method('PUT')
                            <div class="form-group">
                                <label class="d-block mb-2">{{ __('ndr.action') }}</label>
                                <select name="action_taken" class="form-control" id="actionSelect" required>
                                    <option value="">{{ __('ndr.choose_action') }}</option>
                                    <option value="reschedule">{{ __('ndr.reschedule_action') }}</option>
                                    <option value="return_to_merchant">{{ __('ndr.return_to_merchant_action') }}</option>
                                    <option value="transfer_hub">{{ __('ndr.transfer_hub_action') }}</option>
                                    <option value="escalate">{{ __('ndr.escalate_action') }}</option>
                                </select>
                            </div>

                            <div class="form-group d-none" id="dateGroup">
                                <label>{{ __('ndr.next_attempt_date') }}</label>
                                <input type="date" name="next_attempt_date" class="form-control" />
                            </div>

                            <div class="form-group d-none" id="hubGroup">
                                <label>{{ __('ndr.target_hub') }}</label>
                                <select name="hub_id" class="form-control">
                                    <option value="">—</option>
                                    @foreach ($hubs as $hub)
                                        <option value="{{ $hub->id }}">{{ $hub->name ?? ('#'.$hub->id) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-primary btn-block" type="submit">{{ __('ndr.apply_action') }}</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('ndr.resolve', $ndr->id) }}" class="mb-3">
                    @csrf @method('PUT')
                    <button class="btn btn-success btn-block">{{ __('ndr.mark_resolved') }}</button>
                </form>
            @endif

            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('ndr.history') }}</h6></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="tl-item">
                            <strong>{{ __('ndr.ndr_created') }}</strong>
                            <div class="small text-muted">{{ $ndr->created_at?->toDateTimeString() }} — {{ optional($ndr->createdBy)->name }}</div>
                        </div>
                        @if ($ndr->action_taken)
                            <div class="tl-item">
                                <strong>{{ __('ndr.action') }}: {{ ucwords(str_replace('_',' ',$ndr->action_taken)) }}</strong>
                                <div class="small text-muted">{{ $ndr->updated_at?->toDateTimeString() }}</div>
                            </div>
                        @endif
                        @if ($ndr->resolved_at)
                            <div class="tl-item">
                                <strong>{{ __('ndr.resolved') }}</strong>
                                <div class="small text-muted">{{ $ndr->resolved_at?->toDateTimeString() }} — {{ optional($ndr->resolvedBy)->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function ($) {
  function toggle() {
    var v = $('#actionSelect').val();
    $('#dateGroup').toggleClass('d-none', v !== 'reschedule');
    $('#hubGroup').toggleClass('d-none', v !== 'transfer_hub');
  }
  $('#actionSelect').on('change', toggle);
  toggle();
})(jQuery);
</script>
@endpush
@endsection
