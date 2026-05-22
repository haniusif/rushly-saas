<div class="p-3">
  <!-- Tabs -->
  <ul class="nav nav-tabs" id="parcelDetailsTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab" aria-controls="info" aria-selected="true">
        📦 {{ __('Basic Info') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="p3pl-tab" data-toggle="tab" href="#p3pl" role="tab" aria-controls="p3pl" aria-selected="false">
        🚚 {{ __('3PL History') }}
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" id="events-tab" data-toggle="tab" href="#events" role="tab" aria-controls="events" aria-selected="false">
        📜 {{ __('Status Timeline') }}
      </a>
    </li>
    @if(isset($pandaTracking) && $pandaTracking)
      <li class="nav-item">
        <a class="nav-link" id="panda-tab" data-toggle="tab" href="#panda" role="tab" aria-controls="panda" aria-selected="false">
          🐼 {{ __('Panda Tracking') }}
        </a>
      </li>
      
        <li class="nav-item">
        <a class="nav-link" id="awb_pdf-tab" data-toggle="tab" href="#awb_pdf" role="tab" aria-controls="awb_pdf" aria-selected="false">
           {{ __('AWB') }}
        </a>
      </li>
      
      
    @endif
  </ul>

  <div class="tab-content border border-top-0 p-3" id="parcelDetailsTabsContent">

    {{-- 📦 Basic Info --}}
    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <tbody>
            <tr>
              <th style="width:220px">{{ __('Tracking ID') }}</th>
              <td colspan="2" >{!! $parcel->barcode_print ?? '' !!} {{ $parcel->tracking_id }}</td>
            </tr>
            
           
            
            <tr>
              <th>{{ __('Current Status') }}</th>
              <td colspan="2"  >
                 {!! $parcel->parcel_status !!}
                <small class="d-block text-muted mt-1">
                  {{ __('parcel.updated_on') }}: {{ optional($parcel->updated_at)->format('Y-m-d h:i:s A') }}
                </small>
              </td>
            </tr>
            <tr>
              <th>{{ __('Customer') }}</th>
              <td colspan="2"  >
                {{ $parcel->customer_name }} - {{ $parcel->customer_phone }}<br>
                <small class="text-muted">{{ $parcel->customer_address }}</small>
              </td>
            </tr>
            <tr>
              <th>{{ __('City / Area') }}</th>
              <td colspan="2"  >{{ optional($parcel->city)->name ?? optional($parcel->city)->en_name ?? '-' }} / {{ optional($parcel->area)->name ?? optional($parcel->area)->en_name ?? '-' }}</td>
            </tr>
            <tr>
              <th>{{ __('Merchant') }}</th>
              <td colspan="2"  >
                {{ optional($parcel->merchant)->business_name ?? '-' }}<br>
                <small>{{ optional(optional($parcel->merchant)->user)->mobile }}</small><br>
                <small>{{ optional($parcel->merchant)->address }}</small>
              </td>
            </tr>
            <tr>
              <th>{{ __('Financials') }}</th>
              <td colspan="2"  >
                {{ __('levels.cod') }}:
                <b>{{ settings()->currency ?? '' }}{{ number_format($parcel->cash_collection ?? 0, 2) }}</b><br>
                {{ __('levels.total_delivery_amount') }}:
                {{ settings()->currency ?? '' }}{{ number_format($parcel->total_delivery_amount ?? 0, 2) }}<br>
                {{ __('levels.vat_amount') }}:
                {{ settings()->currency ?? '' }}{{ number_format($parcel->vat_amount ?? 0, 2) }}<br>
                {{ __('levels.current_payable') }}:
                <b>{{ settings()->currency ?? '' }}{{ number_format($parcel->current_payable ?? 0, 2) }}</b>
              </td>
            </tr>
            <tr>
              <th>{{ __('Assigned 3PL (Latest)') }}</th>
              <td    >
                @if($parcel->lastParcel3pl)
                  {{ $parcel->lastParcel3pl->company_name ?? $parcel->lastParcel3pl->parcel_3pl_name }}
                  <br><small class="text-muted">
                    {{ __('Assigned at') }}: {{ optional($parcel->lastParcel3pl->created_at)->format('Y-m-d H:i') }}
                  </small>
                @else
                  {{ __('N/A') }}
                @endif
              </td>
              <td>
                  <img src="{!! $parcel->qrcode_id_print ?? '' !!}"   alt="QR Code" />
              </td>
            </tr>
          </tbody>
        </table>
 

      </div>
    </div>

    {{-- 🚚 3PL History --}}
    <div class="tab-pane fade" id="p3pl" role="tabpanel" aria-labelledby="p3pl-tab">
      @php $p3list = $parcel->parcels_3pl ?? collect(); @endphp
      @if($p3list->count())
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ __('Company') }}</th>
                <th>{{ __('Assigned At') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($p3list as $i => $p3)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $p3->company_name ?? $p3->parcel_3pl_name ?? '-' }}</td>
                  <td>{{ optional($p3->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-muted">{{ __('No 3PL assignments found.') }}</p>
      @endif
    </div>

    {{-- 📜 Status Timeline --}}
    <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
      @php $events = $parcel->parcelEvent ?? collect(); @endphp
      @if($events->count())
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Note') }}</th>
                <th>{{ __('At') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($events->sortByDesc('id') as $k => $ev)
                <tr>
                  <td>{{ $k + 1 }}</td>
                  <td><span class="badge badge-secondary">{{ __('parcelStatus.' . ($ev->status_id ?? $ev->status ?? '')) }}</span></td>
                  <td>{{ $ev->note ?? '-' }}</td>
                  <td>{{ optional($ev->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-muted">{{ __('No status events found.') }}</p>
      @endif
    </div>

    {{-- 🐼 Panda Tracking + Internal Mapping --}}
    @if(isset($pandaTracking) && $pandaTracking)
      <div class="tab-pane fade" id="panda" role="tabpanel" aria-labelledby="panda-tab">
        @if(is_array($pandaTracking) && isset($pandaTracking['error']))
          <div class="alert alert-warning">{{ $pandaTracking['error'] }}</div>
        @else
          @foreach($pandaTracking as $track)
          
            @php $shipment = $track['Shipment'] ?? null; @endphp
            @if($shipment)
              @php
                $awbNum    = $shipment['awb_number'] ?? null;
                $mapCurrent = isset($pandaMappings) ? ($pandaMappings[$awbNum]['current'] ?? null) : null;
              @endphp

              <div class="card mb-3">
                <div class="card-body">
                  <h6 class="mb-3">{{ __('AWB Number') }}: {{ $awbNum ?? '-' }}</h6>

                  {{-- External vs Mapped (Current) --}}
                  <div class="row">
                    <div class="col-md-6 mb-2">
                      <b>{{ __('External (Panda) Status') }}:</b>
                      <span class="badge badge-info">{{ $mapCurrent['external_label'] ?? ($shipment['current_status'] ?? '-') }}</span>
                      <small class="text-muted d-block">{{ __('Updated at') }}: {{ $shipment['status_datetime'] ?? '-' }}</small>
                    </div>
                    <div class="col-md-6 mb-2">
                      <b>{{ __('Mapped Internal Status') }}:</b>
                      @if($mapCurrent && $mapCurrent['parcel_status_id'])
                        <span class="badge badge-success">{{ $mapCurrent['internal_label'] }}</span>
                        @if(!empty($mapCurrent['notes']))
                          <small class="text-muted d-block">{{ $mapCurrent['notes'] }}</small>
                        @endif
                      @else
                        <span class="badge badge-secondary">{{ __('N/A') }}</span>
                      @endif
                      
                           @if (hasPermission('parcel_status_update') == true)
                                                <td>
                                                    @if (
                                                        \App\Enums\ParcelStatus::DELIVERED !== $parcel->status &&
                                                            \App\Enums\ParcelStatus::PARTIAL_DELIVERED !== $parcel->status &&
                                                            \App\Enums\ParcelStatus::RETURN_RECEIVED_BY_MERCHANT !== $parcel->status)
                                                        <div class="input-group mb-3">
                                                            <div class="input-group-prepend be-addon">
                                                                <button tabindex="-1" data-toggle="dropdown"
                                                                    type="button"
                                                                    class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                        class="sr-only">Toggle Dropdown</span></button>
                                                                <div class="dropdown-menu">
                                                                    {!! parcelStatus($parcel) !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @else
                                                        ...
                                                    @endif
                                                </td>
                                            @endif
                    </div>
                  </div>

                  {{-- Address --}}
                  <p class="mt-2 mb-2"><b>{{ __('Address') }}:</b>
                    {{ data_get($shipment, 'ShipmentAddress.address') }},
                    {{ data_get($shipment, 'ShipmentAddress.city') }},
                    {{ data_get($shipment, 'ShipmentAddress.country') }}
                  </p>

                  {{-- AWB PDF --}}
                  @if(!empty($shipment['AwbPdf']))
                    <a href="{{ $shipment['AwbPdf'] }}" target="_blank" class="btn btn-sm btn-outline-primary mb-3">
                      {{ __('Open AWB PDF') }}
                    </a>
                  @endif

                  {{-- Activity with mapped status --}}
                  @php
                    $activity = $shipment['Activity'] ?? [];
                    $mapActs  = isset($pandaMappings) ? ($pandaMappings[$awbNum]['activity'] ?? []) : [];
                  @endphp

                  @if(!empty($activity))
                    <h6>{{ __('Activity Timeline') }}</h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-striped">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>{{ __('Datetime') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Details') }}</th>
                            <th>{{ __('Mapped Status') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($activity as $idx => $act)
                            @php $mapAct = $mapActs[$idx] ?? null; @endphp
                            <tr>
                              <td>{{ $idx + 1 }}</td>
                              <td>{{ $act['datetime'] ?? '-' }}</td>
                              <td>{{ $act['status'] ?? '-' }}</td>
                              <td>{{ $act['details'] ?? '-' }}</td>
                              <td>
                                @if($mapAct && $mapAct['parcel_status_id'])
                                  <span class="badge badge-success">{{ $mapAct['internal_label'] }}</span>
                                  @if(!empty($mapAct['notes']))
                                    <small class="text-muted d-block">{{ $mapAct['notes'] }}</small>
                                  @endif
                                @else
                                  <span class="badge badge-secondary">{{ __('N/A') }}</span>
                                @endif
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @endif
                </div>
              </div>
            @endif
          @endforeach
        @endif
      </div>
     <div class="tab-pane fade" id="awb_pdf" role="tabpanel" aria-labelledby="awb_pdf-tab">
    @if (!empty($awb_pdf))
        <iframe 
            src="{{ $awb_pdf }}" 
            width="100%" 
            height="600px" 
            style="border:none;"
        ></iframe>
    @else
        <p class="text-muted text-center mt-3">{{ __('No PDF available') }}</p>
    @endif
</div>

     
    @endif

  </div>
</div>
