@php
    use App\Enums\ParcelStatus;

    $waLink = function ($phone) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return $digits ? 'https://wa.me/' . $digits : null;
    };

    $statusColor = function ($status) {
        $success = [ParcelStatus::DELIVERED, ParcelStatus::DELIVER, ParcelStatus::PARTIAL_DELIVERED, ParcelStatus::RETURN_RECEIVED_BY_MERCHANT, ParcelStatus::RECEIVED_BY_PICKUP_MAN];
        $danger  = [ParcelStatus::CANCELLED, ParcelStatus::RETURN_TO_COURIER, ParcelStatus::RETURNED_MERCHANT, ParcelStatus::NDR_CREATED, ParcelStatus::ABNORMAL];
        $warning = [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::RECEIVED_WAREHOUSE, ParcelStatus::RECEIVED_BY_HUB];
        if (in_array($status, $success)) return 'success';
        if (in_array($status, $danger))  return 'danger';
        if (in_array($status, $warning)) return 'warning';
        return 'info';
    };

    $senderName  = $parcel->merchant->business_name ?? ($parcel->merchantShop->name ?? '—');
    $senderPhone = $parcel->pickup_phone ?: ($parcel->merchant->user->mobile ?? null);
    $currency    = settings()->currency ?? '';
@endphp

