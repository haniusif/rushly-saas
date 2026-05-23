{{--
    Dual badges for a parcel (NDR + Abnormal).
    Usage:
        @include('backend.partials._parcel_flags', ['parcel_id' => $parcel->id])
        @include('backend.partials._parcel_flags', ['parcel_id' => $row->id, 'compact' => true])

    Both queries hit indexed columns (parcel_id) so this is cheap per row.
--}}
@php
    $compact = $compact ?? false;
    $openNdr = \App\Models\Backend\Ndr::companywise()
        ->where('parcel_id', $parcel_id)
        ->whereIn('status', ['open', 'in_progress'])
        ->first();
    $openAbn = \App\Models\Backend\AbnormalShipment::companywise()
        ->where('parcel_id', $parcel_id)
        ->whereIn('status', ['open', 'investigating'])
        ->first();
@endphp

@if ($openNdr || $openAbn)
    <span class="parcel-flags" style="display:inline-flex; gap:4px; align-items:center;">
        @if ($openAbn)
            @php
                $bg = match ($openAbn->severity) {
                    'critical' => '#1f2937',
                    'danger'   => '#fee2e2',
                    default    => '#fef3c7',
                };
                $fg = $openAbn->severity === 'critical' ? '#fcd34d' : ($openAbn->severity === 'danger' ? '#991b1b' : '#92400e');
            @endphp
            <a href="{{ route('abnormal.show', $openAbn->id) }}"
               title="{{ __('Abnormal') }}: {{ $openAbn->stale_days }} {{ __('days stale') }}"
               style="background: {{ $bg }}; color: {{ $fg }}; padding:2px 7px; border-radius:999px; font-size:10.5px; font-weight:700; text-decoration:none; letter-spacing:0.04em;">
                ⚠ ABNORMAL{{ $compact ? '' : ' '.$openAbn->stale_days.'d' }}
            </a>
        @endif
        @if ($openNdr)
            <a href="{{ route('ndr.show', $openNdr->id) }}"
               title="{{ __('NDR') }}: {{ __('Attempt') }} {{ $openNdr->attempt_number }}/3"
               style="background:#fee2e2; color:#991b1b; padding:2px 7px; border-radius:999px; font-size:10.5px; font-weight:700; text-decoration:none; letter-spacing:0.04em;">
                🔴 NDR{{ $compact ? '' : ' '.$openNdr->attempt_number.'/3' }}
            </a>
        @endif
    </span>
@endif
