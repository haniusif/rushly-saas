@extends('backend.partials.master')
@section('title')
    {{ __('NDR') }} #{{ $ndr->id }}
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
                            <li class="breadcrumb-item"><a href="{{ route('ndr.index') }}" class="breadcrumb-link">{{ __('NDR') }}</a></li>
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
                    <h5 class="mb-0">{{ __('NDR') }} #{{ $ndr->id }}</h5>
                    <div>
                        <span class="attempt-badge att-{{ (int) $ndr->attempt_number }}">{{ __('Attempt') }} {{ $ndr->attempt_number }}/3</span>
                        <span class="nstatus ns-{{ $ndr->status }} ml-1">{{ ucwords(str_replace('_',' ',$ndr->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small">{{ __('Parcel') }}</h6>
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Tracking ID') }}</dt><dd>{{ optional($ndr->parcel)->tracking_id }}</dd></div>
                        <div class="info-row"><dt>{{ __('Merchant') }}</dt><dd>{{ optional($ndr->parcel?->merchant)->business_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Customer') }}</dt><dd>{{ optional($ndr->parcel)->customer_name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Phone') }}</dt><dd>{{ optional($ndr->parcel)->customer_phone ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Address') }}</dt><dd>{{ optional($ndr->parcel)->customer_address ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Cash Collection') }}</dt><dd>{{ number_format((float) (optional($ndr->parcel)->cash_collection ?? 0), 2) }}</dd></div>
                    </dl>

                    <h6 class="text-uppercase text-muted small mt-4">{{ __('Failure') }}</h6>
                    <dl class="m-0">
                        <div class="info-row"><dt>{{ __('Reason') }}</dt><dd><strong>{{ ucwords(str_replace('_',' ',$ndr->failure_reason)) }}</strong></dd></div>
                        <div class="info-row"><dt>{{ __('Deliveryman') }}</dt><dd>{{ optional($ndr->deliveryman)->name ?? '—' }}</dd></div>
                        <div class="info-row"><dt>{{ __('Created By') }}</dt><dd>{{ optional($ndr->createdBy)->name ?? '—' }} <small class="text-muted">{{ $ndr->created_at?->diffForHumans() }}</small></dd></div>
                        @if ($ndr->next_attempt_date)
                            <div class="info-row"><dt>{{ __('Next Attempt') }}</dt><dd>{{ $ndr->next_attempt_date->format('Y-m-d') }}</dd></div>
                        @endif
                        @if ($ndr->driver_notes)
                            <div class="info-row"><dt>{{ __('Driver Notes') }}</dt><dd>{{ $ndr->driver_notes }}</dd></div>
                        @endif
                        @if ($ndr->driver_photo)
                            <div class="info-row"><dt>{{ __('Driver Photo') }}</dt><dd><a href="{{ url($ndr->driver_photo) }}" target="_blank"><img src="{{ url($ndr->driver_photo) }}" style="max-width:220px;border-radius:8px;" /></a></dd></div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- RIGHT: action panel + timeline --}}
        <div class="col-lg-5">
            @if ($ndr->status !== 'resolved' && $ndr->status !== 'returned')
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('Take Action') }}</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ndr.action', $ndr->id) }}">
                            @csrf @method('PUT')
                            <div class="form-group">
                                <label class="d-block mb-2">{{ __('Action') }}</label>
                                <select name="action_taken" class="form-control" id="actionSelect" required>
                                    <option value="">{{ __('Choose action…') }}</option>
                                    <option value="reschedule">{{ __('Reschedule (set next attempt)') }}</option>
                                    <option value="return_to_merchant">{{ __('Return to Merchant') }}</option>
                                    <option value="transfer_hub">{{ __('Transfer to Hub') }}</option>
                                    <option value="escalate">{{ __('Escalate to supervisor') }}</option>
                                </select>
                            </div>

                            <div class="form-group d-none" id="dateGroup">
                                <label>{{ __('Next attempt date') }}</label>
                                <input type="date" name="next_attempt_date" class="form-control" />
                            </div>

                            <div class="form-group d-none" id="hubGroup">
                                <label>{{ __('Target hub') }}</label>
                                <select name="hub_id" class="form-control">
                                    <option value="">—</option>
                                    @foreach ($hubs as $hub)
                                        <option value="{{ $hub->id }}">{{ $hub->name ?? ('#'.$hub->id) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button class="btn btn-primary btn-block" type="submit">{{ __('Apply Action') }}</button>
                        </form>
                    </div>
                </div>

                <form method="POST" action="{{ route('ndr.resolve', $ndr->id) }}" class="mb-3">
                    @csrf @method('PUT')
                    <button class="btn btn-success btn-block">{{ __('Mark Resolved') }}</button>
                </form>
            @endif

            <div class="card">
                <div class="card-header"><h6 class="mb-0">{{ __('History') }}</h6></div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="tl-item">
                            <strong>{{ __('NDR created') }}</strong>
                            <div class="small text-muted">{{ $ndr->created_at?->toDateTimeString() }} — {{ optional($ndr->createdBy)->name }}</div>
                        </div>
                        @if ($ndr->action_taken)
                            <div class="tl-item">
                                <strong>{{ __('Action') }}: {{ ucwords(str_replace('_',' ',$ndr->action_taken)) }}</strong>
                                <div class="small text-muted">{{ $ndr->updated_at?->toDateTimeString() }}</div>
                            </div>
                        @endif
                        @if ($ndr->resolved_at)
                            <div class="tl-item">
                                <strong>{{ __('Resolved') }}</strong>
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