<div class="pkg-track">

    {{-- Action toolbar --}}
    @php
        $statusMenu  = trim(parcelStatus($parcel));
        $canEdit     = hasPermission('parcel_update');
        $canStatus   = hasPermission('parcel_status_update');
        $canPostpone = $canStatus && in_array($parcel->status, [ParcelStatus::DELIVERY_MAN_ASSIGN, ParcelStatus::DELIVERY_RE_SCHEDULE]);
    @endphp
    <div class="pkg-actions">
        @if($canEdit)
            <a href="{{ route('parcel.edit', $parcel->id) }}" class="btn btn-sm pkg-actions__btn">
                <i class="fas fa-pencil-alt"></i> {{ __('Edit') }}
            </a>
        @endif

        @if($canStatus && $statusMenu !== '')
            <div class="pkg-actions__dd">
                <button type="button" class="btn btn-sm pkg-actions__btn pkg-ofc-statusbtn">
                    <i class="fas fa-exchange-alt"></i> {{ __('Change Status') }} <i class="fas fa-caret-down ml-1"></i>
                </button>
                <div class="dropdown-menu pkg-ofc-statusmenu">
                    {!! $statusMenu !!}
                </div>
            </div>
        @endif

        @if($canPostpone)
            <button type="button" class="btn btn-sm pkg-actions__btn pkg-ofc-postpone" data-parcel="{{ $parcel->id }}">
                <i class="fas fa-clock"></i> {{ __('Postpone') }}
            </button>
        @endif

        <a href="{{ route('parcel.logs', $parcel->id) }}" target="_blank" rel="noopener" class="btn btn-sm pkg-actions__btn">
            {{ __('Webhooks Logs') }}
        </a>

        <a href="{{ route('parcel.print-label', $parcel->id) }}" target="_blank" rel="noopener" class="btn btn-sm pkg-actions__btn pkg-actions__btn--print">
            <i class="fas fa-print"></i> {{ __('Print') }}
        </a>

        <a href="{{ route('parcel.print', $parcel->id) }}" target="_blank" rel="noopener" class="btn btn-sm pkg-actions__btn pkg-actions__btn--print">
            <i class="fas fa-print"></i> {{ __('Print With tracking') }}
        </a>
    </div>

    <div class="row g-0">

        {{-- LEFT: parties + attachment --}}
        <div class="col-lg-5 pkg-track__left">

            {{-- Sender --}}
            <div class="pkg-party">
                <div class="pkg-party__icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="pkg-party__body">
                    <div class="pkg-party__title">{{ __('levels.sender_info') }}</div>
                    <div class="pkg-party__name">{{ $senderName }}</div>
                    @if($parcel->pickup_address)
                        <div class="pkg-party__addr">{{ $parcel->pickup_address }}</div>
                    @endif
                    @if($senderPhone)
                        <div class="pkg-party__phone">
                            <span>{{ $senderPhone }}</span>
                            @if($waLink($senderPhone))
                                <a href="{{ $waLink($senderPhone) }}" target="_blank" rel="noopener" class="pkg-wa" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recipient --}}
            <div class="pkg-party">
                <div class="pkg-party__icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="pkg-party__body">
                    <div class="pkg-party__title">{{ __('levels.recipient_info') }}</div>
                    <div class="pkg-party__name">{{ $parcel->customer_name }}</div>
                    @if($parcel->customer_address)
                        <div class="pkg-party__addr">{{ $parcel->customer_address }}</div>
                    @endif
                    @if($parcel->customer_phone)
                        <div class="pkg-party__phone">
                            <span>{{ $parcel->customer_phone }}</span>
                            @if($waLink($parcel->customer_phone))
                                <a href="{{ $waLink($parcel->customer_phone) }}" target="_blank" rel="noopener" class="pkg-wa" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Attachment --}}
            @php
                $attachments = [];
                foreach ($parcel->images ?? [] as $img) {
                    $attachments[] = [
                        'url'     => $img->image_url,
                        'label'   => ucfirst(str_replace('_', ' ', $img->type)),
                        'date'    => $img->created_at,
                        'contain' => false,
                    ];
                }
                foreach ($parcelevents as $ev) {
                    if ($ev->delivered_image) {
                        $attachments[] = [
                            'url'     => static_asset($ev->delivered_image),
                            'label'   => __('Delivered Photo'),
                            'date'    => $ev->created_at,
                            'contain' => false,
                        ];
                    }
                    if ($ev->signature_image) {
                        $attachments[] = [
                            'url'     => static_asset($ev->signature_image),
                            'label'   => __('Signature'),
                            'date'    => $ev->created_at,
                            'contain' => true,
                        ];
                    }
                }
            @endphp
            <div class="pkg-party pkg-party--last">
                <div class="pkg-party__icon"><i class="fas fa-paperclip"></i></div>
                <div class="pkg-party__body">
                    <div class="pkg-party__title">{{ __('levels.attachment') }}</div>
                    @if(count($attachments) > 0)
                        <div class="pkg-gallery">
                            @foreach($attachments as $att)
                                <a href="{{ $att['url'] }}" target="_blank" rel="noopener" class="pkg-gallery__item" title="{{ $att['label'] }}">
                                    <img src="{{ $att['url'] }}" alt="{{ $att['label'] }}" class="{{ $att['contain'] ? 'pkg-gallery__img--contain' : '' }}">
                                    <span class="pkg-gallery__cap">
                                        {{ $att['label'] }}
                                        @if($att['date'])
                                            <br>{{ \Carbon\Carbon::parse($att['date'])->format('d/m/Y h:i A') }}
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="pkg-empty">{{ __('No attachments') }}</div>
                    @endif
                </div>
            </div>

        </div>

        {{-- RIGHT: detail card + timeline --}}
        <div class="col-lg-7 pkg-track__right">

            {{-- Detail card --}}
            <div class="pkg-detail">
                <div class="pkg-detail__head">
                    <div class="pkg-detail__box"><i class="fas fa-box"></i></div>
                    <div class="pkg-detail__barcode">{!! $parcel->barcode_print !!}</div>
                </div>

                <div class="pkg-detail__grid">
                    <div class="pkg-row"><span>{{ __('Barcode') }}</span><strong>#{{ $parcel->tracking_id }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.booking_date') }}</span><strong>{{ $parcel->created_at ? \Carbon\Carbon::parse($parcel->created_at)->format('d-m-Y h:i:s A') : '—' }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.cod') }}</span><strong>{{ number_format((float) $parcel->cod_amount, 2) }} {{ $currency }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.price') }}</span><strong>{{ number_format((float) $parcel->selling_price, 2) }} {{ $currency }}</strong></div>
                    <div class="pkg-row"><span>{{ __('invoice.invoice') }}</span><strong>{{ $parcel->invoice_no ?: '—' }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.weight') }}</span><strong>{{ $parcel->weight }} {{ optional($parcel->deliveryCategory)->title }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.delivery_type') }}</span><strong>{{ $parcel->delivery_type_name }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.city') }}</span><strong>{{ optional($parcel->city)->name ?? '—' }}</strong></div>
                    <div class="pkg-row"><span>{{ __('levels.area') }}</span><strong>{{ optional($parcel->area)->name ?? '—' }}</strong></div>
                    <div class="pkg-row pkg-row--wide"><span>{{ __('levels.note') }}</span><strong>{{ $parcel->note ?: '—' }}</strong></div>
                </div>

                <div class="pkg-detail__status">
                    <span>{{ __('levels.status') }}</span>
                    <span class="badge badge-pill badge-{{ $statusColor($parcel->status) }} pkg-status-badge">{{ __('parcelStatus.' . $parcel->status) }}</span>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="pkg-timeline">
                @php $lastDate = null; @endphp

                @foreach($parcelevents as $event)
                    @php
                        $eventDate = $event->created_at ? \Carbon\Carbon::parse($event->created_at)->format('d/m/Y') : null;
                        $actor = optional($event->user)->name
                            ?? optional(optional($event->deliveryMan)->user)->name
                            ?? optional(optional($event->pickupman)->user)->name
                            ?? null;
                        $hubName = optional($event->hub)->name;
                        $color   = $event->cancel_parcel_id ? 'danger' : $statusColor($event->parcel_status);
                    @endphp

                    @if($eventDate && $eventDate !== $lastDate)
                        <div class="pkg-tl__date">{{ $eventDate }}</div>
                        @php $lastDate = $eventDate; @endphp
                    @endif

                    <div class="pkg-tl__item">
                        <div class="pkg-tl__meta">
                            @if($actor)<div class="pkg-tl__actor">{{ $actor }}</div>@endif
                            @if($hubName)<div class="pkg-tl__hub">{{ $hubName }}</div>@endif
                            <div class="pkg-tl__time">{{ $event->created_at ? \Carbon\Carbon::parse($event->created_at)->format('h:i:s A') : '' }}</div>
                        </div>
                        <div class="pkg-tl__line"><span class="pkg-tl__dot bg-{{ $color }}"></span></div>
                        <div class="pkg-tl__content">
                            <div class="pkg-tl__bubble">
                                {{ __('parcelStatus.' . $event->parcel_status) }}
                                @if($event->note)<div class="pkg-tl__note">{{ $event->note }}</div>@endif
                            </div>
                            <span class="badge badge-pill badge-{{ $color }}">{{ __('parcelStatus.' . $event->parcel_status) }}</span>
                        </div>
                    </div>
                @endforeach

                {{-- Shipment creation (first event, oldest — shown at the bottom) --}}
                @php $createdDate = $parcel->created_at ? \Carbon\Carbon::parse($parcel->created_at)->format('d/m/Y') : null; @endphp
                @if($createdDate && $createdDate !== $lastDate)
                    <div class="pkg-tl__date">{{ $createdDate }}</div>
                @endif
                <div class="pkg-tl__item">
                    <div class="pkg-tl__meta">
                        <div class="pkg-tl__actor">{{ $senderName }}</div>
                        <div class="pkg-tl__time">{{ $parcel->created_at ? \Carbon\Carbon::parse($parcel->created_at)->format('h:i:s A') : '' }}</div>
                    </div>
                    <div class="pkg-tl__line"><span class="pkg-tl__dot bg-secondary"></span></div>
                    <div class="pkg-tl__content">
                        <div class="pkg-tl__bubble">{{ __('parcel.parcel_create') }}</div>
                        <span class="badge badge-pill badge-secondary">{{ __('parcel.parcel_create') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
